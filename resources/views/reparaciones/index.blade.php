@extends('layouts.app')

@section('content')
    <div class="container-fluid reparaciones-container">
        <div class="reparaciones-layout">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="venta-title">
                    <i class="bi bi-wrench-adjustable"></i> Reparaciones
                </h2>

                <a href="{{ route('reparaciones.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva orden
                </a>
            </div>

            {{-- FILTROS --}}
            @include('reparaciones.partials.filtros')

            <div class="table-responsive">
                <table class="table tabla-reparaciones">
                    <thead class="tabla-header">
                        <tr>
                            <th class="text-center">N° Orden</th>
                            <th>Cliente</th>
                            <th class="text-center">Teléfono</th>
                            <th>Máquinas</th>
                            <th >Técnicos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Salida</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($ordenes as $orden)

                            @php    
                                $estados = $orden->maquinas->pluck('estado');

                                if ($estados->isEmpty()) {
                                    $estadoGeneral = null;
                                } elseif ($estados->contains('en_reparacion')) {
                                    $estadoGeneral = 'en_reparacion';
                                } elseif ($estados->contains('pendiente') && $estados->contains('entregada')) {
                                    $estadoGeneral = 'en_reparacion';
                                } elseif ($estados->every(fn($e) => $e === 'entregada')) {
                                    $estadoGeneral = 'entregada';
                                } else {
                                    $estadoGeneral = 'pendiente';
                                }

                                $salida = $orden->maquinas
                                    ->whereNotNull('fecha_salida')
                                    ->max('fecha_salida');
                            @endphp


                            <tr class="animate-fade-in" >
                                <td class="fw-bold">{{ $orden->numero_orden }}</td>
                                <td>{{ $orden->cliente->nombre }}</td>
                                <td>{{ $orden->telefono }}</td>

                                <td>
                                    @if(isset($orden->faltante))
                                        <span class="text-muted">No registrada</span>
                                    @else
                                        @foreach ($orden->maquinas as $index => $m)
                                            <div class="d-flex align-items-center mb-1">

                                                <span class="badge bg-light text-dark me-2" style="width: 28px;">
                                                    {{ chr(65 + $index) }}
                                                </span>

                                                <span>
                                                    {{ $m->descripcion }}
                                                </span>

                                            </div>
                                        @endforeach
                                    @endif
                                </td>


                                <td>
                                    @foreach ($orden->maquinas->pluck('tecnico.nombre')->unique() as $tec)
                                        <div>{{ $tec ?? 'Sin asignar' }}</div>
                                    @endforeach
                                </td>

                                <td class="text-center">
                                    @if(!$estadoGeneral)
                                        <span class="badge bg-secondary px-3 py-2">
                                            Sin máquinas
                                        </span>
                                    @else
                                                        <span class="badge px-3 py-2 bg-{{
                                        $estadoGeneral === 'pendiente' ? 'warning' :
                                        ($estadoGeneral === 'en_reparacion' ? 'info' : 'success')
                                                                                                                                        }}">
                                                            {{ ucfirst(str_replace('_', ' ', $estadoGeneral)) }}
                                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    {{ $salida ? \Carbon\Carbon::parse($salida)->format('d/m/Y') : 'F/A' }}
                                </td>

                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button class="btn btn-sm btn-outline-primary" onclick="verOrden({{ $orden->id }})">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-warning" onclick="editarOrden({{ $orden->id }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarOrden({{ $orden->id }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No hay órdenes de reparación
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- FORM DELETE --}}
            <form id="form-eliminar" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
            </form>

            <div class="d-flex justify-content-end mt-3">
                {{ $ordenes->withQueryString()->links() }}
            </div>

        </div>
    </div>

    <script>
        function verOrden(id) {
            const url = `{{ route('reparaciones.show', ':id') }}`.replace(':id', id);

            fetch(url)
                .then(r => {
                    if (!r.ok) throw new Error('Error al cargar la orden');
                    return r.text();
                })
                .then(html => {
                    document.getElementById('modalOrdenContent').innerHTML = html;
                    new bootstrap.Modal(
                        document.getElementById('modalOrden')
                    ).show();
                })
                .catch(err => console.error(err));
        }

        function editarOrden(id) {
            const url = `{{ route('reparaciones.edit', ':id') }}`.replace(':id', id);

            fetch(url)
                .then(r => {
                    if (!r.ok) throw new Error('Error al cargar la edición');
                    return r.text();
                })
                .then(html => {
                    document.getElementById('modalOrdenContent').innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById('modalOrden'));
                    modal.show();

                    // ✅ Interceptar el submit del formulario para hacerlo via AJAX
                    const form = document.getElementById('modalOrdenContent').querySelector('form');
                    if (!form) return;

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();

                        const submitBtn = form.querySelector('[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
                        }

                        // Construir FormData con el método PUT spoofed
                        const formData = new FormData(form);

                        fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(r => {
                            if (r.redirected || r.ok) {
                                // Cerrar modal suavemente
                                bootstrap.Modal.getInstance(document.getElementById('modalOrden')).hide();
                                // Mostrar alerta de éxito y recargar tabla
                                mostrarAlertaGlobal('Orden actualizada correctamente', 'success');
                                setTimeout(() => location.reload(), 1200);
                            } else {
                                return r.text().then(html => {
                                    // Mostrar errores dentro del modal
                                    document.getElementById('modalOrdenContent').innerHTML = html;
                                });
                            }
                        })
                        .catch(() => {
                            mostrarAlertaGlobal('Error al guardar. Intenta de nuevo.', 'danger');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = 'Guardar cambios';
                            }
                        });
                    });
                })
                .catch(err => console.error(err));
        }

        function mostrarAlertaGlobal(mensaje, tipo) {
            showAppAlert(mensaje, tipo === 'danger' ? 'danger' : (tipo === 'success' ? 'success' : 'warning'));
        }

        async function eliminarOrden(id) {
            const confirmed = await confirmDialog('¿Seguro que deseas eliminar esta orden de reparación?');
            if (!confirmed) return;

            const form = document.getElementById('form-eliminar');
            form.action = `{{ route('reparaciones.destroy', ':id') }}`.replace(':id', id);
            form.submit();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const alert = document.getElementById('alert-success');

            if (alert) {
                setTimeout(() => {
                    alert.classList.remove('show');
                    alert.classList.add('fade');
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            }
        });

        document.addEventListener('click', function (e) {

            // ==========================
            // AGREGAR MÁQUINA
            // ==========================
            if (e.target.closest('.btn-agregar-maquina')) {

                const contenedor = document.getElementById('contenedor-maquinas');
                if (!contenedor) return;

                let contador = contenedor.querySelectorAll('.maquina-item').length;
                const letra = String.fromCharCode(65 + contador);

                const nueva = document.createElement('div');
                nueva.className = "border rounded p-3 mb-4 maquina-item";

                nueva.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 titulo-maquina">
                                        <i class="bi bi-hdd"></i> Máquina ${letra}
                                    </h6>
                                    <button type="button"
                                            class="btn btn-sm btn-danger btn-eliminar-maquina">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                                <div class="row g-3 mb-3">

                                    <div class="col-md-4">
                                        <small class="text-muted">Máquina</small>
                                        <input type="text"
                                               name="maquinas[${contador}][descripcion]"
                                               class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <small class="text-muted">Técnico</small>
                                        <select name="maquinas[${contador}][tecnico_id]"
                                                class="form-control">
                                            <option value="">Sin asignar</option>
                                            @foreach($tecnicos as $t)
                                                <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <small class="text-muted">Estado</small>
                                        <select name="maquinas[${contador}][estado]"
                                                class="form-control">
                                            <option value="pendiente">Pendiente</option>
                                            <option value="en_reparacion">En reparación</option>
                                            <option value="entregada">Entregada</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <small class="text-muted">¿Garantía?</small>
                                        <select name="maquinas[${contador}][es_garantia]"
                                                class="form-control">
                                            <option value="0">No</option>
                                            <option value="1">Sí</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <small class="text-muted">Tipo garantía</small>
                                        <input type="text"
                                               name="maquinas[${contador}][tipo_garantia]"
                                               class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <small class="text-muted">Factura</small>
                                        <input type="text"
                                               name="maquinas[${contador}][factura]"
                                               class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <small class="text-muted">Fecha de salida</small>
                                        <input type="date"
                                               name="maquinas[${contador}][fecha_salida]"
                                               class="form-control">
                                    </div>

                                </div>

                                <div class="mb-3">
                                    <small class="text-muted fw-bold">
                                        <i class="bi bi-clipboard-check"></i> Cotización
                                    </small>
                                    <textarea name="maquinas[${contador}][cotizacion]"
                                              class="form-control"
                                              rows="3"></textarea>
                                </div>

                                <div class="row mt-3">

                                    <div class="col-md-6">
                                        <small class="text-muted">Mano de obra</small>
                                        <input type="text"
                                               name="maquinas[${contador}][mano_obra]"
                                               class="form-control dinero">
                                    </div>

                                    <div class="col-md-6">
                                        <small class="text-muted">Total</small>
                                        <input type="text"
                                               name="maquinas[${contador}][total]"
                                               class="form-control dinero">
                                    </div>

                                </div>
                            `;

                contenedor.appendChild(nueva);

                // 🔥 Scroll automático al nuevo bloque
                setTimeout(() => {

                    const modalBody = document.querySelector('.modal-body');

                    if (modalBody) {
                        modalBody.scrollTo({
                            top: nueva.offsetTop,
                            behavior: 'smooth'
                        });
                    } else {
                        // por si no encuentra modal-body
                        nueva.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }

                }, 100);
            }

            // ==========================
            // ELIMINAR MÁQUINA
            // ==========================
            if (e.target.closest('.btn-eliminar-maquina')) {

                const item = e.target.closest('.maquina-item');
                const contenedor = document.getElementById('contenedor-maquinas');

                if (!item || !contenedor) return;

                item.remove();

                const items = contenedor.querySelectorAll('.maquina-item');

                items.forEach((item, index) => {
                    item.querySelector('.titulo-maquina')
                        .innerText = "Máquina " + String.fromCharCode(65 + index);
                });
            }

        });


        // ==========================
        // FORMATO DINERO GLOBAL
        // ==========================
        document.addEventListener('input', function (e) {

            if (e.target.classList.contains('dinero')) {

                let valor = e.target.value.replace(/\D/g, '');

                if (!valor) {
                    e.target.value = '';
                    return;
                }

                e.target.value = new Intl.NumberFormat('es-CO').format(valor);
            }

        });
    </script>
@endsection

@push('modals')
    @include('reparaciones.partials.modales')
@endpush