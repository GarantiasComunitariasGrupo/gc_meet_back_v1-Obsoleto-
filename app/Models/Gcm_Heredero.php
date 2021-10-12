<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gcm_Heredero extends Model
{
    use HasFactory;

    protected $table = 'gcm_herederos';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_heredero'
    ];
}
