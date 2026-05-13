<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Verify your email — {{ config('app.name', 'Inkjin') }}</title>
    <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
    <style type="text/css">
      body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
      table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
      img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
      body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
    </style>
  </head>
  <body style="margin: 0; padding: 0; background-color: #fdf7ff; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
      Your verification code for {{ config('app.name', 'Inkjin') }} Book &amp; Pay.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #fdf7ff;">
      <tr>
        <td align="center" style="padding: 40px 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; width: 100%;">
            <tr>
              <td align="center" style="padding: 0 0 32px 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 28px; font-weight: 800; color: #310f7a; letter-spacing: -0.5px;">
                      {{ strtolower(config('app.name', 'Inkjin')) }}
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <tr>
              <td style="background-color: #ffffff; border-radius: 16px; padding: 48px 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                  <tr>
                    <td align="center" style="padding: 0 0 24px 0;">
                      <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                          <td style="background-color: #F8F1FB; border-radius: 12px; width: 48px; height: 48px; text-align: center;">
                            <img src="{{ asset('design/images/icons/favicon.png') }}" alt="Inkjin" style="width: 48px; height: 48px; object-fit: cover;">
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                  <tr>
                    <td align="center" style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 24px; font-weight: 700; color: #1c1b21; line-height: 1.3; padding: 0 0 12px 0;">
                      Verify your email address
                    </td>
                  </tr>

                  <tr>
                    <td style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 16px; color: #494552; line-height: 1.6; padding: 0 0 24px 0; text-align: center;">
                      Thanks for signing up. Enter this 4-digit code on the verification page to confirm your email.
                    </td>
                  </tr>

                  <tr>
                    <td align="center" style="padding: 0 0 28px 0;">
                      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8F1FB; border: 1px solid #e6e0ea; border-radius: 16px;">
                        <tr>
                          <td style="padding: 28px 48px; text-align: center;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #310f7a; text-transform: uppercase; letter-spacing: 0.12em;">Your code</p>
                            <p style="margin: 0; font-size: 36px; font-weight: 800; letter-spacing: 0.35em; color: #1c1b21; font-family: 'Plus Jakarta Sans', ui-monospace, monospace;">{{ $code }}</p>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                  <tr>
                    <td style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 13px; color: #494552; line-height: 1.5; text-align: center; padding: 0 0 16px 0;">
                      This code expires in {{ $expiresMinutes }} minutes. If you did not create an account, you can ignore this email.
                    </td>
                  </tr>

                  <tr>
                    <td style="padding: 8px 0 0 0;">
                      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                        <tr>
                          <td style="border-top: 1px solid #F8F1FB;"></td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <tr>
              <td align="center" style="padding: 32px 0 0 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td style="font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 12px; color: #494552; line-height: 1.5; text-align: center;">
                      &copy; {{ date('Y') }} {{ config('app.name', 'Inkjin') }}. All rights reserved.
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
