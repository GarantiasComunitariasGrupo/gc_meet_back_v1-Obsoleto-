<?php

namespace App\Console\Commands;

use App\Mail\GestorCorreos;
use App\Models\Gcm_Reunion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Gcm_Log_Acciones_Sistema_Controller;

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
    protected $description = 'Validación para una reunión por si depronto se pasa de la fecha estipulada.';

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
        try {
            $reuniones = Gcm_Reunion::where('estado', '=', '0')->get();

            $fecha_actual = date('Y-m-d');
            $hora_actual = date('H:i:s');

            if (count($reuniones) > 0) {

                $imagenes = [
                    'http://burodeconexiones.com/gc_balanced/public/assets/img/test/GCL.jpg',
                    'http://burodeconexiones.com/gc_balanced/public/assets/img/test/GCP.jpg',
                    'http://burodeconexiones.com/gc_balanced/public/assets/img/test/GBR.jpg',
                    'http://burodeconexiones.com/gc_balanced/public/assets/img/test/GM.jpg',
                ];

                for ($i = 0; $i < count($reuniones); $i++) {
                    if ($reuniones[$i]['fecha_reunion'] < $fecha_actual) {

                        $reunion = Gcm_Reunion::findOrFail($reuniones[$i]['id_reunion']);

                        $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                            ->join('gcm_grupos', 'gcm_tipo_reuniones.id_grupo', '=', 'gcm_grupos.id_grupo')
                            ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.id_grupo')
                            ->where(['id_reunion', $reuniones[$i]['id_reunion']])->get();

                        $reunion->estado = 3;
                        $reunion->save();

                        $id_grupo = $reunion['id_grupo'];

                        if ($id_grupo) {
                            $imagen = $imagenes[$id_grupo - 1];
                        }

                        $correo = 'danilogg2015@gmail.com';
                        $detalle = [
                            'view' => 'emails.reunion_vencida',
                            'message' => 'Alerta de reunión vencida en plataforma de juntas y asambleas',
                            'imagen' => $imagen,
                            'nombre' => 'Danilo Garcia',
                            'descripcion' => $reunion['descripcion'],
                            'fecha_reunion' => $reunion['fecha_reunion'],
                            'hora' => $reunion['hora'],
                        ];

                        Storage::append('archivo.txt', $reunion['descripcion']);
                        Mail::to($correo)->send(new GestorCorreos($detalle));
                        Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Envio de correo al administrador por la cancelacion de una reunion', 'Correos' => $correo), null);
                    }
                    if ($reuniones[$i]['fecha_reunion'] == $fecha_actual) {
                        // if ($hora_actual >= '7:00:00' && $hora_actual <= '18:00:00') {
                        if ($reuniones[$i]['hora'] <= $hora_actual) {

                            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                                ->join('gcm_grupos', 'gcm_tipo_reuniones.id_grupo', '=', 'gcm_grupos.id_grupo')
                                ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.id_grupo')
                                ->where(['id_reunion', $reuniones[$i]['id_reunion']])->get();

                            $id_grupo = $reunion['id_grupo'];

                            if ($id_grupo) {
                                $imagen = $imagenes[$id_grupo - 1];
                            }

                            $correo = 'danilogg2015@gmail.com';
                            $detalle = [
                                'view' => 'emails.alerta_reunion',
                                'message' => 'Alerta de la reunión programa para hoy en la plataforma de juntas y asambleas',
                                'imagen' => $imagen,
                                'nombre' => 'Danilo Garcia',
                                'descripcion' => $reunion['descripcion'],
                                'fecha_reunion' => $reunion['fecha_reunion'],
                                'hora' => $reunion['hora'],
                            ];

                            Storage::append('archivo.txt', $reunion['descripcion']);
                            Mail::to($correo)->send(new GestorCorreos($detalle));
                            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Envio de correo a el administrador por el retraso de la ejecucion de una reunion', 'Correos' => $correo), null);
                        }
                        // }
                    }
                }
            }
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
        }
    }
}
