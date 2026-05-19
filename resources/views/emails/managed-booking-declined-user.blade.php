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
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
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

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fdf7ff;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;">
          <tr>
            <td align="center" style="padding:0 0 32px 0;font-size:28px;font-weight:800;color:#310f7a;">inkjin</td>
          </tr>
          <tr>
            <td style="background-color:#ffffff;border-radius:16px;padding:48px 40px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr><td align="center" style="padding:0 0 16px 0;font-size:36px;">📭</td></tr>
                <tr><td align="center" style="font-size:22px;font-weight:700;color:#1c1b21;padding:0 0 16px 0;">Request not accepted</td></tr>
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 24px 0;text-align:center;">
                    @if(trim($clientName) !== '' && $clientName !== 'Client #'.$bookingRequest->user_id)
                      Hi {{ $clientName }},<br><br>
                    @endif
                    <strong>{{ $artistName }}</strong> is unable to proceed with your booking request for <strong>{{ $designTitle }}</strong> ({{ $reference }}).
                  </td>
                </tr>
                @if($reason !== '')
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fef2f2;border-radius:12px;border:1px solid #fecaca;">
                      <tr><td style="padding:20px;font-size:14px;color:#1c1b21;line-height:1.6;">
                        <strong style="color:#b91c1c;">Message from the artist:</strong><br><br>
                        {{ $reason }}
                      </td></tr>
                    </table>
                  </td>
                </tr>
                @endif
                @if(!empty($requestsUrl))
                <tr>
                  <td align="center" style="padding:0 0 8px 0;">
                    <a href="{{ $requestsUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">View My Requests</a>
                  </td>
                </tr>
                @endif
                <tr><td style="font-size:13px;color:#7a7583;text-align:center;padding-top:8px;">You can explore other artists and designs on Inkjin anytime.</td></tr>
              </table>
            </td>
          </tr>
          <tr><td align="center" style="padding:24px 0 0 0;font-size:12px;color:#494552;">© {{ date('Y') }} Inkjin</td></tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
