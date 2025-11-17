<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
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
            margin-bottom: 30px;
            line-height: 1.8;
        }
        
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        
        .reset-button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #482d91 0%, #5a3aa3 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(72, 45, 145, 0.3);
        }
        
        .reset-button:hover {
            background: linear-gradient(135deg, #5a3aa3 0%, #6b4ab5 100%);
            box-shadow: 0 6px 16px rgba(72, 45, 145, 0.4);
            transform: translateY(-2px);
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
        
        .security-note {
            margin-top: 25px;
            padding: 15px;
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            text-align: center;
        }
        
        .security-note-text {
            font-size: 13px;
            color: #856404;
            line-height: 1.6;
        }

        .link-fallback {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 6px;
            text-align: center;
        }

        .link-fallback-text {
            font-size: 13px;
            color: #666666;
            word-break: break-all;
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
            
            .reset-button {
                padding: 14px 30px;
                font-size: 15px;
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
            <p class="greeting">Hello {{ $user->name }},</p>
            
            <p class="content">
                We received a request to reset your password for your <strong>{{ config('app.name', 'Inkjin') }}</strong> account.
            </p>
            
            <p class="content">
                Click the button below to reset your password. If you didn't request this, please ignore this email.
            </p>
            
            <!-- Reset Button -->
            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button">Reset Password</a>
            </div>

            <!-- Link Fallback -->
            <div class="link-fallback">
                <p class="link-fallback-text">
                    If the button doesn't work, copy and paste this link into your browser:<br>
                    <a href="{{ $resetUrl }}" style="color: #482d91; text-decoration: underline;">{{ $resetUrl }}</a>
                </p>
            </div>
            
            <!-- Security Note -->
            <div class="security-note">
                <p class="security-note-text">
                    <strong>Security Note:</strong> This password reset link will expire in {{ $count }} minutes. If you didn't request a password reset, please ignore this email or contact support if you have concerns.
                </p>
            </div>
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

