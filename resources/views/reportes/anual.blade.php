@extends('layouts.app')

@section('title', 'Resumen anual')
@section('page-icon', 'calendar-range')
@section('page-title', 'Resumen anual — ' . $anio)
@section('page-subtitle', 'Vista consolidada para contabilidad: ventas y reparaciones mes a mes.')

@php
    $mesActual = (int) date('n');
    $anioActual = (int) date('Y');
    $maxMes = collect($resumen)->max('total_mes');
@endphp

@section('content')

    <form method="GET" class="filtro-bar no-print">
        <div>
            <label class="form-label" for="anio">Año a consultar</label>
            <input type="number" id="anio" name="anio" value="{{ $anio }}" class="form-control field-anio"
                min="2020" max="2099">
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search"></i> Ver año
        </button>

        <span class="filtro-divider"></span>

        <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> Imprimir
        </button>

        <a href="{{ route('reportes.exportar', ['tipo' => 'anual', 'anio' => $anio]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exportar a Excel
        </a>

        <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary ms-auto">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </form>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-bag-fill"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Total ventas {{ $anio }}</div>
                <div class="kpi-value">${{ number_format($totalAnioVentas, 0, ',', '.') }}</div>
                <div class="kpi-sub">Suma de los 12 meses</div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-tools"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Total reparaciones {{ $anio }}</div>
                <div class="kpi-value">${{ number_format($totalAnioRep, 0, ',', '.') }}</div>
                <div class="kpi-sub">Suma de los 12 meses</div>
            </div>
        </div>

        <div class="kpi kpi-red">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Ingreso total {{ $anio }}</div>
                <div class="kpi-value">${{ number_format($totalAnio, 0, ',', '.') }}</div>
                <div class="kpi-sub">Ventas + reparaciones</div>
            </div>
        </div>
    </div>

    <div class="section-title"><i class="bi bi-table"></i> Desglose mensual — {{ $anio }}</div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th class="text-center">Ventas (#)</th>
                    <th class="text-end">Ingresos ventas</th>
                    <th class="no-print col-acciones"></th>
                    <th class="text-center">Reparaciones (#)</th>
                    <th class="text-end">Ingresos reparaciones</th>
                    <th class="no-print col-acciones"></th>
                    <th class="text-end">Total del mes</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($resumen as $fila)
                    @php
                        $esMesActual = ($fila['mes_num'] == $mesActual && (int) $anio === $anioActual);
                        $esFuturo = (int) $anio === $anioActual && $fila['mes_num'] > $mesActual;
                        $barV = $maxMes > 0 ? round(($fila['ventas_total'] / $maxMes) * 100) : 0;
                        $barR = $maxMes > 0 ? round(($fila['rep_total'] / $maxMes) * 100) : 0;
                    @endphp
                    <tr class="{{ $esMesActual ? 'row-current' : '' }}">
                        <td class="fw-bold text-nowrap">
                            {{ $fila['mes'] }}
                            @if ($esMesActual)
                                <span class="badge bg-primary ms-1">actual</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if ($esFuturo)
                                <span class="value-zero">—</span>
                            @else
                                {{ $fila['ventas_count'] }}
                            @endif
                        </td>

                        <td class="text-end">
                            @if ($esFuturo)
                                <span class="value-zero">—</span>
                            @elseif ($fila['ventas_total'] == 0)
                                <span class="value-zero">$0</span>
                            @else
                                <span class="fw-bold text-primary">${{ number_format($fila['ventas_total'], 0, ',', '.') }}</span>
                            @endif
                        </td>

                        <td class="no-print">
                            @if (!$esFuturo && $fila['ventas_total'] > 0)
                                <div class="bar-track"><div class="bar-fill" style="width: {{ $barV }}%"></div></div>
                            @endif
                        </td>

                        <td class="text-center">
                            @if ($esFuturo)
                                <span class="value-zero">—</span>
                            @else
                                {{ $fila['rep_count'] }}
                            @endif
                        </td>

                        <td class="text-end">
                            @if ($esFuturo)
                                <span class="value-zero">—</span>
                            @elseif ($fila['rep_total'] == 0)
                                <span class="value-zero">$0</span>
                            @else
                                <span class="fw-bold text-success">${{ number_format($fila['rep_total'], 0, ',', '.') }}</span>
                            @endif
                        </td>

                        <td class="no-print">
                            @if (!$esFuturo && $fila['rep_total'] > 0)
                                <div class="bar-track"><div class="bar-fill green" style="width: {{ $barR }}%"></div></div>
                            @endif
                        </td>

                        <td class="text-end">
                            @if ($esFuturo)
                                <span class="value-zero">—</span>
                            @elseif ($fila['total_mes'] == 0)
                                <span class="value-zero">$0</span>
                            @else
                                <span class="fw-bold">${{ number_format($fila['total_mes'], 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td>Total {{ $anio }}</td>
                    <td class="text-center">{{ array_sum(array_column($resumen, 'ventas_count')) }}</td>
                    <td class="text-end text-primary">${{ number_format($totalAnioVentas, 0, ',', '.') }}</td>
                    <td class="no-print"></td>
                    <td class="text-center">{{ array_sum(array_column($resumen, 'rep_count')) }}</td>
                    <td class="text-end text-success">${{ number_format($totalAnioRep, 0, ',', '.') }}</td>
                    <td class="no-print"></td>
                    <td class="text-end text-danger">${{ number_format($totalAnio, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="report-note no-print">
        <i class="bi bi-info-circle"></i>
        <span>
            <strong>Nota:</strong> los montos de reparaciones corresponden al total cobrado al cliente (campo «Total» de la máquina).
            La mano de obra puede consultarse en el reporte mensual de reparaciones.
            Las fechas se toman de la <strong>fecha de salida</strong> de la máquina.
        </span>
    </p>

@endsection
