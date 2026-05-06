<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Your booking with {{ $artistName }} is confirmed! - Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Your tattoo appointment with {{ $artistName }} is confirmed. Please keep your completion code safe and share it with your artist after your session.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fdf7ff;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;">
          <tr>
            <td align="center" style="padding:0 0 32px 0;font-size:28px;font-weight:800;color:#310f7a;letter-spacing:-0.5px;">
              inkjin
            </td>
          </tr>

          <tr>
            <td style="background-color:#ffffff;border-radius:16px;padding:48px 40px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td align="center" style="padding:0 0 8px 0;font-size:48px;font-weight:800;color:#F8F1FB;letter-spacing:-2px;line-height:1;">
                    ij
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:0 0 20px 0;font-size:40px;">✅</td>
                </tr>
                <tr>
                  <td align="center" style="font-size:24px;font-weight:700;color:#1c1b21;line-height:1.3;padding:0 0 12px 0;">
                    Booking Confirmed!
                  </td>
                </tr>
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 28px 0;text-align:center;">
                    Hi {{ $userName }}, your tattoo appointment is confirmed. Here are your booking details.
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F8F1FB;border-radius:12px;">
                      <tr>
                        <td style="padding:24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr><td style="font-size:14px;font-weight:700;color:#310f7a;text-transform:uppercase;letter-spacing:.5px;padding:0 0 16px 0;">Booking Details</td></tr>
                            <tr><td style="padding:0 0 10px 0;"><strong>Artist:</strong> {{ $artistName }}</td></tr>
                            <tr><td style="padding:0 0 10px 0;"><strong>Tattoo:</strong> {{ $tattooTitle }}</td></tr>
                            <tr><td style="padding:0 0 10px 0;"><strong>Date:</strong> {{ $bookingDate }}</td></tr>
                            <tr><td style="padding:0 0 10px 0;"><strong>Time:</strong> {{ $bookingTime }}</td></tr>
                            <tr><td style="padding:0 0 10px 0;"><strong>Est. Duration:</strong> {{ $duration }} hour(s)</td></tr>
                            <tr><td style="padding:0 0 10px 0;"><strong>Amount Paid:</strong> {{ $currencySymbol }}{{ number_format($totalAmount, 2) }}</td></tr>
                            <tr><td><strong>Booking ID:</strong> #{{ $bookingId }}</td></tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                @if(!empty($completionCode))
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fff8e8;border:1px solid #f2dba8;border-radius:12px;">
                      <tr>
                        <td style="padding:20px 24px;text-align:center;">
                          <p style="margin:0 0 8px 0;font-size:13px;font-weight:700;color:#7a4a00;text-transform:uppercase;letter-spacing:.5px;">Completion Code</p>
                          <p style="margin:0;font-size:30px;letter-spacing:4px;font-weight:800;color:#1c1b21;">{{ $completionCode }}</p>
                          <p style="margin:10px 0 0 0;font-size:13px;line-height:1.5;color:#6b4f1d;">
                            Share this code with your artist only when your tattoo session is finished.
                          </p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                @endif

                @if(!empty($meetLink))
                <tr>
                  <td style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef8ff;border-radius:12px;">
                      <tr>
                        <td style="padding:20px 24px;">
                          <p style="margin:0 0 8px 0;font-size:16px;font-weight:700;color:#0f4c81;">Video Meeting</p>
                          <p style="margin:0 0 12px 0;font-size:14px;color:#1b3e5c;">Meeting time: {{ $meetingTime }}</p>
                          <a href="{{ $meetLink }}" target="_blank" style="display:inline-block;background-color:#0f6bbf;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:10px 16px;border-radius:8px;">Join Google Meet</a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                @endif

                <tr>
                  <td style="padding:0 0 6px 0;font-size:14px;color:#494552;line-height:1.6;text-align:center;">
                    Please arrive on time. For rescheduling, contact your artist in advance.
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#494552;line-height:1.5;">
              © {{ date('Y') }} Inkjin · All rights reserved
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

