<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega un índice sobre created_at en ordenes_reparacion.
     *
     * Justificación: el listado principal de órdenes se va a ordenar por
     * created_at DESC (ver ReparacionController@index). Sin índice, esa
     * ordenación implica un filesort en MySQL a medida que la tabla crezca.
     * No modifica datos existentes ni estructura de columnas, es 100% reversible.
     */
    public function up(): void
    {
        Schema::table('ordenes_reparacion', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_reparacion', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
