<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRecursosTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_recursos', function (Blueprint $table) {
            $table->bigIncrements('id_recurso');
            $table->unsignedBigInteger('id_usuario');
            $table->foreign('id_usuario')->references('id_usuario')->on('gcm_usuarios');
            $table->string('tipo_persona', 2)->index()->required();
            $table->string('identificacion', 20)->index()->required();
            $table->string('razon_social', 100)->required();
            $table->string('telefono', 20)->index()->nullable();
            $table->string('correo', 255)->index()->required();
            $table->unsignedBigInteger('representante')->nullable();
            $table->foreign('representante')->references('id_recurso')->on('gcm_recursos');
            $table->string('estado', 2)->index()->required();
            $table->unique(['id_usuario', 'tipo_persona', 'identificacion']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_recursos');
    }
}
