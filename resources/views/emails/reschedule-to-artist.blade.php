<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Client rescheduled their booking — Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin: 0; padding: 0; background-color: #fdf7ff; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
  @php
    $actionHistory = collect($booking->action_history ?? []);
    $lastReschedule = $actionHistory->where('action', 'reschedule_completed')->last() ?? [];
    $oldDateRaw = $lastReschedule['old_date'] ?? null;
    $oldTimeRaw = $lastReschedule['old_time'] ?? null;
    $newDateRaw = $booking->booking_date ?? null;
    $newTimeRaw = $booking->start_time_utc ?? null;
  @endphp

  <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
    {{ trim((string) (($booking->user->first_name ?? '').' '.($booking->user->last_name ?? ''))) ?: 'Your client' }} has rescheduled their appointment. Review the updated booking.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #fdf7ff;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; width: 100%;">
          <tr>
            <td align="center" style="padding: 0 0 32px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="font-size: 28px; font-weight: 800; color: #310f7a; letter-spacing: -0.5px;">
                    inkjin
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="background-color: #ffffff; border-radius: 16px; padding: 48px 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td align="center" style="padding: 0 0 8px 0; font-size: 48px; font-weight: 800; color: #F8F1FB; letter-spacing: -2px; line-height: 1;">
                    ij
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding: 0 0 20px 0; font-size: 40px;">
                    📅
                  </td>
                </tr>
                <tr>
                  <td align="center" style="font-size: 24px; font-weight: 700; color: #1c1b21; line-height: 1.3; padding: 0 0 12px 0;">
                    Booking Rescheduled
                  </td>
                </tr>
                <tr>
                  <td style="font-size: 16px; color: #494552; line-height: 1.6; padding: 0 0 28px 0; text-align: center;">
                    {{ trim((string) (($booking->user->first_name ?? '').' '.($booking->user->last_name ?? ''))) ?: 'Your client' }} has rescheduled their appointment. Here are the updated details.
                  </td>
                </tr>

                <tr>
                  <td style="padding: 0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #F8F1FB; border-radius: 12px;">
                      <tr>
                        <td style="padding: 24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td style="padding: 0 0 16px 0;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                  <tr>
                                    <td style="font-size: 12px; font-weight: 700; color: #494552; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 6px 0;">
                                      Previous
                                    </td>
                                  </tr>
                                  <tr>
                                    <td style="font-size: 16px; color: #494552; line-height: 1.5; text-decoration: line-through;">
                                      {{ $oldDateRaw ? \Carbon\Carbon::parse($oldDateRaw)->format('F j, Y') : 'N/A' }} at {{ $oldTimeRaw ? \Carbon\Carbon::createFromFormat('H:i:s', $oldTimeRaw)->format('g:i A') : 'N/A' }}
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td align="center" style="padding: 0 0 16px 0; font-size: 20px; color: #310f7a;">
                                ↓
                              </td>
                            </tr>
                            <tr>
                              <td>
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                  <tr>
                                    <td style="font-size: 12px; font-weight: 700; color: #310f7a; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 6px 0;">
                                      New Time
                                    </td>
                                  </tr>
                                  <tr>
                                    <td style="font-size: 18px; color: #310f7a; font-weight: 700; line-height: 1.5; background-color: #ffffff; border-radius: 8px; padding: 10px 14px;">
                                      {{ $newDateRaw ? \Carbon\Carbon::parse($newDateRaw)->format('F j, Y') : 'N/A' }} at {{ $newTimeRaw ? \Carbon\Carbon::createFromFormat('H:i:s', $newTimeRaw)->format('g:i A') : 'N/A' }}
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

                <tr>
                  <td style="padding: 0 0 16px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                      <tr>
                        <td style="font-size: 13px; color: #494552; line-height: 1.4; width: 140px; vertical-align: top;">
                          Service / Style
                        </td>
                        <td style="font-size: 15px; color: #1c1b21; font-weight: 600; line-height: 1.4; vertical-align: top;">
                          {{ $booking->tattoo->title ?? 'Tattoo Session' }}
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                @if(!empty($rescheduleNote))
                <tr>
                  <td style="padding: 0 0 32px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #F8F1FB; border-radius: 12px;">
                      <tr>
                        <td style="padding: 16px 24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td style="font-size: 13px; font-weight: 700; color: #494552; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 6px 0;">
                                Client's Note
                              </td>
                            </tr>
                            <tr>
                              <td style="font-size: 15px; color: #494552; line-height: 1.6; font-style: italic;">
                                "{{ $rescheduleNote }}"
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                @endif

                <tr>
                  <td align="center" style="padding: 0 0 16px 0;">
                    <a href="{{ route('artist.bookings.index') }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #310f7a 0%, #482d91 100%); color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 16px 40px; border-radius: 12px; line-height: 1;">
                      View Updated Booking
                    </a>
                  </td>
                </tr>

                <tr>
                  <td align="center" style="font-size: 14px; color: #494552; line-height: 1.5; padding: 0;">
                    Your calendar has been automatically updated.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding: 32px 0 0 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="font-size: 12px; color: #494552; line-height: 1.5; text-align: center;">
                    &copy; {{ date('Y') }} Inkjin &middot; All rights reserved
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
