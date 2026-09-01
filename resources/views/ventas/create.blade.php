@extends('layouts.app')

@section('content')
    <div class="venta-wrapper">
        <div class="venta-card">
            <h2 class="venta-title">
                <i class="bi bi-cart-plus-fill me-2" style="color:#2563eb;font-size:1.5rem;vertical-align:middle;"></i>
                Nueva Venta
            </h2>
            <hr class="mt-3 mb-4" style="border-color:#e5e7eb;">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('ventas.store') }}">
                @csrf

                {{-- BUSCADOR --}}
                <div class="form-group position-relative">
                    <label>
                        <i class="bi bi-search me-1"></i> Buscar producto
                    </label>
                    <input type="text" class="form-control" id="buscador" placeholder="Buscar por código o nombre...">
                    <div id="autocomplete" class="autocomplete-box"></div>
                </div>

                {{-- DATOS --}}
                <div class="grid">
                    <div class="form-group">
                        <label>Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" id="cantidad" min="1" value="{{ old('cantidad', 1) }}">
                    </div>

                    <div class="form-group">
                        <label>Precio Unitario</label>
                        <input type="text" class="form-control" id="precio" readonly>
                    </div>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Descuento del producto</label>
                        <input type="text" name="descuento_producto" class="form-control" id="descuento_producto" value="{{ old('descuento_producto', 0) }}">
                    </div>

                    <div class="form-group">
                        <label>Subtotal</label>
                        <input type="text" class="form-control" id="subtotal" readonly>
                    </div>
                </div>

                <button type="button" class="btn btn-secondary w-100 mb-4" id="agregar">
                    <i class="bi bi-cart-plus me-1"></i> Agregar al carrito
                </button>

                {{-- TABLA --}}
                <table class="table" id="carrito">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cant.</th>
                            <th>Precio</th>
                            <th>Desc.</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                {{-- RESUMEN --}}
                <div class="resumen-box">
                    <div class="linea">
                        <span>Subtotal:</span>
                        <strong>$ <span id="res_subtotal">0</span></strong>
                    </div>
                    <div class="linea descuento">
                        <span>Descuento:</span>
                        <strong>- $ <span id="res_descuento">0</span></strong>
                    </div>
                    <div class="linea total">
                        <span>TOTAL FINAL:</span>
                        <strong>$ <span id="res_total">0</span></strong>
                    </div>
                </div>

                {{-- INPUTS QUE LARAVEL LEE --}}
                <div id="productos-hidden"></div>

                <input type="hidden" name="total" id="total_input">
                <input type="hidden" name="descuento" id="descuento_input">
                <input type="hidden" name="total_final" id="total_final_input">

                <button type="submit" class="btn-guardar mt-4">
                    <i class="bi bi-save me-1"></i> Guardar Venta
                </button>
            </form>
        </div>
    </div>

    {{-- ESTILOS --}}
    <style>
        .autocomplete-box {
            position: absolute;
            background: #fff;
            width: 100%;
            border: 1px solid #ddd;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .autocomplete-item {
            padding: 10px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
        }

        .autocomplete-item:hover {
            background: #f1f3f4;
        }
    </style>

    {{-- JS --}}
    <script>
        const buscadorUrl = "{{ route('inventario.buscar.producto') }}";
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

        function pesos(v) {
            return new Intl.NumberFormat('es-CO').format(v);
        }

        function normalizar(texto) {
            return texto
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "") // quita tildes
                .trim();
        }

        function buscarProductos(query) {
            const q = normalizar(query);
            if (!q) {
                autocomplete.innerHTML = '';
                autocomplete.style.display = 'none';
                return;
            }

            fetch(buscadorUrl + '?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    autocomplete.innerHTML = '';

                    if (!data.length) {
                        autocomplete.style.display = 'none';
                        return;
                    }

                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = `<div>${p.codigo} — ${p.nombre}</div><div>Stock: ${p.stock}</div>`;
                        div.onclick = () => seleccionar(p);
                        autocomplete.appendChild(div);
                    });

                    autocomplete.style.display = 'block';
                });
        }

        buscador.addEventListener('input', () => {
            buscarProductos(buscador.value);
        });

        function seleccionar(p) {
            productoActual = p;
            buscador.value = `${p.codigo} — ${p.nombre}`;
            precio.value = pesos(p.precio_venta);
            autocomplete.style.display = 'none';
            calcularProducto();
        }

        function calcularProducto() {
            if (!productoActual) return;

            const cant = parseInt(cantidad.value);
            const desc = valorNumerico(descuentoProd);

            let sub = (productoActual.precio_venta * cant) - desc;
            subtotalInput.value = pesos(Math.max(sub, 0));
        }

        cantidad.oninput = calcularProducto;

        descuentoProd.addEventListener('input', () => {
            formatoMilesInput(descuentoProd);
            calcularProducto();
        });

        document.getElementById('agregar').onclick = () => {
            if (!productoActual) return showAppAlert('Seleccione un producto', 'warning');

            const cantidadSeleccionada = parseInt(cantidad.value);
            if (cantidadSeleccionada <= 0) {
                return showAppAlert('Ingrese una cantidad válida', 'warning');
            }

            const cantidadEnCarrito = carrito
                .filter(item => item.producto_id === productoActual.id)
                .reduce((sum, item) => sum + item.cantidad, 0);

            if (cantidadSeleccionada + cantidadEnCarrito > productoActual.stock) {
                return showAppAlert(`Stock insuficiente. Disponible: ${productoActual.stock - cantidadEnCarrito}`, 'warning');
            }

            const existenteIndex = carrito.findIndex(item => item.producto_id === productoActual.id);
            if (existenteIndex !== -1) {
                carrito[existenteIndex].cantidad += cantidadSeleccionada;
                carrito[existenteIndex].descuento += valorNumerico(descuentoProd);
            } else {
                carrito.push({
                    producto_id: productoActual.id,
                    nombre: productoActual.nombre,
                    cantidad: cantidadSeleccionada,
                    precio: productoActual.precio_venta,
                    descuento: valorNumerico(descuentoProd),
                });
            }

            limpiar();
            render();
        };

        function limpiar() {
            buscador.value = '';
            cantidad.value = 1;
            precio.value = '';
            descuentoProd.value = 0;
            subtotalInput.value = '';
            productoActual = null;
        }

        function render() {
            carritoBody.innerHTML = '';
            carrito.forEach((p, i) => {
                const total = (p.precio * p.cantidad) - p.descuento;
                carritoBody.innerHTML += `
                                                            <tr>
                                                                <td>${p.nombre}</td>
                                                                <td>${p.cantidad}</td>
                                                                <td>$ ${pesos(p.precio)}</td>
                                                                <td>-$ ${pesos(p.descuento)}</td>
                                                                <td>$ ${pesos(total)}</td>
                                                                <td><button type="button" class="btn btn-danger btn-sm" onclick="eliminar(${i})"><i class="bi bi-trash"></i></button></td>
                                                            </tr>
                                                        `;
            });
            totales();
        }

        function eliminar(i) {
            carrito.splice(i, 1);
            render();
        }

        function totales() {
            let sub = carrito.reduce((a, b) => a + (b.precio * b.cantidad), 0);
            let desc = carrito.reduce((a, b) => a + b.descuento, 0);
            let total = sub - desc;

            resSubtotal.innerText = pesos(sub);
            resDescuento.innerText = pesos(desc);
            resTotal.innerText = pesos(total);

            totalInput.value = sub;
            descuentoInput.value = desc;
            totalFinalInput.value = total;

            const container = document.getElementById('productos-hidden');
            container.innerHTML = '';
            carrito.forEach((item, index) => {
                container.innerHTML += `
                                                            <input type="hidden" name="productos[${index}][producto_id]" value="${item.producto_id}">
                                                            <input type="hidden" name="productos[${index}][cantidad]" value="${item.cantidad}">
                                                            <input type="hidden" name="productos[${index}][descuento]" value="${item.descuento}">
                                                        `;
            });
        }

        document.querySelector('form').addEventListener('submit', e => {
            if (carrito.length === 0) {
                e.preventDefault();
                showAppAlert('Agrega al menos un producto', 'warning');
            }
        });

        function formatoMilesInput(input) {
            let valor = input.value.replace(/\D/g, '');
            input.value = valor ? new Intl.NumberFormat('es-CO').format(valor) : '';
        }

        function valorNumerico(input) {
            return parseInt(input.value.replace(/\./g, '')) || 0;
        }
    </script>
@endsection