@extends('layouts.app')

@section('title', 'Movimientos')
@section('page-icon', 'arrow-left-right')
@section('page-title', 'Movimientos de inventario')
@section('page-subtitle', 'Entradas, salidas y ajustes de stock registrados.')

@section('page-actions')
    <a href="{{ route('inventario.ajustes.index') }}" class="btn btn-primary">
        <i class="bi bi-sliders"></i> Ajustar stock
    </a>
@endsection

@section('content')

    @php
        $filtrosExtra = ['referencia', 'desde', 'hasta'];
        $activosExtra = collect($filtrosExtra)->filter(fn($f) => request()->filled($f))->count();
        $hayFiltros = request()->anyFilled(['buscar', 'tipo', 'referencia', 'desde', 'hasta']);
        $tipoActual = request('tipo', '');
        $pestanasTipo = [
            '' => 'Todos',
            'entrada' => 'Entradas',
            'salida' => 'Salidas',
            'ajuste' => 'Ajustes',
        ];
    @endphp

    <div class="card filter-card">
        <div class="card-body">
            <form method="GET" action="{{ route('inventario.movimientos.index') }}" autocomplete="off"
                class="d-flex flex-column gap-3">
                <input type="hidden" name="tipo" value="{{ $tipoActual }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-lg-5">
                        <label class="form-label" for="buscar">Producto</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="buscar" name="buscar" value="{{ request('buscar') }}"
                                class="form-control" placeholder="Código o nombre del producto">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="orden">Ordenar por</label>
                        <select id="orden" name="orden" class="form-select" data-auto>
                            <option value="reciente" {{ request('orden', 'reciente') === 'reciente' ? 'selected' : '' }}>Más recientes</option>
                            <option value="antiguo" {{ request('orden') === 'antiguo' ? 'selected' : '' }}>Más antiguos</option>
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            <button type="button"
                                class="btn btn-outline-secondary btn-filtros-extra {{ $activosExtra ? '' : 'collapsed' }}"
                                data-bs-toggle="collapse" data-bs-target="#movimientosFiltrosExtra"
                                aria-expanded="{{ $activosExtra ? 'true' : 'false' }}">
                                Más filtros
                                @if ($activosExtra)
                                    <span class="filtro-count">{{ $activosExtra }}</span>
                                @endif
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            @if ($hayFiltros)
                                <a href="{{ route('inventario.movimientos.index') }}" class="btn btn-outline-secondary"
                                    title="Limpiar filtros">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="chip-row">
                    <span class="chip-label">Tipo</span>
                    @foreach ($pestanasTipo as $valor => $etiqueta)
                        <a href="{{ request()->fullUrlWithQuery(['tipo' => $valor === '' ? null : $valor, 'page' => null]) }}"
                            class="chip {{ (string) $tipoActual === (string) $valor ? 'activo' : '' }}">{{ $etiqueta }}</a>
                    @endforeach

                    <span class="chip-label chip-label-sep">Fecha</span>
                    <button type="button" class="chip" data-rango="hoy">Hoy</button>
                    <button type="button" class="chip" data-rango="semana">Últimos 7 días</button>
                    <button type="button" class="chip" data-rango="mes">Este mes</button>
                    <button type="button" class="chip" data-rango="mes_pasado">Mes pasado</button>
                </div>

                <div class="collapse filtros-extra {{ $activosExtra ? 'show' : '' }}" id="movimientosFiltrosExtra">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="referencia">Referencia</label>
                            <input type="text" id="referencia" name="referencia" value="{{ request('referencia') }}"
                                class="form-control" placeholder="Factura, compra o motivo">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label" for="desde">Desde</label>
                            <input type="date" id="desde" name="desde" value="{{ request('desde') }}" class="form-control">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label" for="hasta">Hasta</label>
                            <input type="date" id="hasta" name="hasta" value="{{ request('hasta') }}" class="form-control">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="result-summary">
        <span><strong>{{ number_format($movimientos->total(), 0, ',', '.') }}</strong> {{ $movimientos->total() == 1 ? 'movimiento' : 'movimientos' }}{{ $hayFiltros ? ' con estos filtros' : '' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Cantidad</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movimientos as $mov)
                    <tr>
                        <td class="text-nowrap">
                            {{ $mov->created_at ? $mov->created_at->format('d/m/Y H:i') : '—' }}
                        </td>

                        <td>
                            <div class="cell-title">{{ $mov->producto->nombre ?? '—' }}</div>
                            <div class="cell-code">{{ $mov->producto->codigo ?? '' }}</div>
                        </td>

                        <td class="text-center">
                            @if ($mov->tipo == 'entrada')
                                <span class="badge bg-success"><i class="bi bi-arrow-down-circle"></i> Entrada</span>
                            @elseif ($mov->tipo == 'salida')
                                <span class="badge bg-danger"><i class="bi bi-arrow-up-circle"></i> Salida</span>
                            @else
                                <span class="badge bg-warning"><i class="bi bi-pencil-square"></i> Ajuste</span>
                            @endif
                        </td>

                        <td class="text-center fw-bold">
                            @if ($mov->tipo == 'entrada')
                                <span class="text-success">+{{ $mov->cantidad }}</span>
                            @elseif ($mov->tipo == 'salida')
                                <span class="text-danger">−{{ $mov->cantidad }}</span>
                            @else
                                {{ $mov->cantidad }}
                            @endif
                        </td>

                        <td>{{ $mov->referencia ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <strong>No hay movimientos para mostrar</strong>
                                <span>{{ $hayFiltros ? 'Prueba con otros filtros.' : 'Los movimientos de stock aparecerán aquí.' }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $movimientos->links() }}
    </div>

@endsection
