<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;

class InventarioEntradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // luego puedes poner roles
    }

    public function rules(): array
    {
        return [
            'producto_id' => 'required|exists:productos,id',
            'cantidad'    => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'Debe seleccionar un producto',
            'producto_id.exists'   => 'El producto no existe',
            'cantidad.required'    => 'Debe indicar la cantidad',
            'cantidad.min'         => 'La cantidad debe ser mayor a cero',
        ];
    }
}
