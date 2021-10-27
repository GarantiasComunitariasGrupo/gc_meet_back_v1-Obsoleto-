<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmArchivosProgramacionTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_archivos_programacion', function (Blueprint $table) {
            $table->bigIncrements('id_archivo_programacion');
            $table->unsignedBigInteger('id_programa');
            $table->foreign('id_programa')->references('id_programa')->on('gcm_programacion');
            $table->string('descripcion', 200)->required();
            $table->integer('peso')->required();
            $table->string('url', 255)->required();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_archivos_programacion');
    }
}
