@extends('layouts.app')

@section('title', 'Importar y exportar')
@section('page-icon', 'file-earmark-spreadsheet')
@section('page-title', 'Importar y exportar inventario')
@section('page-subtitle', 'Cargue o actualice productos desde Excel y descargue su inventario.')

@section('page-actions')
    <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al inventario
    </a>
@endsection

@section('content')

    <div class="import-grid">

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-arrow-down-circle"></i> 1. Descargar plantilla o inventario</span></div>
            <div class="form-block-body">
                <p class="paso-texto">
                    Descargue la plantilla vacía para registrar productos nuevos, o exporte su inventario actual para
                    corregir precios, ubicaciones o hacer un conteo físico y volver a cargarlo.
                </p>
                <div class="paso-acciones">
                    <a href="{{ route('inventario.importar.plantilla') }}" class="btn btn-primary">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Descargar plantilla
                    </a>
                    <a href="{{ route('inventario.exportar') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-down-circle"></i> Exportar inventario actual
                    </a>
                </div>
            </div>
        </div>

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-arrow-up-circle"></i> 2. Cargar el archivo</span></div>
            <div class="form-block-body">
                <form method="POST" action="{{ route('inventario.importar.previsualizar') }}" enctype="multipart/form-data"
                    id="formImportar">
                    @csrf

                    <label class="archivo-zona" id="zonaArchivo" for="archivo">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                        <strong id="archivoNombre">Elija un archivo o arrástrelo aquí</strong>
                        <span id="archivoAyuda">Excel (.xlsx, .xls) o CSV · máximo 5 MB y {{ number_format($maxFilas, 0, ',', '.') }} filas</span>
                        <input type="file" id="archivo" name="archivo" accept=".xlsx,.xls,.csv,.txt" required>
                    </label>

                    @error('archivo')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror

                    <div class="form-label">¿Qué hacer con el stock de productos que ya existen?</div>
                    <div class="opcion-lista">
                        <label class="opcion">
                            <input type="radio" name="stock" value="sumar" {{ old('stock', 'sumar') === 'sumar' ? 'checked' : '' }}>
                            <div>
                                <strong>Sumar las unidades del archivo</strong>
                                <span>Úselo para registrar una compra o ingreso de mercancía.</span>
                            </div>
                        </label>
                        <label class="opcion">
                            <input type="radio" name="stock" value="fijar" {{ old('stock') === 'fijar' ? 'checked' : '' }}>
                            <div>
                                <strong>Dejar el stock igual al archivo</strong>
                                <span>Úselo para un conteo físico: el sistema ajusta la diferencia y la deja en Movimientos.</span>
                            </div>
                        </label>
                        <label class="opcion">
                            <input type="radio" name="stock" value="ignorar" {{ old('stock') === 'ignorar' ? 'checked' : '' }}>
                            <div>
                                <strong>No modificar el stock</strong>
                                <span>Solo se actualizan los datos. Los productos nuevos sí entran con el stock del archivo.</span>
                            </div>
                        </label>
                    </div>

                    <label class="check-linea">
                        <input type="checkbox" name="actualizar_datos" value="1" {{ old('actualizar_datos', '1') ? 'checked' : '' }}>
                        <span>
                            Actualizar también nombre, precios, categoría, ubicación, proveedor y mínimo
                            <small>Las celdas vacías no borran el dato actual.</small>
                        </span>
                    </label>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Revisar archivo antes de importar
                    </button>
                </form>
            </div>
        </div>

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-info-circle"></i> Cómo funciona</span></div>
            <div class="form-block-body">
                <ul class="lista-reglas">
                    <li>El <strong>código</strong> identifica el producto: si ya existe se actualiza y si no, se crea.</li>
                    <li>Para productos nuevos son obligatorios código, nombre, precio de compra y precio de venta.</li>
                    <li>Antes de guardar verá una vista previa con lo que se creará, lo que cambiará y los errores.</li>
                    <li>Las filas con errores no se importan; las demás sí. Puede corregirlas y volver a cargar el archivo.</li>
                    <li>Cada cambio de stock queda registrado en <strong>Movimientos</strong>.</li>
                    <li>Se aceptan precios como 85000 o 85.000 y encabezados parecidos a los de la plantilla.</li>
                </ul>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const zona = document.getElementById('zonaArchivo');
            const input = document.getElementById('archivo');
            const nombre = document.getElementById('archivoNombre');
            const ayuda = document.getElementById('archivoAyuda');
            const form = document.getElementById('formImportar');

            function mostrar() {
                if (input.files && input.files.length) {
                    const f = input.files[0];
                    nombre.textContent = f.name;
                    ayuda.textContent = (f.size / 1024 < 1024)
                        ? Math.max(1, Math.round(f.size / 1024)) + ' KB · listo para revisar'
                        : (f.size / 1048576).toFixed(1).replace('.', ',') + ' MB · listo para revisar';
                    zona.classList.add('con-archivo');
                }
            }

            input.addEventListener('change', mostrar);

            ['dragenter', 'dragover'].forEach(function (ev) {
                zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.add('arrastrando'); });
            });
            ['dragleave', 'drop'].forEach(function (ev) {
                zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.remove('arrastrando'); });
            });
            zona.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    mostrar();
                }
            });

            form.addEventListener('submit', function () {
                const boton = form.querySelector('button[type="submit"]');
                boton.disabled = true;
                boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Revisando archivo…';
            });
        })();
    </script>
@endpush
