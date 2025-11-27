# Booking Table Enhancements Based on System Overview

## Analysis Summary

After reviewing the System Overview requirements, the following fields need to be added to the `bookings` table to fully support all features.

---

## Missing Fields Required

### 1. Booking Type & Custom Tattoo Details

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `booking_type` | ENUM | NOT NULL, DEFAULT 'flash' | Type of booking: 'custom' or 'flash' |
| `custom_tattoo_details` | JSON | NULLABLE | For custom tattoos: style, size, color, placement, description, image references |

**Reason:** System Overview distinguishes between:
- **Custom Tattoo Booking**: Client requests custom design with style, size, color, placement, images, description
- **Flash Tattoo Booking**: Client books from artist's available designs

---

### 2. Consultation Appointment (Custom Tattoos)

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `has_consultation` | BOOLEAN | DEFAULT FALSE | Whether consultation appointment is required |
| `consultation_date` | DATE | NULLABLE | Date of consultation appointment |
| `consultation_start_time_utc` | TIME | NULLABLE | Consultation start time in UTC |
| `consultation_end_time_utc` | TIME | NULLABLE | Consultation end time in UTC |
| `consultation_completed` | BOOLEAN | DEFAULT FALSE | Whether consultation has been completed |

**Reason:** System Overview states: "Maria sends Alex an email with one booking link that includes: A short consultation appointment (30 minutes, in-person or online). The tattoo session (6 hours, £800 total)."

---

### 3. Reschedule Tracking

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `reschedule_count` | INTEGER | DEFAULT 0 | Number of times this booking has been rescheduled |
| `reschedule_limit` | INTEGER | NULLABLE | Maximum allowed reschedules (from artist settings) |

**Reason:** System Overview states: "The artist decides if the client can reschedule and how many times (e.g. never, once, twice, unlimited)."

---

### 4. Payout Tracking

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `deposit_released` | BOOLEAN | DEFAULT FALSE | Whether deposit has been released to artist |
| `deposit_released_at` | TIMESTAMP | NULLABLE | When deposit was released to artist |
| `remaining_amount_released` | BOOLEAN | DEFAULT FALSE | Whether remaining balance has been released (for full payments) |
| `remaining_amount_released_at` | TIMESTAMP | NULLABLE | When remaining balance was released |
| `completion_code` | VARCHAR(255) | NULLABLE, UNIQUE | Code given by client after session completion (for full payment release) |
| `completion_code_entered_at` | TIMESTAMP | NULLABLE | When artist entered the completion code |

**Reason:** System Overview states:
- "Deposits are immediately released to the artist after your cancellation window closes."
- "Full payments while deposit is released immediately, the remaining amount is held until the session is completed (client gives the artist a code after the tattoo session is completed)."

---

### 5. Refund Tracking

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `refund_amount` | DECIMAL(10,2) | DEFAULT 0.00 | Total amount refunded to customer |
| `refund_intent_id` | VARCHAR(255) | NULLABLE | Stripe Refund Intent ID |
| `refunded_at` | TIMESTAMP | NULLABLE | When refund was processed |
| `refund_reason` | TEXT | NULLABLE | Reason for refund |
| `platform_fee_refunded` | BOOLEAN | DEFAULT FALSE | Whether platform fee was refunded |

**Reason:** System Overview has complex refund logic:
- "If a client cancels before the cancellation deadline, their deposit or full payment is refunded automatically."
- "If the client cancels after the cancellation deadline... £10 Inkjin fee refunded"
- Need to track refund amounts and whether platform fee was refunded

---

### 6. Cancellation Window Tracking

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `cancellation_deadline` | TIMESTAMP | NULLABLE | Calculated deadline based on artist's cancellation window |
| `cancellation_window_hours` | INTEGER | NULLABLE | Cancellation window in hours (from artist settings at time of booking) |

**Reason:** System Overview states: "Artist set their own cancellation deadline (e.g. 24h, 48h, 72h)." Need to calculate and store the deadline when booking is created.

---

### 7. Completion & No-Show Tracking

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `completed_at` | TIMESTAMP | NULLABLE | When artist marked booking as completed |
| `completion_notes` | TEXT | NULLABLE | Notes from artist about completion |
| `no_show_marked_at` | TIMESTAMP | NULLABLE | When artist marked customer as no-show |

