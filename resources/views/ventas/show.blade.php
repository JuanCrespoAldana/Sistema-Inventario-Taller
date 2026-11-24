@extends('layouts.app')

@section('title', 'Factura ' . $venta->numero_factura)
@section('page-icon', 'receipt')
@section('page-title', 'Factura ' . $venta->numero_factura)
@section('page-subtitle', 'Registrada el ' . $venta->fecha->format('d/m/Y'))

@section('page-actions')
    <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Historial
    </a>
    <a href="{{ route('ventas.create') }}" class="btn btn-outline-primary">
        <i class="bi bi-cart-plus"></i> Nueva venta
    </a>
    <button type="button" class="btn btn-primary" id="btnPdf" onclick="generarPDF()">
        <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
    </button>
@endsection

@section('content')

    <div class="factura-sheet p-0 mx-auto" style="max-width: 53.75rem;">
        <div id="factura" style="padding: 2.5rem; background: #ffffff;">

            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('img/logo.jpeg') }}" alt="Logo" style="height: 5.25rem; width: 5.25rem; object-fit: cover; border-radius: 14px;">
                    <div>
                        <h5 class="mb-1 fw-bold">SISTEMA TALLER</h5>
                        <div class="text-muted small">Factura de venta</div>
                    </div>
                </div>

                <div class="text-end">
                    <div class="info-label">Factura</div>
                    <div class="fs-5 fw-bold text-primary mb-2">{{ $venta->numero_factura }}</div>
                    <div class="info-label">Fecha</div>
                    <div class="fw-semibold">{{ $venta->fecha->format('d/m/Y') }}</div>
                </div>
            </div>

            <table class="table mb-0" style="border-top: 2px solid #10264b;">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Desc.</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($venta->detalles as $detalle)
                        @php $subtotalLinea = $detalle->precio_unitario * $detalle->cantidad; @endphp
                        <tr>
                            <td class="cell-title">{{ $detalle->producto->nombre ?? 'Producto eliminado' }}</td>
                            <td class="text-center">{{ $detalle->cantidad }}</td>
                            <td class="text-end">${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
                            <td class="text-end">${{ number_format($subtotalLinea, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">
                                {{ $subtotalLinea - $detalle->total > 0 ? '- $' . number_format($subtotalLinea - $detalle->total, 0, ',', '.') : '—' }}
                            </td>
                            <td class="text-end fw-bold">${{ number_format($detalle->total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="row justify-content-end mt-4">
                <div class="col-sm-7 col-md-5">
                    <div class="resumen-box mt-0">
                        <div class="linea">
                            <span>Subtotal</span>
                            <strong>${{ number_format($venta->total, 0, ',', '.') }}</strong>
                        </div>
                        @if ($venta->descuento > 0)
                            <div class="linea descuento">
                                <span>Descuento</span>
                                <strong>- ${{ number_format($venta->descuento, 0, ',', '.') }}</strong>
                            </div>
                        @endif
                        <div class="linea total">
                            <span>Total</span>
                            <strong>${{ number_format($venta->total_final, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="mt-5">
            <p class="text-center text-muted small mb-0">Gracias por su compra</p>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>
    <script>
        function generarPDF() {
            html2pdf()
                .set({
                    margin: 0,
                    filename: 'factura_{{ $venta->numero_factura }}.pdf',
                    image: { type: 'jpeg', quality: 1 },
                    html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                })
                .from(document.getElementById('factura'))
                .save();
        }
    </script>
@endpush
