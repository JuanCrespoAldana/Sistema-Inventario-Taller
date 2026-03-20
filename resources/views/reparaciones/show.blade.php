{{-- ================= MODAL HEADER ================= --}}
<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="bi bi-wrench-adjustable"></i> Orden de reparación
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

{{-- ================= MODAL BODY ================= --}}
<div class="modal-body">

    {{-- ================= INFORMACIÓN GENERAL ================= --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold bg-light">
            <i class="bi bi-info-circle"></i> Información general
        </div>

        <div class="card-body row g-3">

            <div class="col-md-3">
                <small class="text-muted">N° Orden</small>
                <div class="fw-bold fs-6">
                    {{ $orden->numero_orden ?? '—' }}
                </div>
            </div>

            <div class="col-md-3">
                <small class="text-muted">Cliente</small>
                <div class="fw-bold">
                    {{ optional($orden->cliente)->nombre ?? '—' }}
                </div>
            </div>

            <div class="col-md-3">
                <small class="text-muted">Teléfono</small>
                <div class="fw-bold">
                    {{ $orden->telefono ?? '—' }}
                </div>
            </div>

            <div class="col-md-3">
                <small class="text-muted">Fecha de entrada</small>
                <div class="fw-bold">
                    {{ $orden->fecha_entrada
                        ? \Carbon\Carbon::parse($orden->fecha_entrada)->format('d/m/Y')
                        : '—'
                    }}
                </div>
            </div>

        </div>
    </div>

    {{-- ================= MÁQUINAS ================= --}}
    <div class="card shadow-sm">
        <div class="card-header fw-bold bg-light">
            <i class="bi bi-cpu"></i> Máquinas a reparar
        </div>

        <div class="card-body">

            @forelse ($orden->maquinas as $i => $m)
                <div class="border rounded p-3 mb-4">

                    {{-- HEADER MÁQUINA --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">
                            <i class="bi bi-hdd"></i>
                            {{ $m->etiqueta_maquina ?? chr(65 + $i) }}
                        </h6>

                    </div>

                    {{-- INFO GENERAL --}}
                    <div class="row g-3 mb-3">

                        <div class="col-md-4">
                            <small class="text-muted">Maquina</small>
                            <div    class="fw-bold">
                                {{ $m->descripcion ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted">Técnico</small>
                            <div class="fw-bold">
                                {{ optional($m->tecnico)->nombre ?? 'Sin asignar' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted">Estado</small>
                            <div class="fw-bold">
                                {{ ucfirst(str_replace('_', ' ', $m->estado)) }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted">¿Garantía?</small>
                            <div class="fw-bold">
                                {{ $m->es_garantia ? 'Sí' : 'No' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted">Tipo garantía</small>
                            <div class="fw-bold">
                                {{ $m->tipo_garantia ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted">Factura</small>
                            <div class="fw-bold">
                                {{ $m->factura ?? '—' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted">Fecha de salida</small>
                            <div class="fw-bold">
                                {{ $m->fecha_salida
                                    ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y')
                                    : 'F/A'
                                }}
                            </div>
                        </div>

                    </div>

                    {{-- ================= COTIZACIÓN (TEXTO) ================= --}}
                    <div class="mb-3">
                        <small class="text-muted fw-bold">
                            <i class="bi bi-clipboard-check"></i> Cotización
                        </small>

                        <div class="border rounded bg-light p-3 mt-1 fw-bold"
                             style="white-space: pre-line;">
                            {{ $m->cotizacion ?? 'Sin detalles de reparación.' }}
                        </div>
                    </div>

                    {{-- ================= RESUMEN ECONÓMICO ================= --}}
                    <div class="row mt-3">

                        <div class="col-md-6">
                            <small class="text-muted">Mano de obra</small>
                            <div class="fw-bold">
                                ${{ number_format((float)($m->mano_obra ?? 0), 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Total</small>
                            <div class="fw-bold text-success">
                                ${{ number_format((float)($m->total ?? 0), 0, ',', '.') }}
                            </div>
                        </div>

                    </div>

                </div>
            @empty
                <div class="text-muted text-center">
                    No hay máquinas registradas.
                </div>
            @endforelse

        </div>
    </div>

</div>

{{-- ================= MODAL FOOTER ================= --}}
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        Cerrar
    </button>
</div>
