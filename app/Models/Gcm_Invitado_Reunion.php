<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Invitado_Reunion extends Model
{
    use HasFactory;

    protected $table = 'gcm_invitados_reuniones';
    protected $primaryKey = 'id_invitado_reunion';
    public $timestamps = false;

    protected $fillable = [
        'id_invitado_reunion',
        'id_reunion',
        'id_usuario',
        'id_integrante',
        'email',
        'identificacion'
    ];
}
