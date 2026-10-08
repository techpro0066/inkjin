@php
  $otp = (string) ($code ?? '');
  $expires = (int) ($expiresMinutes ?? 10);
  $first = trim((string) ($user->first_name ?? ''));
  if ($first === '' && ! empty($user->name)) {
      $first = explode(' ', trim((string) $user->name))[0];
  }
  $greeting = $first !== '' ? 'Hi '.$first.',' : 'Hi,';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Your Bookpay code: {{ $otp }}</title>
  <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1A1A1A;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Your Bookpay code: {{ $otp }}
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF6FF;">
    <tr>
      <td align="center" style="padding:28px 16px 56px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:520px;width:100%;">
          <tr>
            <td style="background-color:#ffffff;border:1px solid #EEE9F0;border-radius:14px;padding:28px 26px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="font-family:'Space Grotesk','Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-weight:700;font-size:26px;letter-spacing:-0.055em;color:#1A1A1A;padding:0 0 22px 0;">
                    bookpay
                  </td>
                </tr>
                <tr>
                  <td style="font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:15px;line-height:1.55;color:#1A1A1A;padding:0 0 10px 0;">
                    {{ $greeting }}
                  </td>
                </tr>
                <tr>
                  <td style="font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:15px;line-height:1.55;color:#3d3942;padding:0 0 4px 0;">
                    Use this code to confirm your email.
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:20px 0 4px 0;" aria-label="Code {{ implode(' ', str_split($otp)) }}">
                    <div style="font-family:'Space Grotesk','Plus Jakarta Sans',ui-monospace,monospace;font-weight:700;font-size:34px;letter-spacing:12px;text-align:center;background:#F3E8FF;color:#3E007C;border-radius:12px;padding:14px 0 14px 12px;">
                      {{ $otp }}
                    </div>
                  </td>
                </tr>
                <tr>
                  <td style="font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12.5px;line-height:1.5;color:#6F6874;padding:12px 0 0 0;">
                    It works for {{ $expires }} minutes. If you didn&rsquo;t ask for it, you can ignore this email.
                  </td>
                </tr>
                <tr>
                  <td style="font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;color:#6F6874;padding:28px 0 0 0;border-top:1px solid #EEE9F0;">
                    <div style="padding-top:16px;">Bookpay by Inkjin</div>
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
