<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>You’re booked with {{ $artistName }}</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Your session with {{ $artistName }} is confirmed.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fdf7ff;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;">
          <tr>
            <td align="center" style="padding:0 0 32px 0;font-size:22px;font-weight:700;color:#1c1b21;letter-spacing:-0.3px;font-family:'Space Grotesk','Plus Jakarta Sans',sans-serif;">
              bookpay
            </td>
          </tr>

          <tr>
            <td style="background-color:#ffffff;border-radius:16px;padding:48px 40px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="font-size:16px;color:#1c1b21;line-height:1.6;padding:0 0 16px 0;">
                    Hi {{ $clientFirst }},
                  </td>
                </tr>
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.7;padding:0 0 20px 0;">
                    Your session with {{ $artistName }} is confirmed.
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 0 28px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4efe8;border-radius:12px;">
                      <tr>
                        <td style="padding:20px 24px;font-size:15px;color:#1c1b21;line-height:1.8;">
                          <strong>{{ $title }}</strong><br>
                          {{ $sessionDate }}@if($sessionTime !== '') at {{ $sessionTime }}@endif
                          @if($locationLine !== '') · {{ $locationLine }}@endif<br>
                          Paid: {{ $paidAmount }} &nbsp; Due at session: {{ $dueAmount }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#494552;line-height:1.7;padding:0 0 20px 0;">
                    A separate email is on its way with a few quick questions to get everything ready for your session.
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#494552;line-height:1.7;padding:0 0 28px 0;">
                    Need to change something? Reply to {{ $artistName }} directly or open your booking:
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:0 0 28px 0;">
                    <a href="{{ $viewBookingUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background-color:#1c1b21;color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 28px;border-radius:12px;line-height:1;">
                      View my booking
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="font-size:14px;color:#7a7583;line-height:1.6;">
                    — Bookpay, on behalf of {{ $artistName }}
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
