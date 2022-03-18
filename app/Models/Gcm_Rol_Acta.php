<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Rol_Acta extends Model
{
    use HasFactory;

    protected $table = 'gcm_roles_actas';
    protected $primaryKey = 'id_rol_acta';
    public $timestamps = false;

    protected $fillable = [
        'id_rol_acta',
        'id_acta',
        'descripcion',
        'firma',
        'acta',
        'estado',
    ];
}
