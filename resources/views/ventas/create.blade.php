@extends('layouts.app')

@section('title', 'Nueva venta')
@section('page-icon', 'cart-plus')
@section('page-title', 'Nueva venta')
@section('page-subtitle', 'Busca los productos, arma el carrito y registra la venta.')

@section('page-actions')
    <a href="{{ route('ventas.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-receipt-cutoff"></i> Historial de ventas
    </a>
@endsection

@section('content')

    <form method="POST" action="{{ route('ventas.store') }}" id="formVenta" autocomplete="off">
        @csrf

        <div class="row g-4">

            <div class="col-lg-5">
                <div class="card card-flat">
                    <div class="card-header">
                        <span><i class="bi bi-search"></i> Agregar producto</span>
                    </div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label class="form-label" for="buscador">Producto</label>
                            <div class="ac-wrap">
                                <input type="text" class="form-control" id="buscador"
                                    placeholder="Busca por código o nombre">
                                <div id="autocomplete" class="ac-list"></div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label" for="cantidad">Cantidad</label>
                                <input type="number" name="cantidad" class="form-control" id="cantidad" min="1"
                                    value="{{ old('cantidad', 1) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="precio">Precio unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="precio" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label" for="descuento_producto">Descuento</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" name="descuento_producto" class="form-control money-input"
                                        id="descuento_producto" value="{{ old('descuento_producto', 0) }}" inputmode="numeric">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="subtotal">Subtotal</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="subtotal" readonly>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100" id="agregar">
                            <i class="bi bi-cart-plus"></i> Agregar al carrito
                        </button>

                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-flat">
                    <div class="card-header">
                        <span><i class="bi bi-cart3"></i> Carrito</span>
                        <small id="carrito-contador">Sin productos</small>
                    </div>

                    <div class="table-responsive">
                        <table class="table" id="carrito">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Desc.</th>
                                    <th class="text-end">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="card-body">
                        <div class="resumen-box mt-0">
                            <div class="linea">
                                <span>Subtotal</span>
                                <strong>$ <span id="res_subtotal">0</span></strong>
                            </div>
                            <div class="linea descuento">
                                <span>Descuento</span>
                                <strong>- $ <span id="res_descuento">0</span></strong>
                            </div>
                            <div class="linea total">
                                <span>Total a pagar</span>
                                <strong>$ <span id="res_total">0</span></strong>
                            </div>
                        </div>

                        <div id="productos-hidden"></div>

                        <input type="hidden" name="total" id="total_input">
                        <input type="hidden" name="descuento" id="descuento_input">
                        <input type="hidden" name="total_final" id="total_final_input">

                        <button type="submit" class="btn btn-success btn-lg w-100 mt-4">
                            <i class="bi bi-check2-circle"></i> Registrar venta
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>

@endsection

