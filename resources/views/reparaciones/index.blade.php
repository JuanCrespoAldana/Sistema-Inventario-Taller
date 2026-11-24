@extends('layouts.app')

@section('title', 'Reparaciones')
@section('page-icon', 'wrench-adjustable')
@section('page-title', 'Órdenes de reparación')
@section('page-subtitle', 'Consulta, edita y da seguimiento a las máquinas que ingresan al taller.')

@section('page-actions')
    <a href="{{ route('reparaciones.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nueva orden
    </a>
@endsection

@section('content')

    @include('reparaciones.partials.filtros')

    <div class="result-summary">
        <span><strong>{{ number_format($ordenes->total(), 0, ',', '.') }}</strong> {{ $ordenes->total() == 1 ? 'orden' : 'órdenes' }}{{ request()->anyFilled(['numero_orden', 'cliente', 'tecnico_id', 'estado', 'telefono', 'maquina', 'garantia', 'desde', 'hasta', 'entrada_desde', 'entrada_hasta']) ? ' con estos filtros' : '' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Cliente</th>
                    <th>Máquinas</th>
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

                        $etiquetaEstado = [
                            'pendiente' => 'Pendiente',
                            'en_reparacion' => 'En reparación',
                            'entregada' => 'Entregada',
                        ];
                        $colorEstado = [
                            'pendiente' => 'warning',
                            'en_reparacion' => 'info',
                            'entregada' => 'success',
                        ];

                        $salida = $orden->maquinas->whereNotNull('fecha_salida')->max('fecha_salida');
                    @endphp

                    <tr class="fila-estado {{ $estadoGeneral ? 'fila-' . $estadoGeneral : '' }}">
                        <td>
                            <div class="cell-main">{{ $orden->numero_orden }}</div>
                            @if ($orden->fecha_entrada)
                                <div class="cell-sub">Ingresó {{ $orden->fecha_entrada->format('d/m/Y') }}</div>
                            @endif
                        </td>

                        <td>
                            <div class="cell-title">{{ $orden->cliente->nombre ?? '—' }}</div>
                            @if ($orden->telefono)
                                <div class="cell-sub">{{ $orden->telefono }}</div>
                            @endif
                        </td>

                        <td>
                            <div class="mini-lista">
                                @forelse ($orden->maquinas as $index => $m)
                                    <div class="mini-item">
                                        <span class="badge bg-primary badge-letter">{{ chr(65 + $index) }}</span>
                                        <span class="mini-item-text">
                                            <span class="fila-{{ $m->estado }}" title="{{ $etiquetaEstado[$m->estado] ?? '' }}"><span class="estado-punto"></span></span>{{ $m->descripcion }}
                                            <span class="cell-sub">{{ $m->tecnico->nombre ?? 'Sin técnico asignado' }}</span>
                                        </span>
                                    </div>
                                @empty
                                    <span class="value-zero">No registradas</span>
                                @endforelse
                            </div>
                        </td>

                        <td class="text-center">
                            @if ($estadoGeneral)
                                <span class="badge bg-{{ $colorEstado[$estadoGeneral] }}">
                                    {{ $etiquetaEstado[$estadoGeneral] }}
                                </span>
                            @else
                                <span class="badge bg-secondary">Sin máquinas</span>
                            @endif
                        </td>

                        <td class="text-center text-nowrap">
                            @if ($salida)
                                {{ \Carbon\Carbon::parse($salida)->format('d/m/Y') }}
                            @else
                                <span class="value-zero">—</span>
                            @endif
                        </td>

                        <td class="text-center">
                            <div class="btn-group-actions">
                                <button type="button" class="btn btn-outline-primary btn-icon" title="Ver orden"
                                    onclick="verOrden({{ $orden->id }})">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-icon" title="Editar orden"
                                    onclick="editarOrden({{ $orden->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-icon" title="Eliminar orden"
                                    onclick="eliminarOrden({{ $orden->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-wrench-adjustable"></i>
                                <strong>No hay órdenes de reparación</strong>
                                <span>{{ request()->anyFilled(['numero_orden', 'cliente', 'telefono', 'tecnico_id', 'estado', 'desde', 'hasta', 'maquina', 'garantia', 'entrada_desde', 'entrada_hasta']) ? 'Prueba con otros filtros.' : 'Las órdenes que registres aparecerán aquí.' }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form id="form-eliminar" method="POST" class="d-none" data-sin-bloqueo>
        @csrf
        @method('DELETE')
    </form>

    <div class="paginacion-wrap">
        {{ $ordenes->withQueryString()->links() }}
    </div>

@endsection

@push('modals')
    @include('reparaciones.partials.modales')
@endpush

@push('scripts')
    <script>
        const URL_VER = @json(route('reparaciones.show', ':id'));
        const URL_EDITAR = @json(route('reparaciones.edit', ':id'));
        const URL_ELIMINAR = @json(route('reparaciones.destroy', ':id'));

        const modalOrdenEl = document.getElementById('modalOrden');
        const modalOrdenContenido = document.getElementById('modalOrdenContent');

        function abrirModalOrden(html) {
            modalOrdenContenido.innerHTML = html;
            bootstrap.Modal.getOrCreateInstance(modalOrdenEl).show();
        }

        function cargarOrden(url, mensajeError) {
            return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) {
                    if (!r.ok) throw new Error(mensajeError);
                    return r.text();
                });
        }

        function verOrden(id) {
            cargarOrden(URL_VER.replace(':id', id), 'No fue posible cargar la orden.')
                .then(abrirModalOrden)
                .catch(function () {
                    showAppAlert('No fue posible cargar la información de la orden. Intenta nuevamente.', 'danger');
                });
        }

        function editarOrden(id) {
            cargarOrden(URL_EDITAR.replace(':id', id), 'No fue posible cargar la edición.')
                .then(function (html) {
                    abrirModalOrden(html);
                    prepararFormularioEdicion();
                })
                .catch(function () {
                    showAppAlert('No fue posible cargar el formulario de edición. Intenta nuevamente.', 'danger');
                });
        }

        function prepararFormularioEdicion() {
            const form = modalOrdenContenido.querySelector('form');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const boton = form.querySelector('button[type="submit"]');
                const textoOriginal = boton ? boton.innerHTML : '';

                if (boton) {
                    boton.disabled = true;
                    boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
                }

                const datos = new FormData(form);
                form.querySelectorAll('.dinero').forEach(function (input) {
                    datos.set(input.name, input.value.replace(/\./g, ''));
                });

                fetch(form.action, {
                    method: 'POST',
                    body: datos,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(function (r) {
                        return r.json().catch(function () { return {}; }).then(function (cuerpo) {
                            return { ok: r.ok, estado: r.status, cuerpo: cuerpo };
                        });
                    })
                    .then(function (res) {
                        if (res.ok) {
                            bootstrap.Modal.getInstance(modalOrdenEl).hide();
                            showAppAlert(res.cuerpo.mensaje || 'Orden actualizada correctamente.', 'success');
                            setTimeout(function () { location.reload(); }, 600);
                            return;
                        }

                        if (res.estado === 422 && res.cuerpo.errors) {
                            showValidationErrors(res.cuerpo.errors);
                        } else {
                            showAppAlert(res.cuerpo.mensaje || res.cuerpo.message || 'No se pudo actualizar la orden. Intenta nuevamente.', 'danger');
                        }

                        if (boton) {
                            boton.disabled = false;
                            boton.innerHTML = textoOriginal;
                        }
                    })
                    .catch(function () {
                        showAppAlert('No fue posible guardar la información. Intenta nuevamente.', 'danger');
                        if (boton) {
                            boton.disabled = false;
                            boton.innerHTML = textoOriginal;
                        }
                    });
            });
        }

        async function eliminarOrden(id) {
            const confirmado = await confirmDialog('¿Seguro que deseas eliminar esta orden de reparación? Se eliminarán también sus máquinas.');
            if (!confirmado) return;

            const form = document.getElementById('form-eliminar');
            form.action = URL_ELIMINAR.replace(':id', id);
            form.submit();
        }
    </script>
@endpush
