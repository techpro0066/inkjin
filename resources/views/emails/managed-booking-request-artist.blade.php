<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>New booking request — Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  @php
    $clientName = $bookingRequest->clientDisplayName();
    $designTitle = (string) ($bookingRequest->tattoo?->title ?? 'Design');
    $reference = $bookingRequest->referenceLabel();
    $submittedAt = $bookingRequest->created_at?->format('M j, Y \a\t g:i A') ?? now()->format('M j, Y \a\t g:i A');
  @endphp

  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $clientName }} submitted a new booking request for {{ $designTitle }}. Review it in your Inkjin dashboard.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fdf7ff;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;">
          <tr>
            <td align="center" style="padding:0 0 32px 0;font-size:28px;font-weight:800;color:#310f7a;letter-spacing:-0.5px;">
              inkjin
            </td>
          </tr>

          <tr>
            <td style="background-color:#ffffff;border-radius:16px;padding:48px 40px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td align="center" style="padding:0 0 20px 0;font-size:40px;">📬</td>
                </tr>
                <tr>
                  <td align="center" style="font-size:24px;font-weight:700;color:#1c1b21;line-height:1.3;padding:0 0 12px 0;">
                    New booking request
                  </td>
                </tr>
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 28px 0;text-align:center;">
                    <strong>{{ $clientName }}</strong> submitted a request for <strong>{{ $designTitle }}</strong>.
                    Review their availability and respond with time slots when you're ready.
                  </td>
                </tr>

                @if(!empty($requestsUrl))
                <tr>
                  <td align="center" style="padding:0 0 12px 0;">
                    <a href="{{ $requestsUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:16px 40px;border-radius:12px;line-height:1;">
                      View request
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 0 28px 0;font-size:12px;color:#7a7583;line-height:1.5;text-align:center;">
                    Sign in to your artist dashboard to decline, offer times, or message the client.
                  </td>
                </tr>
                @endif

                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F8F1FB;border-radius:12px;">
                      <tr>
                        <td style="padding:24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr><td style="font-size:14px;font-weight:700;color:#310f7a;text-transform:uppercase;letter-spacing:.5px;padding:0 0 16px 0;">Request details</td></tr>
                            <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1c1b21;"><strong>Client:</strong> {{ $clientName }}</td></tr>
                            @if($bookingRequest->user?->email)
                            <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1c1b21;"><strong>Email:</strong> {{ $bookingRequest->user->email }}</td></tr>
                            @endif
                            <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1c1b21;"><strong>Design:</strong> {{ $designTitle }}</td></tr>
                            <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1c1b21;"><strong>Reference:</strong> {{ $reference }}</td></tr>
                            <tr><td style="font-size:15px;color:#1c1b21;"><strong>Submitted:</strong> {{ $submittedAt }}</td></tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="font-size:14px;color:#494552;line-height:1.6;text-align:center;">
                    Clients are waiting for your response. Offering session times helps them complete their booking faster.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#494552;line-height:1.5;">
              © {{ date('Y') }} Inkjin · All rights reserved
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
