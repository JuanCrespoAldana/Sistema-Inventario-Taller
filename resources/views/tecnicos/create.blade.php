@extends('layouts.app')

@section('title', 'Nuevo técnico')
@section('page-icon', 'person-plus')
@section('page-title', 'Nuevo técnico')
@section('page-subtitle', 'Registra a una persona del equipo técnico del taller.')

@section('page-actions')
    <a href="{{ route('tecnicos.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver a técnicos
    </a>
@endsection

@section('content')

    <div class="form-page-sm">
        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-person-gear"></i> Datos del técnico</span></div>
            <div class="form-block-body">
                <form action="{{ route('tecnicos.store') }}" method="POST" autocomplete="off">
                    @csrf

                    <label class="form-label" for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}" maxlength="150" placeholder="Ej: Juan Pérez" required autofocus>
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <div class="form-card-actions">
                        <a href="{{ route('tecnicos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle"></i> Guardar técnico
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
