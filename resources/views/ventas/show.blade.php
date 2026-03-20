@extends('layouts.app')

@section('content')
    <div class="venta-wrapper">
    <div class="venta-card">

        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

        <div id="factura">

            {{-- HEADER --}}
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('img/logo.jpeg') }}" alt="Logo" style="height: 90px;">
                    <div>
                        <h5 class="mb-1 fw-bold">SISTEMA TALLER</h5>
                        <small class="text-muted">
                            <i class="bi bi-receipt me-1"></i> Factura de venta
                        </small>
                    </div>
                </div>

                <div class="text-end">
                    <p class="mb-1">
                        <strong>
                            <i class="bi bi-hash"></i> Factura:
                        </strong>
                        {{ $venta->numero_factura }}
                    </p>
                    <p class="mb-0">
                        <strong>
                            <i class="bi bi-calendar-event"></i> Fecha:
                        </strong>
                        {{ $venta->fecha->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            <hr>

            {{-- TABLA --}}
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th><i class="bi bi-box-seam me-1"></i> Producto</th>
                        <th class="text-center"><i class="bi bi-hash me-1"></i> Cant.</th>
                        <th class="text-end"><i class="bi bi-cash me-1"></i> Precio</th>
                        <th class="text-end"><i class="bi bi-calculator me-1"></i> Subtotal</th>
                        <th class="text-end text-danger"><i class="bi bi-tag-fill me-1"></i> Desc.</th>
                        <th class="text-end fw-bold"><i class="bi bi-wallet2 me-1"></i> Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $detalle)
                                    <tr>
                                        <td>{{ $detalle->producto->nombre }}</td>
                                        <td class="text-center">{{ $detalle->cantidad }}</td>
                                        <td class="text-end">
                                            $ {{ number_format($detalle->precio_unitario, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end">
                                            $ {{ number_format($detalle->precio_unitario * $detalle->cantidad, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end text-danger">
                                            - $ {{ number_format(
                            ($detalle->precio_unitario * $detalle->cantidad) - $detalle->total,
                            0,
                            ',',
                            '.'
                        ) }}
                                        </td>
                                        <td class="text-end fw-bold">
                                            $ {{ number_format($detalle->total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- RESUMEN --}}
            <div class="row justify-content-end mt-4">
                <div class="col-md-5">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-end">
                                <i class="bi bi-cash-stack me-1"></i> Subtotal:
                            </td>
                            <td class="text-end">
                                $ {{ number_format($venta->total, 0, ',', '.') }}
                            </td>
                        </tr>

                        @if($venta->descuento > 0)
                            <tr>
                                <td class="text-end text-danger">
                                    <i class="bi bi-tag-fill me-1"></i> Descuento:
                                </td>
                                <td class="text-end text-danger">
                                    - $ {{ number_format($venta->descuento, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        <tr class="border-top">
                            <td class="text-end fs-5 fw-bold">
                                <i class="bi bi-wallet2 me-1"></i> TOTAL:
                            </td>
                            <td class="text-end fs-5 fw-bold">
                                $ {{ number_format($venta->total_final, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>


            <hr>

            <p class="text-center text-muted small">
                Gracias por su compra
            </p>

        </div>
    </div>

    {{-- BOTONES --}}
    <div class="mt-4 mb-3 d-print-none d-flex justify-content-center gap-3">
        <a href="{{ route('ventas.create') }}" class="btn btn-outline-secondary">
            <i class="bi bi-cart-plus me-1"></i> Nueva venta
        </a>

        <button onclick="generarPDF()" class="btn btn-primary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Descargar PDF
        </button>
    </div>
    </div>

    <script>
        function generarPDF() {
            const element = document.getElementById('factura');

            html2pdf()
                .set({
                    margin: 0,
                    filename: 'factura_{{ $venta->numero_factura }}.pdf',
                    image: { type: 'jpeg', quality: 1 },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff'
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'portrait'
                    }
                })
                .from(element)
                .save();
        }
    </script>
@endsection