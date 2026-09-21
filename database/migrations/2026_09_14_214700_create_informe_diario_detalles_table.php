<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('informe_diario_detalles', function (Blueprint $table) {
            $table->id('id_informe_diario_detalle');
            $table->unsignedBigInteger('id_informe_diario_ejecucion');
            $table->text('descripcion');
            $table->tinyInteger('estado')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informe_diario_detalles');
    }
};
