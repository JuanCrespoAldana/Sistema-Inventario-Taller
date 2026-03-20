<?php

namespace App\Listeners;

use App\Events\VentaRegistrada;
use Illuminate\Support\Facades\Log;

/**
 * Actualiza las métricas cuando se registra una venta.
 * 
 * Este listener se ejecuta cada vez que se registra una venta nueva.
 * Aquí se pueden realizar acciones como:
 * - Cachear información del dashboard
 * - Actualizar estadísticas en tiempo real
 * - Registrar en auditoría
 */
class ActualizarMetricasVenta
{
    public function handle(VentaRegistrada $event): void
    {
        $venta = $event->venta;

        // Registrar en log para auditoría
        Log::channel('default')->info('Métrica de venta actualizada', [
            'venta_id' => $venta->id,
            'numero_factura' => $venta->numero_factura,
            'subtotal' => $venta->total,
            'descuento' => $venta->descuento,
            'total_final' => $venta->total_final,
            'productos_count' => $venta->detalles()->count(),
            'timestamp' => now(),
        ]);

        // Aquí en el futuro puedes agregar:
        // - Cache::forget('dashboard.ventas*') // Limpiar caché específico de ventas
        // - EstadisticaVenta::actualizar($venta)
        // - Reporte::generar('venta', $venta)
    }
}
