@php
    $etiquetaEstado = [
        'pendiente' => 'Pendiente',
        'en_reparacion' => 'En reparación',
        'entregada' => 'Entregada',
    ];
@endphp

<div class="modal-header">
    <div class="modal-heading">
        <h5 class="modal-title">
            <i class="bi bi-wrench-adjustable"></i> Orden de reparación {{ $orden->numero_orden }}
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
                    <span class="info-label">N° de orden</span>
                    <div class="info-value">{{ $orden->numero_orden ?? '—' }}</div>
                </div>

                <div class="span-2">
                    <span class="info-label">Cliente</span>
                    <div class="info-value">{{ optional($orden->cliente)->nombre ?? '—' }}</div>
                </div>

                <div>
                    <span class="info-label">Teléfono</span>
                    <div class="info-value">{{ $orden->telefono ?: '—' }}</div>
                </div>

                <div>
                    <span class="info-label">Fecha de entrada</span>
                    <div class="info-value">
                        {{ $orden->fecha_entrada ? \Carbon\Carbon::parse($orden->fecha_entrada)->format('d/m/Y') : '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-title mt-4 mb-3"><i class="bi bi-cpu"></i> Máquinas de la orden</div>

    <div class="d-grid gap-3">
        @forelse ($orden->maquinas as $i => $m)
            <div class="maquina-card" data-estado="{{ $m->estado }}">

                <div class="maquina-card-head">
                    <div class="maquina-card-title">
                        <span class="maquina-letra-chip">{{ $m->etiqueta_maquina ?? chr(65 + $i) }}</span>
                        <strong>{{ $m->descripcion ?? '—' }}</strong>
                    </div>
                    <span class="badge maquina-estado-pill">
                        {{ $etiquetaEstado[$m->estado] ?? ucfirst(str_replace('_', ' ', $m->estado)) }}
                    </span>
                </div>

                <div class="maquina-card-body">

                    <div class="form-group-title"><i class="bi bi-cpu"></i> Equipo y seguimiento</div>

                    <div class="fields-grid">
                        <div>
                            <span class="info-label">Técnico</span>
                            <div class="info-value">{{ optional($m->tecnico)->nombre ?? 'Sin asignar' }}</div>
                        </div>

                        <div>
                            <span class="info-label">Fecha de salida</span>
                            <div class="info-value">
                                {{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : '—' }}
                            </div>
                        </div>

                        <div>
                            <span class="info-label">N° de factura</span>
                            <div class="info-value">{{ $m->factura ?: '—' }}</div>
                        </div>

                        <div>
                            <span class="info-label">Garantía</span>
                            <div class="info-value">
                                {{ $m->es_garantia ? ($m->tipo_garantia ?: 'Sí') : 'No' }}
                            </div>
                        </div>
                    </div>

                    <div class="form-group-title"><i class="bi bi-clipboard-check"></i> Cotización</div>

                    <div class="cotizacion-box">{{ $m->cotizacion ?: 'Sin detalles de reparación.' }}</div>

                    <div class="form-group-title"><i class="bi bi-cash-coin"></i> Valores</div>

                    <div class="fields-grid">
                        <div>
                            <span class="info-label">Mano de obra</span>
                            <div class="info-value">${{ number_format((float) ($m->mano_obra ?? 0), 0, ',', '.') }}</div>
                        </div>

                        <div>
                            <span class="info-label">Total a cobrar</span>
                            <div class="info-value info-total">${{ number_format((float) ($m->total ?? 0), 0, ',', '.') }}</div>
                        </div>
                    </div>

                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="bi bi-cpu"></i>
                <strong>No hay máquinas registradas</strong>
            </div>
        @endforelse
    </div>

</div>

<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
