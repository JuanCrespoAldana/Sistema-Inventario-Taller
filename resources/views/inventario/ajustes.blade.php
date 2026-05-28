@extends('layouts.app')

@section('title', 'Ajuste de Inventario')

@section('page-title')
    <i class="bi bi-sliders me-2"></i>
    Ajuste de Inventario
@endsection

@section('content')

    <div class="d-flex justify-content-center">
        <div style="width:100%; max-width:600px;">

            {{-- ALERTA ÉXITO --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            {{-- ALERTA ERROR --}}
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">

                    <form id="formAjuste" method="POST" action="{{ route('inventario.ajustes.store') }}">
                        @csrf

                        {{-- PRODUCTOS --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">
                                    <i class="bi bi-search me-1"></i>
                                    Productos
                                </label>
                                <button type="button" id="btnAgregarProducto" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-plus-lg"></i> Agregar producto
                                </button>
                            </div>

                            <div id="productosSeleccionados" class="row g-3 mb-3"></div>

                            <template id="productoRowTemplate">
                                <div class="producto-row row g-2 align-items-end border rounded-3 p-3 position-relative">
                                    <div class="col-md-6">
                                        <label class="form-label visually-hidden">Buscar producto</label>
                                        <input type="text" class="form-control producto-buscar" placeholder="Código o nombre..." autocomplete="off">
                                        <input type="hidden" name="producto_id[]" class="producto-id">
                                        <div class="list-group mt-1 resultados-producto d-none" style="position: relative; z-index: 10;"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label visually-hidden">Cantidad</label>
                                        <input type="number" name="cantidad[]" class="form-control cantidad-producto" min="1" placeholder="Cantidad" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Stock actual</label>
                                        <div class="form-control-plaintext text-secondary producto-stock">-</div>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-producto" title="Quitar producto">×</button>
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-light border small mb-0 d-none info-producto">
                                            <strong class="producto-nombre"></strong><br>
                                            Código: <span class="producto-codigo"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="row g-3">

                            {{-- OPERACIÓN --}}
                            <div class="col-6">
                                <label class="form-label fw-semibold">
                                    Operación
                                </label>
                                <select name="tipo_operacion" id="tipo_operacion" class="form-select @error('tipo_operacion') is-invalid @enderror" required>
                                    <option value="">Seleccione</option>
                                    <option value="sumar" {{ old('tipo_operacion') == 'sumar' ? 'selected' : '' }}>Agregar</option>
                                    <option value="restar" {{ old('tipo_operacion') == 'restar' ? 'selected' : '' }}>Quitar</option>
                                </select>
                                @error('tipo_operacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- MOTIVO --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Motivo
                                </label>
                                <select name="referencia" id="referencia" class="form-select @error('referencia') is-invalid @enderror" required>
                                    <option value="">Seleccione motivo</option>
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

                            {{-- BOTÓN --}}
                            <div class="col-12 mt-3">
                                <button type="submit" id="btnGuardar" class="btn btn-primary w-100 rounded-3">
                                    <i class="bi bi-save me-1"></i>
                                    Guardar Ajuste
                                </button>
                            </div>

                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

@endsection


@push('scripts')
    <script>

        // ================= ALERTA AUTO OCULTAR =================
        setTimeout(function () {
            let alert = document.getElementById('alertSuccess');
            if (alert) {
                alert.classList.remove('show');
                alert.classList.add('fade');
                setTimeout(() => alert.remove(), 500);
            }
        }, 3000);


        // ================= BOTÓN DINÁMICO =================
        const motivosAgregar = [
            { value: 'Compra', label: 'Compra' },
            { value: 'Devolución', label: 'Devolución' },
            { value: 'Ajuste', label: 'Ajuste' },
        ];

        const motivosQuitar = [
            { value: 'Venta', label: 'Venta' },
            { value: 'Uso interno', label: 'Uso interno' },
            { value: 'Ajuste', label: 'Ajuste' },
        ];

        function actualizarMotivos(valor) {
            const select = document.getElementById('referencia');
            const valorActual = select.value;
            select.innerHTML = '<option value="">Seleccione motivo</option>';

            let lista = [];
            if (valor === 'sumar') {
                lista = motivosAgregar;
            } else if (valor === 'restar') {
                lista = motivosQuitar;
            }

            lista.forEach(item => {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.label;
                if (item.value === valorActual) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }

        function actualizarBotonOperacion(valor) {
            let btn = document.getElementById('btnGuardar');

            if (valor === 'sumar') {
                btn.className = 'btn btn-success w-100 rounded-3';
                btn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Agregar Stock';
            } else if (valor === 'restar') {
                btn.className = 'btn btn-danger w-100 rounded-3';
                btn.innerHTML = '<i class="bi bi-dash-circle me-1"></i> Quitar Stock';
            } else {
                btn.className = 'btn btn-primary w-100 rounded-3';
                btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar Ajuste';
            }
        }

        document.getElementById('tipo_operacion')
            .addEventListener('change', function () {
                actualizarBotonOperacion(this.value);
                actualizarMotivos(this.value);
            });

        document.addEventListener('DOMContentLoaded', function () {
            const operacion = document.getElementById('tipo_operacion').value;
            actualizarBotonOperacion(operacion);
            actualizarMotivos(operacion);
        });


        // ================= AUTOCOMPLETE EN TIEMPO REAL =================
        const busquedaProductosUrl = "{{ route('inventario.buscar.producto') }}";

        function crearRowProducto() {
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

            function limpiarRow() {
                hiddenId.value = '';
                stockLabel.textContent = '-';
                nombreLabel.textContent = '';
                codigoLabel.textContent = '';
                infoContainer.classList.add('d-none');
            }

            inputBuscar.addEventListener('input', function () {
                const query = this.value.trim();

                hiddenId.value = '';
                stockLabel.textContent = '-';
                infoContainer.classList.add('d-none');

                if (query.length < 1) {
                    resultados.classList.add('d-none');
                    resultados.innerHTML = '';
                    return;
                }

                fetch(busquedaProductosUrl + '?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        resultados.innerHTML = '';

                        if (!data.length) {
                            resultados.classList.add('d-none');
                            return;
                        }

                        data.forEach(producto => {
                            const item = document.createElement('a');
                            item.classList.add('list-group-item', 'list-group-item-action');
                            item.href = '#';
                            item.innerHTML = `
                                <strong>${producto.codigo}</strong>
                                - ${producto.nombre}
                                <br>
                                <small>Stock: ${producto.stock}</small>
                            `;

                            item.addEventListener('click', function (event) {
                                event.preventDefault();
                                hiddenId.value = producto.id;
                                inputBuscar.value = producto.codigo + ' - ' + producto.nombre;
                                stockLabel.textContent = producto.stock;
                                nombreLabel.textContent = producto.nombre;
                                codigoLabel.textContent = producto.codigo;
                                infoContainer.classList.remove('d-none');
                                resultados.classList.add('d-none');
                            });

                            resultados.appendChild(item);
                        });

                        resultados.classList.remove('d-none');
                    });
            });

            inputBuscar.addEventListener('blur', function () {
                setTimeout(() => resultados.classList.add('d-none'), 150);
            });

            btnEliminar.addEventListener('click', function () {
                nodo.remove();
                if (document.querySelectorAll('#productosSeleccionados .producto-row').length === 0) {
                    agregarProductoRow();
                }
            });

            return nodo;
        }

        function agregarProductoRow() {
            const contenedor = document.getElementById('productosSeleccionados');
            contenedor.appendChild(crearRowProducto());
        }

        document.getElementById('btnAgregarProducto')
            .addEventListener('click', function () {
                agregarProductoRow();
            });

        document.addEventListener('DOMContentLoaded', function () {
            actualizarBotonOperacion(document.getElementById('tipo_operacion').value);
            agregarProductoRow();
        });

    </script>
@endpush