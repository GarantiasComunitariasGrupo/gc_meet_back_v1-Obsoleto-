<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gcm_roles')->insert([
            'descripcion' => 'Junta directiva',
            'relacion' => null,
            'estado' => 1,
        ]);

        DB::table('gcm_roles')->insert([
            'descripcion' => 'Gerente',
            'relacion' => null,
            'estado' => 1,
        ]);

        DB::table('gcm_roles')->insert([
            'descripcion' => 'Representante',
            'relacion' => null,
            'estado' => 1,
        ]);
    }
}
