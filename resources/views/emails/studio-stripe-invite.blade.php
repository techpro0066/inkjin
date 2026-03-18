<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect Your Stripe Account - Inkjin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
        
        .email-body {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .content {
            font-size: 16px;
            color: #666666;
            margin-bottom: 20px;
            line-height: 1.8;
        }
        
        .info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid #482e92;
        }
        
        .info-box p {
            margin: 0;
            color: #666666;
            line-height: 1.8;
        }
        
        .cta-button {
            display: inline-block;
            background-color: #482e92;
            color: #ffffff;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 25px 0;
            font-size: 16px;
            text-align: center;
        }
        
        .cta-button:hover {
            background-color: #3a2474;
        }
        
        .note {
            margin-top: 25px;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }
        
        .note-text {
            font-size: 14px;
            color: #856404;
            line-height: 1.6;
        }
        
        .email-footer {
            background-color: #f8f8f8;
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }
        
        .footer-text {
            font-size: 14px;
            color: #888888;
            line-height: 1.6;
        }
        
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .email-header {
                padding: 15px 20px;
            }
            
            .logo-text {
                font-size: 20px;
            }
            
            .email-logo {
                max-width: 60px;
            }
            
            .email-body {
                padding: 30px 20px;
            }
            
            .cta-button {
                display: block;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="logo-container">
                <img src="{{ url('assets/img/branding/logo.png') }}" alt="{{ config('app.name', 'Inkjin') }}" class="email-logo" />
                <span class="logo-text">{{ config('app.name', 'Inkjin') }}</span>
            </div>
        </div>
        
        <!-- Body -->
        <div class="email-body">
            <p class="greeting">Hi {{ $studioName }},</p>
            
            <p class="content">
                {{ $artistName }} has listed your studio as the payment recipient for bookings made through Inkjin.
            </p>
            
            <p class="content">
                To receive payments, please connect your Stripe account using the link below:
            </p>
            
            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ $connectLink }}" class="cta-button">
                    Connect Stripe
                </a>
            </div>
            
            <!-- Info Box -->
            <div class="info-box">
                <p>
                    Once your account is connected, payments for this artist's bookings will be sent to your studio through Inkjin.
                </p>
            </div>
            
            <!-- Note -->
            <div class="note">
                <p class="note-text">
                    <strong>ℹ️ Information:</strong> If you already have a Stripe account, you can connect it in a few steps. If not, you can create one during the process.
                </p>
            </div>
            
            <p class="content" style="margin-top: 30px; font-size: 14px; color: #999999;">
                If you were not expecting this email or believe it was sent in error, please ignore it.
            </p>
            
            <p class="content" style="margin-top: 20px;">
                Best,<br>
                <strong>The Inkjin Team</strong>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <p class="footer-text" style="font-size: 12px; color: #aaaaaa;">
                © {{ date('Y') }} {{ config('app.name', 'Inkjin') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
