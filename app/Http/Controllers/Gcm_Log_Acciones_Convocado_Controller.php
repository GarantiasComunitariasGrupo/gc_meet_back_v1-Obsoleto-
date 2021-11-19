<?php

namespace App\Http\Controllers;

use App\Models\Gcm_Log_Accion_Convocado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Gcm_Log_Acciones_Convocado_Controller extends Controller
{
    /**
     * Guarda una acción realizada por el convocado
     *
     * @param [type] $action Tipo de acción realizada
     * @param [type] $item Registro afectado
     * @param [type] $tableName Tabla afectada
     * @param [type] $place Lugar desde donde se realizó la acción
     * @return void
     */
    public static function save($action, $item, $tableName = null, $place = null)
    {
        if ($tableName === null && $item instanceof Model) {$tableName = $item->getTable();}
        if ($place === null) {$place = Gcm_Log_Acciones_Convocado_Controller::getIp();}
        $log_accion_usuario_new = new Gcm_Log_Accion_Convocado();
        $log_accion_usuario_new->id_convocado_reunion = 'gcm_danilo';
        $log_accion_usuario_new->accion = $action;
        $log_accion_usuario_new->tabla = $tableName;
        $log_accion_usuario_new->lugar = $place;
        $log_accion_usuario_new->detalle = json_encode($item);
        $log_accion_usuario_new->save();
    }

    /**
     * Obtiene el lugar (dirección ip) desde donde se realizaron la acciones del usuario (Proporcionado por el sistema)
     *
     * @return string Lugar
     */
    private static function getIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return request()->ip(); // it will return server ip when no client ip found
    }
}
