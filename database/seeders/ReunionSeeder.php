<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReunionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gcm_reuniones')->insert([
            'id_reunion' => 1,
            'id_tipo_reunion' => 1,
            'descripcion' => 'Semilla',
            'fecha_reunion' => '2022-03-01',
            'hora' => '08:50:00',
            'quorum' => '0',
            'id_acta' => null,
            'programacion' => null,
            'estado' => '0',
        ]);
    }
}
