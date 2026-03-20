<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orden_maquinas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orden_id')
                ->constrained('ordenes_reparacion')
                ->cascadeOnDelete();

            $table->char('etiqueta_maquina', 1);
            $table->string('descripcion', 255);

            $table->string('historial_orden', 50)->nullable();

            $table->text('cotizacion')->nullable();

            $table->decimal('mano_obra', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->foreignId('tecnico_id')
                ->nullable()
                ->constrained('tecnicos')
                ->nullOnDelete();

            $table->enum('estado', ['pendiente', 'en_reparacion', 'entregada'])
                ->default('pendiente');

            $table->string('factura', 50)->nullable();

            $table->boolean('es_garantia')->default(false);
            $table->string('tipo_garantia', 255)->nullable();

            $table->date('fecha_salida')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Índices
            $table->index('estado');
            $table->index('tecnico_id');
            $table->index('fecha_salida');
            $table->index('historial_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_maquinas');
    }
};
