<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Welcome to Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin: 0; padding: 0; background-color: #FFF6FF; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
  <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
    Welcome to Inkjin — manage your bookings, appointments, and more from one place.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #FFF6FF;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="520" style="max-width: 520px; width: 100%;">
          <tr>
            <td align="center" style="padding: 0 0 32px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="font-size: 26px; font-weight: 700; color: #1A1A1A; letter-spacing: -0.055em; font-family: 'Space Grotesk', system-ui, sans-serif;">
                    bookpay
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="background-color: #ffffff; border-radius: 14px; padding: 28px 26px; border: 1px solid #EEE9F0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td align="center" style="padding: 0 0 20px 0; font-size: 36px;">✨</td>
                </tr>
                <tr>
                  <td align="center" style="font-size: 22px; font-weight: 700; color: #1A1A1A; line-height: 1.3; padding: 0 0 12px 0;">
                    Welcome to Inkjin
                  </td>
                </tr>
                <tr>
                  <td style="font-size: 15px; color: #3d3942; line-height: 1.6; padding: 0 0 28px 0; text-align: center;">
                    @if(trim($recipientName ?? '') !== '')
                      Hi {{ $recipientName }},<br><br>
                    @endif
                    Thanks for completing your first booking with us. Your payment went through and your appointment details are on the way (check your inbox for confirmation).
                  </td>
                </tr>
                <tr>
                  <td style="padding: 0 0 32px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #FFF6FF; border-radius: 12px; border: 1px solid #EEE9F0;">
                      <tr>
                        <td style="padding: 24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td style="font-size: 14px; font-weight: 700; color: #1A1A1A; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 16px 0;">
                                Here's what you can do next
                              </td>
                            </tr>
                            <tr>
                              <td style="font-size: 15px; color: #1A1A1A; line-height: 2;">
                                ✦ View and manage your bookings<br>
                                ✦ Reschedule or cancel within the artist's policy<br>
                                ✦ Keep your completion code handy for session day<br>
                                ✦ Discover more artists anytime on Inkjin
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding: 0 0 16px 0;">
                    <a href="{{ $bookingsUrl }}" target="_blank" style="display: inline-block; background: #1A1A1A; color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; padding: 13px 22px; border-radius: 12px; line-height: 1;">
                      View my bookings
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td align="left" style="padding: 16px 0 0 0; border-top: 1px solid #EEE9F0; font-size: 12px; color: #6F6874; line-height: 1.5;">
              © {{ date('Y') }} Bookpay by Inkjin
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
