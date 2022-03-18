<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Models\Gcm_Recurso;
use App\Models\Gcm_Usuario;
use App\Models\Gcm_Relacion;
use App\Models\Gcm_Tipo_Reunion;
use Validator;
use DB;

class Gcm_Recurso_Controller extends Controller
{

    /**
     * TODO SOBRE RECURSOS
     */

    /**
     * Trae todos los recursos registrados con un usuario en comun
     */
    public function listarRecursos($id_usuario) {
        $recursos = Gcm_Recurso::where('id_usuario', $id_usuario)->get();
        return $recursos;
    }

    /**
     * Trae los datos de un usuario en especifico para autocompletar en el registro de un recurso
     */ //DB::RAW me permite hacer consulta en lenguaje SQL
    public function autocompletar($identificacion) {
        $recurso = Gcm_Usuario::where('identificacion', $identificacion)->get([DB::raw('CONCAT(nombres, " ", apellidos) AS razon_social'), 'telefono', 'correo']);
        return $recurso;
    }

    /**
     * Trae todos los recursos registrados con un usuario y un estado en comun
     */
    public function listarRecursosSelect($id_usuario) {
        $recursos = Gcm_Recurso::where([['id_usuario', $id_usuario], ['estado', '1']])->get();
        return $recursos;
    }

    /**
     * Trae todos los recursos registrados que pueden ser convocados a una reunion
     */
    public function getRecursosReunion($id_tipo_reunion) {

        // $subQuery = Gcm_Tipo_Reunion::where('id_tipo_reunion', $id_tipo_reunion)->get(['id_grupo'])[0];

        $relaciones = Gcm_Relacion::join('gcm_recursos', 'gcm_relaciones.id_recurso', '=', 'gcm_recursos.id_recurso')
        ->join('gcm_roles', 'gcm_relaciones.id_rol', '=', 'gcm_roles.id_rol')
        // ->where([['gcm_relaciones.id_grupo', $subQuery->id_grupo], ['gcm_relaciones.estado', 1]])
        ->where([['gcm_relaciones.id_grupo', '=', function($qry) use ($id_tipo_reunion) {
            $qry->from('gcm_tipo_reuniones')->where('id_tipo_reunion', $id_tipo_reunion)->select('id_grupo');
        }], ['gcm_relaciones.estado', 1]])
        ->select('gcm_relaciones.id_recurso', 'gcm_relaciones.id_relacion', 'gcm_recursos.razon_social', 'gcm_roles.descripcion', 'gcm_recursos.correo')
        ->get();

        return $relaciones;
    }

