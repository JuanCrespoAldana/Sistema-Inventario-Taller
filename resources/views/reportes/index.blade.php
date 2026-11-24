@extends('layouts.app')

@section('title', 'Reportes')
@section('page-icon', 'bar-chart-line')
@section('page-title', 'Reportes')
@section('page-subtitle', 'Consulta el rendimiento del negocio por período.')

@section('page-actions')
    @php
        $mesesEs = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
        $periodoActivo = $mesesEs[(int) date('n')] . ' ' . date('Y');
    @endphp
    <span class="badge bg-primary"><i class="bi bi-calendar3"></i> Período activo: {{ $periodoActivo }}</span>
@endsection

@section('content')

    @php
        $nFact = ($ventas ?? collect())->count();
        $nMaq = ($reparaciones ?? collect())->count();
    @endphp

    <div class="section-title"><i class="bi bi-lightning-fill"></i> Resumen del mes actual</div>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-bag-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ventas este mes</div>
                <div class="kpi-value">${{ number_format($totalVentasMes ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-sub">{{ $nFact }} {{ $nFact === 1 ? 'factura emitida' : 'facturas emitidas' }}</div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-tools"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Reparaciones este mes</div>
                <div class="kpi-value">${{ number_format($totalReparacionesMes ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-sub">{{ $nMaq }} {{ $nMaq === 1 ? 'máquina atendida' : 'máquinas atendidas' }}</div>
            </div>
        </div>

        <div class="kpi kpi-amber">
            <div class="kpi-icon"><i class="bi bi-person-gear"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Técnicos con actividad</div>
                <div class="kpi-value">{{ count($productividad ?? []) }}</div>
                <div class="kpi-sub">Con trabajo registrado</div>
            </div>
        </div>

        <div class="kpi kpi-red">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ingreso total del mes</div>
                <div class="kpi-value">${{ number_format($totalGeneral ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-sub">Ventas + reparaciones</div>
            </div>
        </div>
    </div>

    <div class="section-title"><i class="bi bi-grid-fill"></i> Reportes principales</div>

    <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card report-card ">
                    <div class="report-card-top">
                        <div class="kpi-icon"><i class="bi bi-bar-chart-line"></i></div>
                        <div>
                            <h3 class="report-card-title">Reporte de ventas</h3>
                            <p class="report-card-desc">Totales vendidos, descuentos aplicados, margen estimado y comportamiento mensual por factura.</p>
                        </div>
                    </div>
                    <div class="report-card-tags">
                        <span class="report-tag">Facturas</span>
                        <span class="report-tag">Descuentos</span>
                        <span class="report-tag">Margen estimado</span>
                        <span class="report-tag">Por período</span>
                    </div>
                    <div class="report-card-foot">
                        <a href="{{ route('reportes.ventas') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Abrir reporte de ventas
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card report-card kpi-green">
                    <div class="report-card-top">
                        <div class="kpi-icon"><i class="bi bi-tools"></i></div>
                        <div>
                            <h3 class="report-card-title">Reporte de reparaciones</h3>
                            <p class="report-card-desc">Órdenes procesadas, ingresos por mano de obra y análisis de productividad técnica mensual.</p>
                        </div>
                    </div>
                    <div class="report-card-tags">
                        <span class="report-tag">Órdenes</span>
                        <span class="report-tag">Mano de obra</span>
                        <span class="report-tag">Estados</span>
                        <span class="report-tag">Técnicos</span>
                    </div>
                    <div class="report-card-foot">
                        <a href="{{ route('reportes.reparaciones') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Abrir reporte de reparaciones
                        </a>
                    </div>
                </div>
            </div>
    </div>

    <div class="section-title"><i class="bi bi-stars"></i> Reportes especializados</div>

    <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="card report-card kpi-amber">
                    <div class="report-card-top">
                        <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
                        <div>
                            <h3 class="report-card-title">Inventario</h3>
                            <p class="report-card-desc">Valor actual del inventario, control de stock y margen potencial de venta.</p>
                        </div>
                    </div>
                    <div class="report-card-tags">
                        <span class="report-tag">Stock</span>
                        <span class="report-tag">Valoración</span>
                        <span class="report-tag">Alertas</span>
                    </div>
                    <div class="report-card-foot">
                        <a href="{{ route('reportes.inventario') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Abrir inventario
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card report-card kpi-cyan">
                    <div class="report-card-top">
                        <div class="kpi-icon"><i class="bi bi-person-gear"></i></div>
                        <div>
                            <h3 class="report-card-title">Productividad de técnicos</h3>
                            <p class="report-card-desc">Máquinas atendidas e ingresos por técnico. Ranking del período.</p>
                        </div>
                    </div>
                    <div class="report-card-tags">
                        <span class="report-tag">Ranking</span>
                        <span class="report-tag">Ingresos</span>
                        <span class="report-tag">Máquinas</span>
                    </div>
                    <div class="report-card-foot">
                        <a href="{{ route('reportes.tecnicos') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Abrir técnicos
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card report-card kpi-red">
                    <div class="report-card-top">
                        <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <h3 class="report-card-title">Financiero general</h3>
                            <p class="report-card-desc">Consolidado de ingresos por ventas y reparaciones. Vista gerencial.</p>
                        </div>
                    </div>
                    <div class="report-card-tags">
                        <span class="report-tag">Consolidado</span>
                        <span class="report-tag">Gerencial</span>
                        <span class="report-tag">Ingresos</span>
                    </div>
                    <div class="report-card-foot">
                        <a href="{{ route('reportes.financiero') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Abrir financiero
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card report-card kpi-violet">
                    <div class="report-card-top">
                        <div class="kpi-icon"><i class="bi bi-calendar-range"></i></div>
                        <div>
                            <h3 class="report-card-title">Resumen anual</h3>
                            <p class="report-card-desc">Desglose mes a mes de todo el año. Ideal para el contador y el cierre fiscal.</p>
                        </div>
                    </div>
                    <div class="report-card-tags">
                        <span class="report-tag">Anual</span>
                        <span class="report-tag">Contador</span>
                        <span class="report-tag">12 meses</span>
                    </div>
                    <div class="report-card-foot">
                        <a href="{{ route('reportes.resumen') }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Abrir resumen
                        </a>
                    </div>
                </div>
            </div>
    </div>

@endsection
