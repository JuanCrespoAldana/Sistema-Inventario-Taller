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
}
