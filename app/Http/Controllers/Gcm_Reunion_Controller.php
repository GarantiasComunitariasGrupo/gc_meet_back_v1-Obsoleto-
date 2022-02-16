<?php

namespace App\Http\Controllers;

use App;
use App\Http\Classes\Encrypt;
use App\Mail\ReunionCancelada;
use App\Mail\ReunionReprogramada;
use App\Mail\TestMail;
use App\Models\Gcm_Acta;
use App\Models\Gcm_Archivo_Programacion;
use App\Models\Gcm_Convocado_Reunion;
use App\Models\Gcm_Grupo;
use App\Models\Gcm_Programacion;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Reunion;
use App\Models\Gcm_Rol;
use App\Models\Gcm_Rol_Acta;
use App\Models\Gcm_Tipo_Reunion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Tymon\JWTAuth\Facades\JWTAuth;

class Gcm_Reunion_Controller extends Controller
{

    // MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS

    /**
     * Trae todos los grupos registrados por un usuario con un estado en comun
     */
    public function getGrupos()
    {
        try {
            $grupos = Gcm_Grupo::where('estado', '1')->get();
            return response()->json($grupos);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae los datos de un grupo registrado con un estado en comun
     */
    public function getGrupo($id_grupo)
    {
        try {
            $grupos = Gcm_Grupo::where('id_grupo', $id_grupo)->get();
            return response()->json($grupos);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Consulta todas las reuniones con un tipo de reunion que tiene un grupo en comun
     */
    public function getReuniones($id_grupo)
    {
        try {
            $reuniones = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo')
                ->where([['gcm_tipo_reuniones.id_grupo', '=', $id_grupo], ['gcm_reuniones.estado', '!=', '4']])
                ->orderBy('gcm_reuniones.estado', 'asc')
                ->orderBy('gcm_reuniones.fecha_actualizacion', 'desc')
                ->get();

            $reuniones->map(function ($item) {
                $item->token = (new Encrypt())->encriptar(JWTAuth::user()->id_usuario . '|' . $item->id_reunion); // Genera un token para el acceso del admin a la reunión
                return $item;
            });

            return response()->json($reuniones);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae todos los datos de una reunión en especifico
     */
    public function getReunion($id_reunion)
    {
        try {
            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                ->join('gcm_grupos', 'gcm_tipo_reuniones.id_grupo', '=', 'gcm_grupos.id_grupo')
                ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo', 'gcm_tipo_reuniones.id_grupo', 'gcm_grupos.logo')
                ->where([['id_reunion', $id_reunion], ['gcm_reuniones.estado', '!=', '4']])->get();

            try {
                JWTAuth::parseToken()->authenticate();
                $reunion->map(function ($item) {
                    $item->token = (new Encrypt())->encriptar(JWTAuth::user()->id_usuario . '|' . $item->id_reunion); // Genera un token para el acceso del admin a la reunión
                    return $item;
                });
            } catch (\Throwable $th) {} // Cuando entra al catch es por que qien consulta es un convocado sin sesión iniciada

            return response()->json($reunion);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae todos los roles actas registrados
     */
    public function getRolesActas($id_acta)
    {
        try {
            $rolesActas = Gcm_Rol_Acta::where([['estado', '1'], ['id_acta', $id_acta]])->get();
            return response()->json($rolesActas);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae todas los programas registradas en una reunion
     */
    public function getProgramas($id_reunion)
    {
        try {
            //toma todos los datos de programacion sin importar si tiene o no archivos
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
            return response()->json($programas);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage() . ' - ' . $th->getLine()], 500);
        }
    }

    /**
     * Trae todos los convocados registrados en una reunion y lo utilizo para mostrar la cantidad de convocados en la vista principal de meets
     */
    public function getConvocados($id_reunion)
    {
        try {
            $convocados = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
                ->join(DB::raw('gcm_relaciones AS grc'), 'gcr.id_relacion', '=', 'grc.id_relacion')
                ->join(DB::raw('gcm_recursos AS grs'), 'grc.id_recurso', '=', 'grs.id_recurso')
                ->join(DB::raw('gcm_roles AS grl'), 'grc.id_rol', '=', 'grl.id_rol')
                ->where([[DB::raw('gcr.id_reunion'), $id_reunion], ['gcr.estado', '=', '1']])
                ->select([
                    DB::raw('grs.*'),
                    DB::raw('grl.id_rol'),
                    DB::raw('grl.descripcion AS rol'),
                    'gcr.id_convocado_reunion',
                    'gcr.nit',
                    'gcr.razon_social',
                    'gcr.participacion',
                    'gcr.representacion',
                    'gcr.tipo',
                    'gcr.id_reunion',
                    'gcr.id_relacion',
                    'gcr.fecha',
                    'gcr.soporte',
                    'gcr.fecha_envio_invitacion',
                    'gcr.firma',
                    'gcr.acta',
                ])->get();
            return response()->json($convocados);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Realizo el reenvio de un correo electronico a los convocados de una reunion
     *
     * @param Request Aqui va los id de los convocados, los correos y el id de reunion
     * @return void Retorna un mensaje donde se evidencia si el envio de los correos fue exitoso o fallo
     */
    public function reenviarCorreos(Request $request)
    {
        // Eloquent siempre devuelve colecciones
        // first() trae el primero que encuentre

        try {
            // Aqui realizo un array_map con el objetivo de obtener solo el correo del objeto que llega y que este se almacene en un array nuevo
            $correosOrganizados = array_map(function ($row) {
                return $row['correo'];
            }, $request->correos);

            $encrypt = new Encrypt();
            $programas = [];

            $id_reunion = $request->id_reunion;

            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo')
                ->where([['gcm_reuniones.id_reunion', '=', $id_reunion], ['gcm_reuniones.estado', '!=', 4]])->first();

            $programas = Gcm_Programacion::where([['gcm_programacion.id_reunion', '=', $id_reunion], ['gcm_programacion.estado', '!=', '4']])
                ->whereNull('gcm_programacion.relacion')->get();

            for ($i = 0; $i < count($request->correos); $i++) {

                $convocado = Gcm_Convocado_Reunion::findOrFail($request->correos[$i]['id_convocado']);
                $fecha = date('Y-m-d h:i:s');
                $convocado->fecha_envio_invitacion = $fecha;
                $response = $convocado->save();

                $recurso = Gcm_Recurso::join('gcm_relaciones', 'gcm_relaciones.id_recurso', '=', 'gcm_recursos.id_recurso')
                    ->join('gcm_convocados_reunion', 'gcm_relaciones.id_relacion', '=', 'gcm_convocados_reunion.id_relacion')
                    ->select('gcm_recursos.*')
                    ->where([['gcm_convocados_reunion.id_convocado_reunion', '=', $request->correos[$i]['id_convocado']], ['gcm_recursos.estado', '=', 1]])->first();

                $recurso_actualizar = Gcm_Recurso::findOrFail($recurso['id_recurso']);
                $recurso_actualizar->correo = $request->correos[$i]['correo'];
                $response = $recurso_actualizar->save();

                $valorEncriptado = $encrypt->encriptar($request->correos[$i]['id_convocado']);

                $detalle = [
                    'nombre' => $recurso['nombre'],
                    'titulo' => $reunion['titulo'],
                    'descripcion' => $reunion['descripcion'],
                    'fecha_reunion' => $reunion['fecha_reunion'],
                    'hora' => $reunion['hora'],
                    'programas' => $programas,
                    'url' => env('VIEW_BASE') . '/public/acceso-reunion/acceso/' . $valorEncriptado,
                ];
                Mail::to($request->correos[$i]['correo'])->send(new TestMail($detalle));
            }
            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Reenvio de correos a los convocados a una reunion', 'Correos' => $correosOrganizados), null);
            return response()->json(["response" => 'exitoso'], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el estado de una reunión a 'iniciada'
     *
     * @param [type] $id_reunion Id de la reunión la cual va ser iniciada
     * @return void Retorna un mensaje donde se evidencia si actualizo el estado o si fallo el proceso
     */
    public function iniciarReunion($id_reunion)
    {
        try {
            $reunion = Gcm_Reunion::findOrFail($id_reunion);
            $reunion->estado = 1;
            $response = $reunion->save();
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el estado de una reunión a 'cancelada'
     *
     * @param [type] $id_reunion Id de la reunión la cual va ser cancelada
     * @return void Retorna un mensaje donde se evidencia si actualizo el estado o si fallo el proceso
     */
    public function cancelarReunion(Request $request)
    {
        try {
            $id_reunion = $request->id_reunion;
            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo')
                ->where([['gcm_reuniones.id_reunion', '=', $id_reunion], ['gcm_reuniones.estado', '!=', 4]])->firstOrFail();
            $reunion->estado = 3;
            $response = $reunion->save();

            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Aqui se envía el correo dando informe de la cancelación de una reunión
     *
     * @param Request $request Aqui llegan los datos de la reunion y de los convocados
     * @return void Retorna un mensaje donde se evidencia si envio el correo o si fallo el proceso
     */
    public function correoCancelacion(Request $request)
    {
        $id_reunion = $request->id_reunion;

        try {
            $id_reunion = $request->id_reunion;
            $convocados = $request->convocados;
            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo')
                ->where([['gcm_reuniones.id_reunion', '=', $id_reunion], ['gcm_reuniones.estado', '!=', 4]])->firstOrFail();

            // Aqui realizo un array_map con el objetivo de obtener solo el correo del objeto que llega y que este se almacene en un array nuevo
            $correosOrganizados = array_map(function ($row) {
                return $row['correo'];
            }, $convocados);

            for ($i = 0; $i < count($convocados); $i++) {
                $detalle = [
                    'nombre' => $convocados[$i]['nombre'],
                    'titulo' => $reunion['titulo'],
                    'descripcion' => $reunion['descripcion'],
                    'fecha_reunion' => $reunion['fecha_reunion'],
                    'hora' => $reunion['hora'],
                ];
                Mail::to($convocados[$i]['correo'])->send(new ReunionCancelada($detalle));
            }

            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Envio de correo a los convocados por la cancelacion de una reunion', 'Correos' => $correosOrganizados), null);
            return response()->json(["response" => 'Correo enviado correctamente'], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Elimina una reunión y todos los registros asociados a ella que esten en las demas tablas
     *
     * @param [type] $id_reunion $id_reunion Id de la reunión la cual va ser eliminada
     * @return void Retorna un mensaje donde se evidencia si elimino la reunion o si fallo el proceso
     */
    public function eliminarReunion($id_reunion)
    {
        try {
            Gcm_Archivo_Programacion::groupDeletion(Gcm_Archivo_Programacion::join('gcm_programacion as p', 'p.id_programa', '=', 'gcm_archivos_programacion.id_programa')->where('p.id_reunion', '=', $id_reunion)->get());
            Gcm_Programacion::changeStatus(Gcm_Programacion::where('id_reunion', '=', $id_reunion)->whereNotNull('relacion')->get(), 4);
            Gcm_Programacion::changeStatus(Gcm_Programacion::where('id_reunion', '=', $id_reunion)->get(), 4);
            Gcm_Reunion::changeStatus(Gcm_Reunion::where('id_reunion', '=', $id_reunion)->get(), 4);

            return response()->json(["response" => 'se elimino con exicto'], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Reprograma o actualiza la fecha de una reunión, junto con este cambio se cambia el estado de cancelada a en espera
     *
     * @param Request $request Aqui van los datos para hacer la actualizacion posible, id_reunion, fecha y hora
     * @return void Retorna un mensaje donde se evidencia si actualizo la reunion o si fallo el proceso
     */
    public function reprogramarReunion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_reunion' => 'required',
                'hora' => 'required',
            ], [
                'fecha_reunion.required' => '*Rellena este campo',
                'hora.required' => '*Rellena este campo',
            ]);
            
            if ($validator->fails()) {
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 378), null);
                return response()->json($validator->errors(), 422);
            }
            
            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
            ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo')
            ->where([['gcm_reuniones.id_reunion', '=', $request->id_reunion], ['gcm_reuniones.estado', '!=', 4]])->firstOrFail();
            
            $reunion->fecha_reunion = $request->fecha_reunion;
            $reunion->hora = $request->hora;
            $reunion->estado = 0;
            $response = $reunion->save();
            
            $convocados = $request->convocados;
            $encrypt = new Encrypt();
            // Aqui realizo un array_map con el objetivo de obtener solo el correo del objeto que llega y que este se almacene en un array nuevo
            $correosOrganizados = array_map(function ($row) {
                return $row['correo'];
            }, $convocados);
            
            $imagen = '';

            if ($request->id_grupo == 1) {
                $imagen = 'http://burodeconexiones.com/gc_balanced/public/assets/img/test/GCL.jpg';
            } else if ($request->id_grupo == 2) {
                $imagen = 'http://burodeconexiones.com/gc_balanced/public/assets/img/test/GCP.jpg';
            } else if ($request->id_grupo == 3) {
                $imagen = 'http://burodeconexiones.com/gc_balanced/public/assets/img/test/GBR.jpg';
            } else {
                $imagen = 'http://burodeconexiones.com/gc_balanced/public/assets/img/test/GM.jpg';
            }

            for ($i = 0; $i < count($convocados); $i++) {
                $valorEncriptado = $encrypt->encriptar($convocados[$i]['id_convocado_reunion']);
                $detalle = [
                    'imagen' => $imagen,
                    'nombre' => $convocados[$i]['nombre'],
                    'titulo' => $reunion['titulo'],
                    'descripcion' => $reunion['descripcion'],
                    'fecha_reunion' => $request->fecha_reunion,
                    'hora' => $request->hora,
                    'url' => env('VIEW_BASE') . '/public/acceso-reunion/acceso/' . $valorEncriptado,
                ];
                Mail::to($convocados[$i]['correo'])->send(new ReunionReprogramada($detalle));
            }
            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Envio de correo a los convocados por la reprogramacion de una reunion', 'Correos' => $correosOrganizados), null);
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    // MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT

    /**
     * Consulta todos los recursos registrados
     */
    public function getRecursos($id_grupo)
    {
        try {
            $getMaxFecha = function ($exceptions = [], $nullExceptions = []) use ($id_grupo) {
                // Consulta la máxima fecha de convocatoria de un recurso de acuerdo a las condiciones
                $statement = DB::table('gcm_convocados_reunion AS s1_crn')
                    ->join('gcm_reuniones AS s1_rns', 's1_rns.id_reunion', 's1_crn.id_reunion')
                    ->join('gcm_tipo_reuniones AS s1_trs', 's1_trs.id_tipo_reunion', 's1_rns.id_tipo_reunion')
                    ->join('gcm_relaciones AS s1_rln', 's1_rln.id_relacion', 's1_crn.id_relacion')
                    ->join('gcm_roles AS s1_rls', 's1_rls.id_rol', 's1_rln.id_rol')
                    ->join('gcm_recursos AS s1_rcs', 's1_rcs.id_recurso', 's1_rln.id_recurso')
                    ->whereNull('s1_crn.representacion')->where([
                    ['s1_rns.estado', '!=', 4],
                    ['s1_trs.id_grupo', $id_grupo], ['s1_rcs.estado', 1],
                    ['s1_rls.estado', 1], ['s1_rln.estado', 1],
                    ['s1_crn.estado', 1],
                ])->groupBy('s1_rcs.id_recurso')
                    ->select('s1_rln.id_recurso', DB::raw('MAX(s1_crn.fecha) AS fecha'));
                // Excepciones para consultas where normales
                foreach ($exceptions as $key => $value) {
                    $statement->where($key, $value);
                }
                // Excepciones para consultas where not null
                foreach ($nullExceptions as $value) {
                    $statement->whereNotNull($value);
                }
                return $statement;
            };

            $getInfoRecurso = function ($maxFechaQuery, $alias, $joins = []) {
                $statement = DB::table('gcm_convocados_reunion AS crn')
                    ->join('gcm_relaciones AS rln', 'rln.id_relacion', 'crn.id_relacion')
                    ->joinSub($maxFechaQuery, $alias, function ($join) use ($alias) {
                        $join->on("{$alias}.id_recurso", 'rln.id_recurso');
                        $join->on("{$alias}.fecha", 'crn.fecha');
                    })->where('crn.estado', 1);
                // Información de tablas
                foreach ($joins as $value) {
                    $statement->join($value[0], $value[1], $value[2]);
                }
                return $statement;
            };

            $roles = $getMaxFecha();
            $rol_recursos = $getInfoRecurso($roles, 'rol', [
                ['gcm_roles AS rls', 'rls.id_rol', 'rln.id_rol'],
            ])->select('rln.id_recurso', 'rls.descripcion AS rol');

            $participacion = $getMaxFecha(['s1_rns.quorum' => 1]);
            $participacion_recursos = $getInfoRecurso($participacion, 'participacion')
                ->select('rln.id_recurso', 'crn.participacion');

            $entidad = $getMaxFecha([], ['s1_crn.nit']);
            $entidad_recursos = $getInfoRecurso($entidad, 'entidad')
                ->select('rln.id_recurso', 'crn.nit', 'crn.razon_social', 'crn.soporte', 'crn.fecha');

            $respuesta = DB::table('gcm_recursos AS rcs')
                ->leftJoinSub($rol_recursos, 'rol_recurso', function ($join) {
                    $join->on('rol_recurso.id_recurso', 'rcs.id_recurso');
                })->leftJoinSub($participacion_recursos, 'participacion_recurso', function ($join) {
                $join->on('participacion_recurso.id_recurso', 'rcs.id_recurso');
            })->leftJoinSub($entidad_recursos, 'entidad_recurso', function ($join) {
                $join->on('entidad_recurso.id_recurso', 'rcs.id_recurso');
            })
                ->select(
                    'rcs.*',
                    'rol_recurso.rol',
                    'participacion_recurso.participacion',
                    DB::raw('IF(entidad_recurso.nit IS NULL, 0, 2) AS tipo'),
                    'entidad_recurso.fecha',
                    'entidad_recurso.nit',
                    'entidad_recurso.razon_social',
                    'entidad_recurso.soporte'
                )->where('rcs.estado', 1)->get();

            return response()->json($respuesta);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Consulta los recurso de la base de datos de GCM y ademas los filtra eliminando de GCM los que ya estan GCMEET
     */
    public function getRecursosGcm()
    {
        try {
            $recursos = Gcm_Recurso::select('identificacion')->get();
            $data = array_map(function ($row) {
                return $row->identificacion;
            }, json_decode($recursos));

            $recursos_gcm = Http::post(env('GCAPI_BASE') . '/api/gccrm/get-employees', ['password' => env('GCAPI_PASS'), 'excluded_identifications' => json_encode($data)]);
            return response()->json($recursos_gcm->json());
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            $recursos_gcm = [];
            return response()->json($recursos_gcm);
        }
    }

    /**
     * Consulta todos los roles que tienen una relacion donde el grupo tiene en comun con la tabla tipos de reuniones que tiene en comun con la tabla reuniones.
     */
    public function getRoles($id_reunion)
    {

        try {
            $roles = Gcm_Rol::join('gcm_relaciones', 'gcm_roles.id_rol', '=', 'gcm_relaciones.id_rol')
                ->join('gcm_tipo_reuniones', 'gcm_relaciones.id_grupo', '=', 'gcm_tipo_reuniones.id_grupo')
                ->join('gcm_reuniones', 'gcm_tipo_reuniones.id_tipo_reunion', '=', 'gcm_reuniones.id_tipo_reunion')
                ->leftJoin('gcm_roles as rl2', 'gcm_roles.relacion', '=', 'rl2.id_rol')
                ->select('gcm_roles.*', 'rl2.descripcion as nombre_relacion')
                ->where([['gcm_reuniones.id_reunion', $id_reunion], ['gcm_roles.estado', 1]])
                ->groupBy('gcm_roles.id_rol')->get();
            return response()->json($roles);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Consulta todos los roles que tienen una relacion donde el grupo tiene en comun con la tabla tipos de reuniones que tiene en comun con la tabla reuniones.
     */
    public function getRolesRegistrar($id_grupo)
    {
        try {
            $roles = Gcm_Rol::join('gcm_relaciones', 'gcm_roles.id_rol', '=', 'gcm_relaciones.id_rol')
                ->leftJoin('gcm_roles as rl2', 'gcm_roles.relacion', '=', 'rl2.id_rol')
                ->select('gcm_roles.*', 'rl2.descripcion as nombre_relacion')
                ->where([['gcm_relaciones.id_grupo', $id_grupo], ['gcm_roles.estado', 1]])
                ->groupBy('gcm_roles.id_rol')->get();
            return response()->json($roles);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Consulta todas las reuniones con un tipo de reunion que tiene un grupo en comun
     */
    public function getTiposReuniones($id_grupo)
    {
        try {
            $tiposReuniones = Gcm_Tipo_Reunion::where([['gcm_tipo_reuniones.id_grupo', '=', $id_grupo], ['gcm_tipo_reuniones.estado', '=', 1]])->get();
            return response()->json($tiposReuniones);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae los datos de un tipo de reunión
     */
    public function getTipoReunion($id_tipo_reunion)
    {
        try {
            $tipoReunion = Gcm_Tipo_Reunion::where([['id_tipo_reunion', $id_tipo_reunion], ['estado', 1]])->get();
            return response()->json($tipoReunion);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    // ACTUALIZAR REUNION COMPLETA

    /**
     * Consulta los datos de la reunion mas actualizada de un tipo en especifico
     *
     * @param [type] $id_tipo_reunion Aqui va el id del tipo reunion con el que se va consultar
     * @return void objeto con la reunion que se consulto
     */
    public function traerReunion($id_tipo_reunion)
    {
        try {
            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
                ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo', 'gcm_tipo_reuniones.id_grupo')
                ->where([['gcm_reuniones.id_tipo_reunion', '=', $id_tipo_reunion], ['gcm_reuniones.estado', '!=', 4]])
                ->orderBy('fecha_actualizacion', 'desc')
                ->limit(1)
                ->get();
            return response()->json($reunion);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    public function editarReunionCompleta(Request $request)
    {
        try {

            // Array para almacenar los id de los convocados para poder enviar los correos con la url de la reunion
            $array_id_convocados = [];

            // Actualiza los datos de la reunión
            $data = json_decode($request->reunion, true);
            $dataCollection = collect($data);

            $validator = Validator::make($dataCollection->all(), [
                'titulo' => 'required|max:255',
                'descripcion' => 'max:5000',
                'fecha_reunion' => 'required',
                'hora' => 'required',
                'quorum' => 'required|max:2',
            ], [
                'titulo.required' => '*Rellena este campo',
                'titulo.max' => '*Maximo 255 caracteres',
                'descripcion.max' => '*Maximo 5000 caracteres',
                'fecha_reunion.required' => '*Rellena este campo',
                'hora.required' => '*Rellena este campo',
                'quorum.required' => '*Rellena este campo',
                'quorum.max' => '*Máximo 2 caracteres',
            ]);

            if ($validator->fails()) {
                DB::rollback();
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 647), null);
                return response()->json($validator->errors(), 422);
            }

            $tipo_reunion = Gcm_Tipo_Reunion::find($data['id_tipo_reunion']);

            if (!$tipo_reunion) {
                $tipo_existe = Gcm_Tipo_Reunion::where([['id_grupo', $data['id_grupo']], ['titulo', $data['titulo']]])->first();
                if ($tipo_existe) {$tipo_reunion = Gcm_Tipo_Reunion::find($tipo_existe->id_tipo_reunion);}
            }

            if (!$tipo_reunion) {$tipo_reunion = new Gcm_Tipo_Reunion();}

            $tipo_reunion->imagen = "/assets/img/meets/bg" . random_int(1, 4) . ".png";
            $tipo_reunion->honorifico_representante = 'Representantes';
            $tipo_reunion->honorifico_participante = 'Participantes';
            $tipo_reunion->honorifico_invitado = 'Invitados';
            $tipo_reunion->id_grupo = $data['id_grupo'];
            $tipo_reunion->titulo = $data['titulo'];
            $tipo_reunion->estado = 1;
            $tipo_reunion->save();

            // Array de extensiones que se van a permitir en la inserción del archivo de firma programacion
            $extensionesFirmaProgramacion = array('PNG', 'JPG', 'JPEG', 'PDF');

            $reunion = Gcm_Reunion::find($data['id_reunion']);
            if (!$reunion) {$reunion = new Gcm_Reunion();}

            $reunion->id_tipo_reunion = $tipo_reunion->id_tipo_reunion;
            $reunion->id_acta = $data['id_acta'] == 0 ? null : $data['id_acta'];
            $reunion->fecha_reunion = $data['fecha_reunion'];
            $reunion->descripcion = $data['descripcion'];
            $reunion->quorum = $data['quorum'];
            $reunion->estado = $data['estado'];
            $reunion->hora = $data['hora'];

            $reunion->save();

            if ($request->hasFile('programacion')) {
                $subcarpeta = 'archivos_reunion/' . $reunion->id_reunion;
                $carpeta = 'storage/app/public/' . $subcarpeta;

                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0777, true);
                    chmod($carpeta, 0777);
                }

                $file = $request['programacion'];
                $extension = $file->getClientOriginalExtension();

                if (!in_array(strtoupper($extension), $extensionesFirmaProgramacion)) {
                    DB::rollback();
                    Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'La extensión del archivo no es permitida'), null);
                    return response()->json(['error' => 'La extensión del archivo no es permitida'], 500);
                }

                $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $extension;
                $reunion->programacion = $subcarpeta . '/' . $picture;
                $file->move(storage_path('app/public/archivos_reunion/' . $reunion->id_reunion), $picture);
                chmod(storage_path('app/public/archivos_reunion/' . $reunion->id_reunion . '/' . $picture), 0777);
            } else { $reunion->programacion = $data['programacion'];}

            $reunion->save();

            $convocados = json_decode($request->convocados, true);

            $summonList = array_map(function ($summon) {return $summon['identificacion'];}, $convocados);

            $removedSummonList = Gcm_Convocado_Reunion::join('gcm_relaciones AS rlc', 'gcm_convocados_reunion.id_relacion', '=', 'rlc.id_relacion')
                ->join('gcm_recursos AS rcs', 'rlc.id_recurso', '=', 'rcs.id_recurso')
                ->where([['gcm_convocados_reunion.id_reunion', $reunion->id_reunion], ['gcm_convocados_reunion.estado', '!=', '4']])
                ->whereNotIn('rcs.identificacion', $summonList)->get();

            $removedSummonList->each(function ($summonToRemoveItem) {
                $summonToRemove = Gcm_Convocado_Reunion::find($summonToRemoveItem->id_convocado_reunion);
                if ($summonToRemove) {
                    $summonToRemove->estado = '0';
                    $summonToRemove->save();
                }
            });

            foreach ($convocados as $convocadoItem) {
                $convocadosCollection = collect($convocadoItem);

                $validator = Validator::make($convocadosCollection->all(), [
                    'rol' => 'required|max:100',
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
                    'rol.required' => '*Rellena este campo',
                    'rol.max' => '*Maximo 100 caracteres',
                ]);

                if ($validator->fails()) {
                    DB::rollback();
                    Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 831), null);
                    return response()->json($validator->errors(), 422);
                }

                // Consulta si el convocado ya fue registrado como recurso
                $recurso_existe = DB::table('gcm_recursos')->where('identificacion', '=', $convocadoItem['identificacion'])->first();
                $recurso = !$recurso_existe ? new Gcm_Recurso() : Gcm_Recurso::findOrFail($recurso_existe->id_recurso);

                if (!empty($convocadoItem['telefono'])) {$recurso->telefono = $convocadoItem['telefono'];}
                $recurso->identificacion = $convocadoItem['identificacion'];
                $recurso->nombre = $convocadoItem['nombre'];
                $recurso->correo = $convocadoItem['correo'];
                $recurso->estado = 1;

                $recurso->save();

                $rol_existe = DB::table('gcm_roles')->where('descripcion', '=', $convocadoItem['rol'])->first();
                $rol = !$rol_existe ? new Gcm_Rol() : Gcm_Rol::findOrFail($rol_existe->id_rol);

                $rol->descripcion = $convocadoItem['rol'];
                $rol->relacion = null;
                $rol->estado = 1;
                $rol->save();

                // Consulta si ya existe una relacion registrada con los datos subministrados(id_grupo, id_rol, id_recurso)
                $relacion_existe = DB::table('gcm_relaciones')->where([['id_grupo', '=', $data['id_grupo']], ['id_rol', '=', $rol->id_rol], ['id_recurso', '=', $recurso->id_recurso]])->first();
                $relacion = !$relacion_existe ? new Gcm_Relacion() : Gcm_Relacion::findOrFail($relacion_existe->id_relacion);

                $relacion->id_recurso = $recurso->id_recurso;
                $relacion->id_grupo = $data['id_grupo'];
                $relacion->id_rol = $rol->id_rol;
                $relacion->estado = 1;
                $relacion->save();

                // Registra el convocado con nit y razon social
                $validator;
                if ($convocadoItem['tipo'] == '2') {
                    $validator = Validator::make($convocadosCollection->all(), [
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
                    $validator = Validator::make($convocadosCollection->all(), [
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

                $convocado_existe = Gcm_Convocado_Reunion::join('gcm_relaciones AS rlc', 'gcm_convocados_reunion.id_relacion', '=', 'rlc.id_relacion')
                    ->join('gcm_recursos AS rcs', 'rlc.id_recurso', '=', 'rcs.id_recurso')
                    ->where([['gcm_convocados_reunion.id_reunion', $reunion->id_reunion], ['rcs.identificacion', $recurso->identificacion]])->first();

                $convocado = !$convocado_existe ? new Gcm_Convocado_Reunion() : Gcm_Convocado_Reunion::findOrFail($convocado_existe->id_convocado_reunion);

                $convocado->participacion = ($convocadoItem['tipo'] == '2' || $convocadoItem['tipo'] == 0) ? (isset($convocadoItem['participacion']) ? $convocadoItem['participacion'] : null) : null;
                $convocado->razon_social = $convocadoItem['tipo'] == '2' ? $convocadoItem['razon_social'] : null;
                $convocado->nit = $convocadoItem['tipo'] == '2' ? $convocadoItem['nit'] : null;
                $convocado->id_relacion = $relacion->id_relacion;
                $convocado->id_reunion = $reunion->id_reunion;
                $convocado->firma = $convocadoItem['firma'];
                $convocado->tipo = $convocadoItem['tipo'];
                $convocado->acta = $convocadoItem['acta'];
                $convocado->representacion = null;
                $convocado->soporte = null;
                $convocado->estado = 1;

                $convocado->save();

                array_push($array_id_convocados, $convocado->id_convocado_reunion);
            }

            // Array de extensiones que se van a permitir en la inserción de archivos de la programación
            $extensiones = array('PNG', 'JPG', 'JPEG', 'GIF', 'XLSX', 'CSV', 'PDF', 'DOCX', 'TXT', 'PPTX', 'SVG', 'PDF');

            $programList = isset($request['id_programa']) ? array_values(
                array_filter($request['id_programa'], function ($program) {return $this->stringNullToNull($program) !== null;})
            ) : [];

            $removedProgramList = Gcm_Programacion::where('id_reunion', $reunion->id_reunion)
                ->whereNull('relacion')->whereNotIn('id_programa', $programList)->get();

            $removedProgramList->each(function ($programToRemoveItem) {
                $programToRemove = Gcm_Programacion::find($programToRemoveItem->id_programa);
                if ($programToRemove) {
                    $programToRemove->estado = '4';
                    $programToRemove->save();
                }
            });

            if (isset($request['titulo'])) {
                for ($i = 0; $i < count($request['titulo']); $i++) {

                    $id_rol_acta = null;
                    if ($reunion->id_acta != null && isset($request['rol_acta_descripcion']) && $this->stringNullToNull($request['rol_acta_descripcion'][$i]) !== null) {
                        $rol_acta_existe = Gcm_Rol_Acta::where([['id_acta', $reunion->id_acta], ['descripcion', $request['rol_acta_descripcion'][$i]]])->first();
                        $rol_acta = !$rol_acta_existe ? new Gcm_Rol_Acta() : Gcm_Rol_Acta::findOrFail($rol_acta_existe->id_rol_acta);

                        $rol_acta->descripcion = $request['rol_acta_descripcion'][$i];
                        $rol_acta->firma = $request['rol_acta_firma'][$i];
                        $rol_acta->acta = $request['rol_acta_acta'][$i];
                        $rol_acta->id_acta = $reunion->id_acta;
                        $rol_acta->estado = 1;
                        $rol_acta->save();
                        $id_rol_acta = $rol_acta->id_rol_acta;
                    }

                    $programa = !$this->stringNullToNull($request['id_programa'][$i]) ? new Gcm_Programacion() : Gcm_Programacion::findOrFail($request['id_programa'][$i]);

                    $programa->descripcion = $this->stringNullToNull($request['descripcion'][$i]);
                    $programa->estado = $request['estado'][$i] ? $request['estado'][$i] : 0;
                    $programa->titulo = $this->stringNullToNull($request['titulo'][$i]);
                    $programa->tipo = $this->stringNullToNull($request['tipo'][$i]);
                    $programa->id_reunion = $reunion->id_reunion;
                    $programa->id_rol_acta = $id_rol_acta;
                    $programa->relacion = null;
                    $programa->orden = $i + 1;
                    $programa->numeracion = 0;

                    $programa->save();

                    $fileList = isset($request['file_viejo' . $i]) ? array_map(function ($data) {
                        return json_decode($data)->id_archivo_programacion;
                    }, $request['file_viejo' . $i]) : [];

                    $removedFileList = Gcm_Archivo_Programacion::where('id_programa', $programa->id_programa)
                        ->whereNotIn('id_archivo_programacion', $fileList)->get();

                    $removedFileList->each(function ($fileToRemove) {
                        $fileToRemove->delete();
                    });

                    if ($request->hasFile('file' . $i)) {
                        $request['file' . $i] = array_values($request['file' . $i]);

                        $subcarpeta = 'archivos_reunion/' . $reunion->id_reunion;
                        $carpeta = 'storage/app/public/' . $subcarpeta;

                        if (!file_exists($carpeta)) {
                            mkdir($carpeta, 0777, true);
                            chmod($carpeta, 0777);
                        }

                        for ($j = 0; $j < count($request['file' . $i]); $j++) {
                            $archivo = new Gcm_Archivo_Programacion();
                            $file = $request['file' . $i][$j];
                            $extension = $file->getClientOriginalExtension();

                            if (in_array(strtoupper($extension), $extensiones)) {
                                $archivo->descripcion = $file->getClientOriginalName();
                                $archivo->id_programa = $programa->id_programa;
                                $archivo->peso = filesize($file);
                                $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $extension;
                                $archivo->url = $subcarpeta . '/' . $picture;
                                $file->move(storage_path('app/public/archivos_reunion/' . $reunion->id_reunion), $picture);
                                chmod(storage_path('app/public/archivos_reunion/' . $reunion->id_reunion . '/' . $picture), 0777);

                                $archivo->save();
                            } else {
                                DB::rollback();
                                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'La extensión del archivo no es permitida'), null);
                                return response()->json(['error' => 'La extensión del archivo no es permitida'], 500);
                            }
                        }
                    }

                    if (isset($request['file_viejo' . $i])) {
                        $request['file_viejo' . $i] = array_values($request['file_viejo' . $i]);
                        for ($j = 0; $j < count($request['file_viejo' . $i]); $j++) {
                            $file = json_decode($request['file_viejo' . $i][$j]);

                            $archivo = !$file->id_archivo_programacion ? new Gcm_Archivo_Programacion() : Gcm_Archivo_Programacion::findOrFail($file->id_archivo_programacion);

                            $archivo->id_programa = $programa->id_programa;
                            $archivo->descripcion = $file->name;
                            $archivo->peso = $file->size;
                            $archivo->url = $file->url;
                            $archivo->save();
                        }
                    }

                    $optionList = isset($request['opcion_id_programa' . $i]) ? array_values(
                        array_filter($request['opcion_id_programa' . $i], function ($data) {return $this->stringNullToNull($data) !== null;})
                    ) : [];

                    $removedOptionList = Gcm_Programacion::where('relacion', $programa->id_programa)
                        ->whereNotIn('id_programa', $optionList)->get();

                    $removedOptionList->each(function ($optionToRemoveItem) {
                        $optionToRemove = Gcm_Programacion::find($optionToRemoveItem->id_programa);
                        if ($optionToRemove) {
                            $optionToRemove->estado = '4';
                            $optionToRemove->save();
                        }
                    });

                    // Valida que si vengan opciones para registrar
                    if (isset($request['opcion_titulo' . $i])) {

                        for ($j = 0; $j < count($request['opcion_titulo' . $i]); $j++) {

                            $opcion = !$this->stringNullToNull($request['opcion_id_programa' . $i][$j]) ? new Gcm_Programacion() : Gcm_Programacion::findOrFail($request['opcion_id_programa' . $i][$j]);

                            $opcion->estado = $request['opcion_estado' . $i][$j] ? $request['opcion_estado' . $i][$j] : 0;
                            $opcion->descripcion = $this->stringNullToNull($request['opcion_descripcion' . $i][$j]);
                            $opcion->titulo = $this->stringNullToNull($request['opcion_titulo' . $i][$j]);
                            $opcion->id_reunion = $reunion->id_reunion;
                            $opcion->relacion = $programa->id_programa;
                            $opcion->id_convocado_reunion = null;
                            $opcion->id_rol_acta = null;
                            $opcion->orden = $j + 1;
                            $opcion->numeracion = 1;
                            $opcion->tipo = 0;

                            $opcion->save();

                            $optionFileList = isset($request['opcion_file_viejo' . $i . '_' . $j]) ? array_map(function ($data) {
                                return json_decode($data)->id_archivo_programacion;
                            }, $request['opcion_file_viejo' . $i . '_' . $j]) : [];

                            $removedOptionFileList = Gcm_Archivo_Programacion::where('id_programa', $opcion->id_programa)
                                ->whereNotIn('id_archivo_programacion', $optionFileList)->get();

                            $removedOptionFileList->each(function ($optionFileToRemove) {
                                $optionFileToRemove->delete();
                            });

                            if ($request->hasFile('opcion_file' . $i . '_' . $j)) {
                                $request['opcion_file' . $i . '_' . $j] = array_values($request['opcion_file' . $i . '_' . $j]);

                                $subcarpeta = 'archivos_reunion/' . $reunion->id_reunion;
                                $carpeta = 'storage/app/public/' . $subcarpeta;

                                if (!file_exists($carpeta)) {
                                    mkdir($carpeta, 0777, true);
                                    chmod($carpeta, 0777);
                                }

                                for ($k = 0; $k < count($request['opcion_file' . $i . '_' . $j]); $k++) {
                                    $opcion_file = $request['opcion_file' . $i . '_' . $j][$k];
                                    $opcion_extension = $opcion_file->getClientOriginalExtension();
                                    $archivo = new Gcm_Archivo_Programacion();

                                    if (in_array(strtoupper($opcion_extension), $extensiones)) {
                                        $archivo->descripcion = $opcion_file->getClientOriginalName();
                                        $archivo->id_programa = $opcion->id_programa;
                                        $archivo->peso = filesize($opcion_file);
                                        $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $opcion_extension;
                                        $archivo->url = $subcarpeta . '/' . $picture;
                                        $opcion_file->move(storage_path('app/public/archivos_reunion/' . $reunion->id_reunion), $picture);
                                        chmod(storage_path('app/public/archivos_reunion/' . $reunion->id_reunion . '/' . $picture), 0777);

                                        $archivo->save();
                                    } else {
                                        DB::rollback();
                                        Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'La extensión del archivo no es permitida'), null);
                                        return response()->json(['error' => 'La extensión del archivo no es permitida'], 500);
                                    }
                                }
                            }

                            if (isset($request['opcion_file_viejo' . $i . '_' . $j])) {
                                $request['opcion_file_viejo' . $i . '_' . $j] = array_values($request['opcion_file_viejo' . $i . '_' . $j]);
                                for ($k = 0; $k < count($request['opcion_file_viejo' . $i . '_' . $j]); $k++) {
                                    $opcion_file = json_decode($request['opcion_file_viejo' . $i . '_' . $j][$k]);

                                    $archivo = !$opcion_file->id_archivo_programacion ? new Gcm_Archivo_Programacion() : Gcm_Archivo_Programacion::findOrFail($opcion_file->id_archivo_programacion);

                                    $archivo->id_programa = $programa->id_programa;
                                    $archivo->descripcion = $opcion_file->name;
                                    $archivo->peso = $opcion_file->size;
                                    $archivo->url = $opcion_file->url;
                                    $archivo->save();
                                }
                            }
                        }
                    }

                }
            }

            DB::commit();
            return response()->json(['data' => $array_id_convocados, 'id_reunion' => $reunion->id_reunion], 200);
            return 'ok';
        } catch (\Throwable $th) {
            DB::rollback();
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return $th;
            return response()->json(["error" => $th->getMessage() . ' - ' . $th->getLine()], 500);
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

    /**
     * Descarga documento PDF con la programacion de una reunion
     *
     * @param Request $aqui viene toda la programacion de la reunion
     * @return void Retorna archivo para decargar
     */
    public function descargarPDFProgramacion(Request $request)
    {
        try {
            # Ingresa al directorio fuente
            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            #Tomamos el array donde están todas las fuentes
            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            // Configurar un nombre de archivo
            $documentFileName = "fun.pdf";

            // Crea el documento PDF
            $document = new MPDF([
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
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_header' => '3',
                'margin_top' => '20',
                'margin_bottom' => '20',
                'margin_footer' => '2',
            ]);

            // Establecer algunas informaciones de encabezado para la salida
            $header = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $documentFileName . '"',
            ];

            // Escribe un contenido simple
            $document->WriteHTML("<style>$request->styles</style>");
            $document->WriteHTML(str_replace("\n", "<br/>", $request->data));

            // Guarde PDF en su almacenamiento público
            Storage::disk('public')->put($documentFileName, $document->Output($documentFileName, "S"));

            // Recupere el archivo del almacenamiento con la información del encabezado de dar
            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Descarga del pdf con la programacion de una reunion'), null);
            return Storage::download($documentFileName, 'Request', $header);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Descarga documento PDF con la acta de una reunion
     *
     * @param Request $aqui viene toda la informacion de la reunion
     * @return void Retorna archivo para decargar
     */
    public function descargarPDFActa()
    {
        try {
            # Ingresa al directorio fuente
            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            #Tomamos el array donde están todas las fuentes
            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            // Configurar un nombre de archivo
            $documentFileName = "fun.pdf";

            #Creamos el PDF con las mediadas y orientacion
            $document = new MPDF([
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

            #Realizamos la estructura del PDF
            $style = "
                <style>
                @page{
                    mode: utf-8;
                    format: A4;
                    margin: 0;
                }

                .centro1raPagina {
                    background-image: url('http://192.168.2.89:4200/assets/img/meets/acta2.JPG');
                    background-size: contain;
                    background-repeat: no-repeat;
                    height: 700px;
                }

                .pie1raPagina {
                    height: 100%;
                    border-top: 6px solid;
                    border-color: #9F8C5B;
                    background-color: #16151E;
                }

                .tabla2daPagina {
                    width: 100%;
                    text-align: left;
                    border-bottom: 1px solid black;
                }

                .thTabla2daPagina {
                    font-weight: bold;
                    background: #171717;
                    color: #FFFFFF;
                    text-align: left;
                    border: 1px solid #707070;
                    padding: 10px;
                    font-family: montserratregular;
                    font-size: 13px;
                    letter-spacing: 0px;
                }

                .tdTabla2daPagina {
                    width: 25%;
                    padding: 10px;
                    font-family: montserratregular;
                    color: #545454;
                    font-size: 13px;
                    letter-spacing: 0px;
                }

                .td-texto {
                    width: 50%;
                    padding: 10px;
                    color: #545454;
                    font-family: montserratregular;
                    font-size: 13px;
                    letter-spacing: 0px;
                    text-align: justify;
                }

                .tabla-cifras {
                    width: 100%;
                }

                .th-cifras-1raColumna {
                    font-weight: bold;
                    color: #545454;
                    padding: 10px;
                    font-family: montserratbold;
                    font-size: 13px;
                    letter-spacing: 0px;
                    font-weight: 500;
                    text-align: left;
                }

                .th-cifras-2daColumna {
                    font-weight: bold;
                    color: #545454;
                    padding: 10px;
                    font-family: montserratbold;
                    font-size: 13px;
                    letter-spacing: 0px;
                    font-weight: 500;
                    text-align: center;
                }

                .td-cifras-1raColumna {
                    width: 100%;
                    padding: 10px;
                    color: #545454;
                    font-family: montserratregular;
                    font-size: 15px;
                    letter-spacing: 0px;
                    text-align: left;
                }

                .td-cifras-2daColumna {
                    width: 30%;
                    background-color: #171717;
                    padding: 10px;
                    color: #FFFFFF;
                    font-family: montserratregular;
                    font-size: 15px;
                    letter-spacing: 0px;
                    text-align: center;
                }

                .firma {
                    border-top: 1px solid #DBDBDB;
                    text-align: center;
                }

                .textoFirma {
                    color: #545454;
                    padding: 10px;
                    font-family: montserratsemibold;
                    font-weight: 500;
                    font-size: 13px;
                    letter-spacing: 0px;
                }

                .ultimaPagina {
                    background-image: url('http://192.168.2.89:4200/assets/img/meets/back4.jpg');
                    background-position: center center;
                    background-repeat: no-repeat;
                    background-image-resize: 5;
                    height: 100%;
                    width: 100%;
                    margin: 0;
                }
                </style>
                ";

            // background-size: 100% 100%;
            // Establecer algunas informaciones de encabezado para la salida
            $header = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $documentFileName . '"',
            ];

            $document->WriteHTML($style);

            $html = '
                <body>
                    <div>
                        <div align="center">
                            <img style="margin-top: 2rem; width: 180px;" src="http://192.168.2.89:4200/assets/img/meets/garantias-comunitarias.png">
                        </div>

                        <div>
                            <h5 align="center" style="margin-bottom: 30px; margin-top: 30px; color: #171717; font-family: montserratregular; font-size: 20px; font-weight: 500; letter-spacing: 0px;">
                                ASAMBLEA GENERAL ORDINARIA DE ACCIONISTAS DE GARANTIAS COMUNITARIAS
                            </h5>
                        </div>

                        <div class="centro1raPagina">
                            <h1 align="center" style="margin-top: 510px; color: #9F8C5B; font-family: montserratregular; font-size: 15px; font-weight: 500; letter-spacing: 0px;">GARANTIAS COMUNITARIAS GRUPO S.A.</h1>
                            <h1 align="center" style="margin-top: 25px; color: #FFFFFF; font-family: montserratregular; font-size: 35px; font-weight: bold; letter-spacing: 0px;">CELEBRADA EL DIA 12/01/22</h1>
                        </div>

                        <div class="pie1raPagina">
                            <h1 align="center" style="margin-top: 60px; color: #FFFFFF; font-family: montserratregular; font-size: 20px; font-weight: 500; letter-spacing: 0px;">ACTA NO. 1244</h1>
                        </div>
                    </div>
                </body>
            ';

            #Asignamos la estructura al PDF
            $document->WriteHTML($html);
            $document->AddPage();

            $html2 = '
                <body>
                    <div style="padding: 7% 11% 0% 11%;">
                        <h1 style="color: #171717; font-size: 22px; font-family: montserratregular; font-weight: 500; letter-spacing: 0px; text-align: left; margin: 0">
                            Titulo
                        </h1>

                        <h2 style="color: #545454; font-size: 16px; font-family: montserratregular; font-weight: 400; letter-spacing: 0px; text-align: left; margin: 0 0 6px 0;">
                            Sub titulo
                        </h2>

                        <p style="color: #626262; font-size: 13px; font-family: montserratregular; font-weight: 400; letter-spacing: 0px; text-align: justify; margin: 0;">
                            Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam,
                            eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.
                            Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.
                            Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit,
                            sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.
                            Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam,
                            nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur,
                            vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?.
                        </p>
                    </div>

                    <div style="padding: 2% 11% 0% 11%;">
                        <h1 style="color: #171717; font-size: 22px; font-family: montserratregular; font-weight: 300; letter-spacing: 0px; text-align: left; margin: 0 0 10px 0;">
                            Tabla
                        </h1>
                        <table class="tabla2daPagina">
                            <thead>
                                <tr>
                                    <th class="thTabla2daPagina" scope="col">Nombre</th>
                                    <th class="thTabla2daPagina" scope="col">Apellido</th>
                                    <th class="thTabla2daPagina" scope="col">Correo</th>
                                    <th class="thTabla2daPagina" scope="col">Representante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="tdTabla2daPagina">Juan</td>
                                    <td class="tdTabla2daPagina">Mark</td>
                                    <td class="tdTabla2daPagina">Otto</td>
                                    <td class="tdTabla2daPagina">Marco</td>
                                </tr>
                                <tr>
                                    <td class="tdTabla2daPagina">Keila</td>
                                    <td class="tdTabla2daPagina">Jacob</td>
                                    <td class="tdTabla2daPagina">Thornton</td>
                                    <td class="tdTabla2daPagina">Fatboy</td>
                                </tr>
                                <tr>
                                    <td class="tdTabla2daPagina">Keila</td>
                                    <td class="tdTabla2daPagina">Jacob</td>
                                    <td class="tdTabla2daPagina">Thornton</td>
                                    <td class="tdTabla2daPagina">Fatboy</td>
                                </tr>
                                <tr>
                                    <td class="tdTabla2daPagina">Keila</td>
                                    <td class="tdTabla2daPagina">Jacob</td>
                                    <td class="tdTabla2daPagina">Thornton</td>
                                    <td class="tdTabla2daPagina">Fatboy</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="padding: 2% 11% 0% 11%;">
                        <table>
                            <tbody>
                                <tr>
                                    <td class="td-texto">
                                        <h1 style="float: top; color: #171717; font-size: 22px; font-family: montserratregular; font-weight: 500; letter-spacing: 0px; text-align: left; margin: 0">
                                            Lista
                                        </h1>
                                        1. Lorem dolor sit amet
                                    </td>
                                    <td class="td-texto">
                                        <h1 style="color: #171717; font-size: 22px; font-family: montserratregular; font-weight: 500; letter-spacing: 0px; text-align: left; margin: 0">Texto con campos</h1>
                                        Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque
                                        laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto
                                        beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur
                                        aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="padding: 2% 11% 0% 11%;">
                        <table>
                            <tbody>
                                <tr>
                                    <td class="td-texto">
                                        <h1 style="color: #171717; font-size: 22px; font-family: montserratregular; font-weight: 500; letter-spacing: 0px; text-align: left; margin: 0">
                                            Votaciones
                                        </h1>
                                        Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium,
                                        totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.
                                        sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.
                                    </td>
                                    <td style="with: 100%; border: 1px solid #DBDBDB; background-image: url(http://192.168.2.89:4200/assets/img/meets/recorte.JPG);
                                        background-size: cover; background-repeat: no-repeat; background-position: center;">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </body>
            ';

            #Asignamos la estructura al PDF
            $document->WriteHTML($html2);
            $document->AddPage();

            $html3 = '
                <body>
                    <div style="padding: 7% 11% 0% 11%;">
                        <h1 style="color: #171717; font-size: 22px; font-family: montserratregular; font-weight: 300; letter-spacing: 0px; text-align: left; margin: 0 0 10px 0;">
                            Cifras
                        </h1>
                        <table class="tabla-cifras">
                            <thead>
                                <tr>
                                    <th class="th-cifras-1raColumna" scope="col">Proyecto de distribución de utilidades</th>
                                    <th class="th-cifras-2daColumna" scope="col">2020</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="td-cifras-1raColumna">Quia consequuntur</td>
                                    <td class="td-cifras-2daColumna">$12.252.354</td>
                                </tr>
                                <tr>
                                    <td class="td-cifras-1raColumna">Quia consequuntur</td>
                                    <td class="td-cifras-2daColumna">$12.252.354</td>
                                </tr>
                                <tr>
                                    <td class="td-cifras-1raColumna">Quia consequuntur</td>
                                    <td class="td-cifras-2daColumna">$12.252.354</td>
                                </tr>
                                <tr>
                                    <td class="td-cifras-1raColumna">Quia consequuntur</td>
                                    <td class="td-cifras-2daColumna">$12.252.354</td>
                                </tr>
                                <tr>
                                    <td class="td-cifras-1raColumna">Quia consequuntur</td>
                                    <td class="td-cifras-2daColumna">$12.252.354</td>
                                </tr>
                                <tr>
                                    <td class="td-cifras-1raColumna">Quia consequuntur</td>
                                    <td class="td-cifras-2daColumna">$12.252.354</td>
                                </tr>
                                <tr>
                                    <td class="td-cifras-1raColumna">Quia consequuntur</td>
                                    <td class="td-cifras-2daColumna">$12.252.354</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="padding: 2% 11% 0% 11%;">
                        <table>
                            <tbody>
                                <tr>
                                    <td class="td-texto">
                                        <h1 style="color: #171717; font-size: 22px; font-family: montserratregular; font-weight: 500; letter-spacing: 0px; text-align: left; margin: 0">
                                            Opciones
                                        </h1>
                                        Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium,
                                        totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.
                                        sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.
                                    </td>
                                    <td style="with: 100%; border: 1px solid #DBDBDB; background-image: url(http://192.168.2.89:4200/assets/img/meets/recorte2.JPG);
                                        background-size: cover; background-repeat: no-repeat; background-position: center;">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="padding: 8% 11% 0% 11%;">
                        <table style="width: 100%;">
                            <tbody>
                                <tr>
                                    <td class="firma">
                                        <h1 class="textoFirma">non numquam eius mod</h1>
                                    </td>
                                    <td class="firma">
                                        <h1 class="textoFirma">non numquam eius mod</h1>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </body>
            ';

            #Asignamos la estructura al PDF
            $document->WriteHTML($html3);
            $document->AddPage();

            $html4 = '
                <body>
                    <div class="ultimaPagina">
                        <h1 align="center" style="padding-top: 900px; color: #FFFFFF; font-family: montserratlight; font-size: 20px; font-weight: 500; letter-spacing: 0px;">
                            VOLUPTATEM ACCUSANTIUM DOLOREMQUE LAUDANTIUM
                        </h1>
                        <h1 align="center" style="color: #9F8C5B; font-family: montserratbold; font-size: 15px; font-weight: 500; letter-spacing: 0px;">
                            NON NUMQUAM EIUS MOD
                        </h1>
                    </div>
                </body>
            ';

            #Asignamos la estructura al PDF
            $document->WriteHTML($html4);

            // Guarde PDF en su almacenamiento público
            Storage::disk('public')->put($documentFileName, $document->Output($documentFileName, "S"));

            // Recupere el archivo del almacenamiento con la información del encabezado de dar
            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Descarga del acta de una reunion'), null);
            return Storage::download($documentFileName, 'Request', $header);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    public function getActas()
    {
        try {
            $actas = Gcm_Acta::get();
            return response()->json(["status" => true, "message" => $actas], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["status" => false, "message" => $th->getMessage() . ' - ' . $th->getLine()], 200);
        }
    }

}
