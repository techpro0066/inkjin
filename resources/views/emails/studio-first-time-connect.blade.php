<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connect Stripe for studio payouts</title>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">{{ $artistName }} selected your studio for payouts. Connect Stripe to receive payments.</div>
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
                <tr><td align="center" style="font-size:36px;padding:0 0 16px 0;">💳</td></tr>
                <tr><td align="center" style="font-size:22px;font-weight:700;color:#1A1A1A;padding:0 0 16px 0;">Connect Stripe for studio payouts</td></tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 20px 0;text-align:center;">
                    Hello {{ $studioName }},<br><br>
                    <strong>{{ $artistName }}</strong> selected your studio for payouts on {{ config('app.name', 'Inkjin') }}. This is the first request for this studio email; connect your Stripe account to receive payments.
                  </td>
                </tr>
                <tr>
                  <td align="center">
                    <a href="{{ $connectUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:13px 22px;border-radius:12px;">Connect Stripe</a>
                  </td>
                </tr>
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
