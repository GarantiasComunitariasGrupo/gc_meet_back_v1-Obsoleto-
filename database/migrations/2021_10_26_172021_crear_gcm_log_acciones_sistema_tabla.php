<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CrearGcmLogAccionesSistemaTabla extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gcm_log_acciones_sistema', function (Blueprint $table) {
            $table->bigIncrements('id_log_accion');
            $table->string('accion', 3)->index()->required();
            $table->string('tabla', 100)->index()->nullable();
            $table->timestamp('fecha', $precision = 0)->required()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('lugar', 100)->required();
            $table->longText('detalle')->required();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gcm_log_acciones_sistema');
    }
}
