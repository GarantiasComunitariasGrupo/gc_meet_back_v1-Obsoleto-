<?php

namespace App\Http\Controllers;

use App;
use App\Http\Temporal\Encrypt;
use App\Mail\ReunionCancelada;
use App\Mail\ReunionReprogramada;
use App\Mail\TestMail;
use App\Models\Gcm_Archivo_Programacion;
use App\Models\Gcm_Convocado_Reunion;
use App\Models\Gcm_Grupo;
use App\Models\Gcm_Programacion;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Reunion;
use App\Models\Gcm_Rol_Acta;
use App\Models\Gcm_Rol;
use App\Models\Gcm_Tipo_Reunion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use \Mpdf\Mpdf as PDF;

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
            return response()->json($reunion);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae todos los roles actas registrados
     */
    public function getRolesActas()
    {
        try {
            $rolesActas = Gcm_Rol_Acta::where('estado', '1')->get();
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
                ->select(
                    'gcm_programacion.*',
                    DB::raw('GROUP_CONCAT(gcm_archivos_programacion.descripcion SEPARATOR "|") AS descripciones_archivos'),
                    DB::raw('GROUP_CONCAT(gcm_archivos_programacion.peso SEPARATOR "|") AS pesos_archivos'),
                    DB::raw('GROUP_CONCAT(gcm_archivos_programacion.url SEPARATOR "|") AS url_archivos')
                )->where([['gcm_programacion.id_reunion', $id_reunion], ['gcm_programacion.estado', '!=', '4']])->groupBy('gcm_programacion.id_programa')->get()->toArray();

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
            return response()->json($programas);

        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
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
                $fecha = '2022-02-02 18:06:00';
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
                    'url' => 'gcmeet.com/public/acceso-reunion/acceso/' . $valorEncriptado,
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
            return response()->json(["response" => 'exitoso'], 200);
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
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 382), null);
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

            for ($i = 0; $i < count($convocados); $i++) {
                $valorEncriptado = $encrypt->encriptar($convocados[$i]['id_convocado_reunion']);
                $detalle = [
                    'nombre' => $convocados[$i]['nombre'],
                    'titulo' => $reunion['titulo'],
                    'descripcion' => $reunion['descripcion'],
                    'fecha_reunion' => $request->fecha_reunion,
                    'hora' => $request->hora,
                    'url' => 'gcmeet.com/public/acceso-reunion/acceso/' . $valorEncriptado,
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

            $recursos_gcm = Http::post('http://192.168.2.25:8801/api/gccrm/get-employees', ['password' => 'RTTyQyFRBUT01832', 'excluded_identifications' => json_encode($data)]);
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

    /**
     * Función que actualiza los datos, los convocados y los programas de una reunión.
     *
     * @param Request Aqui toda la información de la reunion, de los convocados y los programas.
     * @return void Retorna un mensaje donde se evidencia si la actualizacion fue exitosa o fallo
     */
    public function editarReunionCompleta(Request $request)
    {

        DB::beginTransaction();
        try {
            // Array para almacenar los id de los convocados para poder enviar los correos con la url de la reunion
            $array_id_convocados = [];

            // Actualiza los datos de la reunión
            $data = json_decode($request->reunion, true);
            $dataCollection = collect($data);
            $id_tipo_reunion = $data['id_tipo_reunion'];

            // En caso de no existir el tipo reunión, registra un tipo nuevo
            if (empty($id_tipo_reunion)) {

                $validator = Validator::make($dataCollection->all(), [
                    'titulo' => 'required|max:255',
                ], [
                    'titulo.required' => '*Rellena este campo',
                    'titulo.max' => '*Maximo 255 caracteres',
                ]);

                if ($validator->fails()) {
                    DB::rollback();
                    Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 651), null);
                    return response()->json($validator->errors(), 422);
                }

                $tipo_reunion_nueva = new Gcm_Tipo_Reunion;
                $tipo_reunion_nueva->id_grupo = $data['id_grupo'];
                $tipo_reunion_nueva->titulo = $data['titulo'];
                $tipo_reunion_nueva->honorifico_participante = 'Participantes';
                $tipo_reunion_nueva->honorifico_invitado = 'Invitados';
                $tipo_reunion_nueva->honorifico_representante = 'Representantes';
                $tipo_reunion_nueva->imagen = null;
                $tipo_reunion_nueva->estado = 1;

                $response = $tipo_reunion_nueva->save();
                $id_tipo_reunion = $tipo_reunion_nueva->id_tipo_reunion;

            }

            // Array de extensiones que se van a permitir en la inserción del archivo de firma programacion
            $extensionesFirmaProgramacion = array('PNG', 'JPG', 'JPEG', 'PDF');
            $id_reunion = $data['id_reunion'];

            // En caso de no existir la reunión, crea una nueva
            if (empty($id_reunion)) {

                $validator = Validator::make($dataCollection->all(), [
                    'descripcion' => 'max:5000',
                    'fecha_reunion' => 'required',
                    'hora' => 'required',
                    'quorum' => 'required|max:2',
                ], [
                    'descripcion.max' => '*Maximo 5000 caracteres',
                    'fecha_reunion.required' => '*Rellena este campo',
                    'hora.required' => '*Rellena este campo',
                    'quorum.required' => '*Rellena este campo',
                    'quorum.max' => '*Máximo 2 caracteres',
                ]);

                if ($validator->fails()) {
                    DB::rollback();
                    Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 691), null);
                    return response()->json($validator->errors(), 422);
                }

                $reunion_nueva = new Gcm_Reunion;
                $reunion_nueva->id_tipo_reunion = $id_tipo_reunion;
                $reunion_nueva->descripcion = $data['descripcion'];
                $reunion_nueva->fecha_reunion = $data['fecha_reunion'];
                $reunion_nueva->hora = $data['hora'];
                $reunion_nueva->quorum = $data['quorum'];
                $reunion_nueva->id_acta = null;
                $reunion_nueva->estado = $data['estado'];

                $response = $reunion_nueva->save();
                $id_reunion = $reunion_nueva->id_reunion;

                $picture = 0;

                if ($request->hasFile('programacion')) {

                    $subcarpeta = 'archivos_reunion/' . $id_reunion;
                    $carpeta = 'storage/app/public/' . $subcarpeta;

                    if (!file_exists($carpeta)) {
                        mkdir($carpeta, 0777, true);
                    }

                    $file = $request['programacion'];
                    $extension = $file->getClientOriginalExtension();

                    if (in_array(strtoupper($extension), $extensionesFirmaProgramacion)) {
                        $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $extension;
                        $reunion_nueva->programacion = $subcarpeta . '/' . $picture;
                        $file->move(storage_path('app/public/archivos_reunion/' . $id_reunion), $picture);
                        chmod(storage_path('app/public/archivos_reunion/' . $id_reunion . '/' . $picture), 0777);
                    } else {
                        DB::rollback();
                        Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'La extensión del archivo no es permitida'), null);
                        return response()->json(['error' => 'La extensión del archivo no es permitida'], 500);
                    }

                } else {
                    $reunion_nueva->programacion = $data['programacion'];
                }
                $response = $reunion_nueva->save();

            } else {

                $validator = Validator::make($dataCollection->all(), [
                    'descripcion' => 'max:5000',
                    'fecha_reunion' => 'required',
                    'hora' => 'required',
                    'quorum' => 'required|max:2',
                ], [
                    'descripcion.max' => '*Maximo 5000 caracteres',
                    'fecha_reunion.required' => '*Rellena este campo',
                    'hora.required' => '*Rellena este campo',
                    'quorum.required' => '*Rellena este campo',
                    'quorum.max' => '*Máximo 2 caracteres',
                ]);

                if ($validator->fails()) {
                    DB::rollback();
                    Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 754), null);
                    return response()->json($validator->errors(), 422);
                }

                // Actualiza los datos de la reunión
                $reunion = Gcm_Reunion::findOrFail($id_reunion);
                $reunion->id_tipo_reunion = $id_tipo_reunion;
                $reunion->descripcion = $data['descripcion'];
                $reunion->fecha_reunion = $data['fecha_reunion'];
                $reunion->hora = $data['hora'];
                $reunion->quorum = $data['quorum'];
                $reunion->id_acta = null;
                $reunion->estado = $data['estado'];

                $picture = 0;

                if ($request->hasFile('programacion')) {

                    $subcarpeta = 'archivos_reunion/' . $id_reunion;
                    $carpeta = 'storage/app/public/' . $subcarpeta;

                    if (!file_exists($carpeta)) {
                        mkdir($carpeta, 0777, true);
                    }

                    $file = $request['programacion'];
                    $extension = $file->getClientOriginalExtension();

                    if (in_array(strtoupper($extension), $extensionesFirmaProgramacion)) {
                        $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $extension;
                        $reunion->programacion = $subcarpeta . '/' . $picture;
                        $file->move(storage_path('app/public/archivos_reunion/' . $id_reunion), $picture);
                        chmod(storage_path('app/public/archivos_reunion/' . $id_reunion . '/' . $picture), 0777);
                    } else {
                        DB::rollback();
                        Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'La extensión del archivo no es permitida'), null);
                        return response()->json(['error' => 'La extensión del archivo no es permitida'], 500);
                    }

                } else {
                    $reunion->programacion = $data['programacion'];
                }
                $response = $reunion->save();
            }

            // Cambia el estado de los convocados de una reunion por eliminado
            Gcm_Convocado_Reunion::changeStatus(Gcm_Convocado_Reunion::where([['id_reunion', '=', $id_reunion], ['estado', 1]])->get(), 0);

            $convocados = json_decode($request->convocados, true);

            // Registra los convocados en el tipo Participante o Representante Legal
            for ($i = 0; $i < count($convocados); $i++) {

                $convocadosCollection = collect($convocados[$i]);

                $relacion_nueva = new Gcm_Relacion;

                // Consulta si el convocado ya fue registrado como recurso
                $recurso_existe = DB::table('gcm_recursos')->where('identificacion', '=', $convocados[$i]['identificacion'])->first();

                // En caso de no existir el recurso, lo registra
                if (!$recurso_existe) {

                    $validator = Validator::make($convocadosCollection->all(), [
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
                        Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 835), null);
                        return response()->json($validator->errors(), 422);
                    }

                    $recurso_nuevo = new Gcm_Recurso;
                    $recurso_nuevo->identificacion = $convocados[$i]['identificacion'];
                    $recurso_nuevo->nombre = $convocados[$i]['nombre'];
                    $recurso_nuevo->telefono = $convocados[$i]['telefono'];
                    $recurso_nuevo->correo = $convocados[$i]['correo'];
                    $recurso_nuevo->estado = 1;

                    $response = $recurso_nuevo->save();

                    // Consulta si el rol ya esta registrado
                    $rol_existe = DB::table('gcm_roles')->where('descripcion', '=', $convocados[$i]['rol'])->first();

                    // En caso de no existir el rol, lo registra
                    if (!$rol_existe) {

                        $validator = Validator::make($convocadosCollection->all(), [
                            'rol' => 'required|max:100',
                        ], [
                            'rol.required' => '*Rellena este campo',
                            'rol.max' => '*Maximo 100 caracteres',
                        ]);

                        if ($validator->fails()) {
                            DB::rollback();
                            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 863), null);
                            return response()->json($validator->errors(), 422);
                        }

                        $rol_nuevo = new Gcm_Rol;
                        $rol_nuevo->descripcion = $convocados[$i]['rol'];
                        $rol_nuevo->relacion = null;
                        $rol_nuevo->estado = 1;

                        $response = $rol_nuevo->save();

                        // Registra la relación nueva
                        $relacion_nueva->id_grupo = $data['id_grupo'];
                        $relacion_nueva->id_rol = $rol_nuevo->id_rol;
                        $relacion_nueva->id_recurso = $recurso_nuevo->id_recurso;
                        $relacion_nueva->estado = 1;

                        $response = $relacion_nueva->save();

                    } else { // En caso de que si exista el rol

                        // Si existe le actualiza el estado en caso que este este inactivo
                        if ($rol_existe->estado === '0') {

                            $validator = Validator::make($convocadosCollection->all(), [
                                'rol' => 'required|max:100',
                            ], [
                                'rol.required' => '*Rellena este campo',
                                'rol.max' => '*Maximo 100 caracteres',
                            ]);

                            if ($validator->fails()) {
                                DB::rollback();
                                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 896), null);
                                return response()->json($validator->errors(), 422);
                            }

                            $rol = Gcm_Rol::findOrFail($rol_existe->id_rol);
                            $rol->descripcion = $convocados[$i]['rol'];
                            $rol->relacion = null;
                            $rol->estado = 1;

                            $response = $rol->save();
                        }

                        // Consulta el id del rol donde la descripcion sea igual a la que se trae en el convocado
                        $rol = DB::table('gcm_roles')->select('id_rol')->where('descripcion', '=', $convocados[$i]['rol'])->first();

                        // Registra la relación nueva
                        $relacion_nueva->id_grupo = $data['id_grupo'];
                        $relacion_nueva->id_rol = $rol->id_rol;
                        $relacion_nueva->id_recurso = $recurso_nuevo->id_recurso;
                        $relacion_nueva->estado = 1;

                        $response = $relacion_nueva->save();
                    }

                    // Registra el convocado con nit y razon social
                    if ($convocados[$i]['tipo'] === '2') {

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

                        if ($validator->fails()) {
                            DB::rollback();
                            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 936), null);
                            return response()->json($validator->errors(), 422);
                        }

                        $fechaActual = date('Y-m-d H:i:s');

                        $convocado = new Gcm_Convocado_Reunion;
                        $convocado->id_reunion = $id_reunion;
                        $convocado->representacion = null;
                        $convocado->id_relacion = $relacion_nueva->id_relacion;
                        $convocado->fecha = $fechaActual;
                        $convocado->tipo = $convocados[$i]['tipo'];
                        $convocado->nit = $convocados[$i]['nit'];
                        $convocado->razon_social = $convocados[$i]['razon_social'];
                        $convocado->participacion = null;
                        $convocado->soporte = null;
                        $convocado->fecha_envio_invitacion = null;
                        $convocado->firma = $convocados[$i]['firma'];
                        $convocado->acta = $convocados[$i]['acta'];
                        $convocado->estado = 1;

                        $response = $convocado->save();

                        array_push($array_id_convocados, $convocado->id_convocado_reunion);
                    } else {

                        // Registra el convocado sin nit ni razon social
                        $validator = Validator::make($convocadosCollection->all(), [
                            'tipo' => 'required|max:2',
                        ], [
                            'tipo.required' => '*Rellena este campo',
                            'tipo.max' => '*Maximo 2 caracteres',
                        ]);

                        if ($validator->fails()) {
                            DB::rollback();
                            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 969), null);
                            return response()->json($validator->errors(), 422);
                        }

                        $fechaActual = date('Y-m-d H:i:s');

                        $convocado = new Gcm_Convocado_Reunion;
                        $convocado->id_reunion = $id_reunion;
                        $convocado->representacion = null;
                        $convocado->id_relacion = $relacion_nueva->id_relacion;
                        $convocado->fecha = $fechaActual;
                        $convocado->tipo = $convocados[$i]['tipo'];
                        $convocado->nit = null;
                        $convocado->razon_social = null;
                        $convocado->participacion = null;
                        $convocado->soporte = null;
                        $convocado->fecha_envio_invitacion = null;
                        $convocado->firma = $convocados[$i]['firma'];
                        $convocado->acta = $convocados[$i]['acta'];
                        $convocado->estado = 1;

                        $response = $convocado->save();

                        array_push($array_id_convocados, $convocado->id_convocado_reunion);
                    }

                } else { // En caso de que si exista el recurso

                    // Actualiza el recurso con los nuevos datos, en caso de enviar el campo de telefono vacio entonces se guarda el antiguo valor del telefono y en caso de que el estado del recurso sea inactivo entonces lo activa

                    $validator = Validator::make($convocadosCollection->all(), [
                        'nombre' => 'required|max:255',
                        'telefono' => 'max:20',
                        'correo' => 'required|max:255|email',
                    ], [
                        'nombre.required' => '*Rellena este campo',
                        'nombre.max' => '*Maximo 255 caracteres',
                        'telefono.max' => '*Maximo 20 caracteres',
                        'correo.required' => '*Rellena este campo',
                        'correo.max' => '*Máximo 255 caracteres',
                        'correo.email' => '*Formato de correo invalido',
                    ]);

                    if ($validator->fails()) {
                        DB::rollback();
                        Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1011), null);
                        return response()->json($validator->errors(), 422);
                    }

                    $recurso = Gcm_Recurso::findOrFail($recurso_existe->id_recurso);
                    $recurso->nombre = $convocados[$i]['nombre'];
                    if (!empty($convocados[$i]['telefono'])) {$recurso->telefono = $convocados[$i]['telefono'];}
                    $recurso->correo = $convocados[$i]['correo'];
                    if ($recurso_existe->estado === '0') {
                        $recurso->estado = 1;
                    }
                    $response = $recurso->save();

                    // Cosulta si existe el rol ya esta registrado
                    $rol_existe = DB::table('gcm_roles')->where('descripcion', '=', $convocados[$i]['rol'])->first();

                    // En caso de que no exista el rol, lo registra
                    if (!$rol_existe) {

                        $validator = Validator::make($convocadosCollection->all(), [
                            'rol' => 'required|max:100',
                        ], [
                            'rol.required' => '*Rellena este campo',
                            'rol.max' => '*Maximo 100 caracteres',
                        ]);

                        if ($validator->fails()) {
                            DB::rollback();
                            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1039), null);
                            return response()->json($validator->errors(), 422);
                        }

                        $rol_nuevo = new Gcm_Rol;
                        $rol_nuevo->descripcion = $convocados[$i]['rol'];
                        $rol_nuevo->relacion = null;
                        $rol_nuevo->estado = 1;

                        $response = $rol_nuevo->save();

                        // Registra la relación
                        $relacion_nueva->id_grupo = $data['id_grupo'];
                        $relacion_nueva->id_rol = $rol_nuevo->id_rol;
                        $relacion_nueva->id_recurso = $recurso_existe->id_recurso;
                        $relacion_nueva->estado = 1;

                        $response = $relacion_nueva->save();

                    } else { // En caso de que si exista el rol

                        if ($rol_existe->estado === '0') {

                            $validator = Validator::make($convocadosCollection->all(), [
                                'rol' => 'required|max:100',
                            ], [
                                'rol.required' => '*Rellena este campo',
                                'rol.max' => '*Maximo 100 caracteres',
                            ]);

                            if ($validator->fails()) {
                                DB::rollback();
                                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1071), null);
                                return response()->json($validator->errors(), 422);
                            }

                            $rol = Gcm_Rol::findOrFail($rol_existe->id_rol);
                            $rol->descripcion = $convocados[$i]['rol'];
                            $rol->relacion = null;
                            $rol->estado = 1;

                            $response = $rol->save();
                        }
                        // Consulta si ya existe una relacion registrada con los datos subministrados(id_grupo, id_rol, id_recurso)
                        $relacion_existe = DB::table('gcm_relaciones')->where([['id_grupo', '=', $data['id_grupo']], ['id_rol', '=', $rol_existe->id_rol], ['id_recurso', '=', $recurso_existe->id_recurso]])->first();

                        // En caso de que no exista la relación, la registra
                        if (!$relacion_existe) {
                            $relacion_nueva->id_grupo = $data['id_grupo'];
                            $relacion_nueva->id_rol = $rol_existe->id_rol;
                            $relacion_nueva->id_recurso = $recurso_existe->id_recurso;
                            $relacion_nueva->estado = 1;

                            $response = $relacion_nueva->save();
                        } else { // En caso de que si exista la relación y el estado de estado sea inactivo entonces lo actualiza a activo

                            if ($relacion_existe->estado === '0') {
                                $relacion = Gcm_Relacion::findOrFail($relacion_existe->id_relacion);
                                $relacion->id_grupo = $data['id_grupo'];
                                $relacion->id_rol = $rol_existe->id_rol;
                                $relacion->id_recurso = $recurso_existe->id_recurso;
                                $relacion->estado = 1;

                                $response = $relacion->save();
                            }
                            // En caso de que si exista la relación actualiza el valor de id_relacion por el que trae el convocado
                            $relacion_nueva->id_relacion = $relacion_existe->id_relacion;
                        }
                    }
                    if ($convocados[$i]['tipo'] === '2') {

                        // Registra el convocado con nit y razon social
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

                        if ($validator->fails()) {
                            DB::rollback();
                            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1124), null);
                            return response()->json($validator->errors(), 422);
                        }

                        $fechaActual = date('Y-m-d H:i:s');

                        $convocado = new Gcm_Convocado_Reunion;
                        $convocado->id_reunion = $id_reunion;
                        $convocado->representacion = null;
                        $convocado->id_relacion = $relacion_nueva->id_relacion;
                        $convocado->fecha = $fechaActual;
                        $convocado->tipo = $convocados[$i]['tipo'];
                        $convocado->nit = $convocados[$i]['nit'];
                        $convocado->razon_social = $convocados[$i]['razon_social'];
                        $convocado->participacion = null;
                        $convocado->soporte = null;
                        $convocado->fecha_envio_invitacion = null;
                        $convocado->firma = $convocados[$i]['firma'];
                        $convocado->acta = $convocados[$i]['acta'];
                        $convocado->estado = 1;

                        $response = $convocado->save();

                        // Añade al array_id_convocados los id de cada convocado que vaya agregando con el fin de poder enviarlos en la funcion de enviar correos
                        array_push($array_id_convocados, $convocado->id_convocado_reunion);
                    } else {

                        // Registra el convocado sin nit ni razon social
                        $validator = Validator::make($convocadosCollection->all(), [
                            'tipo' => 'required|max:2',
                        ], [
                            'tipo.required' => '*Rellena este campo',
                            'tipo.max' => '*Maximo 2 caracteres',
                        ]);

                        if ($validator->fails()) {
                            DB::rollback();
                            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1158), null);
                            return response()->json($validator->errors(), 422);
                        }

                        $fechaActual = date('Y-m-d H:i:s');

                        $convocado = new Gcm_Convocado_Reunion;
                        $convocado->id_reunion = $id_reunion;
                        $convocado->representacion = null;
                        $convocado->id_relacion = $relacion_nueva->id_relacion;
                        $convocado->fecha = $fechaActual;
                        $convocado->tipo = $convocados[$i]['tipo'];
                        $convocado->nit = null;
                        $convocado->razon_social = null;
                        $convocado->participacion = null;
                        $convocado->soporte = null;
                        $convocado->fecha_envio_invitacion = null;
                        $convocado->firma = $convocados[$i]['firma'];
                        $convocado->acta = $convocados[$i]['acta'];
                        $convocado->estado = 1;

                        $response = $convocado->save();

                        // Añade al array_id_convocados los id de cada convocado que vaya agregando con el fin de poder enviarlos en la funcion de enviar correos
                        array_push($array_id_convocados, $convocado->id_convocado_reunion);
                    }
                }
            }

            // Array de extensiones que se van a permitir en la inserción de archivos de la programación
            $extensiones = array('PNG', 'JPG', 'JPEG', 'GIF', 'XLSX', 'CSV', 'PDF', 'DOCX', 'TXT', 'PPTX', 'SVG', 'PDF');

            // Elimina de la tabla de gcm_archivos_programacion los registros donde el id_reunion del programa sea igual al que se esta enviando
            // DB::table('gcm_archivos_programacion as ap')->join('gcm_programacion as p', 'p.id_programa', '=', 'ap.id_programa')->where('p.id_reunion', '=', $id_reunion)->delete();
            Gcm_Archivo_Programacion::groupDeletion(Gcm_Archivo_Programacion::join('gcm_programacion as p', 'p.id_programa', '=', 'gcm_archivos_programacion.id_programa')->where('p.id_reunion', '=', $id_reunion)->get());

            // Elimina de la tabla de programacion las opciones con un id_reunion en comun(las opciones tienen relacion por eso se valida que no sean nulas)
            // DB::table('gcm_programacion')->where('id_reunion', '=', $id_reunion)->whereNotNull('relacion')->delete();
            // Gcm_Programacion::groupDeletion(Gcm_Programacion::where('id_reunion', '=', $id_reunion)->whereNotNull('relacion')->get());
            Gcm_Programacion::changeStatus(Gcm_Programacion::where('id_reunion', '=', $id_reunion)->whereNotNull('relacion')->get(), 4);

            // Vacea la tabla de programacion los programas con un id_reunion en comun
            // DB::table('gcm_programacion')->where('id_reunion', '=', $id_reunion)->delete();
            Gcm_Programacion::changeStatus(Gcm_Programacion::where('id_reunion', '=', $id_reunion)->get(), 4);

            // Valida en el parametro de titulo si vienen programas para registrar
            if (isset($request->titulo)) {

                for ($i = 0; $i < count($request->titulo); $i++) {

                    $validator = Validator::make($request->all(), [
                        'titulo' => 'required|max:500',
                        'descripcion' => 'max:500',
                    ], [
                        'titulo.required' => '*Rellena este campo',
                        'titulo.max' => '*Máximo 500 caracteres',
                        'descripcion.max' => '*Maximo 500 caracteres',
                    ]);

                    if ($validator->fails()) {
                        DB::rollback();
                        Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1216), null);
                        return response()->json($validator->errors(), 422);
                    }

                    // Registra la programación de una reunion
                    $programa_nuevo = new Gcm_Programacion;
                    $programa_nuevo->id_reunion = $this->stringNullToNull($id_reunion);
                    $programa_nuevo->titulo = $this->stringNullToNull($request->titulo[$i]);
                    $programa_nuevo->descripcion = $this->stringNullToNull($request->descripcion[$i]);
                    $programa_nuevo->orden = $i + 1;
                    $programa_nuevo->numeracion = $this->stringNullToNull($request->numeracion[$i]);
                    $programa_nuevo->tipo = $this->stringNullToNull($request->tipo[$i]);
                    $programa_nuevo->relacion = null;
                    $programa_nuevo->id_rol_acta = null;
                    $programa_nuevo->id_convocado_reunion = null;
                    $programa_nuevo->estado = $request->estado[$i] ? $request->estado[$i] : 0;

                    $response = $programa_nuevo->save();

                    $picture = 0;

                    if ($request->hasFile('file' . $i)) {
                        $request['file' . $i] = array_values($request['file' . $i]);

                        $subcarpeta = 'archivos_reunion/' . $id_reunion;
                        $carpeta = 'storage/app/public/' . $subcarpeta;

                        if (!file_exists($carpeta)) {
                            mkdir($carpeta, 0777, true);
                        }

                        for ($j = 0; $j < count($request['file' . $i]); $j++) {

                            $archivo_nuevo = new Gcm_Archivo_Programacion;
                            $file = $request['file' . $i][$j];
                            $extension = $file->getClientOriginalExtension();

                            if (in_array(strtoupper($extension), $extensiones)) {
                                $archivo_nuevo->id_programa = $programa_nuevo->id_programa;
                                $archivo_nuevo->descripcion = $file->getClientOriginalName();
                                $archivo_nuevo->peso = filesize($file);
                                $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $extension;
                                $archivo_nuevo->url = $subcarpeta . '/' . $picture;
                                $file->move(storage_path('app/public/archivos_reunion/' . $id_reunion), $picture);
                                chmod(storage_path('app/public/archivos_reunion/' . $id_reunion . '/' . $picture), 0777);

                                $response = $archivo_nuevo->save();
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
                            $archivo_nuevo = new Gcm_Archivo_Programacion;
                            $file = json_decode($request['file_viejo' . $i][$j]);
                            $archivo_nuevo->id_programa = $programa_nuevo->id_programa;
                            $archivo_nuevo->descripcion = $file->name;
                            $archivo_nuevo->peso = $file->size;
                            $archivo_nuevo->url = $file->url;
                            $response = $archivo_nuevo->save();
                        }
                    }

                    // Valida que si vengan opciones para registrar
                    if (isset($request['opcion_titulo' . $i])) {

                        for ($j = 0; $j < count($request['opcion_titulo' . $i]); $j++) {

                            $validator = Validator::make($request->all(), [
                                'titulo' => 'required|max:500',
                                'descripcion' => 'max:500',
                            ], [
                                'titulo.required' => '*Rellena este campo',
                                'titulo.max' => '*Máximo 500 caracteres',
                                'descripcion.max' => '*Maximo 500 caracteres',
                            ]);

                            if ($validator->fails()) {
                                DB::rollback();
                                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors(), 'linea' => 1300), null);
                                return response()->json($validator->errors(), 422);
                            }

                            // Registra las opciones
                            $opcion_nueva = new Gcm_Programacion;
                            $opcion_nueva->id_reunion = $this->stringNullToNull($id_reunion);
                            $opcion_nueva->titulo = $this->stringNullToNull($request['opcion_titulo' . $i][$j]);
                            $opcion_nueva->descripcion = $this->stringNullToNull($request['opcion_descripcion' . $i][$j]);
                            $opcion_nueva->orden = $j + 1;
                            $opcion_nueva->numeracion = 1;
                            $opcion_nueva->tipo = 0;
                            $opcion_nueva->relacion = $this->stringNullToNull($programa_nuevo->id_programa);
                            $opcion_nueva->id_rol_acta = null;
                            $opcion_nueva->id_convocado_reunion = null;
                            $opcion_nueva->estado = $request['opcion_estado' . $i][$j] ? $request['opcion_estado' . $i][$j] : 0;

                            $response = $opcion_nueva->save();

                            $picture = 0;

                            if ($request->hasFile('opcion_file' . $i . '_' . $j)) {
                                $request['opcion_file' . $i . '_' . $j] = array_values($request['opcion_file' . $i . '_' . $j]);

                                $subcarpeta = 'archivos_reunion/' . $id_reunion;
                                $carpeta = 'storage/app/public/' . $subcarpeta;

                                if (!file_exists($carpeta)) {
                                    mkdir($carpeta, 0777, true);
                                }

                                for ($k = 0; $k < count($request['opcion_file' . $i . '_' . $j]); $k++) {

                                    $archivo_opcion_nuevo = new Gcm_Archivo_Programacion;
                                    $opcion_file = $request['opcion_file' . $i . '_' . $j][$k];
                                    $opcion_extension = $opcion_file->getClientOriginalExtension();

                                    if (in_array(strtoupper($opcion_extension), $extensiones)) {
                                        $archivo_opcion_nuevo->id_programa = $opcion_nueva->id_programa;
                                        $archivo_opcion_nuevo->descripcion = $opcion_file->getClientOriginalName();
                                        $archivo_opcion_nuevo->peso = filesize($opcion_file);
                                        $picture = substr(md5(microtime()), rand(0, 31 - 8), 8) . '.' . $opcion_extension;
                                        $archivo_opcion_nuevo->url = $subcarpeta . '/' . $picture;
                                        $opcion_file->move(storage_path('app/public/archivos_reunion/' . $id_reunion), $picture);
                                        chmod(storage_path('app/public/archivos_reunion/' . $id_reunion . '/' . $picture), 0777);

                                        $response = $archivo_opcion_nuevo->save();
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
                                    $archivo_opcion_nuevo = new Gcm_Archivo_Programacion;
                                    $opcion_file = json_decode($request['opcion_file_viejo' . $i . '_' . $j][$k]);
                                    $archivo_opcion_nuevo->id_programa = $opcion_nueva->id_programa;
                                    $archivo_opcion_nuevo->descripcion = $opcion_file->name;
                                    $archivo_opcion_nuevo->peso = $opcion_file->size;
                                    $archivo_opcion_nuevo->url = $opcion_file->url;
                                    $response = $archivo_opcion_nuevo->save();
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(["response" => $response, 'data' => $array_id_convocados, 'id_reunion' => $id_reunion], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage() . $th->getLine()], 500);
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
     * Realizo el envio de un correo electronico a los convocados de una reunion
     *
     * @param Request Aqui toda la información de la reunion, de los convocados y los programas, pero solo se toma los convocados
     * @return void Retorna un mensaje donde se evidencia si el envio de los correos fue exitoso o fallo
     */
    public function enviarCorreos(Request $request)
    {

        try {
            $programas = [];
            $encrypt = new Encrypt();

            $reunion = json_decode($request->reunion, true);
            $convocados = json_decode($request->convocados, true);
            $array_id_convocados = json_decode($request->array_id_convocados, true);

            // Aqui realizo un array_map con el objetivo de obtener solo el correo del objeto que llega y que este se almacene en un array nuevo
            $correosOrganizados = array_map(function ($row) {
                return $row['correo'];
            }, $convocados);

            if (isset($request->titulo)) {
                for ($i = 0; $i < count($request->titulo); $i++) {
                    array_push($programas, [
                        'titulo' => $request->titulo[$i],
                        'orden' => $i + 1,
                    ]);
                }
            }

            for ($i = 0; $i < count($convocados); $i++) {
                $convocado = Gcm_Convocado_Reunion::findOrFail($array_id_convocados[$i]);
                $fechaActual = date('Y-m-d H:i:s');
                $convocado->fecha_envio_invitacion = $fechaActual;
                $response = $convocado->save();

                $valorEncriptado = $encrypt->encriptar($array_id_convocados[$i]);
                $detalle = [
                    'nombre' => $convocados[$i]['nombre'],
                    'titulo' => $reunion['titulo'],
                    'descripcion' => $reunion['descripcion'],
                    'fecha_reunion' => $reunion['fecha_reunion'],
                    'hora' => $reunion['hora'],
                    'programas' => $programas,
                    'url' => 'gcmeet.com/public/acceso-reunion/acceso/' . $valorEncriptado,
                ];
                Mail::to($convocados[$i]['correo'])->send(new TestMail($detalle));
            }
            Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Envio del primer correo a los convocados de una reunion', 'Correos' => $correosOrganizados), null);
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
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
            $document = new PDF([
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
            $document = new PDF([
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

}