@push('scripts')
    <script>
        const buscadorUrl = @json(route('inventario.buscar.producto'));
        let carrito = [];
        let productoActual = null;

        const buscador = document.getElementById('buscador');
        const autocomplete = document.getElementById('autocomplete');
        const cantidad = document.getElementById('cantidad');
        const precio = document.getElementById('precio');
        const descuentoProd = document.getElementById('descuento_producto');
        const subtotalInput = document.getElementById('subtotal');
        const carritoBody = document.querySelector('#carrito tbody');

        const resSubtotal = document.getElementById('res_subtotal');
        const resDescuento = document.getElementById('res_descuento');
        const resTotal = document.getElementById('res_total');

        const totalInput = document.getElementById('total_input');
        const descuentoInput = document.getElementById('descuento_input');
        const totalFinalInput = document.getElementById('total_final_input');

        function normalizar(texto) {
            return texto
                .toLowerCase()
                .normalize('NFD')
                .replace(/[̀-ͯ]/g, '')
                .trim();
        }

        let timerBusqueda = null;
        let controladorBusqueda = null;

        function cancelarBusqueda() {
            clearTimeout(timerBusqueda);
            if (controladorBusqueda) {
                controladorBusqueda.abort();
                controladorBusqueda = null;
            }
        }

        function ocultarSugerencias() {
            autocomplete.innerHTML = '';
            autocomplete.style.display = 'none';
        }

        function colorStock(stock, minimo) {
            if (stock <= 0) return 'text-danger';
            if (stock <= (minimo === undefined ? 2 : minimo)) return 'text-warning';
            return 'text-success';
        }

        function buscarProductos(query) {
            if (!normalizar(query)) {
                ocultarSugerencias();
                return;
            }

            if (controladorBusqueda) controladorBusqueda.abort();
            controladorBusqueda = new AbortController();

            fetch(buscadorUrl + '?q=' + encodeURIComponent(query), { signal: controladorBusqueda.signal })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    autocomplete.innerHTML = '';

                    if (!data.length) {
                        autocomplete.innerHTML = '<div class="ac-empty">Sin resultados</div>';
                        autocomplete.style.display = 'block';
                        return;
                    }

                    data.forEach(function (p) {
                        const item = document.createElement('div');
                        item.className = 'ac-item';
                        item.innerHTML =
                            '<div><div class="ac-codigo">' + escaparHtml(p.codigo) + '</div>' +
                            '<div class="ac-nombre">' + escaparHtml(p.nombre) + '</div></div>' +
                            '<div class="ac-stock ' + colorStock(p.stock, p.stock_minimo) + '">Stock: ' + escaparHtml(p.stock) + '</div>';
                        item.addEventListener('click', function () { seleccionar(p); });
                        autocomplete.appendChild(item);
                    });

                    autocomplete.style.display = 'block';
                })
                .catch(function (err) {
                    if (err.name !== 'AbortError') console.error(err);
                });
        }

        buscador.addEventListener('input', function () {
            cancelarBusqueda();

            if (!normalizar(buscador.value)) {
                ocultarSugerencias();
                return;
            }

            const valor = buscador.value;
            timerBusqueda = setTimeout(function () { buscarProductos(valor); }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.ac-wrap')) ocultarSugerencias();
        });

        function seleccionar(p) {
            cancelarBusqueda();
            productoActual = p;
            buscador.value = p.codigo + ' — ' + p.nombre;
            precio.value = formatoPesos(p.precio_venta);
            ocultarSugerencias();
            calcularProducto();
        }

        function calcularProducto() {
            if (!productoActual) return;

            const cant = parseInt(cantidad.value, 10) || 0;
            const desc = valorNumerico(descuentoProd);

            subtotalInput.value = formatoPesos(Math.max((productoActual.precio_venta * cant) - desc, 0));
        }

        cantidad.addEventListener('input', calcularProducto);
        descuentoProd.addEventListener('input', calcularProducto);

        document.getElementById('agregar').addEventListener('click', function () {
            if (!productoActual) return showAppAlert('Selecciona un producto de la lista.', 'warning');

            const cantidadSeleccionada = parseInt(cantidad.value, 10);
            if (!cantidadSeleccionada || cantidadSeleccionada <= 0) {
                return showAppAlert('Ingresa una cantidad válida.', 'warning');
            }

            const descuentoSeleccionado = valorNumerico(descuentoProd);
            if (descuentoSeleccionado > productoActual.precio_venta * cantidadSeleccionada) {
                return showAppAlert('El descuento no puede ser mayor que el subtotal del producto.', 'warning');
            }

            const cantidadEnCarrito = carrito
                .filter(function (item) { return item.producto_id === productoActual.id; })
                .reduce(function (sum, item) { return sum + item.cantidad; }, 0);

            if (cantidadSeleccionada + cantidadEnCarrito > productoActual.stock) {
                return showAppAlert('Stock insuficiente. Disponible: ' + (productoActual.stock - cantidadEnCarrito), 'warning');
            }

            const existenteIndex = carrito.findIndex(function (item) { return item.producto_id === productoActual.id; });

            if (existenteIndex !== -1) {
                carrito[existenteIndex].cantidad += cantidadSeleccionada;
                carrito[existenteIndex].descuento += descuentoSeleccionado;
            } else {
                carrito.push({
                    producto_id: productoActual.id,
                    nombre: productoActual.nombre,
                    cantidad: cantidadSeleccionada,
                    precio: productoActual.precio_venta,
                    descuento: descuentoSeleccionado
                });
            }

            limpiar();
            render();
            buscador.focus();
        });

        function limpiar() {
            cancelarBusqueda();
            buscador.value = '';
            cantidad.value = 1;
            precio.value = '';
            descuentoProd.value = 0;
            subtotalInput.value = '';
            productoActual = null;
        }

        function render() {
            if (carrito.length === 0) {
                carritoBody.innerHTML =
                    '<tr><td colspan="6"><div class="empty-state"><i class="bi bi-cart"></i>' +
                    '<strong>El carrito está vacío</strong><span>Agrega productos para registrar la venta.</span></div></td></tr>';
            } else {
                carritoBody.innerHTML = carrito.map(function (p, i) {
                    const total = (p.precio * p.cantidad) - p.descuento;
                    return '<tr>' +
                        '<td class="cell-title">' + escaparHtml(p.nombre) + '</td>' +
                        '<td class="text-center">' + p.cantidad + '</td>' +
                        '<td class="text-end">$ ' + formatoPesos(p.precio) + '</td>' +
                        '<td class="text-end text-danger">- $ ' + formatoPesos(p.descuento) + '</td>' +
                        '<td class="text-end fw-semibold">$ ' + formatoPesos(total) + '</td>' +
                        '<td class="text-end"><button type="button" class="btn btn-outline-danger btn-icon" title="Quitar" onclick="eliminar(' + i + ')"><i class="bi bi-trash"></i></button></td>' +
                        '</tr>';
                }).join('');
            }

            document.getElementById('carrito-contador').textContent =
                carrito.length === 0 ? 'Sin productos' : carrito.length + (carrito.length === 1 ? ' producto' : ' productos');

            totales();
        }

        function eliminar(i) {
            carrito.splice(i, 1);
            render();
        }

        function totales() {
            const sub = carrito.reduce(function (a, b) { return a + (b.precio * b.cantidad); }, 0);
            const desc = carrito.reduce(function (a, b) { return a + b.descuento; }, 0);
            const total = sub - desc;

            resSubtotal.textContent = formatoPesos(sub);
            resDescuento.textContent = formatoPesos(desc);
            resTotal.textContent = formatoPesos(total);

            totalInput.value = sub;
            descuentoInput.value = desc;
            totalFinalInput.value = total;

            document.getElementById('productos-hidden').innerHTML = carrito.map(function (item, index) {
                return '<input type="hidden" name="productos[' + index + '][producto_id]" value="' + item.producto_id + '">' +
                    '<input type="hidden" name="productos[' + index + '][cantidad]" value="' + item.cantidad + '">' +
                    '<input type="hidden" name="productos[' + index + '][descuento]" value="' + item.descuento + '">';
            }).join('');
        }

        document.getElementById('formVenta').addEventListener('submit', function (e) {
            if (carrito.length === 0) {
                e.preventDefault();
                showAppAlert('Agrega al menos un producto al carrito.', 'warning');
            }
        });

        render();
    </script>
@endpush
