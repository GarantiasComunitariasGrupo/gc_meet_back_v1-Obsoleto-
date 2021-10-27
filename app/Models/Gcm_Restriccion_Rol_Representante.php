<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Restriccion_Rol_Representante extends Model
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
