<?php

namespace App\Utilities;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Exception;

class ModelWithEvents extends Model
{
    protected static $userLogModel = '\App\Http\Controllers\Gcm_Log_Acciones_Usuario_Controller';
    /** almacena un registro antes de actualizarlo */
    protected static $originalRecord = null;

    /** Detecta eventos de acciones en la BD */
    public static function boot()
    {
        parent::boot();

        $authorization = request()->header('Authorization');
        if(isset($authorization) && count(explode('|', $authorization)) > 1) {
            static::$userLogModel = '\App\Http\Controllers\Gcm_Log_Acciones_Convocado_Controller';
        } else {
            static::$userLogModel = '\App\Http\Controllers\Gcm_Log_Acciones_Usuario_Controller';
        }
        /** Si hay un usuario en sesión se registrará la acción en el modelo de acciones usuario, de lo contrario se hará en la del sistema */
        $logModel = true ? (static::$userLogModel) : ('\App\Http\Controllers\Gcm_Log_Acciones_Sistema_Controller');
        /** Guarda el valor original del modelo que se actualizará */
        static::saving(function ($model) use ($logModel) {static::$originalRecord = static::find($model->getKey());});
        /** Registra las acciones de inserción y actualización en el log */
        static::saved(function ($model) use ($logModel) {
            if (static::$originalRecord === null) {
                /** Registra una inserción en el log */
                (new $logModel)::save(0, $model);
            } else {
                /** Registra una eliminación en el log */
                /** Es verdadero cuando se encuentran cambios entre el modelo actual y el original */
                $hasChanges = false;
                /** Revisa todas las propiedades del modelo en busca de cambios */
                foreach ($model->getAttributes() as $key => $value) {
                    if (static::$originalRecord[$key] !== $value) {
                        $hasChanges = true;
                        break;
                    }
                }
                if ($hasChanges) {
                    $changes = ['previousValue' => static::$originalRecord, 'currentValue' => $model];
                    (new $logModel)::save(1, $changes, $model->getTable());
                }
                static::$originalRecord = null;
            }
        });
        /** Registra la acción de eliminación en el log */
        static::deleting(function ($model) use ($logModel) {(new $logModel)::save(2, $model);});
    }

    /**
     * Recibe una colección de valores a eliminar
     *
     * @param [type] $collection Colección generada por consulta de eloquent
     * @return void
     */
    public static function groupDeletion($collection)
    {
        if ($collection instanceof Collection) {
            // Elimina uno por uno todos los valores recibidos
            foreach ($collection as $item) {$item->delete();}
            return count($collection);
        } else {
            throw new Exception('La eliminación grupal recibe una colección', 1);
        }
    }

    /**
     * Recibe una colección de valores y les cambia el estado
     *
     * @param [type] $collection Colección generada por consulta de eloquent
     * @return void
     */
    public static function changeStatus($collection, $status)
    {
        if ($collection instanceof Collection) {
            // Elimina uno por uno todos los valores recibidos
            foreach ($collection as $item) {
                isset($item->estado) && ($item->estado = $status);
                $item->save();
            }
        } else {
            throw new Exception('La modificación de estado grupal recibe una colección', 1);
        }
    }

}
