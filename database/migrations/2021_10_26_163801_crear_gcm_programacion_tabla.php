<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmProgramacionTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_programacion', function (Blueprint $table) {
            $table->bigIncrements('id_programa');
            $table->unsignedBigInteger('id_reunion');
            $table->foreign('id_reunion')->references('id_reunion')->on('gcm_reuniones');
            $table->string('titulo', 500)->required();
            $table->string('descripcion', 500)->nullable();
            $table->integer('orden')->required();
            $table->string('numeracion', 2)->required();
            $table->string('tipo', 2)->index()->required();
            $table->unsignedBigInteger('relacion')->nullable(); 
            $table->foreign('relacion')->references('id_programa')->on('gcm_programacion');
            $table->string('estado', 2)->index()->required();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_programacion');
    }
}
