<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Models\Gcm_Reunion;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Convocado_Reunion;
use App\Models\Gcm_Programa;
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
            $base = Gcm_Programa::where('id_reunion', $id_reunion)->get()->toArray();

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
            $queryInvitados = DB::table('gcm_convocados_reunion')
            ->where('tipo', 1)
            ->where('id_reunion', $id_reunion);

            $convocados = DB::query()->fromSub(function ($query) use ($queryInvitados, $id_reunion) {
                $query->from('gcm_convocados_reunion AS cr')
                ->leftJoin(DB::raw('gcm_relaciones AS rl'), 'cr.id_relacion', '=', 'rl.id_relacion')
                ->leftJoin(DB::raw('gcm_recursos AS rs'), 'rl.id_recurso', '=', 'rs.id_recurso')
                ->leftJoin(DB::raw('gcm_roles AS rls'), 'rl.id_rol', '=', 'rls.id_rol')
                ->where('tipo', 0)
                ->where('id_reunion', $id_reunion)
                ->unionAll($queryInvitados)->select([
                    'cr.id_convocado_reunion', 'cr.id_reunion',
                    'cr.id_usuario', 'cr.id_relacion', 'cr.fecha',
                    'cr.tipo', 'rs.identificacion', 'rs.correo',
                    'rs.razon_social', DB::raw('rls.descripcion AS rol'),
                    'rl.participacion', 'rs.telefono'
                ]);
            }, 'convocados')
            ->leftJoin(DB::raw('gcm_recursos AS rs'), 'convocados.identificacion', '=', 'rs.representante')
            ->get([DB::raw('convocados.*, rs.identificacion AS nit, rs.razon_social AS entity')]);

            return $convocados;
        }


    // MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT MEET-MANAGEMENT
    
        /**
         * Consulta todos los recursos registrados
         */
        public function getRecursos() {

            $recursos = DB::table('gcm_recursos as personas')
            ->leftJoinSub(function($query) {
                $query->from('gcm_recursos as sub')
                ->select('sub.identificacion', 'sub.razon_social', 'sub.representante')
                ->where('sub.tipo_persona', '=', 1);
            }, 'entidades', function ($join) {
                $join->on('personas.id_recurso', '=', 'entidades.representante');
            })->leftJoinSub(function($query) {
                $query->from('gcm_convocados_reunion as cr1')
                ->join('gcm_relaciones as rls1', 'rls1.id_relacion', '=', 'cr1.id_relacion')
                ->join('gcm_recursos as rcs1', 'rcs1.id_recurso', '=', 'rls1.id_recurso')
                ->select('rcs1.identificacion', DB::raw('MAX(id_convocado_reunion) as ultima_invitacion'))
                ->where('cr1.tipo', '=', '0')
                ->groupBy('rcs1.identificacion');
            }, 'invitacion', function ($join) {
                $join->on('personas.identificacion', '=', 'invitacion.identificacion');
            })->leftJoin('gcm_convocados_reunion as cr', 'cr.id_convocado_reunion', '=', 'invitacion.ultima_invitacion')
            ->leftJoin('gcm_relaciones as rcs', 'rcs.id_relacion', '=', 'cr.id_relacion')
            ->leftJoin('gcm_roles as rls', 'rls.id_rol', '=', 'rcs.id_rol')
            ->where([['personas.tipo_persona', '=', '0'], ['personas.estado', '=', '1']])
            ->select('personas.id_recurso', 
            'personas.id_usuario', 
            'personas.identificacion',
            'personas.razon_social',
            'personas.telefono',
            'personas.correo',
            DB::raw('IF (entidades.identificacion IS NULL, "0", "2") as tipo'), 
            'entidades.identificacion as nit', 
            'entidades.razon_social as entity',
            'rcs.participacion',
            'rls.descripcion as rol')->get();
            
            return $recursos;
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
            $reunion->lugar = $data['lugar'];
            $reunion->estado = 0;

            $response = $reunion->save();
            
            // Vacea la tabla de convocados con un id_reunion en comun
            DB::table('gcm_convocados_reunion')->where('id_reunion', '=', $data['id_reunion'])->delete();
            
            // Registra los convocados en el tipo Invitado
            $convocadosI = json_decode($request->convocadosI, true);
            for ($i=0; $i < count($convocadosI); $i++) {
                
                $convocado = new Gcm_Convocado_Reunion;
                $convocado->id_reunion = $convocadosI[$i]['id_reunion'];
                $convocado->id_usuario = $convocadosI[$i]['id_usuario'];
                $convocado->id_relacion = null;
                $convocado->tipo = 1;
                $convocado->identificacion = $convocadosI[$i]['identificacion'];
                $convocado->razon_social = $convocadosI[$i]['razon_social'];
                $convocado->correo = $convocadosI[$i]['correo'];
                $convocado->rol = $convocadosI[$i]['rol'];
                $convocado->telefono = $convocadosI[$i]['telefono'];
                $convocado->participacion = 0;

                $response = $convocado->save();
            }

            // Registra los convocados en el tipo Participante o Representante Legal
            $convocadosA = json_decode($request->convocadosA, true);
            for ($i=0; $i < count($convocadosA); $i++) {
                $relacion_nueva = new Gcm_Relacion;
                $recurso_existe = DB::table('gcm_recursos')->where('identificacion', '=', $convocadosA[$i]['identificacion'])->first();

                    // En caso de no existir el recurso, lo registra
                    if(!$recurso_existe) {

                        $recurso_nuevo = new Gcm_Recurso;
                        $recurso_nuevo->id_usuario = $convocadosA[$i]['id_usuario'];
                        $recurso_nuevo->tipo_persona = 0;
                        $recurso_nuevo->identificacion = $convocadosA[$i]['identificacion'];
                        $recurso_nuevo->razon_social = $convocadosA[$i]['razon_social'];
                        $recurso_nuevo->telefono = $convocadosA[$i]['telefono'];
                        $recurso_nuevo->correo = $convocadosA[$i]['correo'];
                        $recurso_nuevo->representante = null;
                        $recurso_nuevo->estado = 1;

                        $response = $recurso_nuevo->save();

                        if ($convocadosA[$i]['tipo'] == '2') {

                            $entidad_existe = DB::table('gcm_recursos')->where('identificacion', '=', $convocadosA[$i]['nit'])->first();

                            if (!$entidad_existe) {
                                $entidad_nueva = new Gcm_Recurso;
                                $entidad_nueva->id_usuario = $convocadosA[$i]['id_usuario'];
                                $entidad_nueva->tipo_persona = 1;
                                $entidad_nueva->identificacion = $convocadosA[$i]['nit'];
                                $entidad_nueva->razon_social = $convocadosA[$i]['entity'];
                                $entidad_nueva->telefono = null;
                                $entidad_nueva->correo = null;
                                $entidad_nueva->representante = $recurso_nuevo->id_recurso;
                                $entidad_nueva->estado = 1;

                                $response = $entidad_nueva->save();
                            } else {
                                DB::rollback();
                                return response()->json(["error" => 'La entidad ingresada ya tiene un representante'], 500);
                            }
                        }

                        $rol_existe = DB::table('gcm_roles')->where('descripcion', '=', $convocadosA[$i]['rol'])->first();

                        // En caso de no existir el rol, lo registra
                        if(!$rol_existe) {
                            $rol_nuevo = new Gcm_Rol;
                            $rol_nuevo->id_usuario = $convocadosA[$i]['id_usuario'];
                            $rol_nuevo->descripcion = $convocadosA[$i]['rol'];
                            $rol_nuevo->relacion = null;
                            $rol_nuevo->estado = 1;

                            $response = $rol_nuevo->save();

                            // Registra la relación nueva
                            $relacion_nueva->id_grupo = $data['id_grupo'];
                            $relacion_nueva->id_rol = $rol_nuevo->id_rol;
                            $relacion_nueva->id_recurso = $recurso_nuevo->id_recurso;
                            $relacion_nueva->participacion = 0;
                            $relacion_nueva->estado = 1;
                            
                            $response = $relacion_nueva->save();

                        } else { // En caso de si exista el rol

                            $rol = DB::table('gcm_roles')->select('id_rol')->where('descripcion', '=', $convocadosA[$i]['rol'])->first();

                            // Registra la relación nueva
                            $relacion_nueva->id_grupo =  $data['id_grupo'];
                            $relacion_nueva->id_rol = $rol->id_rol;
                            $relacion_nueva->id_recurso = $recurso_nuevo->id_recurso;
                            $relacion_nueva->participacion = 0;
                            $relacion_nueva->estado = 1;
                            
                            $response = $relacion_nueva->save();
                        }

                        // Registra el convocado
                        $convocado = new Gcm_Convocado_Reunion;
                        $convocado->id_reunion = $convocadosA[$i]['id_reunion'];
                        $convocado->id_usuario = $convocadosA[$i]['id_usuario'];
                        $convocado->id_relacion = $relacion_nueva->id_relacion;
                        $convocado->tipo = 0;
                        $convocado->identificacion = null;
                        $convocado->razon_social = null;
                        $convocado->correo = null;
                        $convocado->rol = null;
                        $convocado->telefono = null;
                        $convocado->participacion = 0;
        
                        $response = $convocado->save();

                    } else { // En caso de que si exista el recurso

                        if ($convocadosA[$i]['tipo'] == 2) {
                        
                            $representante_existe = DB::table('gcm_recursos as personas')
                                ->leftJoinSub(function($query) {
                                    $query->from('gcm_recursos as sub')
                                    ->select('sub.identificacion', 'sub.razon_social', 'sub.representante')
                                    ->where('sub.tipo_persona', '=', 1);
                                }, 'entidades', function ($join) {
                                    $join->on('personas.id_recurso', '=', 'entidades.representante');
                                })->leftJoinSub(function($query) {
                                    $query->from('gcm_convocados_reunion as cr1')
                                    ->join('gcm_relaciones as rls1', 'rls1.id_relacion', '=', 'cr1.id_relacion')
                                    ->join('gcm_recursos as rcs1', 'rcs1.id_recurso', '=', 'rls1.id_recurso')
                                    ->select('rcs1.identificacion', DB::raw('MAX(id_convocado_reunion) as ultima_invitacion'))
                                    ->where('cr1.tipo', '=', '0')
                                    ->groupBy('rcs1.identificacion');
                                }, 'invitacion', function ($join) {
                                    $join->on('personas.identificacion', '=', 'invitacion.identificacion');
                                })->leftJoin('gcm_convocados_reunion as cr', 'cr.id_convocado_reunion', '=', 'invitacion.ultima_invitacion')
                                ->leftJoin('gcm_relaciones as rcs', 'rcs.id_relacion', '=', 'cr.id_relacion')
                                ->leftJoin('gcm_roles as rls', 'rls.id_rol', '=', 'rcs.id_rol')
                                ->where([['personas.tipo_persona', '=', '0'], ['personas.estado', '=', '1'], ['personas.identificacion', '=', $convocadosA[$i]['identificacion']]])
                                ->select('personas.id_recurso', 
                                'personas.id_usuario', 
                                'personas.identificacion',
                                'personas.razon_social',
                                'personas.telefono',
                                'personas.correo',
                                DB::raw('IF (entidades.identificacion IS NULL, "0", "2") as tipo'), 
                                'entidades.identificacion as nit', 
                                'entidades.razon_social as entity',
                                'rcs.participacion',
                                'rls.descripcion as rol')->first();
                            
                            if (isset($representante_existe) && isset($representante_existe->nit) && $representante_existe->nit !== $convocadosA[$i]['nit']) {

                                DB::rollback();
                                return response()->json(["error" => 'La persona seleccionada ya es representante legal de la entidad: '.$representante_existe->nit.' - '.$representante_existe->entity ], 500);
                                
                            }

                            $entidad_existe = DB::table('gcm_recursos as personas')
                                ->leftJoinSub(function($query) {
                                    $query->from('gcm_recursos as sub')
                                    ->select('sub.identificacion', 'sub.razon_social', 'sub.representante')
                                    ->where('sub.tipo_persona', '=', 1);
                                }, 'entidades', function ($join) {
                                    $join->on('personas.id_recurso', '=', 'entidades.representante');
                                })->leftJoinSub(function($query) {
                                    $query->from('gcm_convocados_reunion as cr1')
                                    ->join('gcm_relaciones as rls1', 'rls1.id_relacion', '=', 'cr1.id_relacion')
                                    ->join('gcm_recursos as rcs1', 'rcs1.id_recurso', '=', 'rls1.id_recurso')
                                    ->select('rcs1.identificacion', DB::raw('MAX(id_convocado_reunion) as ultima_invitacion'))
                                    ->where('cr1.tipo', '=', '0')
                                    ->groupBy('rcs1.identificacion');
                                }, 'invitacion', function ($join) {
                                    $join->on('personas.identificacion', '=', 'invitacion.identificacion');
                                })->leftJoin('gcm_convocados_reunion as cr', 'cr.id_convocado_reunion', '=', 'invitacion.ultima_invitacion')
                                ->leftJoin('gcm_relaciones as rcs', 'rcs.id_relacion', '=', 'cr.id_relacion')
                                ->leftJoin('gcm_roles as rls', 'rls.id_rol', '=', 'rcs.id_rol')
                                ->where([['personas.tipo_persona', '=', '0'], ['personas.estado', '=', '1'], ['entidades.identificacion', '=', $convocadosA[$i]['nit']]])
                                ->select('personas.id_recurso',
                                'personas.id_usuario',
                                'personas.identificacion',
                                'personas.razon_social',
                                'personas.telefono',
                                'personas.correo',
                                DB::raw('IF (entidades.identificacion IS NULL, "0", "2") as tipo'), 
                                'entidades.identificacion as nit', 
                                'entidades.razon_social as entity',
                                'rcs.participacion',
                                'rls.descripcion as rol')->first();
                                
                            if (isset($entidad_existe) && $entidad_existe->identificacion !== $convocadosA[$i]['identificacion']) {

                                DB::rollback();
                                return response()->json(["error" => 'La entidad seleccionada ya tiene un representante legal: '.$entidad_existe->identificacion.' - '.$entidad_existe->razon_social ], 500);

                            }

                            if(!isset($entidad_existe)) {
                                $entidad_nueva = new Gcm_Recurso;
                                $entidad_nueva->id_usuario = $convocadosA[$i]['id_usuario'];
                                $entidad_nueva->tipo_persona = 1;
                                $entidad_nueva->identificacion = $convocadosA[$i]['nit'];
                                $entidad_nueva->razon_social = $convocadosA[$i]['entity'];
                                $entidad_nueva->telefono = null;
                                $entidad_nueva->correo = null;
                                $entidad_nueva->representante = $recurso_existe->id_recurso;
                                $entidad_nueva->estado = 1;
    
                                $response = $entidad_nueva->save();
                            }
                                
                        }

                        $rol_existe = DB::table('gcm_roles')->where('descripcion', '=', $convocadosA[$i]['rol'])->first();

                        // En caso de que no exista el rol, lo registra
                        if(!$rol_existe) {

                            $rol_nuevo = new Gcm_Rol;
                            $rol_nuevo->id_usuario = $convocadosA[$i]['id_usuario'];
                            $rol_nuevo->descripcion = $convocadosA[$i]['rol'];
                            $rol_nuevo->relacion = null;
                            $rol_nuevo->estado = 1;

                            $response = $rol_nuevo->save();

                            // Registra la relación
                            $relacion_nueva->id_grupo =  $data['id_grupo'];
                            $relacion_nueva->id_rol = $rol_nuevo->id_rol;
                            $relacion_nueva->id_recurso = $recurso_existe->id_recurso;
                            $relacion_nueva->participacion = 0;
                            $relacion_nueva->estado = 1;
                            
                            $response = $relacion_nueva->save();

                        } else {// En caso de que si exista el rol

                            $relacion_existe = DB::table('gcm_relaciones')->where([['id_grupo', '=',  $data['id_grupo']], ['id_rol', '=', $rol_existe->id_rol], ['id_recurso', '=', $recurso_existe->id_recurso]])->first();
                            
                            // En caso de que no exista la relación, la registra
                            if(!$relacion_existe) {

                                $relacion_nueva->id_grupo = $data['id_grupo'];
                                $relacion_nueva->id_rol = $rol_existe->id_rol;
                                $relacion_nueva->id_recurso = $recurso_existe->id_recurso;
                                $relacion_nueva->participacion = 0;
                                $relacion_nueva->estado = 1;
                                
                                $response = $relacion_nueva->save();
                            } else { // En caso de que si exista la relación actualiza el valor de id_relacion por el que trae el convocado
                                $relacion_nueva->id_relacion = $relacion_existe->id_relacion;
                            }

                        }

                        // Registra el convocado
                        $convocado = new Gcm_Convocado_Reunion;
                        $convocado->id_reunion = $convocadosA[$i]['id_reunion'];
                        $convocado->id_usuario = $convocadosA[$i]['id_usuario'];
                        $convocado->id_relacion = $relacion_nueva->id_relacion;
                        $convocado->tipo = 0;
                        $convocado->identificacion = null;
                        $convocado->razon_social = null;
                        $convocado->correo = null;
                        $convocado->rol = null;
                        $convocado->telefono = null;
                        $convocado->participacion = 0;
        
                        $response = $convocado->save();
                    }

            }

            // Vacea la tabla de preogramas con un id_reunion en comun
            DB::table('gcm_programas')->where('id_reunion', '=', $data['id_reunion'])->delete();

            // Registra los programas de una reunion
            $descripcion = json_decode($request->descripcion, true);
            print_r($descripcion[0]['descripcion']);
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
