<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Custom request submitted — Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Your custom tattoo request with {{ $artistName }} was submitted. Track it from your Inkjin dashboard.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF6FF;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="520" style="max-width:520px;width:100%;">
          <tr>
            <td align="left" style="padding:0 0 22px 0;font-family:'Space Grotesk',system-ui,sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.055em;color:#1A1A1A;">
              bookpay
            </td>
          </tr>

          <tr>
            <td style="background-color:#ffffff;border-radius:14px;padding:28px 26px;border:1px solid #EEE9F0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                @if($isNewUser)
                <tr>
                  <td align="center" style="padding:0 0 20px 0;font-size:36px;">✨</td>
                </tr>
                <tr>
                  <td align="center" style="font-size:22px;font-weight:700;color:#1A1A1A;line-height:1.3;padding:0 0 12px 0;">
                    Welcome to Inkjin
                  </td>
                </tr>
                @else
                <tr>
                  <td align="center" style="padding:0 0 20px 0;font-size:36px;">🎨</td>
                </tr>
                <tr>
                  <td align="center" style="font-size:22px;font-weight:700;color:#1A1A1A;line-height:1.3;padding:0 0 12px 0;">
                    Custom request submitted
                  </td>
                </tr>
                @endif

                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 28px 0;text-align:center;">
                    @if(trim($recipientName ?? '') !== '')
                      Hi {{ $recipientName }},<br><br>
                    @endif
                    @if($isNewUser)
                      Thanks for submitting your first custom tattoo request with us. {{ $artistName }} will review your ideas and get back to you soon.
                    @else
                      Your custom tattoo request has been sent to {{ $artistName }}. The artist will review your details and follow up with next steps.
                    @endif
                  </td>
                </tr>

                @if(!empty($accessUrl))
                <tr>
                  <td align="center" style="padding:0 0 12px 0;">
                    <a href="{{ $accessUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:13px 22px;border-radius:12px;line-height:1;">
                      Open your dashboard
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 0 28px 0;font-size:12px;color:#6F6874;line-height:1.5;text-align:center;">
                    This link signs you in to Inkjin. It expires in 14 days for your security.
                  </td>
                </tr>
                @endif

                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                      <tr>
                        <td style="padding:24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr><td style="font-size:14px;font-weight:700;color:#1A1A1A;text-transform:uppercase;letter-spacing:.5px;padding:0 0 16px 0;">Request details</td></tr>
                            <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1A1A1A;"><strong>Artist:</strong> {{ $artistName }}</td></tr>
                            <tr><td style="font-size:15px;color:#1A1A1A;"><strong>Reference:</strong> {{ $requestReference }}</td></tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 0 6px 0;font-size:14px;color:#3d3942;line-height:1.6;text-align:center;">
                    You will receive another email when the artist responds with a quote or questions.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td align="left" style="padding:16px 0 0 0;border-top:1px solid #EEE9F0;font-size:12px;color:#6F6874;line-height:1.5;">
              © {{ date('Y') }} Bookpay by Inkjin
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
