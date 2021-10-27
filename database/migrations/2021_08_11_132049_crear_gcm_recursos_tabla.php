<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRecursosTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_recursos', function (Blueprint $table) {
            $table->bigIncrements('id_recurso');
            $table->string('identificacion', 20)->unique()->required();
            $table->string('nombre', 255)->required();
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 255)->index()->required();
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
        Schema::dropIfExists('gcm_recursos');
    }
}
