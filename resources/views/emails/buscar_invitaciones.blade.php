<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Formato correo electrónico</title>
    <style type="text/css">
        body {
            margin: 0;
            background-color: #f4f4f4;
        }

        table {
            border-spacing: 0;
        }

        td {
            padding: 0;
        }

        img {
            border: 0;
        }

        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f4f4;
            padding-bottom: 40px;
        }

        .main {
            background-color: #ffffff;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-spacing: 0;
        }
    </style>
</head>

<body>
    <center class="wrapper">
        <table class="main" width="100%">

            <!-- LOGO SECTION -->
            <tr>
                <td style="text-align:center; line-height:28px; font-weight:bold; position:relative;">
                    <div>
                        <img src="{{env('API_BASE') . '/storage/images/mail/GCL.jpg'}}"
                            style="width:700px; height:277px; display:block; border:none;">
                    </div>
                </td>
            </tr>

            <!-- BANNER IMAGE -->

            <tr align="center">
                <td style="padding: 15px; background-color:#ffffff;">
                    <h1 style="margin-bottom: 10px; font-size: 30px; color: #171717; font-family: Helvetica">¡Hola,
                        Danilo!</h1>
                    <h1 style="font-size: 15px; color: #545454; font-family: Helvetica">Has sido convocado a las
                        siguientes reuniones en la plataforma de GcMeet:</h1>
                </td>
            </tr>

            @for($i = 0; $i < count($data['body']); $i++) 
            <tr>
                <td>
                    <div class="card"
                        style="background: #F8F8F8; margin: 1.5rem; padding: 1rem; border-radius: 7px; align-items: center;">

                        <table style="font-size: 12px; color:#363636;" align="center">

                            <tr>
                                <td>
                                    <h1
                                        style="font-family: Helvetica; color: #545454; font-size: 1rem; margin-right: auto; margin-left: auto;">
                                        {{$data['body'][$i]['descripcion']}}</h1>
                                </td>
                                <td align="end">
                                    <img style="width: 40px; height: 40px;"
                                        src="{{env('API_BASE') . '/storage/images/mail/calendar-bk.png'}}">
                                    <div
                                        style="align-self: center; text-align: left; padding-left: 15px; padding-right: 16px; display: inline-block;">
                                        <div
                                            style="font-size: 14px; color: #545454; font-family: Helvetica; font-weight: bold;">
                                            Fecha:
                                        </div>
                                        <div style="font-size: 20px; color: #171717; font-family: Helvetica">{{$data['body'][$i]['fecha_reunion']}}</div>
                                    </div>

                                    <img style="width: 40px; height: 40px;"
                                        src="{{env('API_BASE') . '/storage/images/mail/clock-bk.png'}}">
                                    <div
                                        style="align-self: center; text-align: left; padding-left: 15px; display: inline-block;">
                                        <div
                                            style="font-size: 14px; color: #545454; font-family: Helvetica; font-weight: bold;">
                                            Hora:</div>
                                        <div style="font-size: 20px; color: #171717; font-family: Helvetica">{{$data['body'][$i]['hora']}}</div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding-top: 10px;">
                                    <h1
                                        style="font-size: 1rem; color: #545454; font-family: Helvetica; margin-right: auto; margin-left: auto;">
                                        Enlace para ingresar:
                                    </h1>
                                </td>
                                <td style="padding-top: 10px;" align="end">
                                    <a href="{{$data['title'][$i]}}"
                                        style="background: #9F8C5B; margin:0; font-size: 16px; text-decoration: none; padding: 8px 13px; color: #171717; border-radius: 4px; display: inline-block;">
                                        <span style="font-weight: bold; color: #FFFFFF; font-family: Helvetica">Haz click aquí para ingresar</span>
                                    </a>
                                </td>
                            </tr>
                            
                        </table>
                    </div>
                </td>
            </tr>
            @endfor

            <tr>
                <td
                    style="padding: 30px; text-align:center; background-color:#16151E; border-color: #C6D2DF; opacity: 1; color:#BBB9C8;">
                    <p style="margin: 0; font-size:13px; line-height:20px; color:#BBB9C8; font-family: Helvetica;">
                        Este es un mensaje automático generado por Garantías Comunitarias, por favor no responda este
                        correo.
                    </p>
                </td>
            </tr>

        </table>
    </center>
</body>

</html>