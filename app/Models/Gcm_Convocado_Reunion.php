<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class Gcm_Convocado_Reunion extends Model
{
    use HasFactory;

    protected $table = 'gcm_convocados_reunion';
    protected $primaryKey = 'id_convocado_reunion';
    public $timestamps = false;

    protected $fillable = [
        'id_convocado_reunion',
        'id_reunion',
        'representacion',
        'id_relacion',
        'fecha',
        'tipo',
        'nit',
        'razon_social',
        'participacion',
        'soporte'
    ];

    public static function boot()
    {
        parent::boot();

        // Gcm_Convocado_Reunion::creating(function ($model) {
        //     throw new Exception('creating', 1);
        // });

        Gcm_Convocado_Reunion::updating(function ($model) {
            throw new Exception('updating', 1);
        });

        // Gcm_Convocado_Reunion::saving(function ($model) {
        //     throw new Exception('saving', 1);
        // });

        Gcm_Convocado_Reunion::deleting(function ($model) {
            throw new Exception('deleting', 1);
        });

        static::deleting(function ($model) {
            throw new Exception('deleting', 1);
        });

        self::deleting(function ($model) {
            throw new Exception('deleting', 1);
        });


        // Gcm_Convocado_Reunion::created(function ($model) {
        //     throw new Exception('created', 1);
        // });

        Gcm_Convocado_Reunion::updated(function ($model) {
            throw new Exception('updated', 1);
        });

        // Gcm_Convocado_Reunion::saved(function ($model) {
        //     throw new Exception('saved', 1);
        // });

        Gcm_Convocado_Reunion::deleted(function ($model) {
            throw new Exception('deleted', 1);
        });

        static::deleted(function ($model) {
            throw new Exception('deleted', 1);
        });

        self::deleted(function ($model) {
            throw new Exception('deleted', 1);
        });
    }
}
