<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRolesActasTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_roles_actas', function (Blueprint $table) {
            $table->bigIncrements('id_rol_acta');
            $table->unsignedBigInteger('id_acta');
            $table->foreign('id_acta')->references('id_acta')->on('gcm_actas');
            $table->string('descripcion', 100)->required();
            $table->string('firma', 2)->index()->required();
            $table->string('acta', 2)->index()->required();
            $table->string('estado', 2)->index()->required();
            $table->unique(['id_acta', 'descripcion']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_roles_actas');
    }
}
