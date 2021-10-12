<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Archivo_Pregunta extends Model
{
    use HasFactory;

    protected $table = 'gcm_archivos_preguntas';
    protected $primaryKey = 'id_archivo_pregunta';
    public $timestamps = false;

    protected $fillable = [
        'id_archivo_pregunta',
        'id_pregunta',
        'tipo',
        'descripcion',
        'url',
    ];
}
