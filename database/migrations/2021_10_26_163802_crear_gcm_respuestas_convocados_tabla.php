<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRespuestasConvocadosTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_respuestas_convocados', function (Blueprint $table) {
            $table->unsignedBigInteger('id_convocado_reunion');
            $table->foreign('id_convocado_reunion')->references('id_convocado_reunion')->on('gcm_convocados_reunion');
            $table->unsignedBigInteger('id_programa');
            $table->foreign('id_programa')->references('id_programa')->on('gcm_programacion');
            $table->longText('descripcion')->required();
            $table->primary(['id_convocado_reunion', 'id_programa'], 'id_respuestas_convocado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_respuestas_convocados');
    }
}
