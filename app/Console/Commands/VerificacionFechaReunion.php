<?php

namespace App\Console\Commands;

use App\Mail\ReunionVencida;
use App\Models\Gcm_Reunion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class VerificacionFechaReunion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validación para un reunión por si depronto se pasa de la fecha estipulada.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $reuniones = Gcm_Reunion::where('estado', '=', '0')->get();

        $fecha_actual = date('Y-m-d');
        $hora_actual = date('H:i:s');

        if (count($reuniones) > 0) {
            for ($i = 0; $i < count($reuniones); $i++) {
                if ($reuniones[$i]['fecha_reunion'] < $fecha_actual) {
                    Storage::append('archivo.txt', $reuniones[$i]['fecha_reunion']);

                    $reunion = Gcm_Reunion::findOrFail($reuniones[$i]['id_reunion']);
                    $reunion->estado = 3;
                    $reunion->save();

                    $correo = 'danilogg2015@gmail.com';
                    $detalle = ['nombre' => 'Danilo'];
                    Mail::to($correo)->send(new ReunionVencida($detalle));
                }
                if ($reuniones[$i]['fecha_reunion'] == $fecha_actual) {
                    if ($hora_actual >= '7:00:00' && $hora_actual <= '18:00:00') {
                        if ($reuniones[$i]['hora'] <= $hora_actual) {
                            $correo = 'danilogg2015@gmail.com';
                            $detalle = ['nombre' => 'Danilo'];
                            Mail::to($correo)->send(new AlertaReunion($detalle));
                        }
                    }
                }
            }
        }
    }
}
