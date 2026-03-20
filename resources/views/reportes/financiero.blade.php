@extends('layouts.app')
@section('title', 'Reporte Financiero General')

@push('styles')
<style>
    .rpt-header { display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap; }
    .rpt-header-ico { width:48px;height:48px;border-radius:12px;background:#fff1f2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
    .rpt-header h4 { font-weight:800;color:#1e293b;margin:0 0 2px;font-size:1.2rem; }
    .rpt-header small { color:#6c757d;font-size:0.82rem; }

    .fin-grid { display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:22px; }
    @media(max-width:768px){ .fin-grid{grid-template-columns:1fr;} }
    .fin-box { background:#fff;border-radius:14px;padding:24px 22px;border:1px solid #e9ecef;box-shadow:0 2px 8px rgba(0,0,0,0.04);text-align:center;position:relative;overflow:hidden; }
    .fin-box.ventas { border-top:4px solid #2563eb; }
    .fin-box.reparac { border-top:4px solid #16a34a; }
    .fin-box.total { border-top:4px solid #dc2626;background:linear-gradient(135deg,#fff1f2,#fff); }
    .fin-box-ico { font-size:1.8rem;margin-bottom:10px;opacity:0.7; }
    .fin-box-lbl { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#9ca3af;margin-bottom:8px; }
    .fin-box-val { font-size:2rem;font-weight:900;color:#1e293b;line-height:1; }
    .fin-box.total .fin-box-val { color:#dc2626; }
    .fin-box-sub { font-size:0.78rem;color:#6c757d;margin-top:6px; }

    .cmp-section { background:#fff;border-radius:12px;border:1px solid #e9ecef;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:16px; }
    .cmp-title { font-weight:700;font-size:0.92rem;color:#374151;margin-bottom:18px;display:flex;align-items:center;gap:8px; }
    .cmp-row { margin-bottom:16px; }
    .cmp-row:last-child { margin-bottom:0; }
    .cmp-top { display:flex;justify-content:space-between;align-items:center;margin-bottom:6px; }
    .cmp-label { font-size:0.85rem;font-weight:600;color:#374151;display:flex;align-items:center;gap:7px; }
    .cmp-amount { font-size:0.92rem;font-weight:800; }
    .cmp-pct { font-size:0.75rem;color:#9ca3af; }
    .cmp-bar-track { background:#f1f5f9;border-radius:6px;height:10px;overflow:hidden; }
    .cmp-bar-fill { height:100%;border-radius:6px;transition:width 0.6s ease; }

    .rpt-card { background:#fff;border-radius:12px;border:1px solid #e9ecef;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden; }
    .rpt-card-head { padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between; }
    .rpt-card-head span { font-weight:700;font-size:0.88rem;color:#374151; }
    .rpt-table { margin:0; }
    .rpt-table thead tr { background:#f1f5f9; }
    .rpt-table thead th { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#64748b;padding:12px 18px;border:none;border-bottom:1px solid #e2e8f0; }
    .rpt-table tbody tr { border-bottom:1px solid #f1f5f9; }
    .rpt-table td { padding:14px 18px;font-size:0.9rem;color:#374151;vertical-align:middle;border:none; }
    .rpt-table tfoot td { background:#f8fafc;font-weight:800;font-size:0.95rem;padding:16px 18px;border-top:2px solid #e2e8f0;color:#1e293b; }
    @media print { .no-print { display:none!important; } }
</style>
@endpush

@section('content')
<div>

    @php
        $mesesEs = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
        $mesNombre = $mesesEs[(int)$mes] ?? '';
        $v = $totalVentasMes ?? 0;
        $r = $totalReparacionesMes ?? 0;
        $t = $totalGeneral ?? ($v + $r);
        $pctV = $t > 0 ? round(($v / $t) * 100, 1) : 0;
        $pctR = $t > 0 ? round(($r / $t) * 100, 1) : 0;
    @endphp

    <div class="rpt-header">
        <div class="rpt-header-ico"><i class="bi bi-cash-coin"></i></div>
        <div>
            <h4>Resumen Financiero General</h4>
            <small>
                <i class="bi bi-calendar3 me-1"></i>
                {{ $mesNombre }} {{ $anio }} &nbsp;·&nbsp; Vista consolidada gerencial
            </small>
        </div>
    </div>

    @include('reportes.partials.filtro')

    <div class="fin-grid">
        <div class="fin-box ventas">
            <div class="fin-box-ico" style="color:#2563eb"><i class="bi bi-bag-fill"></i></div>
            <div class="fin-box-lbl">Ingresos por Ventas</div>
            <div class="fin-box-val">${{ number_format($v, 0, ',', '.') }}</div>
            <div class="fin-box-sub">{{ $pctV }}% del total &nbsp;·&nbsp; {{ ($ventas ?? collect())->count() }} facturas</div>
        </div>
        <div class="fin-box reparac">
            <div class="fin-box-ico" style="color:#16a34a"><i class="bi bi-tools"></i></div>
            <div class="fin-box-lbl">Ingresos por Reparaciones</div>
            <div class="fin-box-val">${{ number_format($r, 0, ',', '.') }}</div>
            <div class="fin-box-sub">{{ $pctR }}% del total &nbsp;·&nbsp; {{ ($reparaciones ?? collect())->count() }} órdenes</div>
        </div>
        <div class="fin-box total">
            <div class="fin-box-ico" style="color:#dc2626"><i class="bi bi-cash-stack"></i></div>
            <div class="fin-box-lbl">Total General del Período</div>
            <div class="fin-box-val">${{ number_format($t, 0, ',', '.') }}</div>
            <div class="fin-box-sub">Ventas + Reparaciones</div>
        </div>
    </div>

    <div class="cmp-section no-print">
        <div class="cmp-title"><i class="bi bi-bar-chart-fill" style="color:#dc2626"></i> Distribución de Ingresos</div>
        <div class="cmp-row">
            <div class="cmp-top">
                <span class="cmp-label">
                    <span style="width:10px;height:10px;background:#2563eb;border-radius:50%;display:inline-block;"></span>
                    Ventas
                </span>
                <span>
                    <span class="cmp-amount" style="color:#2563eb">${{ number_format($v, 0, ',', '.') }}</span>
                    <span class="cmp-pct ms-2">{{ $pctV }}%</span>
                </span>
            </div>
            <div class="cmp-bar-track">
                <div class="cmp-bar-fill" style="width:{{ $pctV }}%;background:linear-gradient(90deg,#2563eb,#60a5fa);"></div>
            </div>
        </div>
        <div class="cmp-row">
            <div class="cmp-top">
                <span class="cmp-label">
                    <span style="width:10px;height:10px;background:#16a34a;border-radius:50%;display:inline-block;"></span>
                    Reparaciones
                </span>
                <span>
                    <span class="cmp-amount" style="color:#16a34a">${{ number_format($r, 0, ',', '.') }}</span>
                    <span class="cmp-pct ms-2">{{ $pctR }}%</span>
                </span>
            </div>
            <div class="cmp-bar-track">
                <div class="cmp-bar-fill" style="width:{{ $pctR }}%;background:linear-gradient(90deg,#16a34a,#4ade80);"></div>
            </div>
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-head">
            <span><i class="bi bi-file-earmark-bar-graph me-2"></i>Cuadro Financiero Consolidado — {{ $mesNombre }} {{ $anio }}</span>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Participación</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <i class="bi bi-bag-fill me-2" style="color:#2563eb"></i>
                            <strong>Ventas</strong>
                            <span style="font-size:0.78rem;color:#9ca3af;margin-left:8px;">{{ ($ventas ?? collect())->count() }} facturas</span>
                        </td>
                        <td class="text-end" style="font-weight:700;color:#2563eb;">${{ number_format($v, 0, ',', '.') }}</td>
                        <td class="text-end" style="font-size:0.85rem;color:#6c757d;">{{ $pctV }}%</td>
                    </tr>
                    <tr>
                        <td>
                            <i class="bi bi-tools me-2" style="color:#16a34a"></i>
                            <strong>Reparaciones</strong>
                            <span style="font-size:0.78rem;color:#9ca3af;margin-left:8px;">{{ ($reparaciones ?? collect())->count() }} órdenes</span>
                        </td>
                        <td class="text-end" style="font-weight:700;color:#16a34a;">${{ number_format($r, 0, ',', '.') }}</td>
                        <td class="text-end" style="font-size:0.85rem;color:#6c757d;">{{ $pctR }}%</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td><i class="bi bi-cash-stack me-2" style="color:#dc2626"></i>TOTAL GENERAL</td>
                        <td class="text-end" style="color:#dc2626;">${{ number_format($t, 0, ',', '.') }}</td>
                        <td class="text-end">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
