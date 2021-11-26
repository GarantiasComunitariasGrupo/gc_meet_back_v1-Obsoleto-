<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Gcm_Usuario;

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
        ],[
            'id_usuario.required' => 'El campo usuario es obligatorio',
            'contrasena.required' => 'El campo contraseña es obligatorio',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // $credenciales = $request->only(['id_usuario', 'contrasena']);
        $credenciales = ['id_usuario' => $request->input('id_usuario'), 'password' =>$request->input('contrasena')];
        
        try {
            $token = JWTAuth::attempt($credenciales);
            // $token = JWTAuth::attempt(["id_usuario" => $credenciales['id_usuario'], 'password' => $credenciales['contrasena']]);
            if (!$token) {
                $res = response()->json(['message' => 'Datos incorrectos', 'status' => false], 200);
            } else {
                $res = $this->createNewToken($token);
            }
            return $res;
        } catch (JWTException $e) {
            return response()->json(["error" => 'No se pudo crear el token' . $e->getMessage()], 500);
        }
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {

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
            'user' => $user
        ], 201);
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json(auth()->user());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'status' => true
        ]);
    }
}