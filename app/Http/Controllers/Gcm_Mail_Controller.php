<?php

namespace App\Http\Controllers;

use App\Mail\GestorCorreos;
use Illuminate\Support\Facades\Mail;

class Gcm_Mail_Controller extends Controller
{
    public function send($view, $message, $title, $body, $destinatario)
    {
        $response = array();

        try {
            $data = array(
                'view' => $view,
                'message' => $message,
                'title' => $title,
                'body' => json_decode($body, true),
            );

            Mail::to($destinatario)->send(new GestorCorreos($data));

            $response = array('ok' => true);
            return $response;
        } catch (\Throwable $th) {
            $response = array('ok' => false, 'error' => $th->getMessage());
            return $response;
        }
    }

    public function sendEmail($title, $detalle, $destinatarios)
    {
        Mail::to($destinatarios)->send(new GestorCorreos($detalle));
        return true;
    }
}
