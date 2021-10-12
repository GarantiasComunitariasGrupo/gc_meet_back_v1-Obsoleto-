<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\Models\Gcm_Rol;
use Validator;

class Gcm_Rol_Controller extends Controller
{
    /**
     * Trae todos los roles registrados por un usuario en la bd
     */
    public function listarRoles($id_usuario) {
        $roles = Gcm_Rol::where('id_usuario', $id_usuario)->get();
        return $roles;
    }

    /**
     * Trae todos los roles registrados por un usuario en la bd
     */
    public function listarRolesSelect($id_usuario) {
        $roles = Gcm_Rol::where([['id_usuario', $id_usuario], ['relacion', null], ['estado', '1']])->get();
        return $roles;
    }

    /**
     * Registra un rol nuevo en la bd
     */
    public function agregarRol(Request $request) {

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

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $rolNuevo = new Gcm_Rol;
            $rolNuevo->id_usuario = $request->id_usuario;
            $rolNuevo->descripcion = $request->descripcion;
            $rolNuevo->estado = $request->estado;
            $rolNuevo->relacion = $request->relacion;
    
            $response = $rolNuevo->save();

            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }

    }

    /**
     * Trae los datos de un rol en especifico
     */
    public function traerRol($id_rol) {
        $rol = Gcm_Rol::where('id_rol', $id_rol)->get();
        return $rol;
    }

    /**
     * Actualiza los datos de un rol ya registrado
     */
    public function editarRol($id_rol, Request $request) {

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

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $rol = Gcm_Rol::findOrFail($id_rol);
            $rol->descripcion = $request->descripcion;
            $rol->estado = $request->estado;
            $rol->relacion = $request->relacion;
    
            $response = $rol->save();

            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el campo estado de un rol en especifico
    */
    public function cambiarEstado(Request $request) {

        $rol = Gcm_Rol::findOrFail($request->id_rol);
        $res;

        if ( $request->estado == '1' ){
            try {
                $rol->estado = $request->estado;
                $rol->save();
    
                $res = response()->json(["response" => 'se cambio'], 200);
            } catch (\Throwable $th) {
                $res = response()->json(["error" => $th->getMessage()], 500);
            }
        } else {
            try {
                $rol->estado = $request->estado;
                $rol->save();
    
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
    public function eliminarRol($id_rol) {

        $rol = Gcm_Rol::findOrFail($id_rol);
        $res;

        try {
            $rol->delete();

            $res = response()->json(["response" => 'Se eliminó'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }

        return $res;
    }
}
