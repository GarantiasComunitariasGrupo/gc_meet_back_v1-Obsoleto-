<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmGruposTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_grupos', function (Blueprint $table) {
            $table->bigIncrements('id_grupo');
            $table->unsignedBigInteger('id_usuario'); 
            $table->foreign('id_usuario')->references('id_usuario')->on('gcm_usuarios');
            $table->string('descripcion', 255)->required();
            $table->string('estado', 2)->index()->required();
            $table->string('imagen', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_grupos');
    }
}
