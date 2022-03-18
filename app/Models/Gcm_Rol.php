<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Utilities\ModelWithEvents;


class Gcm_Rol extends ModelWithEvents
{
    use HasFactory;

    protected $table = 'gcm_roles';
    protected $primaryKey = 'id_rol';
    public $timestamps = false;

    protected $fillable = [
        'id_rol',
        'descripcion',
        'relacion',
        'estado',
    ];
}
