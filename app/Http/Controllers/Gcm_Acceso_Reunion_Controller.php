<?php

namespace App\Http\Controllers;

use App\Http\Classes\Encrypt;
use App\Http\Controllers\Gcm_Log_Acciones_Sistema_Controller;
use App\Http\Controllers\Gcm_Mail_Controller;
use App\Models\Gcm_Asistencia_Reunion;
use App\Models\Gcm_Convocado_Reunion;
use App\Models\Gcm_Log_Accion_Sistema;
use App\Models\Gcm_Programacion;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Respuesta_Convocado;
use App\Models\Gcm_Restriccion_Rol_Representante;
use App\Models\Gcm_Reunion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

                    $encrypt = new Encrypt();

                    $idEncriptado = $encrypt->encriptar($row->id_convocado_reunion);

                    /**
                     * Se envía correo electrónico con invitación a las reuniones
                     */
                    $send = $mailController->send(
                        'emails.formato-email',
                        'Invitación reunión GCMeet',
                        "Invitación reunión - {$row->descripcion}",
                        "Este es el cuerpo del correo => " . env('VIEW_BASE') . "/public/acceso-reunion/acceso/{$idEncriptado}",
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
                        'detalle' => json_encode($send),
                    ]);

                }

                /**
                 * Respuesta
                 */
                $response = array(
                    'ok' => empty($log) ? true : false,
                    'response' => empty($log) ?
                    'Invitaciones enviadas correctamente, por favor revisar el correo electrónico.' : $send['error'],
                );

            } else {
                $response = array('ok' => false, 'response' => 'El usuario no ha sido convocado para ninguna reunión');
            }

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                'response' => ($convocado) ? $convocado : 'El usuario no fue convocado a la reunión',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                'response' => (count($base) > 0) ? $base : 'El usuario no fue convocado a la reunión o la invitación fue cancelada.',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                'response' => (count($restricciones) > 0) ? $restricciones : 'No hay resultados',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }

    }

    /** Si la persona que se elige como representante ha elegido otro representante significa se añade restricción */
    public function getOtrasRestricciones($idConvocadoReunion, $identificacion)
    {
        $restricciones = DB::table('gcm_convocados_reunion AS gcr')
            ->join('gcm_convocados_reunion AS gcr2', 'gcr.representacion', 'gcr2.id_convocado_reunion')
            ->join('gcm_relaciones AS grl', 'grl.id_relacion', 'gcr2.id_relacion')
            ->join('gcm_recursos AS grc', 'grc.id_recurso', 'grl.id_recurso')
            ->where([['gcr.estado', 1], ['grc.identificacion', $identificacion], ['gcr.id_reunion', '=', function ($query) use ($idConvocadoReunion) {
                $query->from('gcm_convocados_reunion')->where('id_convocado_reunion', $idConvocadoReunion)->select('id_reunion');
            }]])->whereNotNull('gcr.representacion')->select('grc.identificacion');

        $response = array(
            'ok' => (count($restricciones->get()) > 0) ? true : false,
            'response' => (count($restricciones->get()) > 0) ? [['descripcion' => 'No es posible designar a un representante que ha notificado la no asistencia a la reunión']] : ('No hay resultados' . $restricciones->toSql()),
        );

        return response()->json($response, 200);
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
            $txtSMS = "Para acceder a la reunión, debe acceder al siguiente link: " . env('VIEW_BASE') . "/public/acceso-reunion/firma/{$id}";

            /** Petición HTTP::POST para consumir servicio de envío SMS */
            $request = Http::post(env('GCAPI_BASE') . "/api/messenger/enviar-sms/{$request->numeroCelular}", [
                'password' => env('GCAPI_PASS'),
                'sms' => $txtSMS,
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

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage(), 'linea' => $th->getLine()], 500);
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

                if (!file_exists(storage_path("app/public/firmas"))) {
                    mkdir(storage_path("app/public/firmas"), 0777);
                    chmod(storage_path("app/public/firmas"), 0777);
                }

                /** Si existe la carpeta con el nombre de => id_reunion */
                if (file_exists(storage_path("app/public/firmas/{$reunion->id_reunion}"))) {
                    /** Se crea archivo con la imagen de la firma */
                    file_put_contents(storage_path("app/public/firmas/{$reunion->id_reunion}/{$filename}"), $decodeImg);
                    /** Se otorgan permisos 0777 para el archivo creado */
                    chmod(storage_path("app/public/firmas/{$reunion->id_reunion}/{$filename}"), 0777);
                } else {
                    /** No existe la carpeta con el nombre de => id_reunion */

                    /** Se crea carpeta. Se le otorgan permisos 0777*/
                    $folder = mkdir(storage_path("app/public/firmas/{$reunion->id_reunion}"), 0777);
                    chmod(storage_path("app/public/firmas/{$reunion->id_reunion}"), 0777);

                    if ($folder) {
                        /** Se crea archivo con la imagen de la firma */
                        file_put_contents(storage_path("app/public/firmas/{$reunion->id_reunion}/{$filename}"), $decodeImg);
                        /** Se otorgan permisos 0777 para el archivo creado */
                        chmod(storage_path("app/public/firmas/{$reunion->id_reunion}/{$filename}"), 0777);
                    }
                }

                /**
                 * Se realiza petición HTTP::GET para enviar URL de la firma a el socket de NODEJS
                 */
                $request = Http::withOptions([
                    'curl' => array(CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false),
                    'verify' => false,
                ])->get(env('API_SOCKETS') . "/get-url-firma", [
                    'url_firma' => "firmas/{$reunion->id_reunion}/{$filename}",
                    'id_convocado_reunion' => $id,
                ]);

                /** Se valida estado de la petición */
                if ($request->status() === 200) {

                    /** Se captura respuesta de la petición */
                    $result = $request->json();

                    if ($result['ok']) {
                        $response = array('ok' => true, 'response' => 'Firma ok');
                    } else {
                        $response = array('ok' => false, 'response' => 'Proceso finalizado por el usuario');
                    }

                    return response()->json($response, 200);
                }

            }
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                'response' => (!$convocado) ?: 'Usted ya realizó este proceso.',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                    'estado' => 1,
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
                'soporte' => $request->params['url_firma'],
            ]);

            /** Se actualiza número de celular en caso de ser modificado */
            $celular = Gcm_Recurso::where('identificacion', $request->params['identificacion'])
                ->update(['telefono' => $request->params['celular']]);

            DB::commit();

            /** Se encripta id_convocado_reunion */
            $idConvocadoReunion = $encrypt->encriptar($convocado->id_convocado_reunion);

            /** Cuerpo del correo */
            $body = "{$request->params['nombreAnfitrion']} lo ha invitado a usted a que lo represente en una reunión.
                    Link: " . env('VIEW_BASE') . "/public/acceso-reunion/acceso/{$idConvocadoReunion}";

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
            return response()->json($response, 200);

        } catch (\Throwable $th) {
            DB::rollback();
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                'response' => ($representante) ? $representante : 'No hay resultados',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                'response' => (count($representados) > 0) ? $representados : 'No hay resultados',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                'response' => ($change) ? 'Se ha cancelado la invitación de representación' : 'Error cancelando invitación',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                    : $log,
                );

                return response()->json($response, 200);
            }

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                ->where('gcr.id_convocado_reunion', $idConvocadoReunion)
                ->where('gcr.estado', 1)
                ->where('gp.estado', '!=', 4)
                ->get();

            $response = array(
                'ok' => (count($reunion) > 0) ? true : false,
                'response' => (count($reunion) > 0) ? $reunion : 'No hay resultados',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
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
                ->join('gcm_tipo_reuniones AS gtr', 'gtr.id_tipo_reunion', '=', 'grns.id_tipo_reunion')
                ->join('gcm_relaciones AS grc', 'gcr.id_relacion', '=', 'grc.id_relacion')
                ->join('gcm_recursos AS grs', 'grc.id_recurso', '=', 'grs.id_recurso')
                ->join('gcm_grupos AS ggps', 'grc.id_grupo', '=', 'ggps.id_grupo')
                ->where('grs.identificacion', $identificacion)
                ->where('grns.id_reunion', '!=', $idReunion)
                ->where('gcr.estado', 1)
                ->whereIn('grns.estado', [0, 1])
                ->groupBy('grns.id_reunion')
                ->select(['grns.descripcion', 'ggps.id_grupo', 'ggps.descripcion AS descripcion_grupo', 'grns.estado', 'grns.fecha_reunion', 'grns.hora', 'gcr.id_convocado_reunion', 'gtr.imagen', 'gtr.titulo'])
                ->get();

            $response;

            if (count($base) > 0) {
                $response = [
                    'ok' => true,
                    'response' => $base->map(function ($item) {
                        $item->token = (new Encrypt())->encriptar($item->id_convocado_reunion);
                        return $item;
                    }),
                ];
            } else {
                $response = [
                    'ok' => false,
                    'response' => 'No hay resultados',
                ];
            }

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }

    }

    /**
     * Función encargada de obtener la programación (orden del día) de una reunión
     * @param $id_reunion => id_reunion
     * @return JSON
     */
    public function getProgramacion($id_reunion, $id_convocado_reunion)
    {
        try {

            $respose = Gcm_Respuesta_Convocado::where('id_convocado_reunion', $id_convocado_reunion)->select();

            //toma todos los datos de programacion sin importar si tiene o no archivos
            $base = Gcm_Programacion::leftJoin('gcm_archivos_programacion', 'gcm_archivos_programacion.id_programa', '=', 'gcm_programacion.id_programa')
                ->leftJoinSub($respose, 'rsc', function ($join) {
                    $join->on('rsc.id_programa', '=', 'gcm_programacion.id_programa');
                })->select(
                'gcm_programacion.*',
                DB::raw('GROUP_CONCAT(gcm_archivos_programacion.descripcion SEPARATOR "|") AS descripciones_archivos'),
                DB::raw('GROUP_CONCAT(gcm_archivos_programacion.peso SEPARATOR "|") AS pesos_archivos'),
                DB::raw('GROUP_CONCAT(gcm_archivos_programacion.url SEPARATOR "|") AS url_archivos'),
                'rsc.descripcion as response')
                ->where([['id_reunion', $id_reunion], ['estado', '!=', '4']])
                ->groupBy('gcm_programacion.id_programa')->get()->toArray();

            $base = array_map(function ($item) {
                $item['archivos'] = [];
                if (!empty($item['descripciones_archivos'])) {
                    $descripcionesArchivo = explode('|', $item['descripciones_archivos']);
                    $pesosArchivo = explode('|', $item['pesos_archivos']);
                    $urlArchivo = explode('|', $item['url_archivos']);

                    for ($i = 0; $i < count($descripcionesArchivo); $i++) {
                        array_push($item['archivos'], [
                            "descripcion" => $descripcionesArchivo[$i],
                            "peso" => $pesosArchivo[$i],
                            "url" => $urlArchivo[$i],
                        ]);
                    }
                }

                unset($item['descripciones_archivos']);
                unset($item['pesos_archivos']);
                unset($item['url_archivos']);

                return $item;
            }, $base);

            $programas = array_filter($base, function ($item) {
                return $item['relacion'] === null || $item['relacion'] === '';
            });

            $programas = array_values($programas);

            $programas = array_map(function ($item) use ($base) {
                $item['opciones'] = array_filter($base, function ($elm) use ($item) {
                    return $elm['relacion'] === $item['id_programa'];
                });
                $item['opciones'] = array_values($item['opciones']);

                return $item;

            }, $programas);

            return response()->json([
                'ok' => ($programas) ? true : false,
                'response' => ($programas) ? $programas : 'No hay resultados',
            ]);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(["error" => $th->getMessage(), "line" => $th->getLine()], 500);
        }
    }

    /**
     * Función encargada de actualizar el estado de un programa
     * @param Request $request => [id_programa, estado]
     * @return JSON
     */
    public function actualizarEstadoPrograma(Request $request)
    {
        try {

            $update = Gcm_Programacion::where('id_programa', $request->id_programa)
                ->update(['estado' => $request->estado]);

            return response()->json(['ok' => ($update) ? true : false]);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }
    }

    /**
     * Función encargada de obtener las respuestas registradas por un convocado
     * @param $id_convocado_reunion => id_convocado_reunion
     * @return JSON
     */
    public function getRespuestasConvocado($id_convocado_reunion)
    {
        try {

            $response = array();

            $base = DB::table('gcm_programacion AS gp')
                ->join('gcm_respuestas_convocados AS grc', 'gp.id_programa', '=', 'grc.id_programa')
                ->join('gcm_convocados_reunion AS gcr', 'gcr.id_reunion', '=', 'gp.id_reunion')
                ->where('grc.id_convocado_reunion', $id_convocado_reunion)
                ->where('gcr.estado', 1)
                ->where('gp.estado', '!=', 4)
                ->groupBy('gp.id_programa')
                ->select(['gp.*', 'grc.id_convocado_reunion', 'grc.descripcion AS descripcion_respuesta'])
                ->get();

            $response = array(
                'ok' => count($base) > 0 ? true : false,
                'response' => count($base) > 0 ? $base : 'No hay resultados',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }
    }

    /**
     * Función encargada de obtener la lista de convocados para una reunión
     * @param $idReunion => id_reunion
     * @return JSON
     */
    public function getListaConvocados($idReunion)
    {
        $response = array();

        try {

            $base = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
                ->join(DB::raw('gcm_relaciones AS grc'), 'gcr.id_relacion', '=', 'grc.id_relacion')
                ->join(DB::raw('gcm_recursos AS grs'), 'grc.id_recurso', '=', 'grs.id_recurso')
                ->join(DB::raw('gcm_roles AS grl'), 'grc.id_rol', '=', 'grl.id_rol')
                ->where(DB::raw('gcr.id_reunion'), $idReunion)
                ->where('gcr.estado', 1)
                ->groupBy('grs.id_recurso')
                ->select([
                    DB::raw('grs.*'),
                    DB::raw('grl.id_rol'),
                    DB::raw('grl.descripcion AS rol'),
                    DB::raw('GROUP_CONCAT(id_convocado_reunion) as convocatoria'),
                    'grc.id_grupo',
                    'gcr.id_convocado_reunion',
                    'gcr.tipo',
                    'gcr.nit',
                    'gcr.razon_social',
                    'gcr.participacion',
                    'gcr.representacion',
                ])->get();

            $response = array(
                'ok' => (count($base) > 0) ? true : false,
                'response' => (count($base) > 0) ? $base : 'No hay resultados',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }
    }

    /**
     * Función encargada de registrar el acceso de un convocado a la reunión
     * @param Request $request [id_convocado_reunion]
     * @return JSON
     */
    public function guardarAccesoReunion(Request $request)
    {
        try {
            if (!isset($request->id_convocado_reunion)) {throw new \Error("guardarAccesoReunion: {id_convocado_reunion} es requerido", 1);}
            $datetime = date('Y-m-d h:i:s');

            $store = DB::statement("INSERT INTO gcm_asistencia_reuniones (id_convocado_reunion, fecha_ingreso, estado) VALUES ($request->id_convocado_reunion, '{$datetime}', 1) ON DUPLICATE KEY UPDATE estado = 1");
            return response()->json(['status' => ($store) ? true : false, 'message' => 'Se ha guardado correctamente']);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['status' => false, 'message' => $th->getMessage() . ' - ' . $th->getLine()], 500);
        }
    }

    /**
     * Función encargada de actualizar el estado de un convocado en la reunión cuando este se sale de la misma
     * @param Request $request [id_convocado_reunion]
     * @return JSON
     */
    public function actualizarAccesoReunion(Request $request)
    {
        try {
            $update = Gcm_Asistencia_Reunion::where('id_convocado_reunion', $request->id_convocado_reunion)
                ->update(['fecha_salida' => date('Y-m-d H:i:s'), 'estado' => 0]);
            return response()->json(['ok' => ($update) ? true : false]);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }
    }

    /**
     * Función encargada de obtener las respuestas registradas para un id_programa
     * @param $id_programa => id_programa
     * @return JSON
     */
    public function getResultadosVotacion($id_programa)
    {
        try {

            $base = Gcm_Respuesta_Convocado::where('id_programa', $id_programa)
                ->get();

            $response = array(
                'ok' => (count($base) > 0) ? true : false,
                'response' => (count($base) > 0) ? $base : 'No hay resultados',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }
    }

    /**
     * Función encargada de obtener las respuestas registradas para una reunión
     * @param $id_reunion => id_reunion
     * @return JSON
     */
    public function getRespuestasReunion($id_reunion)
    {
        try {

            $base = DB::table('gcm_programacion AS gp')
                ->join('gcm_respuestas_convocados AS grc', 'gp.id_programa', '=', 'grc.id_programa')
                ->where('id_reunion', $id_reunion)
                ->select(['grc.*', 'gp.tipo'])
                ->get();

            return response()->json(['status' => true, 'message' => $base], 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['status' => false, 'message' => $th->getMessage() . ' - ' . $th->getLine()], 500);
        }
    }

    /**
     * Función encargada de cancelar/finalizar una reunión
     * @param Request $request => [id_reunion, estado]
     * @return JSON
     */
    public function finalizarReunion(Request $request)
    {
        try {

            $update = Gcm_Reunion::find($request->id_reunion)->update(['estado' => $request->estado]);
            $word = (+$request->estado === 2) ? 'finalizada' : 'cancelada';

            $response = array(
                'ok' => ($update) ? true : false,
                'response' => ($update) ? "Reunión {$word} correctamente" : 'Error actualizando estado',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }
    }

    public function getDataAdmin($token)
    {
        try {
            $data = (new encrypt())->desencriptar($token);
            $data = explode('|', $data);

            if (count($data) !== 2) {return response()->json(['ok' => false, 'response' => 'Token incorrecto', "data" => $data], 200);}

            return ["ok" => true, "response" => ["id_usuario" => $data[0], "id_reunion" => $data[1]]];
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }
    }

    public function answerQuestion(Request $request)
    {
        DB::beginTransaction();
        try {

            if (!isset($request->convocatoria)) {throw new \Error("answerQuestion: {convocatoria} es requerido", 1);}
            if (!isset($request->id_programa)) {throw new \Error("answerQuestion: {id_programa} es requerido", 1);}
            if (!isset($request->response)) {throw new \Error("answerQuestion: {response} es requerido", 1);}

            foreach ($request->convocatoria as $id_convocado_reunion) {
                $summoned = Gcm_Convocado_Reunion::where('id_convocado_reunion', $id_convocado_reunion)->first();

                if ($summoned && +$summoned->tipo !== 1) {
                    $hasAnswered = Gcm_Respuesta_Convocado::where([['id_convocado_reunion', $id_convocado_reunion], ['id_programa', $request->id_programa]])->first();
                    if ($hasAnswered) {throw new \Error("answerQuestion: No es posible responder de nuevo a la misma pregunta", 1);}

                    $response = new Gcm_Respuesta_Convocado();
                    $response->id_convocado_reunion = $id_convocado_reunion;
                    $response->id_programa = $request->id_programa;
                    $response->descripcion = $request->response;

                    $response->save(); # code...
                }

            }
            DB::commit();

            return response()->json(['status' => true, 'message' => 'Guardado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['status' => false, 'message' => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function saveLogout(Request $request)
    {
        try {
            if (!isset($request->id_convocado_reunion)) {throw new \Error("saveLogout: {id_convocado_reunion} es requerido", 1);}
            $datetime = date('Y-m-d h:i:s');

            $summoned = Gcm_Asistencia_Reunion::find($request->id_convocado_reunion);

            if ($summoned) {
                $summoned->estado = 0;
                $summoned->fecha_salida = $datetime;
                $summoned->save();
            }

            return response()->json(['status' => true, 'message' => 'Se ha guardado correctamente']);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['status' => false, 'message' => $th->getMessage() . ' - ' . $th->getLine()], 500);
        }
    }

    public function getAllSummonedList($idReunion)
    {
        $response = array();

        try {

            $base = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
                ->join(DB::raw('gcm_relaciones AS grc'), 'gcr.id_relacion', '=', 'grc.id_relacion')
                ->join(DB::raw('gcm_recursos AS grs'), 'grc.id_recurso', '=', 'grs.id_recurso')
                ->join(DB::raw('gcm_roles AS grl'), 'grc.id_rol', '=', 'grl.id_rol')
                ->where(DB::raw('gcr.id_reunion'), $idReunion)
                ->where('gcr.estado', 1)
                ->select([
                    DB::raw('grs.*'),
                    DB::raw('grl.id_rol'),
                    DB::raw('grl.descripcion AS rol'),
                    'grc.id_grupo',
                    'gcr.id_convocado_reunion',
                    'gcr.tipo',
                    'gcr.nit',
                    'gcr.razon_social',
                    'gcr.participacion',
                    'gcr.representacion',
                ])->get();

            $response = array(
                'ok' => (count($base) > 0) ? true : false,
                'response' => (count($base) > 0) ? $base : 'No hay resultados',
            );

            return response()->json($response, 200);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['ok' => false, 'response' => $th->getMessage()], 500);
        }
    }

}
