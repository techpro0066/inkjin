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
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  @php
    $clientName = $bookingRequest->clientArtistFacingName();
    $designTitle = (string) ($bookingRequest->tattoo?->title ?? 'Design');
    $reference = $bookingRequest->referenceLabel();
    $submittedAt = $bookingRequest->created_at?->format('M j, Y \a\t g:i A') ?? now()->format('M j, Y \a\t g:i A');
    $locationLabel = trim((string) $bookingRequest->clientStudioLabel());
  @endphp

  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $clientName }} submitted a new booking request for {{ $designTitle }}. Review it in your Inkjin dashboard.
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
                <tr>
                  <td align="center" style="padding:0 0 20px 0;font-size:36px;">📬</td>
                </tr>
                <tr>
                  <td align="center" style="font-size:22px;font-weight:700;color:#1A1A1A;line-height:1.3;padding:0 0 12px 0;">
                    New booking request
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 28px 0;text-align:center;">
                    <strong>{{ $clientName }}</strong> submitted a request for <strong>{{ $designTitle }}</strong>.
                    Review their availability and respond with time slots when you're ready.
                  </td>
                </tr>

                @if(!empty($requestsUrl))
                <tr>
                  <td align="center" style="padding:0 0 12px 0;">
                    <a href="{{ $requestsUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:13px 22px;border-radius:12px;line-height:1;">
                      View request
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 0 28px 0;font-size:12px;color:#6F6874;line-height:1.5;text-align:center;">
                    Sign in to your artist dashboard to decline, offer times, or message the client.
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
                            <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1A1A1A;"><strong>Client:</strong> {{ $clientName }}</td></tr>
                            <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1A1A1A;"><strong>Design:</strong> {{ $designTitle }}</td></tr>
                            @if($locationLabel !== '')
                              <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1A1A1A;"><strong>Location:</strong> {{ $locationLabel }}</td></tr>
                            @endif
                            <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1A1A1A;"><strong>Reference:</strong> {{ $reference }}</td></tr>
                            <tr><td style="font-size:15px;color:#1A1A1A;"><strong>Submitted:</strong> {{ $submittedAt }}</td></tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="font-size:14px;color:#3d3942;line-height:1.6;text-align:center;">
                    Clients are waiting for your response. Offering session times helps them complete their booking faster.
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
