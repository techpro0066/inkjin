<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Request Declined</title>
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
            background: #dc3545;
            padding: 20px 30px;
            text-align: center;
        }
        
        .logo-text {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
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
        
        .alert-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
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
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <span class="logo-text">{{ config('app.name', 'Inkjin') }}</span>
        </div>
        
        <div class="email-body">
            <p class="greeting">Hello {{ $booking->artist->name }},</p>
            
            <p class="content">
                Unfortunately, {{ $booking->user->name }} has declined your reschedule request and the booking will be cancelled.
            </p>
            
            <div class="alert-box">
                <h3 style="color: #721c24; margin-bottom: 10px;">Booking Cancelled</h3>
                <p style="color: #721c24; margin: 0;">
                    Since the client declined the reschedule request, the booking has been cancelled. 
                    A full refund will be processed for the client.
                </p>
            </div>
            
            @if($reason)
            <p class="content">
                <strong>Client's reason:</strong> {{ $reason }}
            </p>
            @endif
            
            <p class="content">
                If you have any questions, please contact support.
            </p>
        </div>
        
        <div class="email-footer">
            <p class="footer-text">
                © {{ date('Y') }} {{ config('app.name', 'Inkjin') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
