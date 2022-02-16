<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $detalle;

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
        return $this->subject('Invitación reunión en plataforma de juntas y asambleas')->view('emails.invitacion')->with('data', $this->detalle);
        // return $this->subject('Prueba de correo de Gc_Meet')->view('emails.mensaje')->with('data', $this->detalle);
    }
}
