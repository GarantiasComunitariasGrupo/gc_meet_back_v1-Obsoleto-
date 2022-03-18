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
            $table->string('id_usuario', 20)->primary();
            $table->string('nombre', 255)->required();
            $table->string('correo', 255)->unique()->nullable();
            $table->string('contrasena', 255)->required();
            $table->string('estado', 2)->index()->required();
            $table->string('tipo', 2)->index()->required();
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
