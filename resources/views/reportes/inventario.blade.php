@extends('layouts.app')
@section('title', 'Reporte de Inventario')

@push('styles')
<style>
    .rpt-header { display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap; }
    .rpt-header-ico { width:48px;height:48px;border-radius:12px;background:#fffbeb;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
    .rpt-header h4 { font-weight:800;color:#1e293b;margin:0 0 2px;font-size:1.2rem; }
    .rpt-header small { color:#6c757d;font-size:0.82rem; }

    .kpi-mini { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px; }
    @media(max-width:992px){ .kpi-mini{grid-template-columns:repeat(2,1fr);} }
    @media(max-width:576px){ .kpi-mini{grid-template-columns:1fr;} }
    .km { background:#fff;border-radius:11px;padding:16px 18px;border:1px solid #e9ecef;box-shadow:0 1px 4px rgba(0,0,0,0.04);border-top:3px solid; }
    .km.ca { border-top-color:#d97706; }
    .km.cg { border-top-color:#16a34a; }
    .km.cr { border-top-color:#dc2626; }
    .km.cb { border-top-color:#2563eb; }
    .km-lbl { font-size:0.69rem;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#9ca3af;margin-bottom:4px; }
    .km-val { font-size:1.5rem;font-weight:800;color:#1e293b;line-height:1; }
    .km-sub { font-size:0.75rem;color:#6c757d;margin-top:3px; }

    .rpt-card { background:#fff;border-radius:12px;border:1px solid #e9ecef;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;margin-bottom:16px; }
    .rpt-card-head { padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between; }
    .rpt-card-head span { font-weight:700;font-size:0.88rem;color:#374151; }
    .rpt-card-head small { font-size:0.78rem;color:#6c757d; }

    .rpt-table { margin:0; }
    .rpt-table thead tr { background:#f1f5f9; }
    .rpt-table thead th { font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#64748b;padding:11px 16px;border:none;border-bottom:1px solid #e2e8f0; }
    .rpt-table tbody tr { border-bottom:1px solid #f1f5f9; }
    .rpt-table tbody tr:last-child { border-bottom:none; }
    .rpt-table tbody tr:hover { background:#fafbfc; }
    .rpt-table td { padding:11px 16px;font-size:0.87rem;color:#374151;vertical-align:middle;border:none; }
    .rpt-table tfoot td { background:#f8fafc;font-weight:700;font-size:0.88rem;padding:13px 16px;border-top:2px solid #e2e8f0;color:#1e293b; }

    .stock-critico { display:inline-flex;align-items:center;gap:5px;background:#fff1f2;color:#dc2626;border-radius:6px;padding:3px 9px;font-size:0.75rem;font-weight:700; }
    .stock-bajo { display:inline-flex;align-items:center;gap:5px;background:#fffbeb;color:#d97706;border-radius:6px;padding:3px 9px;font-size:0.75rem;font-weight:700; }
    .stock-normal { display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;color:#16a34a;border-radius:6px;padding:3px 9px;font-size:0.75rem;font-weight:700; }
    .badge-mov-e { display:inline-block;background:#f0fdf4;color:#16a34a;border-radius:6px;padding:2px 9px;font-size:0.75rem;font-weight:700; }
    .badge-mov-s { display:inline-block;background:#fff1f2;color:#dc2626;border-radius:6px;padding:2px 9px;font-size:0.75rem;font-weight:700; }
    .val-positivo { font-weight:700;color:#16a34a; }
    .empty-state { text-align:center;padding:50px 20px;color:#9ca3af; }
    .empty-state i { font-size:2.5rem;display:block;margin-bottom:10px;opacity:0.4; }
    @media print { .no-print { display:none!important; } }
</style>
@endpush

@section('content')
<div>

    @php
        use App\Models\Producto;
        use App\Models\MovimientoInventario;

        $mesesEs = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
        $mesNombre = $mesesEs[(int)$mes] ?? '';

        $productos = Producto::where('activo', 1)->orderBy('nombre')->get();
        $totalCompra = $productos->sum(fn($p) => $p->precio_compra * $p->stock);
        $totalVenta  = $productos->sum(fn($p) => $p->precio_venta  * $p->stock);
        $margenPot   = $totalVenta - $totalCompra;
        $criticos    = $productos->where('stock', 0)->count();
        $bajos       = $productos->whereBetween('stock', [1, 2])->count();

        $movimientos = MovimientoInventario::with('producto')
            ->whereYear('created_at', $anio ?? date('Y'))
            ->whereMonth('created_at', $mes ?? date('m'))
            ->orderByDesc('created_at')
            ->get();
    @endphp

    <div class="rpt-header">
        <div class="rpt-header-ico"><i class="bi bi-box-seam"></i></div>
        <div>
            <h4>Reporte de Inventario</h4>
            <small>
                <i class="bi bi-calendar3 me-1"></i>
                Estado actual · Movimientos de {{ $mesNombre }} {{ $anio }}
            </small>
        </div>
    </div>

    @include('reportes.partials.filtro')

    <div class="kpi-mini">
        <div class="km ca">
            <div class="km-lbl">Valor a precio compra</div>
            <div class="km-val">${{ number_format($totalCompra, 0, ',', '.') }}</div>
            <div class="km-sub">Costo del inventario actual</div>
        </div>
        <div class="km cg">
            <div class="km-lbl">Valor a precio venta</div>
            <div class="km-val">${{ number_format($totalVenta, 0, ',', '.') }}</div>
            <div class="km-sub">Potencial de venta</div>
        </div>
        <div class="km cb">
            <div class="km-lbl">Margen potencial</div>
            <div class="km-val">${{ number_format($margenPot, 0, ',', '.') }}</div>
            <div class="km-sub">Si se vende todo el stock</div>
        </div>
        <div class="km cr">
            <div class="km-lbl">Alertas de stock</div>
            <div class="km-val">{{ $criticos + $bajos }}</div>
            <div class="km-sub">{{ $criticos }} críticos · {{ $bajos }} bajos</div>
        </div>
    </div>

    {{-- TABLA PRODUCTOS --}}
    <div class="rpt-card">
        <div class="rpt-card-head">
            <span><i class="bi bi-boxes me-2"></i>Estado Actual del Inventario</span>
            <span class="no-print d-flex align-items-center gap-3">
                <span id="pag-info-prod" style="font-size:0.8rem;color:#6c757d;"></span>
                <span style="font-size:0.78rem;color:#6c757d;">{{ $productos->count() }} productos activos</span>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">P. Compra</th>
                        <th class="text-end">P. Venta</th>
                        <th class="text-end">Valor Total</th>
                    </tr>
                </thead>
                <tbody id="tbody-prod">
                    @forelse($productos as $prod)
                        <tr @if($prod->stock == 0) style="background:#fff8f8;" @endif>
                            <td style="font-family:monospace;font-size:0.8rem;color:#6c757d;">{{ $prod->codigo }}</td>
                            <td style="font-weight:600;">{{ $prod->nombre }}
                                @if($prod->modelo)
                                    <span style="font-size:0.77rem;color:#9ca3af;"> — {{ $prod->modelo }}</span>
                                @endif
                            </td>
                            <td class="text-center" style="font-weight:700;font-size:1rem;">{{ $prod->stock }}</td>
                            <td class="text-center">
                                @if($prod->stock == 0)
                                    <span class="stock-critico"><i class="bi bi-exclamation-circle-fill"></i>CRÍTICO</span>
                                @elseif($prod->stock <= 2)
                                    <span class="stock-bajo"><i class="bi bi-exclamation-triangle-fill"></i>BAJO</span>
                                @else
                                    <span class="stock-normal"><i class="bi bi-check-circle-fill"></i>Normal</span>
                                @endif
                            </td>
                            <td class="text-end">${{ number_format($prod->precio_compra, 0, ',', '.') }}</td>
                            <td class="text-end">${{ number_format($prod->precio_venta, 0, ',', '.') }}</td>
                            <td class="text-end val-positivo">${{ number_format($prod->precio_venta * $prod->stock, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i>No hay productos activos.</div></td></tr>
                    @endforelse
                </tbody>
                @if($productos->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end">VALOR TOTAL INVENTARIO</td>
                        <td class="text-end" style="color:#16a34a;">${{ number_format($totalVenta, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <div class="no-print d-flex align-items-center justify-content-between px-3 py-2 border-top" id="pag-ctrl-prod" style="background:#f8fafc;display:none!important;">
            <span id="pag-texto-prod" style="font-size:0.82rem;color:#6c757d;"></span>
            <nav><ul class="pagination pagination-sm mb-0">
                <li class="page-item" id="pag-prev-prod"><a class="page-link" href="#">‹ Anterior</a></li>
                <li class="page-item disabled"><span class="page-link" id="pag-num-prod"></span></li>
                <li class="page-item" id="pag-next-prod"><a class="page-link" href="#">Siguiente ›</a></li>
            </ul></nav>
        </div>
    </div>

    {{-- MOVIMIENTOS DEL MES --}}
    <div class="rpt-card">
        <div class="rpt-card-head">
            <span><i class="bi bi-arrow-left-right me-2"></i>Movimientos de {{ $mesNombre }} {{ $anio }}</span>
            <span class="no-print d-flex align-items-center gap-3">
                <span id="pag-info-mov" style="font-size:0.8rem;color:#6c757d;"></span>
                <span style="font-size:0.78rem;color:#6c757d;">{{ $movimientos->count() }} movimientos</span>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Cantidad</th>
                        <th>Referencia</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody id="tbody-mov">
                    @forelse($movimientos as $mov)
                        <tr>
                            <td style="font-weight:600;">{{ $mov->producto->nombre ?? '—' }}</td>
                            <td class="text-center">
                                @if($mov->tipo == 'entrada')
                                    <span class="badge-mov-e"><i class="bi bi-arrow-down-circle-fill me-1"></i>Entrada</span>
                                @else
                                    <span class="badge-mov-s"><i class="bi bi-arrow-up-circle-fill me-1"></i>Salida</span>
                                @endif
                            </td>
                            <td class="text-center" style="font-weight:700;">{{ $mov->cantidad }}</td>
                            <td style="font-size:0.82rem;color:#6c757d;">{{ $mov->referencia ?? '—' }}</td>
                            <td style="font-size:0.82rem;white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($mov->created_at)->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i>No hay movimientos en este período.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="no-print d-flex align-items-center justify-content-between px-3 py-2 border-top" id="pag-ctrl-mov" style="background:#f8fafc;display:none!important;">
            <span id="pag-texto-mov" style="font-size:0.82rem;color:#6c757d;"></span>
            <nav><ul class="pagination pagination-sm mb-0">
                <li class="page-item" id="pag-prev-mov"><a class="page-link" href="#">‹ Anterior</a></li>
                <li class="page-item disabled"><span class="page-link" id="pag-num-mov"></span></li>
                <li class="page-item" id="pag-next-mov"><a class="page-link" href="#">Siguiente ›</a></li>
            </ul></nav>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initPag('tbody-prod','pag-ctrl-prod','pag-prev-prod','pag-next-prod','pag-num-prod','pag-texto-prod','pag-info-prod',20);
    initPag('tbody-mov','pag-ctrl-mov','pag-prev-mov','pag-next-mov','pag-num-mov','pag-texto-mov','pag-info-mov',20);
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
        document.getElementById(textoId).textContent=`Mostrando ${desde}–${hasta} de ${total} registros`;
        document.getElementById(numId).textContent=`Pág. ${page} / ${pages}`;
        if(infoId&&document.getElementById(infoId)) document.getElementById(infoId).textContent=`${desde}–${hasta} de ${total}`;
        document.getElementById(prevId).classList.toggle('disabled',page===1);
        document.getElementById(nextId).classList.toggle('disabled',page===pages);
    }
    document.getElementById(prevId).querySelector('a').addEventListener('click',e=>{e.preventDefault();if(page>1){page--;render();}});
    document.getElementById(nextId).querySelector('a').addEventListener('click',e=>{e.preventDefault();if(page<pages){page++;render();}});
    render();
}
</script>
@endpush
