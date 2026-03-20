<?php

namespace App\Http\Requests\Ventas;

use Illuminate\Foundation\Http\FormRequest;

class VentaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
            'productos.*.descuento' => ['nullable', 'numeric', 'min:0'],

            'total' => ['required', 'numeric', 'min:0'],
            'descuento' => ['required', 'numeric', 'min:0'],
            'total_final' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation()
    {
        if (is_string($this->productos)) {
            $this->merge([
                'productos' => json_decode($this->productos, true),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'productos.required' => 'Debe agregar al menos un producto a la venta.',
            'productos.min' => 'Debe agregar al menos un producto a la venta.',
        ];
    }
}
