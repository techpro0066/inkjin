<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Completion Code</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f6fb; padding:24px; color:#1f2937;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="padding:20px 24px; border-bottom:1px solid #e5e7eb;">
            <h2 style="margin:0; font-size:20px;">Your Completion Code</h2>
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">
                Your artist will ask for this code when marking your booking as completed.
            </p>
            <div style="font-size:28px; letter-spacing:4px; font-weight:700; text-align:center; padding:16px; border:1px dashed #9ca3af; border-radius:10px; background:#f9fafb;">
                {{ $code }}
            </div>
            <p style="margin:18px 0 0;">
                Booking reference:
                <strong>#INK-{{ str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT) }}</strong>
            </p>
            <p style="margin:8px 0 0; color:#6b7280; font-size:13px;">
                Do not share this code with anyone except your artist during your session completion.
            </p>
        </div>
    </div>
</body>
</html>

