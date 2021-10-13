<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Models\Gcm_Reunion;
use App\Models\Gcm_Convocado_Reunion;
use App\Models\Gcm_Pregunta;
use App\Models\Gcm_Grupo;
use App\Models\Gcm_Rol;
use App\Models\Gcm_Usuario;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Tipo_Reunion;
use Illuminate\Support\Facades\DB;
use Validator;

class Gcm_Reunion_Controller extends Controller
{

    /**
     * Trae todos los grupos registrados por un usuario con un estado en comun
     */
    public function getGrupos($id_usuario) {
        $grupos = Gcm_Grupo::where('estado', '1')->get();
        return $grupos;
    }

    /**
     * Consulta todos los roles que tienen una relacion donde el grupo tiene en comun con la tabla tipos de reuniones que tiene en comun con la tabla reuniones.
     */
    public function getRoles($id_reunion) {
        $roles = Gcm_Rol::join('gcm_relaciones', 'gcm_roles.id_rol', '=', 'gcm_relaciones.id_rol')
        ->join('gcm_grupos', 'gcm_relaciones.id_grupo', '=', 'gcm_grupos.id_grupo')
        ->join('gcm_tipo_reuniones', 'gcm_grupos.id_grupo', '=', 'gcm_tipo_reuniones.id_grupo')
        ->join('gcm_reuniones', 'gcm_tipo_reuniones.id_tipo_reunion', '=', 'gcm_reuniones.id_tipo_reunion')
        ->select('gcm_roles.*')
        ->where('gcm_reuniones.id_reunion', $id_reunion)->get();
        return $roles;
    }

    /**
     * Consulta todos los recursos registrados
     */
    public function getRecursos() {
        $recursos = Gcm_Recurso::all();
        return $recursos;
    }

    /**
     * Consulta los datos de un recurso en especifico
     */
    public function autocompletar($identificacion) {
        $recurso = Gcm_Recurso::leftJoin('gcm_relaciones', 'gcm_recursos.id_recurso', '=', 'gcm_relaciones.id_recurso')
        ->leftJoin('gcm_roles', 'gcm_relaciones.id_rol', '=', 'gcm_roles.id_rol')
        ->select('gcm_recursos.*', 'gcm_roles.descripcion AS rol')
        ->where('gcm_recursos.identificacion','=', $identificacion)->get();
        return $recurso;
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
     * Consulta todas las reuniones con un tipo de reunion que tiene un grupo en comun
     */
    public function getTiposReuniones($id_grupo) {
        $tiposReuniones = Gcm_Tipo_Reunion::where('gcm_tipo_reuniones.id_grupo', '=', $id_grupo)->get();
        return $tiposReuniones;
    }

    

    /****************************************
     * TODO SOBRE REUNIONES
     */

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
    public function getReunion($id_reunion) {
        $reunion = Gcm_Reunion::join('gcm_tipo_reuniones', 'gcm_reuniones.id_tipo_reunion', '=', 'gcm_tipo_reuniones.id_tipo_reunion')
        ->select('gcm_reuniones.*', 'gcm_tipo_reuniones.titulo', 'gcm_tipo_reuniones.id_grupo')
        ->where('id_reunion', $id_reunion)->get();
        return $reunion;
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

    /*******************************************
     * TODO SOBRE CONVOCADOS A UNA REUNION
     */

    /**
     * Trae todos los convocados registrados en una reunion y lo utilizo para mostrar la cantidad de convocados en la vista principal de meets
     */
    public function getConvocados($id_reunion) {
        $convocados = Gcm_Convocado_Reunion::where([['id_reunion', $id_reunion]])->get();
        return $convocados;
    }

    /**
     * Trae todos los convocados registrados en una reunion
     */
    public function getConvocadosA($id_reunion) {
        $convocadosA = Gcm_Convocado_Reunion::where([['id_reunion', $id_reunion], ['tipo', '0']])->get();
        return $convocadosA;
    }

    /**
     * Trae todos los convocados registrados en una reunion
     */
    public function getConvocadosI($id_reunion) {
        $convocadosI = Gcm_Convocado_Reunion::where([['id_reunion', $id_reunion], ['tipo', '1']])->get();
        return $convocadosI;
    }

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

    /*******************************************
     * TODO SOBRE PREGUNTAS DE UNA REUNION
     */

     /**
     * Trae todas las preguntas registradas en una reunion
     */ 
    public function getPreguntas($id_reunion) {
        $base = Gcm_Pregunta::where('id_reunion', $id_reunion)->get()->toArray();

        $preguntas = array_filter($base, function($item){
            return $item['relacion'] === null || $item['relacion'] === '';
        });

        $preguntas = array_values($preguntas);

        $preguntas = array_map(function($item) use($base) {
            $item['opciones'] = array_filter($base, function ($elm) use($item) {
                return $elm['relacion'] === $item['id_pregunta'];
            });
            $item['opciones'] = array_values($item['opciones']);

            return $item;

        }, $preguntas);

        return $preguntas;
    }

    /**
     * Trae los datos de una pregunta en especifico
     */
    public function getPregunta($id_pregunta) {
        $pregunta = Gcm_Pregunta::where('id_pregunta', $id_pregunta)->get();
        return $pregunta;
    }

    /**
     * Elimina una pregunta
     */
    public function eliminarPregunta($id_pregunta) {

        $pregunta = Gcm_Pregunta::findOrFail($id_pregunta);
        $res;

        try {
            $pregunta->delete();

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

            $data = json_decode($request->reunion, true);

            $id_tipo_reunion = $data['id_tipo_reunion'];

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
            
            $reunion = Gcm_Reunion::findOrFail($data['id_reunion']);
            $reunion->id_tipo_reunion = $id_tipo_reunion;
            $reunion->descripcion = $data['descripcion'];
            $reunion->fecha_reunion = $data['fecha_reunion'];
            $reunion->hora = $data['hora'];
            $reunion->lugar = $data['lugar'];
            $reunion->estado = 0;

            $response = $reunion->save();

            $convocados = json_decode($request->convocados, true);
            for ($i=0; $i < count($convocados); $i++) {
                DB::table('gcm_convocados_reunion')->where('id_reunion', '=', $convocados[$i]['id_reunion'])->delete();
            }
        
            $convocadosI = json_decode($request->convocadosI, true);
            for ($i=0; $i < count($convocadosI); $i++) {
                
                $convocado = new Gcm_Convocado_Reunion;
                $convocado->id_reunion = $convocadosI[$i]['id_reunion'];
                $convocado->id_usuario = $convocadosI[$i]['id_usuario'];
                $convocado->id_relacion = $convocadosI[$i]['id_relacion'];
                $convocado->tipo = $convocadosI[$i]['tipo'];
                $convocado->identificacion = $convocadosI[$i]['identificacion'];
                $convocado->razon_social = $convocadosI[$i]['razon_social'];
                $convocado->correo = $convocadosI[$i]['correo'];
                $convocado->rol = $convocadosI[$i]['rol'];
                $convocado->telefono = $convocadosI[$i]['telefono'];
                $convocado->participacion = 0;
                // $convocado->nit = $convocadosI[$i]['nit'];
                // $convocado->entity = $convocadosI[$i]['entity'];

                $response = $convocado->save();
            }
            
            DB::commit();
            return response()->json(["response" => $response], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

}
