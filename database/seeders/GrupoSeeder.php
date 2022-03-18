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
            'acceso' => null,
            'descripcion' => 'Garantías Comunitarias Colombia',
            'imagen' => '/assets/img/meets/bg1.png',
            'logo' => '/assets/img/meets/garantias-comunitarias.png',
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => null,
            'descripcion' => 'Garantías Comunitarias Panamá',
            'imagen' => '/assets/img/meets/bg2.png',
            'logo' => '/assets/img/meets/garantias-panama.png',
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => null,
            'descripcion' => 'GCBloomRisk',
            'imagen' => '/assets/img/meets/bg3.png',
            'logo' => '/assets/img/meets/gcbloomrisk.png',
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => null,
            'descripcion' => 'GCMutual',
            'imagen' => '/assets/img/meets/bg4.png',
            'logo' => '/assets/img/meets/gcmutual.png',
            'estado' => 1,
        ]);
    }
}
