<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoReunionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('gcm_tipo_reuniones')->insert([
            'id_grupo' => 1,
            'titulo' => 'Asamblea de accionistas',
            'honorifico_participante' => 'Participantes',
            'honorifico_invitado' => 'Invitados',
            'honorifico_representante' => 'Representantes',
            'imagen' => '/assets/img/fondo-logo2.jpg',
            'estado' => 1,
        ]);

        DB::table('gcm_tipo_reuniones')->insert([
            'id_grupo' => 1,
            'titulo' => 'Junta directiva',
            'honorifico_participante' => 'Participantes',
            'honorifico_invitado' => 'Invitados',
            'honorifico_representante' => 'Representantes',
            'imagen' => '/assets/img/fondo-logo2.jpg',
            'estado' => 1,
        ]);

        DB::table('gcm_tipo_reuniones')->insert([
            'id_grupo' => 2,
            'titulo' => 'Asamblea de accionistas',
            'honorifico_participante' => 'Participantes',
            'honorifico_invitado' => 'Invitados',
            'honorifico_representante' => 'Representantes',
            'imagen' => '/assets/img/fondo-logo2.jpg',
            'estado' => 1,
        ]);

        DB::table('gcm_tipo_reuniones')->insert([
            'id_grupo' => 2,
            'titulo' => 'Junta directiva',
            'honorifico_participante' => 'Participantes',
            'honorifico_invitado' => 'Invitados',
            'honorifico_representante' => 'Representantes',
            'imagen' => '/assets/img/fondo-logo2.jpg',
            'estado' => 1,
        ]);

        DB::table('gcm_tipo_reuniones')->insert([
            'id_grupo' => 3,
            'titulo' => 'Asamblea de accionistas',
            'honorifico_participante' => 'Participantes',
            'honorifico_invitado' => 'Invitados',
            'honorifico_representante' => 'Representantes',
            'imagen' => '/assets/img/fondo-logo2.jpg',
            'estado' => 1,
        ]);

        DB::table('gcm_tipo_reuniones')->insert([
            'id_grupo' => 3,
            'titulo' => 'Junta directiva',
            'honorifico_participante' => 'Participantes',
            'honorifico_invitado' => 'Invitados',
            'honorifico_representante' => 'Representantes',
            'imagen' => '/assets/img/fondo-logo2.jpg',
            'estado' => 1,
        ]);

        DB::table('gcm_tipo_reuniones')->insert([
            'id_grupo' => 4,
            'titulo' => 'Asamblea de accionistas',
            'honorifico_participante' => 'Participantes',
            'honorifico_invitado' => 'Invitados',
            'honorifico_representante' => 'Representantes',
            'imagen' => '/assets/img/fondo-logo2.jpg',
            'estado' => 1,
        ]);

        DB::table('gcm_tipo_reuniones')->insert([
            'id_grupo' => 4,
            'titulo' => 'Junta directiva',
            'honorifico_participante' => 'Participantes',
            'honorifico_invitado' => 'Invitados',
            'honorifico_representante' => 'Representantes',
            'imagen' => '/assets/img/fondo-logo2.jpg',
            'estado' => 1,
        ]);
    }
}
