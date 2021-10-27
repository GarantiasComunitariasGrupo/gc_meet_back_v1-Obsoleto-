<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Grupo extends Model
{
    use HasFactory;

    protected $table = 'gcm_grupos';
    protected $primaryKey = 'id_grupo';
    public $timestamps = false;

    protected $fillable = [
        'id_grupo',
        'acceso',
        'descripcion',
        'imagen',
        'logo',
        'estado',
    ];
}
