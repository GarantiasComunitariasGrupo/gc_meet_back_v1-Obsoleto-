<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Log_Accion_Convocado extends Model
{
    use HasFactory;

    protected $table = 'gcm_log_acciones_convocado';
    protected $primaryKey = 'id_log_accion';
    public $timestamps = false;

    protected $fillable = [
        'id_log_accion',
        'id_convocado_reunion',
        'accion',
        'tabla',
        'fecha',
        'lugar',
        'detalle',
    ];
}
