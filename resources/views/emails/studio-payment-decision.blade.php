<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Payment Approval Request</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .logo-text {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        .email-logo {
            max-width: 80px;
            height: auto;
        }
        .email-body { padding: 40px 30px; }
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .content {
            font-size: 16px;
            color: #666666;
            margin-bottom: 18px;
            line-height: 1.8;
        }
        .button-row {
            text-align: center;
            margin: 30px 0 16px;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            margin: 0 6px;
        }
        .btn-allow { background: linear-gradient(135deg, #1e8e3e 0%, #2aa04b 100%); }
        .btn-decline { background: linear-gradient(135deg, #b42318 0%, #c5221f 100%); }
        .security-note {
            margin-top: 24px;
            padding: 14px;
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }
        .security-note-text {
            font-size: 13px;
            color: #856404;
            line-height: 1.6;
        }
        .email-footer {
            background-color: #f8f8f8;
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #aaaaaa;
        }
        @media only screen and (max-width: 600px) {
            body { padding: 10px; }
            .email-header { padding: 15px 20px; }
            .logo-text { font-size: 20px; }
            .email-logo { max-width: 60px; }
            .email-body { padding: 30px 20px; }
            .btn { display: block; margin: 8px 0; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="logo-container">
                <img src="{{ url('assets/img/branding/logo.png') }}" alt="{{ config('app.name', 'Inkjin') }}" class="email-logo" />
                <span class="logo-text">{{ config('app.name', 'Inkjin') }}</span>
            </div>
        </div>

        <div class="email-body">
            <p class="greeting">Hello {{ $studioName }},</p>

            <p class="content">
                <strong>{{ $artistName }}</strong> selected your studio as payout receiver on {{ config('app.name', 'Inkjin') }}.
            </p>
            <p class="content">
                Please approve or decline this request.
                This is only an access decision email (yes/no) and does not ask you to connect Stripe right now.
            </p>

            <div class="button-row">
                <a href="{{ $allowUrl }}" class="btn btn-allow">Allow</a>
                <a href="{{ $declineUrl }}" class="btn btn-decline">Decline</a>
            </div>

            <div class="security-note">
                <p class="security-note-text">
                    <strong>Important:</strong> Once submitted, this decision cannot be changed.
                </p>
            </div>
        </div>

        <div class="email-footer">
            © {{ date('Y') }} {{ config('app.name', 'Inkjin') }}. All rights reserved.
        </div>
    </div>
</body>
</html>

