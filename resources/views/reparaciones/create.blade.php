@extends('layouts.app')

@section('title', 'Nueva orden')
@section('page-icon', 'wrench-adjustable')
@section('page-title', 'Nueva orden de reparación')
@section('page-subtitle', 'Registra el ingreso del cliente y las máquinas que deja en el taller.')

@section('page-actions')
    <a href="{{ route('reparaciones.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver a órdenes
    </a>
@endsection

@section('content')

    @php
        $maquinasForm = collect(old('maquinas', []))->values();
        if ($maquinasForm->isEmpty()) {
            $maquinasForm = collect([[]]);
        }
    @endphp

    <form method="POST" action="{{ route('reparaciones.store') }}">
        @csrf

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-people"></i> Datos del cliente y de la orden</span></div>
            <div class="form-block-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label" for="numero_orden">N° de orden</label>
                        <input type="text" id="numero_orden" name="numero_orden"
                            class="form-control @error('numero_orden') is-invalid @enderror"
                            value="{{ old('numero_orden') }}" maxlength="50" placeholder="Ej: 1015" autofocus>
                        @error('numero_orden')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="cliente_nombre">Cliente</label>
                        <input type="text" id="cliente_nombre" name="cliente_nombre"
                            class="form-control @error('cliente_nombre') is-invalid @enderror"
                            value="{{ old('cliente_nombre') }}" maxlength="255" placeholder="Nombre o razón social" required>
                        @error('cliente_nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input type="text" id="telefono" name="telefono"
                            class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono') }}"
                            maxlength="50" placeholder="Ej: 300 123 4567">
                        @error('telefono')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="fecha_entrada">Fecha de entrada</label>
                        <input type="date" id="fecha_entrada" name="fecha_entrada"
                            class="form-control @error('fecha_entrada') is-invalid @enderror"
                            value="{{ old('fecha_entrada', date('Y-m-d')) }}" required>
                        @error('fecha_entrada')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 mt-4">
            <div class="section-title my-0"><i class="bi bi-cpu"></i> Máquinas a reparar</div>
            <button type="button" class="btn btn-outline-primary btn-agregar-maquina">
                <i class="bi bi-plus-circle"></i> Agregar máquina
            </button>
        </div>

        @error('maquinas')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror

        <div id="contenedor-maquinas" class="d-grid gap-3 mb-4">
            @foreach ($maquinasForm as $i => $m)
                @include('reparaciones.partials.maquina', ['m' => $m, 'i' => $i, 'letra' => chr(65 + $i)])
            @endforeach
        </div>

        <template id="plantilla-maquina">
            @include('reparaciones.partials.maquina', ['m' => [], 'i' => '__INDEX__', 'letra' => 'A'])
        </template>

        <div class="sticky-actions">
            <a href="{{ route('reparaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Guardar orden
            </button>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        document.querySelector('form[action="{{ route('reparaciones.store') }}"]').addEventListener('submit', function () {
            this.querySelectorAll('.dinero').forEach(function (input) {
                input.dataset.formateado = input.value;
                input.value = input.value.replace(/\./g, '');
            });
        });

        window.addEventListener('pageshow', function () {
            document.querySelectorAll('.dinero[data-formateado]').forEach(function (input) {
                input.value = input.dataset.formateado;
            });
        });
    </script>
@endpush
