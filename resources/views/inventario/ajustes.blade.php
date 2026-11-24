@extends('layouts.app')

@section('title', 'Ajustes de stock')
@section('page-icon', 'sliders')
@section('page-title', 'Ajustes de stock')
@section('page-subtitle', 'Agrega o quita existencias de uno o varios productos.')

@section('page-actions')
    <a href="{{ route('inventario.movimientos.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left-right"></i> Ver movimientos
    </a>
@endsection

@section('content')

    <form id="formAjuste" method="POST" action="{{ route('inventario.ajustes.store') }}" class="form-page" autocomplete="off">
        @csrf

        <div class="form-block">
            <div class="form-block-head">
                <span><i class="bi bi-box-seam"></i> Productos a ajustar</span>
                <button type="button" id="btnAgregarProducto" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Agregar producto
                </button>
            </div>
            <div class="form-block-body">
                <div id="productosSeleccionados" class="d-flex flex-column gap-3"></div>
            </div>
        </div>

                <template id="productoRowTemplate">
                    <div class="producto-row row g-3 align-items-start mx-0 position-relative">
                        <div class="col-md-6">
                            <label class="form-label">Producto</label>
                            <div class="ac-wrap">
                                <input type="text" class="form-control producto-buscar" placeholder="Código o nombre"
                                    autocomplete="off">
                                <input type="hidden" name="producto_id[]" class="producto-id">
                                <div class="ac-list resultados-producto"></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" name="cantidad[]" class="form-control cantidad-producto" min="1"
                                placeholder="0" required>
                        </div>
                        <div class="col-5 col-md-2">
                            <label class="form-label">Stock actual</label>
                            <div class="form-control-plaintext fw-semibold producto-stock">—</div>
                        </div>
                        <div class="col-1 text-end pt-md-4">
                            <button type="button" class="btn btn-outline-danger btn-icon btn-eliminar-producto"
                                title="Quitar producto">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="col-12 info-producto d-none">
                            <div class="form-hint mt-0">
                                <strong class="producto-nombre"></strong> · Código: <span class="producto-codigo"></span>
                            </div>
                        </div>
                    </div>
                </template>

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-arrow-left-right"></i> Tipo de movimiento</span></div>
            <div class="form-block-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="tipo_operacion">Operación</label>
                        <select name="tipo_operacion" id="tipo_operacion"
                            class="form-select @error('tipo_operacion') is-invalid @enderror" required>
                            <option value="">Selecciona una opción</option>
                            <option value="sumar" {{ old('tipo_operacion') == 'sumar' ? 'selected' : '' }}>Agregar stock</option>
                            <option value="restar" {{ old('tipo_operacion') == 'restar' ? 'selected' : '' }}>Quitar stock</option>
                        </select>
                        @error('tipo_operacion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="referencia">Motivo</label>
                        <select name="referencia" id="referencia"
                            class="form-select @error('referencia') is-invalid @enderror" required>
                            <option value="">Selecciona el motivo</option>
                            <option value="Venta" {{ old('referencia') == 'Venta' ? 'selected' : '' }}>Venta</option>
                            <option value="Compra" {{ old('referencia') == 'Compra' ? 'selected' : '' }}>Compra</option>
                            <option value="Devolución" {{ old('referencia') == 'Devolución' ? 'selected' : '' }}>Devolución</option>
                            <option value="Uso interno" {{ old('referencia') == 'Uso interno' ? 'selected' : '' }}>Uso interno</option>
                            <option value="Ajuste" {{ old('referencia') == 'Ajuste' ? 'selected' : '' }}>Ajuste</option>
                        </select>
                        @error('referencia')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        <button type="submit" id="btnGuardar" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-save"></i> Guardar ajuste
        </button>

    </form>

@endsection

@push('scripts')
    <script>
        const motivosAgregar = ['Compra', 'Devolución', 'Ajuste'];
        const motivosQuitar = ['Venta', 'Uso interno', 'Ajuste'];

        function actualizarMotivos(valor) {
            const select = document.getElementById('referencia');
            const valorActual = select.value;
            select.innerHTML = '<option value="">Selecciona el motivo</option>';

            const lista = valor === 'sumar' ? motivosAgregar : (valor === 'restar' ? motivosQuitar : []);

            lista.forEach(function (motivo) {
                const option = document.createElement('option');
                option.value = motivo;
                option.textContent = motivo;
                option.selected = motivo === valorActual;
                select.appendChild(option);
            });
        }

        function actualizarBotonOperacion(valor) {
            const btn = document.getElementById('btnGuardar');

            if (valor === 'sumar') {
                btn.className = 'btn btn-success btn-lg w-100';
                btn.innerHTML = '<i class="bi bi-plus-circle"></i> Agregar stock';
            } else if (valor === 'restar') {
                btn.className = 'btn btn-danger btn-lg w-100';
                btn.innerHTML = '<i class="bi bi-dash-circle"></i> Quitar stock';
            } else {
                btn.className = 'btn btn-primary btn-lg w-100';
                btn.innerHTML = '<i class="bi bi-save"></i> Guardar ajuste';
            }
        }

        document.getElementById('tipo_operacion').addEventListener('change', function () {
            actualizarBotonOperacion(this.value);
            actualizarMotivos(this.value);
        });

        const busquedaProductosUrl = @json(route('inventario.buscar.producto'));
        const productosAnteriores = @json($productosSeleccionadosAnteriores ?? []);

        function crearRowProducto(datosIniciales) {
            const template = document.getElementById('productoRowTemplate');
            const nodo = template.content.firstElementChild.cloneNode(true);

            const inputBuscar = nodo.querySelector('.producto-buscar');
            const hiddenId = nodo.querySelector('.producto-id');
            const stockLabel = nodo.querySelector('.producto-stock');
            const infoContainer = nodo.querySelector('.info-producto');
            const nombreLabel = nodo.querySelector('.producto-nombre');
            const codigoLabel = nodo.querySelector('.producto-codigo');
            const resultados = nodo.querySelector('.resultados-producto');
            const btnEliminar = nodo.querySelector('.btn-eliminar-producto');
            const cantidadInput = nodo.querySelector('.cantidad-producto');

            if (datosIniciales && datosIniciales.id) {
                hiddenId.value = datosIniciales.id;
                inputBuscar.value = (datosIniciales.codigo || '') + ' - ' + (datosIniciales.nombre || '');
                cantidadInput.value = datosIniciales.cantidad || '';
                stockLabel.textContent = datosIniciales.stock ?? '—';
                nombreLabel.textContent = datosIniciales.nombre || '';
                codigoLabel.textContent = datosIniciales.codigo || '';
                if (datosIniciales.nombre) infoContainer.classList.remove('d-none');
            }

            let timerBusqueda = null;
            let controladorBusqueda = null;

            function ocultarResultados() {
                resultados.style.display = 'none';
                resultados.innerHTML = '';
            }

            function cancelarBusqueda() {
                clearTimeout(timerBusqueda);
                if (controladorBusqueda) {
                    controladorBusqueda.abort();
                    controladorBusqueda = null;
                }
            }

            function colorStock(stock, minimo) {
                if (stock <= 0) return 'text-danger';
                if (stock <= (minimo === undefined ? 2 : minimo)) return 'text-warning';
                return 'text-success';
            }

            function buscar(query) {
                controladorBusqueda = new AbortController();

                fetch(busquedaProductosUrl + '?q=' + encodeURIComponent(query), { signal: controladorBusqueda.signal })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        resultados.innerHTML = '';

                        if (!data.length) {
                            resultados.innerHTML = '<div class="ac-empty">Sin resultados</div>';
                            resultados.style.display = 'block';
                            return;
                        }

                        data.forEach(function (producto) {
                            const item = document.createElement('div');
                            item.className = 'ac-item';
                            item.innerHTML =
                                '<div><div class="ac-codigo">' + escaparHtml(producto.codigo) + '</div>' +
                                '<div class="ac-nombre">' + escaparHtml(producto.nombre) + '</div></div>' +
                                '<div class="ac-stock ' + colorStock(producto.stock, producto.stock_minimo) + '">Stock: ' + escaparHtml(producto.stock) + '</div>';

                            item.addEventListener('mousedown', function (event) {
                                event.preventDefault();
                                cancelarBusqueda();
                                hiddenId.value = producto.id;
                                inputBuscar.value = producto.codigo + ' - ' + producto.nombre;
                                inputBuscar.classList.remove('is-invalid');
                                stockLabel.textContent = producto.stock;
                                nombreLabel.textContent = producto.nombre;
                                codigoLabel.textContent = producto.codigo;
                                infoContainer.classList.remove('d-none');
                                ocultarResultados();
                                cantidadInput.focus();
                            });

                            resultados.appendChild(item);
                        });

                        resultados.style.display = 'block';
                    })
                    .catch(function (err) {
                        if (err.name !== 'AbortError') console.error(err);
                    });
            }

            inputBuscar.addEventListener('input', function () {
                const query = this.value.trim();

                hiddenId.value = '';
                stockLabel.textContent = '—';
                infoContainer.classList.add('d-none');

                cancelarBusqueda();

                if (query.length < 1) {
                    ocultarResultados();
                    return;
                }

                timerBusqueda = setTimeout(function () { buscar(query); }, 250);
            });

            inputBuscar.addEventListener('blur', function () {
                setTimeout(ocultarResultados, 150);
            });

            btnEliminar.addEventListener('click', function () {
                nodo.remove();
                if (document.querySelectorAll('#productosSeleccionados .producto-row').length === 0) {
                    agregarProductoRow();
                }
            });

            return nodo;
        }

        function agregarProductoRow(datosIniciales) {
            document.getElementById('productosSeleccionados').appendChild(crearRowProducto(datosIniciales));
        }

        document.getElementById('btnAgregarProducto').addEventListener('click', function () {
            agregarProductoRow();
        });

        const operacionInicial = document.getElementById('tipo_operacion').value;
        actualizarBotonOperacion(operacionInicial);
        actualizarMotivos(operacionInicial);

        if (productosAnteriores.length > 0) {
            productosAnteriores.forEach(function (item) { agregarProductoRow(item); });
        } else {
            agregarProductoRow();
        }

        document.getElementById('formAjuste').addEventListener('submit', function (event) {
            const filas = document.querySelectorAll('#productosSeleccionados .producto-row');
            let formularioValido = true;

            filas.forEach(function (fila) {
                const hiddenId = fila.querySelector('.producto-id');
                const inputBuscar = fila.querySelector('.producto-buscar');
                const cantidadInput = fila.querySelector('.cantidad-producto');

                inputBuscar.classList.remove('is-invalid');
                cantidadInput.classList.remove('is-invalid');

                if (!hiddenId.value) {
                    inputBuscar.classList.add('is-invalid');
                    formularioValido = false;
                }

                if (!cantidadInput.value || parseInt(cantidadInput.value, 10) < 1) {
                    cantidadInput.classList.add('is-invalid');
                    formularioValido = false;
                }
            });

            let alerta = document.getElementById('alertaSeleccionProducto');

            if (!formularioValido) {
                event.preventDefault();

                if (!alerta) {
                    alerta = document.createElement('div');
                    alerta.id = 'alertaSeleccionProducto';
                    alerta.className = 'alert alert-danger mb-3';
                    document.getElementById('productosSeleccionados').insertAdjacentElement('beforebegin', alerta);
                }

                alerta.textContent = 'Revisa los productos marcados en rojo: debes elegirlos de la lista de sugerencias y completar la cantidad.';
            } else if (alerta) {
                alerta.remove();
            }
        });
    </script>
@endpush
