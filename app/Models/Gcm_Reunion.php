<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Utilities\ModelWithEvents;

class Gcm_Reunion extends ModelWithEvents
{
    use HasFactory;

    protected $table = 'gcm_reuniones';
    protected $primaryKey = 'id_reunion';
    public $timestamps = false;

    protected $fillable = [
        'id_reunion',
        'id_tipo_reunion',
        'descripcion',
        'fecha_actualizacion',
        'fecha_reunion',
        'hora',
        'quorum',
        'id_acta',
        'programacion',
        'estado',
        'acta',
    ];

}
