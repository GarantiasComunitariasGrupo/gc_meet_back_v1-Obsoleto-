<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Utilities\ModelWithEvents;


class Gcm_Recurso extends ModelWithEvents
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
