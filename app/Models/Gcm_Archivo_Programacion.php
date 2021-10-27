<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Archivo_Programacion extends Model
{
    use HasFactory;

    protected $table = 'gcm_archivos_programacion';
    protected $primaryKey = 'id_archivo_programacion';
    public $timestamps = false;

    protected $fillable = [
        'id_archivo_programacion',
        'id_programa',
        'descripcion',
        'peso',
        'url',
    ];
}
