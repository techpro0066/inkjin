<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Decline confirmation — Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  @php
    $clientName = $bookingRequest->clientDisplayName();
    $designTitle = (string) ($bookingRequest->tattoo?->title ?? 'Design');
    $reference = $bookingRequest->referenceLabel();
    $reason = trim((string) ($bookingRequest->reason_decline ?? ''));
  @endphp

  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    You declined {{ $clientName }}'s request ({{ $reference }}). This is your confirmation copy.
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
                <tr><td align="center" style="padding:0 0 16px 0;font-size:36px;">✓</td></tr>
                <tr><td align="center" style="font-size:22px;font-weight:700;color:#1c1b21;padding:0 0 16px 0;">Decline recorded</td></tr>
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 20px 0;text-align:center;">
                    This email confirms you declined the booking request from <strong>{{ $clientName }}</strong> for <strong>{{ $designTitle }}</strong> ({{ $reference }}).
                  </td>
                </tr>
                @if($reason !== '')
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F8F1FB;border-radius:12px;">
                      <tr><td style="padding:20px;font-size:14px;color:#1c1b21;line-height:1.6;">
                        <strong style="color:#310f7a;">Reason sent to the client:</strong><br><br>
                        {{ $reason }}
                      </td></tr>
                    </table>
                  </td>
                </tr>
                @endif
                @if(!empty($requestsUrl))
                <tr>
                  <td align="center">
                    <a href="{{ $requestsUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">Open Requests</a>
                  </td>
                </tr>
                @endif
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
