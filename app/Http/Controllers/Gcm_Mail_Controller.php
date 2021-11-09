<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class Gcm_Mail_Controller extends Controller
{
    public function sendEmail($title, $body, $destinatarios)
    {
        $detalle = [
            'title' => $title,
            'body' => $body
        ];

        Mail::to($destinatarios)->send(new TestMail($detalle));
        return true;
    }
}
