@extends('layouts.app')
@section('title', 'Productividad Técnicos')

@push('styles')
<style>
    .rpt-header { display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap; }
    .rpt-header-ico { width:48px;height:48px;border-radius:12px;background:#f0fdfa;color:#0d9488;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
    .rpt-header h4 { font-weight:800;color:#1e293b;margin:0 0 2px;font-size:1.2rem; }
    .rpt-header small { color:#6c757d;font-size:0.82rem; }

    /* TOP 5 CARDS */
    .top5-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:22px; }
    .tec-card { background:#fff;border-radius:12px;border:1px solid #e9ecef;padding:16px 18px;box-shadow:0 1px 4px rgba(0,0,0,0.04);display:flex;align-items:center;gap:12px;transition:all 0.2s; }
    .tec-card:hover { box-shadow:0 6px 18px rgba(0,0,0,0.09);transform:translateY(-2px); }
    .tec-rank { width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem;flex-shrink:0; }
    .rank-1 { background:#fef9c3;color:#854d0e; }
    .rank-2 { background:#e2e8f0;color:#475569; }
    .rank-3 { background:#fef3c7;color:#92400e; }
    .rank-n { background:#f0fdfa;color:#0d9488; }
    .tec-info { flex:1;min-width:0; }
    .tec-nombre { font-weight:700;font-size:0.9rem;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .tec-total { font-size:1.1rem;font-weight:800;color:#0d9488; }
    .tec-sub { font-size:0.73rem;color:#9ca3af; }
    .tec-bar-wrap { margin-top:5px;background:#f0fdfa;border-radius:4px;height:4px;overflow:hidden; }
    .tec-bar { height:100%;border-radius:4px;background:linear-gradient(90deg,#0d9488,#14b8a6); }

    .rpt-card { background:#fff;border-radius:12px;border:1px solid #e9ecef;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;margin-top:6px; }
    .rpt-card-head { padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between; }
    .rpt-card-head span { font-weight:700;font-size:0.88rem;color:#374151; }
    .rpt-table { margin:0; }
    .rpt-table thead tr { background:#f1f5f9; }
    .rpt-table thead th { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#64748b;padding:11px 16px;border:none;border-bottom:1px solid #e2e8f0; }
    .rpt-table tbody tr { border-bottom:1px solid #f1f5f9; }
    .rpt-table tbody tr:last-child { border-bottom:none; }
    .rpt-table tbody tr:hover { background:#fafbfc; }
    .rpt-table td { padding:11px 16px;font-size:0.87rem;color:#374151;vertical-align:middle;border:none; }
    .rpt-table tfoot td { background:#f8fafc;font-weight:700;font-size:0.88rem;padding:13px 16px;border-top:2px solid #e2e8f0;color:#1e293b; }
    .rank-badge { display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;font-weight:800;font-size:0.8rem; }
    .empty-state { text-align:center;padding:50px 20px;color:#9ca3af; }
    .empty-state i { font-size:2.5rem;display:block;margin-bottom:10px;opacity:0.4; }
    @media print { .no-print { display:none!important; } }
</style>
@endpush

@section('content')
<div>

    @php
        $mesesEs = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
        $mesNombre = $mesesEs[(int)$mes] ?? '';
        $prod = $productividad ?? [];
        // prod es array de ['total'=>x,'maquinas'=>y,'mano_obra'=>z] indexado por nombre
        // ordenado DESC por total ya desde el service
        $sumaTotal = array_sum(array_column($prod, 'total'));
        $maxVal    = count($prod) > 0 ? max(array_column($prod, 'total')) : 1;
        $top5      = array_slice($prod, 0, 5, true);
    @endphp

    <div class="rpt-header">
        <div class="rpt-header-ico"><i class="bi bi-person-gear"></i></div>
        <div>
            <h4>Productividad de Técnicos</h4>
            <small>
                <i class="bi bi-calendar3 me-1"></i>
                {{ $mesNombre }} {{ $anio }} &nbsp;·&nbsp; {{ count($prod) }} técnicos con actividad
            </small>
        </div>
    </div>

    @include('reportes.partials.filtro')

    {{-- TOP 5 EN CUADROS --}}
    @if(count($top5) > 0)
        <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;margin-bottom:10px;">
            <i class="bi bi-trophy-fill me-1" style="color:#d97706;"></i> Top {{ count($top5) }} — {{ $mesNombre }} {{ $anio }}
        </div>
        <div class="top5-grid">
            @foreach($top5 as $nombre => $datos)
                @php $rank = $loop->iteration; @endphp
                <div class="tec-card">
                    <div class="tec-rank {{ $rank==1?'rank-1':($rank==2?'rank-2':($rank==3?'rank-3':'rank-n')) }}">
                        @if($rank==1)<i class="bi bi-trophy-fill" style="color:#b45309;font-size:1rem;"></i>
                        @elseif($rank==2)<i class="bi bi-award-fill" style="color:#64748b;font-size:1rem;"></i>
                        @elseif($rank==3)<i class="bi bi-award" style="color:#92400e;font-size:1rem;"></i>
                        @else <span>{{ $rank }}</span>
                        @endif
                    </div>
                    <div class="tec-info">
                        <div class="tec-nombre">{{ $nombre }}</div>
                        <div class="tec-total">${{ number_format($datos['total'], 0, ',', '.') }}</div>
                        <div class="tec-bar-wrap">
                            <div class="tec-bar" style="width:{{ $maxVal > 0 ? round(($datos['total']/$maxVal)*100) : 0 }}%"></div>
                        </div>
                        <div class="tec-sub mt-1">{{ $datos['maquinas'] }} máq. · {{ $sumaTotal > 0 ? round(($datos['total']/$sumaTotal)*100,1) : 0 }}%</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- TABLA COMPLETA --}}
    <div class="rpt-card">
        <div class="rpt-card-head">
            <span><i class="bi bi-table me-2"></i>Tabla completa — {{ $mesNombre }} {{ $anio }}</span>
            <small class="no-print">{{ count($prod) }} técnicos</small>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table">
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
                <tbody>
                    @forelse($prod as $nombre => $datos)
                        @php $pct = $sumaTotal > 0 ? round(($datos['total']/$sumaTotal)*100,1) : 0; @endphp
                        <tr>
                            <td>
                                @if($loop->iteration==1)
                                    <span class="rank-badge rank-1"><i class="bi bi-trophy-fill" style="color:#b45309;"></i></span>
                                @elseif($loop->iteration==2)
                                    <span class="rank-badge rank-2"><i class="bi bi-award-fill" style="color:#64748b;"></i></span>
                                @elseif($loop->iteration==3)
                                    <span class="rank-badge rank-3"><i class="bi bi-award" style="color:#92400e;"></i></span>
                                @else
                                    <span style="color:#9ca3af;font-size:0.82rem;">{{ $loop->iteration }}°</span>
                                @endif
                            </td>
                            <td style="font-weight:600;">
                                <i class="bi bi-person-circle me-1" style="color:#0d9488;"></i>{{ $nombre }}
                            </td>
                            <td class="text-center">
                                <span style="font-weight:700;color:#374151;">{{ $datos['maquinas'] }}</span>
                            </td>
                            <td class="text-end" style="color:#6c757d;">
                                ${{ number_format($datos['mano_obra'], 0, ',', '.') }}
                            </td>
                            <td class="text-end" style="font-weight:700;color:#0d9488;">
                                ${{ number_format($datos['total'], 0, ',', '.') }}
                            </td>
                            <td class="text-end no-print">
                                <span style="font-size:0.82rem;color:#6c757d;">{{ $pct }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-person-x"></i>
                                    No hay técnicos con actividad en este período.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($prod) > 0)
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">TOTAL</td>
                        <td class="text-center">{{ array_sum(array_column($prod,'maquinas')) }}</td>
                        <td class="text-end">${{ number_format(array_sum(array_column($prod,'mano_obra')), 0, ',', '.') }}</td>
                        <td class="text-end" style="color:#0d9488;">${{ number_format($sumaTotal, 0, ',', '.') }}</td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
