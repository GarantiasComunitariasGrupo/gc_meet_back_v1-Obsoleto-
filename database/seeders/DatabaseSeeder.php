<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            ActaSeeder::class,
            RolesActaSeeder::class,
            UsuarioSeeder::class,
            GrupoSeeder::class,
            RolSeeder::class,
            TipoReunionSeeder::class,
            RestriccionSeeder::class,
            ReunionSeeder::class,
        ]);
    }
}
