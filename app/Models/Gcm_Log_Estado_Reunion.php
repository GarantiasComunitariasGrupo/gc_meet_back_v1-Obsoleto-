<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Log_Estado_Reunion extends Model
{
    use HasFactory;

    protected $table = 'gcm_log_estados_reuniones';
    protected $primaryKey = 'id_log_estado_reunion';
    public $timestamps = false;

    protected $fillable = [
        'id_log_estado_reunion',
        'id_reunion',
        'estado',
        'fecha',
    ];
}
