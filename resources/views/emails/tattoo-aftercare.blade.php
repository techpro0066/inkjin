<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Caring for your new tattoo — Bookpay</title>
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
            <td style="background-color:#ffffff;border-radius:14px;border:1px solid #EEE9F0;overflow:hidden;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="padding:28px 26px 18px 26px;font-family:'Space Grotesk',system-ui,sans-serif;font-size:26px;font-weight:700;letter-spacing:-0.055em;color:#1A1A1A;">
                    bookpay
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 26px 10px 26px;font-size:15px;color:#1A1A1A;line-height:1.55;">
                    Hi {{ $clientFirstName }},
                  </td>
                </tr>
                <tr>
                  <td style="padding:0 26px 18px 26px;font-size:15px;color:#3d3942;line-height:1.55;">
                    {{ $introText }}
                  </td>
                </tr>

                <tr>
                  <td style="background:#1A1A1A;padding:18px 26px;">
                    <div style="font-size:17px;font-weight:700;color:#ffffff;line-height:1.35;">{{ $bannerTitle }}</div>
                    <div style="font-size:12.5px;color:#ffffff;opacity:0.75;margin-top:4px;">{{ $bannerMeta }}</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:8px 26px 28px 26px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                      @foreach($sections as $section)
                        @php $items = $section['items'] ?? []; @endphp
                        @if(is_array($items) && count($items) > 0)
                          <tr>
                            <td style="font-size:15px;font-weight:800;color:#1A1A1A;padding:20px 0 0 0;">
                              {{ $section['title'] ?? '' }}
                            </td>
                          </tr>
                          <tr>
                            <td style="padding:4px 0 0 0;">
                              <ul style="margin:8px 0 0 0;padding-left:20px;font-size:14px;color:#3d3942;line-height:1.55;">
                                @foreach($items as $item)
                                  <li style="margin:3px 0;">{{ $item }}</li>
                                @endforeach
                              </ul>
                            </td>
                          </tr>
                        @endif
                      @endforeach

                      @foreach($otherBlocks as $block)
                        <tr>
                          <td style="font-size:15px;font-weight:800;color:#1A1A1A;padding:20px 0 0 0;">
                            {{ $block['title'] }}
                          </td>
                        </tr>
                        <tr>
                          <td style="font-size:14px;color:#3d3942;line-height:1.55;padding:8px 0 0 0;">
                            {!! nl2br(e($block['body'])) !!}
                          </td>
                        </tr>
                      @endforeach

                      @if(!empty($helpBlock))
                        <tr>
                          <td style="padding:16px 0 0 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#FFF3DC;border-radius:10px;">
                              <tr>
                                <td style="padding:12px 14px;font-size:13px;color:#3d3942;line-height:1.5;">
                                  <strong>{{ rtrim((string) ($helpBlock['title'] ?? 'When to get help'), '.') }}.</strong>
                                  {{ $helpBlock['body'] }}
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      @endif

                      <tr>
                        <td style="padding:24px 0 28px 0;">
                          @if(!empty($messageUrl))
                            <a href="{{ $messageUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:13px 22px;border-radius:12px;">
                              Message {{ $artistFirstName }}
                            </a>
                          @endif
                        </td>
                      </tr>

                      <tr>
                        <td style="font-size:12px;color:#6F6874;line-height:1.5;padding-top:16px;border-top:1px solid #EEE9F0;">
                          You got this email because you booked with {{ $artistName }} through Bookpay. Replies to this email aren’t read: message {{ $artistFirstName }} in Bookpay instead.<br>
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
      </td>
    </tr>
  </table>
</body>
</html>
