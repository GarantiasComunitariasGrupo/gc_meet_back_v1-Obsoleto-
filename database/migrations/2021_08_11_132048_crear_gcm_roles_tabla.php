<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRolesTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_roles', function (Blueprint $table) {
            $table->bigIncrements('id_rol');
            $table->string('descripcion', 100)->required();
            $table->unsignedBigInteger('relacion')->nullable(); 
            $table->foreign('relacion')->references('id_rol')->on('gcm_roles');
            $table->string('estado', 2)->index()->required();
            $table->unique(['descripcion', 'relacion']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_roles');
    }
}
