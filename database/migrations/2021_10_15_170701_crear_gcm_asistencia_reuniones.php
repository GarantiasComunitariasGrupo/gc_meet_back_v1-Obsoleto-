<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmAsistenciaReuniones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_asistencia_reuniones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_convocado_reunion')->primary();
            $table->dateTime('fecha_ingreso')->required();
            $table->dateTime('fecha_salida')->nullable();
            $table->string('direccion_ip', 50)->required();
            $table->foreign('id_convocado_reunion')->references('id_convocado_reunion')->on('gcm_convocados_reunion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_asistencia_reuniones');
    }
}
