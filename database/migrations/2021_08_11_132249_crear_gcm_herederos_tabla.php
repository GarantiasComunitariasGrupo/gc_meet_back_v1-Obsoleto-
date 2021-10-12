<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearGcmHerederosTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_herederos', function (Blueprint $table) {
            $table->integer('id_usuario')->required();
            $table->integer('id_heredero')->required();
            $table->primary(['id_usuario', 'id_heredero']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_herederos');
    }
}
