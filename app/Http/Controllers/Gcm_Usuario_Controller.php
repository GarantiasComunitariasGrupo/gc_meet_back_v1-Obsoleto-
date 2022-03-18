<?php

namespace App\Http\Controllers;

use App;
use App\Models\Gcm_Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;


use Validator;

class Gcm_Usuario_Controller extends Controller
{
    /**
     * Consulta todos los usuarios registrados
     *
     * @return void Retorna la correcta ejecución o el posible fallo de la función 
     */
    public function getUsers()
    {
        try {
            $usuarios = Gcm_Usuario::all();
            return response()->json($usuarios);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Guarda o actualiza un usuario
     *
     * @param Request Aqui van todos los datos del usuario
     * @return void Retorna la correcta ejecución o el posible fallo de la función
     */
    public function saveUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_usuario' => 'required',
                'nombre' => 'required',
                'correo' => 'required|email',
                'contrasena' => 'required',
                'estado' => 'required',
                'tipo' => 'required',
            ],

                [
                    'id_usuario.required' => '*Rellena este campo',
                    'nombre.required' => '*Rellena este campo',
                    'correo.required' => '*Rellena este campo',
                    'correo.email' => '*Ingresa un e-mail válido',
                    'estado.required' => '*Rellena este campo',
                    'tipo.required' => '*Rellena este campo',
                ]

            );

            if ($validator->fails()) {
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors()), null);
                return response()->json($validator->messages(), 422);
            }

            $user_exist = DB::table('gcm_usuarios')->where('id_usuario', '=', $request->id_usuario)->first();

            if (!$user_exist) {
                $userNew = new Gcm_Usuario;
                $userNew->id_usuario = $request->id_usuario;
                $userNew->nombre = $request->nombre;
                $userNew->correo = $request->correo;
                $userNew->contrasena = Hash::make($request->contrasena);
                $userNew->estado = $request->estado;
                $userNew->tipo = $request->tipo;
                $response = $userNew->save();

                Mail::to($request->correos[$i]['correo'])->send(new RegisterUser($detalle));

            } else {
                $user = Gcm_Usuario::findOrFail($user_exist->id_usuario);
                $user->nombre = $request->nombre;
                $user->correo = $request->correo;
                $user->contrasena = Hash::make($request->contrasena);
                $user->estado = $request->estado;
                $user->tipo = $request->tipo;
                $response = $user->save();
            }

            DB::commit();
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            DB::rollback();
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $id_usuario
     * @return void
     */
    public function getUser($id_usuario)
    {
        try {
            $usuario = Gcm_Usuario::where('id_usuario', $id_usuario)->get();
            return response()->json($usuario);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Actualiza el estado de un usuario
     *
     * @param Request Aqui van los dos datos para la actualizacion del estado, id_usuario y el estado
     * @return void Retorna la correcta ejecución o el posible fallo de la función
     */
    public function updateCondition(Request $request)
    {
        try {
            $user = Gcm_Usuario::findOrFail($request->id_usuario);
            $user->estado = $request->estado;
            $response = $user->save();
            
            DB::commit();
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            DB::rollback();
            return response()->json(["error" => $th->getMessage()], 500);
        }

    }

    /**
     * Actualiza el tipo de un usuario
     *
     * @param Request Aqui van los dos datos para la actualizacion del tipo, id_usuario y el tipo
     * @return void Retorna la correcta ejecución o el posible fallo de la función
     */
    public function updateType(Request $request)
    {
        try {
            $user = Gcm_Usuario::findOrFail($request->id_usuario);
            $user->tipo = $request->tipo;
            $response = $user->save();
            
            DB::commit();
            return response()->json(["response" => $response], 200);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            DB::rollback();
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    public function confirmarContrasena(Request $request)
    {

        $usuario = Gcm_Usuario::findOrFail($request->id);
        $res;

        if ($request->accion === 'editar') {
            try {
                $usuario->contrasena = Hash::make($request->clave);
                $response = $usuario->save();
                $res = response()->json(["response" => 'se cambio'], 200);
            } catch (\Throwable $th) {
                return response()->json(["error" => $th->getMessage()], 500);
            }
        } else {
            if (Hash::check($request->clave, $usuario->contrasena)) {
                $res = response()->json(["response" => 'Contraseña correcta'], 200);
            } else {
                $res = response()->json(["error" => 'Contraseña incorrecta'], 422);
            }
        }
        return $res;
    }
}
