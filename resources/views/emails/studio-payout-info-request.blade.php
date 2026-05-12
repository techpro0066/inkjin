<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout details</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: #482e92;
            padding: 20px 30px;
            text-align: center;
        }
        .logo-text { color: #ffffff; font-size: 24px; font-weight: 600; }
        .email-body { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #333333; margin-bottom: 20px; font-weight: 500; }
        .content { font-size: 16px; color: #666666; margin-bottom: 18px; line-height: 1.8; }
        .button-row { text-align: center; margin: 30px 0 16px; }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            background: linear-gradient(135deg, #310f7a 0%, #482d91 100%);
        }
        .btn-decline {
            background: #ffffff;
            color: #b42318 !important;
            border: 2px solid #b42318;
            margin-left: 12px;
        }
        .btn-row-split { text-align: center; margin: 30px 0 16px; }
        .btn-row-split .btn { margin: 6px 8px; }
        .email-footer {
            background-color: #f8f8f8;
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #aaaaaa;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <span class="logo-text">{{ config('app.name', 'Inkjin') }}</span>
        </div>
        <div class="email-body">
            <p class="greeting">Hello {{ $studioName }},</p>
            <p class="content">
                <strong>{{ $artistName }}</strong> has selected your studio for payouts on {{ config('app.name', 'Inkjin') }}.
            </p>
            @if(!empty($showApproveDecline))
                <p class="content">
                    We already have your studio’s bank payout details on file. Please choose whether to <strong>allow this artist to receive payouts through your studio</strong> on {{ config('app.name', 'Inkjin') }}.
                </p>
                <div class="btn-row-split">
                    <a href="{{ $approveUrl }}" class="btn">Approve</a>
                    <a href="{{ $declineUrl }}" class="btn btn-decline">Decline</a>
                </div>
                <p class="content" style="font-size: 13px; color: #888;">
                    If the buttons do not work, copy one of these URLs:<br>
                    <span style="word-break: break-all;">Approve: {{ $approveUrl }}</span><br><br>
                    <span style="word-break: break-all;">Decline: {{ $declineUrl }}</span>
                </p>
            @else
                <p class="content">
                    Please open the secure link below and submit your payout bank details. You can use this link again whenever we send a new request to update your information.
                </p>
                <div class="button-row">
                    <a href="{{ $formUrl }}" class="btn">Provide payout details</a>
                </div>
                <p class="content" style="font-size: 13px; color: #888;">
                    If the button does not work, copy and paste this URL into your browser:<br>
                    <span style="word-break: break-all;">{{ $formUrl }}</span>
                </p>
            @endif
        </div>
        <div class="email-footer">
            © {{ date('Y') }} {{ config('app.name', 'Inkjin') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
