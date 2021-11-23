<?php

namespace App\Models;

use App\Utilities\ModelWithEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gcm_Convocado_Reunion extends ModelWithEvents
{
    use HasFactory;

    protected $table = 'gcm_convocados_reunion';
    protected $primaryKey = 'id_convocado_reunion';
    public $timestamps = false;

    protected $fillable = [
        'id_convocado_reunion',
        'id_reunion',
        'representacion',
        'id_relacion',
        'fecha',
        'tipo',
        'nit',
        'razon_social',
        'participacion',
        'soporte',
        'estado',
    ];

}
