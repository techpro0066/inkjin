<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ !empty($requirementsReminder) ? 'Complete Stripe requirements' : 'Studio invitation' }} — Bookpay</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    @if(!empty($requirementsReminder))
      {{ $artistName }} needs your studio to complete Stripe information on Bookpay.
    @else
      {{ $artistName }} invited your studio to Bookpay as their {{ $relationshipPhrase }}.
    @endif
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
                @if(!empty($requirementsReminder))
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                      Hi {{ $studioName }},
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                      <strong>{{ $artistName }}</strong> needs your studio to complete additional Stripe information for payouts on Bookpay.
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 28px 0;">
                      Please open the secure link below and submit the required details.
                    </td>
                  </tr>
                  <tr>
                    <td align="center" style="padding:0 0 16px 0;">
                      <a href="{{ $formUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">Complete required information</a>
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;color:#7a7583;line-height:1.5;word-break:break-all;">
                      If the button does not work, copy and paste this URL into your browser:<br>{{ $formUrl }}
                    </td>
                  </tr>
                @else
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                      Hi {{ $studioName }},
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                      <strong>{{ $artistName }}</strong> invited your studio to Bookpay as their {{ $relationshipPhrase }}.
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 28px 0;">
                      Your share of each booking is paid to you automatically. Free for studios.
                    </td>
                  </tr>
                  <tr>
                    <td align="center" style="padding:0 0 16px 0;">
                      <a href="{{ $approveUrl ?: $formUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">See the invitation</a>
                    </td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;color:#7a7583;line-height:1.5;word-break:break-all;">
                      If the button does not work, copy and paste this URL into your browser:<br>{{ $approveUrl ?: $formUrl }}
                    </td>
                  </tr>
                @endif
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#494552;">© {{ date('Y') }} Bookpay by Inkjin</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
