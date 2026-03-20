@extends('layouts.app')
@section('title', 'Resumen Anual')

@push('styles')
<style>
    .rpt-header { display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap; }
    .rpt-header-ico { width:48px;height:48px;border-radius:12px;background:#f5f3ff;color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
    .rpt-header h4 { font-weight:800;color:#1e293b;margin:0 0 2px;font-size:1.2rem; }
    .rpt-header small { color:#6c757d;font-size:0.82rem; }

    .anio-selector { background:#fff;border:1px solid #e9ecef;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;box-shadow:0 1px 4px rgba(0,0,0,0.04); }
    .anio-selector label { font-size:0.75rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px; }

    .totales-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px; }
    @media(max-width:768px){ .totales-grid{grid-template-columns:1fr;} }
    .tot-box { background:#fff;border-radius:12px;padding:20px;border:1px solid #e9ecef;box-shadow:0 1px 4px rgba(0,0,0,0.04);text-align:center;border-top:4px solid; }
    .tot-box.azul { border-top-color:#2563eb; }
    .tot-box.verde { border-top-color:#16a34a; }
    .tot-box.rojo { border-top-color:#dc2626;background:linear-gradient(135deg,#fff8f8,#fff); }
    .tot-lbl { font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#9ca3af;margin-bottom:6px; }
    .tot-val { font-size:1.9rem;font-weight:900;color:#1e293b;line-height:1; }
    .tot-box.rojo .tot-val { color:#dc2626; }
    .tot-sub { font-size:0.77rem;color:#6c757d;margin-top:5px; }

    .rpt-card { background:#fff;border-radius:12px;border:1px solid #e9ecef;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;margin-bottom:16px; }
    .rpt-card-head { padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px; }
    .rpt-card-head span { font-weight:700;font-size:0.88rem;color:#374151; }

    .rpt-table { margin:0; }
    .rpt-table thead tr { background:#f1f5f9; }
    .rpt-table thead th { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#64748b;padding:11px 16px;border:none;border-bottom:1px solid #e2e8f0; }
    .rpt-table tbody tr { border-bottom:1px solid #f1f5f9;transition:background 0.15s; }
    .rpt-table tbody tr:hover { background:#f8fafc; }
    .rpt-table tbody tr.mes-actual { background:#fffbeb; }
    .rpt-table td { padding:12px 16px;font-size:0.88rem;color:#374151;vertical-align:middle;border:none; }
    .rpt-table tfoot td { background:#f8fafc;font-weight:800;font-size:0.92rem;padding:14px 16px;border-top:2px solid #e2e8f0;color:#1e293b; }

    .bar-v { display:inline-block;height:10px;border-radius:3px;background:linear-gradient(90deg,#2563eb,#60a5fa);vertical-align:middle; }
    .bar-r { display:inline-block;height:10px;border-radius:3px;background:linear-gradient(90deg,#16a34a,#4ade80);vertical-align:middle; }
    .mes-cero { color:#cbd5e1;font-style:italic; }
    .badge-mes { display:inline-block;background:#fef9c3;color:#854d0e;border-radius:4px;padding:1px 7px;font-size:0.7rem;font-weight:700;margin-left:6px; }

    @media print {
        .no-print { display:none!important; }
        body { font-size:11px; }
        .rpt-table td, .rpt-table th { padding:7px 10px; }
        .bar-v, .bar-r { display:none; }
    }
</style>
@endpush

@section('content')
<div>

    @php
        $mesesEs = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
        $mesActual = (int) date('m');
        $anioActual = (int) date('Y');
        $maxMes = collect($resumen)->max('total_mes');
    @endphp

    <div class="rpt-header">
        <div class="rpt-header-ico"><i class="bi bi-calendar-range"></i></div>
        <div>
            <h4>Resumen Anual — {{ $anio }}</h4>
            <small>
                <i class="bi bi-building me-1"></i>
                Vista consolidada para contabilidad · Ventas + Reparaciones por mes
            </small>
        </div>
    </div>

    {{-- SELECTOR DE AÑO --}}
    <form method="GET" class="anio-selector no-print">
        <div>
            <label>Año a consultar</label>
            <input type="number" name="anio" value="{{ $anio }}"
                   class="form-control" style="width:110px;border-radius:8px;" min="2020" max="2099">
        </div>
        <div style="align-self:flex-end;">
            <button type="submit" class="btn btn-primary" style="border-radius:8px;padding:8px 18px;font-weight:600;">
                <i class="bi bi-search me-1"></i> Ver año
            </button>
        </div>
        <div style="align-self:flex-end;">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary"
                    style="border-radius:8px;padding:8px 14px;">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
        <div style="align-self:flex-end;margin-left:auto;">
            <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary"
               style="border-radius:8px;padding:8px 14px;">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </form>

    {{-- TOTALES ANUALES --}}
    <div class="totales-grid">
        <div class="tot-box azul">
            <div class="tot-lbl"><i class="bi bi-bag-fill me-1"></i>Total ventas {{ $anio }}</div>
            <div class="tot-val">${{ number_format($totalAnioVentas, 0, ',', '.') }}</div>
            <div class="tot-sub">Suma de los 12 meses</div>
        </div>
        <div class="tot-box verde">
            <div class="tot-lbl"><i class="bi bi-tools me-1"></i>Total reparaciones {{ $anio }}</div>
            <div class="tot-val">${{ number_format($totalAnioRep, 0, ',', '.') }}</div>
            <div class="tot-sub">Suma de los 12 meses</div>
        </div>
        <div class="tot-box rojo">
            <div class="tot-lbl"><i class="bi bi-cash-stack me-1"></i>Ingreso total {{ $anio }}</div>
            <div class="tot-val">${{ number_format($totalAnio, 0, ',', '.') }}</div>
            <div class="tot-sub">Ventas + Reparaciones</div>
        </div>
    </div>

    {{-- TABLA MES A MES --}}
    <div class="rpt-card">
        <div class="rpt-card-head">
            <span><i class="bi bi-table me-2"></i>Desglose mensual — {{ $anio }}</span>
            <span style="font-size:0.8rem;color:#6c757d;" class="no-print">
                <span style="display:inline-block;width:12px;height:8px;background:#2563eb;border-radius:2px;margin-right:4px;"></span> Ventas &nbsp;
                <span style="display:inline-block;width:12px;height:8px;background:#16a34a;border-radius:2px;margin-right:4px;"></span> Reparaciones
            </span>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-center">Ventas (#)</th>
                        <th class="text-end">Ingresos ventas</th>
                        <th class="no-print" style="width:120px;"></th>
                        <th class="text-center">Reparac. (#)</th>
                        <th class="text-end">Ingresos reparac.</th>
                        <th class="no-print" style="width:120px;"></th>
                        <th class="text-end">Total mes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen as $fila)
                        @php
                            $esMesActual = ($fila['mes_num'] == $mesActual && (int)$anio == $anioActual);
                            $esFuturo    = (int)$anio == $anioActual && $fila['mes_num'] > $mesActual;
                            $barV = $maxMes > 0 ? round(($fila['ventas_total'] / $maxMes) * 100) : 0;
                            $barR = $maxMes > 0 ? round(($fila['rep_total'] / $maxMes) * 100) : 0;
                        @endphp
                        <tr class="{{ $esMesActual ? 'mes-actual' : '' }}">
                            <td style="font-weight:600;">
                                {{ $fila['mes'] }}
                                @if($esMesActual)<span class="badge-mes">actual</span>@endif
                            </td>
                            <td class="text-center">
                                @if($esFuturo) <span class="mes-cero">—</span>
                                @else <span style="font-weight:600;">{{ $fila['ventas_count'] }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($esFuturo) <span class="mes-cero">—</span>
                                @elseif($fila['ventas_total'] == 0) <span class="mes-cero">$0</span>
                                @else <span style="font-weight:700;color:#2563eb;">${{ number_format($fila['ventas_total'], 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="no-print" style="vertical-align:middle;">
                                @if(!$esFuturo && $fila['ventas_total'] > 0)
                                    <span class="bar-v" style="width:{{ $barV }}px;max-width:110px;"></span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($esFuturo) <span class="mes-cero">—</span>
                                @else <span style="font-weight:600;">{{ $fila['rep_count'] }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($esFuturo) <span class="mes-cero">—</span>
                                @elseif($fila['rep_total'] == 0) <span class="mes-cero">$0</span>
                                @else <span style="font-weight:700;color:#16a34a;">${{ number_format($fila['rep_total'], 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="no-print" style="vertical-align:middle;">
                                @if(!$esFuturo && $fila['rep_total'] > 0)
                                    <span class="bar-r" style="width:{{ $barR }}px;max-width:110px;"></span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($esFuturo) <span class="mes-cero">—</span>
                                @elseif($fila['total_mes'] == 0) <span class="mes-cero">$0</span>
                                @else <span style="font-weight:800;color:#1e293b;">${{ number_format($fila['total_mes'], 0, ',', '.') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><i class="bi bi-calculator me-1"></i>TOTAL {{ $anio }}</td>
                        <td class="text-center">{{ array_sum(array_column($resumen,'ventas_count')) }}</td>
                        <td class="text-end" style="color:#2563eb;">${{ number_format($totalAnioVentas, 0, ',', '.') }}</td>
                        <td class="no-print"></td>
                        <td class="text-center">{{ array_sum(array_column($resumen,'rep_count')) }}</td>
                        <td class="text-end" style="color:#16a34a;">${{ number_format($totalAnioRep, 0, ',', '.') }}</td>
                        <td class="no-print"></td>
                        <td class="text-end" style="color:#dc2626;">${{ number_format($totalAnio, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- NOTA PARA CONTADOR --}}
    <div class="no-print" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 18px;font-size:0.82rem;color:#6c757d;">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Nota:</strong> Los montos de reparaciones corresponden al total cobrado al cliente (campo "Total" de la máquina).
        La columna "Mano de obra" puede consultarse en el reporte mensual de reparaciones.
        Las fechas son tomadas de la <strong>fecha de entrada</strong> de la orden de servicio.
    </div>

</div>
@endsection
