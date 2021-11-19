<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Utilities\ModelWithEvents;


class Gcm_Tipo_Reunion extends ModelWithEvents
{
    use HasFactory;

    protected $table = 'gcm_tipo_reuniones';
    protected $primaryKey = 'id_tipo_reunion';
    public $timestamps = false;

    protected $fillable = [
        'id_tipo_reunion',
        'id_grupo',
        'titulo',
        'honorifico_participante',
        'honorifico_invitado',
        'honorifico_representante',
        'imagen',
        'estado',
    ];
}
