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
            @if(session('success'))
                <div id="alertSuccess" class="alert alert-success alert-dismissible fade show shadow-sm">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- ALERTA ERROR --}}
            @if(session('error'))
                <div class="alert alert-danger shadow-sm">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">

                    <form id="formAjuste" method="POST" action="{{ route('inventario.ajustes.store') }}">
                        @csrf

                        {{-- BUSCAR PRODUCTO --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-search me-1"></i>
                                Buscar Producto
                            </label>

                            <input type="text" id="buscarProducto" class="form-control"
                                placeholder="Escriba código o nombre...">

                            <div id="resultados" class="list-group mt-2 shadow-sm"
                                style="display:none; max-height:200px; overflow-y:auto;">
                            </div>

                            <input type="hidden" name="producto_id" id="producto_id" required>
                        </div>

                        {{-- INFO PRODUCTO --}}
                        <div id="infoProducto" class="alert alert-light border d-none small">
                            <strong id="productoNombre"></strong><br>
                            Código: <span id="productoCodigo"></span><br>
                            Stock actual:
                            <span id="productoStock" class="fw-bold"></span>
                        </div>

                        <div class="row g-3">

                            {{-- OPERACIÓN --}}
                            <div class="col-6">
                                <label class="form-label fw-semibold">
                                    Operación
                                </label>
                                <select name="tipo_operacion" id="tipo_operacion" class="form-select" required>
                                    <option value="">Seleccione</option>
                                    <option value="sumar">Agregar</option>
                                    <option value="restar">Quitar</option>
                                </select>
                            </div>

                            {{-- CANTIDAD --}}
                            <div class="col-6">
                                <label class="form-label fw-semibold">
                                    Cantidad
                                </label>
                                <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" required>
                            </div>

                            {{-- MOTIVO --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Motivo
                                </label>
                                <select name="referencia" id="referencia" class="form-select" required>
                                    <option value="">Seleccione motivo</option>
                                    <option value="Entrada por compra">Entrada por compra</option>
                                    <option value="Entrada por devolución">Entrada por devolución</option>
                                    <option value="Salida por daño">Salida por daño</option>
                                    <option value="Salida por uso interno">Salida por uso interno</option>
                                    <option value="Ajuste inventario físico">Ajuste inventario físico</option>
                                </select>
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


        // ================= BOTÓN DINÁMICO + SINCRONIZAR MOTIVOS =================
        const motivosEntrada = [
            { value: 'Entrada por compra',      label: 'Entrada por compra'      },
            { value: 'Entrada por devolución',  label: 'Entrada por devolución'  },
            { value: 'Ajuste inventario físico',label: 'Ajuste inventario físico'},
        ];
        const motivosSalida = [
            { value: 'Salida por daño',         label: 'Salida por daño'         },
            { value: 'Salida por uso interno',  label: 'Salida por uso interno'  },
            { value: 'Ajuste inventario físico',label: 'Ajuste inventario físico'},
        ];

        function actualizarMotivos(operacion) {
            const sel = document.getElementById('referencia');
            const lista = operacion === 'sumar' ? motivosEntrada : (operacion === 'restar' ? motivosSalida : []);
            sel.innerHTML = '<option value="">Seleccione motivo</option>';
            lista.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.value;
                opt.textContent = m.label;
                sel.appendChild(opt);
            });
        }

        document.getElementById('tipo_operacion')
            .addEventListener('change', function () {

                let btn = document.getElementById('btnGuardar');

                if (this.value === 'sumar') {
                    btn.classList.remove('btn-danger');
                    btn.classList.add('btn-success');
                    btn.innerHTML =
                        '<i class="bi bi-plus-circle me-1"></i> Agregar Stock';
                }
                else if (this.value === 'restar') {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-danger');
                    btn.innerHTML =
                        '<i class="bi bi-dash-circle me-1"></i> Quitar Stock';
                }
                else {
                    btn.classList.remove('btn-success', 'btn-danger');
                    btn.classList.add('btn-primary');
                    btn.innerHTML =
                        '<i class="bi bi-save me-1"></i> Guardar Ajuste';
                }

                actualizarMotivos(this.value);
            });


        // ================= AUTOCOMPLETE EN TIEMPO REAL =================
        document.getElementById('buscarProducto')
            .addEventListener('keyup', function () {

                let query = this.value;

                if (query.length < 1) {
                    document.getElementById('resultados').style.display = 'none';
                    return;
                }

                fetch("{{ route('inventario.buscar.producto') }}?q=" + query)
                    .then(response => response.json())
                    .then(data => {

                        let resultados =
                            document.getElementById('resultados');

                        resultados.innerHTML = '';

                        if (data.length === 0) {
                            resultados.style.display = 'none';
                            return;
                        }

                        data.forEach(producto => {

                            let item = document.createElement('a');

                            item.classList.add(
                                'list-group-item',
                                'list-group-item-action'
                            );

                            item.innerHTML = `
                                <strong>${producto.codigo}</strong>
                                - ${producto.nombre}
                                <br>
                                <small>Stock: ${producto.stock}</small>
                            `;

                            item.onclick = function () {

                                document.getElementById('producto_id').value =
                                    producto.id;

                                document.getElementById('buscarProducto').value =
                                    producto.codigo + ' - ' + producto.nombre;

                                document.getElementById('productoNombre')
                                    .innerText = producto.nombre;

                                document.getElementById('productoCodigo')
                                    .innerText = producto.codigo;

                                document.getElementById('productoStock')
                                    .innerText = producto.stock;

                                document.getElementById('infoProducto')
                                    .classList.remove('d-none');

                                resultados.style.display = 'none';
                            };

                            resultados.appendChild(item);
                        });

                        resultados.style.display = 'block';
                    });
            });

    </script>
@endpush