<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedInteger('stock_minimo')->default(2)->after('stock');
            $table->string('categoria', 80)->nullable()->after('modelo');
            $table->string('ubicacion', 80)->nullable()->after('categoria');
            $table->string('proveedor', 120)->nullable()->after('ubicacion');
            $table->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['categoria']);
            $table->dropColumn(['stock_minimo', 'categoria', 'ubicacion', 'proveedor']);
        });
    }
};
