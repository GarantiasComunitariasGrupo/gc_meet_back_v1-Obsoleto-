<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Pregunta extends Model
{
    use HasFactory;

    protected $table = 'gcm_preguntas';
    protected $primaryKey = 'id_pregunta';
    public $timestamps = false;

    protected $fillable = [
        'id_pregunta',
        'id_reunion',
        'descripcion',
        'titulo',
        'orden',
        'tipo',
        'relacion',
        'extra',
    ];
}
