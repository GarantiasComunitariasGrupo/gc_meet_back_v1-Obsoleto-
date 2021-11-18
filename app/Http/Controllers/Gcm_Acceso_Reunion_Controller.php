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
use App\Http\Controllers\Gcm_Log_Acciones_Sistema_Controller;

class Gcm_Acceso_Reunion_Controller extends Controller
{
    /**
     * Función encargada de consultar las reuniones con estado (En espera, en curso)
     * a las que esté convocado un recurso 
     * y enviarle las respectivas invitaciones a su correo electrónico
     * @param $identificacion -> documento de identidad
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
            ->where('gcr.estado', 1)
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
                        'accion' => 4, 'tabla' => null,
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
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'detalle' => 'Function => buscarInvitacion()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }

    }

    /**
     * Función encargada de validar si un recurso tiene acceso a una reunión específica.
     * Se desencripta $idConvocadoReunion y se hace una consulta para validar
     * si la identificación asociada a ese registro coincide con la enviada por el usuario
     * @param $identificacion -> documento de identidad
     * @param $idConvocadoReunion -> id_convocado_reunion encriptado
     * @return JSON
     */
    public function validacionConvocado($identificacion, $idConvocadoReunion)
    {
        try {

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
            ->where('gcr.estado', 1)
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

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => validacionConvocado()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }
    }

    /**
     * Función encargada de consultar los diferentes id_convocado_reunion
     * que tenga un convocado para la reunión
     * @param $identificacion -> documento de identidad
     * @param $idReunion -> id_reunion
     * @return JSON
     */
    public function getIdConvocado($identificacion, $idReunion)
    {
        try {

            $response = array();
    
            $base = DB::table('gcm_convocados_reunion AS gcr')
            ->join('gcm_relaciones AS grc', 'gcr.id_relacion', '=', 'grc.id_relacion')
            ->join('gcm_recursos AS grcs', 'grc.id_recurso', '=', 'grcs.id_recurso')
            ->where('gcr.id_reunion', $idReunion)
            ->where('gcr.estado', 1)
            ->where('grcs.identificacion', $identificacion)
            ->select(['*'])
            ->get();
    
            $response = array(
                'ok' => (count($base) > 0) ? true : false,
                'response' => (count($base) > 0) ? $base : 'El usuario no fue convocado a la reunión o la invitación fue cancelada.'
            );
    
            return response()->json($response);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => getIdConvocado()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }
        
    }

    /**
     * Función encargada de consultar las restricciones que pueda tener un representante
     * @param $idConvocadoReunion -> id_convocado_reunion
     * @param $identificacion -> documento de identidad
     * @return JSON
     */
    public function getRestricciones($idConvocadoReunion, $identificacion)
    {
        try {

            $response = array();
    
            $tipoReunion = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
            ->join(DB::raw('gcm_reuniones AS grns'), 'gcr.id_reunion', '=', 'grns.id_reunion')
            ->where('gcr.id_convocado_reunion', $idConvocadoReunion)
            ->where('gcr.estado', 1)
            ->where('grns.estado', '!=', 4)
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

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => getRestricciones()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }
        
    }

