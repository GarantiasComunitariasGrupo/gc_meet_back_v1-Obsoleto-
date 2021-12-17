<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500" rel="stylesheet">
<script src="https://kit.fontawesome.com/1e30f8602f.js" crossorigin="anonymous"></script>
  <title></title>
  <style>
    table, td, div, h1, p {
      font-family: Montserrat;
    }
    @media screen and (max-width: 530px) {
      .unsub {
        display: block;
        padding: 8px;
        margin-top: 14px;
        border-radius: 6px;
        background-color: #555555;
        text-decoration: none !important;
        font-weight: bold;
      }
      .col-lge {
        max-width: 100% !important;
      }
    }
    @media screen and (min-width: 531px) {
      .col-sml {
        max-width: 27% !important;
      }
      .col-lge {
        max-width: 73% !important;
      }
    }
  </style>
</head>
<body style="margin:0;padding:0;word-spacing:normal;background-color:#939297;">
  <div role="article" aria-roledescription="email" lang="en" style="text-size-adjust:100%; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; background-color:#939297;">
    <table role="presentation" style="width:100%;">
      <tr>
        <td align="center" style="padding: 1rem;">
          <table role="presentation" style="width:94%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:Arial,sans-serif;font-size:16px;line-height:22px;color:#363636;">

            <tr>
              <td style="padding:0; line-height:28px; font-weight:bold; position:relative;">
                <div>
                    <img src="C:\Users\dgarciag\Desktop\Zurich\front\src\assets\img\fondo_correo.jpg" style="width:100%; height:auto; display:block; border:none;">
                </div> 
                <div style="position:absolute; width: 70%; height: 55%; top: 50%; left: 50%; transform: translate(-50%, -50%)">
                    <img src="C:\Users\dgarciag\Desktop\Zurich\front\src\assets\img\gcm-icon.png" style="width:100%; height:auto; display:block; border:none;">
                </div>
              </td>
            </tr>

            <tr align="center">
              <td style="padding: 30px; background-color:#ffffff;">
                <h1 style="margin-bottom: 16px; font-size: 30px; color: #171717;">¡Hola, {{$data['nombre']}}!</h1>
                <p style="font-size: 15px; color: #545454">Has sido invitado a {{$data['titulo']}}</p>
              </td>
            </tr>

            <tr align="center">
              <td style="padding: 20px; background-color:#ffffff; width:100%; padding-bottom:20px; color:#545454; font-size: 15px; color: #545454;">
                <div class="col-lge">
                  <p>
                    {{$data['descripcion']}}
                  </p>
                </div>
              </td>
            </tr>

            <tr align="center">
              <td style="padding: 20px; background-color:#ffffff;">
                <div class="col-lge" style="width:100%; padding-bottom:20px; font-size:16px; color:#363636; display: flex; justify-content: space-around;">

                  <div style="margin-top:0; margin-bottom:12px; font-size:14px; color: #545454; display: flex;">
                    <img src="C:\Users\dgarciag\Desktop\Zurich\front\src\assets\img\calendario.png">
                    <div style="align-self: center; text-align: left; padding-left: 15px;">
                      <div>Fecha</div>
                      <div style="font-size: 30px; color: #171717">{{$data['fecha_reunion']}}</div>
                    </div>
                  </div>

                  <div style="margin-top:0; margin-bottom:12px; font-size:14px; color: #545454; display: flex;">
                    <img src="C:\Users\dgarciag\Desktop\Zurich\front\src\assets\img\hora.png">
                    <div style="align-self: center; text-align: left; padding-left: 15px;">
                      <div>Hora</div>
                      <div style="font-size: 30px; color: #171717">{{$data['hora']}}</div>
                    </div>
                  </div>

                </div>
              </td>
            </tr>

            <tr align="lef">
              <td style="padding: 20px; background-color:#ffffff;">
                <h1 style="margin-bottom: 16px; font-size: 15px; color: #171717">Orden del dia</h1>
                @for($i = 0; $i < count($data['programas']); $i++)
                <p style="font-size: 15px; color: #545454">
                  {{$data['programas'][$i]['orden']}}. {{$data['programas'][$i]['titulo']}}
                </p>
                @endfor
              </td>
            </tr>

            <tr align="center">
              <td style="padding:20px; background-color:#ffffff;">
                <p style="margin: 10px; font-size:15px; color: #545454">
                    Puedes ingresar a través del siguiente enlace:
                </p>

                <a href="gcmeet.com/public/acceso-reunion/.$valorEncriptado" style="margin:0; font-size:22px; background: #4883BE; text-decoration: none; padding: 15px 15px; color: #171717; border-radius: 4px; display: inline-block;">
                    <span style="font-weight:bold;">{{$data['url']}}</span>
                </a>
                
                <p style="margin: 12px; font-size:13px; color: #545454">
                    Este enlace es único e intransferible
                </p>
              </td>
            </tr>

            <tr>
              <td style="padding:30px; text-align:center; font-size:13px; background-color:#16151E; border-color: #C6D2DF; opacity: 1; color:#BBB9C8;">
                <p style="margin:0; font-size:14px; line-height:20px; color:#BBB9C8;">
                    Este es un mensaje automático generado por Garantías Comunitarias, por favor no responda este correo.
                </p>
              </td>
            </tr>

          </table>

        </td>
      </tr>
    </table>
  </div>
</body>
</html>