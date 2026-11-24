@extends('layouts.app')

@section('title', 'Reposición')
@section('page-icon', 'cart-plus')
@section('page-title', 'Reposición de inventario')
@section('page-subtitle', 'Productos agotados o por debajo del mínimo, con la cantidad sugerida a comprar.')

@section('page-actions')
    <a href="{{ route('inventario.reposicion.exportar', request()->query()) }}" class="btn btn-primary {{ $filas->isEmpty() ? 'disabled' : '' }}">
        <i class="bi bi-file-earmark-spreadsheet"></i> Descargar lista de compra
    </a>
@endsection

@section('content')

    @php
        $hayFiltros = request()->anyFilled(['proveedor', 'categoria']);
        $agotados = $filas->where('estado', 'agotado')->count();
        $bajos = $filas->where('estado', 'bajo')->count();
    @endphp

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-cart-plus"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Productos por reponer</div>
                <div class="kpi-value">{{ number_format($filas->count(), 0, ',', '.') }}</div>
                <div class="kpi-sub">{{ $hayFiltros ? 'Con los filtros aplicados' : 'En todo el inventario' }}</div>
            </div>
        </div>

        <div class="kpi kpi-red {{ $agotados > 0 ? 'kpi-alert' : '' }}">
            <div class="kpi-icon"><i class="bi bi-exclamation-octagon"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Agotados</div>
                <div class="kpi-value">{{ $agotados }}</div>
                <div class="kpi-sub">Sin existencias</div>
            </div>
        </div>

        <div class="kpi kpi-amber">
            <div class="kpi-icon"><i class="bi bi-exclamation-circle"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Stock bajo</div>
                <div class="kpi-value">{{ $bajos }}</div>
                <div class="kpi-sub">En o por debajo del mínimo</div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Inversión estimada</div>
                <div class="kpi-value">${{ number_format($totalEstimado, 0, ',', '.') }}</div>
                <div class="kpi-sub">A precio de compra actual</div>
            </div>
        </div>
    </div>

    <div class="card filter-card">
        <div class="card-body">
            <form method="GET" action="{{ route('inventario.reposicion') }}" autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5 col-lg-4">
                        <label class="form-label" for="proveedor">Proveedor</label>
                        <select id="proveedor" name="proveedor" class="form-select" data-auto>
                            <option value="">Todos</option>
                            <option value="__sin" {{ request('proveedor') === '__sin' ? 'selected' : '' }}>Sin proveedor</option>
                            @foreach ($proveedores as $proveedor)
                                <option value="{{ $proveedor }}" {{ request('proveedor') === $proveedor ? 'selected' : '' }}>{{ $proveedor }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-5 col-lg-4">
                        <label class="form-label" for="categoria">Categoría</label>
                        <select id="categoria" name="categoria" class="form-select" data-auto>
                            <option value="">Todas</option>
                            <option value="__sin" {{ request('categoria') === '__sin' ? 'selected' : '' }}>Sin categoría</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria }}" {{ request('categoria') === $categoria ? 'selected' : '' }}>{{ $categoria }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 col-lg-4">
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            @if ($hayFiltros)
                                <a href="{{ route('inventario.reposicion') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="result-summary">
        <span><strong>{{ number_format($filas->count(), 0, ',', '.') }}</strong> {{ $filas->count() == 1 ? 'producto' : 'productos' }} para reponer{{ $hayFiltros ? ' con estos filtros' : '' }}</span>
        <span class="cell-sub">La cantidad sugerida llega al doble del mínimo o cubre lo vendido en los últimos 30 días.</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Proveedor</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Vendidos<br><small>(30 días)</small></th>
                    <th class="text-center">Comprar</th>
                    <th class="text-end">Costo estimado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $f)
                    <tr class="fila-estado {{ $f->estado === 'agotado' ? 'fila-agotado' : 'fila-bajo' }}">
                        <td>
                            <div class="cell-title">{{ $f->nombre }}</div>
                            <div class="cell-code">{{ $f->codigo }}@if ($f->categoria) · {{ $f->categoria }}@endif</div>
                        </td>
                        <td>{{ $f->proveedor ?: '—' }}</td>
                        <td class="text-center">
                            @if ($f->estado === 'agotado')
                                <span class="badge bg-danger">Agotado</span>
                            @else
                                <span class="badge bg-warning" title="Stock mínimo: {{ $f->stock_minimo }}">{{ $f->stock }} · mín. {{ $f->stock_minimo }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $f->vendidos }}</td>
                        <td class="text-center fw-bold">{{ number_format($f->sugerido, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <div class="cell-amount">${{ number_format($f->costo_estimado, 0, ',', '.') }}</div>
                            <div class="cell-sub">${{ number_format($f->precio_compra, 0, ',', '.') }} c/u</div>
                        </td>
                        <td class="text-center">
                            <div class="btn-group-actions">
                                <a href="{{ route('inventario.ajustes.index') }}" class="btn btn-outline-primary btn-icon" title="Registrar entrada o ajuste">
                                    <i class="bi bi-sliders"></i>
                                </a>
                                <a href="{{ route('inventario.kardex', $f->id) }}" class="btn btn-outline-secondary btn-icon" title="Ver historial (kardex)">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>No hay productos por reponer</strong>
                                <span>{{ $hayFiltros ? 'Prueba con otros filtros.' : 'Todos los productos están por encima de su stock mínimo.' }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
