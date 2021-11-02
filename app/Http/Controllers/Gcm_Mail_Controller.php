<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;
use App\Mail\GestorCorreos;

class Gcm_Mail_Controller extends Controller
{
    public function send($view, $subject, $title, $body, $destinatario)
    {
        $response = array();

        try {

            $data = array(
                'view' => $view,
                'subject' => $subject,
                'title' => $title,
                'body' => $body
            );

            Mail::to($destinatario)->send(new GestorCorreos($data));
            
            $response = array('ok' => true);
            return $response;
        } catch (\Throwable $th) {
            $response = array('ok' => false,' error' => $th->getMessage());
            return $response;
        }

    }
}
