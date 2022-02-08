<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReunionReprogramada extends Mailable
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
        return $this->subject('Reprogramación de una reunión en plataforma de juntas y asambleas')->view('emails.reunion_reprogramada')->with('data', $this->detalle);;
    }
}
