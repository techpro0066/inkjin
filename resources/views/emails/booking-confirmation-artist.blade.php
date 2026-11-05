<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>New booking: {{ $clientDisplayName }} — Bookpay</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $clientFirstName }} booked with you and paid the deposit.
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
                          <div style="width:44px;height:44px;border-radius:50%;background:#F3E8FF;color:#3E007C;font-weight:800;font-size:14px;line-height:44px;text-align:center;">
                            {{ $clientInitials }}
                          </div>
                        </td>
                        <td style="padding:12px 12px 12px 0;vertical-align:middle;">
                          <div style="font-size:15px;font-weight:700;color:#1A1A1A;">{{ $clientDisplayName }}</div>
                          <div style="font-size:13px;color:#6F6874;margin-top:2px;">New booking</div>
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
                    {{ $clientFirstName }} booked with you and paid the deposit.
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 0 8px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                      <tr>
                        <td style="padding:4px 14px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;color:#6F6874;">Booking</td>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $bookingReference }}</td>
                            </tr>
                            <tr>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;color:#6F6874;">Design</td>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $tattooTitle }}</td>
                            </tr>
                            <tr>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;color:#6F6874;">When</td>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $whenLabel }}</td>
                            </tr>
                            @if(!empty($whereLabel))
                              <tr>
                                <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;color:#6F6874;">Where</td>
                                <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $whereLabel }}</td>
                              </tr>
                            @endif
                            <tr>
                              <td style="padding:9px 0;@if(($balanceAmount ?? 0) > 0.009) border-bottom:1px solid #F3EEF4;@endif font-size:14px;color:#6F6874;">Paid</td>
                              <td style="padding:9px 0;@if(($balanceAmount ?? 0) > 0.009) border-bottom:1px solid #F3EEF4;@endif font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $paidLabel }}</td>
                            </tr>
                            @if(($balanceAmount ?? 0) > 0.009)
                              <tr>
                                <td style="padding:9px 0;font-size:14px;color:#6F6874;">Balance</td>
                                <td style="padding:9px 0;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $balanceLabel }}</td>
                              </tr>
                            @endif
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="padding:16px 0 8px 0;">
                    @if(!empty($viewBookingUrl))
                      <a href="{{ $viewBookingUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:13px 22px;border-radius:12px;">
                        View booking
                      </a>
                    @endif
                  </td>
                </tr>

                <tr>
                  <td style="font-size:12px;color:#6F6874;line-height:1.5;padding-top:28px;border-top:1px solid #EEE9F0;">
                    You can change which emails you get in Account &gt; Notifications.<br>
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
