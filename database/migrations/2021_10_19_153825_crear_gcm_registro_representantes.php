<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmRegistroRepresentantes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_registro_representantes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_recurso')->required();
            $table->unsignedBigInteger('id_reunion')->required();
            $table->string('identificacion', 20)->required();
            $table->string('url_archivo', 255)->required();
            $table->char('estado', 1)->default(1);
            $table->primary(['id_recurso', 'id_reunion', 'identificacion'], 'pk_rec_reu_iden');
            $table->foreign('id_recurso')->references('id_recurso')->on('gcm_recursos');
            $table->foreign('id_reunion')->references('id_reunion')->on('gcm_reuniones');
            $table->index('identificacion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_registro_representantes');
    }
}
