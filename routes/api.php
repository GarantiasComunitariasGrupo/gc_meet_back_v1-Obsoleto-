<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Gcm_Acceso_Reunion_Controller;
use App\Http\Controllers\Gcm_Grupo_Controller;
use App\Http\Controllers\Gcm_Recurso_Controller;
use App\Http\Controllers\Gcm_Reunion_Controller;
use App\Http\Controllers\Gcm_Rol_Controller;
use App\Http\Controllers\Gcm_TipoReunion_Controller;
use App\Http\Controllers\Gcm_Usuario_Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

/**
 * Rutas del componente de login
 */
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/recuperar', [AuthController::class, 'recuperarContrasena']);
    Route::post('/restablecer', [AuthController::class, 'restablecerContrasena']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
});

/**
 * Rutas del componente de usuario
 */
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'user',
], function ($router) {
    Route::post('/save-user', [Gcm_Usuario_Controller::class, 'saveUser']);
    Route::get('/get-users', [Gcm_Usuario_Controller::class, 'getUsers']);
    Route::post('/confirmar-contrasena', [Gcm_Usuario_Controller::class, 'confirmarContrasena']);
    Route::put('/editar-usuario/{id_usuario}', [Gcm_Usuario_Controller::class, 'editarUsuario']);
    Route::put('/update-condition', [Gcm_Usuario_Controller::class, 'updateCondition']);
    Route::put('/update-type', [Gcm_Usuario_Controller::class, 'updateType']);
    Route::get('/get-user/{id_usuario}', [Gcm_Usuario_Controller::class, 'getUser']);
});

/**
 * Rutas del componente de grupo
 */
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'grupo',
], function ($router) {
    Route::post('/agregar-grupo', [Gcm_Grupo_Controller::class, 'agregarGrupo']);
    Route::get('/listar-grupos/{id}', [Gcm_Grupo_Controller::class, 'listarGrupos']);
    Route::put('/editar-grupo/{id_grupo}', [Gcm_Grupo_Controller::class, 'editarGrupo']);
    Route::put('/cambiar-estado', [Gcm_Grupo_Controller::class, 'cambiarEstado']);
    Route::get('/traer-grupo/{id_grupo}', [Gcm_Grupo_Controller::class, 'traerGrupo']);
    Route::delete('/eliminar-grupo/{id_grupo}', [Gcm_Grupo_Controller::class, 'eliminarGrupo']);
});

/**
 * Rutas del componente de rol
 */
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'rol',
], function ($router) {
    Route::post('/agregar-rol', [Gcm_Rol_Controller::class, 'agregarRol']);
    Route::get('/listar-roles/{id}', [Gcm_Rol_Controller::class, 'listarRoles']);
    Route::get('/listar-roles-select/{id}', [Gcm_Rol_Controller::class, 'listarRolesSelect']);
    Route::put('/editar-rol/{id_rol}', [Gcm_Rol_Controller::class, 'editarRol']);
    Route::put('/cambiar-estado', [Gcm_Rol_Controller::class, 'cambiarEstado']);
    Route::get('/traer-rol/{id_rol}', [Gcm_Rol_Controller::class, 'traerRol']);
    Route::delete('/eliminar-rol/{id_rol}', [Gcm_Rol_Controller::class, 'eliminarRol']);
});

/**
 * Rutas del componente de recurso y relaci??n
 */
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'recurso',
], function ($router) {
    // Recurso
    Route::post('/agregar-recurso', [Gcm_Recurso_Controller::class, 'agregarRecurso']);
    Route::get('/listar-recursos/{id}', [Gcm_Recurso_Controller::class, 'listarRecursos']);
    Route::get('/autocompletar/{identificacion}', [Gcm_Recurso_Controller::class, 'autocompletar']);
    Route::get('/listar-recursos-select/{id}', [Gcm_Recurso_Controller::class, 'listarRecursosSelect']);
    Route::get('/listar-recursos-reunion/{id_tipo_reunion}', [Gcm_Recurso_Controller::class, 'getRecursosReunion']);
    Route::put('/editar-recurso/{id_recurso}', [Gcm_Recurso_Controller::class, 'editarRecurso']);
    Route::put('/cambiar-estado-recurso', [Gcm_Recurso_Controller::class, 'cambiarEstadoRecurso']);
    Route::get('/traer-recurso/{id_recurso}', [Gcm_Recurso_Controller::class, 'getRecurso']);
    Route::delete('/eliminar-recurso/{id_recurso}', [Gcm_Recurso_Controller::class, 'eliminarRecurso']);
    //Relacion
    Route::get('/traer-relaciones/{id}', [Gcm_Recurso_Controller::class, 'traerRelaciones']);
    Route::get('/traer-relacion/{id}', [Gcm_Recurso_Controller::class, 'getRelacion']);
    Route::post('/agregar-relacion', [Gcm_Recurso_Controller::class, 'agregarRelacion']);
    Route::put('/editar-relacion/{id_relacion}', [Gcm_Recurso_Controller::class, 'editarRelacion']);
    Route::put('/cambiar-estado-relacion', [Gcm_Recurso_Controller::class, 'cambiarEstadoRelacion']);
    Route::delete('/eliminar-relacion/{id_relacion}', [Gcm_Recurso_Controller::class, 'eliminarRelacion']);
});

