@extends('layouts.app')

@section('title', 'Ventas')
@section('page-icon', 'receipt-cutoff')
@section('page-title', 'Historial de ventas')
@section('page-subtitle', 'Consulta las facturas registradas y descarga su comprobante.')

@section('page-actions')
    <a href="{{ route('ventas.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nueva venta
    </a>
@endsection

@section('content')

    @php
        $filtrosBasicos = ['factura', 'desde', 'hasta'];
        $filtrosExtra = ['producto', 'descuento', 'monto_min', 'monto_max'];
        $hayFiltros = request()->anyFilled(array_merge($filtrosBasicos, $filtrosExtra));
        $activosExtra = collect($filtrosExtra)->filter(fn($f) => request()->filled($f))->count();
    @endphp

    <div class="card filter-card">
        <div class="card-body">
            <form method="GET" action="{{ route('ventas.index') }}" autocomplete="off" class="d-flex flex-column gap-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label" for="factura">N° de factura</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="factura" name="factura" value="{{ request('factura') }}"
                                class="form-control" placeholder="Ej: FAC-120">
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="desde">Desde</label>
                        <input type="date" id="desde" name="desde" value="{{ request('desde') }}" class="form-control">
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label class="form-label" for="hasta">Hasta</label>
                        <input type="date" id="hasta" name="hasta" value="{{ request('hasta') }}" class="form-control">
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label" for="orden">Ordenar por</label>
                        <select id="orden" name="orden" class="form-select" data-auto>
                            <option value="reciente" {{ request('orden', 'reciente') === 'reciente' ? 'selected' : '' }}>Más recientes</option>
                            <option value="antigua" {{ request('orden') === 'antigua' ? 'selected' : '' }}>Más antiguas</option>
                            <option value="monto_desc" {{ request('orden') === 'monto_desc' ? 'selected' : '' }}>Mayor valor</option>
                            <option value="monto_asc" {{ request('orden') === 'monto_asc' ? 'selected' : '' }}>Menor valor</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-filtros-extra {{ $activosExtra ? '' : 'collapsed' }}"
                                data-bs-toggle="collapse" data-bs-target="#ventasFiltrosExtra" aria-expanded="{{ $activosExtra ? 'true' : 'false' }}">
                                Más filtros
                                @if ($activosExtra)
                                    <span class="filtro-count">{{ $activosExtra }}</span>
                                @endif
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            @if ($hayFiltros)
                                <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="chip-row">
                    <span class="chip-label">Rango rápido</span>
                    <button type="button" class="chip" data-rango="hoy">Hoy</button>
                    <button type="button" class="chip" data-rango="semana">Últimos 7 días</button>
                    <button type="button" class="chip" data-rango="mes">Este mes</button>
                    <button type="button" class="chip" data-rango="mes_pasado">Mes pasado</button>
                </div>

                <div class="collapse filtros-extra {{ $activosExtra ? 'show' : '' }}" id="ventasFiltrosExtra">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label" for="producto">Producto vendido</label>
                            <input type="text" id="producto" name="producto" value="{{ request('producto') }}"
                                class="form-control" placeholder="Código o nombre del producto">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label" for="descuento">Descuento</label>
                            <select id="descuento" name="descuento" class="form-select">
                                <option value="">Todas</option>
                                <option value="con" {{ request('descuento') === 'con' ? 'selected' : '' }}>Con descuento</option>
                                <option value="sin" {{ request('descuento') === 'sin' ? 'selected' : '' }}>Sin descuento</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="monto_min">Valor mínimo</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" id="monto_min" name="monto_min" value="{{ request('monto_min') }}"
                                    class="form-control money" inputmode="numeric" placeholder="0">
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="monto_max">Valor máximo</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" id="monto_max" name="monto_max" value="{{ request('monto_max') }}"
                                    class="form-control money" inputmode="numeric" placeholder="Sin límite">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="result-summary">
        <span><strong>{{ number_format($resumen->cantidad, 0, ',', '.') }}</strong> {{ $resumen->cantidad == 1 ? 'venta' : 'ventas' }}{{ $hayFiltros ? ' con estos filtros' : '' }}</span>
        <span>Total vendido: <strong class="resumen-total">${{ number_format($resumen->suma, 0, ',', '.') }}</strong></span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Productos</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($ventas as $venta)
                    @php
                        $detalles = $venta->detalles;
                        $visibles = $detalles->take(2);
                        $restantes = $detalles->count() - $visibles->count();
                    @endphp
                    <tr>
                        <td>
                            <div class="cell-main">{{ $venta->numero_factura }}</div>
                            <div class="cell-sub">{{ $venta->fecha->format('d/m/Y') }}</div>
                        </td>

                        <td>
                            <div class="mini-lista">
                                @foreach ($visibles as $detalle)
                                    <div class="mini-item">
                                        <span class="mini-item-text en-linea">
                                            {{ $detalle->producto->nombre ?? 'Producto eliminado' }}
                                            <span class="cell-sub">× {{ $detalle->cantidad }}</span>
                                        </span>
                                    </div>
                                @endforeach
                                @if ($restantes > 0)
                                    <span class="mini-mas">+ {{ $restantes }} {{ $restantes == 1 ? 'producto más' : 'productos más' }}</span>
                                @endif
                            </div>
                        </td>

                        <td class="text-end">
                            <div class="cell-amount positivo">${{ number_format($venta->total_final, 0, ',', '.') }}</div>
                            @if ($venta->descuento > 0)
                                <span class="cell-note"><i class="bi bi-tag"></i> Desc. ${{ number_format($venta->descuento, 0, ',', '.') }}</span>
                            @endif
                        </td>

                        <td class="text-center">
                            <a href="{{ route('ventas.show', $venta) }}" class="btn btn-outline-primary btn-icon"
                                title="Ver factura">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="bi bi-receipt"></i>
                                <strong>No hay ventas registradas</strong>
                                <span>{{ $hayFiltros ? 'Prueba con otros filtros.' : 'Las ventas que registres aparecerán aquí.' }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $ventas->links() }}
    </div>

@endsection
