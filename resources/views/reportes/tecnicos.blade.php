@extends('layouts.app')

@section('title', 'Productividad')
@section('page-icon', 'person-gear')
@section('page-title', 'Productividad de técnicos')

@php
    $mesesEs = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    $mesNombre = $mesesEs[(int) $mes] ?? '';
    $prod = $productividad ?? [];
    $sumaTotal = array_sum(array_column($prod, 'total'));
    $maxVal = count($prod) > 0 ? max(array_column($prod, 'total')) : 1;
    $top5 = array_slice($prod, 0, 5, true);
@endphp

@section('page-subtitle')
    {{ $mesNombre }} {{ $anio }} · {{ count($prod) }} {{ count($prod) === 1 ? 'técnico con actividad' : 'técnicos con actividad' }}
@endsection

@section('content')

    @include('reportes.partials.filtro')

    @if (count($top5) > 0)
        <div class="section-title">
            <i class="bi bi-trophy-fill"></i> Top {{ count($top5) }} — {{ $mesNombre }} {{ $anio }}
        </div>

        <div class="row g-3 mb-4">
            @foreach ($top5 as $nombre => $datos)
                @php $rank = $loop->iteration; @endphp
                <div class="col-sm-6 col-xl">
                    <div class="card h-100 mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="rank-chip {{ $rank <= 3 ? 'rank-' . $rank : '' }}">{{ $rank }}</span>
                                <span class="cell-title text-truncate">{{ $nombre }}</span>
                            </div>
                            <div class="kpi-value mb-2">${{ number_format($datos['total'], 0, ',', '.') }}</div>
                            <div class="bar-track">
                                <div class="bar-fill green" style="width: {{ $maxVal > 0 ? round(($datos['total'] / $maxVal) * 100) : 0 }}%"></div>
                            </div>
                            <div class="kpi-sub mt-2">
                                {{ $datos['maquinas'] }} {{ $datos['maquinas'] == 1 ? 'máquina' : 'máquinas' }}
                                · {{ $sumaTotal > 0 ? round(($datos['total'] / $sumaTotal) * 100, 1) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="section-title"><i class="bi bi-table"></i> Tabla completa — {{ $mesNombre }} {{ $anio }}</div>

    @include('partials.filtro-tabla', ['tabla' => 'tablaReporteTecnicos', 'placeholder' => 'Buscar técnico'])

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Técnico</th>
                    <th class="text-center">Máquinas</th>
                    <th class="text-end">Mano de obra</th>
                    <th class="text-end">Total cobrado</th>
                    <th class="text-end no-print">Participación</th>
                </tr>
            </thead>

            <tbody id="tablaReporteTecnicos" data-paginar="10">
                @forelse ($prod as $nombre => $datos)
                    @php $pct = $sumaTotal > 0 ? round(($datos['total'] / $sumaTotal) * 100, 1) : 0; @endphp
                    <tr>
                        <td>
                            <span class="rank-chip {{ $loop->iteration <= 3 ? 'rank-' . $loop->iteration : '' }}">{{ $loop->iteration }}</span>
                        </td>
                        <td class="cell-title">{{ $nombre }}</td>
                        <td class="text-center fw-bold">{{ $datos['maquinas'] }}</td>
                        <td class="text-end">${{ number_format($datos['mano_obra'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-success">${{ number_format($datos['total'], 0, ',', '.') }}</td>
                        <td class="text-end no-print">{{ $pct }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-person-x"></i>
                                <strong>No hay técnicos con actividad en este período</strong>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if (count($prod) > 0)
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-center">{{ array_sum(array_column($prod, 'maquinas')) }}</td>
                        <td class="text-end">${{ number_format(array_sum(array_column($prod, 'mano_obra')), 0, ',', '.') }}</td>
                        <td class="text-end text-success">${{ number_format($sumaTotal, 0, ',', '.') }}</td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

@endsection
