<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gcm_actas')->insert([
            'plantilla' => 'asamblea-general-ordinaria-accionistas',
            'descripcion' => 'Asamblea general ordinaria de accionistas',
            'estado' => 1,
        ]);
    }
}
