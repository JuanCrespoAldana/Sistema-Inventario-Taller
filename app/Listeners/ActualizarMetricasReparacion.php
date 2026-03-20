<?php

namespace App\Listeners;

use App\Events\ReparacionEntregada;
use Illuminate\Support\Facades\Log;

/**
 * Actualiza las métricas cuando una reparación se entrega.
 * 
 * Este listener se ejecuta cuando una máquina pasa a estado 'entregada'.
 * Aquí se registra:
 * - Ingresos por reparación
 * - Productividad del técnico
 * - Auditoría de entregas
 */
class ActualizarMetricasReparacion
{
    public function handle(ReparacionEntregada $event): void
    {
        $maquina = $event->maquina;
        $orden = $maquina->orden;

        // Registrar en log para auditoría
        Log::channel('default')->info('Reparación entregada', [
            'orden_id' => $orden->id,
            'numero_orden' => $orden->numero_orden,
            'maquina_orden' => $maquina->etiqueta_maquina,
            'tecnico_id' => $maquina->tecnico_id,
            'mano_obra' => $maquina->mano_obra,
            'total' => $maquina->total,
            'timestamp' => $maquina->fecha_salida,
        ]);

        // Aquí en el futuro puedes agregar:
        // - Cache::forget('dashboard.*') // Limpiar caché del dashboard
        // - TecnicoMetrica::actualizar($maquina->tecnico_id)
        // - Contabilidad::registrarIngreso($maquina)
        // - Notificacion::enviar('reparacion_entregada', $maquina)
    }
}