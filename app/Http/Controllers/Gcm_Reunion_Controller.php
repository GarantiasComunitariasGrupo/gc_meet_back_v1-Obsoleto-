<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Models\Gcm_Reunion;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Convocado_Reunion;
use App\Models\Gcm_Programacion;
use App\Models\Gcm_Grupo;
use App\Models\Gcm_Rol;
use App\Models\Gcm_Usuario;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Tipo_Reunion;
use Illuminate\Support\Facades\DB;
use Validator;

class Gcm_Reunion_Controller extends Controller
{

    // MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS MEETS

        /**
         * Trae todos los grupos registrados por un usuario con un estado en comun
         */
        public function getGrupos($id_usuario) {
            $grupos = Gcm_Grupo::where('estado', '1')->get();
            return $grupos;
        }

        /**
         * Consulta todas las reuniones con un tipo de reunion que tiene un grupo en comun
         */
        public function getReuniones($id_grupo) {
            $reuniones = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
            ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo')
            ->where('gcm_tipo_reuniones.id_grupo', '=', $id_grupo)
            ->get();
            return $reuniones;
        }

        /**
         * Trae todos los datos de una reunión en especifico
         */
        public function getReunion($id_reunion) {
            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
            ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo', 'gcm_tipo_reuniones.id_grupo')
            ->where('id_reunion', $id_reunion)->get();
            return $reunion;
        }

        /**
        * Trae todas los programas registradas en una reunion
        */ 
        public function getProgramas($id_reunion) {
            $base = Gcm_Programacion::where('id_reunion', $id_reunion)->get()->toArray();

            $programas = array_filter($base, function($item){
                return $item['relacion'] === null || $item['relacion'] === '';
            });

            $programas = array_values($programas);

            $programas = array_map(function($item) use($base) {
                $item['opciones'] = array_filter($base, function ($elm) use($item) {
                    return $elm['relacion'] === $item['id_programa'];
                });
                $item['opciones'] = array_values($item['opciones']);

                return $item;

            }, $programas);

            return $programas;
        }

