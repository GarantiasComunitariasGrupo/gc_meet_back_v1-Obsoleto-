<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReunionVencida extends Mailable
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
        return $this->subject('Alerta de reunión vencida en plataforma de juntas y asambleas')->view('emails.reunion_vencida')->with('data', $this->detalle);;
    }
}
