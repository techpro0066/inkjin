<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking rescheduled</title>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">Your booking has been rescheduled with updated date and time details.</div>
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
                <tr><td align="center" style="font-size:36px;padding:0 0 16px 0;">📅</td></tr>
                <tr><td align="center" style="font-size:22px;font-weight:700;color:#1A1A1A;padding:0 0 16px 0;">Booking rescheduled</td></tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 20px 0;text-align:center;">
                    Hello {{ $isArtist ? $booking->artist->name : $booking->user->name }},<br><br>
                    @if($isArtist)
                      The booking has been rescheduled. The client selected a new date and time.
                    @else
                      Your booking has been successfully rescheduled.
                    @endif
                  </td>
                </tr>
                @php
                  $actionHistory = $booking->action_history ?? [];
                  $lastReschedule = collect($actionHistory)->where('action', 'reschedule_completed')->last();
                @endphp
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                      <tr><td style="padding:16px;font-size:14px;color:#1A1A1A;line-height:1.7;">
                        <strong>Updated booking details</strong><br><br>
                        @if($lastReschedule)
                        <span style="color:#6F6874;">Previous date and time:</span><br>
                        {{ \Carbon\Carbon::parse($lastReschedule['old_date'])->format('F d, Y') }} · {{ $lastReschedule['old_time'] ?? 'N/A' }}<br><br>
                        @endif
                        <strong>New date and time:</strong><br>
                        {{ $booking->booking_date->format('F d, Y') }} · {{ $booking->booking_time['start'] ?? 'N/A' }} - {{ $booking->booking_time['end'] ?? 'N/A' }}<br><br>
                        <strong>Tattoo:</strong> {{ $booking->tattoo->title ?? 'Custom Tattoo' }}<br>
                        <strong>Booking ID:</strong> #{{ $booking->id }}
                      </td></tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;">
                    Please make note of the new date and time. If you have any questions, contact {{ $isArtist ? $booking->user->name : $booking->artist->name }} directly.
                  </td>
                </tr>
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
