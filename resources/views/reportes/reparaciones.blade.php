@extends('layouts.app')

@section('title', 'Reporte de reparaciones')
@section('page-icon', 'tools')
@section('page-title', 'Reporte de reparaciones')

@php
    $mesesEs = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    $mesNombre = $mesesEs[(int) $mes] ?? '';
    $listaRep = ($reparaciones ?? collect())->values();
    $totalR = $totalReparacionesMes ?? 0;
    $totalMO = $listaRep->sum('mano_obra');
    $countR = $listaRep->count();
    $promR = $countR > 0 ? $totalR / $countR : 0;
    $ordenes = $listaRep->pluck('orden_id')->unique()->count();
@endphp

@section('page-subtitle')
    {{ $mesNombre }} {{ $anio }} · {{ $countR }} {{ $countR === 1 ? 'máquina' : 'máquinas' }} · {{ $ordenes }} {{ $ordenes === 1 ? 'orden' : 'órdenes' }}.
    Filtrado por fecha de salida de la máquina.
@endsection

@section('content')

    @include('reportes.partials.filtro')

    <div class="kpi-grid">
        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Total cobrado</div>
                <div class="kpi-value">${{ number_format($totalR, 0, ',', '.') }}</div>
                <div class="kpi-sub">Ingresos del período</div>
            </div>
        </div>

        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-person-workspace"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Mano de obra</div>
                <div class="kpi-value">${{ number_format($totalMO, 0, ',', '.') }}</div>
                <div class="kpi-sub">Solo labor técnica</div>
            </div>
        </div>

        <div class="kpi kpi-violet">
            <div class="kpi-icon"><i class="bi bi-cpu"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Máquinas atendidas</div>
                <div class="kpi-value">{{ $countR }}</div>
                <div class="kpi-sub">{{ $ordenes }} {{ $ordenes === 1 ? 'orden de servicio' : 'órdenes de servicio' }}</div>
            </div>
        </div>

        <div class="kpi kpi-amber">
            <div class="kpi-icon"><i class="bi bi-graph-up"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ticket promedio</div>
                <div class="kpi-value">${{ number_format($promR, 0, ',', '.') }}</div>
                <div class="kpi-sub">Por máquina atendida</div>
            </div>
        </div>
    </div>

    @include('partials.filtro-tabla', ['tabla' => 'tablaReporteReparaciones', 'placeholder' => 'Buscar por orden, cliente, máquina o técnico'])

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Máquina</th>
                    <th>Técnico</th>
                    <th>Fecha de salida</th>
                    <th class="text-end">Mano de obra</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody id="tablaReporteReparaciones" data-paginar="10">
                @forelse ($reparaciones as $reparacion)
                    <tr>
                        <td class="fw-bold text-primary">{{ $reparacion->orden->numero_orden ?? ('#' . $reparacion->orden_id) }}</td>
                        <td>{{ $reparacion->orden->cliente->nombre ?? 'Sin cliente' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit(trim(($reparacion->etiqueta_maquina ? $reparacion->etiqueta_maquina . ' · ' : '') . ($reparacion->descripcion ?? '')), 48) ?: '—' }}</td>
                        <td>{{ $reparacion->tecnico->nombre ?? 'Sin técnico' }}</td>
                        <td class="text-nowrap">{{ optional($reparacion->fecha_salida)->format('d/m/Y') }}</td>
                        <td class="text-end">${{ number_format($reparacion->mano_obra ?? 0, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-success">${{ number_format($reparacion->total ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <strong>No se encontraron reparaciones en este período</strong>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
