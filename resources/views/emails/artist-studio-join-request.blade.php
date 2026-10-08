<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ !empty($hasStudioAccount) ? 'Join request' : 'Studio invitation' }} — Bookpay</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    @if(!empty($hasStudioAccount))
      {{ $artistName }} asked to join {{ $studioName }} on Bookpay.
    @else
      {{ $artistName }} invited your studio to Bookpay.
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
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                    Hi {{ $studioName }},
                  </td>
                </tr>

                @if(!empty($hasStudioAccount))
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                      <strong>{{ $artistName }}</strong> wants to join your studio on Bookpay
                      @if(!empty($relationshipLabel))
                        as a <strong>{{ $relationshipLabel }}</strong>
                      @endif.
                    </td>
                  </tr>
                  @if($artistPercent !== null)
                    <tr>
                      <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                        They proposed a revenue split of <strong>You {{ 100 - (int) $artistPercent }}% · Artist {{ (int) $artistPercent }}%</strong>.
                        You can confirm or suggest a different split when you accept them.
                      </td>
                    </tr>
                  @else
                    <tr>
                      <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                        They’ll be paid directly to their own Stripe account. There’s no revenue split to confirm.
                      </td>
                    </tr>
                  @endif
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 28px 0;">
                      Open your Bookpay studio dashboard to review and accept this request.
                    </td>
                  </tr>
                  <tr>
                    <td align="center" style="padding:0 0 16px 0;">
                      <a href="{{ $actionUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">Open studio dashboard</a>
                    </td>
                  </tr>
                @else
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                      <strong>{{ $artistName }}</strong> invited your studio to Bookpay
                      @if(!empty($relationshipLabel))
                        as their <strong>{{ $relationshipLabel }}</strong>
                      @endif.
                    </td>
                  </tr>
                  @if($artistPercent !== null)
                    <tr>
                      <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;">
                        They proposed a revenue split of <strong>You {{ 100 - (int) $artistPercent }}% · Artist {{ (int) $artistPercent }}%</strong>.
                      </td>
                    </tr>
                  @endif
                  <tr>
                    <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 28px 0;">
                      Accept the invitation to review the request and get set up on Bookpay. Free for studios.
                    </td>
                  </tr>
                  <tr>
                    <td align="center" style="padding:0 0 16px 0;">
                      <a href="{{ $actionUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">Accept invitation</a>
                    </td>
                  </tr>
                @endif

                <tr>
                  <td style="font-size:13px;color:#7a7583;line-height:1.5;word-break:break-all;">
                    If the button does not work, copy and paste this URL into your browser:<br>{{ $actionUrl }}
                  </td>
                </tr>
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
