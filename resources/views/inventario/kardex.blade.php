@extends('layouts.app')

@section('title', 'Kardex')
@section('page-icon', 'clock-history')
@section('page-title', 'Kardex del producto')
@section('page-subtitle', 'Historial de movimientos y saldo después de cada uno.')

@section('page-actions')
    <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al inventario
    </a>
    <a href="{{ route('inventario.kardex.exportar', $producto) }}" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-spreadsheet"></i> Exportar
    </a>
    <a href="{{ route('inventario.ajustes.index') }}" class="btn btn-primary">
        <i class="bi bi-sliders"></i> Ajustar stock
    </a>
@endsection

@section('content')

    @php
        $estado = $producto->estado_stock;
        $detalle = collect([$producto->modelo, $producto->categoria, $producto->ubicacion, $producto->proveedor])->filter()->implode(' · ');
    @endphp

    <div class="card kardex-ficha">
        <div class="card-body">
            <div>
                <div class="cell-code">{{ $producto->codigo }}</div>
                <h2 class="kardex-nombre">{{ $producto->nombre }}</h2>
                @if ($detalle !== '')
                    <div class="cell-sub">{{ $detalle }}</div>
                @endif
            </div>
            <div class="kardex-precios">
                <div>
                    <span class="cell-sub">Precio de venta</span>
                    <strong>${{ number_format($producto->precio_venta, 0, ',', '.') }}</strong>
                </div>
                <div>
                    <span class="cell-sub">Costo</span>
                    <strong>${{ number_format($producto->precio_compra, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi {{ $estado === 'agotado' ? 'kpi-red kpi-alert' : ($estado === 'bajo' ? 'kpi-amber' : 'kpi-green') }}">
            <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Stock actual</div>
                <div class="kpi-value">{{ number_format($producto->stock, 0, ',', '.') }}</div>
                <div class="kpi-sub">
                    @if ($estado === 'agotado')
                        Agotado
                    @elseif ($estado === 'bajo')
                        Bajo · mínimo {{ $producto->stock_minimo }}
                    @else
                        Mínimo configurado: {{ $producto->stock_minimo }}
                    @endif
                </div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-arrow-down-circle"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Entradas acumuladas</div>
                <div class="kpi-value">{{ number_format($totales->entradas ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-sub">Unidades que han ingresado</div>
            </div>
        </div>

        <div class="kpi kpi-red">
            <div class="kpi-icon"><i class="bi bi-arrow-up-circle"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Salidas acumuladas</div>
                <div class="kpi-value">{{ number_format($totales->salidas ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-sub">Ventas y retiros de stock</div>
            </div>
        </div>

        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-currency-dollar"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Valor en bodega</div>
                <div class="kpi-value">${{ number_format($producto->stock * $producto->precio_compra, 0, ',', '.') }}</div>
                <div class="kpi-sub">Stock por costo unitario</div>
            </div>
        </div>
    </div>

    <div class="result-summary">
        <span><strong>{{ number_format($movimientos->total(), 0, ',', '.') }}</strong> {{ $movimientos->total() == 1 ? 'movimiento registrado' : 'movimientos registrados' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Saldo</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movimientos as $mov)
                    <tr>
                        <td class="text-nowrap">{{ $mov->created_at ? $mov->created_at->format('d/m/Y H:i') : '—' }}</td>

                        <td class="text-center">
                            @if ($mov->tipo == 'entrada')
                                <span class="badge bg-success"><i class="bi bi-arrow-down-circle"></i> Entrada</span>
                            @elseif ($mov->tipo == 'salida')
                                <span class="badge bg-danger"><i class="bi bi-arrow-up-circle"></i> Salida</span>
                            @else
                                <span class="badge bg-warning"><i class="bi bi-pencil-square"></i> Ajuste</span>
                            @endif
                        </td>

                        <td class="text-center fw-bold">
                            @if ($mov->tipo == 'entrada')
                                <span class="text-success">+{{ $mov->cantidad }}</span>
                            @elseif ($mov->tipo == 'salida')
                                <span class="text-danger">−{{ $mov->cantidad }}</span>
                            @else
                                {{ $mov->cantidad > 0 ? '+' : '' }}{{ $mov->cantidad }}
                            @endif
                        </td>

                        <td class="text-center fw-bold">{{ number_format($mov->saldo, 0, ',', '.') }}</td>

                        <td>{{ $mov->referencia ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <strong>Este producto aún no tiene movimientos</strong>
                                <span>Cuando entre o salga stock, el historial aparecerá aquí.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse

                @if ($saldoInicial !== null && $movimientos->count() > 0 && $saldoInicial != 0)
                    <tr class="fila-nota">
                        <td colspan="3" class="cell-sub">Stock anterior al primer movimiento registrado</td>
                        <td class="text-center fw-bold">{{ number_format($saldoInicial, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $movimientos->links() }}
    </div>

@endsection
