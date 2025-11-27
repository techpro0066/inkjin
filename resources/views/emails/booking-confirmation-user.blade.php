<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
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
        
        .booking-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid #482e92;
        }
        
        .booking-details h3 {
            color: #482e92;
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
        
        .success-note {
            margin-top: 25px;
            padding: 15px;
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }
        
        .success-note-text {
            font-size: 14px;
            color: #155724;
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
            
            .detail-row {
                flex-direction: column;
            }
            
            .detail-value {
                text-align: left;
                margin-top: 5px;
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
            <p class="greeting">Hello {{ $userName }},</p>
            
            <p class="content">
                Your booking has been confirmed! We're excited to see you for your tattoo appointment.
            </p>
            
            <!-- Booking Details -->
            <div class="booking-details">
                <h3>📅 Booking Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Tattoo:</span>
                    <span class="detail-value">{{ $tattooTitle }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Artist:</span>
                    <span class="detail-value">{{ $artistName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $bookingDate }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">{{ $bookingTime }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value">{{ $duration }} hour(s)</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value">{{ $currencySymbol }}{{ number_format($totalAmount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">#{{ $bookingId }}</span>
                </div>
            </div>
            
            <!-- Success Note -->
            <div class="success-note">
                <p class="success-note-text">
                    <strong>✅ Payment Confirmed!</strong> Your booking is confirmed and your payment has been processed successfully. 
                    Please arrive on time for your appointment. If you need to reschedule or cancel, please contact the artist as soon as possible.
                </p>
            </div>
            
            <p class="content">
                If you have any questions or need to make changes to your booking, please contact the artist directly.
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

