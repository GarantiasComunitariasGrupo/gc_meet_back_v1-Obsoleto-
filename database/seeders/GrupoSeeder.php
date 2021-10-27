<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gcm_grupos')->insert([
            'acceso' => 'gcm_danilo',
            'descripcion' => 'Garantías Comunitarias Colombia',
            'imagen' => null,
            'logo' => null,
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => 'gcm_danilo',
            'descripcion' => 'Garantías Comunitarias Panamá',
            'imagen' => null,
            'logo' => null,
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => 'gcm_danilo',
            'descripcion' => 'GCBloomRisk',
            'imagen' => null,
            'logo' => null,
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => 'gcm_danilo',
            'descripcion' => 'GCMutual',
            'imagen' => null,
            'logo' => null,
            'estado' => 1,
        ]);
    }
}