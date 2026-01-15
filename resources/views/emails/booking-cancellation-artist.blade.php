<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Cancellation Notification</title>
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
            border-left: 4px solid #dc3545;
        }
        
        .booking-details h3 {
            color: #dc3545;
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
        
        @if($isNoShow)
        .no-show-notice {
            margin-top: 25px;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }
        
        .no-show-notice-text {
            font-size: 14px;
            color: #856404;
            line-height: 1.6;
        }
        @else
        .cancellation-notice {
            margin-top: 25px;
            padding: 15px;
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            border-radius: 4px;
        }
        
        .cancellation-notice-text {
            font-size: 14px;
            color: #1565c0;
            line-height: 1.6;
        }
        @endif
        
        .refund-info {
            margin-top: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            border-radius: 4px;
        }
        
        .refund-info-text {
            font-size: 14px;
            color: #495057;
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
            <p class="greeting">Hello {{ $artistName }},</p>
            
            @if($isNoShow)
            <p class="content">
                A booking has been marked as a no-show. The customer did not attend the scheduled appointment.
            </p>
            @elseif($cancellationType === 'artist')
            <p class="content">
                You have cancelled a booking. The customer has been notified and will receive a full refund.
            </p>
            @else
            <p class="content">
                A customer has cancelled their booking. Please see the details below.
            </p>
            @endif
            
            <!-- Booking Details -->
            <div class="booking-details">
                <h3>📅 Cancelled Booking Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Customer:</span>
                    <span class="detail-value">{{ $customerName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Customer Email:</span>
                    <span class="detail-value">{{ $customerEmail }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tattoo:</span>
                    <span class="detail-value">{{ $tattooTitle }}</span>
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
                    <span class="detail-label">Cancelled At:</span>
                    <span class="detail-value">{{ $cancelledAt }}</span>
                </div>
                @if($cancellationReason)
                <div class="detail-row">
                    <span class="detail-label">Reason:</span>
                    <span class="detail-value">{{ $cancellationReason }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">#{{ $bookingId }}</span>
                </div>
            </div>
            
            @if($isNoShow)
            <!-- No-Show Notice -->
            <div class="no-show-notice">
                <p class="no-show-notice-text">
                    <strong>⚠️ No-Show</strong><br>
                    The customer did not attend the scheduled appointment.
                    @if($depositForfeited > 0)
                    You will keep the deposit of {{ $currencySymbol }}{{ number_format($depositForfeited, 2) }}.
                    @endif
                </p>
            </div>
            @else
            <!-- Cancellation Notice -->
            <div class="cancellation-notice">
                <p class="cancellation-notice-text">
                    <strong>ℹ️ Booking Cancelled</strong><br>
                    @if($cancellationType === 'artist')
                    You cancelled this booking. The customer will receive a full refund.
                    @else
                    The customer cancelled this booking.
                    @endif
                </p>
            </div>
            @endif
            
            <!-- Refund Information -->
            <div class="refund-info">
                <p class="refund-info-text">
                    <strong>💰 Refund Status</strong><br>
                    @if($hasRefund)
                    Customer will receive a refund of {{ $currencySymbol }}{{ number_format($refundAmount, 2) }}.
                    @if($depositForfeited > 0)
                    You will keep the deposit of {{ $currencySymbol }}{{ number_format($depositForfeited, 2) }}.
                    @endif
                    @else
                    @if($depositForfeited > 0)
                    You will keep the deposit of {{ $currencySymbol }}{{ number_format($depositForfeited, 2) }} as per the cancellation policy.
                    @else
                    No refund will be issued to the customer.
                    @endif
                    @endif
                </p>
            </div>
            
            <p class="content">
                You can view all your bookings and cancellations in your dashboard.
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

