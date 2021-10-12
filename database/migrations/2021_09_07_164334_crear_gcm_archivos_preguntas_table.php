<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmArchivosPreguntasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_archivos_preguntas', function (Blueprint $table) {
            $table->bigIncrements('id_archivo_pregunta');
            $table->unsignedBigInteger('id_pregunta');
            $table->foreign('id_pregunta')->references('id_pregunta')->on('gcm_preguntas');
            $table->string('tipo', 2)->index()->required();
            $table->string('descripcion', 255)->nullable();
            $table->string('url', 255)->required();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_archivos_preguntas');
    }
}
