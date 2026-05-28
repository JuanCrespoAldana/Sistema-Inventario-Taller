<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class ReporteFiltroRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'cliente_id' => 'nullable|exists:clientes,id',
            'producto_id' => 'nullable|exists:productos,id',
            'tecnico_id' => 'nullable|exists:tecnicos,id',
            'estado' => 'nullable|string',
            'tipo' => 'nullable|in:venta,reparacion'
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'La fecha fin es obligatoria.',
            'fecha_fin.date' => 'La fecha fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
            'cliente_id.exists' => 'El cliente seleccionado no es válido.',
            'producto_id.exists' => 'El producto seleccionado no es válido.',
            'tecnico_id.exists' => 'El técnico seleccionado no es válido.',
            'tipo.in' => 'El tipo seleccionado no es válido.',
        ];
    }
}

