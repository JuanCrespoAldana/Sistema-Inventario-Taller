<?php

namespace App\Listeners;

use App\Events\VentaRegistrada;
use Illuminate\Support\Facades\Log;

/**
 * Registra el ingreso de una venta nueva.
 * 
 * Este listener se ejecuta cada vez que se registra una venta.
 * Aquí se puede:
 * - Actualizar caché de métricas
 * - Registrar en contabilidad
 * - Enviar notificaciones
 * - Generar alertas
 */
class RegistrarIngresoVenta
{
    public function handle(VentaRegistrada $event): void
    {
        $venta = $event->venta;

        // Registrar en log para auditoría
        Log::channel('default')->info('Venta registrada', [
            'venta_id' => $venta->id,
            'numero_factura' => $venta->numero_factura,
            'total' => $venta->total_final,
            'descuento' => $venta->descuento,
            'timestamp' => $venta->created_at,
        ]);

        // Aquí en el futuro puedes agregar:
        // - Cache::forget('dashboard.*') // Limpiar caché del dashboard
        // - Contabilidad::registrarIngreso($venta)
        // - Notificacion::enviar('venta_registrada', $venta)
    }
}
