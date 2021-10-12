<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmTipoReunionesTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_tipo_reuniones', function (Blueprint $table) {
            $table->bigIncrements('id_tipo_reunion');
            $table->unsignedBigInteger('id_grupo');
            $table->foreign('id_grupo')->references('id_grupo')->on('gcm_grupos');
            $table->string('titulo', 255)->required();
            $table->string('honorifico_participante', 50)->required();
            $table->string('honorifico_invitado', 50)->required();
            $table->string('honorifico_representante', 50)->required();
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
        Schema::dropIfExists('gcm_tipo_reuniones');
    }
}
