<?php

namespace App\Services\Reparaciones;

use App\Models\OrdenMaquina;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\ReparacionEntregada;


class ReparacionService
{
    public function cambiarEstado(int $ordenMaquinaId, string $nuevoEstado): OrdenMaquina
    {
        return DB::transaction(function () use ($ordenMaquinaId, $nuevoEstado) {

            $maquina = OrdenMaquina::lockForUpdate()->findOrFail($ordenMaquinaId);

            $this->validarFlujo($maquina->estado, $nuevoEstado);

            $maquina->estado = $nuevoEstado;

            if ($nuevoEstado === 'entregada') {
                $maquina->fecha_salida = Carbon::now();
                $maquina->total = $maquina->mano_obra;

                event(new ReparacionEntregada($maquina));
            }

            $maquina->save();

            return $maquina;
        });
    }

    protected function validarFlujo(string $actual, string $nuevo): void
    {
        $flujo = [
            'pendiente' => ['en_reparacion'],
            'en_reparacion' => ['entregada'],
            'entregada' => [],
        ];

        if (!in_array($nuevo, $flujo[$actual] ?? [])) {
            throw new \Exception("Cambio de estado no permitido");
        }
    }
}
