<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>How was your session — Bookpay</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Tell us how your session with {{ $clientDisplayName }} went.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF6FF;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="520" style="max-width:520px;width:100%;">
          <tr>
            <td style="background-color:#ffffff;border-radius:14px;border:1px solid #EEE9F0;padding:28px 26px;overflow:hidden;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="font-family:'Space Grotesk',system-ui,sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.055em;color:#1A1A1A;padding:0 0 22px 0;">
                    bookpay
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 0 20px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                      <tr>
                        <td style="padding:12px;width:56px;vertical-align:middle;">
                          <div style="width:44px;height:44px;border-radius:50%;background:#F3E8FF;color:#3E007C;font-weight:800;font-size:14px;line-height:44px;text-align:center;">{{ $clientInitials }}</div>
                        </td>
                        <td style="padding:12px 12px 12px 0;vertical-align:middle;">
                          <div style="font-size:15px;font-weight:700;color:#1A1A1A;">{{ $clientDisplayName }}</div>
                          <div style="font-size:13px;color:#6F6874;margin-top:2px;">{{ $sessionMeta }}</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="font-size:15px;color:#1A1A1A;line-height:1.55;padding:0 0 10px 0;">
                    Hi {{ $artistFirstName }},
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 8px 0;">
                    Your session with {{ $clientFirstName }} is marked complete. A quick note about how it went helps Bookpay and future bookings.
                  </td>
                </tr>

                <tr>
                  <td align="center" style="padding:24px 0 8px 0;">
                    <a href="{{ $feedbackUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 28px;border-radius:12px;">
                      Submit feedback
                    </a>
                  </td>
                </tr>
                <tr>
                  <td align="center" style="font-size:12.5px;color:#6F6874;line-height:1.45;padding:0 0 8px 0;">
                    {{ $clientFirstName }} doesn’t see your answers. Feedback about Bookpay stays with us.
                  </td>
                </tr>

                <tr>
                  <td style="padding:28px 0 0 0;border-top:1px solid #EEE9F0;font-size:12px;color:#6F6874;line-height:1.5;">
                    <div style="padding-top:16px;">
                      You got this email because you completed a session booked through Bookpay.<br>
                      Bookpay by Inkjin
                    </div>
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
