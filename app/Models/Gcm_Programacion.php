<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Utilities\ModelWithEvents;



class Gcm_Programacion extends ModelWithEvents
{
    use HasFactory;

    protected $table = 'gcm_programacion';
    protected $primaryKey = 'id_programa';
    public $timestamps = false;

    protected $fillable = [
        'id_programa',
        'id_reunion',
        'titulo',
        'descripcion',
        'orden',
        'numeracion',
        'tipo',
        'relacion',
        'id_rol_acta',
        'id_convocado_reunion',
        'estado',
    ];
}
