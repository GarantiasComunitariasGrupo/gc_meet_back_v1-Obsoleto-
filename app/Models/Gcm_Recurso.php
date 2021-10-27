<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Recurso extends Model
{
    use HasFactory;

    protected $table = 'gcm_recursos';
    protected $primaryKey = 'id_recurso';
    public $timestamps = false;

    protected $fillable = [
        'id_recurso',
        'identificacion',
        'nombre',
        'telefono',
        'correo',
        'estado'
    ];
}
