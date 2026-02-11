<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Rescheduled</title>
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
            border-left: 4px solid #28a745;
        }
        
        .booking-details h3 {
            color: #28a745;
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
        
        .old-booking {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .new-booking {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
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
            <p class="greeting">Hello {{ $isArtist ? $booking->artist->name : $booking->user->name }},</p>
            
            <p class="content">
                @if($isArtist)
                    The booking has been rescheduled. The client has selected a new date and time.
                @else
                    Your booking has been successfully rescheduled!
                @endif
            </p>
            
            <div class="booking-details">
                <h3>📅 Updated Booking Details</h3>
                
                @php
                    $actionHistory = $booking->action_history ?? [];
                    $lastReschedule = collect($actionHistory)->where('action', 'reschedule_completed')->last();
                @endphp
                
                @if($lastReschedule)
                <div class="old-booking">
                    <strong>Previous Date & Time:</strong><br>
                    {{ \Carbon\Carbon::parse($lastReschedule['old_date'])->format('F d, Y') }}<br>
                    {{ $lastReschedule['old_time'] ?? 'N/A' }}
                </div>
                @endif
                
                <div class="new-booking">
                    <strong>New Date & Time:</strong><br>
                    {{ $booking->booking_date->format('F d, Y') }}<br>
                    {{ $booking->booking_time['start'] ?? 'N/A' }} - {{ $booking->booking_time['end'] ?? 'N/A' }}
                </div>
                
                <div class="detail-row" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                    <span class="detail-label">Tattoo:</span>
                    <span class="detail-value">{{ $booking->tattoo->title ?? 'Custom Tattoo' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">#{{ $booking->id }}</span>
                </div>
            </div>
            
            <p class="content">
                Please make note of the new date and time. If you have any questions, please contact {{ $isArtist ? $booking->user->name : $booking->artist->name }} directly.
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
