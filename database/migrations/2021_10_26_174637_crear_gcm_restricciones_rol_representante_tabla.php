<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRestriccionesRolRepresentanteTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_restricciones_rol_representante', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo_reunion');
            $table->foreign('id_tipo_reunion')->references('id_tipo_reunion')->on('gcm_tipo_reuniones');
            $table->unsignedBigInteger('id_rol');
            $table->foreign('id_rol')->references('id_rol')->on('gcm_roles');
            $table->string('descripcion', 5000)->required();
            $table->string('estado', 2)->index()->required();
            $table->primary(['id_tipo_reunion', 'id_rol'], 'id_rrr');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_restricciones_rol_representante');
    }
}
