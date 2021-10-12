<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Rol extends Model
{
    use HasFactory;

    protected $table = 'gcm_roles';
    protected $primaryKey = 'id_rol';
    public $timestamps = false;

    protected $fillable = [
        'id_rol',
        'id_usuario',
        'descripcion',
        'estado',
        'relacion',
        'fecha',
    ];
}
