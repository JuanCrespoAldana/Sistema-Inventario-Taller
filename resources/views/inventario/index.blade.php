@extends('layouts.app')

@section('title', 'Inventario')
@section('page-icon', 'box-seam')
@section('page-title', 'Inventario')
@section('page-subtitle', 'Productos activos, existencias y valor del inventario.')

@section('page-actions')
    <a href="{{ route('inventario.importar.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-spreadsheet"></i> Importar / Exportar
    </a>
    <a href="{{ route('inventario.ajustes.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-sliders"></i> Ajustar stock
    </a>
    <a href="{{ route('inventario.productos.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nuevo producto
    </a>
@endsection

@section('content')

    <div class="kpi-grid">
        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-currency-dollar"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Valor a precio de compra</div>
                <div class="kpi-value">${{ number_format($valorCompra, 0, ',', '.') }}</div>
                <div class="kpi-sub">Costo del inventario actual</div>
            </div>
        </div>

        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Valor a precio de venta</div>
                <div class="kpi-value">${{ number_format($valorVenta, 0, ',', '.') }}</div>
                <div class="kpi-sub">Potencial de venta</div>
            </div>
        </div>

        <div class="kpi kpi-red {{ $productosCriticos > 0 ? 'kpi-alert' : '' }}">
            <div class="kpi-icon"><i class="bi bi-exclamation-octagon"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Productos críticos</div>
                <div class="kpi-value">{{ $productosCriticos }}</div>
                <div class="kpi-sub">Sin existencias</div>
            </div>
        </div>

        <div class="kpi kpi-amber">
            <div class="kpi-icon"><i class="bi bi-exclamation-circle"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Stock bajo</div>
                <div class="kpi-value">{{ $productosBajos }}</div>
                <div class="kpi-sub">En o por debajo del mínimo</div>
            </div>
        </div>
    </div>

    @php
        $filtrosExtra = ['categoria', 'proveedor', 'ubicacion', 'precio_min', 'precio_max'];
        $activosExtra = collect($filtrosExtra)->filter(fn($f) => request()->filled($f))->count();
        $hayFiltros = request()->anyFilled(['buscar', 'filtro_stock', 'categoria', 'proveedor', 'ubicacion', 'precio_min', 'precio_max']);
        $stockActual = request('filtro_stock', '');
        $pestanasStock = [
            '' => ['Todos', $totalProductos],
            'disponible' => ['Con stock', max(0, $totalProductos - $productosCriticos - $productosBajos)],
            'bajo' => ['Stock bajo', $productosBajos],
            'critico' => ['Agotados', $productosCriticos],
            'sin_rotacion' => ['Sin ventas en 90 días', $sinRotacion],
        ];
    @endphp

    <div class="card filter-card">
        <div class="card-body">
            <form method="GET" action="{{ route('inventario.index') }}" id="formBusqueda" autocomplete="off"
                class="d-flex flex-column gap-3">
                <input type="hidden" name="filtro_stock" value="{{ $stockActual }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-12 col-lg-5">
                        <label for="buscar" class="form-label">Buscar</label>
                        <div class="ac-wrap">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="buscar" name="buscar" value="{{ request('buscar') }}"
                                    class="form-control" placeholder="Código, nombre o modelo del producto" role="combobox"
                                    aria-expanded="false" aria-autocomplete="list" aria-controls="autocomplete-lista">
                            </div>
                            <ul id="autocomplete-lista" role="listbox" class="ac-list"></ul>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label for="orden" class="form-label">Ordenar por</label>
                        <select name="orden" id="orden" class="form-select" data-auto>
                            <option value="reciente" {{ request('orden', 'reciente') === 'reciente' ? 'selected' : '' }}>Más recientes</option>
                            <option value="nombre" {{ request('orden') === 'nombre' ? 'selected' : '' }}>Nombre (A–Z)</option>
                            <option value="stock_asc" {{ request('orden') === 'stock_asc' ? 'selected' : '' }}>Menos stock primero</option>
                            <option value="stock_desc" {{ request('orden') === 'stock_desc' ? 'selected' : '' }}>Más stock primero</option>
                            <option value="precio_desc" {{ request('orden') === 'precio_desc' ? 'selected' : '' }}>Mayor precio de venta</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                            <button type="button"
                                class="btn btn-outline-secondary btn-filtros-extra {{ $activosExtra ? '' : 'collapsed' }}"
                                data-bs-toggle="collapse" data-bs-target="#inventarioFiltrosExtra"
                                aria-expanded="{{ $activosExtra ? 'true' : 'false' }}">
                                Más filtros
                                @if ($activosExtra)
                                    <span class="filtro-count">{{ $activosExtra }}</span>
                                @endif
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            @if ($hayFiltros)
                                <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary"
                                    title="Limpiar filtros">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="chip-row">
                    <span class="chip-label">Stock</span>
                    @foreach ($pestanasStock as $valor => $datos)
                        <a href="{{ request()->fullUrlWithQuery(['filtro_stock' => $valor === '' ? null : $valor, 'page' => null]) }}"
                            class="chip {{ (string) $stockActual === (string) $valor ? 'activo' : '' }}">
                            {{ $datos[0] }} <span class="chip-count">{{ number_format($datos[1], 0, ',', '.') }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="collapse filtros-extra {{ $activosExtra ? 'show' : '' }}" id="inventarioFiltrosExtra">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="categoria">Categoría</label>
                            <select id="categoria" name="categoria" class="form-select" data-auto>
                                <option value="">Todas</option>
                                <option value="__sin" {{ request('categoria') === '__sin' ? 'selected' : '' }}>Sin categoría</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria }}" {{ request('categoria') === $categoria ? 'selected' : '' }}>{{ $categoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="proveedor">Proveedor</label>
                            <select id="proveedor" name="proveedor" class="form-select" data-auto>
                                <option value="">Todos</option>
                                @foreach ($proveedores as $proveedor)
                                    <option value="{{ $proveedor }}" {{ request('proveedor') === $proveedor ? 'selected' : '' }}>{{ $proveedor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="ubicacion">Ubicación</label>
                            <input type="text" id="ubicacion" name="ubicacion" value="{{ request('ubicacion') }}"
                                class="form-control" placeholder="Ej: Estante A">
                        </div>
                    </div>
                    <div class="row g-3 mt-0">
                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="precio_min">Precio de venta mínimo</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" id="precio_min" name="precio_min" value="{{ request('precio_min') }}"
                                    class="form-control money" inputmode="numeric" placeholder="0">
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label" for="precio_max">Precio de venta máximo</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" id="precio_max" name="precio_max" value="{{ request('precio_max') }}"
                                    class="form-control money" inputmode="numeric" placeholder="Sin límite">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="result-summary">
        <span><strong>{{ number_format($productos->total(), 0, ',', '.') }}</strong> {{ $productos->total() == 1 ? 'producto' : 'productos' }}{{ $hayFiltros ? ' con estos filtros' : '' }}</span>
        <a href="{{ route('inventario.exportar', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exportar {{ $hayFiltros ? 'esta lista' : 'inventario' }}
        </a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th class="text-end">Precio de venta</th>
                    <th class="text-end">Costo</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $producto)
                    @php
                        $estadoStock = $producto->estado_stock;
                        $claseFila = $estadoStock === 'agotado' ? 'fila-agotado' : ($estadoStock === 'bajo' ? 'fila-bajo' : '');
                        $detalleProducto = collect([$producto->modelo, $producto->categoria, $producto->ubicacion])->filter()->implode(' · ');
                        $margen = $producto->precio_venta > 0
                            ? round((($producto->precio_venta - $producto->precio_compra) / $producto->precio_venta) * 100)
                            : null;
                    @endphp
                    <tr class="fila-estado {{ $claseFila }}">
                        <td class="cell-code">{{ $producto->codigo }}</td>
                        <td>
                            <div class="cell-title">{{ $producto->nombre }}</div>
                            @if ($detalleProducto !== '')
                                <div class="cell-sub">{{ $detalleProducto }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="cell-amount">${{ number_format($producto->precio_venta, 0, ',', '.') }}</div>
                            @if ($margen !== null)
                                <div class="cell-sub">Margen {{ $margen }}%</div>
                            @endif
                        </td>
                        <td class="text-end cell-cost">${{ number_format($producto->precio_compra, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if ($estadoStock === 'agotado')
                                <span class="badge bg-danger">Agotado</span>
                            @elseif ($estadoStock === 'bajo')
                                <span class="badge bg-warning" title="Stock mínimo: {{ $producto->stock_minimo }}">{{ $producto->stock }} · bajo</span>
                            @else
                                <span class="badge bg-success">{{ $producto->stock }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group-actions">
                                <a href="{{ route('inventario.kardex', $producto) }}" class="btn btn-outline-secondary btn-icon"
                                    title="Ver historial (kardex)">
                                    <i class="bi bi-clock-history"></i>
                                </a>

                                <button type="button" class="btn btn-outline-primary btn-icon btn-editar-producto"
                                    title="Editar producto"
                                    data-id="{{ $producto->id }}"
                                    data-codigo="{{ $producto->codigo }}"
                                    data-nombre="{{ $producto->nombre }}"
                                    data-modelo="{{ $producto->modelo ?? '' }}"
                                    data-categoria="{{ $producto->categoria ?? '' }}"
                                    data-ubicacion="{{ $producto->ubicacion ?? '' }}"
                                    data-proveedor="{{ $producto->proveedor ?? '' }}"
                                    data-stock-minimo="{{ $producto->stock_minimo }}"
                                    data-precio-compra="{{ $producto->precio_compra }}"
                                    data-precio-venta="{{ $producto->precio_venta }}"
                                    data-url="{{ route('inventario.productos.update', $producto) }}">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <form action="{{ route('inventario.productos.destroy', $producto) }}" method="POST"
                                    class="d-inline m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-outline-danger btn-icon btn-confirm-delete"
                                        title="Eliminar producto"
                                        data-message="¿Eliminar el producto «{{ $producto->nombre }}»? Esta acción no se puede deshacer.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-box"></i>
                                <strong>No se encontraron productos</strong>
                                <span>{{ $hayFiltros ? 'Prueba con otros filtros o registra un producto nuevo.' : 'Registra tu primer producto para empezar.' }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $productos->links() }}
    </div>

@endsection

@push('modals')
    <div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="formEditarProducto">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar producto</h5>
                            <p class="modal-subtitle">El stock se cambia desde «Ajustar stock».</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-block">
                            <div class="form-block-head"><span><i class="bi bi-tag"></i> Identificación</span></div>
                            <div class="form-block-body">
                                <div class="fields-grid">
                                    <div>
                                        <label class="form-label" for="edit_codigo">Código</label>
                                        <input type="text" name="codigo" id="edit_codigo" class="form-control" required>
                                    </div>
                                    <div>
                                        <label class="form-label" for="edit_modelo">Modelo</label>
                                        <input type="text" name="modelo" id="edit_modelo" class="form-control">
                                    </div>
                                    <div class="span-all">
                                        <label class="form-label" for="edit_nombre">Nombre del producto</label>
                                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-block">
                            <div class="form-block-head"><span><i class="bi bi-cash-coin"></i> Precios</span></div>
                            <div class="form-block-body">
                                <div class="fields-grid">
                                    <div>
                                        <label class="form-label" for="edit_precio_compra">Precio de compra</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" name="precio_compra" id="edit_precio_compra"
                                                class="form-control money-input" inputmode="numeric" required>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label" for="edit_precio_venta">Precio de venta</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" name="precio_venta" id="edit_precio_venta"
                                                class="form-control money-input" inputmode="numeric" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-block">
                            <div class="form-block-head"><span><i class="bi bi-boxes"></i> Organización</span></div>
                            <div class="form-block-body">
                                <div class="fields-grid">
                                    <div>
                                        <label class="form-label" for="edit_categoria">Categoría</label>
                                        <input type="text" name="categoria" id="edit_categoria" class="form-control"
                                            list="listaCategorias" maxlength="80" placeholder="Ej: Válvulas">
                                    </div>
                                    <div>
                                        <label class="form-label" for="edit_ubicacion">Ubicación</label>
                                        <input type="text" name="ubicacion" id="edit_ubicacion" class="form-control"
                                            maxlength="80" placeholder="Ej: Estante A-2">
                                    </div>
                                    <div>
                                        <label class="form-label" for="edit_proveedor">Proveedor</label>
                                        <input type="text" name="proveedor" id="edit_proveedor" class="form-control"
                                            list="listaProveedores" maxlength="120" placeholder="Ej: Distribuidora Andina">
                                    </div>
                                    <div>
                                        <label class="form-label" for="edit_stock_minimo">Stock mínimo</label>
                                        <input type="number" name="stock_minimo" id="edit_stock_minimo" class="form-control"
                                            min="0" max="100000" inputmode="numeric" placeholder="2">
                                    </div>
                                </div>
                                <datalist id="listaCategorias">
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria }}"></option>
                                    @endforeach
                                </datalist>
                                <datalist id="listaProveedores">
                                    @foreach ($proveedores as $proveedor)
                                        <option value="{{ $proveedor }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        (function () {
            function resaltar(texto, query) {
                if (!query) return escaparHtml(texto);
                const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                return escaparHtml(texto).replace(re, '<mark>$1</mark>');
            }

            const inputBuscar = document.getElementById('buscar');
            const lista = document.getElementById('autocomplete-lista');
            const formBusqueda = document.getElementById('formBusqueda');
            const urlBuscar = @json(route('inventario.buscar.producto'));

            let timer = null;
            let activeIndex = -1;
            let ultimaQuery = '';

            function mostrarLista(html) {
                lista.innerHTML = html;
                lista.style.display = 'block';
                inputBuscar.setAttribute('aria-expanded', 'true');
                activeIndex = -1;
            }

            function ocultarLista() {
                lista.style.display = 'none';
                lista.innerHTML = '';
                inputBuscar.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
            }

            function etiquetaStock(stock, minimo) {
                if (stock <= 0) return '<span class="ac-stock text-danger">● Sin stock</span>';
                if (stock <= minimo) return '<span class="ac-stock text-warning">● Stock: ' + stock + '</span>';
                return '<span class="ac-stock text-success">● Stock: ' + stock + '</span>';
            }

            function renderSugerencias(productos, query) {
                if (!productos.length) {
                    mostrarLista('<li class="ac-empty">Sin resultados para «' + escaparHtml(query) + '»</li>');
                    return;
                }

                mostrarLista(productos.map(function (p, i) {
                    return '<li class="ac-item" role="option" data-value="' + escaparHtml(p.nombre) + '" data-index="' + i + '">' +
                        '<div>' +
                        '<div class="ac-codigo">' + resaltar(p.codigo, query) + '</div>' +
                        '<div class="ac-nombre">' + resaltar(p.nombre, query) + '</div>' +
                        (p.modelo ? '<div class="ac-modelo">' + escaparHtml(p.modelo) + '</div>' : '') +
                        '</div>' +
                        '<div class="text-end flex-shrink-0">' + etiquetaStock(p.stock, p.stock_minimo) + '</div>' +
                        '</li>';
                }).join(''));
            }

            async function buscar(query) {
                if (query.length < 2) { ocultarLista(); return; }
                if (query === ultimaQuery) return;
                ultimaQuery = query;

                mostrarLista('<li class="ac-loading"><span class="spinner-border spinner-border-sm me-2"></span>Buscando…</li>');

                try {
                    const res = await fetch(urlBuscar + '?q=' + encodeURIComponent(query));
                    if (!res.ok) throw new Error();
                    renderSugerencias(await res.json(), query);
                } catch (e) {
                    ocultarLista();
                }
            }

            inputBuscar.addEventListener('input', function () {
                clearTimeout(timer);
                const q = this.value.trim();
                if (q.length < 2) { ocultarLista(); ultimaQuery = ''; return; }
                timer = setTimeout(function () { buscar(q); }, 220);
            });

            inputBuscar.addEventListener('keydown', function (e) {
                const items = lista.querySelectorAll('li[data-value]');
                if (!items.length) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, items.length - 1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                } else if (e.key === 'Escape') {
                    ocultarLista();
                    return;
                } else if (e.key === 'Enter' && activeIndex >= 0) {
                    e.preventDefault();
                    items[activeIndex].click();
                    return;
                } else {
                    return;
                }

                items.forEach(function (li, i) { li.classList.toggle('ac-active', i === activeIndex); });
                if (items[activeIndex]) items[activeIndex].scrollIntoView({ block: 'nearest' });
            });

            lista.addEventListener('click', function (e) {
                const li = e.target.closest('li[data-value]');
                if (!li) return;
                inputBuscar.value = li.dataset.value;
                ocultarLista();
                formBusqueda.submit();
            });

            document.addEventListener('click', function (e) {
                if (!inputBuscar.contains(e.target) && !lista.contains(e.target)) ocultarLista();
            });

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-editar-producto');
                if (!btn) return;

                document.getElementById('edit_codigo').value = btn.dataset.codigo;
                document.getElementById('edit_nombre').value = btn.dataset.nombre;
                document.getElementById('edit_modelo').value = btn.dataset.modelo;
                document.getElementById('edit_categoria').value = btn.dataset.categoria || '';
                document.getElementById('edit_ubicacion').value = btn.dataset.ubicacion || '';
                document.getElementById('edit_proveedor').value = btn.dataset.proveedor || '';
                document.getElementById('edit_stock_minimo').value = btn.dataset.stockMinimo || '';
                document.getElementById('edit_precio_compra').value = formatoPesos(btn.dataset.precioCompra);
                document.getElementById('edit_precio_venta').value = formatoPesos(btn.dataset.precioVenta);
                document.getElementById('formEditarProducto').action = btn.dataset.url;

                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarProducto')).show();
            });

            document.addEventListener('click', async function (e) {
                const btn = e.target.closest('.btn-confirm-delete');
                if (!btn) return;

                const form = btn.closest('form');
                const ok = await confirmDialog(btn.dataset.message || '¿Confirmas la eliminación?', { confirmar: 'Eliminar' });
                if (ok && form) form.submit();
            });
        })();
    </script>
@endpush
