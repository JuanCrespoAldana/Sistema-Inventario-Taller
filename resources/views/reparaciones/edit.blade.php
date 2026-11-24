@php
    $maquinasForm = $orden->maquinas->map(fn($m) => [
        'id' => $m->id,
        'descripcion' => $m->descripcion,
        'tecnico_id' => $m->tecnico_id,
        'tecnico_nombre' => optional($m->tecnico)->nombre,
        'estado' => $m->estado,
        'es_garantia' => $m->es_garantia,
        'tipo_garantia' => $m->tipo_garantia,
        'factura' => $m->factura,
        'cotizacion' => $m->cotizacion,
        'fecha_salida' => optional($m->fecha_salida)->format('Y-m-d'),
        'mano_obra' => $m->mano_obra,
        'total' => $m->total,
    ])->values();
@endphp

<form action="{{ route('reparaciones.update', $orden->id) }}" method="POST" data-sin-bloqueo>
    @csrf
    @method('PUT')

    <div class="modal-header">
        <div class="modal-heading">
            <h5 class="modal-title">
                <i class="bi bi-pencil-square"></i> Editar orden {{ $orden->numero_orden }}
            </h5>
            <p class="modal-subtitle">
                {{ optional($orden->cliente)->nombre ?? 'Sin cliente' }} ·
                {{ $orden->maquinas->count() }} {{ $orden->maquinas->count() === 1 ? 'máquina' : 'máquinas' }}
            </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>

    <div class="modal-body">

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-people"></i> Datos del cliente y de la orden</span></div>
            <div class="form-block-body">
                <div class="fields-grid">
                    <div>
                        <label class="form-label">N° de orden</label>
                        <input type="text" class="form-control" value="{{ $orden->numero_orden }}" readonly>
                    </div>

                    <div class="span-2">
                        <label class="form-label" for="edit-cliente">Cliente</label>
                        <input type="text" id="edit-cliente" name="cliente_nombre" class="form-control"
                            value="{{ optional($orden->cliente)->nombre }}" maxlength="255" required>
                    </div>

                    <div>
                        <label class="form-label" for="edit-telefono">Teléfono</label>
                        <input type="text" id="edit-telefono" name="telefono" class="form-control"
                            value="{{ $orden->telefono }}" maxlength="50">
                    </div>

                    <div>
                        <label class="form-label" for="edit-fecha">Fecha de entrada</label>
                        <input type="date" id="edit-fecha" name="fecha_entrada" class="form-control"
                            value="{{ optional($orden->fecha_entrada)->format('Y-m-d') }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 mt-4">
            <div class="section-title my-0"><i class="bi bi-cpu"></i> Máquinas de la orden</div>
            <button type="button" class="btn btn-primary btn-sm btn-agregar-maquina">
                <i class="bi bi-plus-circle"></i> Agregar máquina
            </button>
        </div>

        <div id="contenedor-maquinas" class="d-grid gap-3">
            @foreach ($maquinasForm as $i => $m)
                @include('reparaciones.partials.maquina', ['m' => $m, 'i' => $i, 'letra' => chr(65 + $i)])
            @endforeach
        </div>

        <template id="plantilla-maquina">
            @include('reparaciones.partials.maquina', ['m' => [], 'i' => '__INDEX__', 'letra' => 'A'])
        </template>

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-lg"></i> Guardar cambios
        </button>
    </div>
</form>
