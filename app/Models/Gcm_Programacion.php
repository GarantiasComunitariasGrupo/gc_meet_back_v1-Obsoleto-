<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Programacion extends Model
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
        'estado',
    ];
}
