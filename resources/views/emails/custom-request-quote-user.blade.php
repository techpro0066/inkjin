<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your custom tattoo quote — Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  @php
    $artistName = $customRequest->artistDisplayName();
    $reference = $customRequest->referenceLabel();
    $clientName = $customRequest->clientDisplayName();
    $message = trim((string) ($customRequest->message_for_client ?? ''));
  @endphp

  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $artistName }} sent you a quote for your custom tattoo request ({{ $reference }}).
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
                <tr><td align="center" style="padding:0 0 16px 0;font-size:36px;">💬</td></tr>
                <tr><td align="center" style="font-size:22px;font-weight:700;color:#1c1b21;padding:0 0 16px 0;">You received a quote</td></tr>
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 24px 0;text-align:center;">
                    @if(trim($clientName) !== '' && $clientName !== 'Client #'.$customRequest->user_id)
                      Hi {{ $clientName }},<br><br>
                    @endif
                    <strong>{{ $artistName }}</strong> reviewed your custom tattoo request and sent you a quote ({{ $reference }}).
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F8F1FB;border-radius:12px;">
                      <tr><td style="padding:24px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                          <tr><td style="font-size:14px;font-weight:700;color:#310f7a;text-transform:uppercase;letter-spacing:.5px;padding:0 0 16px 0;">Quote details</td></tr>
                          <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1c1b21;"><strong>Estimated price:</strong> {{ $customRequest->estimatedPriceLabel() }}</td></tr>
                          <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1c1b21;"><strong>Estimated duration:</strong> {{ $customRequest->estimated_time ?: '—' }}</td></tr>
                          <tr><td style="padding:0 0 10px 0;font-size:15px;color:#1c1b21;"><strong>Number of sessions:</strong> {{ $customRequest->number_of_sessions ?: '—' }}</td></tr>
                        </table>
                      </td></tr>
                    </table>
                  </td>
                </tr>
                @php
                  $sessionSlots = $customRequest->isManagedRequest() ? $customRequest->normalizedArtistSlots() : [];
                @endphp
                @if(count($sessionSlots) > 0)
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
                      <tr><td style="padding:24px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                          <tr><td style="font-size:14px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.5px;padding:0 0 16px 0;">Offered session times</td></tr>
                          @foreach($sessionSlots as $slot)
                            @php
                              try {
                                $slotDateLabel = \Carbon\Carbon::parse($slot['date'])->format('l, M j, Y');
                              } catch (\Throwable) {
                                $slotDateLabel = $slot['date'];
                              }
                            @endphp
                            <tr>
                              <td style="padding:0 0 12px 0;font-size:15px;color:#1c1b21;">
                                <strong>{{ $slotDateLabel }}</strong>
                                <ul style="margin:8px 0 0 0;padding:0 0 0 18px;color:#494552;font-size:14px;line-height:1.5;">
                                  @foreach($slot['ranges'] ?? [] as $range)
                                    <li>{{ $customRequest->formatTimeRangeLabel($range['from'] ?? '', $range['to'] ?? '') }}</li>
                                  @endforeach
                                </ul>
                              </td>
                            </tr>
                          @endforeach
                        </table>
                      </td></tr>
                    </table>
                  </td>
                </tr>
                @endif
                @if($message !== '')
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
                      <tr><td style="padding:20px;font-size:14px;color:#1c1b21;line-height:1.6;">
                        <strong style="color:#15803d;">Message from {{ $artistName }}:</strong><br><br>
                        {{ $message }}
                      </td></tr>
                    </table>
                  </td>
                </tr>
                @endif
                @if(!empty($accessUrl))
                <tr>
                  <td align="center" style="padding:0 0 8px 0;">
                    <a href="{{ $accessUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:linear-gradient(135deg,#310f7a 0%,#482d91 100%);color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">View quote in dashboard</a>
                  </td>
                </tr>
                <tr><td style="font-size:13px;color:#7a7583;text-align:center;">Sign in to review the full details and next steps.</td></tr>
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
