<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmConvocadosReunionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_convocados_reunion', function (Blueprint $table) {
            $table->bigIncrements('id_convocado_reunion');
            $table->unsignedBigInteger('id_reunion');
            $table->foreign('id_reunion')->references('id_reunion')->on('gcm_reuniones');
            $table->unsignedBigInteger('representacion')->nullable();
            $table->foreign('representacion')->references('id_convocado_reunion')->on('gcm_convocados_reunion');
            $table->unsignedBigInteger('id_relacion');
            $table->foreign('id_relacion')->references('id_relacion')->on('gcm_relaciones');
            $table->timestamp('fecha', $precision = 0);
            $table->string('tipo', 2)->index()->required();
            $table->string('nit', 20)->index()->nullable();
            $table->string('razon_social', 100)->nullable();
            $table->decimal('participacion', $precision = 5, $scale = 2)->nullable();
            $table->string('soporte', 255)->nullable();
            $table->string('estado', 2)->index()->required()->default('1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_convocados_reunion');
    }
}
