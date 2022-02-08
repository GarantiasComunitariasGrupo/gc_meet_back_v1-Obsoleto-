<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Repositorio_Acta extends Model
{
    use HasFactory;

    protected $table = 'gcm_repositorio_actas';
    protected $primaryKey = 'id_repositorio_acta';
    public $timestamps = false;

    protected $fillable = [
        'id_rol_acta',
        'id_reunion',
        'url',
        'estado',
    ];
}
