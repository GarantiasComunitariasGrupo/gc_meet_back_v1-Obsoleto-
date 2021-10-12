<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmPreguntasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_preguntas', function (Blueprint $table) {
            $table->bigIncrements('id_pregunta');
            $table->unsignedBigInteger('id_reunion');
            $table->foreign('id_reunion')->references('id_reunion')->on('gcm_reuniones');
            $table->string('descripcion', 500)->nullable();
            $table->string('titulo', 500)->required();
            $table->string('orden', 3)->required();
            $table->string('tipo', 2)->index()->required();
            $table->unsignedBigInteger('relacion')->nullable(); 
            $table->string('extra', 255)->nullable();
            $table->foreign('relacion')->references('id_pregunta')->on('gcm_preguntas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_preguntas');
    }
}
