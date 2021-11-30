<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use App\Utilities\ModelWithEvents;

class Gcm_Respuesta_Convocado extends ModelWithEvents
{
    use HasFactory;

    protected $table = 'gcm_respuestas_convocados';
    protected $primaryKey = ['id_convocado_reunion', 'id_programa'];
    public $timestamps = false;
    public $incrementing = false;


    protected $fillable = [
        'id_convocado_reunion',
        'id_programa',
        'descripcion',
    ];
}
