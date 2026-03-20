<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">

        <form method="GET" action="{{ route('reparaciones.index') }}">

            {{-- FILA 1 --}}
            <div class="row g-4 mb-4">

                <div class="col-lg-2">
                    <label class="form-label">N° Orden</label>
                    <input type="text" name="numero_orden" class="form-control" value="{{ request('numero_orden') }}">
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Cliente</label>
                    <input type="text" name="cliente" class="form-control" value="{{ request('cliente') }}">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ request('telefono') }}">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Técnico</label>
                    <select name="tecnico_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($tecnicos as $t)
                            <option value="{{ $t->id }}" {{ request('tecnico_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>
                            Pendiente
                        </option>
                        <option value="en_reparacion" {{ request('estado') == 'en_reparacion' ? 'selected' : '' }}>
                            En reparación
                        </option>
                        <option value="entregada" {{ request('estado') == 'entregada' ? 'selected' : '' }}>
                            Entregada
                        </option>
                    </select>
                </div>

            </div>

            {{-- FILA 2 --}}
            <div class="row g-4 align-items-end">

                <div class="col-lg-2">
                    <label class="form-label">Salida desde</label>
                    <input type="date" name="desde" class="form-control" value="{{ request('desde') }}">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Salida hasta</label>
                    <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}">
                </div>

                {{-- ESPACIO --}}
                <div class="col-lg-4"></div>

                {{-- BOTONES --}}
                <div class="col-lg-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('reparaciones.index') }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-1"></i> Limpiar
                    </a>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>