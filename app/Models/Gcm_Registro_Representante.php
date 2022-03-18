<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Registro_Representante extends Model
{
    use HasFactory;

    protected $table = 'gcm_registro_representantes';
    protected $primaryKey = ['id_recurso', 'id_reunion', 'identificacion'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_recurso',
        'id_reunion',
        'identificacion',
        'url_archivo',
        'estado'
    ];
}
