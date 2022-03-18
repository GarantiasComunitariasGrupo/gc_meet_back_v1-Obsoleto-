<?php

namespace App\Http\Controllers;

use App\Http\Classes\Encrypt;
use App\Http\Controllers\Gcm_Log_Acciones_Sistema_Controller;
use App\Http\Controllers\Gcm_Mail_Controller;
use App\Mail\GestorCorreos;
use App\Models\Gcm_Archivo_Programacion;
use App\Models\Gcm_Asistencia_Reunion;
use App\Models\Gcm_Convocado_Reunion;
use App\Models\Gcm_Log_Accion_Sistema;
use App\Models\Gcm_Programacion;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Respuesta_Convocado;
use App\Models\Gcm_Restriccion_Rol_Representante;
use App\Models\Gcm_Reunion;
use App\Models\Gcm_Rol;
use App\Models\Gcm_Rol_Acta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

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
                ->select(['gcr.*', 'grcs.*', 'grns.*'])
                ->get();

            /**
             * Si está convocado a reuniones en espera / en curso
             */
            if (count($base) > 0) {

                /**
                 * Se captura correo electrónico del recurso
                 */
                $correo = $base[0]->correo;

                // Aqui realizo un array_map con el objetivo de obtener los id_convocado y añadirlo a un nuevo array junto junto con la url de la pagina que se direcciona
                $arrayUrls = $base->map(function ($row) {
                    $encrypt = new Encrypt();
                    return env('VIEW_BASE') . '/public/acceso-reunion/acceso/' . $encrypt->encriptar($row->id_convocado_reunion);
                });

                /**
                 * Se envía correo electrónico con invitación a las reuniones
                 */
                $send = $mailController->send(
                    'emails.buscar_invitaciones',
                    'Invitaciónes de reuniones en GCMeet',
                    $arrayUrls,
                    $base,
                    $correo,
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
                // }

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
            return response()->json(['ok' => false, 'response' => $th->getMessage().'-'.$th->getLine()], 500);
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

            /** Se consulta los datos de la reunion */
            $meet = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo')
                ->where([['gcm_reuniones.id_reunion', '=', $request->params['id_reunion']], ['gcm_reuniones.estado', '!=', 4]])->firstOrFail();

            /** Se consulta los programas de la reunion */
            $programList = null;

            if (!$programList) {
                $programList = Gcm_Programacion::where([['id_reunion', $request->params['id_reunion']], ['estado', '!=', '4']])
                    ->whereNull('relacion')->orderBy('orden', 'ASC')->get();
            }

            /** Se actualiza número de celular en caso de ser modificado */
            $celular = Gcm_Recurso::where('identificacion', $request->params['identificacion'])
                ->update(['telefono' => $request->params['celular']]);

            DB::commit();

            $imagenes = [
                'https://gc.gcbloomrisk.com/assets/images/test/GCL.jpg',
                'https://gc.gcbloomrisk.com/assets/images/test/GCP.jpg',
                'https://gc.gcbloomrisk.com/assets/images/test/GBR.jpg',
                'https://gc.gcbloomrisk.com/assets/images/test/GM.jpg',
            ];

            $id_grupo = $request->params['id_grupo'];

            if ($id_grupo) {
                $imagen = $imagenes[$id_grupo - 1];
            }

            /** Se encripta id_convocado_reunion */
            $idConvocadoReunion = $encrypt->encriptar($convocado->id_convocado_reunion);

            /** Cuerpo del correo */
            $body = "{$request->params['nombreAnfitrion']} lo ha invitado a usted a que lo represente en una reunión.
                    Link: " . env('VIEW_BASE') . "/public/acceso-reunion/acceso/{$idConvocadoReunion}";

            /** Se envía correo electrónico de invitación al representante */
            // $send = $mailController->send(
            //     'emails.formato-email',
            //     'Invitación de representación - GCMeet',
            //     'Invitación ?',
            //     $body,
            //     $request->params['correo']
            // );

            $data = [
                'view' => 'emails.email_representante',
                'message' => 'Invitación de representación - GCMeet',
                'imagen' => $imagen,
                'nombre' => $request->params['nombre'],
                'nombreAnfitrion' => $request->params['nombreAnfitrion'],
                'titulo' => $meet->titulo,
                'descripcion' => $meet->descripcion,
                'fecha_reunion' => $meet->fecha_reunion,
                'hora' => $meet->hora,
                'programas' => $programList,
                'url' => env('VIEW_BASE') . '/public/acceso-reunion/acceso/' . $idConvocadoReunion,
            ];

            Mail::to($request->params['correo'])->send(new GestorCorreos($data));

            /** Se guarda log de acciones del sistema */
            // $send['correos'] = $request->params['correo'];
            // $send['descripcion'] = 'Correo designación de poder';

            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Correo designación de poder', 'Correos' => $request->params['correo']), null, null);

            $response = array('ok' => true, 'response' => ['recurso' => $recurso, 'convocado' => $convocado, 'mail' => $data]);
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
                ->leftJoin('gcm_roles_actas as gra', 'gcm_programacion.id_rol_acta', '=', 'gra.id_rol_acta')
                ->leftJoinSub($respose, 'rsc', function ($join) {
                    $join->on('rsc.id_programa', '=', 'gcm_programacion.id_programa');
                })->select(
                    'gcm_programacion.*',
                    'gra.descripcion as rol_acta_descripcion',
                    'gra.firma as rol_acta_firma',
                    'gra.acta as rol_acta_acta',
                    DB::raw('GROUP_CONCAT(gcm_archivos_programacion.id_archivo_programacion SEPARATOR "|") AS id_archivo_programacion_archivos'),
                    DB::raw('GROUP_CONCAT(gcm_archivos_programacion.descripcion SEPARATOR "|") AS descripciones_archivos'),
                    DB::raw('GROUP_CONCAT(gcm_archivos_programacion.id_programa SEPARATOR "|") AS id_programas_archivos'),
                    DB::raw('GROUP_CONCAT(gcm_archivos_programacion.peso SEPARATOR "|") AS pesos_archivos'),
                    DB::raw('GROUP_CONCAT(gcm_archivos_programacion.url SEPARATOR "|") AS url_archivos'),
                    'rsc.descripcion as response'
                )
                ->where([['id_reunion', $id_reunion], ['gcm_programacion.estado', '!=', '4']])
                ->groupBy('gcm_programacion.id_programa')->get()->toArray();

            $base = array_map(function ($item) {
                $item['archivos'] = [];
                if (!empty($item['descripciones_archivos'])) {
                    $descripcionesArchivo = explode('|', $item['descripciones_archivos']);
                    $idArchivo = explode('|', $item['id_archivo_programacion_archivos']);
                    $idPrograma = explode('|', $item['id_programas_archivos']);
                    $pesosArchivo = explode('|', $item['pesos_archivos']);
                    $urlArchivo = explode('|', $item['url_archivos']);

                    for ($i = 0; $i < count($descripcionesArchivo); $i++) {
                        array_push($item['archivos'], [
                            "id_archivo_programacion" => $idArchivo[$i],
                            "descripcion" => $descripcionesArchivo[$i],
                            "id_programa" => $idPrograma[$i],
                            "peso" => $pesosArchivo[$i],
                            "url" => $urlArchivo[$i],
                        ]);
                    }
                }

                unset($item['id_archivo_programacion_archivos']);
                unset($item['descripciones_archivos']);
                unset($item['id_programas_archivos']);
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

            return response()->json(['ok' => true, 'response' => $programas]);

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

            $program = Gcm_Programacion::find($request->id_programa);
            $program->estado = $request->estado;
            $update = $program->save();

            if (+$request->estado === 4) { // Si se elimina un programa se actualiza el orden del resto
                $programList = Gcm_Programacion::where([['estado', '!=', '4'], ['id_reunion', $program->id_reunion]]);
                if ($program->relacion) {
                    $programList = $programList->whereNotNull('relacion');
                }
                if (!$program->relacion) {
                    $programList = $programList->whereNull('relacion');
                }
                $programList = $programList->orderBy('orden', 'ASC')->get();

                $programList->each(function ($item, $i) {
                    $item->orden = $i + 1;
                    $item->save();
                });
            }

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
            $datetime = date('Y-m-d H:i:s');

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

            $meet = Gcm_Reunion::find($request->id_reunion);
            $meet->estado = $request->estado;
            if (+$request->estado === 2) { // Si el estado es finalizada se actualiza la fecha de finalización
                $meet->fecha_finalizacion = date('Y-m-d H:i:s');
            }

            $update = $meet->save();

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

            if (count($data) !== 2) {
                return response()->json(['ok' => false, 'response' => 'Token incorrecto', "data" => $data], 200);
            }

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

            if (!isset($request->convocatoria)) {
                throw new \Error("answerQuestion: {convocatoria} es requerido", 1);
            }
            if (!isset($request->id_programa)) {
                throw new \Error("answerQuestion: {id_programa} es requerido", 1);
            }
            if (!isset($request->response)) {
                throw new \Error("answerQuestion: {response} es requerido", 1);
            }

            foreach ($request->convocatoria as $id_convocado_reunion) {
                $summoned = Gcm_Convocado_Reunion::where('id_convocado_reunion', $id_convocado_reunion)->first();

                if ($summoned && +$summoned->tipo !== 1) {
                    $hasAnswered = Gcm_Respuesta_Convocado::where([['id_convocado_reunion', $id_convocado_reunion], ['id_programa', $request->id_programa]])->first();
                    if ($hasAnswered) {
                        throw new \Error("answerQuestion: No es posible responder de nuevo a la misma pregunta", 1);
                    }

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
            $datetime = date('Y-m-d H:i:s');

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

    public function saveProgram(Request $request)
    {

        DB::beginTransaction();
        try {
            // Array de extensiones que se van a permitir en la inserción de archivos de la programación
            $extensiones = array('PNG', 'JPG', 'JPEG', 'GIF', 'XLSX', 'CSV', 'PDF', 'DOCX', 'TXT', 'PPTX', 'SVG', 'PDF');

            $validator = Validator::make($request->all(), [
                'id_reunion' => 'required',
                'titulo' => 'required|max:500',
                'descripcion' => 'max:500',
            ], [
                'id_reunion.required' => '*Rellena este campo',
                'titulo.required' => '*Rellena este campo',
                'titulo.max' => '*Máximo 500 caracteres',
                'descripcion.max' => '*Maximo 500 caracteres',
            ]);

            if ($validator->fails()) {
                DB::rollback();
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1212), null);
                return response()->json($validator->errors(), 422);
            }

            if (!$request->id_programa) {
                $this->liberarOrden($request->id_reunion, $request->orden);
            }

            $id_rol_acta = null;
            if ($request->id_acta != null && isset($request->rol_acta_descripcion)) {
                $rol_acta_existe = Gcm_Rol_Acta::where([['id_acta', $request->id_acta], ['descripcion', $request->rol_acta_descripcion]])->first();
                $rol_acta = !$rol_acta_existe ? new Gcm_Rol_Acta() : Gcm_Rol_Acta::findOrFail($rol_acta_existe->id_rol_acta);

                $rol_acta->descripcion = $request->rol_acta_descripcion;
                $rol_acta->firma = $request->rol_acta_firma;
                $rol_acta->acta = $request->rol_acta_acta;
                $rol_acta->id_acta = $request->id_acta;
                $rol_acta->estado = 1;
                $rol_acta->save();
                $id_rol_acta = $rol_acta->id_rol_acta;
            }

            // Registra la programación de una reunion
            $programa_nuevo = new Gcm_Programacion;
            if ($this->stringNullToNull($request->id_programa) !== null) {
                $programa_nuevo = Gcm_Programacion::find($request->id_programa);
                if (!$programa_nuevo) {
                    throw new \Error("El programa no existe", 1);
                }
            }
            $programa_nuevo->descripcion = $this->stringNullToNull($request->descripcion);
            $programa_nuevo->estado = $request->estado ? $request->estado : 0;
            $programa_nuevo->titulo = $this->stringNullToNull($request->titulo);
            $programa_nuevo->tipo = $this->stringNullToNull($request->tipo);
            $programa_nuevo->id_reunion = $this->stringNullToNull($request->id_reunion);
            $programa_nuevo->id_rol_acta = $id_rol_acta;
            $programa_nuevo->relacion = null;
            $programa_nuevo->orden = $request->orden;
            $programa_nuevo->numeracion = 0;

            $programa_nuevo->save();

            $picture = 0;

            $fileList = isset($request['file_viejo']) ? array_map(function ($data) {
                return json_decode($data)->id_archivo_programacion;
            }, $request['file_viejo']) : [];

            $removedFileList = Gcm_Archivo_Programacion::where('id_programa', $programa_nuevo->id_programa)
                ->whereNotIn('id_archivo_programacion', $fileList)->get();

            $removedFileList->each(function ($fileToRemove) {
                $fileToRemove->delete();
            });

            if ($request->hasFile('file')) {
                $request['file'] = array_values($request['file']);

                $subcarpeta = 'archivos_reunion/' . $request->id_reunion;
                $carpeta = 'storage/app/public/' . $subcarpeta;

                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0777, true);
                    chmod($carpeta, 0777);
                }

                for ($j = 0; $j < count($request['file']); $j++) {

                    $archivo_nuevo = new Gcm_Archivo_Programacion();
                    $file = $request['file'][$j];
                    $extension = $file->getClientOriginalExtension();

                    if (in_array(strtoupper($extension), $extensiones)) {
                        $archivo_nuevo->id_programa = $programa_nuevo->id_programa;
                        $archivo_nuevo->descripcion = $file->getClientOriginalName();
                        $archivo_nuevo->peso = filesize($file);
                        $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $extension;
                        $archivo_nuevo->url = $subcarpeta . '/' . $picture;
                        $file->move(storage_path('app/public/archivos_reunion/' . $request->id_reunion), $picture);
                        chmod(storage_path('app/public/archivos_reunion/' . $request->id_reunion . '/' . $picture), 0777);

                        $archivo_nuevo->save();
                    } else {
                        DB::rollback();
                        Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'La extensión del archivo no es permitida'), null);
                        return response()->json(['error' => 'La extensión del archivo no es permitida'], 500);
                    }
                }
            }

            if (isset($request['file_viejo'])) {
                $request['file_viejo'] = array_values($request['file_viejo']);
                for ($j = 0; $j < count($request['file_viejo']); $j++) {
                    $file = json_decode($request['file_viejo'][$j]);

                    $archivo_nuevo = new Gcm_Archivo_Programacion;
                    if ($this->stringNullToNull($file->id_archivo_programacion) !== null) {
                        $archivo_nuevo = Gcm_Archivo_Programacion::find($file->id_archivo_programacion);
                        if (!$archivo_nuevo) {
                            throw new \Error("El archivo no existe", 1);
                        }
                    }

                    $archivo_nuevo->id_programa = $programa_nuevo->id_programa;
                    $archivo_nuevo->descripcion = $file->name;
                    $archivo_nuevo->peso = $file->size;
                    $archivo_nuevo->url = $file->url;
                    $archivo_nuevo->save();
                }
            }

            $optionList = isset($request['opcion_id_programa']) ? array_values(
                array_filter($request['opcion_id_programa'], function ($data) {
                    return $this->stringNullToNull($data) !== null;
                })
            ) : [];

            $removedOptionList = Gcm_Programacion::where('relacion', $programa_nuevo->id_programa)
                ->whereNotIn('id_programa', $optionList)->get();

            $removedOptionList->each(function ($optionToRemove) {
                $optionToRemove->delete();
            });

            // Valida que si vengan opciones para registrar
            if (isset($request['opcion_titulo'])) {

                for ($j = 0; $j < count($request['opcion_titulo']); $j++) {

                    // Registra las opciones
                    $opcion_nueva = new Gcm_Programacion;
                    if ($this->stringNullToNull($request['opcion_id_programa'][$j]) !== null) {
                        $opcion_nueva = Gcm_Programacion::find($request['opcion_id_programa'][$j]);
                        if (!$opcion_nueva) {
                            throw new \Error("La opción no existe", 1);
                        }
                    }

                    $opcion_nueva->estado = $request['opcion_estado'][$j] ? $request['opcion_estado'][$j] : 0;
                    $opcion_nueva->descripcion = $this->stringNullToNull($request['opcion_descripcion'][$j]);
                    $opcion_nueva->titulo = $this->stringNullToNull($request['opcion_titulo'][$j]);
                    $opcion_nueva->id_reunion = $this->stringNullToNull($request->id_reunion);
                    $opcion_nueva->relacion = $this->stringNullToNull($programa_nuevo->id_programa);
                    $opcion_nueva->id_convocado_reunion = null;
                    $opcion_nueva->id_rol_acta = null;
                    $opcion_nueva->orden = $j + 1;
                    $opcion_nueva->numeracion = 1;
                    $opcion_nueva->tipo = 0;

                    $opcion_nueva->save();

                    $picture = 0;

                    $optionFileList = isset($request['opcion_file_viejo_' . $j]) ? array_map(function ($data) {
                        return json_decode($data)->id_archivo_programacion;
                    }, $request['opcion_file_viejo_' . $j]) : [];

                    $removedOptionFileList = Gcm_Archivo_Programacion::where('id_programa', $opcion_nueva->id_programa)
                        ->whereNotIn('id_archivo_programacion', $optionFileList)->get();

                    $removedOptionFileList->each(function ($optionFileToRemove) {
                        $optionFileToRemove->delete();
                    });

                    if ($request->hasFile('opcion_file_' . $j)) {
                        $request['opcion_file_' . $j] = array_values($request['opcion_file_' . $j]);

                        $subcarpeta = 'archivos_reunion/' . $programa_nuevo->id_reunion;
                        $carpeta = 'storage/app/public/' . $subcarpeta;

                        if (!file_exists($carpeta)) {
                            mkdir($carpeta, 0777, true);
                            chmod($carpeta, 0777);
                        }

                        for ($k = 0; $k < count($request['opcion_file_' . $j]); $k++) {

                            $archivo_opcion_nuevo = new Gcm_Archivo_Programacion;
                            $opcion_file = $request['opcion_file_' . $j][$k];
                            $opcion_extension = $opcion_file->getClientOriginalExtension();

                            if (in_array(strtoupper($opcion_extension), $extensiones)) {
                                $archivo_opcion_nuevo->id_programa = $opcion_nueva->id_programa;
                                $archivo_opcion_nuevo->descripcion = $opcion_file->getClientOriginalName();
                                $archivo_opcion_nuevo->peso = filesize($opcion_file);
                                $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $opcion_extension;
                                $archivo_opcion_nuevo->url = $subcarpeta . '/' . $picture;
                                $opcion_file->move(storage_path('app/public/archivos_reunion/' . $programa_nuevo->id_reunion), $picture);
                                chmod(storage_path('app/public/archivos_reunion/' . $programa_nuevo->id_reunion . '/' . $picture), 0777);

                                $archivo_opcion_nuevo->save();
                            } else {
                                DB::rollback();
                                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'La extensión del archivo no es permitida'), null);
                                return response()->json(['error' => 'La extensión del archivo no es permitida'], 500);
                            }
                        }
                    }

                    if (isset($request['opcion_file_viejo_' . $j])) {
                        $request['opcion_file_viejo_' . $j] = array_values($request['opcion_file_viejo_' . $j]);
                        for ($k = 0; $k < count($request['opcion_file_viejo_' . $j]); $k++) {
                            $opcion_file = json_decode($request['opcion_file_viejo_' . $j][$k]);

                            $archivo_opcion_nuevo = new Gcm_Archivo_Programacion;
                            if ($this->stringNullToNull($opcion_file->id_archivo_programacion) !== null) {
                                $archivo_opcion_nuevo = Gcm_Archivo_Programacion::find($opcion_file->id_archivo_programacion);
                                if (!$archivo_opcion_nuevo) {
                                    throw new \Error("El archivo de la opción no existe", 1);
                                }
                            }

                            $archivo_opcion_nuevo->id_programa = $opcion_nueva->id_programa;
                            $archivo_opcion_nuevo->descripcion = $opcion_file->name;
                            $archivo_opcion_nuevo->peso = $opcion_file->size;
                            $archivo_opcion_nuevo->url = $opcion_file->url;
                            $archivo_opcion_nuevo->save();
                        }
                    }
                }
            }

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Programa agregado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['status' => false, 'message' => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function liberarOrden($id_reunion, $ordenTarget)
    {
        try {
            $program = Gcm_Programacion::where([['estado', '!=', 4], ['orden', $ordenTarget], ['id_reunion', $id_reunion]])
                ->whereNull('relacion')->first();
            if ($program) {
                $this->liberarOrden($id_reunion, $ordenTarget + 1);
                $program->orden = $ordenTarget + 1;
                $program->save();
            }
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['status' => false, 'message' => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    /**
     * Valida si un valor es 'null' o 'undefined' y lo convierte a null, de lo contrario devuelve el valor original
     *
     * @param [type] $val Valor a revisar
     * @return void Valor null o el original
     */
    public function stringNullToNull($val)
    {
        return in_array($val, ['null', 'undefined']) ? null : $val;
    }

    public function summon($id_grupo, $id_reunion, Request $request)
    {
        DB::beginTransaction();
        try {

            $validator = Validator::make($request->all(), [
                'identificacion' => 'required|max:20',
                'nombre' => 'required|max:255',
                'telefono' => 'max:20',
                'correo' => 'required|max:255|email',
            ], [
                'identificacion.required' => '*Rellena este campo',
                'identificacion.max' => '*Maximo 20 caracteres',
                'nombre.required' => '*Rellena este campo',
                'nombre.max' => '*Maximo 255 caracteres',
                'telefono.max' => '*Maximo 20 caracteres',
                'correo.required' => '*Rellena este campo',
                'correo.max' => '*Máximo 255 caracteres',
                'correo.email' => '*Formato de correo invalido',
            ]);

            if ($validator->fails()) {
                DB::rollback();
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1517), null);
                return response()->json($validator->errors(), 422);
            }

            // Consulta si el convocado ya fue registrado como recurso
            $recurso_existe = DB::table('gcm_recursos')->where('identificacion', '=', $request->identificacion)->first();

            $recurso_existe = !$recurso_existe ? new Gcm_Recurso() : Gcm_Recurso::findOrFail($recurso_existe->id_recurso);

            $recurso_existe->identificacion = $request->identificacion;
            $recurso_existe->nombre = $request->nombre;
            if (!empty($request->telefono)) {
                $recurso_existe->telefono = $request->telefono;
            }
            $recurso_existe->correo = $request->correo;
            $recurso_existe->estado = 1;

            $recurso_existe->save();

            // Consulta si el rol ya esta registrado
            $validator = Validator::make($request->all(), [
                'rol' => 'required|max:100',
            ], [
                'rol.required' => '*Rellena este campo',
                'rol.max' => '*Maximo 100 caracteres',
            ]);

            if ($validator->fails()) {
                DB::rollback();
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1544), null);
                return response()->json($validator->errors(), 422);
            }

            $rol_existe = DB::table('gcm_roles')->where('descripcion', '=', $request->rol)->first();

            $rol_existe = !$rol_existe ? new Gcm_Rol() : Gcm_Rol::findOrFail($rol_existe->id_rol);
            $rol_existe->descripcion = $request->rol;
            $rol_existe->relacion = null;
            $rol_existe->estado = 1;
            $rol_existe->save();

            // Consulta si ya existe una relacion registrada con los datos subministrados(id_grupo, id_rol, id_recurso)
            $relacion_existe = DB::table('gcm_relaciones')->where([['id_grupo', '=', $id_grupo], ['id_rol', '=', $rol_existe->id_rol], ['id_recurso', '=', $recurso_existe->id_recurso]])->first();

            // Registra la relación nueva
            $relacion_nueva = !$relacion_existe ? new Gcm_Relacion() : Gcm_Relacion::findOrFail($relacion_existe->id_relacion);
            $relacion_nueva->id_grupo = $id_grupo;
            $relacion_nueva->id_rol = $rol_existe->id_rol;
            $relacion_nueva->id_recurso = $recurso_existe->id_recurso;
            $relacion_nueva->estado = 1;

            $relacion_nueva->save();

            // Registra el convocado con nit y razon social
            $validator;
            if ($request->tipo == '2') {
                $validator = Validator::make($request->all(), [
                    'tipo' => 'required|max:2',
                    'nit' => 'max:20',
                    'razon_social' => 'max:100',
                ], [
                    'tipo.required' => '*Rellena este campo',
                    'tipo.max' => '*Maximo 2 caracteres',
                    'nit.max' => '*Maximo 20 caracteres',
                    'razon_social.max' => '*Máximo 100 caracteres',
                ]);
            } else {
                // Registra el convocado sin nit ni razon social
                $validator = Validator::make($request->all(), [
                    'tipo' => 'required|max:2',
                ], [
                    'tipo.required' => '*Rellena este campo',
                    'tipo.max' => '*Maximo 2 caracteres',
                ]);
            }

            if ($validator->fails()) {
                DB::rollback();
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1593), null);
                return response()->json($validator->errors(), 200);
            }

            $convocado = new Gcm_Convocado_Reunion;
            $convocado->id_reunion = $id_reunion;
            $convocado->representacion = null;
            $convocado->id_relacion = $relacion_nueva->id_relacion;
            $convocado->tipo = $request->tipo;
            $convocado->nit = $request->tipo == '2' ? $request->nit : null;
            $convocado->razon_social = $request->tipo == '2' ? $request->razon_social : null;
            $convocado->participacion = ($request->tipo == '2' || $request->tipo == 0) ? (isset($request->participacion) ? $request->participacion : null) : null;
            $convocado->soporte = null;
            $convocado->fecha_envio_invitacion = null;
            $convocado->firma = $request->firma;
            $convocado->acta = $request->acta;
            $convocado->estado = 1;

            $convocado->save();

            DB::commit();
            return response()->json(['status' => true, 'message' => $convocado->id_convocado_reunion]);
        } catch (\Throwable $th) {
            DB::rollback();
            Gcm_Log_Acciones_Sistema_Controller::save(7, ['error' => $th->getMessage(), 'linea' => $th->getLine()], null, null);
            return response()->json(['status' => false, 'message' => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function getProgramas($id_reunion) {
        $base = Gcm_Programacion::leftJoin('gcm_archivos_programacion', 'gcm_archivos_programacion.id_programa', '=', 'gcm_programacion.id_programa')
        ->leftJoin('gcm_roles_actas as gra', 'gcm_programacion.id_rol_acta', '=', 'gra.id_rol_acta')
        ->select(
            'gcm_programacion.*',
            'gra.descripcion as rol_acta_descripcion',
            'gra.firma as rol_acta_firma',
            'gra.acta as rol_acta_acta',
            DB::raw('GROUP_CONCAT(gcm_archivos_programacion.id_archivo_programacion SEPARATOR "|") AS id_archivo_programacion_archivos'),
            DB::raw('GROUP_CONCAT(gcm_archivos_programacion.descripcion SEPARATOR "|") AS descripciones_archivos'),
            DB::raw('GROUP_CONCAT(gcm_archivos_programacion.id_programa SEPARATOR "|") AS id_programas_archivos'),
            DB::raw('GROUP_CONCAT(gcm_archivos_programacion.peso SEPARATOR "|") AS pesos_archivos'),
            DB::raw('GROUP_CONCAT(gcm_archivos_programacion.url SEPARATOR "|") AS url_archivos')
        )->where([['gcm_programacion.id_reunion', $id_reunion], ['gcm_programacion.estado', '!=', '4']])->groupBy('gcm_programacion.id_programa')->get()->toArray();

    $base = array_map(function ($item) {
        $item['archivos'] = [];
        if (!empty($item['descripciones_archivos'])) {
            $descripcionesArchivo = explode('|', $item['descripciones_archivos']);
            $idArchivo = explode('|', $item['id_archivo_programacion_archivos']);
            $idPrograma = explode('|', $item['id_programas_archivos']);
            $pesosArchivo = explode('|', $item['pesos_archivos']);
            $urlArchivo = explode('|', $item['url_archivos']);

            for ($i = 0; $i < count($descripcionesArchivo); $i++) {
                array_push($item['archivos'], [
                    "id_archivo_programacion" => $idArchivo[$i],
                    "descripcion" => $descripcionesArchivo[$i],
                    "id_programa" => $idPrograma[$i],
                    "peso" => $pesosArchivo[$i],
                    "url" => $urlArchivo[$i],
                ]);
            }
        }

        unset($item['id_archivo_programacion_archivos']);
        unset($item['descripciones_archivos']);
        unset($item['id_programas_archivos']);
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

    return $programas;
    }

    /**
     * Realizo el envio de un correo electronico a los convocados de una reunion
     *
     * @param Request Aqui toda la información de la reunion, de los convocados y los programas, pero solo se toma los convocados
     * @return void Retorna un mensaje donde se evidencia si el envio de los correos fue exitoso o fallo
     */
    public function sendMailToSummon(Request $request)
    {
        try {

            if (!isset($request->summonedList)) {
                throw new \Error("sendMailToSummon: {summonedList} es requerido", 1);
            }

            $encrypt = new Encrypt();
            $informacion = json_decode($request->summonedList, true);
            $summonedList = $informacion['ids'];


            $programList = null;




            $meet = null;
            $mailList = [];

            $imagenes = [
                'https://gc.gcbloomrisk.com/assets/images/test/GCL.jpg',
                'https://gc.gcbloomrisk.com/assets/images/test/GCP.jpg',
                'https://gc.gcbloomrisk.com/assets/images/test/GBR.jpg',
                'https://gc.gcbloomrisk.com/assets/images/test/GM.jpg',
            ];

            $id_grupo = $informacion['id_grupo'];
            $titulo = $informacion['titulo'];

            if ($id_grupo) {
                $imagen = $imagenes[$id_grupo - 1];
            }

            foreach ($summonedList as $id_convocado_reunion) {
                $summoned = Gcm_Convocado_Reunion::findOrFail($id_convocado_reunion);
                $summoned->fecha_envio_invitacion = date('Y-m-d H:i:s');
                $summoned->save();

                $resource = Gcm_Convocado_Reunion::join('gcm_relaciones AS rlc', 'gcm_convocados_reunion.id_relacion', '=', 'rlc.id_relacion')
                    ->join('gcm_recursos AS rcs', 'rlc.id_recurso', '=', 'rcs.id_recurso')->where('id_convocado_reunion', $id_convocado_reunion)->first();

                if (!$resource) {
                    throw new \Error("Recurso no encontrado", 1);
                }

                if (!$programList) {
                    // $programList = Gcm_Programacion::where([['id_reunion', $resource->id_reunion], ['estado', '!=', '4']])
                    //     ->whereNull('relacion')->orderBy('orden', 'ASC')->get();
                    $programList = $this->getProgramas($resource->id_reunion);
                }

                if (!$meet) {
                    $meet = Gcm_Reunion::where('id_reunion', $resource->id_reunion)->first();
                }

                $token = $encrypt->encriptar($id_convocado_reunion);

                $data = [
                    'view' => 'emails.invitacion',
                    'message' => 'Convocado a reunión en plataforma de juntas y asambleas',
                    'imagen' => $imagen,
                    'nombre' => $resource->nombre,
                    'titulo' => $titulo,
                    'descripcion' => $meet->descripcion,
                    'fecha_reunion' => $meet->fecha_reunion,
                    'hora' => $meet->hora,
                    'programas' => $programList,
                    // 'url' => env('VIEW_BASE') . '/public/acceso-reunion/acceso/' . $token,
                    'url' => 'https://gcmeet.garantiascomunitarias.com' . '/public/reunion/acceso/' . $token,
                ];

                array_push($mailList, $resource->correo);
                Mail::to($resource->correo)->send(new GestorCorreos($data));
            }

            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Envio correo reunión en curso', 'Correos' => $mailList), null);
            return response()->json(["status" => true, "message" => 'Correo enviado correctamente'], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["status" => false, "message" => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function checkElection(Request $request)
    {
        try {
            if (!isset($request->id_reunion)) {throw new \Error("checkElection: El campo {id_reunion} es requerido", 1);}
            $meet = Gcm_Reunion::find($request->id_reunion);
            if (!isset($request->id_reunion)) {throw new \Error("checkElection: La reunión no ha sido encontrada", 1);}
            if ($meet->id_acta == null) {return response()->json(["status" => true, "message" => (object) []], 200);}

            $programList = Gcm_Programacion::join('gcm_roles_actas AS ra', 'gcm_programacion.id_rol_acta', 'ra.id_rol_acta')
                ->select('gcm_programacion.*', 'ra.descripcion AS rol_acta')->whereNull(['relacion', 'id_convocado_reunion'])
                ->where([['id_reunion', $meet->id_reunion], ['tipo', '5']])
                ->whereNotIn('gcm_programacion.estado', [3, 4])
                ->get();
            $programIdList = $programList->map(function ($item) {return $item->id_programa;});

            $optionList = Gcm_Programacion::whereIn('relacion', $programIdList)->get();
            $answerList = Gcm_Respuesta_Convocado::whereIn('id_programa', $programIdList)->get();

            $election = (object) [];
            $programList->each(function ($program) use ($optionList, $answerList, &$election) {
                $programOptionList = $optionList->filter(function ($item) use ($program) {return $item->relacion == $program->id_programa;})->values();

                $programAnswerList = $answerList->filter(function ($item) use ($program) {return $item->id_programa == $program->id_programa;})->values()
                    ->map(function ($item) {return json_decode($item->descripcion)->seleccion[0];});

                $max = 0;
                $programOptionList = $programOptionList->map(function ($option) use ($programAnswerList, &$max) {
                    $option->votes = count($programAnswerList->filter(function ($answer) use ($option) {return $answer == $option->id_programa;})->values());
                    if ($option->votes > $max) {$max = $option->votes;}
                    return $option;
                });
                $election->{$program->id_rol_acta} = [
                    "elected" => $programOptionList->filter(function ($option) use ($max) {return $option->votes == $max;})->values(),
                    "description" => $program->rol_acta,
                ];
            });
            return response()->json(["status" => true, "message" => $election], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["status" => false, "message" => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function saveElection(Request $request)
    {
        try {
            if (!isset($request->data)) {throw new \Error("saveElection: El campo {data} es requerido", 1);}
            foreach ($request->data as $key => $value) {
                $option = $value['elected'][0];
                $program = Gcm_Programacion::find($option['relacion']);
                if (!$program) {throw new \Error("saveElection: No se ha encontrado el programa", 1);}
                $summoned = Gcm_Recurso::join('gcm_relaciones AS rlc', 'gcm_recursos.id_recurso', 'rlc.id_recurso')
                    ->join('gcm_convocados_reunion AS crn', 'crn.id_relacion', 'rlc.id_relacion')
                    ->where([['identificacion', $option['titulo']], ['crn.id_reunion', $program->id_reunion], ['crn.tipo', '!=', '1'], ['crn.estado', '1']])
                    ->orderBy('crn.representacion', 'ASC')->first();
                if (!$summoned) {throw new \Error("saveElection: No se ha encontrado el convocado", 1);}
                $program->id_convocado_reunion = $summoned->id_convocado_reunion;
                $program->save();
            }
            return response()->json(["status" => true, "message" => 'Elecciones guardadas correctamente'], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["status" => false, "message" => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function checkFirmaActa(Request $request)
    {
        try {
            if (!isset($request->id_reunion)) {throw new \Error("checkFirmaActa: El campo {id_reunion} es requerido", 1);}
            $meet = Gcm_Reunion::find($request->id_reunion);
            if (!isset($request->id_reunion)) {throw new \Error("checkFirmaActa: La reunión no ha sido encontrada", 1);}
            if ($meet->id_acta == null) {return response()->json(["status" => true, "message" => ['acta' => false]], 200);}

            $summonedList = Gcm_Convocado_Reunion::join('gcm_relaciones AS rlc', 'rlc.id_relacion', 'gcm_convocados_reunion.id_relacion')
                ->join('gcm_recursos AS rcs', 'rcs.id_recurso', 'rlc.id_recurso')->where([['gcm_convocados_reunion.estado', '1'], ['id_reunion', $meet->id_reunion]])
                ->select('id_convocado_reunion', 'rcs.identificacion', 'rcs.nombre', 'rcs.correo', 'nit', 'razon_social', 'participacion', 'firma', 'acta')->get();

            $signList = $summonedList->filter(function ($item) {return $item->firma == '1';})->values()->toArray();

            $programList = Gcm_Programacion::join('gcm_roles_actas AS ra', 'gcm_programacion.id_rol_acta', 'ra.id_rol_acta')
                ->join('gcm_convocados_reunion AS cr', 'cr.id_convocado_reunion', 'gcm_programacion.id_convocado_reunion')
                ->where([['gcm_programacion.id_reunion', $meet->id_reunion], ['gcm_programacion.tipo', '5'], ['ra.firma', '1']])
                ->whereNull('gcm_programacion.relacion')->whereNotNull('gcm_programacion.id_convocado_reunion')
                ->whereNotIn('gcm_programacion.estado', [3, 4])
                ->select('cr.*')->get();

            $programList->each(function ($summoned) use (&$signList) {array_push($signList, $summoned);});

            return response()->json(["status" => true, "message" => ['acta' => true, 'signList' => $signList]], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["status" => false, "message" => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function getNumeroActa($id_tipo_reunion)
    {
        try {
            if (!isset($id_tipo_reunion)) {throw new \Error("getNumeroActa: El campo {id_tipo_reunion} es requerido", 1);}

            $actas = Gcm_Reunion::where([['id_tipo_reunion', $id_tipo_reunion]])->whereNotNull('acta')->get();

            return response()->json(["status" => true, "message" => count($actas) + 1], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["status" => false, "message" => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function getAnnouncementDate($id_reunion)
    {
        try {
            if (!isset($id_reunion)) {throw new \Error("getAnnouncementDate: El campo {id_reunion} es requerido", 1);}

            $firstSummoned = Gcm_Convocado_Reunion::where([['id_reunion', $id_reunion], ['estado', '1']])
                ->whereNotNull('fecha_envio_invitacion')->orderBy('fecha_envio_invitacion')->first();

            return response()->json(["status" => true, "message" => $firstSummoned ? $firstSummoned->fecha_envio_invitacion : date('Y-m-d H:i:s')], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["status" => false, "message" => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

    public function downloadActa(Request $request)
    {
        try {
            // Configurar un nombre de archivo
            $documentFileName = "acta.pdf";

            $header = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $documentFileName . '"',
            ];

            // # Ingresa al directorio fuente
            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            #Tomamos el array donde están todas las fuentes
            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            #Creamos el PDF con las mediadas y orientacion
            $document = new Mpdf([
                'mode' => 'utf-8',
                'format' => [279, 216],
                #Asignamos la orientacion de las paginas
                'orientation' => 'L',
                'margin_bottom' => 0,
                'margin_right' => 0,
                'margin_left' => 0,
                'margin_top' => 0,
                # Se toma la ruta de donde estan ubicadas las nuevas fuentes
                'fontDir' => array_merge($fontDirs, [
                    storage_path('app/public/fonts'),
                ]),
                # A las fuentes que ya tenemos adicione las nuevas
                'fontdata' => $fontData + [
                    "montserratblack" => [
                        'R' => "Montserrat-Black.ttf",
                    ],
                    "montserratbold" => [
                        'R' => "Montserrat-Bold.ttf",
                    ],
                    "montserratextrabold" => [
                        'R' => "Montserrat-ExtraBold.ttf",
                    ],
                    "montserratextralight" => [
                        'R' => "Montserrat-ExtraLight.ttf",
                    ],
                    "montserratlight" => [
                        'R' => "Montserrat-Light.ttf",
                    ],
                    "montserratmedium" => [
                        'R' => "Montserrat-Medium.ttf",
                    ],
                    "montserratregular" => [
                        'R' => "Montserrat-Regular.ttf",
                    ],
                    "montserratsemibold" => [
                        'R' => "Montserrat-SemiBold.ttf",
                    ],
                    "montserratthin" => [
                        'R' => "Montserrat-Thin.ttf",
                    ],
                ],

                # Fuente por defecto que tendra el PDF
                'default_font' => 'montserratmedium',

            ]);

            $document->WriteHTML($request->style);

            $document->WriteHTML(str_replace("\n", "<br/>", $request->headerContent));

            $document->AddPageByArray([
                'margin-left' => 16,
                'margin-right' => 16,
                'margin-top' => 16,
                'margin-bottom' => 16,
            ]);

            $document->WriteHTML(str_replace("\n", "<br/>", $request->pageContent));

            if ($request->action === 'save') {
                $subcarpeta = 'archivos_reunion/' . $request->id_reunion;
                $carpeta = 'app/public/' . $subcarpeta;

                if (!file_exists(storage_path($carpeta))) {
                    mkdir(storage_path($carpeta), 0777, true);
                }

                $meet = Gcm_Reunion::find($request->id_reunion);

                $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.pdf';
                $meet->acta = $subcarpeta . '/' . $picture;

                $document->Output(storage_path($carpeta . '/' . $picture), 'F');
                chmod(storage_path($carpeta . '/' . $picture), 0777);

                $meet->save();
            }

            // Guarde PDF en su almacenamiento público
            Storage::put($documentFileName, $document->Output($documentFileName, "S"));

            // Recupere el archivo del almacenamiento con la información del encabezado de dar
            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Descarga del acta de una reunion'), null);
            return Storage::download($documentFileName, 'Request', $header);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return $th;
            return response()->json(["error" => $th->getMessage() . ' - ' . $th->getLine()], 500);
        }
    }

    public function getSummonedLoggedinList($id_reunion)
    {
        try {
            if (!isset($id_reunion)) {throw new \Error("getSummonedLoggedinList: El campo {id_reunion} es requerido", 1);}

            $resource = Gcm_Convocado_Reunion::join('gcm_relaciones AS rlc', 'rlc.id_relacion', 'gcm_convocados_reunion.id_relacion')
                ->join('gcm_recursos AS rcs', 'rcs.id_recurso', 'rlc.id_recurso')
                ->join('gcm_asistencia_reuniones AS arn', 'arn.id_convocado_reunion', 'gcm_convocados_reunion.id_convocado_reunion')
                ->where([['gcm_convocados_reunion.id_reunion', $id_reunion], ['gcm_convocados_reunion.estado', '1']]);

            $summonedList = $resource->leftJoinSub($resource->select([
                'gcm_convocados_reunion.id_convocado_reunion', 'nit', 'tipo', 'razon_social',
                'participacion', 'rcs.identificacion', 'rcs.nombre', 'rcs.correo',
            ]), 'crn', function ($join) {
                $join->on('crn.id_convocado_reunion', 'gcm_convocados_reunion.representacion');
            })->select([
                DB::raw('IFNULL(crn.id_convocado_reunion, gcm_convocados_reunion.id_convocado_reunion) AS id_convocado_reunion'),
                DB::raw('IFNULL(crn.tipo, gcm_convocados_reunion.tipo) AS tipo'),
                DB::raw('IFNULL(crn.nit, gcm_convocados_reunion.nit) AS nit'),
                DB::raw('IFNULL(crn.razon_social, gcm_convocados_reunion.razon_social) AS razon_social'),
                DB::raw('IFNULL(crn.participacion, gcm_convocados_reunion.participacion) AS participacion'),
                DB::raw('IFNULL(crn.identificacion, rcs.identificacion) AS identificacion'),
                DB::raw('IFNULL(crn.nombre, rcs.nombre) AS nombre'),
                DB::raw('IFNULL(crn.correo, rcs.correo) AS correo'),
                DB::raw('IF(crn.id_convocado_reunion IS NULL, null, gcm_convocados_reunion.id_relacion) AS id_representante'),
                DB::raw('IF(crn.identificacion IS NULL, null, crn.identificacion) AS identificacion_representante'),
                DB::raw('IF(crn.nombre IS NULL, null, crn.nombre) AS nombre_representante'),
            ])->groupBy('id_convocado_reunion')->get();

            return response()->json(["status" => true, "message" => $summonedList], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["status" => false, "message" => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }
}
