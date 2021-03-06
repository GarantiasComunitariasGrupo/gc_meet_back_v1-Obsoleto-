<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestriccionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Restricciones grupo 1
        DB::table('gcm_restricciones_rol_representante')->insert([
            'id_tipo_reunion' => 1,
            'id_rol' => 1,
            'descripcion' => 'Un miembro de Junta Directiva o el Gerente solo puede representar las acciones propias',
            'estado' => 1,
        ]);

        DB::table('gcm_restricciones_rol_representante')->insert([
            'id_tipo_reunion' => 1,
            'id_rol' => 2,
            'descripcion' => 'Un miembro de Junta Directiva o el Gerente solo puede representar las acciones propias',
            'estado' => 1,
        ]);

        // Restricciones grupo 2
        DB::table('gcm_restricciones_rol_representante')->insert([
            'id_tipo_reunion' => 3,
            'id_rol' => 1,
            'descripcion' => 'Un miembro de Junta Directiva o el Gerente solo puede representar las acciones propias',
            'estado' => 1,
        ]);

        DB::table('gcm_restricciones_rol_representante')->insert([
            'id_tipo_reunion' => 3,
            'id_rol' => 2,
            'descripcion' => 'Un miembro de Junta Directiva o el Gerente solo puede representar las acciones propias',
            'estado' => 1,
        ]);

        // Restricciones grupo 3
        DB::table('gcm_restricciones_rol_representante')->insert([
            'id_tipo_reunion' => 5,
            'id_rol' => 1,
            'descripcion' => 'Un miembro de Junta Directiva o el Gerente solo puede representar las acciones propias',
            'estado' => 1,
        ]);

        DB::table('gcm_restricciones_rol_representante')->insert([
            'id_tipo_reunion' => 5,
            'id_rol' => 2,
            'descripcion' => 'Un miembro de Junta Directiva o el Gerente solo puede representar las acciones propias',
            'estado' => 1,
        ]);

        //// Restricciones grupo 4
        DB::table('gcm_restricciones_rol_representante')->insert([
            'id_tipo_reunion' => 7,
            'id_rol' => 1,
            'descripcion' => 'Un miembro de Junta Directiva o el Gerente solo puede representar las acciones propias',
            'estado' => 1,
        ]);

        DB::table('gcm_restricciones_rol_representante')->insert([
            'id_tipo_reunion' => 7,
            'id_rol' => 2,
            'descripcion' => 'Un miembro de Junta Directiva o el Gerente solo puede representar las acciones propias',
            'estado' => 1,
        ]);
    }
}
