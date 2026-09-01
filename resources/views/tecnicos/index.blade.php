@extends('layouts.app')

@section('title', 'Técnicos')
@section('page-title')
    <i class="bi bi-person-gear me-2" style="color:#2563eb;"></i>
    Técnicos
@endsection

@section('content')

    <div class="card card-modern">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-semibold mb-0">Listado de Técnicos</h5>

                <a href="{{ route('tecnicos.create') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-plus-lg me-1"></i>
                    Nuevo Técnico
                </a>
            </div>

            {{-- FILTROS --}}
            <div class="tabla-tecnicos-wrap">
                <form method="GET" action="{{ route('tecnicos.index') }}" class="row g-2 align-items-end mb-4">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted mb-1">
                            <i class="bi bi-search me-1"></i>Buscar por nombre
                        </label>
                        <input type="text" name="nombre" value="{{ request('nombre') }}" class="form-control rounded-3"
                            placeholder="Nombre del técnico...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted mb-1">
                            <i class="bi bi-toggle-on me-1"></i>Estado
                        </label>
                        <select name="estado" class="form-select rounded-3">
                            <option value="">Todos</option>
                            <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                        <a href="{{ route('tecnicos.index') }}" class="btn btn-outline-secondary rounded-3 px-3">
                            <i class="bi bi-x-circle me-1"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>

            {{-- Como son pocos técnicos, la tabla no necesita ocupar todo el ancho:
                 se limita el ancho máximo y se centra, con filas más altas y texto
                 más grande para que sea fácil de leer sin perderse entre filas. --}}
            <div class="tabla-tecnicos-wrap">
                <table class="table align-middle tabla-tecnicos-nueva">
                    <thead>
                        <tr>
                            <th>Técnico</th>
                            <th style="width:150px;">Estado</th>
                            <th class="text-end" style="width:190px;">Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($tecnicos as $tecnico)
                            <tr class="{{ $tecnico->activo == 1 ? 'fila-activo' : 'fila-inactivo' }}">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="tecnico-avatar {{ $tecnico->activo == 1 ? '' : 'tecnico-avatar-inactivo' }}">
                                            {{ mb_strtoupper(mb_substr($tecnico->nombre, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="tecnico-nombre">{{ $tecnico->nombre }}</div>
                                            <div class="tecnico-id">Técnico #{{ $tecnico->id }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if($tecnico->activo == 1)
                                        <span class="tecnico-badge tecnico-badge-activo">
                                            <i class="bi bi-check-circle-fill"></i> Activo
                                        </span>
                                    @else
                                        <span class="tecnico-badge tecnico-badge-inactivo">
                                            <i class="bi bi-dash-circle-fill"></i> Inactivo
                                        </span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button type="button"
                                            class="btn btn-outline-primary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                                            style="width:34px; height:34px;"
                                            title="Editar técnico"
                                            onclick="abrirEditarTecnico({{ $tecnico->id }}, @js($tecnico->nombre))">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <form action="{{ route('tecnicos.toggle', $tecnico->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')

                                            @if($tecnico->activo == 1)
                                                <button class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1">
                                                    Desactivar
                                                </button>
                                            @else
                                                <button class="btn btn-outline-success btn-sm rounded-pill px-3 py-1">
                                                    Activar
                                                </button>
                                            @endif
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    No hay técnicos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tabla-tecnicos-wrap d-flex justify-content-center mt-3">
                {{ $tecnicos->links() }}
            </div>

        </div>
    </div>

    <style>
        .tabla-tecnicos-wrap {
            max-width: 820px;
            margin: 0 auto;
        }
        .tabla-tecnicos-wrap .pagination {
            margin-bottom: 0;
        }
        .tabla-tecnicos-nueva {
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .tabla-tecnicos-nueva thead th {
            border: none;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #94a3b8;
            font-weight: 700;
            padding: 0 .9rem .4rem;
        }
        .tabla-tecnicos-nueva tbody tr {
            background: #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            border-left: 4px solid #cbd5e1;
        }
        .tabla-tecnicos-nueva tbody tr.fila-activo {
            border-left-color: #22c55e;
        }
        .tabla-tecnicos-nueva tbody tr.fila-inactivo {
            border-left-color: #cbd5e1;
        }
        .tabla-tecnicos-nueva tbody tr.fila-inactivo .tecnico-nombre {
            color: #94a3b8;
        }
        .tabla-tecnicos-nueva tbody td {
            border-top: 1px solid #eef1f5;
            border-bottom: 1px solid #eef1f5;
            padding: .85rem .9rem;
            font-size: .95rem;
        }
        .tabla-tecnicos-nueva tbody td:first-child {
            border-left: none;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }
        .tabla-tecnicos-nueva tbody td:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        .tecnico-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .tecnico-avatar-inactivo {
            background: #f1f5f9;
            color: #94a3b8;
        }
        .tecnico-nombre {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
        }
        .tecnico-id {
            font-size: .75rem;
            color: #94a3b8;
        }
        .tecnico-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .82rem;
            font-weight: 600;
            padding: .35rem .75rem;
            border-radius: 999px;
        }
        .tecnico-badge-activo {
            background: #dcfce7;
            color: #15803d;
        }
        .tecnico-badge-inactivo {
            background: #f1f5f9;
            color: #64748b;
        }
    </style>

    @push('scripts')
        <script>
            let tecnicoEditandoId = null;

            function abrirEditarTecnico(id, nombreActual) {
                tecnicoEditandoId = id;
                document.getElementById('editarTecnicoNombre').value = nombreActual;
                new bootstrap.Modal(document.getElementById('modalEditarTecnico')).show();
            }

            function guardarEdicionTecnico() {
                enviarEdicionTecnico(false);
            }

            async function enviarEdicionTecnico(confirmarFusion) {
                const nombre = document.getElementById('editarTecnicoNombre').value.trim();

                if (!nombre) {
                    showAppAlert('El nombre no puede estar vacío.', 'warning');
                    return;
                }

                try {
                    const resp = await fetch(`/tecnicos/${tecnicoEditandoId}`, {
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

                    // El backend pide confirmar la fusión antes de ejecutarla.
                    if (resp.status === 409 && data.requiere_confirmacion) {
                        bootstrap.Modal.getInstance(document.getElementById('modalEditarTecnico'))?.hide();

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

                    bootstrap.Modal.getInstance(document.getElementById('modalEditarTecnico'))?.hide();
                    showAppAlert(data.mensaje, 'success');
                    setTimeout(() => window.location.reload(), 900);

                } catch (err) {
                    console.error(err);
                    showAppAlert('No se pudo completar la operación en este momento.', 'danger');
                }
            }
        </script>
    @endpush

@endsection

{{-- El modal va en el stack 'modals' del layout (igual que en reparaciones/index.blade.php),
     NO dentro de @section('content'): ese contenido se renderiza dentro del contenedor
     flex del sidebar, y ahí el modal de Bootstrap queda mal posicionado (se ve el fondo
     oscuro pero el cuadro de diálogo no aparece). Colocándolo en el stack, el layout lo
     imprime a nivel de <body>, igual que el resto de modales del sistema. --}}
@push('modals')
    <div class="modal fade" id="modalEditarTecnico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Editar técnico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold small text-muted mb-1">Nombre</label>
                    <input type="text" id="editarTecnicoNombre" class="form-control rounded-3" maxlength="150">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary rounded-3" onclick="guardarEdicionTecnico()">
                        Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush
