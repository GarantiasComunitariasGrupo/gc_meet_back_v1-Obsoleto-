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
            'imagen' => '/assets/img/gc_colombia.png',
            'logo' => null,
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => 'gcm_danilo',
            'descripcion' => 'Garantías Comunitarias Panamá',
            'imagen' => '/assets/img/gc_panama.png',
            'logo' => null,
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => 'gcm_danilo',
            'descripcion' => 'GCBloomRisk',
            'imagen' => '/assets/img/gc_bloomrisk.png',
            'logo' => null,
            'estado' => 1,
        ]);

        DB::table('gcm_grupos')->insert([
            'acceso' => 'gcm_danilo',
            'descripcion' => 'GCMutual',
            'imagen' => '/assets/img/gc_mutual.png',
            'logo' => null,
            'estado' => 1,
        ]);
    }
}
