<?php

namespace App\Services\Inventario;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    public function entrada(
        int $productoId,
        int $cantidad,
        ?string $referencia = null,
        ?int $usuarioInventarioId = null
    ) {
        return $this->registrarMovimiento(
            $productoId,
            'entrada',
            $cantidad,
            $referencia,
            $usuarioInventarioId
        );
    }

    public function salida(
        int $productoId,
        int $cantidad,
        ?string $referencia = null,
        ?int $usuarioInventarioId = null
    ) {
        return $this->registrarMovimiento(
            $productoId,
            'salida',
            $cantidad,
            $referencia,
            $usuarioInventarioId
        );
    }

    public function ajuste(
        int $productoId,
        int $cantidad,
        ?string $referencia = null,
        ?int $usuarioInventarioId = null
    ) {
        return $this->registrarMovimiento(
            $productoId,
            'ajuste',
            $cantidad,
            $referencia,
            $usuarioInventarioId
        );
    }

    protected function registrarMovimiento(
        int $productoId,
        string $tipo,
        int $cantidad,
        ?string $referencia,
        ?int $usuarioInventarioId
    ) {
        $producto = Producto::lockForUpdate()->findOrFail($productoId);

        if ($tipo === 'salida') {

            if ($producto->stock < $cantidad) {
                throw new \RuntimeException("Stock insuficiente para {$producto->nombre}");
            }

            $producto->decrement('stock', $cantidad);

        } elseif ($tipo === 'entrada') {

            $producto->increment('stock', $cantidad);

        } elseif ($tipo === 'ajuste') {

            $nuevoStock = $producto->stock + $cantidad;

            if ($nuevoStock < 0) {
                throw new \RuntimeException("El ajuste dejaría stock negativo para {$producto->nombre}");
            }

            $producto->update(['stock' => $nuevoStock]);
        }

        return MovimientoInventario::create([
            'producto_id' => $producto->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'referencia' => $referencia,
            'usuario' => $usuarioInventarioId,
        ]);
    }
}
