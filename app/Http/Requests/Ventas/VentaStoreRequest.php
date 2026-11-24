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
            'productos.*.producto_id' => ['required', 'distinct', 'exists:productos,id'],
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
            'productos.*.producto_id.required' => 'El producto es obligatorio.',
            'productos.*.producto_id.exists' => 'El producto seleccionado no es válido.',
            'productos.*.producto_id.distinct' => 'No puedes repetir el mismo producto en la venta.',
            'productos.*.cantidad.required' => 'La cantidad es obligatoria.',
            'productos.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'productos.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'productos.*.descuento.numeric' => 'El descuento debe ser numérico.',

            'total.required' => 'El subtotal es obligatorio.',
            'total.numeric' => 'El subtotal debe ser numérico.',
            'descuento.required' => 'El descuento total es obligatorio.',
            'descuento.numeric' => 'El descuento debe ser numérico.',
            'total_final.required' => 'El total final es obligatorio.',
            'total_final.numeric' => 'El total final debe ser numérico.',
        ];
    }
}
