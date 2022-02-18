<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Acta extends Model
{
    use HasFactory;

    protected $table = 'gcm_actas';
    protected $primaryKey = 'id_acta';
    public $timestamps = false;

    protected $fillable = [
        'id_acta',
        'descripcion',
        'plantilla',
        'estado',
    ];
}
