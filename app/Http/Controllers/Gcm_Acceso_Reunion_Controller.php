<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gcm_Convocado_Reunion;
use App\Http\Classes\Encrypt;
use App\Http\Controllers\Gcm_Mail_Controller;
use App\Models\Gcm_Log_Accion_Sistema;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Restriccion_Rol_Representante;
use Illuminate\Support\Facades\Http;

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
                    $send['correos'] = $correo;
                    $send['identificador'] = 'Consulta invitaciones';

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

    public function getIdConvocado($identificacion, $idReunion)
    {
        $response = array();

        $base = DB::table('gcm_convocados_reunion AS gcr')
        ->join('gcm_relaciones AS grc', 'gcr.id_relacion', '=', 'grc.id_relacion')
        ->join('gcm_recursos AS grcs', 'grc.id_recurso', '=', 'grcs.id_recurso')
        ->where('gcr.id_reunion', $idReunion)
        ->where('grcs.identificacion', $identificacion)
        ->select(['*'])
        ->get();

        $response = array(
            'ok' => (count($base) > 0) ? true : false,
            'response' => (count($base) > 0) ? $base : 'El usuario no fue convocado a la reunión o la invitación fue cancelada.'
        );

        return response()->json($response);
    }

    public function getRestricciones($idConvocadoReunion, $identificacion)
    {
        $response = array();

        $tipoReunion = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
        ->join(DB::raw('gcm_reuniones AS grns'), 'gcr.id_reunion', '=', 'grns.id_reunion')
        ->where('gcr.id_convocado_reunion', $idConvocadoReunion)
        ->select(['id_tipo_reunion'])
        ->first();

        $roles = DB::table(DB::raw('gcm_recursos AS grs'))
        ->join(DB::raw('gcm_relaciones AS grc'), 'grs.id_recurso', '=', 'grc.id_recurso')
        ->where('identificacion', $identificacion)
        ->where('grc.estado', 1)
        ->select(['id_rol'])
        ->get();

        $restricciones = Gcm_Restriccion_Rol_Representante::where('estado', 1)
        ->where('id_tipo_reunion', $tipoReunion->id_tipo_reunion)
        ->whereIn('id_rol', array_column($roles->toArray(), 'id_rol'))
        ->get();

        $response = array(
            'ok' => (count($restricciones) > 0) ? true : false,
            'response' => (count($restricciones) > 0) ? $restricciones : 'No hay resultados'
        );

        return response()->json($response);
    }

    public function enviarSMS(Request $request)
    {
        try {
            
            $encrypt = new Encrypt();
            $response = array();

            $id = $encrypt->encriptar($request->idConvocadoReunion);
            $txtSMS = "Para acceder a la reunión, debe acceder al siguiente link: " . "http://192.168.2.85:4200/public/acceso-reunion/firma/{$id}";

            $request = Http::post("http://192.168.2.120:8801/api/messenger/enviar-sms/{$request->numeroCelular}", [
                'password' => 'tJXc1Mo/dBQUbqD5kg==',
                'sms' => $txtSMS
            ]);

            $responseRequest = $request->json()['message'];
            $result = $responseRequest['action'];

            if ($request->status() === 200) {
                if ($result === 'sendmessage') {
                    $response = array('ok' => true, 'response' => $responseRequest['data']['acceptreport']);
                } else {
                    $response = array('ok' => false, 'response' => $responseRequest['data']['errormessage']);
                }
            }

            return response()->json($response);

        } catch (\Throwable $th) {
            $response = array('ok' => false, 'response' => $th->getMessage());
            return response()->json($response);
        }

    }

    public function enviarFirma(Request $request)
    {
        try {

            $response = array();
            $encrypt = new Encrypt();
            
            $id = $encrypt->desencriptar($request->idConvocadoReunion);

            if ($request->firmaBase64) {

                $imgExplode = explode(';base64,', $request->firmaBase64);
                $imgType = explode('/', $imgExplode[0])[1];
                $decodeImg = base64_decode($imgExplode[1]);
                $filename = uniqid() . '.' . $imgType;

                $reunion = Gcm_Convocado_Reunion::where('id_convocado_reunion', $id)
                ->first();

                if (file_exists(public_path("storage/firmas/{$reunion->id_reunion}"))) {
                    file_put_contents(public_path("storage/firmas/{$reunion->id_reunion}/{$filename}"), $decodeImg);
                    chmod(public_path("storage/firmas/{$reunion->id_reunion}/{$filename}"), 0555);
                } else {
                    $folder = mkdir(public_path("storage/firmas/{$reunion->id_reunion}"), 0777);
                    if ($folder) {
                        file_put_contents(public_path("storage/firmas/{$reunion->id_reunion}/{$filename}"), $decodeImg);
                        chmod(public_path("storage/firmas/{$reunion->id_reunion}/{$filename}"), 0555);
                    }
                }

                $request = Http::withOptions([
                    'curl' => array(CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false),
                    'verify' => false,
                ])->get("https://192.168.2.85:3009/get-url-firma", [
                    'url_firma' => "firmas/{$reunion->id_reunion}/{$filename}",
                    'id_convocado_reunion' => $id
                ]);

                if ($request->status() === 200) {

                    $result = $request->json();

                    if ($result['ok']) {
                        $response = array('ok' => true, 'response' => 'Firma ok');
                    } else {
                        $response = array('ok' => false, 'response' => 'Error');
                    }

                    return response()->json($response);
                }

            }
        } catch(\Throwable $th) {
            $response = array('ok' => false, 'response' => $th->getMessage());
            return response()->json($response);
        }
    }

    public function permitirFirma($idConvocadoReunion)
    {
        $encrypt = new Encrypt();
        $response = array();

        $id = $encrypt->desencriptar($idConvocadoReunion);

        $convocado = Gcm_Convocado_Reunion::where('representacion', $id)
        ->first();

        $response = array(
            'ok' => (!$convocado) ? true : false,
            'response' => (!$convocado) ?: 'Usted ya realizó este proceso.'
        );

        return response()->json($response);
    }

    public function registrarRepresentante(Request $request)
    {
        $mailController = new Gcm_Mail_Controller();
        $encrypt = new Encrypt();
        $response = array();

        DB::beginTransaction();
        
        try {

            $anfitrion = Gcm_Convocado_Reunion::where('id_convocado_reunion', $request->params['id_convocado_reunion'])->first();
            $participacionRepresentante = $anfitrion->participacion;

            $recurso = Gcm_Recurso::where('identificacion', $request->params['identificacion'])->first();

            if (!$recurso) {
                $recurso = Gcm_Recurso::create([
                    'identificacion' => $request->params['identificacion'],
                    'nombre' => $request->params['nombre'],
                    'correo' => $request->params['correo'],
                    'estado' => 1
                ]);
            }

            $relacion = Gcm_Relacion::where('id_grupo', $request->params['id_grupo'])
            ->where('id_rol', $request->params['id_rol'])
            ->where('id_recurso', $recurso->id_recurso)
            ->first();

            if (!$relacion) {
                $relacion = Gcm_Relacion::create([
                    'id_grupo' => $request->params['id_grupo'],
                    'id_rol' => $request->params['id_rol'],
                    'id_recurso' => $recurso->id_recurso,
                    'estado' => 1,
                ]);
            }

            $convocado = Gcm_Convocado_Reunion::create([
                'id_reunion' => $request->params['id_reunion'],
                'representacion' => $request->params['id_convocado_reunion'],
                'id_relacion' => $relacion->id_relacion,
                'fecha' => date('Y-m-d H:i:s'),
                'tipo' => 0,
                'participacion' => $participacionRepresentante,
                'soporte' => $request->params['url_firma']
            ]);

            $celular = Gcm_Recurso::where('identificacion', $request->params['identificacion'])
            ->update(['telefono' => $request->params['celular']]);

            DB::commit();

            $idConvocadoReunion = $encrypt->encriptar($convocado->id_convocado_reunion);

            $body = "{$request->parmams['nombreAnfitrion']} lo ha invitado a usted a que lo represente en una reunión.
                    Link: http://192.168.2.85:4200/public/acceso-reunion/reunion/{$idConvocadoReunion}";

            $send = $mailController->send(
                'emails.formato-email',
                'Invitación de representación - GCMeet',
                'Invitación ?',
                $body,
                $request->params['correo']
            );

            $response = array('ok' => true, 'response' => ['recurso' => $recurso, 'convocado' => $convocado, 'mail' => $send]);
            return response()->json($response);

        } catch (\Throwable $th) {
            DB::rollback();
            $response = array('ok' => false, 'response' => $th->getMessage());
            return response()->json($response);
        }

    }

    public function getRepresentante($idConvocadoReunion)
    {
        $representante = DB::table('gcm_convocados_reunion AS gcr')
        ->join('gcm_relaciones AS grc', 'gcr.id_relacion', '=', 'grc.id_relacion')
        ->join('gcm_recursos AS grs', 'grc.id_recurso', '=', 'grs.id_recurso')
        ->where('gcr.representacion', $idConvocadoReunion)
        ->select(['*'])
        ->first();

        $response = array(
            'ok' => ($representante) ? true : false,
            'response' => ($representante) ? $representante : 'No hay resultados'
        );

        return response()->json($response);
    }

    public function getRepresentados(Request $request)
    {
        $representados = DB::table('gcm_convocados_reunion AS gcr1')
        ->join('gcm_convocados_reunion AS gcr2', 'gcr1.representacion', '=', 'gcr2.id_convocado_reunion')
        ->join('gcm_relaciones AS grc', 'grc.id_relacion', '=', 'gcr2.id_relacion')
        ->join('gcm_recursos AS grs', 'grs.id_recurso', '=', 'grc.id_recurso')
        ->whereNotNull('gcr1.representacion')
        ->whereIn('gcr1.id_convocado_reunion', $request->idConvocadoReunion)
        ->get();

        $response = array(
            'ok' => (count($representados) > 0) ? true : false,
            'response' => (count($representados) > 0) ? $representados : 'No hay resultados'
        );

        return response()->json($response);
    }

    public function cancelarRepresentacion(Request $request)
    {
        try {

            $delete = Gcm_Convocado_Reunion::where('id_convocado_reunion', $request->idConvocadoReunion)
            ->delete();

            $response = array(
                'ok' => ($delete) ? true : false,
                'response' => ($delete) ? 'Se ha cancelado la invitación de representación' : 'Error cancelando invitación'
            );

            return response()->json($response);

        } catch (\Throwable $th) {
            $response = array('ok' => false, 'response' => $th->getMessage());
            return response()->json($response);
        }

    }

    public function encriptar($valor)
    {
        $encrypt = new Encrypt();

        $resultado = $encrypt->encriptar($valor);
        return response()->json($resultado);
    }

    public function cancelarRepresentaciones(Request $request)
    {
        return response()->json($request);
    }

}
