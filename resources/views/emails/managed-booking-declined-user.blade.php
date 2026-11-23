<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking request update — Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  @php
    $artistName = $bookingRequest->artistDisplayName();
    $designTitle = (string) ($bookingRequest->tattoo?->title ?? 'Design');
    $reference = $bookingRequest->referenceLabel();
    $reason = trim((string) ($bookingRequest->reason_decline ?? ''));
    $clientName = $bookingRequest->clientDisplayName();
  @endphp

  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $artistName }} was unable to move forward with your request for {{ $designTitle }}.
  </div>

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
                <tr><td align="center" style="padding:0 0 16px 0;font-size:36px;">📭</td></tr>
                <tr><td align="center" style="font-size:22px;font-weight:700;color:#1A1A1A;padding:0 0 16px 0;">Request not accepted</td></tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 24px 0;text-align:center;">
                    @if(trim($clientName) !== '' && $clientName !== 'Client #'.$bookingRequest->user_id)
                      Hi {{ $clientName }},<br><br>
                    @endif
                    <strong>{{ $artistName }}</strong> is unable to proceed with your booking request for <strong>{{ $designTitle }}</strong> ({{ $reference }}).
                  </td>
                </tr>
                @if($reason !== '')
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#FFF6FF;border-radius:12px;border:1px solid #EEE9F0;">
                      <tr><td style="padding:16px;font-size:14px;color:#1A1A1A;line-height:1.6;">
                        <strong style="color:#1A1A1A;">Message from the artist:</strong><br><br>
                        {{ $reason }}
                      </td></tr>
                    </table>
                  </td>
                </tr>
                @endif
                @if(!empty($requestsUrl))
                <tr>
                  <td align="center" style="padding:0 0 8px 0;">
                    <a href="{{ $requestsUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:13px 22px;border-radius:12px;">View My Requests</a>
                  </td>
                </tr>
                @endif
                <tr><td style="font-size:13px;color:#6F6874;text-align:center;padding-top:8px;">You can explore other artists and designs on Inkjin anytime.</td></tr>
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
