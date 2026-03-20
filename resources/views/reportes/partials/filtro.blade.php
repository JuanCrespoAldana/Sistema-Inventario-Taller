<style>
    .filtro-bar {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }
    .filtro-bar label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .filtro-bar .form-control,
    .filtro-bar .form-select {
        border-radius: 8px;
        font-size: 0.875rem;
        border-color: #dee2e6;
        padding: 8px 12px;
    }
    .filtro-divider {
        width: 1px;
        height: 38px;
        background: #e9ecef;
        align-self: flex-end;
        margin: 0 4px;
    }
    @media print {
        .filtro-bar, .no-print { display: none !important; }
    }
</style>

@php
$mesesEs = [
    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril',
    5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto',
    9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
];
@endphp

<form method="GET" class="filtro-bar">
    <div>
        <label>Mes</label>
        <select name="mes" class="form-select" style="width:150px">
            @for($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}" {{ ($mes ?? date('m')) == $i ? 'selected' : '' }}>
                    {{ $mesesEs[$i] }}
                </option>
            @endfor
        </select>
    </div>
    <div>
        <label>Año</label>
        <input type="number" name="anio" value="{{ $anio ?? date('Y') }}"
               class="form-control" style="width:100px" min="2020" max="2099">
    </div>
    <div>
        <label style="display:block; visibility:hidden">x</label>
        <button type="submit" class="btn btn-primary"
            style="border-radius:8px; padding:8px 18px; font-size:0.87rem; font-weight:600;">
            <i class="bi bi-search me-1"></i> Generar
        </button>
    </div>
    <div class="filtro-divider"></div>
    <div>
        <label style="display:block; visibility:hidden">x</label>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary no-print"
            style="border-radius:8px; padding:8px 14px; font-size:0.87rem;">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
    <div class="ms-auto">
        <label style="display:block; visibility:hidden">x</label>
        <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary no-print"
            style="border-radius:8px; padding:8px 14px; font-size:0.87rem;">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</form>