        /**
         * Trae todos los convocados registrados en una reunion y lo utilizo para mostrar la cantidad de convocados en la vista principal de meets
         */
        public function getConvocados($id_reunion) {

            $convocados = DB::table(DB::raw('gcm_convocados_reunion AS gcr'))
                ->join(DB::raw('gcm_relaciones AS grc'), 'gcr.id_relacion', '=', 'grc.id_relacion')
                ->join(DB::raw('gcm_recursos AS grs'), 'grc.id_recurso', '=', 'grs.id_recurso')
                ->join(DB::raw('gcm_roles AS grl'), 'grc.id_rol', '=', 'grl.id_rol')
                ->where(DB::raw('gcr.id_reunion'), $id_reunion)
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
                ])->get();
            return $convocados;
        }


    // MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT
    
        /**
         * Consulta todos los recursos registrados
         */
        public function _getRecursos() {

            $recursos = DB::table('gcm_recursos as personas')
            ->leftJoinSub(function($query) {
                $query->from('gcm_convocados_reunion as cr1')
                ->join('gcm_relaciones as rls1', 'rls1.id_relacion', '=', 'cr1.id_relacion')
                ->join('gcm_recursos as rcs1', 'rcs1.id_recurso', '=', 'rls1.id_recurso')
                ->select('rcs1.identificacion', DB::raw('MAX(id_convocado_reunion) as ultima_invitacion'))
                ->groupBy('rcs1.identificacion');
            }, 'invitacion', function ($join) {
                $join->on('personas.identificacion', '=', 'invitacion.identificacion');
            })->leftJoin('gcm_convocados_reunion as cr', 'cr.id_convocado_reunion', '=', 'invitacion.ultima_invitacion')
            ->leftJoin('gcm_relaciones as rcs', 'rcs.id_relacion', '=', 'cr.id_relacion')
            ->leftJoin('gcm_roles as rls', 'rls.id_rol', '=', 'rcs.id_rol')
            ->select('personas.id_recurso',
                'personas.identificacion',
                'personas.nombre',
                'personas.telefono',
                'personas.correo', 
                'rls.descripcion as rol',
                'cr.tipo')
            ->where([['personas.estado', '=', '1']])->get();
            
            return $recursos;

            // $recursos = DB::table('gcm_recursos as personas')
            // ->leftJoinSub(function($query) {
            //     $query->from('gcm_recursos as sub')
            //     ->select('sub.identificacion', 'sub.razon_social', 'sub.representante')
            //     ->where('sub.tipo_persona', '=', 1);
            // }, 'entidades', function ($join) {
            //     $join->on('personas.id_recurso', '=', 'entidades.representante');
            // })->leftJoinSub(function($query) {
            //     $query->from('gcm_convocados_reunion as cr1')
            //     ->join('gcm_relaciones as rls1', 'rls1.id_relacion', '=', 'cr1.id_relacion')
            //     ->join('gcm_recursos as rcs1', 'rcs1.id_recurso', '=', 'rls1.id_recurso')
            //     ->select('rcs1.identificacion', DB::raw('MAX(id_convocado_reunion) as ultima_invitacion'))
            //     ->where('cr1.tipo', '=', '0')
            //     ->groupBy('rcs1.identificacion');
            // }, 'invitacion', function ($join) {
            //     $join->on('personas.identificacion', '=', 'invitacion.identificacion');
            // })->leftJoin('gcm_convocados_reunion as cr', 'cr.id_convocado_reunion', '=', 'invitacion.ultima_invitacion')
            // ->leftJoin('gcm_relaciones as rcs', 'rcs.id_relacion', '=', 'cr.id_relacion')
            // ->leftJoin('gcm_roles as rls', 'rls.id_rol', '=', 'rcs.id_rol')
            // ->where([['personas.tipo_persona', '=', '0'], ['personas.estado', '=', '1']])
            // ->select('personas.id_recurso', 
            // 'personas.id_usuario', 
            // 'personas.identificacion',
            // 'personas.razon_social',
            // 'personas.telefono',
            // 'personas.correo',
            // DB::raw('IF (entidades.identificacion IS NULL, "0", "2") as tipo'), 
            // 'entidades.identificacion as nit', 
            // 'entidades.razon_social as entity',
            // 'rcs.participacion',
            // 'rls.descripcion as rol')->get();
            
            // return $recursos;
        }

        public function getRecursos() {
            $grupo = 1;
            $getMaxFecha = function ($exceptions = [], $nullExceptions = []) use ($grupo) {
                // Consulta la máxima fecha de convocatoria de un recurso de acuerdo a las condiciones
                $statement = DB::table('gcm_convocados_reunion AS s1_crn')
                ->join('gcm_reuniones AS s1_rns', 's1_rns.id_reunion', 's1_crn.id_reunion')
                ->join('gcm_tipo_reuniones AS s1_trs', 's1_trs.id_tipo_reunion', 's1_rns.id_tipo_reunion')
                ->join('gcm_relaciones AS s1_rln', 's1_rln.id_relacion', 's1_crn.id_relacion')
                ->join('gcm_roles AS s1_rls', 's1_rls.id_rol', 's1_rln.id_rol')
                ->join('gcm_recursos AS s1_rcs', 's1_rcs.id_recurso', 's1_rln.id_recurso')
                ->whereNull('s1_crn.representacion')->where([
                    ['s1_trs.id_grupo', $grupo], ['s1_rcs.estado', 1],
                    ['s1_rls.estado', 1], ['s1_rln.estado', 1],
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
                });
                // Información de tablas
                foreach ($joins as $value) {
                    $statement->join($value[0], $value[1], $value[2]);
                }
                return $statement;
            };

            $roles = $getMaxFecha();
            $rol_recursos = $getInfoRecurso($roles, 'rol', [
                ['gcm_roles AS rls', 'rls.id_rol', 'rln.id_rol']
            ])->select('rln.id_recurso', 'rls.descripcion AS rol');

            $participacion = $getMaxFecha(['s1_rns.quorum' => 1]);
            $participacion_recursos = $getInfoRecurso($participacion, 'participacion')
            ->select('rln.id_recurso', 'crn.participacion');

            $entidad = $getMaxFecha([], ['s1_crn.nit']);
            $entidad_recursos = $getInfoRecurso($entidad, 'entidad')
            ->select('rln.id_recurso', 'crn.nit', 'crn.razon_social', 'crn.soporte');

            $respuesta = DB::table('gcm_recursos AS rcs')
            ->leftJoinSub($rol_recursos, 'rol_recurso', function($join) {
                $join->on('rol_recurso.id_recurso', 'rcs.id_recurso');
            })->leftJoinSub($participacion_recursos, 'participacion_recurso', function($join) {
                $join->on('participacion_recurso.id_recurso', 'rcs.id_recurso');
            })->leftJoinSub($entidad_recursos, 'entidad_recurso', function($join) {
                $join->on('entidad_recurso.id_recurso', 'rcs.id_recurso');
            })->select(
                'rcs.*', 
                'rol_recurso.rol', 
                'participacion_recurso.participacion', 
                DB::raw('IF(entidad_recurso.nit IS NULL, 0, 2) AS tipo'),
                'entidad_recurso.nit', 
                'entidad_recurso.razon_social', 
                'entidad_recurso.soporte'
            )->where('rcs.estado', 1)->get();

            return $respuesta;
        }

        /**
         * Consulta todos los roles que tienen una relacion donde el grupo tiene en comun con la tabla tipos de reuniones que tiene en comun con la tabla reuniones.
         */
        public function getRoles($id_reunion) {
            $roles = Gcm_Rol::join('gcm_relaciones', 'gcm_roles.id_rol', '=', 'gcm_relaciones.id_rol')
            ->join('gcm_tipo_reuniones', 'gcm_relaciones.id_grupo', '=', 'gcm_tipo_reuniones.id_grupo')
            ->join('gcm_reuniones', 'gcm_tipo_reuniones.id_tipo_reunion', '=', 'gcm_reuniones.id_tipo_reunion')
            ->leftJoin('gcm_roles as rl2', 'gcm_roles.relacion', '=', 'rl2.id_rol')
            ->select('gcm_roles.*', 'rl2.descripcion as nombre_relacion')
            ->where([['gcm_reuniones.id_reunion', $id_reunion], ['gcm_roles.estado', 1]])
            ->groupBy('gcm_roles.id_rol')->get();
            return $roles;
        }

        /**
         * Consulta todas las reuniones con un tipo de reunion que tiene un grupo en comun
         */
        public function getTiposReuniones($id_grupo) {
            $tiposReuniones = Gcm_Tipo_Reunion::where('gcm_tipo_reuniones.id_grupo', '=', $id_grupo)->get();
            return $tiposReuniones;
        }

    

    // REUNIONES REUNIONES REUNIONES REUNIONES REUNIONES REUNIONES REUNIONES REUNIONES REUNIONES REUNIONES REUNIONES

        /**
         * Consulta y trae todas las reuniones registradas con un grupo que tiene un usuario en comun
         */
        public function listarReuniones($id_usuario) {
            $reuniones = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
            ->join('gcm_grupos', 'gcm_tipo_reuniones.id_grupo', '=', 'gcm_grupos.id_grupo')
            ->select('gcm_reuniones.id_reunion', 'gcm_reuniones.id_tipo_reunion', 'gcm_reuniones.descripcion', 
            'gcm_reuniones.fecha_creacion', 'gcm_reuniones.fecha_reunion', 'gcm_reuniones.hora', 
            'gcm_reuniones.lugar', 'gcm_reuniones.quorum', 'gcm_reuniones.estado')
            ->where('gcm_grupos.id_usuario', '=', $id_usuario)
            ->get();
            return $reuniones;
        }

        /**
         * Trae todos los datos de una reunión en especifico
         */
        public function getReunionRegistrar($id_grupo) {
            $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
            ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo', 'gcm_tipo_reuniones.id_grupo')
            ->where('id_grupo', '=', $id_grupo)->get();
            return $reunion;
        }

        /**
        * Registra una nueva reunion
        */
        public function agregarReunion(Request $request) {

            $validator = Validator::make($request->all(), [
                'id_tipo_reunion'=>'required|max:2',
                'descripcion'=>'max:5000',
                'fecha_reunion'=>'required',
                'hora'=>'required',
                'lugar'=>'max:100',
                'quorum'=>'required|max:5',
                'estado'=>'required|max:2',
            ],

            [
                'id_tipo_reunion.required'=> '*Rellena este campo',
                'id_tipo_reunion.max'=> '*Maximo 2 caracteres',
                'descripcion.max' => '*Maximo 5000 caracteres',
                'fecha_reunion.required' => '*Rellena este campo',
                'hora.required' => '*Rellena este campo',
                'lugar.max' => '*Máximo 100 caracteres',
                'quorum.required' => '*Rellena este campo',
                'quorum.max' => '*Máximo 5 caracteres',
                'estado.required' => '*Rellena este campo',
                'estado.max'=> '*Maximo 2 caracteres',
            ]

            );

            if ($validator->fails()) {
                return response()->json($validator->messages(), 422);
            }

            try {

                $reunionNueva = new Gcm_Reunion;
                $reunionNueva->id_tipo_reunion = $request->id_tipo_reunion;
                $reunionNueva->descripcion = $request->descripcion;
                $reunionNueva->fecha_reunion = $request->fecha_reunion;
                $reunionNueva->hora = $request->hora;
                $reunionNueva->lugar = $request->lugar;
                $reunionNueva->quorum = $request->quorum;
                $reunionNueva->estado = $request->estado;
        
                $response = $reunionNueva->save();
                
                return response()->json(["response" => $response], 200);
            } catch (\Throwable $th) {
                return response()->json(["error" => $th->getMessage()], 500);
            }
        }

        /**
         * Actualiza los datos de una reunión en especifico
         */
        public function editarReunion($id_reunion, Request $request) {

            if($request->estado === '0' || $request->estado === '1' || $request->estado === '2' || $request->estado === '3') {

                $validator = Validator::make($request->all(), [
                    'id_tipo_reunion'=>'required|max:2',
                    'descripcion'=>'max:5000',
                    'fecha_reunion'=>'required',
                    'hora'=>'required',
                    'lugar'=>'max:100',
                    'quorum'=>'required|max:5',
                    'estado'=>'required|max:2',
                ],
        
                [
                    'id_tipo_reunion.required'=> '*Rellena este campo',
                    'id_tipo_reunion.max'=> '*Maximo 2 caracteres',
                    'descripcion.max' => '*Maximo 5000 caracteres',
                    'fecha_reunion.required' => '*Rellena este campo',
                    'hora.required' => '*Rellena este campo',
                    'lugar.max' => '*Máximo 100 caracteres',
                    'quorum.required' => '*Rellena este campo',
                    'quorum.max' => '*Máximo 5 caracteres',
                    'estado.required' => '*Rellena este campo',
                    'estado.max'=> '*Maximo 2 caracteres',
                ]
        
                );
        
                if ($validator->fails()) {
                    return response()->json($validator->messages(), 422);
                }
        
                try {
                    $reunion = Gcm_Reunion::findOrFail($id_reunion);
                    $reunion->id_tipo_reunion = $request->id_tipo_reunion;
                    $reunion->descripcion = $request->descripcion;
                    $reunion->fecha_reunion = $request->fecha_reunion;
                    $reunion->hora = $request->hora;
                    $reunion->lugar = $request->lugar;
                    $reunion->quorum = $request->quorum;
                    $reunion->estado = $request->estado;
            
                    $response = $reunion->save();
                    
                    return response()->json(["response" => $response], 200);
                } catch (\Throwable $th) {
                    return response()->json(["error" => $th->getMessage()], 500);
                }

            } else {

                $validator = Validator::make($request->all(), [
                    'fecha_reunion'=>'required',
                ],
                [
                    'fecha_reunion.required' => '*Rellena este campo',
                ]
        
                );
        
                if ($validator->fails()) {
                    return response()->json($validator->messages(), 422);
                }
        
                try {
                    $reunion = Gcm_Reunion::findOrFail($id_reunion);
                    $reunion->fecha_reunion = $request->fecha_reunion;
                    $reunion->estado = 0;
            
                    $response = $reunion->save();
                    
                    return response()->json(["response" => $response], 200);
                } catch (\Throwable $th) {
                    return response()->json(["error" => $th->getMessage()], 500);
                }

            }
        }

        /**
         * Actualiza el campo estado de una reunion
        */
        public function cambiarEstado(Request $request) {
            $reunion = Gcm_Reunion::findOrFail($request->id_reunion);
            $res;
            try {
                $reunion->estado = $request->estado;
                $reunion->save();

                $res = response()->json(["response" => 'se cambio'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
            return $res;
        }

        /**
         * Elimina una reunion
         */
        public function eliminarReunion($id_reunion) {
            $reunion = Gcm_Reunion::findOrFail($id_reunion);
            $res;
            try {
                $reunion->delete();
                $res = response()->json(["response" => 'Se eliminó'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
            return $res;
        }


    // CONVOCADOS CONVOCADOS CONVOCADOS CONVOCADOS CONVOCADOS CONVOCADOS CONVOCADOS CONVOCADOS CONVOCADOS 


        /**
         * Trae todos los datos de un convocado en especifico
         */
        public function getConvocado($id_convocado_reunion) {
            $convocado = Gcm_Convocado_Reunion::where('id_convocado_reunion', $id_convocado_reunion)->get();
            return $convocado;
        }

        /**
        * Registra tanto los convocados como los invitados a una reunion
        */
        public function agregarConvocados(Request $request) {

            DB::beginTransaction();

            try {
                
                for ($i = 0; $i < count($request->completados); $i++) {

                    $nuevoConvocado = new Gcm_Convocado_Reunion;
                    $nuevoConvocado->id_reunion = $request->completados[$i]['id_reunion'];
                    $nuevoConvocado->id_usuario = $request->completados[$i]['id_usuario'];
                    $nuevoConvocado->id_relacion = $request->completados[$i]['id_relacion'];
                    $nuevoConvocado->tipo = $request->completados[$i]['tipo'];
                    $nuevoConvocado->identificacion = $request->completados[$i]['identificacion'];
                    $nuevoConvocado->correo = $request->completados[$i]['correo'];
                    $nuevoConvocado->razon_social = $request->completados[$i]['razon_social'];
                    $nuevoConvocado->rol = $request->completados[$i]['rol'];
                    $nuevoConvocado->participacion = $request->completados[$i]['participacion'];

                    $response = $nuevoConvocado->save();

                }

                for ($j = 0; $j < count($request->invitados); $j++) {
                    $nuevoConvocado = new Gcm_Convocado_Reunion;
                    $nuevoConvocado->id_reunion = $request->invitados[$j]['id_reunion'];
                    $nuevoConvocado->id_usuario = $request->invitados[$j]['id_usuario'];
                    $nuevoConvocado->id_relacion = $request->invitados[$j]['id_relacion'];
                    $nuevoConvocado->tipo = $request->invitados[$j]['tipo'];
                    $nuevoConvocado->identificacion = $request->invitados[$j]['identificacion'];
                    $nuevoConvocado->correo = $request->invitados[$j]['correo'];
                    $nuevoConvocado->razon_social = $request->invitados[$j]['razon_social'];
                    $nuevoConvocado->rol = $request->invitados[$j]['rol'];
                    $nuevoConvocado->participacion = $request->invitados[$j]['participacion'];

                    $response = $nuevoConvocado->save();

                }

                DB::commit();
                return response()->json(["response" => $response], 200);
                
            } catch (\Throwable $th) {
                DB::rollback();
                return response()->json(["error" => $th->getMessage()], 500);
            }
        }

        /**
         * Elimina un convocado
         */
        public function eliminarConvocado($id_convocado_reunion) {
            $convocado = Gcm_Convocado_Reunion::findOrFail($id_convocado_reunion);
            $res;
            try {
                $convocado->delete();

                $res = response()->json(["response" => 'Se eliminó'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
            return $res;
        }


    // PROGRAMAS PROGRAMAS PROGRAMAS PROGRAMAS PROGRAMAS PROGRAMAS PROGRAMAS PROGRAMAS PROGRAMAS PROGRAMAS 

        /**
         * Trae los datos de un programa en especifico
         */
        public function getPrograma($id_programa) {
            $programa = Gcm_Programa::where('id_programa', $id_programa)->get();
            return $programa;
        }

        /**
         * Elimina una programa
         */
        public function eliminarPrograma($id_programa) {

            $programa = Gcm_Programa::findOrFail($id_programa);
            $res;

            try {
                $programa->delete();
                $res = response()->json(["response" => 'Se eliminó'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
            return $res;
        }


    // ACTUALIZAR REUNION COMPLETA

    public function editarReunionCompleta(Request $request) {
        // return response()->json($_FILES);

        DB::beginTransaction();
        try {

            // Actualiza los datos de la reunión
            $data = json_decode($request->reunion, true);

            $id_tipo_reunion = $data['id_tipo_reunion'];

            // En caso de no existir el tipo reunión, registra uno nuevo
            if(empty($id_tipo_reunion)) {
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

            // Actualiza los datos de la reunión
            $reunion = Gcm_Reunion::findOrFail($data['id_reunion']);
            $reunion->id_tipo_reunion = $id_tipo_reunion;
            $reunion->descripcion = $data['descripcion'];
            $reunion->fecha_reunion = $data['fecha_reunion'];
            $reunion->hora = $data['hora'];
            $reunion->quorum = $data['quorum'];
            $reunion->estado = $data['estado'];

            $response = $reunion->save();
            
            // // Vacea la tabla de convocados con un id_reunion en comun
            DB::table('gcm_convocados_reunion')->where('id_reunion', '=', $data['id_reunion'])->delete();
            
            // // Registra los convocados en el tipo Invitado
            // $convocadosI = json_decode($request->convocadosI, true);
            // for ($i=0; $i < count($convocadosI); $i++) {

            //     $convocado = new Gcm_Convocado_Reunion;
            //     $convocado->id_reunion = $convocadosI[$i]['id_reunion'];
            //     $convocado->representacion = null;
            //     $convocado->tipo = $convocadosI[$i]['tipo'];
            //     $convocado->id_relacion = 1;
            //     $convocado->nit = $convocadosI[$i]['nit'];
            //     $convocado->razon_social = null;
            //     $convocado->participacion = null;
            //     $convocado->soporte = null;

            //     $response = $convocado->save();
            // }

            // Registra los convocados en el tipo Participante o Representante Legal
            $convocados = json_decode($request->convocados, true);
            for ($i=0; $i < count($convocados); $i++) {
                $relacion_nueva = new Gcm_Relacion;
                $recurso_existe = DB::table('gcm_recursos')->where('identificacion', '=', $convocados[$i]['identificacion'])->first();
                
                    // En caso de no existir el recurso, lo registra
                    if(!$recurso_existe) {

                        $recurso_nuevo = new Gcm_Recurso;
                        $recurso_nuevo->identificacion = $convocados[$i]['identificacion'];
                        $recurso_nuevo->nombre = $convocados[$i]['nombre'];
                        $recurso_nuevo->telefono = $convocados[$i]['telefono'];
                        $recurso_nuevo->correo = $convocados[$i]['correo'];
                        $recurso_nuevo->estado = 1;

                        $response = $recurso_nuevo->save();

                        $rol_existe = DB::table('gcm_roles')->where('descripcion', '=', $convocados[$i]['rol'])->first();

                        // En caso de no existir el rol, lo registra
                        if(!$rol_existe) {
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

                        } else { // En caso de si exista el rol

                            if ($rol_existe->estado === '0') {
                                $rol = Gcm_Rol::findOrFail($rol_existe->id_rol);
                                $rol->descripcion = $convocados[$i]['rol'];
                                $rol->relacion = null;
                                $rol->estado = 1;

                                $response = $rol->save();
                            }

                            $rol = DB::table('gcm_roles')->select('id_rol')->where('descripcion', '=', $convocados[$i]['rol'])->first();

                            // Registra la relación nueva
                            $relacion_nueva->id_grupo =  $data['id_grupo'];
                            $relacion_nueva->id_rol = $rol->id_rol;
                            $relacion_nueva->id_recurso = $recurso_nuevo->id_recurso;
                            $relacion_nueva->estado = 1;
                            
                            $response = $relacion_nueva->save();
                        }

                        // Registra el convocado
                        $convocado = new Gcm_Convocado_Reunion;
                        $convocado->id_reunion = $convocados[$i]['id_reunion'];
                        $convocado->representacion = null;
                        $convocado->id_relacion = $relacion_nueva->id_relacion;
                        $convocado->tipo = $convocados[$i]['tipo'];
                        $convocado->nit = null;
                        $convocado->razon_social = null;
                        $convocado->participacion = null;
                        $convocado->soporte = null;
        
                        $response = $convocado->save();

                    } else { // En caso de que si exista el recurso

                        if ($recurso_existe->estado === '0') {

                            $recurso = Gcm_Recurso::findOrFail($recurso_existe->id_recurso);
                            $recurso->nombre = $convocados[$i]['nombre'];
                            $recurso->telefono = $convocados[$i]['telefono'];
                            $recurso->correo = $convocados[$i]['correo'];
                            $recurso->estado = 1;

                            $response = $recurso->save();
                        }

                        $rol_existe = DB::table('gcm_roles')->where('descripcion', '=', $convocados[$i]['rol'])->first();

                        // En caso de que no exista el rol, lo registra
                        if(!$rol_existe) {

                            $rol_nuevo = new Gcm_Rol;
                            $rol_nuevo->descripcion = $convocados[$i]['rol'];
                            $rol_nuevo->relacion = null;
                            $rol_nuevo->estado = 1;

                            $response = $rol_nuevo->save();

                            // Registra la relación
                            $relacion_nueva->id_grupo =  $data['id_grupo'];
                            $relacion_nueva->id_rol = $rol_nuevo->id_rol;
                            $relacion_nueva->id_recurso = $recurso_existe->id_recurso;
                            $relacion_nueva->estado = 1;
                            
                            $response = $relacion_nueva->save();

                        } else {// En caso de que si exista el rol

                            if ($rol_existe->estado === '0') {
                                $rol = Gcm_Rol::findOrFail($rol_existe->id_rol);
                                $rol->descripcion = $convocados[$i]['rol'];
                                $rol->relacion = null;
                                $rol->estado = 1;

                                $response = $rol->save();
                            }

                            $relacion_existe = DB::table('gcm_relaciones')->where([['id_grupo', '=',  $data['id_grupo']], ['id_rol', '=', $rol_existe->id_rol], ['id_recurso', '=', $recurso_existe->id_recurso]])->first();
                            
                            // En caso de que no exista la relación, la registra
                            if(!$relacion_existe) {

                                $relacion_nueva->id_grupo = $data['id_grupo'];
                                $relacion_nueva->id_rol = $rol_existe->id_rol;
                                $relacion_nueva->id_recurso = $recurso_existe->id_recurso;
                                $relacion_nueva->estado = 1;
                                
                                $response = $relacion_nueva->save();
                            } else { // En caso de que si exista la relación actualiza el valor de id_relacion por el que trae el convocado

                                if ($relacion_existe->estado === '0') {
                                    $relacion = Gcm_Relacion::findOrFail($relacion_existe->id_relacion);
                                    $relacion->id_grupo = $data['id_grupo'];
                                    $relacion->id_rol = $rol_existe->id_rol;
                                    $relacion->id_recurso = $recurso_existe->id_recurso;
                                    $relacion->estado = 1;
                                    
                                    $response = $relacion->save();
                                }

                                $relacion_nueva->id_relacion = $relacion_existe->id_relacion;
                            }

                        }

                        // Registra el convocado
                        $convocado = new Gcm_Convocado_Reunion;
                        $convocado->id_reunion = $convocados[$i]['id_reunion'];
                        $convocado->representacion = null;
                        $convocado->id_relacion = $relacion_nueva->id_relacion;
                        $convocado->tipo = $convocados[$i]['tipo'];
                        $convocado->nit = $convocados[$i]['nit'];
                        $convocado->razon_social = $convocados[$i]['razon_social'];
                        $convocado->participacion = null;
                        $convocado->soporte = null;

                        $response = $convocado->save();
                    }

            }

            // Vacea la tabla de preogramas con un id_reunion en comun
            // DB::table('gcm_programas')->where('id_reunion', '=', $data['id_reunion'])->delete();

            // Registra los programas de una reunion
            // // $descripcion = json_decode($request->descripcion, true);
            // print_r($descripcion[0]['descripcion']);
            // for ($i=0; $i < count($request->descripcion); $i++) {
            //     $programaNuevo = new Gcm_Programa;
            //     $programaNuevo->id_reunion = $request->id_reunion;
            //     $programaNuevo->descripcion = $request->descripcion;
            //     $programaNuevo->titulo = $request->titulo;
            //     $programaNuevo->orden = 0;
            //     $programaNuevo->tipo = $request->tipo;
            //     $programaNuevo->relacion = null;
            //     $programaNuevo->extra = null;

            //     $response = $programaNuevo->save();
            // }

            // Registra los programas de una reunion
            // $programas = json_decode($request->programas, true);
            // print_r($programas);
            // for ($i=0; $i < count($programas); $i++) {
            //     $programaNuevo = new Gcm_Programa;
            //     $programaNuevo->id_reunion = $programas[$i]['id_reunion'];
            //     $programaNuevo->descripcion = $programas[$i]['descripcion'];
            //     $programaNuevo->titulo = $programas[$i]['titulo'];
            //     $programaNuevo->orden = 0;
            //     $programaNuevo->tipo = $programas[$i]['tipo'];
            //     $programaNuevo->relacion = null;
            //     $programaNuevo->extra = null;

            //     $response = $programaNuevo->save();

                

            //     // if (count($programas[$i]['opciones']['listadoOpciones']) > 0) {
                    
            //     // }
            // }
            
            DB::commit();
            return response()->json(["response" => $response], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
}
