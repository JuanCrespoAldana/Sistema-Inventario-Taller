<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para las columnas por las que más se filtra y ordena (dashboard, reportes,
 * listados de ventas, inventario y movimientos).
 *
 * Solo AGREGA índices: no cambia ni borra ningún dato. Es segura de correr más de una vez:
 * si una tabla, una columna o un índice equivalente ya existe, simplemente lo salta.
 */
return new class extends Migration
{
    /** tabla => lista de índices (cada uno es la lista de columnas) */
    private array $indices = [
        'ventas'                 => [['fecha'], ['numero_factura']],
        'inventario_movimientos' => [['created_at']],
        'productos'              => [['activo', 'stock'], ['activo', 'created_at']],
        'orden_maquinas'         => [['estado', 'fecha_salida']],
    ];

    public function up(): void
    {
        foreach ($this->indices as $tabla => $lista) {
            if (!Schema::hasTable($tabla)) {
                continue;
            }

            foreach ($lista as $columnas) {
                if (!Schema::hasColumns($tabla, $columnas)) {
                    continue;
                }

                // Ya existe un índice sobre exactamente esas columnas (con cualquier nombre).
                if (Schema::hasIndex($tabla, $columnas)) {
                    continue;
                }

                Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                    $table->index($columnas);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indices as $tabla => $lista) {
            if (!Schema::hasTable($tabla)) {
                continue;
            }

            foreach ($lista as $columnas) {
                $nombre = $tabla . '_' . implode('_', $columnas) . '_index';

                if (Schema::hasIndex($tabla, $nombre)) {
                    Schema::table($tabla, function (Blueprint $table) use ($nombre) {
                        $table->dropIndex($nombre);
                    });
                }
            }
        }
    }
};
