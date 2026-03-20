@extends('layouts.app')

@section('page-title')
    <i class="bi bi-plus-circle me-2"></i>
    Agregar Máquina
@endsection

@section('content')

    <div class="d-flex justify-content-center">
        <div style="width:100%; max-width:700px;">

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 small">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('inventario.productos.store') }}">
                        @csrf

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Código</label>
                                <input type="text" name="codigo" class="form-control" placeholder="Ej: LAP-001" value="{{ old('codigo') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" class="form-control" placeholder="Ej: ThinkPad E14" value="{{ old('modelo') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej: Laptop Lenovo ThinkPad" value="{{ old('nombre') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Precio Compra</label>
                                <input type="text" name="precio_compra" class="form-control money-input" placeholder="0"
                                    value="{{ old('precio_compra') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Precio Venta</label>
                                <input type="text" name="precio_venta" class="form-control money-input" placeholder="0"
                                    value="{{ old('precio_venta') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Stock Inicial</label>
                                <input type="number" name="stock" class="form-control" min="0"
                                    value="{{ old('stock') }}" placeholder="0">
                            </div>

                            <div class="col-12 mt-4 d-flex gap-2">

                                <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary w-50 rounded-3">
                                    Cancelar
                                </a>

                                <button type="submit" class="btn btn-success w-50 rounded-3">
                                    Guardar Máquina
                                </button>

                            </div>

                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                function formatMoney(value) {
                    value = value.replace(/\D/g, '');
                    return value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }

                document.addEventListener('input', function (e) {
                    if (e.target.classList.contains('money-input')) {
                        e.target.value = formatMoney(e.target.value);
                    }
                });

            });
        </script>
    @endpush

@endsection