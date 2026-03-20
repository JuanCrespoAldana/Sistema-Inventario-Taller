@extends('layouts.app')

@section('title', 'Nuevo Técnico')
@section('page-title')
    <i class="bi bi-person-plus me-2" style="color:#2563eb;"></i>
    Crear Técnico
@endsection

@section('content')

    {{-- ALERT ERRORES --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-modern auto-dismiss">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-modern form-card">
        <div class="card-body p-4">

            <h5 class="fw-semibold mb-4">Registrar Técnico</h5>

            <form action="{{ route('tecnicos.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control input-modern" value="{{ old('nombre') }}" required>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('tecnicos.index') }}" class="btn btn-light border rounded-pill px-4">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        Guardar
                    </button>
                </div>

            </form>

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