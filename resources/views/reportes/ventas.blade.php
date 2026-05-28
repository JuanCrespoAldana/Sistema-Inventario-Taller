@extends('layouts.app')
@section('title', 'Reporte de Ventas')

@push('styles')
<style>
    .rpt-header { display:flex; align-items:center; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
    .rpt-header-ico { width:48px;height:48px;border-radius:12px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
    .rpt-header h4 { font-weight:800;color:#1e293b;margin:0 0 2px;font-size:1.2rem; }
    .rpt-header small { color:#6c757d;font-size:0.82rem; }

    .kpi-mini { display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px; }
    @media(max-width:768px){ .kpi-mini{grid-template-columns:1fr;} }
    .km { background:#fff;border-radius:11px;padding:16px 18px;border:1px solid #e9ecef;box-shadow:0 1px 4px rgba(0,0,0,0.04);border-top:3px solid; }
    .km.cb { border-top-color:#2563eb; }
    .km.cg { border-top-color:#16a34a; }
    .km.cp { border-top-color:#7c3aed; }
    .km-lbl { font-size:0.69rem;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#9ca3af;margin-bottom:4px; }
    .km-val { font-size:1.5rem;font-weight:800;color:#1e293b;line-height:1; }
    .km-sub { font-size:0.75rem;color:#6c757d;margin-top:3px; }

    .rpt-card { background:#fff;border-radius:12px;border:1px solid #e9ecef;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden; }
    .rpt-card-head { padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between; }
    .rpt-card-head span { font-weight:700;font-size:0.88rem;color:#374151; }
    .rpt-card-head small { font-size:0.78rem;color:#6c757d; }

    .rpt-table { margin:0; }
    .rpt-table thead tr { background:#f1f5f9; }
    .rpt-table thead th { font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.7px;color:#1f2937;padding:12px 16px;border:none;border-bottom:2px solid #e2e8f0;text-align:center; }
        .rpt-table tbody tr { border-bottom:1px solid #f1f5f9; }
        .rpt-table tbody tr:nth-child(even) { background:#f8fafc; }
        .rpt-table tbody tr:last-child { border-bottom:none; }
        .rpt-table tbody tr:hover { background:#e7f1ff; }
        .rpt-table td { padding:12px 16px;font-size:0.9rem;color:#374151;vertical-align:middle;border:none;text-align:center; }
        .rpt-table tfoot td { background:#f8fafc;font-weight:700;font-size:0.88rem;padding:13px 16px;border-top:2px solid #e2e8f0;color:#1e293b; }

        /* Decorative badges */
        .badge-num { display:inline-block;background:linear-gradient(180deg,#eef8ff,#dbeffd);color:#1e3a8a;border-radius:8px;padding:4px 10px;font-size:0.78rem;font-weight:800;font-family:monospace;border:1px solid #cfe6ff; }
        .badge-cli { display:inline-block;background:#eff6ff;color:#1d4ed8;border-radius:8px;padding:4px 10px;font-size:0.8rem;font-weight:700; }
        .date-muted { color:#6c757d;font-size:0.86rem; }
        .total-badge { display:inline-block;padding:6px 10px;border-radius:8px;background:linear-gradient(90deg,#ecfff2,#e6fff8);color:#065f46;font-weight:800;border:1px solid #c6f6e5; }
        .total-badge.negative { background:linear-gradient(90deg,#fff1f2,#fff6f6);color:#7f1d1d;border:1px solid #ffd6d6; }

        .val-positivo { font-weight:700;color:#16a34a; }
        .empty-state { text-align:center;padding:50px 20px;color:#9ca3af; }
        .empty-state i { font-size:2.5rem;display:block;margin-bottom:10px;opacity:0.4; }
    .badge-num { display:inline-block;background:#eff6ff;color:#2563eb;border-radius:6px;padding:2px 8px;font-size:0.75rem;font-weight:700;font-family:monospace; }
    .val-positivo { font-weight:700;color:#16a34a; }
    .empty-state { text-align:center;padding:50px 20px;color:#9ca3af; }
    .empty-state i { font-size:2.5rem;display:block;margin-bottom:10px;opacity:0.4; }

    /* Paginación */
    .pag-info { font-size:0.8rem;color:#6c757d; }
    @media print { .no-print { display:none!important; } }
</style>
@endpush

@section('content')
<div>

    @php
        $mesesEs = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
        $mesNombre = $mesesEs[(int)$mes] ?? '';
        $listaVentas = ($ventas ?? collect())->values();
        $totalV = $totalVentasMes ?? 0;
        $countV = $listaVentas->count();
        $promV = $countV > 0 ? $totalV / $countV : 0;
    @endphp

    <div class="rpt-header">
        <div class="rpt-header-ico"><i class="bi bi-bar-chart-line"></i></div>
        <div>
            <h4>Reporte de Ventas</h4>
            <small>
                <i class="bi bi-calendar3 me-1"></i>
                {{ $mesNombre }} {{ $anio }} &nbsp;·&nbsp; {{ $countV }} registros encontrados
            </small>
        </div>
    </div>

    @include('reportes.partials.filtro')

    <div class="kpi-mini">
        <div class="km cb">
            <div class="km-lbl">Total facturado</div>
            <div class="km-val">${{ number_format($totalV, 0, ',', '.') }}</div>
            <div class="km-sub">Suma de ventas del período</div>
        </div>
        <div class="km cg">
            <div class="km-lbl">Número de ventas</div>
            <div class="km-val">{{ $countV }}</div>
            <div class="km-sub">Facturas emitidas</div>
        </div>
        <div class="km cp">
            <div class="km-lbl">Ticket promedio</div>
            <div class="km-val">${{ number_format($promV, 0, ',', '.') }}</div>
            <div class="km-sub">Por factura emitida</div>
        </div>
    </div>

    <div class="rpt-card">
        <div class="rpt-card-head">
            <span>Ventas por Fecha</span>
            <small>Filtradas por rango de fechas</small>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="tbody-ventas">
                    @forelse($ventas as $venta)
                        <tr>
                            <td>
                                <span class="badge-num">{{ $venta->numero_factura }}</span>
                            </td>
                            <td>{{ $venta->cliente->nombre ?? 'Sin cliente' }}</td>
                            <td>
                                <span class="date-muted">{{ optional($venta->fecha)->format('d/m/Y') }}</span>
                            </td>
                            <td>
                                @php $vt = $venta->total_final ?? $venta->total; @endphp
                                <span class="total-badge {{ ($vt ?? 0) < 0 ? 'negative' : '' }}">${{ number_format($vt ?? 0, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>No se encontraron ventas en este período.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="no-print d-flex align-items-center justify-content-between px-3 py-2 border-top" id="pag-ctrl-ventas" style="background:#f8fafc;display:none!important;">
            <span id="pag-info-ventas" style="font-size:0.82rem;color:#6c757d;"></span>
            <span id="pag-texto-ventas" style="font-size:0.82rem;color:#6c757d;"></span>
            <nav><ul class="pagination pagination-sm mb-0">
                <li class="page-item" id="pag-prev-ventas"><a class="page-link" href="#">‹ Anterior</a></li>
                <li class="page-item disabled"><span class="page-link" id="pag-num-ventas"></span></li>
                <li class="page-item" id="pag-next-ventas"><a class="page-link" href="#">Siguiente ›</a></li>
            </ul></nav>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initTablePagination('tbody-ventas', 'pag-ctrl-ventas', 'pag-prev-ventas', 'pag-next-ventas', 'pag-num-ventas', 'pag-texto-ventas', 'pag-info-ventas', 10);
});

function initTablePagination(tbodyId, ctrlId, prevId, nextId, numId, textoId, infoId, pageSize) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => !r.querySelector('.empty-state'));
    if (rows.length <= pageSize) return;

    let page = 1;
    const total = rows.length;
    const pages = Math.ceil(total / pageSize);

    const ctrl = document.getElementById(ctrlId);
    ctrl.style.removeProperty('display');
    ctrl.style.display = 'flex';

    function render() {
        rows.forEach((r, i) => {
            r.style.display = (i >= (page-1)*pageSize && i < page*pageSize) ? '' : 'none';
        });
        const desde = (page-1)*pageSize + 1;
        const hasta = Math.min(page*pageSize, total);
        document.getElementById(textoId).textContent = `Mostrando ${desde}–${hasta} de ${total} registros`;
        document.getElementById(numId).textContent = `Pág. ${page} / ${pages}`;
        document.getElementById(infoId).textContent = `${desde}–${hasta} de ${total}`;
        document.getElementById(prevId).classList.toggle('disabled', page === 1);
        document.getElementById(nextId).classList.toggle('disabled', page === pages);
    }

    document.getElementById(prevId).querySelector('a').addEventListener('click', e => { e.preventDefault(); if (page > 1) { page--; render(); } });
    document.getElementById(nextId).querySelector('a').addEventListener('click', e => { e.preventDefault(); if (page < pages) { page++; render(); } });

    render();
}
</script>
@endpush
