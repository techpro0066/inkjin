<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stripe balance too low for payout</title>
</head>
<body style="margin:0;padding:0;background:#fdf7ff;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fdf7ff;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background:#fff;border-radius:16px;padding:40px;">
          <tr>
            <td style="font-size:24px;font-weight:800;color:#310f7a;padding-bottom:24px;">inkjin admin</td>
          </tr>
          <tr>
            <td style="font-size:18px;font-weight:700;color:#1c1b21;padding-bottom:12px;">
              Stripe platform balance is too low
            </td>
          </tr>
          <tr>
            <td style="font-size:15px;color:#494552;line-height:1.7;padding-bottom:24px;">
              An artist payout could not be sent because the platform Stripe available balance is lower than the requested transfer amount.
            </td>
          </tr>
          <tr>
            <td style="background:#f8f1fb;border-radius:12px;padding:20px;font-size:14px;color:#1c1b21;line-height:1.8;">
              <strong>Trigger:</strong>
              {{ $source === 'manual_request' ? 'Manual payout request' : 'Automatic daily payout' }}<br>
              <strong>Requested:</strong> {{ $currency === 'EUR' ? '€' : $currency.' ' }}{{ number_format($requestedAmount, 2) }}<br>
              <strong>Stripe available:</strong> {{ $currency === 'EUR' ? '€' : $currency.' ' }}{{ number_format($availableAmount, 2) }}<br>
              @if ($artistName)
                <strong>Artist:</strong> {{ $artistName }}<br>
              @endif
              @if ($bookingReference)
                <strong>Booking:</strong> {{ $bookingReference }}@if($bookingId) (#{{ $bookingId }})@endif<br>
              @endif
            </td>
          </tr>
          <tr>
            <td style="font-size:14px;color:#494552;line-height:1.7;padding-top:24px;padding-bottom:28px;">
              Top up the platform Stripe balance or review recent payouts, then retry the artist payout if needed.
            </td>
          </tr>
          @if ($dashboardUrl)
            <tr>
              <td>
                <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#310f7a;color:#fff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 20px;border-radius:999px;">
                  Open admin dashboard
                </a>
              </td>
            </tr>
          @endif
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
