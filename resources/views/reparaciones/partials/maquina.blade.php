@php
    $v = fn ($campo, $defecto = '') => $m[$campo] ?? $defecto;
    $dinero = function ($campo) use ($m) {
        $valor = $m[$campo] ?? null;
        return ($valor === null || $valor === '') ? '' : number_format((float) $valor, 0, ',', '.');
    };
    $err = fn ($campo) => is_numeric($i) ? $errors->first('maquinas.' . $i . '.' . $campo) : null;
    $estadoActual = $v('estado', 'pendiente');
    $etiquetasEstado = ['pendiente' => 'Pendiente', 'en_reparacion' => 'En reparación', 'entregada' => 'Entregada'];
    $esGarantia = (int) $v('es_garantia', 0) === 1;
@endphp

<div class="maquina-card" data-maquina data-estado="{{ $estadoActual }}">

    @if (!empty($m['id']))
        <input type="hidden" name="maquinas[{{ $i }}][id]" value="{{ $m['id'] }}">
    @endif

    <div class="maquina-card-head">
        <div class="maquina-card-title">
            <span class="maquina-letra-chip maquina-letra">{{ $letra }}</span>
            <strong>Máquina</strong>
            <span class="badge maquina-estado-pill">{{ $etiquetasEstado[$estadoActual] ?? 'Pendiente' }}</span>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-maquina" title="Quitar máquina">
            <i class="bi bi-trash"></i> Quitar
        </button>
    </div>

    <div class="maquina-card-body">

        <div class="form-group-title"><i class="bi bi-cpu"></i> Equipo y seguimiento</div>

        <div class="fields-grid">
            <div class="span-2">
                <label class="form-label">Descripción de la máquina</label>
                <input type="text" name="maquinas[{{ $i }}][descripcion]"
                    class="form-control {{ $err('descripcion') ? 'is-invalid' : '' }}" value="{{ $v('descripcion') }}"
                    maxlength="255" placeholder="Ej: Compresor de tornillo 15 HP" required>
                @if ($err('descripcion'))
                    <div class="invalid-feedback">{{ $err('descripcion') }}</div>
                @endif
            </div>

            <div>
                <label class="form-label">Técnico</label>
                <select name="maquinas[{{ $i }}][tecnico_id]"
                    class="form-select {{ $err('tecnico_id') ? 'is-invalid' : '' }}">
                    <option value="">Sin asignar</option>
                    @if ($v('tecnico_id') && !$tecnicos->contains('id', $v('tecnico_id')))
                        <option value="{{ $v('tecnico_id') }}" selected>
                            {{ $v('tecnico_nombre', 'Técnico') }} (inactivo)
                        </option>
                    @endif
                    @foreach ($tecnicos as $tecnico)
                        <option value="{{ $tecnico->id }}" {{ (string) $v('tecnico_id') === (string) $tecnico->id ? 'selected' : '' }}>
                            {{ $tecnico->nombre }}
                        </option>
                    @endforeach
                </select>
                @if ($err('tecnico_id'))
                    <div class="invalid-feedback">{{ $err('tecnico_id') }}</div>
                @endif
            </div>

            <div>
                <label class="form-label">Estado</label>
                <select name="maquinas[{{ $i }}][estado]" data-campo-estado
                    class="form-select {{ $err('estado') ? 'is-invalid' : '' }}">
                    <option value="pendiente" {{ $estadoActual === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="en_reparacion" {{ $estadoActual === 'en_reparacion' ? 'selected' : '' }}>En reparación</option>
                    <option value="entregada" {{ $estadoActual === 'entregada' ? 'selected' : '' }}>Entregada</option>
                </select>
                @if ($err('estado'))
                    <div class="invalid-feedback">{{ $err('estado') }}</div>
                @endif
            </div>
        </div>

        <div class="form-group-title"><i class="bi bi-receipt"></i> Garantía y facturación</div>

        <div class="fields-grid">
            <div>
                <label class="form-label">¿Tiene garantía?</label>
                <select name="maquinas[{{ $i }}][es_garantia]" class="form-select" data-campo-garantia>
                    <option value="0" {{ !$esGarantia ? 'selected' : '' }}>No</option>
                    <option value="1" {{ $esGarantia ? 'selected' : '' }}>Sí</option>
                </select>
            </div>

            <div>
                <label class="form-label">Tipo de garantía</label>
                <input type="text" name="maquinas[{{ $i }}][tipo_garantia]" class="form-control"
                    value="{{ $v('tipo_garantia') }}" maxlength="255" placeholder="Ej: Garantía de reparación"
                    data-campo-tipo-garantia {{ $esGarantia ? '' : 'disabled' }}>
            </div>

            <div>
                <label class="form-label">N° de factura</label>
                <input type="text" name="maquinas[{{ $i }}][factura]" class="form-control"
                    value="{{ $v('factura') }}" maxlength="255" placeholder="Opcional">
            </div>

            <div class="campo-salida">
                <label class="form-label">Fecha de salida</label>
                <input type="date" name="maquinas[{{ $i }}][fecha_salida]" class="form-control"
                    value="{{ $v('fecha_salida') }}" data-campo-fecha-salida>
            </div>
        </div>

        <div class="form-group-title"><i class="bi bi-clipboard-check"></i> Cotización</div>

        <textarea name="maquinas[{{ $i }}][cotizacion]" class="form-control" rows="3" maxlength="1000"
            placeholder="Detalle del trabajo a realizar y repuestos">{{ $v('cotizacion') }}</textarea>

        <div class="form-group-title"><i class="bi bi-cash-coin"></i> Valores</div>

        <div class="fields-grid">
            <div>
                <label class="form-label">Mano de obra</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="text" name="maquinas[{{ $i }}][mano_obra]" class="form-control dinero"
                        value="{{ $dinero('mano_obra') }}" placeholder="0" inputmode="numeric">
                </div>
            </div>

            <div>
                <label class="form-label">Total a cobrar</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="text" name="maquinas[{{ $i }}][total]" class="form-control dinero input-total"
                        value="{{ $dinero('total') }}" placeholder="0" inputmode="numeric">
                </div>
            </div>
        </div>

    </div>
</div>
