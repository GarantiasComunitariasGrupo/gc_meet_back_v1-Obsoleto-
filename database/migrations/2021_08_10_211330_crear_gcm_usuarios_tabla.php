<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmUsuariosTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_usuarios', function (Blueprint $table) {
            $table->bigIncrements('id_usuario');
            $table->string('identificacion', 20)->unique()->required();
            $table->string('nombres', 50)->required();
            $table->string('apellidos', 50)->required();
            $table->string('correo', 255)->index()->required();
            $table->string('telefono', 20)->index()->nullable();
            $table->string('contrasena', 255);
            $table->string('tipo', 2)->index()->required();
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
        Schema::dropIfExists('gcm_usuarios');
    }
}
