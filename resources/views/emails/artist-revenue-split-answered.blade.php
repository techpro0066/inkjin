<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>
    @if($accepted)
      {{ $studioName }} accepted your new split — Bookpay
    @else
      {{ $studioName }} declined your new split — Bookpay
    @endif
  </title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $introText }}
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
                  <td style="padding:0 0 20px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                      <tr>
                        <td style="padding:12px;width:44px;vertical-align:middle;">
                          @if(!empty($studioAvatarUrl))
                            <img src="{{ $studioAvatarUrl }}" alt="{{ $studioName }}" width="44" height="44" style="display:block;width:44px;height:44px;border-radius:50%;object-fit:cover;">
                          @else
                            <div style="width:44px;height:44px;border-radius:50%;background:#F3E8FF;color:#3E007C;font-weight:800;font-size:14px;line-height:44px;text-align:center;">
                              {{ $studioInitials }}
                            </div>
                          @endif
                        </td>
                        <td style="padding:12px 12px 12px 0;vertical-align:middle;">
                          <div style="font-size:15px;font-weight:700;color:#1A1A1A;">{{ $studioName }}</div>
                          @if(!empty($studioMeta))
                            <div style="font-size:13px;color:#6F6874;margin-top:2px;">{{ $studioMeta }}</div>
                          @endif
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="font-size:15px;color:#1A1A1A;line-height:1.55;padding:0 0 10px 0;">
                    Hi {{ $artistFirstName }},
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 18px 0;">
                    {{ $introText }}
                  </td>
                </tr>

                @if($accepted)
                  <tr>
                    <td style="padding:0 0 8px 0;">
                      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                        <tr>
                          <td style="padding:4px 14px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                              <tr>
                                <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;color:#6F6874;">Before</td>
                                <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $beforeLabel }}</td>
                              </tr>
                              <tr>
                                <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:800;color:#1A1A1A;">Now</td>
                                <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:800;color:#1A1A1A;text-align:right;">{{ $nowLabel }}</td>
                              </tr>
                              <tr>
                                <td style="padding:9px 0;font-size:14px;color:#6F6874;">Applies to</td>
                                <td style="padding:9px 0;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $appliesLabel }}</td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>

                  <tr>
                    <td style="font-size:12.5px;color:#6F6874;line-height:1.5;padding:8px 0 0 0;">
                      Bookings made before keep the old split.
                    </td>
                  </tr>
                @endif

                <tr>
                  <td style="padding:16px 0 8px 0;">
                    @if(!empty($actionUrl))
                      <a href="{{ $actionUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:13px 22px;border-radius:12px;">
                        {{ $ctaLabel }}
                      </a>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td style="font-size:12px;color:#6F6874;line-height:1.5;padding-top:28px;border-top:1px solid #EEE9F0;">
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
