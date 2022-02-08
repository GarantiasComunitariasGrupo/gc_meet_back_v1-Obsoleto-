<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
                            <td
                                style="text-align:center; padding: 15px; line-height:28px; font-weight:bold; position:relative; border-bottom: 1px solid #E2E2E2;">
                                <div>
                                    <img src="http://burodeconexiones.com/gc_balanced/public/assets/img/test/fondo-con-logo.PNG"
                                        style="width:700px; height:277px; display:block; border:none;">
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- BANNER IMAGE -->

            <tr align="center">
              <td style="padding: 20px; background-color:#ffffff;">
                <h1 style="margin-bottom: 16px; font-size: 30px; color: #171717; font-family: Helvetica">¡Hola, {{$data['nombre']}}!</h1>
                <h1 style="font-size: 15px; color: #545454; font-family: Helvetica">Este es un correo informativo acerca del estado de la reunión que programaste.</h1>
              </td>
            </tr>

            <tr align="center">
                <td style="padding: 20px; background-color:#ffffff; width:100%; padding-bottom:20px;">
                    <div class="col-lge">
                        <h1 style="font-family: Helvetica; font-size: 15px; color: #545454;">
                        Descripción: {{$data['descripcion']}}
                        </h1>
                    </div>
                    <div class="col-lge">
                        <h1 style="font-family: Helvetica; font-size: 15px; color: #545454;">
                        Fecha de la reunión: {{$data['fecha_reunion']}}
                        </h1>
                    </div>
                    <div class="col-lge">
                        <h1 style="font-family: Helvetica; font-size: 15px; color: #545454;">
                        Hora de la reunión: {{$data['hora']}}
                        </h1>
                    </div>
                    <div class="col-lge">
                        <h1 style="font-family: Helvetica; font-size: 15px; color: #545454;">
                        Estado: RETRASADA
                        </h1>
                    </div>
                </td>
            </tr>

            <tr align="center">
                <td style="padding:20px; background-color:#ffffff;">
                    <h1 style="margin: 10px; font-size:15px; color: #545454; font-family: Helvetica">
                      La reunión programada presenta un retraso en su hora de ejecución, si finalizado el dia aun no se le ha dado inicio a la reunión, 
                      entonces esta pasara a estado cancelada y debera reprogramarla.
                    </h1>

                    <a href="http://192.168.2.89:4200/public/login/ingreso" style="margin:0; font-size:28px; text-decoration: none; padding: 15px 15px; border-radius: 4px; display: inline-block;">
                      <span style="font-weight: bold; color: #4883BE; font-family: Helvetica">Haz click aquí para ingresar a la plataforma</span>
                    </a>
                </td>
            </tr>

            <tr>
                <td
                    style="padding:30px; text-align:center; font-size:13px; background-color:#16151E; border-color: #C6D2DF; opacity: 1; color:#BBB9C8;">
                    <p style="margin:0; font-size:14px; line-height:20px; color:#BBB9C8; font-family: Helvetica;">
                      Este es un mensaje automático generado por Garantías Comunitarias, por favor no responda este correo.
                    </p>
                </td>
            </tr>
        </table>

    </center>

</body>

</html>
