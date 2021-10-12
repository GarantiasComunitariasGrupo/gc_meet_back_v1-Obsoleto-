<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmInvitadosReunionesTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_invitados_reuniones', function (Blueprint $table) {
            $table->bigIncrements('id_invitado_reunion');
            $table->unsignedBigInteger('id_reunion');
            $table->foreign('id_reunion')->references('id_reunion')->on('gcm_reuniones');
            $table->unsignedBigInteger('id_usuario'); 
            $table->foreign('id_usuario')->references('id_usuario')->on('gcm_usuarios');
            $table->integer('id_recurso')->index()->require();
            $table->string('email')->index()->require();
            $table->string('identificacion')->index()->require();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_invitados_reuniones');
    }
}
