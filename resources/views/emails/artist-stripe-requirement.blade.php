<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Your Bookpay payouts are paused</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Stripe paused your payouts. Bookings still work, but you won't get paid until this is fixed.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF6FF;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="520" style="max-width:520px;width:100%;">
          <tr>
            <td style="background-color:#ffffff;border-radius:14px;padding:28px 26px;border:1px solid #EEE9F0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="font-family:'Space Grotesk',system-ui,sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.055em;color:#1A1A1A;padding:0 0 22px 0;">
                    bookpay
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#1A1A1A;line-height:1.55;padding:0 0 10px 0;">
                    Hi {{ $firstName }},
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 18px 0;">
                    @if (!empty($isStudioPayout))
                      Stripe paused your payouts because your studio&rsquo;s payout account is missing some required information. Bookings and your booking page still work normally, but you won&rsquo;t get paid until this is fixed.
                    @else
                      Stripe paused your payouts because your account is missing some required information. Bookings and your booking page still work normally, but you won&rsquo;t get paid until this is fixed.
                    @endif
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 0 8px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                      <tr>
                        <td style="padding:12px 14px;font-size:14px;color:#6B4A00;line-height:1.55;background:#FFF3DC;">
                          Held payouts will remain paused until Stripe verifies the required account information.
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td style="padding:16px 0 8px 0;">
                    <a href="{{ $actionUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:13px 22px;border-radius:12px;">
                      Fix Stripe account
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="font-size:12.5px;color:#6F6874;line-height:1.5;padding:4px 0 28px 0;">
                    @if (!empty($isStudioPayout))
                      Once the missing details are added, Stripe usually clears the restriction within a day or two, and your held payouts will release automatically.
                    @else
                      Once you add what&rsquo;s needed, Stripe usually clears the restriction within a day or two, and your held payouts will release automatically.
                    @endif
                  </td>
                </tr>
                <tr>
                  <td style="font-size:12px;color:#6F6874;line-height:1.5;padding-top:16px;border-top:1px solid #EEE9F0;">
                    Need help? Reply to this email.<br>
                    Bookpay by Inkjin
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
