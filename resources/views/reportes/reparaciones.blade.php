@extends('layouts.app')
@section('title', 'Reporte de Reparaciones')

@push('styles')
<style>
    .rpt-header { display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap; }
    .rpt-header-ico { width:48px;height:48px;border-radius:12px;background:#ecfdf5;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:1.4rem; }
    .rpt-header h4 { font-weight:800;color:#1e293b;margin:0 0 2px;font-size:1.2rem; }
    .rpt-header small { color:#6c757d;font-size:0.82rem; }

    .kpi-mini { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px; }
    @media(max-width:992px){ .kpi-mini{grid-template-columns:repeat(2,1fr);} }
    @media(max-width:576px){ .kpi-mini{grid-template-columns:1fr;} }
    .km { background:#fff;border-radius:11px;padding:16px 18px;border:1px solid #e9ecef;box-shadow:0 1px 4px rgba(0,0,0,0.04);border-top:3px solid; }
    .km.cg { border-top-color:#16a34a; }
    .km.cb { border-top-color:#2563eb; }
    .km.cp { border-top-color:#7c3aed; }
    .km.ca { border-top-color:#d97706; }
    .km-lbl { font-size:0.69rem;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#9ca3af;margin-bottom:4px; }
    .km-val { font-size:1.5rem;font-weight:800;color:#1e293b;line-height:1; }
    .km-sub { font-size:0.75rem;color:#6c757d;margin-top:3px; }

    .rpt-card { background:#fff;border-radius:12px;border:1px solid #e9ecef;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden; }
    .rpt-card-head { padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px; }
    .rpt-card-head span { font-weight:700;font-size:0.88rem;color:#374151; }

    .rpt-table { margin:0; }
    .rpt-table thead tr { background:#f1f5f9; }
    .rpt-table thead th { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#64748b;padding:11px 16px;border:none;border-bottom:1px solid #e2e8f0;text-align:center; }
    .rpt-table tbody tr { border-bottom:1px solid #f1f5f9; }
    .rpt-table tbody tr:nth-child(even) { background:#f8fafc; }
    .rpt-table tbody tr:hover { background:#e7f1ff; }
    .rpt-table td { padding:10px 16px;font-size:0.87rem;color:#374151;border:none;vertical-align:middle;text-align:center; }
    .rpt-table tfoot td { background:#f8fafc;font-weight:700;font-size:0.88rem;padding:13px 16px;border-top:2px solid #e2e8f0;color:#1e293b; }
    .val-positivo { font-weight:700;color:#16a34a; }
    .badge-tec { background:#eef2ff;color:#4338ca;padding:3px 8px;border-radius:6px;font-size:0.75rem;font-weight:600; }
    .badge-ord { background:#f0fdf4;color:#166534;padding:2px 8px;border-radius:6px;font-size:0.73rem;font-weight:700;font-family:monospace; }
    .badge-cli { background:#eff6ff;color:#1d4ed8;padding:2px 8px;border-radius:6px;font-size:0.73rem;font-weight:600; }
    .date-muted { color:#6c757d;font-size:0.86rem; }
    .total-badge { display:inline-block;padding:6px 10px;border-radius:8px;background:linear-gradient(90deg,#ecfff2,#e6fff8);color:#065f46;font-weight:800;border:1px solid #c6f6e5; }
    .empty-state { text-align:center;padding:50px 20px;color:#9ca3af; }
    .empty-state i { font-size:2.5rem;display:block;margin-bottom:10px;opacity:0.4; }
    @media print {
        .no-print { display:none!important; }
        body { font-size:12px; }
        .rpt-table td, .rpt-table th { padding:7px 10px; }
    }
</style>
@endpush

@section('content')
<div>

    @php
        $mesesEs = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
        $mesNombre = $mesesEs[(int)$mes] ?? '';
        $listaRep  = ($reparaciones ?? collect())->values();
        $totalR    = $totalReparacionesMes ?? 0;
        $totalMO   = $listaRep->sum('mano_obra');
        $countR    = $listaRep->count();
        $promR     = $countR > 0 ? $totalR / $countR : 0;
        $ordenes   = $listaRep->pluck('orden_id')->unique()->count();
    @endphp

    <div class="rpt-header">
        <div class="rpt-header-ico"><i class="bi bi-tools"></i></div>
        <div>
            <h4>Reporte de Reparaciones</h4>
            <small>
                <i class="bi bi-calendar3 me-1"></i>
                {{ $mesNombre }} {{ $anio }} &nbsp;·&nbsp; {{ $countR }} máquinas · {{ $ordenes }} órdenes
                <span class="no-print" style="color:#9ca3af;"> — filtrado por fecha de salida de la máquina</span>
            </small>
        </div>
    </div>

    @include('reportes.partials.filtro')

    <div class="kpi-mini">
        <div class="km cg">
            <div class="km-lbl">Total cobrado</div>
            <div class="km-val">${{ number_format($totalR, 0, ',', '.') }}</div>
            <div class="km-sub">Ingresos del período</div>
        </div>
        <div class="km cb">
            <div class="km-lbl">Mano de obra</div>
            <div class="km-val">${{ number_format($totalMO, 0, ',', '.') }}</div>
            <div class="km-sub">Solo labor técnica</div>
        </div>
        <div class="km cp">
            <div class="km-lbl">Máquinas atendidas</div>
            <div class="km-val">{{ $countR }}</div>
            <div class="km-sub">{{ $ordenes }} órdenes de servicio</div>
        </div>
        <div class="km ca">
            <div class="km-lbl">Ticket promedio</div>
            <div class="km-val">${{ number_format($promR, 0, ',', '.') }}</div>
            <div class="km-sub">Por máquina atendida</div>
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-head">
            <span>Reparaciones por Fecha de Salida</span>
            <small>Filtradas por rango de fechas</small>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Técnico</th>
                        <th>Fecha de Salida</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="tbody-rep">
                    @forelse($reparaciones as $reparacion)
                        <tr>
                            <td>
                                <span class="badge-ord">{{ $reparacion->orden->numero_orden ?? ('#'.$reparacion->orden_id) }}</span>
                            </td>
                            <td>{{ $reparacion->tecnico->nombre ?? 'Sin técnico' }}</td>
                            <td><span class="date-muted">{{ optional($reparacion->fecha_salida)->format('d/m/Y') }}</span></td>
                            <td>
                                <span class="total-badge">${{ number_format($reparacion->total ?? 0, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>No se encontraron reparaciones en este período.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="no-print d-flex align-items-center justify-content-between px-3 py-2 border-top" id="pag-ctrl-rep" style="background:#f8fafc;display:none!important;">
            <span id="pag-info-rep" style="font-size:0.82rem;color:#6c757d;"></span>
            <span id="pag-texto-rep" style="font-size:0.82rem;color:#6c757d;"></span>
            <nav><ul class="pagination pagination-sm mb-0">
                <li class="page-item" id="pag-prev-rep"><a class="page-link" href="#">‹ Anterior</a></li>
                <li class="page-item disabled"><span class="page-link" id="pag-num-rep"></span></li>
                <li class="page-item" id="pag-next-rep"><a class="page-link" href="#">Siguiente ›</a></li>
            </ul></nav>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initPag('tbody-rep','pag-ctrl-rep','pag-prev-rep','pag-next-rep','pag-num-rep','pag-texto-rep','pag-info-rep',10);
});
function initPag(tbodyId,ctrlId,prevId,nextId,numId,textoId,infoId,pageSize){
    const tbody=document.getElementById(tbodyId);
    if(!tbody)return;
    const rows=Array.from(tbody.querySelectorAll('tr')).filter(r=>!r.querySelector('.empty-state'));
    if(rows.length<=pageSize)return;
    let page=1;
    const total=rows.length,pages=Math.ceil(total/pageSize);
    const ctrl=document.getElementById(ctrlId);
    ctrl.style.removeProperty('display');ctrl.style.display='flex';
    function render(){
        rows.forEach((r,i)=>{r.style.display=(i>=(page-1)*pageSize&&i<page*pageSize)?'':'none';});
        const desde=(page-1)*pageSize+1,hasta=Math.min(page*pageSize,total);
        document.getElementById(textoId).textContent=`Mostrando ${desde}–${hasta} de ${total}`;
        document.getElementById(numId).textContent=`Pág. ${page}/${pages}`;
        if(infoId&&document.getElementById(infoId))document.getElementById(infoId).textContent=`${desde}–${hasta} de ${total}`;
        document.getElementById(prevId).classList.toggle('disabled',page===1);
        document.getElementById(nextId).classList.toggle('disabled',page===pages);
    }
    document.getElementById(prevId).querySelector('a').addEventListener('click',e=>{e.preventDefault();if(page>1){page--;render();}});
    document.getElementById(nextId).querySelector('a').addEventListener('click',e=>{e.preventDefault();if(page<pages){page++;render();}});
    render();
}
</script>
@endpush
