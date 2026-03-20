<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->string('modelo', 100)->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('precio_compra', 10, 2);
            $table->decimal('precio_venta', 10, 2);
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
