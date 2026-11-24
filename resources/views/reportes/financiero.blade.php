@extends('layouts.app')

@section('title', 'Resumen financiero')
@section('page-icon', 'cash-coin')
@section('page-title', 'Resumen financiero general')

@php
    $mesesEs = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    $mesNombre = $mesesEs[(int) $mes] ?? '';
    $v = $totalVentasMes ?? 0;
    $r = $totalReparacionesMes ?? 0;
    $t = $totalGeneral ?? ($v + $r);
    $pctV = $t > 0 ? round(($v / $t) * 100, 1) : 0;
    $pctR = $t > 0 ? round(($r / $t) * 100, 1) : 0;
    $nFacturas = ($ventas ?? collect())->count();
    $nOrdenes = ($reparaciones ?? collect())->count();
@endphp

@section('page-subtitle')
    {{ $mesNombre }} {{ $anio }} · Vista consolidada gerencial
@endsection

@section('content')

    @include('reportes.partials.filtro')

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-bag-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ingresos por ventas</div>
                <div class="kpi-value">${{ number_format($ingresosVentas ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-sub">Según la fecha de venta</div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-tools"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ingresos por reparaciones</div>
                <div class="kpi-value">${{ number_format($ingresosReparaciones ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-sub">Según la fecha de salida</div>
            </div>
        </div>

        <div class="kpi kpi-red">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Total general del período</div>
                <div class="kpi-value">${{ number_format($t, 0, ',', '.') }}</div>
                <div class="kpi-sub">Ventas + reparaciones</div>
            </div>
        </div>
    </div>

    <div class="card no-print">
        <div class="card-header"><span><i class="bi bi-bar-chart-fill"></i> Distribución de ingresos</span></div>
        <div class="card-body">
            <div class="cmp-row">
                <div class="cmp-top">
                    <span>Ventas</span>
                    <span><span class="text-primary">${{ number_format($v, 0, ',', '.') }}</span> <span class="cell-sub ms-1">{{ $pctV }}%</span></span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: {{ $pctV }}%"></div>
                </div>
            </div>

            <div class="cmp-row">
                <div class="cmp-top">
                    <span>Reparaciones</span>
                    <span><span class="text-success">${{ number_format($r, 0, ',', '.') }}</span> <span class="cell-sub ms-1">{{ $pctR }}%</span></span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill green" style="width: {{ $pctR }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-title"><i class="bi bi-file-earmark-bar-graph"></i> Cuadro financiero consolidado — {{ $mesNombre }} {{ $anio }}</div>

    <div class="table-responsive">
        <table class="table">
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
                        <i class="bi bi-bag-fill text-primary me-1"></i>
                        <strong>Ventas</strong>
                        <span class="cell-sub ms-2">{{ $nFacturas }} {{ $nFacturas === 1 ? 'factura' : 'facturas' }}</span>
                    </td>
                    <td class="text-end fw-bold text-primary">${{ number_format($v, 0, ',', '.') }}</td>
                    <td class="text-end">{{ $pctV }}%</td>
                </tr>
                <tr>
                    <td>
                        <i class="bi bi-tools text-success me-1"></i>
                        <strong>Reparaciones</strong>
                        <span class="cell-sub ms-2">{{ $nOrdenes }} {{ $nOrdenes === 1 ? 'máquina' : 'máquinas' }}</span>
                    </td>
                    <td class="text-end fw-bold text-success">${{ number_format($r, 0, ',', '.') }}</td>
                    <td class="text-end">{{ $pctR }}%</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total general</td>
                    <td class="text-end text-danger">${{ number_format($t, 0, ',', '.') }}</td>
                    <td class="text-end">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>

@endsection
