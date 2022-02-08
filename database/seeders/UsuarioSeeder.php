<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gcm_usuarios')->insert([
            'id_usuario' => 'gc_meet',
            'nombre' => 'GC Meet',
            'correo' => 'danilogg2015@gmail.com',
            'contrasena' => 'GCM' . Str::random(8),
            'estado' => 1,
            'tipo' => 0,
        ]);
    }
}
