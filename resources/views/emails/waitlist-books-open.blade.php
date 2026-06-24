<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Books are open</title>
</head>
<body style="margin: 0; padding: 0; background-color: #fdf7ff; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #fdf7ff;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; width: 100%;">
          <tr>
            <td align="center" style="padding: 0 0 32px 0; font-size: 28px; font-weight: 800; color: #310f7a; letter-spacing: -0.5px;">
              inkjin
            </td>
          </tr>
          <tr>
            <td style="background-color: #ffffff; border-radius: 16px; padding: 48px 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
              <p style="margin: 0 0 12px 0; font-size: 24px; font-weight: 700; color: #1c1b21; line-height: 1.3; text-align: center;">
                {{ $artistName }} is now accepting bookings
              </p>
              <p style="margin: 0 0 28px 0; font-size: 16px; color: #494552; line-height: 1.6; text-align: center;">
                Hi {{ $recipientName }},<br><br>
                You asked to be notified when {{ $artistName }} opened their books — they're ready for you now. Visit their page to browse designs and book your session.
              </p>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td align="center" style="padding: 0 0 24px 0;">
                    <a href="{{ $profileUrl }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; background: linear-gradient(135deg, #310f7a 0%, #482d91 100%); color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 12px;">
                      View artist page
                    </a>
                  </td>
                </tr>
              </table>
              <p style="margin: 0; font-size: 13px; color: #7a7583; line-height: 1.5; text-align: center;">
                Spots can fill quickly — we recommend booking soon.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
