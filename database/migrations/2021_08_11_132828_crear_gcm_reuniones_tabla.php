<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CrearGcmReunionesTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_reuniones', function (Blueprint $table) {
            $table->bigIncrements('id_reunion');
            $table->unsignedBigInteger('id_tipo_reunion');
            $table->foreign('id_tipo_reunion')->references('id_tipo_reunion')->on('gcm_tipo_reuniones');
            $table->string('descripcion', 5000)->nullable();
            $table->timestamp('fecha_actualizacion', $precision = 0)->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->date('fecha_reunion')->required();
            $table->time('hora')->required();
            $table->string('quorum', 2)->index()->required();
            $table->unsignedBigInteger('id_acta')->nullable();
            $table->foreign('id_acta')->references('id_acta')->on('gcm_actas');
            $table->string('programacion', 500)->nullable();
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
        Schema::dropIfExists('gcm_reuniones');
    }
}
