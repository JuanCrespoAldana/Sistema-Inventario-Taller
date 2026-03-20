<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Producto;
use App\Services\Inventario\InventarioService;
use App\Events\VentaRegistrada;
use Illuminate\Support\Facades\DB;
use Exception;

class VentaService
{
    public function crearVenta(array $data): Venta
    {
        return DB::transaction(function () use ($data) {

            // 🔐 Validación base
            if (
                !isset($data['productos']) ||
                !is_array($data['productos']) ||
                count($data['productos']) === 0
            ) {
                throw new \RuntimeException('La venta debe contener al menos un producto.');
            }

            $subtotalCalculado = 0;
            $descuentoCalculado = 0;

            // 1️⃣ VALIDAR + CALCULAR TOTALES
            foreach ($data['productos'] as $item) {

                $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);

                $cantidad = (int) $item['cantidad'];
                $precioUnitario = (float) $producto->precio_venta;
                $descuentoProducto = (float) ($item['descuento'] ?? 0);

                if ($producto->stock < $cantidad) {
                    throw new \RuntimeException(
                        "Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock}"
                    );
                }

                $subtotalProducto = $precioUnitario * $cantidad;

                $subtotalCalculado += $subtotalProducto;
                $descuentoCalculado += $descuentoProducto;
            }

            $totalFinalCalculado = $subtotalCalculado - $descuentoCalculado;

            // 2️⃣ VALIDAR TOTALES DEL FRONT
            if (
                abs($subtotalCalculado - (float) $data['total']) > 1 ||
                abs($descuentoCalculado - (float) $data['descuento']) > 1 ||
                abs($totalFinalCalculado - (float) $data['total_final']) > 1
            ) {
                throw new \RuntimeException('Inconsistencia en los totales de la venta.');
            }

            // 3️⃣ CREAR VENTA
            $venta = Venta::create([
                'numero_factura' => $this->generarNumeroFactura(),
                'fecha' => now(),
                'total' => $subtotalCalculado,
                'descuento' => $descuentoCalculado,
                'total_final' => $totalFinalCalculado,
            ]);

            // 4️⃣ DETALLES + INVENTARIO
            foreach ($data['productos'] as $item) {

                $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);

                $cantidad = (int) $item['cantidad'];
                $precioUnitario = (float) $producto->precio_venta;
                $descuentoProducto = (float) ($item['descuento'] ?? 0);

                $subtotalProducto = $precioUnitario * $cantidad;
                $totalProducto = max($subtotalProducto - $descuentoProducto, 0);

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'total' => $totalProducto,
                ]);

                // 📉 Stock
                // 🧾 Movimiento inventario
                app(InventarioService::class)
                    ->salida(
                        $producto->id,
                        $cantidad,
                        'VENTA ' . $venta->numero_factura,
                        null
                    );
            }

            // 🎯 DISPARAR EVENTO PARA ACTUALIZAR MÉTRICAS
            event(new VentaRegistrada($venta));

            return $venta;
        });
    }

    private function generarNumeroFactura(): string
    {
        $ultimaVenta = Venta::orderBy('id', 'desc')->first();

        $numero = $ultimaVenta
            ? ((int) str_replace('FAC-', '', $ultimaVenta->numero_factura) + 1)
            : 1;

        return 'FAC-' . $numero;
    }
}
