@extends('layouts.app')

@section('title', 'Vista previa')
@section('page-icon', 'file-earmark-spreadsheet')
@section('page-title', 'Revisar importación')
@section('page-subtitle', 'Verifique los cambios antes de guardarlos. Todavía no se ha modificado nada.')

@section('page-actions')
    <form method="POST" action="{{ route('inventario.importar.cancelar') }}" class="m-0">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <button type="submit" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Cargar otro archivo
        </button>
    </form>
@endsection

@section('content')

    @php
        $modos = [
            'sumar' => 'Sumar las unidades del archivo',
            'fijar' => 'Dejar el stock igual al archivo',
            'ignorar' => 'No modificar el stock existente',
        ];
    @endphp

    <div class="kpi-grid">
        <div class="kpi kpi-green">
            <div class="kpi-icon"><i class="bi bi-plus-circle"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Productos nuevos</div>
                <div class="kpi-value">{{ number_format($resumen['crear'], 0, ',', '.') }}</div>
                <div class="kpi-sub">Se crearán</div>
            </div>
        </div>

        <div class="kpi">
            <div class="kpi-icon"><i class="bi bi-pencil-square"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Por actualizar</div>
                <div class="kpi-value">{{ number_format($resumen['actualizar'], 0, ',', '.') }}</div>
                <div class="kpi-sub">Tienen cambios</div>
            </div>
        </div>

        <div class="kpi kpi-amber">
            <div class="kpi-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Sin cambios</div>
                <div class="kpi-value">{{ number_format($resumen['sin_cambios'], 0, ',', '.') }}</div>
                <div class="kpi-sub">Ya están al día</div>
            </div>
        </div>

        <div class="kpi kpi-red {{ $resumen['errores'] > 0 ? 'kpi-alert' : '' }}">
            <div class="kpi-icon"><i class="bi bi-exclamation-octagon"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Con errores</div>
                <div class="kpi-value">{{ number_format($resumen['errores'], 0, ',', '.') }}</div>
                <div class="kpi-sub">No se importarán</div>
            </div>
        </div>
    </div>

    @if ($ignoradas)
        <div class="alert alert-warning import-aviso">
            Estas columnas del archivo no se reconocieron y se ignoraron: <strong>{{ implode(', ', $ignoradas) }}</strong>.
        </div>
    @endif

    <div class="result-summary">
        <span><strong>{{ number_format($resumen['total'], 0, ',', '.') }}</strong> filas leídas del archivo</span>
        <span>Stock: <strong>{{ $modos[$opciones['stock']] ?? '' }}</strong></span>
        <span>Datos: <strong>{{ $opciones['actualizar_datos'] ? 'se actualizan' : 'no se modifican' }}</strong></span>
    </div>

    <div class="chip-row mb-3">
        <span class="chip-label">Mostrar</span>
        <button type="button" class="chip activo" data-filtro="">Todas <span class="chip-count">{{ $filas->count() + $ocultas }}</span></button>
        @if ($resumen['errores'])
            <button type="button" class="chip" data-filtro="error">Con errores <span class="chip-count">{{ $resumen['errores'] }}</span></button>
        @endif
        <button type="button" class="chip" data-filtro="crear">Nuevos <span class="chip-count">{{ $resumen['crear'] }}</span></button>
        <button type="button" class="chip" data-filtro="actualizar">Actualizar <span class="chip-count">{{ $resumen['actualizar'] }}</span></button>
        <button type="button" class="chip" data-filtro="sin_cambios">Sin cambios <span class="chip-count">{{ $resumen['sin_cambios'] }}</span></button>
    </div>

    <div class="table-responsive">
        <table class="table" id="tablaImportacion">
            <thead>
                <tr>
                    <th class="text-center">Fila</th>
                    <th>Producto</th>
                    <th class="text-center">Acción</th>
                    <th>Detalle</th>
                    <th class="text-center">Stock</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $f)
                    <tr class="fila-estado {{ $f['accion'] === 'error' ? 'fila-agotado' : ($f['advertencias'] ? 'fila-bajo' : '') }}"
                        data-accion="{{ $f['accion'] }}">
                        <td class="text-center cell-code">{{ $f['fila'] }}</td>
                        <td>
                            <div class="cell-title">{{ $f['nombre'] !== '' ? $f['nombre'] : '—' }}</div>
                            <div class="cell-code">{{ $f['codigo'] !== '' ? $f['codigo'] : 'Sin código' }}</div>
                        </td>
                        <td class="text-center">
                            @switch($f['accion'])
                                @case('crear')
                                    <span class="badge bg-success">Nuevo</span>
                                    @break
                                @case('actualizar')
                                    <span class="badge bg-info">Actualizar</span>
                                    @break
                                @case('sin_cambios')
                                    <span class="badge bg-secondary">Sin cambios</span>
                                    @break
                                @default
                                    <span class="badge bg-danger">Error</span>
                            @endswitch
                        </td>
                        <td>
                            <ul class="import-lista">
                                @foreach ($f['errores'] as $msg)
                                    <li class="err">{{ $msg }}</li>
                                @endforeach
                                @foreach ($f['advertencias'] as $msg)
                                    <li class="adv">{{ $msg }}</li>
                                @endforeach
                                @foreach ($f['cambios'] as $msg)
                                    <li class="ok">{{ $msg }}</li>
                                @endforeach
                                @if (!$f['errores'] && !$f['cambios'])
                                    <li class="ok">Sin diferencias con el inventario actual.</li>
                                @endif
                            </ul>
                        </td>
                        <td class="text-center">
                            @if ($f['stock_antes'] !== null)
                                @php $dif = $f['stock_despues'] - $f['stock_antes']; @endphp
                                <span class="stock-flecha">
                                    {{ $f['stock_antes'] }} → <span class="{{ $dif > 0 ? 'sube' : ($dif < 0 ? 'baja' : '') }}">{{ $f['stock_despues'] }}</span>
                                </span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($ocultas > 0)
        <div class="result-summary">
            <span>Se muestran las primeras {{ $filas->count() }} filas (primero los errores y los cambios). Las otras {{ number_format($ocultas, 0, ',', '.') }} no tienen cambios o se aplicarán igual al confirmar.</span>
        </div>
    @endif

    <div class="card">
        <div class="card-body import-barra">
            <div>
                @if ($importables > 0)
                    <strong>Se guardarán {{ number_format($importables, 0, ',', '.') }} {{ $importables == 1 ? 'producto' : 'productos' }}.</strong>
                    @if ($resumen['errores'] > 0)
                        <div class="cell-sub">Las {{ number_format($resumen['errores'], 0, ',', '.') }} filas con errores se omitirán.</div>
                    @endif
                @else
                    <strong>No hay filas válidas para importar.</strong>
                    <div class="cell-sub">Corrija el archivo y vuelva a cargarlo.</div>
                @endif
            </div>

            <form method="POST" action="{{ route('inventario.importar.confirmar') }}" id="formConfirmar">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <button type="submit" class="btn btn-primary" {{ $importables > 0 ? '' : 'disabled' }}>
                    <i class="bi bi-check2-circle"></i> Importar {{ number_format($importables, 0, ',', '.') }} {{ $importables == 1 ? 'fila válida' : 'filas válidas' }}
                </button>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const chips = document.querySelectorAll('[data-filtro]');
            const filas = document.querySelectorAll('#tablaImportacion tbody tr');

            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    const filtro = chip.dataset.filtro;
                    chips.forEach(function (c) { c.classList.toggle('activo', c === chip); });
                    filas.forEach(function (tr) {
                        tr.style.display = (!filtro || tr.dataset.accion === filtro) ? '' : 'none';
                    });
                });
            });

            const form = document.getElementById('formConfirmar');
            form.addEventListener('submit', function () {
                const boton = form.querySelector('button[type="submit"]');
                boton.disabled = true;
                boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importando…';
            });
        })();
    </script>
@endpush