    /**
     * Función encargada de enviar SMS con link para que el convocado pueda firmar
     * @param Request $request
     * @return JSON
     */
    public function enviarSMS(Request $request)
    {
        try {
            
            $encrypt = new Encrypt();
            $response = array();

            /**Se encripta id_convocado_reunion */
            $id = $encrypt->encriptar($request->idConvocadoReunion);
            $txtSMS = "Para acceder a la reunión, debe acceder al siguiente link: " . "http://192.168.2.85:4200/public/acceso-reunion/firma/{$id}";

            /** Petición HTTP::POST para consumir servicio de envío SMS */
            $request = Http::post("http://192.168.2.120:8801/api/messenger/enviar-sms/{$request->numeroCelular}", [
                'password' => 'tJXc1Mo/dBQUbqD5kg==',
                'sms' => $txtSMS
            ]);

            /** Se captura respuesta de la petición */
            $responseRequest = $request->json()['message'];
            $result = $responseRequest['action'];

            /** Se valida estado de la petición */
            if ($request->status() === 200) {

                if ($result === 'sendmessage') {
                    $response = array('ok' => true, 'response' => $responseRequest['data']['acceptreport']);
                } else {
                    $response = array('ok' => false, 'response' => $responseRequest['data']['errormessage']);
                }

                /** Se guarda log para acciones del sistema */
                $response['descripcion'] = 'Envío SMS firma digital';
                Gcm_Log_Acciones_Sistema_Controller::save(5, $response, null, null);

            }

            return response()->json($response);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => enviarSMS()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }

    }

