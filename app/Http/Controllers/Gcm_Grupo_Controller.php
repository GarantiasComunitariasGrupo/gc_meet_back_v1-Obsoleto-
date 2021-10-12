<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Models\Gcm_Grupo;
use Validator;

class Gcm_Grupo_Controller extends Controller
{
    
    /**
     * Trae todos los grupos registrados por un usuario en la bd
     */
    public function listarGrupos($id_usuario) {
        $grupos = Gcm_Grupo::where('id_usuario', $id_usuario)->get();
        return $grupos;
    }

    /**
     * Registra un grupo nuevo en la bd
     */
    public function agregarGrupo(Request $request) {

        $validator = Validator::make($request->all(), [
            'descripcion'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'estado'=>'required|max:2',
        ],

        [
            'descripcion.required' => '*Rellena este campo',
            'descripcion.max' => '*Máximo 50 caracteres',
            'descripcion.regex' => '*Ingresa sólo letras',
            'estado.required' => '*Rellena este campo',
        ]

        );

        if($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $grupoNuevo = new Gcm_Grupo;
            $grupoNuevo->id_usuario = $request->id_usuario;
            $grupoNuevo->descripcion = $request->descripcion;
            $grupoNuevo->estado = $request->estado;
            $grupoNuevo->imagen = $request->imagen;
    
            $response = $grupoNuevo->save();

            return response()->json(["response" => $response], 200  );
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }

    }

    /**
     * Trae los datos de un grupo en especifico
     */
    public function traerGrupo($id_grupo) {
        
        $grupo = Gcm_Grupo::where('id_grupo', $id_grupo)->get();
        return $grupo;
    }

    /**
     * Actualiza los datos de un grupo ya registrado
     */
    public function editarGrupo($id_grupo, Request $request) {

        $validator = Validator::make($request->all(), [
            'descripcion'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'estado'=>'required|max:2',
            'imagen'=>'required',
        ],

        [
            'descripcion.required' => '*Rellena este campo',
            'descripcion.max' => '*Máximo 50 caracteres',
            'descripcion.regex' => '*Ingresa sólo letras',
            'estado.required' => '*Rellena este campo',
            'imagen.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            //code...
            $grupo = Gcm_Grupo::findOrFail($id_grupo);
            $grupo->descripcion = $request->descripcion;
            $grupo->estado = $request->estado;
            $grupo->imagen = $request->imagen;
    
            $response = $grupo->save();

            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el campo estado de un grupo en especifico
    */
    public function cambiarEstado(Request $request) {

        $grupo = Gcm_Grupo::findOrFail($request->id_grupo);
        $res;

        if ( $request->estado == '1' ){
            try {
                $grupo->estado = $request->estado;
                $grupo->save();
    
                $res = response()->json(["response" => 'se cambio'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
        } else {
            try {
                $grupo->estado = $request->estado;
                $grupo->save();
    
                $res = response()->json(["response" => 'Se cambio'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
        }

        return $res;
    }

    /**
     * Elimina un rol en especifico de la bd
     */
    public function eliminarGrupo($id_grupo) {

        $grupo = Gcm_Grupo::findOrFail($id_grupo);
        $res;

        try {
            $grupo->delete();

            $res = response()->json(["response" => 'Se eliminó'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }

        return $res;
    }
}
