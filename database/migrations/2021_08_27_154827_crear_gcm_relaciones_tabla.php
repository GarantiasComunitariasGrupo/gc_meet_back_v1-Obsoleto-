<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRelacionesTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_relaciones', function (Blueprint $table) {
            $table->bigIncrements('id_relacion');
            $table->unsignedBigInteger('id_grupo'); 
            $table->foreign('id_grupo')->references('id_grupo')->on('gcm_grupos');
            $table->unsignedBigInteger('id_rol'); 
            $table->foreign('id_rol')->references('id_rol')->on('gcm_roles');
            $table->unsignedBigInteger('id_recurso'); 
            $table->foreign('id_recurso')->references('id_recurso')->on('gcm_recursos');
            $table->decimal('participacion', $precision = 3, $scale = 2);
            $table->string('estado', 2)->index()->required();
            $table->unique(['id_grupo', 'id_rol', 'id_recurso']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_relaciones');
    }
}