    /**
     * Función encargada de recibir base64 de la imagen de la firma
     * y guardarla en su respectivo directorio
     * @param Request $request
     * @return JSON
     */
    public function enviarFirma(Request $request)
    {
        try {

            $response = array();
            $encrypt = new Encrypt();
            
            /**Se desencripta id_convocad_reunion */
            $id = $encrypt->desencriptar($request->idConvocadoReunion);

            /**Se valida que haya llegado el base64 de la imagen */
            if ($request->firmaBase64) {

                /** Se separa el contenido del base64 */
                $imgExplode = explode(';base64,', $request->firmaBase64);
                /** Se captura extensión de la imagen*/
                $imgType = explode('/', $imgExplode[0])[1];
                /** Se decodifica el base64 de la imagen*/
                $decodeImg = base64_decode($imgExplode[1]);
                /** Nombre para el archivo */
                $filename = uniqid() . '.' . $imgType;

                /**Se obtiene info. del id_convocado_reunion */
                $reunion = Gcm_Convocado_Reunion::where('id_convocado_reunion', $id)
                ->where('estado', 1)
                ->first();

                /** Si existe la carpeta con el nombre de => id_reunion */
                if (file_exists(public_path("storage/firmas/{$reunion->id_reunion}"))) {
                    /** Se crea archivo con la imagen de la firma */
                    file_put_contents(public_path("storage/firmas/{$reunion->id_reunion}/{$filename}"), $decodeImg);
                    /** Se otorgan permisos 0555 para el archivo creado */
                    chmod(public_path("storage/firmas/{$reunion->id_reunion}/{$filename}"), 0555);
                } else {
                    /** No existe la carpeta con el nombre de => id_reunion */

                    /** Se crea carpeta. Se le otorgan permisos 0777*/
                    $folder = mkdir(public_path("storage/firmas/{$reunion->id_reunion}"), 0777);

                    if ($folder) {
                        /** Se crea archivo con la imagen de la firma */
                        file_put_contents(public_path("storage/firmas/{$reunion->id_reunion}/{$filename}"), $decodeImg);
                        /** Se otorgan permisos 0555 para el archivo creado */
                        chmod(public_path("storage/firmas/{$reunion->id_reunion}/{$filename}"), 0555);
                    }
                }

                /**
                 * Se realiza petición HTTP::GET para enviar URL de la firma a el socket de NODEJS
                 */
                $request = Http::withOptions([
                    'curl' => array(CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false),
                    'verify' => false,
                ])->get("https://192.168.2.85:3009/get-url-firma", [
                    'url_firma' => "firmas/{$reunion->id_reunion}/{$filename}",
                    'id_convocado_reunion' => $id
                ]);

                /** Se valida estado de la petición */
                if ($request->status() === 200) {

                    /** Se captura respuesta de la petición */
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
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => enviarFirma()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }
    }

    /**
     * Función encargada de consultar si un convocado ya realizó la firma
     * @param $idConvocadoReunion -> id_convocado_reunion
     * @return JSON
     */
    public function permitirFirma($idConvocadoReunion)
    {
        try {

            $encrypt = new Encrypt();
            $response = array();
    
            /**Se desencripta id_convocado_reunion */
            $id = $encrypt->desencriptar($idConvocadoReunion);
    
            $convocado = Gcm_Convocado_Reunion::where('representacion', $id)
            ->where('estado', 1)
            ->first();
    
            $response = array(
                'ok' => (!$convocado) ? true : false,
                'response' => (!$convocado) ?: 'Usted ya realizó este proceso.'
            );
    
            return response()->json($response);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => permitirFirma()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }
        
    }

    /**
     * Función encargada de registrar un representante
     * @param Request $request
     * @return JSON
     */
    public function registrarRepresentante(Request $request)
    {
        $mailController = new Gcm_Mail_Controller();
        $encrypt = new Encrypt();
        $response = array();

        /**Transacción SQL */
        DB::beginTransaction();
        
        try {

            /** Se consulta la participación que tiene el convocado para asignarsela al representante del mismo */
            $anfitrion = Gcm_Convocado_Reunion::where('id_convocado_reunion', $request->params['id_convocado_reunion'])->where('estado', 1)->first();
            $participacionRepresentante = $anfitrion->participacion;

            /**Se consulta recurso */
            $recurso = Gcm_Recurso::where('identificacion', $request->params['identificacion'])->first();

            /** Si no existe el recurso, se registra */
            if (!$recurso) {
                $recurso = Gcm_Recurso::create([
                    'identificacion' => $request->params['identificacion'],
                    'nombre' => $request->params['nombre'],
                    'correo' => $request->params['correo'],
                    'estado' => 1
                ]);
            }

            /**Se consulta la relación */
            $relacion = Gcm_Relacion::where('id_grupo', $request->params['id_grupo'])
            ->where('id_rol', $request->params['id_rol'])
            ->where('id_recurso', $recurso->id_recurso)
            ->first();

            /** Si no existe la relación, se registra */
            if (!$relacion) {
                $relacion = Gcm_Relacion::create([
                    'id_grupo' => $request->params['id_grupo'],
                    'id_rol' => $request->params['id_rol'],
                    'id_recurso' => $recurso->id_recurso,
                    'estado' => 1,
                ]);
            }

            /** Se registra el convocado */
            $convocado = Gcm_Convocado_Reunion::create([
                'id_reunion' => $request->params['id_reunion'],
                'representacion' => $request->params['id_convocado_reunion'],
                'id_relacion' => $relacion->id_relacion,
                'fecha' => date('Y-m-d H:i:s'),
                'tipo' => 0,
                'participacion' => $participacionRepresentante,
                'soporte' => $request->params['url_firma']
            ]);

            /** Se actualiza número de celular en caso de ser modificado */
            $celular = Gcm_Recurso::where('identificacion', $request->params['identificacion'])
            ->update(['telefono' => $request->params['celular']]);

            DB::commit();

            /** Se encripta id_convocado_reunion */
            $idConvocadoReunion = $encrypt->encriptar($convocado->id_convocado_reunion);

            /** Cuerpo del correo */
            $body = "{$request->params['nombreAnfitrion']} lo ha invitado a usted a que lo represente en una reunión.
                    Link: http://192.168.2.85:4200/public/acceso-reunion/reunion/{$idConvocadoReunion}";

            /** Se envía correo electrónico de invitación al representante */
            $send = $mailController->send(
                'emails.formato-email',
                'Invitación de representación - GCMeet',
                'Invitación ?',
                $body,
                $request->params['correo']
            );

            /** Se guarda log de acciones del sistema */
            $send['correos'] = $request->params['correo'];
            $send['descripcion'] = 'Correo designación de poder';
            Gcm_Log_Acciones_Sistema_Controller::save(4, $send, null, null);

            $response = array('ok' => true, 'response' => ['recurso' => $recurso, 'convocado' => $convocado, 'mail' => $send]);
            return response()->json($response);

        } catch (\Throwable $th) {
            DB::rollback();
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => registrarRepresentante()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }

    }

    /**
     * Función encargada de consultar si un convocado tiene un representante para la reunión
     * @param $idConvocadoReunion -> id_convocado_reunion
     * @return JSON
     */
    public function getRepresentante($idConvocadoReunion)
    {
        try {
        
            $representante = DB::table('gcm_convocados_reunion AS gcr')
            ->join('gcm_relaciones AS grc', 'gcr.id_relacion', '=', 'grc.id_relacion')
            ->join('gcm_recursos AS grs', 'grc.id_recurso', '=', 'grs.id_recurso')
            ->where('gcr.representacion', $idConvocadoReunion)
            ->where('gcr.estado', 1)
            ->select(['*'])
            ->first();
    
            $response = array(
                'ok' => ($representante) ? true : false,
                'response' => ($representante) ? $representante : 'No hay resultados'
            );
    
            return response()->json($response);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => getRepresentante()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }
        
    }

    /**
     * Función encargada de consultar las designaciones de poder que se le otorgaron a un representante
     * @param Request $request
     * @return JSON
     */
    public function getRepresentados(Request $request)
    {
        try {

            $representados = DB::table('gcm_convocados_reunion AS gcr1')
            ->join('gcm_convocados_reunion AS gcr2', 'gcr1.representacion', '=', 'gcr2.id_convocado_reunion')
            ->join('gcm_relaciones AS grc', 'grc.id_relacion', '=', 'gcr2.id_relacion')
            ->join('gcm_recursos AS grs', 'grs.id_recurso', '=', 'grc.id_recurso')
            ->whereNotNull('gcr1.representacion')
            ->where('gcr1.estado', 1)
            ->where('gcr2.estado', 1)
            ->whereIn('gcr1.id_convocado_reunion', $request->idConvocadoReunion)
            ->get();
    
            $response = array(
                'ok' => (count($representados) > 0) ? true : false,
                'response' => (count($representados) > 0) ? $representados : 'No hay resultados'
            );
    
            return response()->json($response);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => getRepresentados()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }

    }

    /**
     * Función encargada de cancelar una designación de poder
     * @param Request $request
     * @return JSON
     */
    public function cancelarRepresentacion(Request $request)
    {
        try {

            $change = Gcm_Convocado_Reunion::where('id_convocado_reunion', $request->idConvocadoReunion)
            ->where('estado', 1)
            ->update(['estado' => 0]);

            $response = array(
                'ok' => ($change) ? true : false,
                'response' => ($change) ? 'Se ha cancelado la invitación de representación' : 'Error cancelando invitación'
            );

            return response()->json($response);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => cancelarRepresentacion()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }

    }

    /** 
     * Función privada para realizar pruebas de encriptar/desencriptar un valor
     * @param $valor -> string a encriptar/desencriptar
     * @param $tipo -> acción
     * @return JSON
     */
    protected function encriptar($valor, $tipo)
    {
        $encrypt = new Encrypt();
        $accion = (+$tipo === 1) ? 'encriptar' : 'desencriptar';
        $resultado = $encrypt->$accion($valor);
        
        return response()->json($resultado);
    }

    /**
     * Función encargada de cancelar las designaciones de poder que se le otorgaron a un representante
     * @param Request $request
     * @return JSON
     */
    public function cancelarRepresentaciones(Request $request)
    {
        $mailController = new Gcm_Mail_Controller();
        $response = array();
        $log = array();

        try {

            if (!empty($request->params)) {
    
                /**
                 * Se iteran las designaciones de poder
                 */
                foreach ($request->params as $key => $row) {
                    
                    /**Cuerpo del correo */
                    $body = "Cordial saludo, {$row['nombreRepresentado']}. El motivo de este correo es para notificarle que {$row['nombreRepresentante']} ha cancelado la representación a su nombre para la reunión.";
    
                    /** Se envía correo electrónico */
                    $send = $mailController->send(
                        'emails.formato-email',
                        'Cancelación de representación - GCMeet',
                        'Cancelación ?',
                        $body,
                        $row['correo']
                    );

                    $change = Gcm_Convocado_Reunion::where('id_convocado_reunion', $row['id_convocado_reunion'])
                    ->where('estado', 1)
                    ->update(['estado' => 0]);
    
                    /**
                     * Valida errores en el envío del correo electrónico
                     */
                    if (!$send['ok']) {
                        array_push($log['email'], ['error' => $send['error']]);
                    }
    
                    if (!$change) {
                        array_push($log['change'], ['error' => "Error actualizando {$row['id_convocado_reunion']}"]);
                    }
    
                }

                /** Se guarda log de acciones del sistema */
                $mail['result'] = (empty($log['email'])) ? true : false;
                (!empty($log['email'])) ? $mail['error'] = $log['email'] : null;
                $mail['correos'] = array_column(array($request->params), 'correo');
                $mail['descripcion'] = 'Correo cancelación de representaciones';
                Gcm_Log_Acciones_Sistema_Controller::save(4, $mail, null, null);

                $response = array(
                    'ok' => empty($log) ? true : false,
                    'response' => empty($log)
                        ? 'Las representaciones se han cancelado correctamente.'
                        : $log
                );
    
                return response()->json($response);
            }

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => cancelarRepresentaciones()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }

    }

    /**
     * Función encargada de consultar la programación de una reunión
     * @param $idConvocadoReunion -> id_convocado_reunion
     * @return JSON
     */
    public function getAvanceReunion($idConvocadoReunion)
    {
        try {
            
            $response = array();
    
            $reunion = DB::table('gcm_convocados_reunion AS gcr')
            ->join('gcm_programacion AS gp', 'gcr.id_reunion', '=', 'gp.id_reunion')
            ->where('id_convocado_reunion', $idConvocadoReunion)
            ->where('gcr.estado', 1)
            ->where('gp.estado', '!=', 4)
            ->get();
    
            $response = array(
                'ok' => (count($reunion) > 0) ? true : false,
                'response' => (count($reunion) > 0) ? $reunion : 'No hay resultados'
            );
    
            return response()->json($response);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => getAvanceReunion()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }

    }

    /**
     * Función enargada de obtener las reuniones en estado de espera/en curso a las que está invitado un convocado
     * Nota: Se buscan reuniones diferentes a la reunión actual
     * @param $idReunion -> id_reunion
     * @param $identificacion -> documento de identidad
     * @return JSON
     */
    public function getListadoReuniones($idReunion, $identificacion)
    {
        try {

            $response = array();
    
            $base = DB::table('gcm_convocados_reunion AS gcr')
            ->join('gcm_reuniones AS grns', 'gcr.id_reunion', '=', 'grns.id_reunion')
            ->join('gcm_relaciones AS grc', 'gcr.id_relacion', '=', 'grc.id_relacion')
            ->join('gcm_recursos AS grs', 'grc.id_recurso', '=', 'grs.id_recurso')
            ->join('gcm_grupos AS ggps', 'grc.id_grupo', '=', 'ggps.id_grupo')
            ->where('grs.identificacion', $identificacion)
            ->where('grns.id_reunion', '!=', $idReunion)
            ->where('gcr.estado', 1)
            ->whereIn('grns.estado', [0, 1])
            ->groupBy('grns.id_reunion')
            ->select(['gcr.*', 'grns.*', 'grc.*', 'ggps.descripcion AS descripcion_grupo'])
            ->get();
    
            $response = array(
                'ok' => (count($base) > 0) ? true : false,
                'response' => (count($base) > 0) ? $base : 'No hay resultados'
            );
    
            return response()->json($response);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['Error' => $th->getMessage(), 'Detalle' => 'Function => getListadoReuniones()'], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()]);
        }

    }

}
