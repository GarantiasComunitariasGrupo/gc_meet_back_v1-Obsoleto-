<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Log_Accion_Sistema extends Model
{
    use HasFactory;

    protected $table = 'gcm_log_acciones_sistema';
    protected $primaryKey = 'id_log_accion';
    public $timestamps = false;

    protected $fillable = [
        'id_log_accion',
        'accion',
        'tabla',
        'fecha',
        'lugar',
        'detalle',
    ];
}
