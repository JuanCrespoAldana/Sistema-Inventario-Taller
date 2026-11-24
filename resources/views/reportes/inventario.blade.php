@extends('layouts.app')

@section('title', 'Reporte de inventario')
@section('page-icon', 'box-seam')
@section('page-title', 'Reporte de inventario')

@php
    $mesesEs = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    $mesNombre = $mesesEs[(int) $mes] ?? '';
@endphp

@section('page-subtitle')
    Estado actual del inventario · Movimientos de {{ $mesNombre }} {{ $anio }}
@endsection

@section('content')

    @include('reportes.partials.filtro')

    <div class="kpi-grid">
        <div class="kpi kpi-amber">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Valor a precio de compra</div>
                <div class="kpi-value">${{ number_format($totalCompra, 0, ',', '.') }}</div>
                <div class="kpi-sub">Costo del inventario actual</div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-tag"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Valor a precio de venta</div>
                <div class="kpi-value">${{ number_format($totalVenta, 0, ',', '.') }}</div>
                <div class="kpi-sub">Potencial de venta</div>
            </div>
        </div>

        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Margen potencial</div>
                <div class="kpi-value">${{ number_format($margenPot, 0, ',', '.') }}</div>
                <div class="kpi-sub">Si se vende todo el stock</div>
            </div>
        </div>

        <div class="kpi kpi-red">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Alertas de stock</div>
                <div class="kpi-value">{{ $criticos + $bajos }}</div>
                <div class="kpi-sub">{{ $criticos }} críticos · {{ $bajos }} bajos</div>
            </div>
        </div>
    </div>

    @php $hayFiltrosInv = request()->anyFilled(['buscar', 'estado', 'tipo']); @endphp

    <div class="card filter-card no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.inventario') }}" autocomplete="off">
                <input type="hidden" name="mes" value="{{ $mes }}">
                <input type="hidden" name="anio" value="{{ $anio }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-12 col-lg-4">
                        <label class="form-label" for="rep-buscar">Producto</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="rep-buscar" name="buscar" value="{{ request('buscar') }}" class="form-control"
                                placeholder="Código, nombre o modelo">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="rep-estado">Estado del stock</label>
                        <select id="rep-estado" name="estado" class="form-select" data-auto>
                            <option value="">Todos</option>
                            <option value="normal" {{ request('estado') === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="bajo" {{ request('estado') === 'bajo' ? 'selected' : '' }}>Stock bajo</option>
                            <option value="agotado" {{ request('estado') === 'agotado' ? 'selected' : '' }}>Agotados</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label" for="rep-tipo">Tipo de movimiento</label>
                        <select id="rep-tipo" name="tipo" class="form-select" data-auto>
                            <option value="">Todos</option>
                            <option value="entrada" {{ request('tipo') === 'entrada' ? 'selected' : '' }}>Entradas</option>
                            <option value="salida" {{ request('tipo') === 'salida' ? 'selected' : '' }}>Salidas</option>
                            <option value="ajuste" {{ request('tipo') === 'ajuste' ? 'selected' : '' }}>Ajustes</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Filtrar</button>
                            @if ($hayFiltrosInv)
                                <a href="{{ route('reportes.inventario', ['mes' => $mes, 'anio' => $anio]) }}"
                                    class="btn btn-outline-secondary" title="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="section-title">
        <i class="bi bi-boxes"></i> Estado actual del inventario
        <span class="cell-sub ms-2">{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto activo' : 'productos activos' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">P. compra</th>
                    <th class="text-end">P. venta</th>
                    <th class="text-end">Valor total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $prod)
                    <tr>
                        <td class="cell-code">{{ $prod->codigo }}</td>
                        <td>
                            <span class="cell-title">{{ $prod->nombre }}</span>
                            @if ($prod->modelo)
                                <span class="cell-sub"> — {{ $prod->modelo }}</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">{{ $prod->stock }}</td>
                        <td class="text-center">
                            @if ($prod->estado_stock === 'agotado')
                                <span class="badge bg-danger"><i class="bi bi-exclamation-circle-fill"></i> Crítico</span>
                            @elseif ($prod->estado_stock === 'bajo')
                                <span class="badge bg-warning"><i class="bi bi-exclamation-triangle-fill"></i> Bajo</span>
                            @else
                                <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Normal</span>
                            @endif
                        </td>
                        <td class="text-end">${{ number_format($prod->precio_compra, 0, ',', '.') }}</td>
                        <td class="text-end">${{ number_format($prod->precio_venta, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-success">${{ number_format($prod->precio_venta * $prod->stock, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <strong>No hay productos activos</strong>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if ($productos->total() > 0)
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end">Valor total del inventario</td>
                        <td class="text-end text-success">${{ number_format($totalVenta, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div class="paginacion-wrap">{{ $productos->links() }}</div>

    <div class="section-title">
        <i class="bi bi-arrow-left-right"></i> Movimientos de {{ $mesNombre }} {{ $anio }}
        <span class="cell-sub ms-2">{{ $movimientos->total() }} {{ $movimientos->total() === 1 ? 'movimiento' : 'movimientos' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Cantidad</th>
                    <th>Referencia</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movimientos as $mov)
                    <tr>
                        <td class="cell-title">{{ $mov->producto->nombre ?? '—' }}</td>
                        <td class="text-center">
                            @if ($mov->tipo == 'entrada')
                                <span class="badge bg-success"><i class="bi bi-arrow-down-circle"></i> Entrada</span>
                            @elseif ($mov->tipo == 'salida')
                                <span class="badge bg-danger"><i class="bi bi-arrow-up-circle"></i> Salida</span>
                            @else
                                <span class="badge bg-warning"><i class="bi bi-pencil-square"></i> Ajuste</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">{{ $mov->cantidad }}</td>
                        <td>{{ $mov->referencia ?? '—' }}</td>
                        <td class="text-nowrap">{{ optional($mov->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <strong>No hay movimientos en este período</strong>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">{{ $movimientos->links() }}</div>

@endsection
