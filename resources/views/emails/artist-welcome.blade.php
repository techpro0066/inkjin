<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Welcome to Bookpay, {{ $artistFirstName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#FFF6FF;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Welcome to Bookpay, {{ $artistFirstName }}. Your account is ready.
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
                    Hi {{ $artistFirstName }},
                  </td>
                </tr>
                <tr>
                  <td style="font-size:15px;color:#3d3942;line-height:1.55;padding:0 0 18px 0;">
                    Your Bookpay account is ready. Do {{ $remainingLabel }} and clients can start booking you.
                  </td>
                </tr>

                @if(!empty($showPayoutStep) || !empty($showWorkingHoursStep) || !empty($showOpenBooksStep))
                  <tr>
                    <td style="padding:0 0 8px 0;">
                      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #EEE9F0;border-radius:12px;">
                        <tr>
                          <td style="padding:4px 14px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                              @php $step = 1; @endphp
                              @if(!empty($showPayoutStep))
                                <tr>
                                  <td style="width:36px;padding:12px 0;border-bottom:1px solid #F3EEF4;vertical-align:top;">
                                    <div style="width:24px;height:24px;border-radius:50%;background:#F3E8FF;color:#3E007C;font-weight:800;font-size:12.5px;line-height:24px;text-align:center;">{{ $step++ }}</div>
                                  </td>
                                  <td style="padding:12px 0;border-bottom:1px solid #F3EEF4;font-size:14px;color:#1A1A1A;">
                                    <strong>Finish payouts</strong><br>
                                    <span style="color:#6F6874;font-size:13px;">Connect Stripe so clients can pay deposits and you get paid.</span>
                                  </td>
                                </tr>
                              @endif
                              @if(!empty($showWorkingHoursStep))
                                <tr>
                                  <td style="width:36px;padding:12px 0;@if(!empty($showOpenBooksStep))border-bottom:1px solid #F3EEF4;@endif vertical-align:top;">
                                    <div style="width:24px;height:24px;border-radius:50%;background:#F3E8FF;color:#3E007C;font-weight:800;font-size:12.5px;line-height:24px;text-align:center;">{{ $step++ }}</div>
                                  </td>
                                  <td style="padding:12px 0;@if(!empty($showOpenBooksStep))border-bottom:1px solid #F3EEF4;@endif font-size:14px;color:#1A1A1A;">
                                    <strong>Set your working hours</strong><br>
                                    <span style="color:#6F6874;font-size:13px;">The days and times clients can book you.</span>
                                  </td>
                                </tr>
                              @endif
                              @if(!empty($showOpenBooksStep))
                                <tr>
                                  <td style="width:36px;padding:12px 0;vertical-align:top;">
                                    <div style="width:24px;height:24px;border-radius:50%;background:#F3E8FF;color:#3E007C;font-weight:800;font-size:12.5px;line-height:24px;text-align:center;">{{ $step++ }}</div>
                                  </td>
                                  <td style="padding:12px 0;font-size:14px;color:#1A1A1A;">
                                    <strong>Open your books</strong><br>
                                    <span style="color:#6F6874;font-size:13px;">Your books start closed. Open them when you&rsquo;re ready to take bookings.</span>
                                  </td>
                                </tr>
                              @endif
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                @endif

                @if(!empty($publicPageUrl))
                  <tr>
                    <td style="margin-top:18px;padding:12px 14px;border:1px dashed #D9CCE0;border-radius:12px;font-size:14px;color:#3d3942;">
                      Then share your link everywhere:<br>
                      <strong style="color:#3E007C;">{{ $publicPageUrl }}</strong>
                    </td>
                  </tr>
                @endif

                @if(!empty($showFounding))
                  <tr>
                    <td style="padding:14px 16px;border-radius:12px;background:#F3E8FF;color:#3E007C;font-size:14px;line-height:1.5;">
                      <strong>You&rsquo;re a Founding artist</strong><br>
                      You joined before 31 December, so you pay no subscription and no commission for as long as your account is active. Your clients pay a small booking fee.
                    </td>
                  </tr>
                @endif

                <tr>
                  <td style="padding:16px 0 8px 0;">
                    <a href="{{ $dashboardUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#1A1A1A;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:13px 22px;border-radius:12px;">
                      Finish setting up
                    </a>
                  </td>
                </tr>
                <tr>
                  <td style="font-size:12.5px;color:#6F6874;line-height:1.5;padding:4px 0 28px 0;">
                    Need help? Email us at <a href="mailto:support@inkjin.com" style="color:#3E007C;font-weight:700;text-decoration:none;">support@inkjin.com</a> or click Get Help in your dashboard to chat with us.
                  </td>
                </tr>
                <tr>
                  <td style="font-size:12px;color:#6F6874;line-height:1.5;padding-top:16px;border-top:1px solid #EEE9F0;">
                    You got this email because you created a Bookpay account.<br>
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
