<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmActasTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_actas', function (Blueprint $table) {
            $table->bigIncrements('id_acta');
            $table->string('descripcion', 100)->required();
            $table->string('estado', 2)->index()->required();
            $table->string('plantilla', 100)->required();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_actas');
    }
}
