@extends('layouts.app')

@section('content')
    <div class="venta-wrapper">
        <div class="venta-card">

            <h2 class="venta-title">
                <i class="bi bi-wrench-adjustable me-2" style="color:#2563eb;font-size:1.5rem;vertical-align:middle;"></i>
                Nueva Orden de Reparación
            </h2>
            <hr class="mt-3 mb-4" style="border-color:#e5e7eb;">

            <div id="alertas-js"></div>

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show auto-close">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show auto-close">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ERRORES --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('reparaciones.store') }}">
                @csrf

                {{-- NÚMERO DE ORDEN --}}
                <div class="form-group mb-3">
                    <label>Número de orden</label>
                    <input type="text" name="numero_orden" class="form-control" value="{{ old('numero_orden') }}">
                </div>

                {{-- CLIENTE --}}
                <div class="form-group mb-3">
                    <label>Cliente</label>
                    <input type="text" name="cliente_nombre" class="form-control" value="{{ old('cliente_nombre') }}"
                        required>
                </div>

                {{-- TELEFONO --}}
                <div class="form-group mb-3">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                </div>

                {{-- FECHA --}}
                <div class="form-group mb-4">
                    <label>Fecha de entrada</label>
                    <input type="date" name="fecha_entrada" class="form-control"
                        value="{{ old('fecha_entrada', date('Y-m-d')) }}" required>
                </div>

                <hr>

                {{-- MAQUINAS --}}
                <h5 class="mb-3">
                    <i class="bi bi-cpu"></i> Máquinas a reparar
                </h5>

                <div id="maquinas">
                    @if (old('maquinas'))
                        @foreach (old('maquinas') as $i => $maquina)
                            <div class="card mb-4 p-3 maquina-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <span class="maquina-letra">{{ chr(65 + $i) }}</span>
                                    </h5>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarMaquina(this)">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>

                                <div class="mb-2">
                                    <label>Maquina</label>
                                    <input type="text" class="form-control" name="maquinas[{{ $i }}][descripcion]"
                                        value="{{ $maquina['descripcion'] ?? '' }}" required>
                                </div>

                                <div class="mb-2">
                                    <label>Cotización</label>
                                    <textarea class="form-control"
                                        name="maquinas[{{ $i }}][cotizacion]">{{ $maquina['cotizacion'] ?? '' }}</textarea>
                                </div>

                                <div class="mb-2">
                                    <label>Técnico</label>
                                    <select class="form-select" name="maquinas[{{ $i }}][tecnico_id]">
                                        <option value="">Sin asignar</option>
                                        @foreach ($tecnicos as $tecnico)
                                            <option value="{{ $tecnico->id }}" {{ ($maquina['tecnico_id'] ?? '') == $tecnico->id ? 'selected' : '' }}>
                                                {{ $tecnico->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label>Estado</label>
                                        <select class="form-select" name="maquinas[{{ $i }}][estado]">
                                            <option value="pendiente" {{ ($maquina['estado'] ?? '') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                            <option value="en_reparacion" {{ ($maquina['estado'] ?? '') == 'en_reparacion' ? 'selected' : '' }}>En reparación</option>
                                            <option value="entregada" {{ ($maquina['estado'] ?? '') == 'entregada' ? 'selected' : '' }}>Entregada</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label>¿Garantía?</label>
                                        <select class="form-select" name="maquinas[{{ $i }}][es_garantia]">
                                            <option value="0" {{ ($maquina['es_garantia'] ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                            <option value="1" {{ ($maquina['es_garantia'] ?? 0) == 1 ? 'selected' : '' }}>Sí</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label>Tipo garantía</label>
                                        <input type="text" class="form-control" name="maquinas[{{ $i }}][tipo_garantia]"
                                            value="{{ $maquina['tipo_garantia'] ?? '' }}">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label>Factura</label>
                                    <input type="text" class="form-control" name="maquinas[{{ $i }}][factura]"
                                        value="{{ $maquina['factura'] ?? '' }}">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label>Mano de obra</label>
                                        <input type="text" class="form-control money" name="maquinas[{{ $i }}][mano_obra]"
                                            value="{{ number_format($maquina['mano_obra'] ?? 0, 0, '', '.') }}">
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <label>Total</label>
                                        <input type="text" class="form-control money" name="maquinas[{{ $i }}][total]"
                                            value="{{ number_format($maquina['total'] ?? 0, 0, '', '.') }}">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label>Fecha de salida</label>
                                    <input type="date" class="form-control" name="maquinas[{{ $i }}][fecha_salida]"
                                        value="{{ $maquina['fecha_salida'] ?? '' }}">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <button type="button" class="btn btn-outline-primary w-100 mb-3" id="agregar-maquina">
                    <i class="bi bi-plus-circle"></i> Agregar máquina
                </button>

                <button type="submit" class="btn-guardar mt-3">
                    <i class="bi bi-save"></i> Guardar Orden
                </button>
            </form>
        </div>
    </div>

    {{-- JS --}}
    <script>
        const maquinasDiv = document.getElementById('maquinas');

        document.getElementById('agregar-maquina').addEventListener('click', agregarMaquina);

        function mostrarAlerta(mensaje, tipo = 'success', tiempo = 3000) {
            const alertas = document.getElementById('alertas-js');

            const alerta = document.createElement('div');
            alerta.className = `alert alert-${tipo} animate__animated animate__fadeInDown`;
            alerta.innerHTML = `
                                                            <i class="bi ${tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i>
                                                            ${mensaje}
                                                        `;

            alertas.appendChild(alerta);

            setTimeout(() => {
                alerta.classList.remove('animate__fadeInDown');
                alerta.classList.add('animate__fadeOutUp');

                setTimeout(() => alerta.remove(), 800);
            }, tiempo);
        }

        function agregarMaquina() {
            const html = plantillaMaquina();
            maquinasDiv.insertAdjacentHTML('beforeend', html);
            reordenarMaquinas();
            const cards = maquinasDiv.querySelectorAll('.maquina-card');
            const newCard = cards[cards.length - 1];
            newCard.classList.add('maquina-entering');
            setTimeout(() => newCard.classList.remove('maquina-entering'), 450);
        }

        function eliminarMaquina(btn) {
            const card = btn.closest('.maquina-card');
            card.classList.add('maquina-saliendo');

            setTimeout(() => {
                card.remove();
                reordenarMaquinas();
            }, 380);
        }

        function reordenarMaquinas() {
            const cards = document.querySelectorAll('.maquina-card');

            cards.forEach((card, i) => {
                const letra = String.fromCharCode(65 + i); // A, B, C...
                card.querySelector('.maquina-letra').innerText = letra;

                card.querySelectorAll('input, select, textarea').forEach(el => {
                    if (el.dataset.name) {
                        el.name = `maquinas[${i}][${el.dataset.name}]`;
                    }
                });
            });
        }

        function plantillaMaquina() {
            return `
                                                                                <div class="card mb-4 p-3 maquina-card">
                                                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                                                        <h5 class="mb-0">
                                                                                            <span class="maquina-letra">A</span>
                                                                                        </h5>
                                                                                        <button type="button" class="btn btn-sm btn-danger"
                                                                                                onclick="eliminarMaquina(this)">
                                                                                            <i class="bi bi-x"></i>
                                                                                        </button>
                                                                                    </div>

                                                                                    <div class="mb-2">
                                                                                        <label>Maquina</label>
                                                                                        <input type="text" class="form-control"
                                                                                               data-name="descripcion" required>
                                                                                    </div>

                                                                                    <div class="mb-2">
                                                                                        <label>Cotización</label>
                                                                                        <textarea class="form-control"
                                                                                                data-name="cotizacion"></textarea>
                                                                                    </div>

                                                                                    <div class="mb-2">
                                                                                        <label>Técnico</label>
                                                                                        <select class="form-select" data-name="tecnico_id">
                                                                                            <option value="">Sin asignar</option>
                                                                                            @foreach ($tecnicos as $tecnico)
                                                                                                <option value="{{ $tecnico->id }}">{{ $tecnico->nombre }}</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                    </div>

                                                                                    <div class="row">
                                                                                        <div class="col-md-4 mb-2">
                                                                                            <label>Estado</label>
                                                                                                <select class="form-select" data-name="estado">
                                                                                                    <option value="pendiente">Pendiente</option>
                                                                                                    <option value="en_reparacion">En reparación</option>
                                                                                                    <option value="entregada">Entregada</option>
                                                                                                </select>
                                                                                        </div>

                                                                                        <div class="col-md-4 mb-2">
                                                                                            <label>¿Garantía?</label>
                                                                                            <select class="form-select" data-name="es_garantia">
                                                                                                <option value="0">No</option>
                                                                                                <option value="1">Sí</option>
                                                                                            </select>
                                                                                        </div>

                                                                                        <div class="col-md-4 mb-2">
                                                                                            <label>Tipo garantía</label>
                                                                                            <input type="text" class="form-control"
                                                                                                   data-name="tipo_garantia">
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="mb-2">
                                                                                        <label>Factura</label>
                                                                                        <input type="text" class="form-control"
                                                                                               data-name="factura">
                                                                                    </div>

                                                                                    <div class="row">
                                                                                        <div class="col-md-6 mb-2">
                                                                                            <label>Mano de obra</label>
                                                                                            <input type="text"
                                                                                                    class="form-control money"
                                                                                                    data-name="mano_obra"
                                                                                                    placeholder="$ 0">
                                                                                        </div>

                                                                                        <div class="col-md-6 mb-2">
                                                                                            <label>Total</label>
                                                                                            <input type="text"
                                                                                                class="form-control money"
                                                                                                data-name="total"
                                                                                                placeholder="$ 0">
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="mb-2">
                                                                                        <label>Fecha de salida</label>
                                                                                        <input type="date" class="form-control"
                                                                                               data-name="fecha_salida">
                                                                                    </div>
                                                                                </div>
                                                                                `;
        }

        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('money')) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = value ? value.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            }
        });

        // Antes de enviar el formulario, limpiar puntos
        document.querySelector('form').addEventListener('submit', function () {
            document.querySelectorAll('.money').forEach(input => {
                input.value = input.value.replace(/\./g, '');
            });
        });

        setTimeout(() => {
            document.querySelectorAll('.auto-close').forEach(alerta => {
                alerta.classList.add('fade');
                setTimeout(() => alerta.remove(), 500);
            });
        }, 4000);
    </script>
@endsection