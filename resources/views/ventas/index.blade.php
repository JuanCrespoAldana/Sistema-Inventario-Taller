@extends('layouts.app')

@section('content')
    <div class="venta-wrapper">
        <div class="venta-card">

            {{-- HEADER --}}
            <div class="venta-header">
                <h2 class="venta-title">
                    <i class="bi bi-receipt-cutoff me-2"></i> Historial de Ventas
                </h2>

                <a href="{{ route('ventas.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Nueva venta
                </a>
            </div>

            {{-- FILTROS --}}
            <form method="GET" action="{{ route('ventas.index') }}" class="row g-2 align-items-end mb-4 mt-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:0.8rem;">N° Factura</label>
                    <input type="text" name="factura" value="{{ request('factura') }}"
                           class="form-control" placeholder="FAC-123">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:0.8rem;">Desde (Fecha de Salida)</label>
                    <input type="date" name="desde" value="{{ request('desde') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:0.8rem;">Hasta (Fecha de Salida)</label>
                    <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                    @if(request()->hasAny(['factura','desde','hasta']))
                        <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                </div>
            </form>

            {{-- TABLA --}}
            <div class="table-responsive mt-3">
                <table class="table ventas-table align-middle">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Producto</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Subtotal</th>
                            <th class="text-center">Descuento</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($ventas as $venta)
                            <tr>
                                <td class="fw-semibold text-primary">
                                    {{ $venta->numero_factura }}
                                </td>

                                <td class="productos-col">
                                    @foreach($venta->detalles as $detalle)
                                        <div class="producto-item">
                                            <i class="bi bi-box-seam me-1"></i>
                                            {{ $detalle->producto->nombre }}
                                        </div>
                                    @endforeach
                                </td>

                                <td>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    {{ $venta->fecha->format('d/m/Y') }}
                                </td>

                                <td class="text-end">
                                    $ {{ number_format($venta->total, 0, ',', '.') }}
                                </td>

                                <td class="text-end text-danger">
                                    - $ {{ number_format($venta->descuento, 0, ',', '.') }}
                                </td>

                                <td class="text-end fw-bold text-success">
                                    $ {{ number_format($venta->total_final, 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    <a href="{{ route('ventas.show', $venta) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-info-circle me-1"></i>
                                    No hay ventas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN --}}
            <div class="d-flex justify-content-end mt-4">
                {{ $ventas->links() }}
            </div>

        </div>
    </div>
@endsection