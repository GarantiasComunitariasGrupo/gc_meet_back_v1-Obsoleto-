<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertaReunion extends Mailable
{
    use Queueable, SerializesModels;

    public $detalle;

    // public $subject = 'Informacion de contacto';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($detalle)
    {
        $this->detalle = $detalle;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Alerta de la reunión programa para hoy en la plataforma de juntas y asambleas')->view('emails.alerta_reunion')->with('data', $this->detalle);
        // return $this->subject('Prueba de correo de Gc_Meet')->view('emails.mensaje')->with('data', $this->detalle);
    }
}
