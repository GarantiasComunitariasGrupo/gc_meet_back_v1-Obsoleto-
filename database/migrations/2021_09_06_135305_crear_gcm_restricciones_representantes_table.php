<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRestriccionesRepresentantesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_restricciones_representantes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo_reunion');
            $table->foreign('id_tipo_reunion')->references('id_tipo_reunion')->on('gcm_tipo_reuniones');
            $table->string('tipo', 2)->required();
            $table->string('id_elemento', 20)->required();
            $table->string('descripcion', 5000)->required();
            $table->string('estado', 2)->index()->required();
            $table->primary(['id_tipo_reunion', 'tipo', 'id_elemento'], 'pk_restricciones_representantes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_restricciones_representantes');
    }
}
