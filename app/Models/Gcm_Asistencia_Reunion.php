<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Asistencia_Reunion extends Model
{
    use HasFactory;

    protected $table = 'gcm_asistencia_reuniones';
    protected $primaryKey = 'id_convocado_reunion';
    public $timestamps = false;

    protected $fillable = [
        'id_convocado_reunion',
        'fecha_ingreso',
        'fecha_salida',
        'estado',
    ];
}
