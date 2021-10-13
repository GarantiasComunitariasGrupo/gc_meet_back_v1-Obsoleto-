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
            $table->unsignedBigInteger('id_usuario');
            $table->foreign('id_usuario')->references('id_usuario')->on('gcm_usuarios');
            $table->unsignedBigInteger('id_relacion')->nullable()->index();
            $table->foreign('id_relacion')->references('id_relacion')->on('gcm_relaciones');
            $table->timestampTz('fecha', $precision = 0);
            $table->string('tipo', 2)->index()->required();
            $table->string('identificacion', 20)->index()->nullable();
            $table->string('correo', 255)->index()->nullable();
            $table->string('razon_social', 100)->nullable();
            $table->string('rol', 50)->nullable();
            $table->decimal('participacion', $precision = 3, $scale = 2)->nullable();
            $table->string('telefono', 20)->index()->nullable();
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