/**
 * Rutas del componente de tipoReunion y restriccion
 */
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'tipoReunion',
], function ($router) {
    // Tipo reuni??n
    Route::post('/agregar-tipoReunion', [Gcm_TipoReunion_Controller::class, 'agregarTipoReunion']);
    Route::get('/listar-tiposReunion/{id_usuario}', [Gcm_TipoReunion_Controller::class, 'listarTiposReunion']);
    Route::get('/listar-tiposReunion-select', [Gcm_TipoReunion_Controller::class, 'listarTiposReunionSelect']);
    Route::put('/editar-tipoReunion/{id_tipo_reunion}', [Gcm_TipoReunion_Controller::class, 'editarTipoReunion']);
    Route::put('/cambiar-estado', [Gcm_TipoReunion_Controller::class, 'cambiarEstado']);
    Route::get('/traer-tipoReunion/{id_tipo_reunion}', [Gcm_TipoReunion_Controller::class, 'getTipoReunion']);
    Route::delete('/eliminar-tipoReunion/{id_tipo_reunion}', [Gcm_TipoReunion_Controller::class, 'eliminarTipoReunion']);
    // Restricci??n
    Route::get('/traer-restricciones/{id_tipo_reunion}', [Gcm_TipoReunion_Controller::class, 'getRestricciones']);
    Route::get('/traer-restriccion/{id_tipo_reunion}/{tipo}/{id_elemento}', [Gcm_TipoReunion_Controller::class, 'getRestriccion']);
    Route::post('/agregar-restriccion', [Gcm_TipoReunion_Controller::class, 'agregarRestriccion']);
    Route::put('/editar-restriccion/{id_tipo_reunion}/{tipo}/{id_elemento}', [Gcm_TipoReunion_Controller::class, 'editarRestriccion']);
    Route::put('/cambiar-estado-restriccion', [Gcm_TipoReunion_Controller::class, 'cambiarEstadoRestriccion']);
    Route::delete('/eliminar-restriccion/{id_tipo_reunion}/{tipo}/{id_elemento}', [Gcm_TipoReunion_Controller::class, 'eliminarRestriccion']);
});

