<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Request</title>
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
        
        .booking-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid #ff9800;
        }
        
        .booking-details h3 {
            color: #ff9800;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #333333;
        }
        
        .detail-value {
            color: #666666;
            text-align: right;
        }
        
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .button {
            display: inline-block;
            background-color: #482e92;
            color: #ffffff;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
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
            <p class="greeting">Hello {{ $booking->user->name }},</p>
            
            <p class="content">
                {{ $booking->artist->name }} has requested to reschedule your tattoo booking.
            </p>
            
            <div class="booking-details">
                <h3>📅 Current Booking Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $booking->booking_date->format('F d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">{{ $booking->booking_time['start'] ?? 'N/A' }} - {{ $booking->booking_time['end'] ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tattoo:</span>
                    <span class="detail-value">{{ $booking->tattoo->title ?? 'Custom Tattoo' }}</span>
                </div>
                @if($reason)
                <div class="detail-row">
                    <span class="detail-label">Reason:</span>
                    <span class="detail-value">{{ $reason }}</span>
                </div>
                @endif
            </div>
            
            <p class="content">
                Please select a new date and time by clicking the button below. This reschedule will not count against your reschedule limit.
            </p>
            
            <div class="button-container">
                <a href="{{ route('bookings.reschedule-flow', $booking->id) }}" class="button">
                    Select New Date & Time
                </a>
            </div>
            
            <p class="content" style="font-size: 14px; color: #999;">
                If you cannot accommodate a new time, you may cancel the booking for a full refund.
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
