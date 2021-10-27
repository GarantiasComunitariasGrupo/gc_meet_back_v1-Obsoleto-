<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Models\Gcm_Tipo_Reunion;
use App\Models\Gcm_Restriccion_Representante;
use Validator;
use DB;

class Gcm_TipoReunion_Controller extends Controller
{
    /**
     * TODO SOBRE TIPO DE REUNIONES
     */

    /**
     * Trae todos los tipos de reuniones registrados con un grupo que tiene un usuario en comun
     */
    public function listarTiposReunion($id_usuario) {
        $tiposReunion = Gcm_Tipo_Reunion::join('gcm_grupos', 'gcm_tipo_reuniones.id_grupo', '=', 'gcm_grupos.id_grupo')
        ->select('gcm_tipo_reuniones.*')
        ->where('gcm_grupos.id_usuario', '=', $id_usuario)
        ->get();
        return $tiposReunion;
    }

    /**
     * Trae todos los tipos de reunion registrados en la bd donde su estado sea activo
     */
    public function listarTiposReunionSelect() {
        $tiposReunion = Gcm_Tipo_Reunion::where([['estado', '1']])->get();
        return $tiposReunion;
    }

    /**
     * Registra un nuevo tipo reunion en la base de datos
     */
    public function agregarTipoReunion(Request $request) {

        $validator = Validator::make($request->all(), [
            'id_grupo'=>'required',
            'titulo'=>'required|regex:/^[\pL\s\-]+$/u|max:255',
            'honorifico_participante'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'honorifico_invitado'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'honorifico_representante'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'imagen'=>'required|max:255',
            'estado'=>'required|max:2',
        ],

        [
            'id_grupo.required' => '*Rellena este campo',
            'titulo.required' => '*Rellena este campo',
            'titulo.max' => '*Máximo 255 caracteres',
            'titulo.regex' => '*Ingresa sólo letras',
            'honorifico_participante.required' => '*Rellena este campo',
            'honorifico_participante.max' => '*Máximo 50 caracteres',
            'honorifico_participante.regex' => '*Ingresa sólo letras',
            'honorifico_invitado.required' => '*Rellena este campo',
            'honorifico_invitado.max' => '*Máximo 50 caracteres',
            'honorifico_invitado.regex' => '*Ingresa sólo letras',
            'honorifico_representante.required' => '*Rellena este campo',
            'honorifico_representante.max' => '*Máximo 50 caracteres',
            'honorifico_representante.regex' => '*Ingresa sólo letras',
            'imagen.required' => '*Rellena este campo',
            'imagen.max' => '*Máximo 255 caracteres',
            'estado.required' => '*Rellena este campo',
        ]
        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $tipoReunionNew = new Gcm_Tipo_Reunion;
            $tipoReunionNew->id_grupo = $request->id_grupo;
            $tipoReunionNew->titulo = $request->titulo;
            $tipoReunionNew->honorifico_participante = $request->honorifico_participante;
            $tipoReunionNew->honorifico_invitado = $request->honorifico_invitado;
            $tipoReunionNew->honorifico_representante = $request->honorifico_representante;
            $tipoReunionNew->imagen = $request->imagen;
            $tipoReunionNew->estado = $request->estado;
    
            $response = $tipoReunionNew->save();
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae todos los datos de un tipo de reunión
     */
    public function getTipoReunion($id_tipo_reunion) {
        $tipoReunion = Gcm_Tipo_Reunion::where('id_tipo_reunion', $id_tipo_reunion)->get();
        return $tipoReunion;
    }

    /**
     * Actualiza los datos de un tipo de reunión
     */
    public function editarTipoReunion($id_tipo_reunion, Request $request) {

        $validator = Validator::make($request->all(), [
            'id_grupo'=>'required',
            'titulo'=>'required|regex:/^[\pL\s\-]+$/u|max:255',
            'honorifico_participante'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'honorifico_invitado'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'honorifico_representante'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'imagen'=>'required|max:255',
            'estado'=>'required|max:2',
        ],

        [
            'id_grupo.required' => '*Rellena este campo',
            'titulo.required' => '*Rellena este campo',
            'titulo.max' => '*Máximo 255 caracteres',
            'titulo.regex' => '*Ingresa sólo letras',
            'honorifico_participante.required' => '*Rellena este campo',
            'honorifico_participante.max' => '*Máximo 50 caracteres',
            'honorifico_participante.regex' => '*Ingresa sólo letras',
            'honorifico_invitado.required' => '*Rellena este campo',
            'honorifico_invitado.max' => '*Máximo 50 caracteres',
            'honorifico_invitado.regex' => '*Ingresa sólo letras',
            'honorifico_representante.required' => '*Rellena este campo',
            'honorifico_representante.max' => '*Máximo 50 caracteres',
            'honorifico_representante.regex' => '*Ingresa sólo letras',
            'imagen.required' => '*Rellena este campo',
            'imagen.max' => '*Máximo 255 caracteres',
            'estado.required' => '*Rellena este campo',
        ]
        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $tipoReunion = Gcm_Tipo_Reunion::findOrFail($id_tipo_reunion);
            $tipoReunion->id_grupo = $request->id_grupo;
            $tipoReunion->titulo = $request->titulo;
            $tipoReunion->honorifico_participante = $request->honorifico_participante;
            $tipoReunion->honorifico_invitado = $request->honorifico_invitado;
            $tipoReunion->honorifico_representante = $request->honorifico_representante;
            $tipoReunion->imagen = $request->imagen;
            $tipoReunion->estado = $request->estado;
    
            $response = $tipoReunion->save();
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el campo estado de un recurso
    */
    public function cambiarEstado(Request $request) {

        $tipoReunion = Gcm_Tipo_Reunion::findOrFail($request->id_tipo_reunion);
        $res;

        if ( $request->estado == '1' ){
            try {
                $tipoReunion->estado = $request->estado;
                $tipoReunion->save();
    
                $res = response()->json(["response" => 'se cambio'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
        } else {
            try {
                $tipoReunion->estado = $request->estado;
                $tipoReunion->save();
    
                $res = response()->json(["response" => 'Se cambio'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
        }

        return $res;
    }

    /**
     * Elimina un tipo de reunión de la base de datos
     */
    public function eliminarTipoReunion($id_tipo_reunion) {

        $tipoReunion = Gcm_Tipo_Reunion::findOrFail($id_tipo_reunion);
        $res;

        try {
            $tipoReunion->delete();

            $res = response()->json(["response" => 'Se eliminó'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }

        return $res;
    }

    /**
     * TODO SOBRE RESTRICCIONES
     */

    /**
    * Registra una nueva restriccion en la base de datos
    */
    public function agregarRestriccion(Request $request) {

        $validator = Validator::make($request->all(), [
            'id_tipo_reunion'=>'required',
            'id_rol'=>'required',
            'descripcion'=>'required|max:5000',
            'estado'=>'required|max:2',
        ],

        [
            'id_tipo_reunion.required' => '*Rellena este campo',
            'id_rol.required' => '*Rellena este campo',
            'descripcion.required' => '*Rellena este campo',
            'descripcion.max' => '*Maximo 5000 caracteres',
            'estado.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $restriccionNueva = new Gcm_Restriccion_Representante;
            $restriccionNueva->id_tipo_reunion = $request->id_tipo_reunion;
            $restriccionNueva->id_rol = $request->id_rol;
            $restriccionNueva->descripcion = $request->descripcion;
            $restriccionNueva->estado = $request->estado;
    
            $response = $restriccionNueva->save();
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae todos los datos de una restriccion
     */
    public function getRestriccion($id_tipo_reunion, $id_rol) {
        $restriccion = Gcm_Restriccion_Representante::where(['id_tipo_reunion' => $id_tipo_reunion, 'id_rol' => $id_rol])->get();
        return $restriccion;
    }

    /**
     * Trae todas las restricciones registradas con un tipo de reunion en comun
     */
    public function getRestricciones($id_tipo_reunion) {
        $restricciones = Gcm_Restriccion_Representante::where('id_tipo_reunion', $id_tipo_reunion)->get();
        return $restricciones;
    }

    /**
     * Actualiza todos los datos de una restriccion 
     */
    public function editarRestriccion($id_tipo_reunion, $id_rol, Request $request) {

        $validator = Validator::make($request->all(), [
            'id_tipo_reunion'=>'required',
            'id_rol'=>'required',
            'descripcion'=>'required|max:5000',
            'estado'=>'required|max:2',
        ],

        [
            'id_tipo_reunion.required' => '*Rellena este campo',
            'id_rol.required' => '*Rellena este campo',
            'descripcion.required' => '*Rellena este campo',
            'descripcion.max' => '*Maximo 5000 caracteres',
            'estado.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el campo estado de una restriccion
    */
    public function cambiarEstadoRestriccion(Request $request) {
        $res;
        try {
            $restriccion = Gcm_Restriccion_Representante::where([['id_tipo_reunion', $request->id_tipo_reunion], ['id_rol', $request->id_rol]])->update(['estado'=> $request->estado]);
            
            $res = response()->json(["response" => 'se cambio'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }
        return $res;
    }

    /**
     * Elimina una restriccion de la base de datos
     */
    public function eliminarRestriccion($id_tipo_reunion, $id_rol) {
        $restriccion = Gcm_Restriccion_Representante::where([['id_tipo_reunion', $id_tipo_reunion], ['id_rol', $id_rol]]);
        $res;
        try {
            $restriccion->delete();
            $res = response()->json(["response" => 'Se eliminó'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }
        return $res;
    }
}
