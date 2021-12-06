<?php

namespace App\Http\Controllers;

use App\Http\Temporal\Encrypt;
use App\Mail\Recuperar;
use App\Models\Gcm_Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;

class AuthController extends Controller
{

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_usuario' => 'required',
            'contrasena' => 'required',
        ], [
            'id_usuario.required' => 'El campo usuario es obligatorio',
            'contrasena.required' => 'El campo contraseña es obligatorio',
        ]);

        if ($validator->fails()) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors()), null);
            return response()->json($validator->errors(), 422);
        }

        $credenciales = ['id_usuario' => $request->input('id_usuario'), 'password' => $request->input('contrasena')];

        try {
            // findOrFail = Encuentrelo o falle
            Gcm_Usuario::findOrFail($request->id_usuario);
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'Usuario Incorrecto'), null);
            return response()->json(["message" => 'Usuario Incorrecto'], 200);
        }

        try {
            $token = JWTAuth::attempt($credenciales);
            if (!$token) {
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'Contraseña incorrecta'), null);
                $res = response()->json(['message' => 'Contraseña incorrecta', 'status' => false], 200);
            } else {
                $res = $this->createNewToken($token);
            }
            return $res;
        } catch (JWTException $e) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'No se pudo crear el token'), null);
            return response()->json(["error" => 'No se pudo crear el token' . $e->getMessage()], 500);
        }
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $password = 'danilo123';

        $user = Gcm_Usuario::create([
            "id_usuario" => 'gc_meet',
            "nombre" => 'Admin',
            "correo" => 'danilogg2015@gmail2.com',
            "estado" => '1',
            "tipo" => '0',
            "contrasena" => Hash::make($password),
        ]);

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user,
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'status' => true,
        ]);
    }

    /**
     * Función en la cual realizo la validacion de un usuario que desea reestablecer la contraseña, si las validaciones se cumplen, se envia el correo para continuar el proceso
     *
     * @param Request $request Aqui viene el id del usuario a verificar
     * @return void Retorna informacion de la correcta validacion o el prosible error de esta
     */
    public function recuperarContrasena(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id_usuario' => 'required',
        ], [
            'id_usuario.required' => 'El campo usuario es obligatorio',
        ]);

        if ($validator->fails()) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors()), null);
            return response()->json($validator->errors(), 422);
        }

        try {

            $usuario_existe = DB::table('gcm_usuarios')->where('id_usuario', '=', $request->id_usuario)->first();

            if (!$usuario_existe) {
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'El usuario no se encuentra registrado'), null);
                return response()->json(['message' => 'El usuario no se encuentra registrado', 'status' => false], 200);
            } else {
                $encrypt = new Encrypt();

                $caducidad = time() + 1800;

                $valorEncriptado = $encrypt->encriptar($request->id_usuario . '|' . $caducidad);

                $detalle = [
                    'nombre' => $usuario_existe->nombre,
                    'url' => '192.168.2.71:4200/public/login/restablecer/' . $valorEncriptado,
                ];

                Mail::to($usuario_existe->correo)->send(new Recuperar($detalle));
                Gcm_Log_Acciones_Sistema_Controller::save(4, array('Descripcion' => 'Envio de link para recuperacion de contrasena', 'Correos' => $usuario_existe->correo), null);
                return response()->json(["response" => 'Puede ingresar a su correo electronico para continuar con el proceso', 'status' => true], 200);
            }
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }

    /**
     * Se encarga de actualizar la contraseña de ingreso de un usuario
     *
     * @param Request $request Aqui viene el id del usuario y el valor de la nueva contraseña
     * @return void Respuesta con la confirmacion o la posible falla de la actualizacion
     */
    public function restablecerContrasena(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contrasena' => 'required',
            'confirmar' => 'required',
        ], [
            'contrasena.required' => 'El campo contrasena es obligatorio',
            'confirmar.required' => 'El campo confirmar es obligatorio',
        ]);

        if ($validator->fails()) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $validator->errors()), null);
            return response()->json($validator->errors(), 422);
        }

        try {
            $encrypt = new Encrypt();

            $datos = $encrypt->desencriptar($request->id_usuario);

            $datos_separados = explode('|', $datos);

            if (isset($datos_separados[1])) {
                $hora_actual = time();

                if ($hora_actual <= $datos_separados[1]) {
                    $id_usuario = $datos_separados[0];
                    if ($request->contrasena === $request->confirmar) {
                        $usuario = Gcm_Usuario::findOrFail($id_usuario);
                        $usuario->contrasena = Hash::make($request->contrasena);

                        $response = $usuario->save();
                        return response()->json(["response" => $response, 'status' => true], 200);
                    } else {
                        Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'Las contraseñas no coinciden'), null);
                        return response()->json(['message' => 'Las contraseñas no coinciden', 'status' => false], 200);
                    }
                } else {
                    Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'No es posible realizar la acción, el token ha expirado.'), null);
                    return response()->json(['message' => 'No es posible realizar la acción, el token ha expirado.', 'status' => false], 200);
                }
            } else {
                Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => 'No es posible realizar la acción, el token ha expirado.'), null);
                return response()->json(['message' => 'No es posible realizar la acción, el token ha expirado.', 'status' => false], 200);
            }
        } catch (\Throwable $th) {
            Gcm_Log_Acciones_Sistema_Controller::save(7, array('mensaje' => $th->getMessage(), 'linea' => $th->getLine()), null);
            return response()->json(["error" => $th->getMessage()], 500);
        }
    }
}
