<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Your booking has been rescheduled — Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin: 0; padding: 0; background-color: #FFF6FF; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
  @php
    $actionHistory = collect($booking->action_history ?? []);
    $lastReschedule = $actionHistory->where('action', 'reschedule_completed')->last() ?? [];
    $oldDateRaw = $lastReschedule['old_date'] ?? null;
    $oldTimeRaw = $lastReschedule['old_time'] ?? null;
    $newDateRaw = $booking->booking_date ?? null;
    $newTimeRaw = $booking->start_time_utc ?? null;
    $artistName = $booking->artist?->userDetail?->publicDisplayName()
        ?: trim((string) (($booking->artist->first_name ?? '').' '.($booking->artist->last_name ?? '')));
    if ($artistName === '') {
      $artistName = $booking->artist->artist_handle ?? 'your artist';
    }
    $studioName = $booking->studioNameForClient() ?: ($booking->artist->userDetail->studio_name ?? 'Inkjin Studio');
    $studioAddress = $booking->artist->userDetail->studio_address ?? '';
  @endphp

  <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
    Your booking has been rescheduled. Review the new date and time.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #FFF6FF;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="520" style="max-width: 520px; width: 100%;">
          <tr>
            <td align="center" style="padding: 0 0 32px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="font-size: 26px; font-weight: 700; color: #1A1A1A; letter-spacing: -0.055em; font-family: 'Space Grotesk', system-ui, sans-serif;">
                    bookpay
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="background-color: #ffffff; border-radius: 14px; padding: 28px 26px; border: 1px solid #EEE9F0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td align="center" style="padding: 0 0 8px 0; font-size: 36px; font-weight: 700; color: #EEE9F0; letter-spacing: -2px; line-height: 1;">
                    ij
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding: 0 0 20px 0; font-size: 36px;">
                    🔄
                  </td>
                </tr>
                <tr>
                  <td align="center" style="font-size: 22px; font-weight: 700; color: #1A1A1A; line-height: 1.3; padding: 0 0 12px 0;">
                    Appointment Rescheduled
                  </td>
                </tr>
                <tr>
                  <td style="font-size: 15px; color: #3d3942; line-height: 1.6; padding: 0 0 28px 0; text-align: center;">
                    @if($isArtistRequested)
                      {{ $artistName }} requested a reschedule and your updated appointment is now confirmed.
                    @else
                      Your new appointment time has been confirmed successfully.
                    @endif
                  </td>
                </tr>

                <tr>
                  <td style="padding: 0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #FFF6FF; border-radius: 12px; border: 1px solid #EEE9F0;">
                      <tr>
                        <td style="padding: 24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td style="padding: 0 0 16px 0;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                  <tr>
                                    <td style="font-size: 12px; font-weight: 700; color: #3d3942; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 6px 0;">
                                      Previous
                                    </td>
                                  </tr>
                                  <tr>
                                    <td style="font-size: 15px; color: #3d3942; line-height: 1.5; text-decoration: line-through;">
                                      {{ $oldDateRaw ? \Carbon\Carbon::parse($oldDateRaw)->format('F j, Y') : 'N/A' }} at {{ $oldTimeRaw ? \Carbon\Carbon::createFromFormat('H:i:s', $oldTimeRaw)->format('g:i A') : 'N/A' }}
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td align="center" style="padding: 0 0 16px 0; font-size: 15px; color: #1A1A1A;">↓</td>
                            </tr>
                            <tr>
                              <td>
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                  <tr>
                                    <td style="font-size: 12px; font-weight: 700; color: #1A1A1A; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 6px 0;">
                                      New Time
                                    </td>
                                  </tr>
                                  <tr>
                                    <td style="font-size: 15px; color: #1A1A1A; font-weight: 700; line-height: 1.55; background-color: #ffffff; border-radius: 8px; padding: 10px 14px;">
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

                @if($isArtistRequested && !empty($booking->reschedule_reason))
                <tr>
                  <td style="padding: 0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #FFF6FF; border-radius: 12px; border: 1px solid #EEE9F0;">
                      <tr>
                        <td style="padding: 16px 24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                              <td style="font-size: 13px; font-weight: 700; color: #3d3942; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 0 6px 0;">
                                Artist's Note
                              </td>
                            </tr>
                            <tr>
                              <td style="font-size: 15px; color: #3d3942; line-height: 1.6; font-style: italic;">
                                "{{ $booking->reschedule_reason }}"
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
                  <td style="padding: 0 0 32px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                      <tr>
                        <td style="font-size: 13px; color: #3d3942; line-height: 1.4; padding: 0 0 4px 0;">
                          Location
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size: 15px; color: #1A1A1A; font-weight: 600; line-height: 1.5;">
                          {{ $studioName }}
                          @if($studioAddress !== '')
                          <br><span style="font-size: 13px; font-weight: 400; color: #3d3942;">{{ $studioAddress }}</span>
                          @endif
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td align="center" style="padding: 0 0 16px 0;">
                    <a href="{{ route('user.bookings.index') }}" target="_blank" style="display: inline-block; background: #1A1A1A; color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; padding: 13px 22px; border-radius: 12px; line-height: 1;">
                      View Updated Booking
                    </a>
                  </td>
                </tr>

                <tr>
                  <td align="center" style="font-size: 14px; color: #3d3942; line-height: 1.5; padding: 0;">
                    Need another change? You can manage your booking from your dashboard.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding: 32px 0 0 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="font-size: 12px; color: #3d3942; line-height: 1.5; text-align: center;">
                    © {{ date('Y') }} Bookpay by Inkjin
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