/**
 * Rutas del componente de reunion y convocado
 */
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'reunion',
], function ($router) {
    // Reuni??n
    Route::post('/agregar-reunion', [Gcm_Reunion_Controller::class, 'agregarReunion']);
    Route::get('/listar-reuniones-select', [Gcm_Reunion_Controller::class, 'listarReunionesSelect']);
    Route::put('/editar-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'editarReunion']);
    Route::put('/cambiar-estado', [Gcm_Reunion_Controller::class, 'cambiarEstado']);
    Route::get('/traer-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'getReunion']);
    Route::delete('/eliminar-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'eliminarReunion']);
    // Convocado
    Route::get('/traer-convocados/{id_reunion}', [Gcm_Reunion_Controller::class, 'getConvocados']);
    Route::post('/agregar-convocados', [Gcm_Reunion_Controller::class, 'agregarConvocados']);
    Route::get('/traer-convocado/{id_convocado_reunion}', [Gcm_Reunion_Controller::class, 'getConvocado']);
    Route::delete('/eliminar-convocado/{id_convocado_reunion}', [Gcm_Reunion_Controller::class, 'eliminarConvocado']);
    // Programa
    Route::get('/traer-programas/{id_reunion}', [Gcm_Reunion_Controller::class, 'getProgramas']);
    Route::post('/agregar-programas', [Gcm_Reunion_Controller::class, 'agregarProgramas']);
    Route::delete('/eliminar-programa/{id_programa}', [Gcm_Reunion_Controller::class, 'eliminarPrograma']);
    Route::get('/traer-programa/{id_programa}', [Gcm_Reunion_Controller::class, 'getPrograma']);
    // Grupo
    Route::get('/traer-grupos/{id_usuario}', [Gcm_Reunion_Controller::class, 'getGrupos']);
});

Route::group([
    'prefix' => 'meets',
], function ($router) {
    Route::get('/traer-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'getReunion']);
    // Programas
    Route::get('/traer-programas/{id_reunion}', [Gcm_Reunion_Controller::class, 'getProgramas']);
});

/**
 * Rutas del componente de meets
 */
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'meets',
], function ($router) {
    // Reuniones
    Route::get('/traer-reuniones/{id_grupo}', [Gcm_Reunion_Controller::class, 'getReuniones']);
    Route::post('/cancelar-reunion', [Gcm_Reunion_Controller::class, 'cancelarReunion']);
    Route::post('/correo-cancelacion-reunion', [Gcm_Reunion_Controller::class, 'correoCancelacion']);
    Route::get('/iniciar-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'iniciarReunion']);
    Route::get('/eliminar-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'eliminarReunion']);
    Route::post('/reprogramar-reunion', [Gcm_Reunion_Controller::class, 'reprogramarReunion']);
    // Grupos
    Route::get('/traer-grupos', [Gcm_Reunion_Controller::class, 'getGrupos']);
    // Convocados
    Route::get('/traer-convocados/{id_reunion}', [Gcm_Reunion_Controller::class, 'getConvocados']);
    Route::post('/reenviar-correos', [Gcm_Reunion_Controller::class, 'reenviarCorreos']);
});

/**
 * Rutas del componente de meet-management
 */
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'meet-management',
], function ($router) {
    // Reuniones
    Route::get('/traer-reuniones/{id_grupo}', [Gcm_Reunion_Controller::class, 'getReuniones']);
    Route::get('/traer-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'getReunion']);
    Route::get('/traer-ultima-reunion/{id_tipo_reunion}', [Gcm_Reunion_Controller::class, 'traerReunion']);
    Route::post('/editar-reunion', [Gcm_Reunion_Controller::class, 'editarReunionCompleta']);
    Route::get('/traer-roles-actas/{id_acta}', [Gcm_Reunion_Controller::class, 'getRolesActas']);
    // Grupos
    Route::get('/traer-grupos/{id_usuario}', [Gcm_Reunion_Controller::class, 'getGrupos']);
    Route::get('/traer-grupo/{id_grupo}', [Gcm_Reunion_Controller::class, 'getGrupo']);
    // Roles
    Route::get('/traer-roles/{id_reunion}', [Gcm_Reunion_Controller::class, 'getRoles']);
    Route::get('/traer-roles-registrar/{id_grupo}', [Gcm_Reunion_Controller::class, 'getRolesRegistrar']);
    // Tipos de reuniones
    Route::get('/traer-tiposReuniones/{id_reunion}', [Gcm_Reunion_Controller::class, 'getTiposReuniones']);
    Route::get('/traer-tipo-reunion/{id_tipo_reunion}', [Gcm_Reunion_Controller::class, 'getTipoReunion']);
    // Programas
    Route::get('/traer-programas/{id_reunion}', [Gcm_Reunion_Controller::class, 'getProgramas']);
    // Recursos
    Route::get('/traer-recursos/{id_grupo}', [Gcm_Reunion_Controller::class, 'getRecursos']);
    Route::get('/traer-recursos-gcm', [Gcm_Reunion_Controller::class, 'getRecursosGcm']);
    // Convocados
    Route::get('/traer-convocados/{id_reunion}', [Gcm_Reunion_Controller::class, 'getConvocados']);
    Route::get('/autocompletar/{identificacion}', [Gcm_Reunion_Controller::class, 'autocompletar']);
    // PDF Programaci??n
    Route::post('/downloadPDF-programacion', [Gcm_Reunion_Controller::class, 'descargarPDFProgramacion']);
    // Actas
    Route::get('/get-actas', [Gcm_Reunion_Controller::class, 'getActas']);
});

/**
 * Rutas para acceso a una reuni??n
 */
Route::group([
    'prefix' => 'acceso-reunion',
], function ($router) {
    Route::get('/validacion-convocado/{identificacion}/{id_convocado_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'validacionConvocado']);
    Route::get('/buscar-invitacion/{identificacion}', [Gcm_Acceso_Reunion_Controller::class, 'buscarInvitacion']);
    Route::get('/get-id-convocado/{identificacion}/{id_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getIdConvocado']);
    Route::get('/get-restricciones/{id_convocado_reunion}/{identificacion}', [Gcm_Acceso_Reunion_Controller::class, 'getRestricciones']);
    Route::get('/get-otras-restricciones/{id_convocado_reunion}/{identificacion}', [Gcm_Acceso_Reunion_Controller::class, 'getOtrasRestricciones']);
    Route::post('/enviar-sms', [Gcm_Acceso_Reunion_Controller::class, 'enviarSMS']);
    Route::post('/enviar-firma', [Gcm_Acceso_Reunion_Controller::class, 'enviarFirma']);
    Route::get('/permitir-firma/{id_convocado_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'permitirFirma']);
    Route::post('/registrar-representante', [Gcm_Acceso_Reunion_Controller::class, 'registrarRepresentante']);
    Route::get('/get-representante/{id_convocado_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getRepresentante']);
    Route::post('/cancelar-representacion', [Gcm_Acceso_Reunion_Controller::class, 'cancelarRepresentacion']);
    Route::post('/get-representados', [Gcm_Acceso_Reunion_Controller::class, 'getRepresentados']);
    Route::get('/encriptar/{valor}/{tipo}', [Gcm_Acceso_Reunion_Controller::class, 'encriptar']);
    Route::post('/cancelar-representaciones', [Gcm_Acceso_Reunion_Controller::class, 'cancelarRepresentaciones']);
    Route::get('/get-avance-reunion/{id_convocado_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getAvanceReunion']);
    Route::get('/get-listado-reuniones/{id_reunion}/{identificacion}', [Gcm_Acceso_Reunion_Controller::class, 'getListadoReuniones']);
    Route::get('/get-programacion/{id_reunion}/{id_convocado_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getProgramacion']);
    Route::post('/avanzar-paso', [Gcm_Acceso_Reunion_Controller::class, 'avanzarPrograma']);
    Route::post('/actualizar-estado-programa', [Gcm_Acceso_Reunion_Controller::class, 'actualizarEstadoPrograma']);
    Route::post('/answer-question', [Gcm_Acceso_Reunion_Controller::class, 'answerQuestion']);
    Route::get('/get-respuestas-convocado/{id_convocado_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getRespuestasConvocado']);
    Route::get('/get-lista-convocados/{id_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getListaConvocados']);
    Route::post('/guardar-acceso-reunion', [Gcm_Acceso_Reunion_Controller::class, 'guardarAccesoReunion']);
    Route::post('/actualizar-acceso-reunion', [Gcm_Acceso_Reunion_Controller::class, 'actualizarAccesoReunion']);
    Route::get('/get-resultados-votacion/{id_programa}', [Gcm_Acceso_Reunion_Controller::class, 'getResultadosVotacion']);
    Route::get('/get-respuestas-reunion/{id_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getRespuestasReunion']);
    Route::post('/finalizar-reunion', [Gcm_Acceso_Reunion_Controller::class, 'finalizarReunion']);
    Route::get('/get-data-admin/{token}', [Gcm_Acceso_Reunion_Controller::class, 'getDataAdmin']);
    Route::post('/save-logout', [Gcm_Acceso_Reunion_Controller::class, 'saveLogout']);
    Route::get('/get-all-summoned-list/{id_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getAllSummonedList']);
    Route::post('/save-program', [Gcm_Acceso_Reunion_Controller::class, 'saveProgram']);
    Route::post('/summon/{id_grupo}/{id_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'summon']);
    Route::post('/send-mail-to-summon', [Gcm_Acceso_Reunion_Controller::class, 'sendMailToSummon']);
    Route::post('/send-mail-to-summon-running', [Gcm_Acceso_Reunion_Controller::class, 'sendMailToSummonRunning']);
    Route::post('/check-election', [Gcm_Acceso_Reunion_Controller::class, 'checkElection']);
    Route::post('/save-election', [Gcm_Acceso_Reunion_Controller::class, 'saveElection']);
    Route::post('/check-firma-acta', [Gcm_Acceso_Reunion_Controller::class, 'checkFirmaActa']);
    Route::get('/get-numero-acta/{id_tipo_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getNumeroActa']);
    Route::get('/get-announcement-date/{id_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getAnnouncementDate']);
    Route::post('/download-acta', [Gcm_Acceso_Reunion_Controller::class, 'downloadActa']);
    Route::get('/get-summoned-loggedin-list/{id_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getSummonedLoggedinList']);
});

Route::get('/buscar-archivos', function (Request $request) {
    $params = (object) $request->all();

    if (isset($params->name)) {

        $path = storage_path("app/public/{$params->name}");
        $exists = File::exists($path);

        if ($exists) {
            return response()->file($path);
        } else {
            return response()->json(["message" => "File doesn't exist" . $path]);
        }

    } else {
        return response()->json(['response' => 'Archivo no existe']);
    }
});
