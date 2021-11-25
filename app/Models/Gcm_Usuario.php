<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

//Añadimos la clase JWTSubject 
use Tymon\JWTAuth\Contracts\JWTSubject;

class Gcm_Usuario extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'gcm_usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

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
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     * Devuelve una matriz de valor clave, que contiene cualquier reclamo personalizado que se agregará al JWT.
     * @return array
     */
    public function getJWTCustomClaims() {
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