    /**
     * Registra un nuevo recurso en la base de datos
     */
    public function agregarRecurso(Request $request) {

        $validator = Validator::make($request->all(), [
            'tipo_persona'=>'required|max:2',
            'identificacion'=>'required|max:20',
            'razon_social'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'telefono'=>'max:20',
            'correo'=>'required|email|max:255',
            'estado'=>'required|max:2',
        ],

        [
            'tipo_persona.required' => '*Rellena este campo',
            'identificacion.required' => '*Rellena este campo',
            'razon_social.required' => '*Rellena este campo',
            'razon_social.max' => '*Máximo 50 caracteres',
            'razon_social.regex' => '*Ingresa sólo letras',
            'telefono.required' => '*Rellena este campo',
            'correo.required' => '*Rellena este campo',
            'correo.email' => '*Ingresa un e-mail válido',
            'estado.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $recursoNuevo = new Gcm_Recurso;
            $recursoNuevo->id_usuario = $request->usuario;
            $recursoNuevo->tipo_persona = $request->tipo_persona;
            $recursoNuevo->identificacion = $request->identificacion;
            $recursoNuevo->razon_social = $request->razon_social;
            $recursoNuevo->telefono = $request->telefono;
            $recursoNuevo->correo = $request->correo;
            $recursoNuevo->representante = $request->representante;
            $recursoNuevo->estado = $request->estado;
    
            $response = $recursoNuevo->save();
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae todos los datos de un recurso
     */
    public function getRecurso($id_recurso) {
        
        $recurso = Gcm_Recurso::where('id_recurso', $id_recurso)->get();
        return $recurso;
    }

    /**
     * Actualiza los datos de un recurso
     */
    public function editarRecurso($id_recurso, Request $request) {

        $validator = Validator::make($request->all(), [
            'razon_social'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'telefono'=>'max:20',
            'correo'=>'required|email|max:255',
            'estado'=>'required|max:2',
        ],

        [
            'razon_social.required' => '*Rellena este campo',
            'razon_social.max' => '*Máximo 50 caracteres',
            'razon_social.regex' => '*Ingresa sólo letras',
            'telefono.required' => '*Rellena este campo',
            'correo.required' => '*Rellena este campo',
            'correo.email' => '*Ingresa un e-mail válido',
            'estado.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $recurso = Gcm_Recurso::findOrFail($id_recurso);
            $recurso->razon_social = $request->razon_social;
            $recurso->telefono = $request->telefono;
            $recurso->correo = $request->correo;
            $recurso->representante = $request->representante;
            $recurso->estado = $request->estado;
    
            $response = $recurso->save();
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el campo estado de un recurso
    */
    public function cambiarEstadoRecurso(Request $request) {

        $recurso = Gcm_Recurso::findOrFail($request->id_recurso);
        $res;
        try {
            $recurso->estado = $request->estado;
            $recurso->save();

            $res = response()->json(["response" => 'se cambio'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }
        return $res;
    }

    /**
     * Elimina un recurso de la base de datos
     */
    public function eliminarRecurso($id_recurso) {

        $recurso = Gcm_Recurso::findOrFail($id_recurso);
        $res;

        try {
            $recurso->delete();

            $res = response()->json(["response" => 'Se eliminó'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }

        return $res;
    }


    /**
     * TODO PARA LAS RELACIONES
     */

     /**
     * Registra una nueva relacion en la base de datos
     */
    public function agregarRelacion(Request $request) {

        $validator = Validator::make($request->all(), [
            'id_grupo'=>'required|max:11',
            'id_rol'=>'required|max:11',
            'participacion'=>'required',
            'estado'=>'required|max:2',
        ],

        [
            'id_grupo.required' => '*Rellena este campo',
            'id_rol.required' => '*Rellena este campo',
            'participacion.required' => '*Rellena este campo',
            'estado.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $relacionNueva = new Gcm_Relacion;
            $relacionNueva->id_grupo = $request->id_grupo;
            $relacionNueva->id_rol = $request->id_rol;
            $relacionNueva->id_recurso = $request->id_recurso;
            $relacionNueva->participacion = $request->participacion;
            $relacionNueva->estado = $request->estado;
    
            $response = $relacionNueva->save();
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

     /**
     * Trae todos los datos de una relacion
     */
    public function getRelacion($id_relacion) {
        
        $relacion = Gcm_Relacion::where('id_relacion', $id_relacion)->get();
        return $relacion;
    }

    /**
     * Trae todas las relaciones registradas con un recurso en comun
     */
    public function traerRelaciones($id_recurso) {
        $relaciones = Gcm_Relacion::where('id_recurso', $id_recurso)->get();
        return $relaciones;
    }

    /**
     * Actualiza todos los datos de una relacion
     */
    public function editarRelacion($id_relacion, Request $request) {

        $validator = Validator::make($request->all(), [
            'id_grupo'=>'required|max:11',
            'id_rol'=>'required|max:11',
            'participacion'=>'required',
            'estado'=>'required|max:2',
        ],

        [
            'id_grupo.required' => '*Rellena este campo',
            'id_rol.required' => '*Rellena este campo',
            'participacion.required' => '*Rellena este campo',
            'estado.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {

            $relacion = Gcm_Relacion::findOrFail($id_relacion);
            $relacion->id_grupo = $request->id_grupo;
            $relacion->id_rol = $request->id_rol;
            $relacion->id_recurso = $request->id_recurso;
            $relacion->participacion = $request->participacion;
            $relacion->estado = $request->estado;
    
            $response = $relacion->save();
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el campo estado de una relacion
    */
    public function cambiarEstadoRelacion(Request $request) {
        $relacion = Gcm_Relacion::findOrFail($request->id_relacion);
        $res;
        if ( $request->estado == '1' ){
            try {
                $relacion->estado = $request->estado;
                $relacion->save();
                $res = response()->json(["response" => 'se cambio'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
        } else {
            try {
                $relacion->estado = $request->estado;
                $relacion->save();
                $res = response()->json(["response" => 'Se cambio'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
        }
        return $res;
    }

    /**
     * Elimina una relacion de la base de datos
     */
    public function eliminarRelacion($id_relacion) {
        $relacion = Gcm_Relacion::findOrFail($id_relacion);
        $res;
        try {
            $relacion->delete();
            $res = response()->json(["response" => 'Se eliminó'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }
        return $res;
    }
}
