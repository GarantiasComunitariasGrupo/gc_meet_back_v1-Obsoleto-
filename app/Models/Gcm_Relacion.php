<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Utilities\ModelWithEvents;


class Gcm_Relacion extends ModelWithEvents
{
    use HasFactory;

    protected $table = 'gcm_relaciones';
    protected $primaryKey = 'id_relacion';
    public $timestamps = false;

    protected $fillable = [
        'id_relacion',
        'id_grupo',
        'id_rol',
        'id_recurso',
        'estado',
    ];
}
