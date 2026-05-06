<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>New booking from {{ $customerName }} - Inkjin</title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#fdf7ff;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    You have a new booking from {{ $customerName }}. Review details and prepare for the appointment.
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
                  <td align="center" style="padding:0 0 24px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="background-color:#F8F1FB;border-radius:12px;width:48px;height:48px;text-align:center;font-size:18px;font-weight:800;color:#310f7a;line-height:48px;">
                          ij
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <tr>
                  <td align="center" style="font-size:24px;font-weight:700;color:#1c1b21;line-height:1.3;padding:0 0 12px 0;">
                    You have a new booking!
                  </td>
                </tr>
                <tr>
                  <td style="font-size:16px;color:#494552;line-height:1.6;padding:0 0 28px 0;text-align:center;">
                    Hi {{ $artistName }}, a client has booked a session with you. Here are the details:
                  </td>
                </tr>

                <tr>
                  <td style="padding:0 0 22px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F8F1FB;border-radius:12px;">
                      <tr>
                        <td style="padding:24px;">
                          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr><td style="font-size:12px;color:#494552;text-transform:uppercase;letter-spacing:.5px;padding:0 0 4px 0;">Client</td></tr>
                            <tr><td style="font-size:16px;font-weight:600;color:#1c1b21;padding:0 0 10px 0;">{{ $customerName }}</td></tr>
                            <tr><td style="font-size:12px;color:#494552;text-transform:uppercase;letter-spacing:.5px;padding:0 0 4px 0;">Tattoo</td></tr>
                            <tr><td style="font-size:16px;font-weight:600;color:#1c1b21;padding:0 0 10px 0;">{{ $tattooTitle }}</td></tr>
                            <tr><td style="font-size:12px;color:#494552;text-transform:uppercase;letter-spacing:.5px;padding:0 0 4px 0;">Date &amp; Time</td></tr>
                            <tr><td style="font-size:16px;font-weight:600;color:#1c1b21;padding:0 0 10px 0;">{{ $bookingDate }} at {{ $bookingTime }}</td></tr>
                            <tr><td style="font-size:12px;color:#494552;text-transform:uppercase;letter-spacing:.5px;padding:0 0 4px 0;">Estimated Duration</td></tr>
                            <tr><td style="font-size:16px;font-weight:600;color:#1c1b21;padding:0 0 10px 0;">{{ $duration }} hour(s)</td></tr>
                            <tr><td style="font-size:12px;color:#494552;text-transform:uppercase;letter-spacing:.5px;padding:0 0 4px 0;">Amount Received</td></tr>
                            <tr><td style="font-size:18px;font-weight:700;color:#310f7a;padding:0 0 10px 0;">{{ $currencySymbol }}{{ number_format($amountReceived, 2) }}</td></tr>
                            <tr><td style="font-size:12px;color:#494552;text-transform:uppercase;letter-spacing:.5px;padding:0 0 4px 0;">Booking ID</td></tr>
                            <tr><td style="font-size:16px;font-weight:600;color:#1c1b21;">#{{ $bookingId }}</td></tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                @if(!empty($questionsAnswers))
                <tr>
                  <td style="padding:0 0 22px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fff8e1;border-radius:12px;">
                      <tr>
                        <td style="padding:20px 24px;">
                          <p style="margin:0 0 12px 0;font-size:14px;font-weight:700;color:#7a5c00;text-transform:uppercase;letter-spacing:.5px;">Client Answers</p>
                          @foreach($questionsAnswers as $questionId => $answer)
                            @php
                              $answerPayload = is_array($answer) ? $answer : ['answer' => $answer];
                              $questionText = (string) ($answerPayload['question'] ?? ($questions[$questionId] ?? ('Question #' . $questionId)));
                              $answerType = (string) ($answerPayload['type'] ?? '');
                              $answerValue = $answerPayload['answer'] ?? '';
                              $isImageAnswer = $answerType === 'image'
                                  || (is_string($answerValue) && (str_starts_with($answerValue, 'http://') || str_starts_with($answerValue, 'https://') || str_starts_with($answerValue, '/uploads/')));
                            @endphp
                            <p style="margin:0 0 4px 0;font-size:13px;font-weight:700;color:#6a5000;">Q: {{ $questionText }}</p>
                            @if($isImageAnswer)
                              <p style="margin:0 0 12px 0;font-size:14px;color:#4d4d4d;">
                                A:
                                <a href="{{ str_starts_with((string) $answerValue, 'http') ? $answerValue : url((string) $answerValue) }}" target="_blank" style="color:#310f7a;text-decoration:underline;">View uploaded image</a>
                              </p>
                            @else
                              <p style="margin:0 0 12px 0;font-size:14px;color:#4d4d4d;">A: {{ is_array($answerValue) ? implode(', ', $answerValue) : $answerValue }}</p>
                            @endif
                          @endforeach
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                @endif

                @if(!empty($meetLink))
                <tr>
                  <td style="padding:0 0 22px 0;">
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
                  <td style="font-size:13px;color:#494552;line-height:1.5;text-align:center;">
                    Please review this booking in your dashboard and be ready for the scheduled time.
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

