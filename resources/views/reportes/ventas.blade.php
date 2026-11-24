@extends('layouts.app')

@section('title', 'Reporte de ventas')
@section('page-icon', 'bar-chart-line')
@section('page-title', 'Reporte de ventas')

@php
    $mesesEs = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    $mesNombre = $mesesEs[(int) $mes] ?? '';
    $listaVentas = ($ventas ?? collect())->values();
    $totalV = $totalVentasMes ?? 0;
    $countV = $listaVentas->count();
    $promV = $countV > 0 ? $totalV / $countV : 0;
@endphp

@section('page-subtitle')
    {{ $mesNombre }} {{ $anio }} · {{ $countV }} {{ $countV === 1 ? 'registro encontrado' : 'registros encontrados' }}
@endsection

@section('content')

    @include('reportes.partials.filtro')

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Total facturado</div>
                <div class="kpi-value">${{ number_format($totalV, 0, ',', '.') }}</div>
                <div class="kpi-sub">Suma de ventas del período</div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-receipt"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Número de ventas</div>
                <div class="kpi-value">{{ $countV }}</div>
                <div class="kpi-sub">Facturas emitidas</div>
            </div>
        </div>

        <div class="kpi kpi-violet">
            <div class="kpi-icon"><i class="bi bi-graph-up"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ticket promedio</div>
                <div class="kpi-value">${{ number_format($promV, 0, ',', '.') }}</div>
                <div class="kpi-sub">Por factura emitida</div>
            </div>
        </div>
    </div>

    @include('partials.filtro-tabla', ['tabla' => 'tablaReporteVentas', 'placeholder' => 'Buscar por factura o fecha'])

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Fecha</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">Descuento</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody id="tablaReporteVentas" data-paginar="10">
                @forelse ($ventas as $venta)
                    @php $totalFinal = $venta->total_final ?? $venta->total ?? 0; @endphp
                    <tr>
                        <td class="fw-bold text-primary">{{ $venta->numero_factura }}</td>
                        <td class="text-nowrap">{{ optional($venta->fecha)->format('d/m/Y') }}</td>
                        <td class="text-end">${{ number_format($venta->total ?? 0, 0, ',', '.') }}</td>
                        <td class="text-end {{ ($venta->descuento ?? 0) > 0 ? 'text-danger' : 'value-zero' }}">
                            {{ ($venta->descuento ?? 0) > 0 ? '- $' . number_format($venta->descuento, 0, ',', '.') : '—' }}
                        </td>
                        <td class="text-end fw-bold text-success">${{ number_format($totalFinal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <strong>No se encontraron ventas en este período</strong>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
