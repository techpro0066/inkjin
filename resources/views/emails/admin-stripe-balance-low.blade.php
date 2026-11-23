<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Action required: Stripe balance too low for payout</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  @php
    $currencyCode = strtoupper((string) ($currency ?? 'EUR'));
    $currencyLabel = match ($currencyCode) {
      'EUR' => '€',
      'GBP' => '£',
      'USD' => '$',
      default => $currencyCode.' ',
    };
    $triggerLabel = match ($source ?? '') {
      'manual_request' => 'Manual payout request',
      'automatic_studio_payout' => 'Automatic studio payout',
      default => 'Automatic daily payout',
    };
  @endphp
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    The platform Stripe balance is too low for this payout.
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
                  <td style="font-size:15px;color:#1A1A1A;line-height:1.55;padding:0 0 10px 0;">
                    Hi admin,
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 18px 0;">
                    A payout could not be sent because the platform Stripe available balance is lower than the requested transfer amount.
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 0 8px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                      <tr>
                        <td style="padding:4px 14px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;color:#6F6874;">Trigger</td>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $triggerLabel }}</td>
                            </tr>
                            <tr>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;color:#6F6874;">Requested</td>
                              <td style="padding:9px 0;border-bottom:1px solid #F3EEF4;font-size:14px;font-weight:800;color:#1A1A1A;text-align:right;">{{ $currencyLabel }}{{ number_format($requestedAmount, 2) }}</td>
                            </tr>
                            <tr>
                              <td style="padding:9px 0;@if(!empty($artistName) || !empty($bookingReference))border-bottom:1px solid #F3EEF4;@endif font-size:14px;color:#6F6874;">Stripe available</td>
                              <td style="padding:9px 0;@if(!empty($artistName) || !empty($bookingReference))border-bottom:1px solid #F3EEF4;@endif font-size:14px;font-weight:800;color:#1A1A1A;text-align:right;">{{ $currencyLabel }}{{ number_format($availableAmount, 2) }}</td>
                            </tr>
                            @if(!empty($artistName))
                              <tr>
                                <td style="padding:9px 0;@if(!empty($bookingReference))border-bottom:1px solid #F3EEF4;@endif font-size:14px;color:#6F6874;">Artist</td>
                                <td style="padding:9px 0;@if(!empty($bookingReference))border-bottom:1px solid #F3EEF4;@endif font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $artistName }}</td>
                              </tr>
                            @endif
                            @if(!empty($bookingReference))
                              <tr>
                                <td style="padding:9px 0;font-size:14px;color:#6F6874;">Booking</td>
                                <td style="padding:9px 0;font-size:14px;font-weight:600;color:#1A1A1A;text-align:right;">{{ $bookingReference }}@if(!empty($bookingId)) (#{{ $bookingId }})@endif</td>
                              </tr>
                            @endif
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td style="font-size:12.5px;color:#6F6874;line-height:1.5;padding:12px 0 8px 0;">
                    Top up the platform Stripe balance or review recent payouts, then retry the payout if needed.
                  </td>
                </tr>

                @if(!empty($dashboardUrl))
                  <tr>
                    <td style="padding:16px 0 8px 0;">
                      <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:13px 22px;border-radius:12px;">
                        Open admin dashboard
                      </a>
                    </td>
                  </tr>
                @endif

                <tr>
                  <td style="font-size:12px;color:#6F6874;line-height:1.5;padding-top:16px;border-top:1px solid #EEE9F0;">
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
