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
            'id_acta' => 1,
            'descripcion' => 'Presidente',
            'firma' => 1,
            'acta' => 0,
            'estado' => 1,
        ]);

        DB::table('gcm_roles_actas')->insert([
            'id_acta' => 1,
            'descripcion' => 'Secretario',
            'firma' => 1,
            'acta' => 0,
            'estado' => 1,
        ]);
    }
}
