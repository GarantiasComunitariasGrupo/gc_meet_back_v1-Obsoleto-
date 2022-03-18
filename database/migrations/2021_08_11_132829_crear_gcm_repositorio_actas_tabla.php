<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRepositorioActasTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_repositorio_actas', function (Blueprint $table) {
            $table->bigIncrements('id_repositorio_acta');
            $table->unsignedBigInteger('id_reunion');
            $table->foreign('id_reunion')->references('id_reunion')->on('gcm_reuniones');
            $table->string('url', 5000)->required();
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
        Schema::dropIfExists('gcm_repositorio_actas');
    }
}
