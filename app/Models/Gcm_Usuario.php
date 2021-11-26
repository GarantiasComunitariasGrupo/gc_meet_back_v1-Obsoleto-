<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

//Añadimos la clase JWTSubject
use Tymon\JWTAuth\Contracts\JWTSubject;

class Gcm_Usuario extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'gcm_usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    /**
     * The "type" of the non auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_usuario',
        'nombre',
        'correo',
        'contrasena',
        'estado',
        'tipo',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'contrasena',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     * Devuelve una matriz de valor clave, que contiene cualquier reclamo personalizado que se agregará al JWT.
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return
            [
            //     'email'=> $this->email,
            //     'accionista'=> $this->accionista,
            //     'representante'=> $this->representante,
            //     'identificacion'=> $this->identificacion,
            //     'idRol'=> $this->idRol,
        ];
    }
}
