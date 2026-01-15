# Cancellation Flow - Complete Guide

## Table of Contents
1. [Overview](#overview)
2. [Business Rules](#business-rules)
3. [Database Schema](#database-schema)
4. [Cancellation Scenarios](#cancellation-scenarios)
5. [Refund Logic](#refund-logic)
6. [Implementation Flow](#implementation-flow)
7. [API Endpoints](#api-endpoints)
8. [Email Notifications](#email-notifications)
9. [UI/UX Considerations](#uiux-considerations)
10. [Edge Cases](#edge-cases)

---

## Overview

The cancellation system allows both artists and clients to cancel bookings with automatic refund processing based on cancellation deadlines set by artists. The system handles partial refunds, full refunds, and no refund scenarios based on timing and payment status.

---

## Business Rules

### 1. Cancellation Deadline
- **Artist Configuration**: Each artist sets their own cancellation deadline (e.g., 24 hours, 48 hours, 72 hours before booking time)
- **Storage**: Stored in `user_details.cancellation_window_hours` (already exists)
- **Calculation**: Cancellation deadline = Booking date/time - Cancellation window hours

### 2. Refund Rules

#### Client Cancellation Before Deadline
- ✅ **Full Refund**: Both deposit and full payment are refunded
- ✅ **Status**: Booking status changes to `cancelled`
- ✅ **Cancelled By**: Set to client's user ID
- ✅ **Refund Reason**: "Cancelled before deadline"

#### Client Cancellation After Deadline
- ❌ **No Refund**: Artist keeps the deposit
- ✅ **Status**: Booking status changes to `cancelled`
- ✅ **Cancelled By**: Set to client's user ID
- ✅ **Refund Reason**: "Cancelled after deadline - deposit forfeited"

#### Client Cancellation After Deadline (Full Payment Made)
- 💰 **Partial Refund**: 
  - Artist keeps deposit amount
  - Remaining balance (full payment - deposit) is refunded to client
- ✅ **Status**: Booking status changes to `cancelled`
- ✅ **Cancelled By**: Set to client's user ID
- ✅ **Refund Reason**: "Cancelled after deadline - partial refund"

#### Client No-Show
- ❌ **No Refund**: Artist keeps the deposit (same as cancellation after deadline)
- ✅ **Status**: Booking status changes to `no_show`
- ✅ **Cancelled By**: Set to client's user ID (or system)
- ✅ **Refund Reason**: "No-show - deposit forfeited"

#### Artist Cancellation
- ✅ **Full Refund**: Both deposit and full payment are refunded (regardless of timing)
- ✅ **Status**: Booking status changes to `cancelled`
- ✅ **Cancelled By**: Set to artist's user ID
- ✅ **Refund Reason**: "Cancelled by artist"

---

## Database Schema

### Existing Fields (Already in `bookings` table)
- `cancelled_by` (user_id) - Who cancelled the booking
- `cancelled_at` (datetime) - When cancellation occurred
- `cancellation_reason` (text) - Reason for cancellation
- `cancellation_deadline` (datetime) - Calculated deadline
- `cancellation_window_hours` (integer) - Artist's cancellation window
- `refund_amount` (decimal) - Amount to be refunded
- `refund_intent_id` (string) - Stripe refund intent ID
- `refunded_at` (datetime) - When refund was processed
- `refund_reason` (text) - Reason for refund
- `platform_fee_refunded` (boolean) - Whether platform fee was refunded
- `status` (enum) - Booking status (includes 'cancelled', 'no_show')

### Additional Fields Needed
```sql
-- Add to bookings table
ALTER TABLE bookings ADD COLUMN cancellation_initiated_at DATETIME NULL AFTER cancelled_at;
ALTER TABLE bookings ADD COLUMN cancellation_type ENUM('client', 'artist', 'system') NULL AFTER cancellation_reason;
ALTER TABLE bookings ADD COLUMN deposit_forfeited DECIMAL(10,2) DEFAULT 0 AFTER refund_amount;
ALTER TABLE bookings ADD COLUMN refund_status ENUM('pending', 'processing', 'completed', 'failed', 'partial') NULL AFTER refund_reason;
```

---

## Cancellation Scenarios

### Scenario 1: Client Cancels Before Deadline (Deposit Paid)
```
Timeline:
- Booking Date: Dec 5, 2025 2:00 PM
- Cancellation Window: 24 hours
- Cancellation Deadline: Dec 4, 2025 2:00 PM
- Client Cancels: Dec 3, 2025 10:00 AM ✅ (Before deadline)

Payment Status:
- Deposit Paid: $100
- Full Payment: Not paid

Result:
✅ Full Refund: $100
✅ Platform Fee Refunded: Yes
✅ Status: cancelled
✅ Cancelled By: Client User ID
✅ Refund Reason: "Cancelled before deadline"
```

### Scenario 2: Client Cancels Before Deadline (Full Payment Paid)
```
Timeline:
- Booking Date: Dec 5, 2025 2:00 PM
- Cancellation Window: 24 hours
- Cancellation Deadline: Dec 4, 2025 2:00 PM
- Client Cancels: Dec 3, 2025 10:00 AM ✅ (Before deadline)

Payment Status:
- Deposit Paid: $100
- Full Payment Paid: $500 (Total: $600)

Result:
✅ Full Refund: $600
✅ Platform Fee Refunded: Yes
✅ Status: cancelled
✅ Cancelled By: Client User ID
✅ Refund Reason: "Cancelled before deadline"
```

### Scenario 3: Client Cancels After Deadline (Deposit Paid)
```
Timeline:
- Booking Date: Dec 5, 2025 2:00 PM
- Cancellation Window: 24 hours
- Cancellation Deadline: Dec 4, 2025 2:00 PM
- Client Cancels: Dec 4, 2025 5:00 PM ❌ (After deadline)

Payment Status:
- Deposit Paid: $100
- Full Payment: Not paid

Result:
❌ No Refund: $0 (Artist keeps deposit)
❌ Platform Fee: Not refunded
✅ Status: cancelled
✅ Cancelled By: Client User ID
✅ Refund Reason: "Cancelled after deadline - deposit forfeited"
✅ Deposit Forfeited: $100
```

### Scenario 4: Client Cancels After Deadline (Full Payment Paid)
```
Timeline:
- Booking Date: Dec 5, 2025 2:00 PM
- Cancellation Window: 24 hours
- Cancellation Deadline: Dec 4, 2025 2:00 PM
- Client Cancels: Dec 4, 2025 5:00 PM ❌ (After deadline)

Payment Status:
- Deposit Paid: $100
- Full Payment Paid: $500 (Total: $600)

Result:
💰 Partial Refund: $500 (Full payment - Deposit)
💰 Artist Keeps: $100 (Deposit)
✅ Platform Fee Refunded: Partial (proportional)
✅ Status: cancelled
✅ Cancelled By: Client User ID
✅ Refund Reason: "Cancelled after deadline - partial refund"
✅ Deposit Forfeited: $100
✅ Refund Status: partial
```

### Scenario 5: Client No-Show
```
Timeline:
- Booking Date: Dec 5, 2025 2:00 PM
- Booking Time Passed: Dec 5, 2025 2:30 PM
- Client: Did not show up

Payment Status:
- Deposit Paid: $100
- Full Payment: Not paid (or paid)

Result:
❌ No Refund: $0 (Artist keeps deposit)
❌ Platform Fee: Not refunded
✅ Status: no_show
✅ Cancelled By: Client User ID (or System)
✅ Refund Reason: "No-show - deposit forfeited"
✅ Deposit Forfeited: $100
```

### Scenario 6: Artist Cancels (Any Time)
```
Timeline:
- Booking Date: Dec 5, 2025 2:00 PM
- Artist Cancels: Dec 4, 2025 8:00 PM (Any time)

Payment Status:
- Deposit Paid: $100 (or Full Payment Paid: $600)

Result:
✅ Full Refund: $100 (or $600)
✅ Platform Fee Refunded: Yes
✅ Status: cancelled
✅ Cancelled By: Artist User ID
✅ Refund Reason: "Cancelled by artist"
✅ Cancellation Type: artist
```

---

## Refund Logic

### Refund Calculation Flow

```php
function calculateRefund($booking, $cancelledBy) {
    $now = now();
    $bookingDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time_utc);
    $cancellationDeadline = $bookingDateTime->subHours($booking->cancellation_window_hours);
    
    // Artist cancellation = full refund always
    if ($cancelledBy === $booking->artist_user_id) {
        return [
            'refund_amount' => $booking->total_amount_paid,
            'deposit_forfeited' => 0,
            'platform_fee_refunded' => true,
            'refund_reason' => 'Cancelled by artist',
            'refund_status' => 'pending'
        ];
    }
    
    // Client cancellation before deadline = full refund
    if ($now->lt($cancellationDeadline)) {
        return [
            'refund_amount' => $booking->total_amount_paid,
            'deposit_forfeited' => 0,
            'platform_fee_refunded' => true,
            'refund_reason' => 'Cancelled before deadline',
            'refund_status' => 'pending'
        ];
    }
    
    // Client cancellation after deadline
    if ($now->gte($cancellationDeadline)) {
        $depositAmount = $booking->deposit_amount;
        
        // If only deposit paid, artist keeps it all
        if (!$booking->full_amount_paid) {
            return [
                'refund_amount' => 0,
                'deposit_forfeited' => $depositAmount,
                'platform_fee_refunded' => false,
                'refund_reason' => 'Cancelled after deadline - deposit forfeited',
                'refund_status' => 'completed' // No refund to process
            ];
        }
        
        // If full payment made, refund remaining balance
        $remainingBalance = $booking->total_amount_paid - $depositAmount;
        $platformFeeRefund = ($booking->platform_fee / $booking->total_amount_paid) * $remainingBalance;
        
        return [
            'refund_amount' => $remainingBalance,
            'deposit_forfeited' => $depositAmount,
            'platform_fee_refunded' => true,
            'platform_fee_refund_amount' => $platformFeeRefund,
            'refund_reason' => 'Cancelled after deadline - partial refund',
            'refund_status' => 'pending'
        ];
    }
}

// No-show logic (similar to cancellation after deadline)
function handleNoShow($booking) {
    $depositAmount = $booking->deposit_amount;
    
    if (!$booking->full_amount_paid) {
        return [
            'refund_amount' => 0,
            'deposit_forfeited' => $depositAmount,
            'platform_fee_refunded' => false,
            'refund_reason' => 'No-show - deposit forfeited',
            'refund_status' => 'completed'
        ];
    }
    
    // If full payment made, refund remaining balance
    $remainingBalance = $booking->total_amount_paid - $depositAmount;
    
    return [
        'refund_amount' => $remainingBalance,
        'deposit_forfeited' => $depositAmount,
        'platform_fee_refunded' => true,
        'refund_reason' => 'No-show - partial refund',
        'refund_status' => 'pending'
    ];
}
```

### Stripe Refund Processing

```php
function processStripeRefund($booking, $refundAmount, $reason) {
    try {
        // Get payment intent
        $paymentIntentId = $booking->payment_intent_id;
        
        // Create refund via Stripe
        $refund = \Stripe\Refund::create([
            'payment_intent' => $paymentIntentId,
            'amount' => $refundAmount * 100, // Convert to cents
            'reason' => 'requested_by_customer',
            'metadata' => [
                'booking_id' => $booking->id,
                'refund_reason' => $reason,
            ]
        ]);
        
        // Update booking
        $booking->update([
            'refund_intent_id' => $refund->id,
            'refunded_at' => now(),
            'refund_status' => $refund->status === 'succeeded' ? 'completed' : 'processing',
        ]);
        
        return $refund;
    } catch (\Exception $e) {
        // Handle error
        $booking->update([
            'refund_status' => 'failed',
        ]);
        throw $e;
    }
}
```

---

## Implementation Flow

### Step 1: Client Initiates Cancellation

```
1. Client clicks "Cancel Booking" button
2. System checks:
   - Is booking cancellable? (status = 'confirmed')
   - Calculate cancellation deadline
   - Determine refund eligibility
3. Show confirmation modal with:
   - Cancellation deadline info
   - Refund amount (if any)
   - Warning if deposit will be forfeited
4. Client confirms cancellation
5. Process cancellation
```

### Step 2: Cancellation Processing

```
1. Calculate refund amount using refund logic
2. Update booking record:
   - status = 'cancelled' or 'no_show'
   - cancelled_by = user_id
   - cancelled_at = now()
   - cancellation_reason = user input
   - cancellation_type = 'client'
   - refund_amount = calculated amount
   - deposit_forfeited = calculated amount
   - refund_status = 'pending' or 'completed'
3. If refund_amount > 0:
   - Process Stripe refund
   - Update refund_intent_id
   - Update refunded_at
   - Update refund_status
4. Update action_history
5. Send email notifications
6. Cancel Google Calendar event (if exists)
```

### Step 3: Artist Initiates Cancellation

```
1. Artist clicks "Cancel Booking" button
2. System checks:
   - Is booking cancellable? (status = 'confirmed')
3. Show confirmation modal (optional reason)
4. Artist confirms cancellation
5. Process cancellation:
   - Always full refund
   - Update booking record
   - Process Stripe refund
   - Send notifications
   - Cancel calendar event
```

### Step 4: No-Show Detection

```
1. System checks bookings after booking time passes
2. For bookings with status = 'confirmed':
   - Check if booking time has passed
   - Check if no completion code entered
   - Mark as no-show after grace period (e.g., 30 minutes)
3. Process no-show:
   - Update status to 'no_show'
   - Apply no-show refund logic
   - Send notifications
```

---

## API Endpoints

### 1. Cancel Booking (Client/Artist)
```
POST /api/bookings/{id}/cancel

Request Body:
{
    "reason": "Optional cancellation reason",
    "confirmed": true
}

Response:
{
    "success": true,
    "message": "Booking cancelled successfully",
    "booking": {
        "id": 123,
        "status": "cancelled",
        "refund_amount": 100.00,
        "refund_status": "pending",
        "deposit_forfeited": 0,
        "cancellation_deadline": "2025-12-04 14:00:00",
        "cancelled_at": "2025-12-03 10:00:00"
    }
}
```

### 2. Get Cancellation Info
```
GET /api/bookings/{id}/cancellation-info

Response:
{
    "booking_id": 123,
    "booking_date": "2025-12-05 14:00:00",
    "cancellation_window_hours": 24,
    "cancellation_deadline": "2025-12-04 14:00:00",
    "can_cancel": true,
    "is_before_deadline": true,
    "estimated_refund": {
        "amount": 100.00,
        "deposit_forfeited": 0,
        "platform_fee_refunded": true
    },
    "refund_eligibility": "full_refund"
}
```

### 3. Mark No-Show (Artist/System)
```
POST /api/bookings/{id}/mark-no-show

Request Body:
{
    "confirmed": true
}

Response:
{
    "success": true,
    "message": "Booking marked as no-show",
    "booking": {
        "id": 123,
        "status": "no_show",
        "deposit_forfeited": 100.00
    }
}
```

---

## Email Notifications

### 1. Client Cancellation Email (Before Deadline)
```
Subject: Booking Cancelled - Full Refund Processed

Content:
- Booking details
- Cancellation confirmation
- Refund amount and timeline
- Refund will appear in 5-10 business days
```

### 2. Client Cancellation Email (After Deadline - Deposit Only)
```
Subject: Booking Cancelled - Deposit Forfeited

Content:
- Booking details
- Cancellation confirmation
- Deposit forfeited notice
- No refund will be issued
```

### 3. Client Cancellation Email (After Deadline - Partial Refund)
```
Subject: Booking Cancelled - Partial Refund Processed

Content:
- Booking details
- Cancellation confirmation
- Deposit forfeited: $X
- Refund amount: $Y
- Refund timeline
```

### 4. Artist Cancellation Email (To Client)
```
Subject: Booking Cancelled by Artist - Full Refund Processed

Content:
- Booking details
- Artist cancellation notice
- Full refund confirmation
- Apology message
```

### 5. No-Show Notification (To Client)
```
Subject: Booking Marked as No-Show

Content:
- Booking details
- No-show notice
- Deposit forfeited
- Future booking policies
```

### 6. Cancellation Notification (To Artist)
```
Subject: Booking Cancelled by Client

Content:
- Booking details
- Cancellation reason (if provided)
- Refund status
- Deposit status (kept or refunded)
```

---

## UI/UX Considerations

### Cancellation Button Placement
- **Client View**: 
  - In booking detail modal
  - In bookings list (for confirmed bookings)
  - Disabled if booking time has passed
  
- **Artist View**:
  - In booking detail modal
  - In bookings list
  - Can cancel anytime before booking completion

### Cancellation Modal Content

#### Before Deadline
```
⚠️ Cancel Booking?

You are cancelling before the cancellation deadline.
✅ You will receive a full refund of $X.XX
✅ Refund will be processed within 5-10 business days

Cancellation Deadline: [Date/Time]
Current Time: [Date/Time]

Reason (optional):
[Text area]

[Cancel] [Confirm Cancellation]
```

#### After Deadline (Deposit Only)
```
⚠️ Cancel Booking?

You are cancelling after the cancellation deadline.
❌ Your deposit of $X.XX will be forfeited
❌ No refund will be issued

Cancellation Deadline: [Date/Time] (Passed)
Current Time: [Date/Time]

Reason (optional):
[Text area]

[Cancel] [Confirm Cancellation]
```

#### After Deadline (Partial Refund)
```
⚠️ Cancel Booking?

You are cancelling after the cancellation deadline.
💰 Your deposit of $X.XX will be forfeited
✅ Remaining balance of $Y.YY will be refunded
✅ Refund will be processed within 5-10 business days

Cancellation Deadline: [Date/Time] (Passed)
Current Time: [Date/Time]

Reason (optional):
[Text area]

[Cancel] [Confirm Cancellation]
```

### Booking Status Badges
- **Cancelled**: Red badge with refund status
- **No-Show**: Dark badge
- **Refund Pending**: Yellow badge with "Refund Processing"
- **Refund Completed**: Green badge with "Refunded"

---

## Edge Cases

### Edge Case 1: Cancellation Exactly at Deadline
```
Solution: Use >= comparison (after deadline includes deadline time)
Result: Treated as after deadline
```

### Edge Case 2: Multiple Cancellation Attempts
```
Solution: Check booking status before processing
Prevent: Already cancelled bookings cannot be cancelled again
```

### Edge Case 3: Refund Processing Failure
```
Solution: 
- Set refund_status = 'failed'
- Log error
- Allow manual retry
- Notify admin
```

### Edge Case 4: Booking Already Started
```
Solution: 
- Disable cancellation button
- Show "Booking in progress" message
- Only allow artist to mark as completed/no-show
```

### Edge Case 5: Stripe Account Disconnected
```
Solution:
- Store refund request
- Queue for manual processing
- Notify admin
- Update refund_status = 'pending_manual'
```

### Edge Case 6: Partial Payment Scenarios
```
Solution:
- Track what was actually paid
- Refund only what was paid
- Handle platform fee proportionally
```

### Edge Case 7: Timezone Differences
```
Solution:
- All calculations in UTC
- Display in user's timezone
- Ensure cancellation deadline uses booking timezone
```

---

## Database Queries Needed

### Get Cancellable Bookings
```sql
SELECT * FROM bookings 
WHERE status = 'confirmed' 
AND booking_date >= CURDATE()
AND (booking_date > CURDATE() OR start_time_utc > CURTIME())
```

### Get Bookings Past Cancellation Deadline
```sql
SELECT * FROM bookings 
WHERE status = 'confirmed'
AND DATE_SUB(CONCAT(booking_date, ' ', start_time_utc), 
             INTERVAL cancellation_window_hours HOUR) < NOW()
```

### Get Pending Refunds
```sql
SELECT * FROM bookings 
WHERE refund_status IN ('pending', 'processing')
AND refund_amount > 0
```

---

## Testing Scenarios

### Test Case 1: Client Cancels Before Deadline (Deposit)
- ✅ Verify full refund processed
- ✅ Verify booking status updated
- ✅ Verify email sent
- ✅ Verify calendar event cancelled

### Test Case 2: Client Cancels After Deadline (Deposit)
- ✅ Verify no refund
- ✅ Verify deposit forfeited
- ✅ Verify booking status updated
- ✅ Verify email sent

### Test Case 3: Client Cancels After Deadline (Full Payment)
- ✅ Verify partial refund (full - deposit)
- ✅ Verify deposit forfeited
- ✅ Verify booking status updated
- ✅ Verify email sent

### Test Case 4: Artist Cancels
- ✅ Verify full refund always
- ✅ Verify booking status updated
- ✅ Verify both emails sent
- ✅ Verify calendar event cancelled

### Test Case 5: No-Show
- ✅ Verify deposit forfeited
- ✅ Verify booking status = no_show
- ✅ Verify email sent
- ✅ Verify no refund (if deposit only)

### Test Case 6: Edge Cases
- ✅ Cancellation at exact deadline
- ✅ Multiple cancellation attempts
- ✅ Refund processing failure
- ✅ Timezone handling

---

## Summary

This cancellation flow provides:
1. ✅ Flexible cancellation deadlines per artist
2. ✅ Automatic refund processing based on timing
3. ✅ Fair handling of deposits and full payments
4. ✅ Clear communication via emails
5. ✅ Protection for artists (deposit retention)
6. ✅ Protection for clients (full refund before deadline)
7. ✅ Special handling for artist cancellations
8. ✅ No-show detection and handling

The system ensures transparency, fairness, and automated processing while protecting both parties' interests.

