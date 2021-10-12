<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Tipo_Reunion extends Model
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
        'estado'
    ];
}
