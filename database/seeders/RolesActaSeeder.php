<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesActaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gcm_roles_actas')->insert([
            'descripcion' => 'Presidente',
            'id_acta' => 1,
            'estado' => 1,
            'firma' => 1,
            'acta' => 0,
        ]);

        DB::table('gcm_roles_actas')->insert([
            'descripcion' => 'Secretario',
            'id_acta' => 1,
            'estado' => 1,
            'firma' => 1,
            'acta' => 0,
        ]);
    }
}
