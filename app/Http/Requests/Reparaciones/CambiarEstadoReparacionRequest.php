<?php

namespace App\Http\Requests\Reparaciones;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoReparacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => 'required|in:pendiente,en_reparacion,entregada',
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe indicar el estado',
            'estado.in'       => 'Estado no válido',
        ];
    }
}
