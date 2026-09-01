<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Artist referral reward ready</title>
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
              Artist referral reward ready to pay
            </td>
          </tr>
          <tr>
            <td style="font-size:15px;color:#494552;line-height:1.7;padding-bottom:24px;">
              A referred artist has qualified for their referrer reward. Please review and pay the referring artist manually.
            </td>
          </tr>
          <tr>
            <td style="background:#f8f1fb;border-radius:12px;padding:20px;font-size:14px;color:#1c1b21;line-height:1.8;">
              <strong>Referrer:</strong> {{ \App\Services\ArtistReferralRewardService::displayName($referral->referrer) }}<br>
              <strong>Referrer email:</strong> {{ $referral->referrer?->email ?? '—' }}<br>
              <strong>Referred artist:</strong> {{ \App\Services\ArtistReferralRewardService::displayName($referral->referred) }}<br>
              <strong>Referred email:</strong> {{ $referral->referred?->email ?? '—' }}<br>
              <strong>Reward amount:</strong> ${{ number_format((float) $referral->reward_amount, 2) }}<br>
              <strong>Qualified booking:</strong> {{ $booking->referenceLabel() }} (#{{ $booking->id }})<br>
              <strong>Trigger:</strong> {{ \App\Services\ArtistReferralRewardService::qualifyReasonLabel($qualifyReason) }}<br>
              <strong>Referral signed up:</strong> {{ $referral->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}
            </td>
          </tr>
          <tr>
            <td style="font-size:14px;color:#494552;line-height:1.7;padding-top:24px;padding-bottom:28px;">
              This referral has been marked as <strong>Wait for approval</strong> in the referrals list.
            </td>
          </tr>
          <tr>
            <td>
              <a href="{{ $referralsUrl }}" style="display:inline-block;background:#310f7a;color:#fff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 20px;border-radius:999px;">
                View referrals
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