**Reason:** System Overview mentions:
- "After appointment ends, artist marks as completed"
- "If the user does not show for the appointment, the artist can mark the session as a 'no-show.'"

---

### 8. Booking History/Actions

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `action_history` | JSON | NULLABLE | Array of actions taken on booking (cancellations, reschedules, status changes, etc.) |

**Reason:** System Overview states: "For every booking the artist should be able to see... A complete history of actions (cancelation, reschedule etc)"

**Example JSON:**
```json
[
  {
    "action": "created",
    "user_id": 1,
    "user_type": "customer",
    "timestamp": "2025-11-25 10:00:00",
    "notes": "Booking created"
  },
  {
    "action": "rescheduled",
    "user_id": 1,
    "user_type": "customer",
    "timestamp": "2025-11-26 14:30:00",
    "old_date": "2025-12-01",
    "new_date": "2025-12-05",
    "notes": "Client requested reschedule"
  }
]
```

---

## Updated Migration Structure

### New Fields to Add

```php
// Booking Type & Custom Details
$table->enum('booking_type', ['custom', 'flash'])->default('flash')->after('tattoo_id');
$table->json('custom_tattoo_details')->nullable()->after('booking_type');

// Consultation Appointment
$table->boolean('has_consultation')->default(false)->after('end_time_utc');
$table->date('consultation_date')->nullable()->after('has_consultation');
$table->time('consultation_start_time_utc')->nullable()->after('consultation_date');
$table->time('consultation_end_time_utc')->nullable()->after('consultation_start_time_utc');
$table->boolean('consultation_completed')->default(false)->after('consultation_end_time_utc');

// Reschedule Tracking
$table->integer('reschedule_count')->default(0)->after('rescheduled_at');
$table->integer('reschedule_limit')->nullable()->after('reschedule_count');

// Payout Tracking
$table->boolean('deposit_released')->default(false)->after('total_amount_paid');
$table->timestamp('deposit_released_at')->nullable()->after('deposit_released');
$table->boolean('remaining_amount_released')->default(false)->after('deposit_released_at');
$table->timestamp('remaining_amount_released_at')->nullable()->after('remaining_amount_released');
$table->string('completion_code', 255)->nullable()->unique()->after('remaining_amount_released_at');
$table->timestamp('completion_code_entered_at')->nullable()->after('completion_code');

// Refund Tracking
$table->decimal('refund_amount', 10, 2)->default(0.00)->after('completion_code_entered_at');
$table->string('refund_intent_id', 255)->nullable()->after('refund_amount');
$table->timestamp('refunded_at')->nullable()->after('refund_intent_id');
$table->text('refund_reason')->nullable()->after('refunded_at');
$table->boolean('platform_fee_refunded')->default(false)->after('refund_reason');

// Cancellation Window
$table->timestamp('cancellation_deadline')->nullable()->after('platform_fee_refunded');
$table->integer('cancellation_window_hours')->nullable()->after('cancellation_deadline');

// Completion & No-Show
$table->timestamp('completed_at')->nullable()->after('cancellation_window_hours');
$table->text('completion_notes')->nullable()->after('completed_at');
$table->timestamp('no_show_marked_at')->nullable()->after('completion_notes');

// Action History
$table->json('action_history')->nullable()->after('no_show_marked_at');
```

### Additional Indexes

```php
$table->index('booking_type');
$table->index('deposit_released');
$table->index('remaining_amount_released');
$table->index('completion_code');
$table->index('cancellation_deadline');
$table->index('consultation_date');
```

---

## Updated Status Enum

The current status enum is good, but we should ensure it covers all cases:

```php
$table->enum('status', [
    'pending',        // Booking created, payment pending
    'confirmed',      // Payment received, booking confirmed
    'cancelled',      // Booking cancelled (by user or artist)
    'completed',      // Appointment completed successfully
    'no_show',        // Customer didn't show up
    'rescheduled',    // Booking was rescheduled (original booking)
])->default('pending');
```

**Note:** Status enum is already correct, no changes needed.

