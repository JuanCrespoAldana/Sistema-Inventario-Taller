@extends('layouts.app')

@section('title', 'Módulo de Reportes')

@push('styles')
<style>
    .reportes-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 16px;
        padding: 32px 36px;
        color: white;
        margin-bottom: 26px;
        position: relative;
        overflow: hidden;
    }
    .reportes-hero::after {
        content: '\F34F';
        font-family: 'Bootstrap Icons';
        position: absolute;
        right: 36px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 110px;
        opacity: 0.05;
        color: white;
        pointer-events: none;
    }
    .reportes-hero h2 { font-weight: 800; font-size: 1.7rem; margin-bottom: 6px; }
    .reportes-hero p  { opacity: 0.72; margin: 0; font-size: 0.93rem; }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.13);
        border: 1px solid rgba(255,255,255,0.22);
        border-radius: 20px;
        padding: 5px 14px;
        font-size: 0.82rem;
        margin-top: 14px;
        font-weight: 500;
    }

    /* KPIs */
    .kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 26px; }
    @media(max-width:992px){ .kpi-row{grid-template-columns:repeat(2,1fr);} }
    @media(max-width:576px){ .kpi-row{grid-template-columns:1fr;} }

    .kpi-box {
        background: #fff;
        border-radius: 12px;
        padding: 18px 20px;
        border: 1px solid #e9ecef;
        border-left: 4px solid;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
    }
    .kpi-box.c-blue  { border-left-color: #2563eb; }
    .kpi-box.c-green { border-left-color: #16a34a; }
    .kpi-box.c-amber { border-left-color: #d97706; }
    .kpi-box.c-red   { border-left-color: #dc2626; }
    .kpi-ico {
        position: absolute; right: 16px; top: 50%;
        transform: translateY(-50%);
        font-size: 2rem; opacity: 0.1;
    }
    .kpi-lbl { font-size: 0.69rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; }
    .kpi-val { font-size: 1.6rem; font-weight: 800; color: #1e293b; line-height: 1.1; margin: 4px 0 2px; }
    .kpi-hint { font-size: 0.76rem; color: #6c757d; }

    /* Report Cards */
    .rc {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: all 0.25s;
    }
    .rc:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(0,0,0,0.09); }
    .rc-top { padding: 22px 22px 12px; display: flex; gap: 14px; align-items: flex-start; }
    .rc-ico {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; flex-shrink: 0;
    }
    .rc-ico.ib { background:#eff6ff; color:#2563eb; }
    .rc-ico.ig { background:#f0fdf4; color:#16a34a; }
    .rc-ico.ia { background:#fffbeb; color:#d97706; }
    .rc-ico.it { background:#f0fdfa; color:#0d9488; }
    .rc-ico.ir { background:#fff1f2; color:#dc2626; }
    .rc-title { font-weight: 700; font-size: 0.98rem; color: #1e293b; margin: 0 0 4px; }
    .rc-desc  { font-size: 0.81rem; color: #6c757d; line-height: 1.5; margin: 0; }
    .rc-tags  { padding: 0 22px 12px; display: flex; flex-wrap: wrap; gap: 5px; }
    .rc-tag   { font-size: 0.68rem; font-weight: 600; padding: 2px 9px; border-radius: 20px; background: #f1f5f9; color: #475569; }
    .rc-foot  { padding: 12px 22px 20px; margin-top: auto; }
    .btn-go {
        display: flex; align-items: center; justify-content: center; gap: 7px;
        width: 100%; padding: 9px 14px;
        border-radius: 8px; font-weight: 600; font-size: 0.85rem;
        text-decoration: none; border: none; transition: filter 0.2s;
    }
    .btn-go:hover { filter: brightness(1.1); color: white; }
    .bg-ib { background:#2563eb; color:white; }
    .bg-ig { background:#16a34a; color:white; }
    .bg-ia { background:#d97706; color:white; }
    .bg-it { background:#0d9488; color:white; }
    .bg-ir { background:#dc2626; color:white; }

    .section-lbl {
        font-size: 0.7rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1px;
        color: #9ca3af; margin-bottom: 12px; margin-top: 6px;
    }
</style>
@endpush

@section('content')
<div>

    {{-- HERO --}}
    <div class="reportes-hero">
        <h2><i class="bi bi-bar-chart-fill me-2"></i>Módulo de Reportes</h2>
        <p>Centro de análisis e inteligencia operativa. Consulta el rendimiento del negocio por período.</p>
        @php
            $mesesEs = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
            $mesActual = (int) date('n');
            $anioActual = date('Y');
            $periodoActivo = ($mesesEs[$mesActual] ?? date('F')) . ' ' . $anioActual;
        @endphp
        <span class="hero-badge">
            <i class="bi bi-calendar3"></i>
            Período activo: <strong>{{ $periodoActivo }}</strong>
        </span>
    </div>

    {{-- KPIs DEL MES --}}
    <div class="section-lbl"><i class="bi bi-lightning-fill me-1"></i>Resumen del mes actual</div>
    <div class="kpi-row">
        <div class="kpi-box c-blue">
            <i class="bi bi-bag-fill kpi-ico"></i>
            <div class="kpi-lbl">Ventas este mes</div>
            <div class="kpi-val">${{ number_format($totalVentasMes ?? 0, 0, ',', '.') }}</div>
            <div class="kpi-hint">{{ ($ventas ?? collect())->count() }} facturas emitidas</div>
        </div>
        <div class="kpi-box c-green">
            <i class="bi bi-tools kpi-ico"></i>
            <div class="kpi-lbl">Reparaciones este mes</div>
            <div class="kpi-val">${{ number_format($totalReparacionesMes ?? 0, 0, ',', '.') }}</div>
            <div class="kpi-hint">{{ ($reparaciones ?? collect())->count() }} máquinas atendidas</div>
        </div>
        <div class="kpi-box c-amber">
            <i class="bi bi-person-gear kpi-ico"></i>
            <div class="kpi-lbl">Técnicos con actividad</div>
            <div class="kpi-val">{{ count($productividad ?? []) }}</div>
            <div class="kpi-hint">Con trabajo registrado</div>
        </div>
        <div class="kpi-box c-red">
            <i class="bi bi-cash-stack kpi-ico"></i>
            <div class="kpi-lbl">Ingreso total mes</div>
            <div class="kpi-val">${{ number_format($totalGeneral ?? 0, 0, ',', '.') }}</div>
            <div class="kpi-hint">Ventas + Reparaciones</div>
        </div>
    </div>

    {{-- REPORTES PRINCIPALES --}}
    <div class="section-lbl"><i class="bi bi-grid-fill me-1"></i>Reportes principales</div>
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="rc">
                <div class="rc-top">
                    <div class="rc-ico ib"><i class="bi bi-bar-chart-line"></i></div>
                    <div>
                        <p class="rc-title">Reporte de Ventas</p>
                        <p class="rc-desc">Totales vendidos, descuentos aplicados, margen estimado y comportamiento mensual por factura.</p>
                    </div>
                </div>
                <div class="rc-tags">
                    <span class="rc-tag">Facturas</span>
                    <span class="rc-tag">Descuentos</span>
                    <span class="rc-tag">Margen estimado</span>
                    <span class="rc-tag">Por período</span>
                </div>
                <div class="rc-foot">
                    <a href="{{ route('reportes.ventas') }}" class="btn-go bg-ib">
                        <i class="bi bi-arrow-right-circle-fill"></i> Abrir Reporte de Ventas
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="rc">
                <div class="rc-top">
                    <div class="rc-ico ig"><i class="bi bi-tools"></i></div>
                    <div>
                        <p class="rc-title">Reporte de Reparaciones</p>
                        <p class="rc-desc">Órdenes procesadas, ingresos por mano de obra y análisis de productividad técnica mensual.</p>
                    </div>
                </div>
                <div class="rc-tags">
                    <span class="rc-tag">Órdenes</span>
                    <span class="rc-tag">Mano de obra</span>
                    <span class="rc-tag">Estados</span>
                    <span class="rc-tag">Técnicos</span>
                </div>
                <div class="rc-foot">
                    <a href="{{ route('reportes.reparaciones') }}" class="btn-go bg-ig">
                        <i class="bi bi-arrow-right-circle-fill"></i> Abrir Reporte de Reparaciones
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- REPORTES ESPECIALIZADOS --}}
    <div class="section-lbl"><i class="bi bi-stars me-1"></i>Reportes especializados</div>
    <div class="row g-3">
        <div class="col-md-3">
            <div class="rc">
                <div class="rc-top">
                    <div class="rc-ico ia"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <p class="rc-title">Inventario</p>
                        <p class="rc-desc">Valor actual del inventario, control de stock y margen potencial de venta.</p>
                    </div>
                </div>
                <div class="rc-tags">
                    <span class="rc-tag">Stock</span>
                    <span class="rc-tag">Valoración</span>
                    <span class="rc-tag">Alertas</span>
                </div>
                <div class="rc-foot">
                    <a href="{{ route('reportes.inventario') }}" class="btn-go bg-ia">
                        <i class="bi bi-arrow-right-circle-fill"></i> Abrir Inventario
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rc">
                <div class="rc-top">
                    <div class="rc-ico it"><i class="bi bi-person-gear"></i></div>
                    <div>
                        <p class="rc-title">Productividad Técnicos</p>
                        <p class="rc-desc">Máquinas atendidas e ingresos por técnico. Ranking del período.</p>
                    </div>
                </div>
                <div class="rc-tags">
                    <span class="rc-tag">Ranking</span>
                    <span class="rc-tag">Ingresos</span>
                    <span class="rc-tag">Máquinas</span>
                </div>
                <div class="rc-foot">
                    <a href="{{ route('reportes.tecnicos') }}" class="btn-go bg-it">
                        <i class="bi bi-arrow-right-circle-fill"></i> Abrir Técnicos
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rc">
                <div class="rc-top">
                    <div class="rc-ico ir"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <p class="rc-title">Financiero General</p>
                        <p class="rc-desc">Consolidado de ingresos por ventas y reparaciones. Vista gerencial.</p>
                    </div>
                </div>
                <div class="rc-tags">
                    <span class="rc-tag">Consolidado</span>
                    <span class="rc-tag">Gerencial</span>
                    <span class="rc-tag">Ingresos</span>
                </div>
                <div class="rc-foot">
                    <a href="{{ route('reportes.financiero') }}" class="btn-go bg-ir">
                        <i class="bi bi-arrow-right-circle-fill"></i> Abrir Financiero
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rc">
                <div class="rc-top">
                    <div class="rc-ico" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-calendar-range"></i></div>
                    <div>
                        <p class="rc-title">Resumen</p>
                        <p class="rc-desc">Desglose mes a mes de todo el año. Ideal para el contador y cierre fiscal.</p>
                    </div>
                </div>
                <div class="rc-tags">
                    <span class="rc-tag">Anual</span>
                    <span class="rc-tag">Contador</span>
                    <span class="rc-tag">12 meses</span>
                </div>
                <div class="rc-foot">
                    <a href="{{ route('reportes.resumen') }}" class="btn-go" style="background:#7c3aed;color:white;">
                        <i class="bi bi-arrow-right-circle-fill"></i> Abrir Resumen
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
