<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500" rel="stylesheet">
    <title>Garantías Comunitarias</title>
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
                <td>
                    <table width="100%">
                        <tr>
                            <td style="text-align:center; line-height:28px; font-weight:bold; position:relative;">
                                <div>
                                    <img src="{{$data['imagen']}}" style="width:700px; height:277px; display:block; border:none;">
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- BANNER IMAGE -->

            <tr align="center">
                <td style="padding: 15px; background-color:#ffffff;">
                    <h1 style="margin-bottom: 10px; font-size: 30px; color: #171717; font-family: Helvetica;">¡Hola, {{$data['nombre']}}!</h1>
                    <h1 style="font-size: 15px; color: #545454; font-family: Helvetica;">Has sido convocado a la reunión de {{$data['titulo']}}</h1>
                </td>
            </tr>

            <tr align="center">
                <td style="padding: 10px; background-color:#ffffff; width:100%; padding-bottom: 10px;">
                    <div class="col-lge">
                        <h1 style="font-family: Helvetica; font-size: 15px; color: #545454;">
                            {{$data['descripcion']}}
                        </h1>
                    </div>
                </td>
            </tr>

            <tr align="center">
                <td style="padding: 15px; background-color:#ffffff;">
                    <table class="col-lge" style="padding-bottom: 15px; font-size:16px; color:#363636; margin-left: auto; margin-right: auto;">
                        <tr>
                            <td style="margin-top:0; margin-bottom: 12px; padding-right: 12px;">
                                <img style="width:50px; height: 50px;" src="{{env('API_BASE') . '/storage/images/mail/calendar-bk.png'}}">
                                <div style="align-self: center; text-align: left; padding-left: 15px; display: inline-block;">
                                    <div style="font-size: 16px; color: #545454; font-family: Helvetica;">Fecha:</div>
                                    <div style="font-size: 25px; color: #171717; font-family: Helvetica;">{{$data['fecha_reunion']}}</div>
                                </div>
                            </td>

                            <td style="margin-top:0; margin-bottom: 12px; padding-left: 12px;">
                                <img style="width: 50px; height: 50px;" src="{{env('API_BASE') . '/storage/images/mail/clock-bk.png'}}">
                                <div style="align-self: center; text-align: left; padding-left: 15px; display: inline-block;">
                                    <div style="font-size: 16px; color: #545454; font-family: Helvetica;">Hora</div>
                                    <div style="font-size: 25px; color: #171717; font-family: Helvetica;">{{$data['hora']}}</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr align="lef">
                <td style="padding: 15px; background-color:#ffffff;">
                    @if(count($data['programas']) > 0)
                    <h1 style="margin-bottom: 16px; font-size: 15px; color: #171717; font-family: Helvetica; font-weight: bold;">Orden del día</h1>
                    @endif
                    @for($i = 0; $i < count($data['programas']); $i++) <p style="font-size: 15px; color: #545454; font-family: Helvetica;">
                        <b>{{$data['programas'][$i]['orden']}}</b>. {{$data['programas'][$i]['titulo']}}
                        @php
                        $letra = 'a';
                        @endphp
                        @for($j = 0; $j < count($data['programas'][$i]['opciones']); $j++) <p style="margin-left: 15px; font-size: 15px; color: #545454; font-family: Helvetica;">
                        <b>{{$letra++}}</b>. {{$data['programas'][$i]['tipo'] == '5' ?  strtoupper($data['programas'][$i]['opciones'][$j]['descripcion']) : $data['programas'][$i]['opciones'][$j]['titulo']}}
                        </p>
                        @endfor
                        </p>
                        @endfor
                </td>
            </tr>

            <tr align="center">
                <td style="padding: 15px; background-color:#ffffff;">
                    <h1 style="margin: 10px; font-size:15px; color: #545454; font-family: Helvetica;">
                        Puedes ingresar a través del siguiente enlace:
                    </h1>

                    <a href="{{$data['url']}}" style="background: #9F8C5B; margin:0; font-size: 16px; text-decoration: none; padding: 10px 15px; color: #171717; border-radius: 4px; display: inline-block;">
                        <span style="font-weight: bold; color: #FFFFFF; font-family: Helvetica;">Haz click aquí para ingresar</span>
                    </a>

                    <p style="margin: 12px; font-size: 14px; color: #545454; font-family: Helvetica;">
                        Este enlace es único e intransferible, además es la única manera de ingresar a la reunión
                    </p>
                </td>
            </tr>

            <tr>
                <td style="padding: 30px; text-align:center; background-color:#16151E; border-color: #C6D2DF; opacity: 1; color:#BBB9C8;">
                    <p style="margin: 0; font-size:13px; line-height:20px; color:#BBB9C8; font-family: Helvetica;">
                        Este es un mensaje automático generado por Garantías Comunitarias, por favor no responda este correo.
                    </p>
                </td>
            </tr>
        </table>

    </center>

</body>

</html>
