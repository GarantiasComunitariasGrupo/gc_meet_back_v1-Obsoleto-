<?php

namespace App\Http\Controllers;

use App;
use Illuminate\Http\Request;
use App\Models\Gcm_Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Controllers\Gcm_Mail_Controller;
use Validator;

class Gcm_Usuario_Controller extends Controller

{
    /**
     * Trae todos los usuarios registrados en la bd
     */
    public function listarUsuarios() {
        $usuarios = Gcm_Usuario::all();
        return $usuarios;
    }

    /**
     * Registra un usuario nuevo en la bd
     */
    public function agregarUsuario(Request $request) {

        $validator = Validator::make($request->all(), [
            'nombre'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'correo'=>'required|email|max:255',
            'estado'=>'required|max:2',
            'tipo'=>'required|max:2',
        ],

        [
            'nombre.required' => '*Rellena este campo',
            'nombre.max' => '*Máximo 50 caracteres',
            'nombre.regex' => '*Ingresa sólo letras',
            'correo.required' => '*Rellena este campo',
            'correo.email' => '*Ingresa un e-mail válido',
            'estado.required' => '*Rellena este campo',
            'tipo.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $mc = new Gcm_Mail_Controller();

        try {
            $usuarioNuevo = new Gcm_Usuario;
            $usuarioNuevo->nombre = $request->nombre;
            $usuarioNuevo->correo = $request->correo;
            $aquita = 'GCM' . Str::random(8);
            $usuarioNuevo->contrasena = Hash::make($aquita);
            $usuarioNuevo->estado = $request->estado;
            $usuarioNuevo->tipo = $request->tipo;

            $response = $usuarioNuevo->save();

            // print_r($aquita); // imprime el valor, tipo, longitud
            // var_dump($aquita); //imprime valor en tipo string
            // echo($aquita); // imprime valor
            // print($aquita); // imprime valor

            $mc->sendEmail('Este es el título', $aquita, $request->correo);

            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Trae los datos de un usuario en especifico
     */
    public function traerUsuario($id_usuario) {
        $usuario = Gcm_Usuario::where('id_usuario', $id_usuario)->get();
        return $usuario;
    }

    /**
     * Confirma si una contraseña enviada coincide con la contraseña actual de la bd
     */
    public function confirmarContrasena(Request $request) {

        $usuario = Gcm_Usuario::findOrFail($request->id);
        $res;

        if($request->accion === 'editar'){
            try {
                $usuario->contrasena = Hash::make($request->clave);
                $response = $usuario->save();
                $res =  response()->json(["response" => 'se cambio'], 200);
            } catch (\Throwable $th) {
                return response()->json(["error" => $th->getMessage()], 500);
            }
        } else {
            if(Hash::check($request->clave, $usuario->contrasena)){
                $res = response()->json(["response" => 'Contraseña correcta'], 200);
            }else{
                $res = response()->json(["error" => 'Contraseña incorrecta'], 422);
            }
        }
        return $res;
    }

    /**
     * Actualiza los datos de un usuario ya registrado
     */
    public function editarUsuario($id_usuario, Request $request) {

        $validator = Validator::make($request->all(), [
            
            'nombre'=>'required|regex:/^[\pL\s\-]+$/u|max:50',
            'correo'=>'required|email|max:255',
            'estado'=>'required|max:2',
            'tipo'=>'required|max:2',
        ],

        [
            'nombre.required' => '*Rellena este campo',
            'nombre.max' => '*Máximo 50 caracteres',
            'nombre.regex' => '*Ingresa sólo letras',
            'correo.required' => '*Rellena este campo',
            'correo.email' => '*Ingresa un e-mail válido',
            'estado.required' => '*Rellena este campo',
            'tipo.required' => '*Rellena este campo',
        ]

        );

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        try {
            $usuario = Gcm_Usuario::findOrFail($id_usuario);
            $usuario->nombre = $request->nombre;
            $usuario->correo = $request->correo;
            $usuario->estado = $request->estado;
            $usuario->tipo = $request->tipo;
    
            $response = $usuario->save();
            
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el campo estado de un usuario en especifico
     */
    public function cambiarEstado(Request $request) {

        $usuario = Gcm_Usuario::findOrFail($request->id_usuario);
        $res;
        try {
            $usuario->estado = $request->estado;
            $usuario->save();

            $res = response()->json(["response" => 'se cambio'], 200);
        } catch (\Throwable $th) {
            $res = response()->json(["error" => $th->getMessage()], 500);
        }
        return $res;
    }
}


