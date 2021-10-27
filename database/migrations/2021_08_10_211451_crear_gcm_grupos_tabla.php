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
            $table->string('acceso', 20)->nullable();
            $table->foreign('acceso')->references('id_usuario')->on('gcm_usuarios');
            $table->string('descripcion', 255)->unique()->required();
            $table->string('imagen', 255)->nullable();
            $table->string('logo', 255)->nullable();
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
        Schema::dropIfExists('gcm_grupos');
    }
}
