<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class Gcm_Mail_Controller extends Controller
{
    public function sendEmail($title, $detalle, $destinatarios)
    {
        Mail::to($destinatarios)->send(new TestMail($detalle));
        return true;
    }
}
