<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total_final', 10, 2);
        });
    }
};
