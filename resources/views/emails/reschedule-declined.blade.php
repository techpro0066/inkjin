<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reschedule request declined</title>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">Your client declined the reschedule request and the booking was cancelled.</div>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF6FF;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="520" style="max-width:520px;width:100%;">
          <tr>
            <td align="left" style="padding:0 0 22px 0;font-family:'Space Grotesk',system-ui,sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.055em;color:#1A1A1A;">bookpay</td>
          </tr>
          <tr>
            <td style="background-color:#ffffff;border-radius:14px;padding:28px 26px;border:1px solid #EEE9F0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr><td align="center" style="font-size:36px;padding:0 0 16px 0;">📅</td></tr>
                <tr><td align="center" style="font-size:22px;font-weight:700;color:#1A1A1A;padding:0 0 16px 0;">Reschedule request declined</td></tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 20px 0;">
                    Hello {{ $booking->artist->name }},<br><br>
                    Unfortunately, {{ $booking->user->name }} has declined your reschedule request and the booking will be cancelled.
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF6FF;border-radius:12px;border:1px solid #EEE9F0;">
                      <tr><td style="padding:16px;font-size:14px;color:#1A1A1A;line-height:1.6;">
                        <strong>Booking cancelled</strong><br>
                        Since the client declined the reschedule request, the booking has been cancelled. A full refund will be processed for the client.
                      </td></tr>
                    </table>
                  </td>
                </tr>
                @if($reason)
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 20px 0;">
                    <strong>Client's reason:</strong> {{ $reason }}
                  </td>
                </tr>
                @endif
                <tr><td style="font-size:15px;color:#3d3942;line-height:1.55;">If you have any questions, please contact support.</td></tr>
              </table>
            </td>
          </tr>
          <tr><td align="left" style="padding:16px 0 0 0;border-top:1px solid #EEE9F0;font-size:12px;color:#6F6874;">© {{ date('Y') }} Bookpay by Inkjin</td></tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
