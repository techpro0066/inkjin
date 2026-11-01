<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Studio invitation — Bookpay</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $studioName }} invited you to join on Bookpay.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fdf7ff;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;">
          <tr>
            <td align="center" style="padding:0 0 32px 0;font-size:28px;font-weight:800;color:#310f7a;">bookpay</td>
          </tr>
          <tr>
            <td style="background-color:#ffffff;border-radius:16px;padding:48px 40px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                    Hi,
                  </td>
                </tr>
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                    <strong>{{ $studioName }}</strong> invited you to join their studio on Bookpay
                    @if(!empty($relationshipLabel))
                      as a <strong>{{ $relationshipLabel }}</strong>
                    @endif.
                  </td>
                </tr>
                @if($artistPercent !== null)
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                      They offered a revenue split of <strong>Artist {{ (int) $artistPercent }}% · Studio {{ 100 - (int) $artistPercent }}%</strong>.
                    </td>
                  </tr>
                @else
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                      You’ll be paid directly for bookings (workstation rent — no split).
                    </td>
                  </tr>
                @endif
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 28px 0;">
                    @if(!empty($hasBookpayAccount))
                      Open Bookpay to review and accept the invite.
                    @else
                      Create a Bookpay account from the link below, then accept the invite.
                    @endif
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:0 0 16px 0;">
                    <a href="{{ $actionUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">Review invite</a>
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
