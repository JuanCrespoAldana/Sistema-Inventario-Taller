@php
    $mesesEs = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    $tipoReporte = match (request()->route()?->getName()) {
        'reportes.ventas' => 'ventas',
        'reportes.reparaciones' => 'reparaciones',
        'reportes.inventario' => 'inventario',
        'reportes.tecnicos' => 'tecnicos',
        'reportes.financiero' => 'financiero',
        default => null,
    };
@endphp

<form method="GET" class="filtro-bar no-print">
    <div>
        <label class="form-label" for="mes">Mes</label>
        <select id="mes" name="mes" class="form-select field-mes">
            @for ($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}" {{ (int) ($mes ?? date('n')) === $i ? 'selected' : '' }}>
                    {{ $mesesEs[$i] }}
                </option>
            @endfor
        </select>
    </div>

    <div>
        <label class="form-label" for="anio">Año</label>
        <input type="number" id="anio" name="anio" value="{{ $anio ?? date('Y') }}" class="form-control field-anio"
            min="2020" max="2099">
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-search"></i> Generar
    </button>

    @if ($tipoReporte)
        <span class="filtro-divider"></span>
        <a href="{{ route('reportes.exportar', ['tipo' => $tipoReporte, 'mes' => $mes ?? date('n'), 'anio' => $anio ?? date('Y')]) }}"
            class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exportar a Excel
        </a>
    @endif

    <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary ms-auto">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</form>
