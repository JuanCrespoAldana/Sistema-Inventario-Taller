<form action="{{ route('reparaciones.update', $orden->id) }}" method="POST">

    @csrf
    @method('PUT')

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @php
        $oldMaquinas = old('maquinas', []);
    @endphp

    {{-- ================= MODAL HEADER ================= --}}
    <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
            <i class="bi bi-wrench-adjustable"></i> Editar orden de reparación
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>

    {{-- ================= MODAL BODY ================= --}}
    <div class="modal-body" style="max-height:75vh; overflow-y:auto;">

        {{-- ================= INFORMACIÓN GENERAL ================= --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header fw-bold bg-light">
                <i class="bi bi-info-circle"></i> Información general
            </div>

            <div class="card-body">
                <div class="row g-4">

                    <div class="col-md-3">
                        <small class="text-muted">N° Orden</small>
                        <input type="text" class="form-control" value="{{ $orden->numero_orden }}" readonly>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted">Cliente</small>
                        <input type="text" name="cliente_nombre" class="form-control @error('cliente_nombre') is-invalid @enderror"
                            value="{{ old('cliente_nombre', optional($orden->cliente)->nombre) }}">
                        @error('cliente_nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted">Teléfono</small>
                        <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono', $orden->telefono) }}">
                        @error('telefono')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted">Fecha de entrada</small>
                        <input type="date" name="fecha_entrada" class="form-control @error('fecha_entrada') is-invalid @enderror"
                            value="{{ old('fecha_entrada', $orden->fecha_entrada) }}">
                        @error('fecha_entrada')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- ================= MÁQUINAS ================= --}}
        <div class="card shadow-sm border-0">
            <div class="card-header fw-bold bg-light d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-cpu"></i> Máquinas a reparar
                </span>

                <button type="button" class="btn btn-sm btn-primary btn-agregar-maquina">
                    <i class="bi bi-plus-circle"></i> Agregar máquina
                </button>
            </div>

            <div class="card-body" id="contenedor-maquinas">

                @foreach ($orden->maquinas as $i => $m)

                    <div class="card shadow-sm border-0 mb-4 maquina-item">

                        <div class="card-body">

                            <input type="hidden" name="maquinas[{{ $i }}][id]" value="{{ $m->id }}">

                            {{-- HEADER MAQUINA --}}
                            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                                <h6 class="mb-0 titulo-maquina fw-bold">
                                    <i class="bi bi-hdd"></i>
                                    Máquina {{ chr(65 + $i) }}
                                </h6>

                                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-maquina">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            {{-- DATOS PRINCIPALES --}}
                            <div class="row g-4 mb-4">

                                <div class="col-md-4">
                                    <small class="text-muted">Máquina</small>
                                    <input type="text" name="maquinas[{{ $i }}][descripcion]" class="form-control @error('maquinas.'.$i.'.descripcion') is-invalid @enderror"
                                        value="{{ old('maquinas.'.$i.'.descripcion', $m->descripcion) }}">
                                    @error('maquinas.'.$i.'.descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted">Técnico</small>
                                    <select name="maquinas[{{ $i }}][tecnico_id]" class="form-control @error('maquinas.'.$i.'.tecnico_id') is-invalid @enderror">
                                        <option value="">Sin asignar</option>
                                        @foreach($tecnicos as $t)
                                            <option value="{{ $t->id }}" {{ old('maquinas.'.$i.'.tecnico_id', $m->tecnico_id) == $t->id ? 'selected' : '' }}>
                                                {{ $t->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('maquinas.'.$i.'.tecnico_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted">Estado</small>
                                    <select name="maquinas[{{ $i }}][estado]" class="form-control @error('maquinas.'.$i.'.estado') is-invalid @enderror">
                                        <option value="pendiente" {{ old('maquinas.'.$i.'.estado', $m->estado) == 'pendiente' ? 'selected' : '' }}>
                                            Pendiente
                                        </option>
                                        <option value="en_reparacion" {{ old('maquinas.'.$i.'.estado', $m->estado) == 'en_reparacion' ? 'selected' : '' }}>
                                            En reparación
                                        </option>
                                        <option value="entregada" {{ old('maquinas.'.$i.'.estado', $m->estado) == 'entregada' ? 'selected' : '' }}>
                                            Entregada
                                        </option>
                                    </select>
                                    @error('maquinas.'.$i.'.estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted">¿Garantía?</small>
                                    <select name="maquinas[{{ $i }}][es_garantia]" class="form-control @error('maquinas.'.$i.'.es_garantia') is-invalid @enderror">
                                        <option value="0" {{ old('maquinas.'.$i.'.es_garantia', $m->es_garantia) == 0 ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('maquinas.'.$i.'.es_garantia', $m->es_garantia) == 1 ? 'selected' : '' }}>Sí</option>
                                    </select>
                                    @error('maquinas.'.$i.'.es_garantia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted">Tipo garantía</small>
                                    <input type="text" name="maquinas[{{ $i }}][tipo_garantia]" class="form-control"
                                        value="{{ old('maquinas.'.$i.'.tipo_garantia', $m->tipo_garantia) }}">
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted">Factura</small>
                                    <input type="text" name="maquinas[{{ $i }}][factura]" class="form-control"
                                        value="{{ old('maquinas.'.$i.'.factura', $m->factura) }}">
                                </div>

                                <div class="col-md-4">
                                    <small class="text-muted">Fecha de salida</small>
                                    <input type="date" name="maquinas[{{ $i }}][fecha_salida]" class="form-control"
                                        value="{{ old('maquinas.'.$i.'.fecha_salida', $m->fecha_salida) }}">
                                </div>

                            </div>

                            {{-- COTIZACIÓN --}}
                            <div class="mb-4">
                                <small class="text-muted fw-bold">
                                    <i class="bi bi-clipboard-check"></i> Cotización
                                </small>
                                <textarea name="maquinas[{{ $i }}][cotizacion]" class="form-control mt-2"
                                    rows="3">{{ old('maquinas.'.$i.'.cotizacion', $m->cotizacion) }}</textarea>
                            </div>

                            {{-- COSTOS --}}
                            <div class="row g-4">

                                <div class="col-md-6">
                                    <small class="text-muted">Mano de obra</small>
                                    <div class="input-group mt-1">
                                        <span class="input-group-text">$</span>
                                        <input type="text" name="maquinas[{{ $i }}][mano_obra]" class="form-control dinero"
                                            value="{{ old('maquinas.'.$i.'.mano_obra', number_format((float) $m->mano_obra, 0, ',', '.')) }}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <small class="text-muted">Total</small>
                                    <div class="input-group mt-1">
                                        <span class="input-group-text">$</span>
                                        <input type="text" name="maquinas[{{ $i }}][total]" class="form-control dinero"
                                            value="{{ old('maquinas.'.$i.'.total', number_format((float) $m->total, 0, ',', '.')) }}">
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                @endforeach

            </div>
        </div>

    </div>

    {{-- ================= MODAL FOOTER ================= --}}
    <div class="modal-footer bg-light">
        <button type="submit" class="btn btn-success">
            Guardar cambios
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancelar
        </button>
    </div>

</form>