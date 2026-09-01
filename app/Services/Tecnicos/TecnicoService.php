<?php

namespace App\Services\Tecnicos;

use App\Models\OrdenMaquina;
use App\Models\Tecnico;
use Illuminate\Support\Facades\DB;

class TecnicoService
{
    /**
     * Busca un técnico existente cuyo nombre coincida con $nombre después de
     * normalizar espacios al inicio/final y mayúsculas/minúsculas (sin fuzzy
     * matching). $exceptoId permite excluir al propio técnico al editar.
     */
    public function buscarDuplicado(string $nombre, ?int $exceptoId = null): ?Tecnico
    {
        $normalizado = mb_strtolower(trim($nombre));

        return Tecnico::when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [$normalizado])
            ->first();
    }

    /**
     * Corrige el nombre de un técnico.
     *
     * - Si el nombre nuevo no coincide con ningún otro técnico: se
     *   actualiza directamente, conservando id, órdenes y estado.
     * - Si coincide (comparando sin espacios extra y sin distinguir
     *   mayúsculas/minúsculas) con otro técnico existente, y aún no se
     *   confirmó la fusión: se devuelve una señal para pedir confirmación,
     *   sin cambiar nada todavía.
     * - Si coincide y ya se confirmó la fusión: se reasignan todas las
     *   órdenes del técnico origen al técnico destino, y el origen queda
     *   desactivado (no se elimina, para no perder historial). Todo dentro
     *   de una transacción.
     *
     * @return array{tipo: string, mensaje: string, destino?: Tecnico, ordenes_movidas?: int}
     */
    public function actualizarNombre(Tecnico $tecnico, string $nombreNuevo, bool $confirmarFusion = false): array
    {
        $nombreNuevo = trim($nombreNuevo);
        $duplicado = $this->buscarDuplicado($nombreNuevo, $tecnico->id);

        // Caso 1: no hay ningún otro técnico con ese nombre -> corrección simple.
        if (!$duplicado) {
            $tecnico->update(['nombre' => $nombreNuevo]);

            return [
                'tipo' => 'actualizado',
                'mensaje' => 'Técnico actualizado correctamente.',
            ];
        }

        // Caso 2: existe otro técnico con ese nombre, pero todavía no se
        // confirmó la fusión -> se pide confirmación explícita, sin tocar nada.
        if (!$confirmarFusion) {
            return [
                'tipo' => 'requiere_confirmacion',
                'mensaje' => 'El nombre ingresado ya pertenece a otro técnico. Las órdenes '
                    . 'asignadas a este técnico se asociarán al técnico existente. ¿Deseas continuar?',
                'destino' => $duplicado,
            ];
        }

        // Caso 2, confirmado: fusión transaccional.
        return DB::transaction(function () use ($tecnico, $duplicado) {
            $ordenesMovidas = OrdenMaquina::where('tecnico_id', $tecnico->id)->count();

            OrdenMaquina::where('tecnico_id', $tecnico->id)
                ->update(['tecnico_id' => $duplicado->id]);

            // Se desactiva el técnico origen en vez de eliminarlo: conserva su
            // id y su historial por si algo más lo referencia, pero ya no
            // puede seleccionarse para nuevas órdenes ni aparece como activo.
            $tecnico->update(['activo' => false]);

            return [
                'tipo' => 'fusionado',
                'mensaje' => "Técnicos fusionados correctamente. Se reasignaron {$ordenesMovidas} "
                    . "orden(es) a \"{$duplicado->nombre}\".",
                'destino' => $duplicado,
                'ordenes_movidas' => $ordenesMovidas,
            ];
        });
    }
}
