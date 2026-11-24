@extends('layouts.app')

@section('title', 'Técnicos')
@section('page-icon', 'person-gear')
@section('page-title', 'Técnicos')
@section('page-subtitle', 'Administra el personal que atiende las reparaciones del taller.')

@section('page-actions')
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoTecnico">
        <i class="bi bi-plus-lg"></i> Nuevo técnico
    </button>
@endsection

@section('content')

    @php
        $hayFiltros = request()->anyFilled(['nombre', 'estado']);
        $estadoActual = request('estado', '');
        $pestanasEstado = [
            '' => 'Todos',
            '1' => 'Activos',
            '0' => 'Inactivos',
        ];
    @endphp

    <div class="card filter-card">
        <div class="card-body">
            <form method="GET" action="{{ route('tecnicos.index') }}" autocomplete="off" class="d-flex flex-column gap-3">
                <input type="hidden" name="estado" value="{{ $estadoActual }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label" for="nombre">Buscar por nombre</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="nombre" name="nombre" value="{{ request('nombre') }}"
                                class="form-control" placeholder="Nombre del técnico">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search"></i> Filtrar
                            </button>
                            @if ($hayFiltros)
                                <a href="{{ route('tecnicos.index') }}" class="btn btn-outline-secondary"
                                    title="Limpiar filtros">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="chip-row">
                    <span class="chip-label">Estado</span>
                    @foreach ($pestanasEstado as $valor => $etiqueta)
                        <a href="{{ request()->fullUrlWithQuery(['estado' => $valor === '' ? null : $valor, 'page' => null]) }}"
                            class="chip {{ (string) $estadoActual === (string) $valor ? 'activo' : '' }}">{{ $etiqueta }}</a>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

    <div class="result-summary">
        <span><strong>{{ number_format($tecnicos->total(), 0, ',', '.') }}</strong> {{ $tecnicos->total() == 1 ? 'técnico' : 'técnicos' }}{{ $hayFiltros ? ' con estos filtros' : '' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Técnico</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($tecnicos as $tecnico)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar {{ $tecnico->activo == 1 ? '' : 'inactive' }}">
                                    {{ mb_strtoupper(mb_substr($tecnico->nombre, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cell-title {{ $tecnico->activo == 1 ? '' : 'value-zero' }}">{{ $tecnico->nombre }}</div>
                                    <div class="cell-sub">Técnico #{{ $tecnico->id }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="text-center">
                            @if ($tecnico->activo == 1)
                                <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Activo</span>
                            @else
                                <span class="badge bg-secondary"><i class="bi bi-dash-circle-fill"></i> Inactivo</span>
                            @endif
                        </td>

                        <td class="text-center">
                            <div class="btn-group-actions">
                                <button type="button" class="btn btn-outline-primary btn-icon" title="Editar técnico"
                                    onclick="abrirEditarTecnico({{ $tecnico->id }}, @js($tecnico->nombre))">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                <form action="{{ route('tecnicos.toggle', $tecnico->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')

                                    @if ($tecnico->activo == 1)
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-person-dash"></i> Desactivar
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-person-check"></i> Activar
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <i class="bi bi-person-gear"></i>
                                <strong>No hay técnicos registrados</strong>
                                <span>{{ request()->anyFilled(['nombre', 'estado']) ? 'Prueba con otros filtros.' : 'Los técnicos que registres aparecerán aquí.' }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion-wrap">
        {{ $tecnicos->links() }}
    </div>

@endsection

@push('modals')
    <div class="modal fade" id="modalNuevoTecnico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('tecnicos.store') }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="modal-header">
                        <div class="modal-heading">
                            <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo técnico</h5>
                            <p class="modal-subtitle">Se podrá asignar a las máquinas de las órdenes.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body bg-white">
                        <label class="form-label" for="nuevoTecnicoNombre">Nombre completo</label>
                        <input type="text" id="nuevoTecnicoNombre" name="nombre" class="form-control"
                            value="{{ old('nombre') }}" maxlength="150" placeholder="Ej: Juan Pérez" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle"></i> Guardar técnico
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarTecnico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-heading">
                        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar técnico</h5>
                        <p class="modal-subtitle">Corrige el nombre sin perder sus órdenes ni su historial.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body bg-white">
                    <label class="form-label" for="editarTecnicoNombre">Nombre completo</label>
                    <input type="text" id="editarTecnicoNombre" class="form-control" maxlength="150">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarEdicionTecnico()">
                        <i class="bi bi-check2-circle"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        const URL_ACTUALIZAR_TECNICO = @json(route('tecnicos.update', ':id'));
        const modalEditarTecnicoEl = document.getElementById('modalEditarTecnico');
        const modalNuevoTecnicoEl = document.getElementById('modalNuevoTecnico');
        const campoNuevoTecnico = document.getElementById('nuevoTecnicoNombre');
        const campoNombreTecnico = document.getElementById('editarTecnicoNombre');
        let tecnicoEditandoId = null;

        function abrirEditarTecnico(id, nombreActual) {
            tecnicoEditandoId = id;
            campoNombreTecnico.value = nombreActual;
            bootstrap.Modal.getOrCreateInstance(modalEditarTecnicoEl).show();
        }

        modalNuevoTecnicoEl.addEventListener('shown.bs.modal', function () {
            campoNuevoTecnico.focus();
        });

        @if ($errors->has('nombre'))
            bootstrap.Modal.getOrCreateInstance(modalNuevoTecnicoEl).show();
        @endif

        modalEditarTecnicoEl.addEventListener('shown.bs.modal', function () {
            campoNombreTecnico.focus();
            campoNombreTecnico.select();
        });

        campoNombreTecnico.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                guardarEdicionTecnico();
            }
        });

        function guardarEdicionTecnico() {
            enviarEdicionTecnico(false);
        }

        async function enviarEdicionTecnico(confirmarFusion) {
            const nombre = campoNombreTecnico.value.trim();

            if (!nombre) {
                showAppAlert('El nombre no puede estar vacío.', 'warning');
                return;
            }

            try {
                const resp = await fetch(URL_ACTUALIZAR_TECNICO.replace(':id', tecnicoEditandoId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ nombre: nombre, confirmar_fusion: confirmarFusion }),
                });

                const data = await resp.json();

                if (resp.status === 409 && data.requiere_confirmacion) {
                    bootstrap.Modal.getInstance(modalEditarTecnicoEl)?.hide();
                    const confirmado = await confirmDialog(data.mensaje);
                    if (confirmado) {
                        await enviarEdicionTecnico(true);
                    }
                    return;
                }

                if (!resp.ok) {
                    let mensaje = 'No fue posible actualizar el técnico.';
                    if (data.errors) {
                        mensaje = Object.values(data.errors)[0][0];
                    } else if (data.mensaje) {
                        mensaje = data.mensaje;
                    }
                    showAppAlert(mensaje, 'danger');
                    return;
                }

                bootstrap.Modal.getInstance(modalEditarTecnicoEl)?.hide();
                showAppAlert(data.mensaje, 'success');
                setTimeout(function () { window.location.reload(); }, 900);
            } catch (err) {
                console.error(err);
                showAppAlert('No se pudo completar la operación en este momento.', 'danger');
            }
        }
    </script>
@endpush
