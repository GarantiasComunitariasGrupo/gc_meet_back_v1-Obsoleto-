<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Respuesta_Convocado extends Model
{
    use HasFactory;

    protected $table = 'gcm_respuestas_convocados';
    protected $primaryKey = ['id_convocado_reunion', 'id_programa'];
    public $timestamps = false;

    protected $fillable = [
        'id_convocado_reunion',
        'id_programa',
        'descripcion',
    ];
}
