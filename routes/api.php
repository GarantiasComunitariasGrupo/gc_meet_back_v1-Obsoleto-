<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Gcm_Usuario_Controller;
use App\Http\Controllers\Gcm_Grupo_Controller;
use App\Http\Controllers\Gcm_Rol_Controller;
use App\Http\Controllers\Gcm_Recurso_Controller;
use App\Http\Controllers\Gcm_TipoReunion_Controller;
use App\Http\Controllers\Gcm_Reunion_Controller;
use App\Http\Controllers\Gcm_Acceso_Reunion_Controller;

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
 * Rutas del componente de usuario
 */
Route::group([
    'prefix' => 'usuario'
], function ($router) {
    Route::post('/agregar-usuario', [Gcm_Usuario_Controller::class, 'agregarUsuario']);
    Route::get('/listar-usuarios', [Gcm_Usuario_Controller::class, 'listarUsuarios']);
    Route::post('/confirmar-contrasena', [Gcm_Usuario_Controller::class, 'confirmarContrasena']);
    Route::put('/editar-usuario/{id_usuario}', [Gcm_Usuario_Controller::class, 'editarUsuario']);
    Route::put('/cambiar-estado', [Gcm_Usuario_Controller::class, 'cambiarEstado']);
    Route::get('/traer-usuario/{id_usuario}', [Gcm_Usuario_Controller::class, 'traerUsuario']);
});

/**
 * Rutas del componente de grupo
 */
Route::group([
    'prefix' => 'grupo'
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
    'prefix' => 'rol'
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
 * Rutas del componente de recurso y relación
 */
Route::group([
    'prefix' => 'recurso'
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
    'prefix' => 'tipoReunion'
], function ($router) {
    // Tipo reunión
    Route::post('/agregar-tipoReunion', [Gcm_TipoReunion_Controller::class, 'agregarTipoReunion']);
    Route::get('/listar-tiposReunion/{id_usuario}', [Gcm_TipoReunion_Controller::class, 'listarTiposReunion']);
    Route::get('/listar-tiposReunion-select', [Gcm_TipoReunion_Controller::class, 'listarTiposReunionSelect']);
    Route::put('/editar-tipoReunion/{id_tipo_reunion}', [Gcm_TipoReunion_Controller::class, 'editarTipoReunion']);
    Route::put('/cambiar-estado', [Gcm_TipoReunion_Controller::class, 'cambiarEstado']);
    Route::get('/traer-tipoReunion/{id_tipo_reunion}', [Gcm_TipoReunion_Controller::class, 'getTipoReunion']);
    Route::delete('/eliminar-tipoReunion/{id_tipo_reunion}', [Gcm_TipoReunion_Controller::class, 'eliminarTipoReunion']);
    // Restricción
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
    'prefix' => 'reunion'
], function ($router) {
    // Reunión
    Route::post('/agregar-reunion', [Gcm_Reunion_Controller::class, 'agregarReunion']);
    Route::get('/listar-reuniones/{id_usuario}', [Gcm_Reunion_Controller::class, 'listarReuniones']);
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

/**
 * Rutas del componente de meets
 */
Route::group([
    'prefix' => 'meets'
], function ($router) {
    // Reuniones
    Route::get('/traer-reuniones/{id_grupo}', [Gcm_Reunion_Controller::class, 'getReuniones']);
    Route::get('/traer-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'getReunion']);
    // Grupos
    Route::get('/traer-grupos/{id_usuario}', [Gcm_Reunion_Controller::class, 'getGrupos']);
    // Programas
    Route::get('/traer-programas/{id_reunion}', [Gcm_Reunion_Controller::class, 'getProgramas']);
    // Convocados
    Route::get('/traer-convocados/{id_reunion}', [Gcm_Reunion_Controller::class, 'getConvocados']);
});

/**
 * Rutas del componente de meet-management
 */
Route::group([
    'prefix' => 'meet-management'
], function ($router) {
    // Reuniones
    Route::get('/traer-reuniones/{id_grupo}', [Gcm_Reunion_Controller::class, 'getReuniones']);
    Route::get('/traer-reunion/{id_reunion}', [Gcm_Reunion_Controller::class, 'getReunion']);
    Route::get('/traer-reunion-registrar/{id_grupo}', [Gcm_Reunion_Controller::class, 'getReunionRegistrar']);
    Route::post('/editar-reunion', [Gcm_Reunion_Controller::class, 'editarReunionCompleta']);
    // Grupos
    Route::get('/traer-grupos/{id_usuario}', [Gcm_Reunion_Controller::class, 'getGrupos']);
    // Roles
    Route::get('/traer-roles/{id_reunion}', [Gcm_Reunion_Controller::class, 'getRoles']);
    // Tipos de reuniones
    Route::get('/traer-tiposReuniones/{id_reunion}', [Gcm_Reunion_Controller::class, 'getTiposReuniones']);
    // Programas
    Route::get('/traer-programas/{id_reunion}', [Gcm_Reunion_Controller::class, 'getProgramas']);
    // Recursos
    Route::get('/traer-recursos', [Gcm_Reunion_Controller::class, 'getRecursos']);
    // Convocados
    Route::get('/traer-convocados/{id_reunion}', [Gcm_Reunion_Controller::class, 'getConvocados']);
    Route::get('/autocompletar/{identificacion}', [Gcm_Reunion_Controller::class, 'autocompletar']);
});

Route::group([
    'prefix' => 'acceso-reunion'
], function ($router) {
    Route::get('lista-convocados-reunion/{id_reunion}', [Gcm_Acceso_Reunion_Controller::class, 'getListaConvocados']);
    Route::post('/guardar-acceso-reunion', [Gcm_Acceso_Reunion_Controller::class, 'guardarAccesoReunion']);
    Route::post('/actualizar-acceso-reunion', [Gcm_Acceso_Reunion_Controller::class, 'actualizarAccesoReunion']);
    Route::post('/guardar-representante-reunion', [Gcm_Acceso_Reunion_Controller::class, 'guardarRepresentante']);
    Route::get('/consultar-representante/{id_reunion}/{id_recurso}', [Gcm_Acceso_Reunion_Controller::class, 'consultarRepresentante']);
    Route::post('/cancelarInvitacion', [Gcm_Acceso_Reunion_Controller::class, 'cancelarInvitacion']);
});