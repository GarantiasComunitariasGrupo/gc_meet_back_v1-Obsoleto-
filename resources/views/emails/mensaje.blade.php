<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500" rel="stylesheet">
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
                <h1 style="margin-bottom: 16px; font-size: 30px;">¡Hola, {{$data['nombre']}}!</h1>
                <p style="font-size: 15px; color: #545454">Has sido invitado a {{$data['titulo']}}</p>
              </td>
            </tr>


            <tr align="center">
              <td style="padding:35px 30px 11px 30px;font-size:0;background-color:#ffffff;border-bottom:1px solid #f0f0f5;border-color:rgba(201,201,207,.35);">
                
                <div class="col-lge" style="display:inline-block;width:100%;max-width:395px;vertical-align:top;padding-bottom:20px;font-family:Arial,sans-serif;font-size:16px;line-height:22px;color:#363636;">
                    <p style="margin-top:0;margin-bottom:12px;">
                        Nullam mollis sapien vel cursus fermentum. Integer porttitor augue id ligula facilisis pharetra. In eu ex et elit ultricies ornare nec ac ex. Mauris sapien massa, placerat non venenatis et, tincidunt eget leo.
                    </p>
                    <p style="margin-top:0;margin-bottom:18px;">
                        Nam non ante risus. Vestibulum vitae eleifend nisl, quis vehicula justo. Integer viverra efficitur pharetra. Nullam eget erat nibh.
                    </p>
                    
                </div>
              </td>
            </tr>

            <tr align="center">
              <td style="padding:30px;background-color:#ffffff;">
                <p style="margin:0; font-size:15px; color: #545454">
                    Puedes ingresar a través del siguiente enlace:
                </p>
                <a href="https://example.com/" style="margin:0; font-size:25px; background: #4883BE; text-decoration: none; padding: 15px 15px; color: #171717; border-radius: 4px; display: inline-block;">
                    <span style="font-weight:bold;">www.enlace.com</span>
                </a>
                <p style="margin:0; font-size:13px; color: #545454">
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