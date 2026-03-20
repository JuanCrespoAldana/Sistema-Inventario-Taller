@extends('layouts.app')

@section('title', 'Técnicos')
@section('page-title')
    <i class="bi bi-person-gear me-2" style="color:#2563eb;"></i>
    Técnicos
@endsection

@section('content')

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="alert alert-success alert-modern auto-dismiss">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-modern auto-dismiss">
            {{ session('error') }}
        </div>
    @endif


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

            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm table-modern">

                    <thead>
                        <tr>
                            <th style="width:80px;">ID</th>
                            <th>Nombre</th>
                            <th style="width:140px;">Estado</th>
                            <th class="text-end" style="width:140px;">Acción</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($tecnicos as $tecnico)
                            <tr>
                                <td>{{ $tecnico->id }}</td>
                                <td class="fw-semibold">{{ $tecnico->nombre }}</td>

                                <td>
                                    @if($tecnico->activo == 1)
                                        <span class="badge-activo">Activo</span>
                                    @else
                                        <span class="badge-inactivo">Inactivo</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <form action="{{ route('tecnicos.toggle', $tecnico->id) }}" method="POST">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    No hay técnicos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

            <div class="mt-3">
                {{ $tecnicos->links() }}
            </div>

        </div>
    </div>

    {{-- AUTO DISMISS --}}
    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll('.auto-dismiss').forEach(function (alert) {
                    setTimeout(() => {
                        alert.style.transition = "opacity .4s ease";
                        alert.style.opacity = "0";
                        setTimeout(() => alert.remove(), 400);
                    }, 3000);
                });
            });
        </script>
    @endpush

@endsection