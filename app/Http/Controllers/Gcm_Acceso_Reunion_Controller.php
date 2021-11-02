<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gcm_Convocado_Reunion;
use App\Http\Classes\Encrypt;
use App\Http\Controllers\Gcm_Mail_Controller;
use App\Models\Gcm_Log_Accion_Sistema;

class Gcm_Acceso_Reunion_Controller extends Controller
{
    /**
     * Función encargada de consultar las reuniones con estado (En espera, en curso)
     * a las que esté convocado un recurso 
     * y enviarle las respectivas invitaciones a su correo electrónico
     * 
     * @param $identificacion -> documento de identidad
     * 
     * @return JSON
     */
    public function buscarInvitacion($identificacion)
    {
        try {
            
            $mailController = new Gcm_Mail_Controller();
            $response = array();
            $log = array();

            $base = DB::table('gcm_convocados_reunion AS gcr')
            ->join('gcm_reuniones AS grns', 'gcr.id_reunion', '=', 'grns.id_reunion')
            ->join('gcm_relaciones AS grc', 'gcr.id_relacion', '=', 'grc.id_relacion')
            ->join('gcm_recursos AS grcs', 'grc.id_recurso', '=', 'grcs.id_recurso')
            ->where('identificacion', $identificacion)
            ->whereIn('grns.estado', [0, 1])
            ->groupBy('gcr.id_reunion')
            ->select(['gcr.*', 'grcs.*', 'grns.descripcion'])
            ->get();

            /**
             * Si está convocado a reuniones en espera / en curso
             */
            if (count($base) > 0) {
                
                /**
                 * Se captura correo electrónico del recurso
                 */
                $correo = $base[0]->correo;

                /**
                 * Se recorren las reuniones a las que está convocado el recurso
                 */
                foreach ($base as $row) {

                    /**
                     * Se envía correo electrónico con invitación a las reuniones
                     */
                    $send = $mailController->send(
                        'emails.formato-email',
                        'Invitación reunión GCMeet',
                        "Invitación reunión - {$row->descripcion}",
                        'Este es el cuerpo del correo',
                        $correo
                    );

                    /**
                     * Si falla en envío de un correo, se guarda log
                     */
                    if (!$send['ok']) {
                        array_push($log, ['reunion' => 'id_reunion', 'error' => $send['error']]);
                    }

                    /**
                     * Se guarda log del sistema: Envío de correo electrónico
                     */
                    Gcm_Log_Accion_Sistema::create([
                        'accion' => 5, 'tabla' => null,
                        'fecha' => date('Y-m-d H:i:s'), 'lugar' => 'Invitar reunión',
                        'detalle' => json_encode($send)
                    ]);

                }

                /**
                 * Respuesta
                 */
                $response = array(
                    'ok' => empty($log) ? true : false,
                    'response' => empty($log) ? 
                    'Invitaciones enviadas correctamente, por favor revisar el correo electrónico.' : $send['error']
                );

            } else {
                $response = array('ok' => false, 'response' => 'El usuario no ha sido convocado para ninguna reunión');
            }
            
            return response()->json($response);

        } catch (\Throwable $th) {
            $response = array('ok' => false, 'response' => $th->getMessage());
            return response()->json($response);
        }

    }

    /**
     * Función encargada de validar si un recurso tiene acceso a una reunión específica.
     * 
     * Se desencripta $idConvocadoReunion y se hace una consulta para validar
     * si la identificación asociada a ese registro coincide con la enviada por el usuario
     * 
     * @param @identificacion -> documento de identidad
     * @param $idConvocadoReunion -> id_convocado_reunion encriptado
     * 
     * @return JSON
     */
    public function validacionConvocado($identificacion, $idConvocadoReunion)
    {
        $encrypt = new Encrypt();
        $response = array();

        /**
         * Se obtiene el valor del parámetro ya desencriptado
         */
        $id = $encrypt->desencriptar($idConvocadoReunion);

        /**
         * Consulta de validación
         */
        $convocado = DB::table('gcm_convocados_reunion AS gcr')
        ->join('gcm_relaciones AS grc', 'gcr.id_relacion', '=', 'grc.id_relacion')
        ->join('gcm_recursos AS grcs', 'grc.id_recurso', '=', 'grcs.id_recurso')
        ->where('id_convocado_reunion', $id)
        ->where('grcs.identificacion', $identificacion)
        ->first();

        /**
         * Respuesta
         */
        $response = array(
            'ok' => ($convocado) ? true : false,
            'response' => ($convocado) ? $convocado : 'El usuario no fue convocado a la reunión'
        );

        return response()->json($response);
    }

}
