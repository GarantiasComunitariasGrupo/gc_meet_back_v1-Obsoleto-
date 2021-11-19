<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Utilities\ModelWithEvents;


class Gcm_Restriccion_Rol_Representante extends ModelWithEvents
{
    use HasFactory;

    protected $table = 'gcm_restricciones_rol_representante';
    protected $primaryKey = ['id_tipo_reunion', 'id_rol'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_tipo_reunion',
        'id_rol',
        'descripcion',
        'estado',
    ];
}