---

## Custom Tattoo Details JSON Structure

For `custom_tattoo_details` field:

```json
{
  "style": "Old School",
  "size": "Upper arm, half sleeve",
  "color": "Black and Gray",
  "placement": "Right arm",
  "description": "I want a traditional rose with clock elements",
  "image_references": [
    "https://example.com/reference1.jpg",
    "https://example.com/reference2.jpg"
  ],
  "estimated_hours": 6,
  "estimated_price": 800,
  "requires_consultation": true
}
```

---

## Action History JSON Structure

For `action_history` field:

```json
[
  {
    "action": "created",
    "user_id": 1,
    "user_type": "customer",
    "timestamp": "2025-11-25 10:00:00",
    "notes": "Booking created via website"
  },
  {
    "action": "payment_received",
    "user_id": 1,
    "user_type": "system",
    "timestamp": "2025-11-25 10:05:00",
    "amount": 210.00,
    "payment_intent_id": "pi_123456"
  },
  {
    "action": "rescheduled",
    "user_id": 1,
    "user_type": "customer",
    "timestamp": "2025-11-26 14:30:00",
    "old_date": "2025-12-01",
    "old_time": "10:00:00",
    "new_date": "2025-12-05",
    "new_time": "14:00:00",
    "reason": "Client requested different date"
  },
  {
    "action": "cancelled",
    "user_id": 2,
    "user_type": "artist",
    "timestamp": "2025-11-27 09:00:00",
    "reason": "Artist emergency",
    "refund_processed": true,
    "refund_amount": 210.00
  }
]
```

---

## Summary of Changes

### Fields to Add: **23 new fields**

1. **Booking Type & Custom Details** (2 fields)
   - `booking_type` - ENUM
   - `custom_tattoo_details` - JSON

2. **Consultation** (5 fields)
   - `has_consultation` - BOOLEAN
   - `consultation_date` - DATE
   - `consultation_start_time_utc` - TIME
   - `consultation_end_time_utc` - TIME
   - `consultation_completed` - BOOLEAN

3. **Reschedule Tracking** (2 fields)
   - `reschedule_count` - INTEGER
   - `reschedule_limit` - INTEGER

4. **Payout Tracking** (5 fields)
   - `deposit_released` - BOOLEAN
   - `deposit_released_at` - TIMESTAMP
   - `remaining_amount_released` - BOOLEAN
   - `remaining_amount_released_at` - TIMESTAMP
   - `completion_code` - VARCHAR(255)
   - `completion_code_entered_at` - TIMESTAMP

5. **Refund Tracking** (5 fields)
   - `refund_amount` - DECIMAL(10,2)
   - `refund_intent_id` - VARCHAR(255)
   - `refunded_at` - TIMESTAMP
   - `refund_reason` - TEXT
   - `platform_fee_refunded` - BOOLEAN

6. **Cancellation Window** (2 fields)
   - `cancellation_deadline` - TIMESTAMP
   - `cancellation_window_hours` - INTEGER

7. **Completion & No-Show** (3 fields)
   - `completed_at` - TIMESTAMP
   - `completion_notes` - TEXT
   - `no_show_marked_at` - TIMESTAMP

8. **Action History** (1 field)
   - `action_history` - JSON

### Indexes to Add: **6 new indexes**
- `booking_type`
- `deposit_released`
- `remaining_amount_released`
- `completion_code`
- `cancellation_deadline`
- `consultation_date`

---

## Implementation Priority

### High Priority (Core Features)
1. ✅ Booking Type & Custom Details
2. ✅ Consultation Appointment
3. ✅ Reschedule Count & Limit
4. ✅ Cancellation Deadline

### Medium Priority (Payment Features)
5. ✅ Payout Tracking
6. ✅ Refund Tracking

### Low Priority (Tracking & History)
7. ✅ Completion & No-Show Timestamps
8. ✅ Action History

---

## Notes

- All new fields are nullable or have defaults to maintain backward compatibility
- JSON fields allow flexible data storage without schema changes
- Timestamps help track when events occurred for reporting
- Indexes improve query performance for common lookups
- Foreign keys maintain data integrity

