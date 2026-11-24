@php
    $filtrosExtra = ['telefono', 'maquina', 'garantia', 'antiguedad', 'desde', 'hasta', 'entrada_desde', 'entrada_hasta'];
    $activosExtra = collect($filtrosExtra)->filter(fn($f) => request()->filled($f))->count();
    $hayFiltros = request()->anyFilled(array_merge(['numero_orden', 'cliente', 'tecnico_id', 'estado'], $filtrosExtra));
    $estadoActual = request('estado', '');
    $pestanasEstado = [
        '' => ['Todas', $totalOrdenes ?? 0],
        'pendiente' => ['Pendientes', $conteoEstados['pendiente'] ?? 0],
        'en_reparacion' => ['En reparación', $conteoEstados['en_reparacion'] ?? 0],
        'entregada' => ['Entregadas', $conteoEstados['entregada'] ?? 0],
    ];
@endphp

<div class="card filter-card">
    <div class="card-body">
        <form method="GET" action="{{ route('reparaciones.index') }}" autocomplete="off" class="d-flex flex-column gap-3">
            <input type="hidden" name="estado" value="{{ $estadoActual }}">

            <div class="row g-3 align-items-end">

                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="f-numero-orden">N° de orden</label>
                    <input type="text" id="f-numero-orden" name="numero_orden" class="form-control"
                        value="{{ request('numero_orden') }}" placeholder="Ej: 1520">
                </div>

                <div class="col-md-8 col-lg-3">
                    <label class="form-label" for="f-cliente">Cliente</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="f-cliente" name="cliente" class="form-control"
                            value="{{ request('cliente') }}" placeholder="Nombre del cliente">
                    </div>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="form-label" for="f-tecnico">Técnico</label>
                    <select id="f-tecnico" name="tecnico_id" class="form-select" data-auto>
                        <option value="">Todos</option>
                        @foreach ($tecnicos as $t)
                            <option value="{{ $t->id }}" {{ request('tecnico_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="form-label" for="f-orden">Ordenar por</label>
                    <select id="f-orden" name="orden" class="form-select" data-auto>
                        <option value="orden_desc" {{ request('orden', 'orden_desc') == 'orden_desc' ? 'selected' : '' }}>
                            Mayor N° primero
                        </option>
                        <option value="orden_asc" {{ request('orden') == 'orden_asc' ? 'selected' : '' }}>
                            Menor N° primero
                        </option>
                        <option value="reciente" {{ request('orden') == 'reciente' ? 'selected' : '' }}>
                            Más reciente
                        </option>
                    </select>
                </div>

                <div class="col-lg-3">
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <button type="button"
                            class="btn btn-outline-secondary btn-filtros-extra {{ $activosExtra ? '' : 'collapsed' }}"
                            data-bs-toggle="collapse" data-bs-target="#reparacionesFiltrosExtra"
                            aria-expanded="{{ $activosExtra ? 'true' : 'false' }}">
                            Más filtros
                            @if ($activosExtra)
                                <span class="filtro-count">{{ $activosExtra }}</span>
                            @endif
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        @if ($hayFiltros)
                            <a href="{{ route('reparaciones.index') }}" class="btn btn-outline-secondary"
                                title="Limpiar filtros">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>

            </div>

            <div class="chip-row">
                <span class="chip-label">Estado</span>
                @foreach ($pestanasEstado as $valor => $datos)
                    <a href="{{ request()->fullUrlWithQuery(['estado' => $valor === '' ? null : $valor, 'page' => null]) }}"
                        class="chip {{ (string) $estadoActual === (string) $valor ? 'activo' : '' }}">
                        {{ $datos[0] }} <span class="chip-count">{{ number_format($datos[1], 0, ',', '.') }}</span>
                    </a>
                @endforeach

                <span class="chip-label chip-label-sep">Salida</span>
                <button type="button" class="chip" data-rango="hoy">Hoy</button>
                <button type="button" class="chip" data-rango="semana">Últimos 7 días</button>
                <button type="button" class="chip" data-rango="mes">Este mes</button>
                <button type="button" class="chip" data-rango="mes_pasado">Mes pasado</button>
            </div>

            <div class="collapse filtros-extra {{ $activosExtra ? 'show' : '' }}" id="reparacionesFiltrosExtra">
                <div class="row g-3">

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="f-telefono">Teléfono</label>
                        <input type="text" id="f-telefono" name="telefono" class="form-control"
                            value="{{ request('telefono') }}" placeholder="Número del cliente">
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="f-maquina">Máquina o equipo</label>
                        <input type="text" id="f-maquina" name="maquina" class="form-control"
                            value="{{ request('maquina') }}" placeholder="Descripción de la máquina">
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label" for="f-garantia">Garantía</label>
                        <select id="f-garantia" name="garantia" class="form-select">
                            <option value="">Todas</option>
                            <option value="1" {{ request('garantia') === '1' ? 'selected' : '' }}>Con garantía</option>
                            <option value="0" {{ request('garantia') === '0' ? 'selected' : '' }}>Sin garantía</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label" for="f-antiguedad">Tiempo en el taller</label>
                        <select id="f-antiguedad" name="antiguedad" class="form-select">
                            <option value="">Cualquiera</option>
                            @foreach ([7 => 'Más de 7 días', 15 => 'Más de 15 días', 30 => 'Más de 30 días'] as $dias => $etiqueta)
                                <option value="{{ $dias }}" {{ (string) request('antiguedad') === (string) $dias ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="f-desde">Salida desde</label>
                        <input type="date" id="f-desde" name="desde" class="form-control" value="{{ request('desde') }}">
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="f-hasta">Salida hasta</label>
                        <input type="date" id="f-hasta" name="hasta" class="form-control" value="{{ request('hasta') }}">
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="f-entrada-desde">Entrada desde</label>
                        <input type="date" id="f-entrada-desde" name="entrada_desde" class="form-control"
                            value="{{ request('entrada_desde') }}">
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label" for="f-entrada-hasta">Entrada hasta</label>
                        <input type="date" id="f-entrada-hasta" name="entrada_hasta" class="form-control"
                            value="{{ request('entrada_hasta') }}">
                    </div>

                </div>
            </div>

        </form>
    </div>
</div>
