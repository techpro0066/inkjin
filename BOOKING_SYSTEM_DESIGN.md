# Booking System Database Design

## Overview
This document outlines the database structure and flow for managing tattoo bookings, including booking creation, cancellation (by user or artist), and rescheduling functionality.

---

## Table Structure: `bookings`

### Fields

| Field Name | Type | Constraints | Description |
|------------|------|-------------|-------------|
| `id` | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique booking identifier |
| `user_id` | BIGINT UNSIGNED | FOREIGN KEY → `users.id` | Customer who made the booking |
| `artist_user_id` | BIGINT UNSIGNED | FOREIGN KEY → `users.id` | Artist providing the service |
| `tattoo_id` | BIGINT UNSIGNED | FOREIGN KEY → `inkjin_tattoos.id` | Tattoo being booked |
| `booking_date` | DATE | NOT NULL | Date of the appointment (YYYY-MM-DD) |
| `start_time_utc` | TIME | NOT NULL | Start time in UTC (HH:MM:SS) |
| `end_time_utc` | TIME | NOT NULL | End time in UTC (HH:MM:SS) |
| `timezone` | VARCHAR(255) | DEFAULT 'UTC' | Artist's timezone for display |
| `status` | ENUM | NOT NULL, DEFAULT 'pending' | Booking status (see Status Flow below) |
| `cancelled_by` | BIGINT UNSIGNED | NULLABLE, FOREIGN KEY → `users.id` | User who cancelled (if cancelled) |
| `cancelled_at` | TIMESTAMP | NULLABLE | When the booking was cancelled |
| `cancellation_reason` | TEXT | NULLABLE | Reason for cancellation |
| `rescheduled_from_booking_id` | BIGINT UNSIGNED | NULLABLE, FOREIGN KEY → `bookings.id` | Original booking ID if rescheduled |
| `rescheduled_by` | BIGINT UNSIGNED | NULLABLE, FOREIGN KEY → `users.id` | User who requested reschedule |
| `rescheduled_at` | TIMESTAMP | NULLABLE | When the reschedule was requested |
| `reschedule_reason` | TEXT | NULLABLE | Reason for rescheduling |
| `payment_intent_id` | VARCHAR(255) | NULLABLE, UNIQUE | Stripe Payment Intent ID |
| `payment_status` | ENUM | DEFAULT 'pending' | Payment status: pending, paid, refunded, failed |
| `deposit_amount` | DECIMAL(10,2) | NOT NULL, DEFAULT 0.00 | Deposit amount charged |
| `full_amount_paid` | BOOLEAN | DEFAULT FALSE | Whether full amount was paid (vs deposit only) |
| `platform_fee` | DECIMAL(10,2) | NOT NULL, DEFAULT 0.00 | Platform fee charged |
| `total_amount_paid` | DECIMAL(10,2) | NOT NULL, DEFAULT 0.00 | Total amount paid (deposit + platform fee or full + platform fee) |
| `currency` | VARCHAR(3) | DEFAULT 'USD' | Currency code (USD, EUR, etc.) |
| `questions_answers` | JSON | NULLABLE | JSON object storing question IDs and answers |
| `notes` | TEXT | NULLABLE | Additional notes from artist or customer |
| `reminder_sent_at` | TIMESTAMP | NULLABLE | When reminder email/SMS was sent |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Booking creation timestamp |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

### Indexes

```sql
INDEX idx_user_id (user_id)
INDEX idx_artist_user_id (artist_user_id)
INDEX idx_tattoo_id (tattoo_id)
INDEX idx_booking_date (booking_date)
INDEX idx_status (status)
INDEX idx_payment_intent_id (payment_intent_id)
INDEX idx_cancelled_by (cancelled_by)
INDEX idx_rescheduled_from_booking_id (rescheduled_from_booking_id)
```

---

## Status Flow

### Status Enum Values

- `pending` - Booking created, payment pending
- `confirmed` - Payment received, booking confirmed
- `cancelled` - Booking cancelled (by user or artist)
- `completed` - Appointment completed successfully
- `no_show` - Customer didn't show up
- `rescheduled` - Booking was rescheduled (original booking)

### Status Transitions

```
pending → confirmed (after successful payment)
confirmed → cancelled (by user or artist)
confirmed → completed (after appointment)
confirmed → no_show (if customer doesn't show)
confirmed → rescheduled (if rescheduled)
pending → cancelled (before payment)
```

---

## Relationships

### Foreign Keys

1. **user_id** → `users.id`
   - Customer who made the booking
   - ON DELETE: RESTRICT (prevent deletion if bookings exist)

2. **artist_user_id** → `users.id`
   - Artist providing the service
   - ON DELETE: RESTRICT

3. **tattoo_id** → `inkjin_tattoos.id`
   - Tattoo being booked
   - ON DELETE: RESTRICT

4. **cancelled_by** → `users.id`
   - User who cancelled (can be customer or artist)
   - ON DELETE: SET NULL

5. **rescheduled_from_booking_id** → `bookings.id`
   - Original booking if this is a rescheduled booking
   - ON DELETE: SET NULL

6. **rescheduled_by** → `users.id`
   - User who requested reschedule
   - ON DELETE: SET NULL

---

## Use Cases & Flows

### 1. Create New Booking

**Flow:**
1. Customer selects date, time slot, and answers questions
2. Payment intent created via Stripe
3. Payment processed successfully
4. Booking record created with:
   - `status` = `pending` initially
   - `payment_status` = `paid`
   - `payment_intent_id` = Stripe Payment Intent ID
   - `questions_answers` = JSON of question IDs and answers
   - `deposit_amount` or `full_amount_paid` based on selection
5. Status updated to `confirmed` after payment confirmation

**Example JSON for `questions_answers`:**
```json
{
  "1": "I want a black and gray design",
  "2": "Medium size",
  "3": "https://example.com/image.jpg",
  "4": "Option A"
}
```

---

### 2. User Cancels Booking

**Flow:**
1. User requests cancellation
2. Check cancellation policy (time window, refund eligibility)
3. Update booking:
   - `status` = `cancelled`
   - `cancelled_by` = user_id
   - `cancelled_at` = CURRENT_TIMESTAMP
   - `cancellation_reason` = user-provided reason
4. Process refund if eligible (update `payment_status` = `refunded`)
5. Send confirmation email to user and artist

**Cancellation Rules:**
- Check `cancellation_window` from `user_details` table
- If cancelled within window: Full refund
- If cancelled outside window: No refund or partial refund
- Platform fee may or may not be refunded based on policy

---

### 3. Artist Cancels Booking

**Flow:**
1. Artist requests cancellation
2. Update booking:
   - `status` = `cancelled`
   - `cancelled_by` = artist_user_id
   - `cancelled_at` = CURRENT_TIMESTAMP
   - `cancellation_reason` = artist-provided reason
3. Process full refund to customer
4. Update `payment_status` = `refunded`
5. Send notification to customer with apology

---

### 4. User Reschedules Booking

**Flow:**
1. User requests reschedule
2. Check reschedule policy (`reschedule_times` from `user_details`)
3. User selects new date/time
4. Create new booking record:
   - All same data as original
   - `booking_date`, `start_time_utc`, `end_time_utc` = new values
   - `rescheduled_from_booking_id` = original booking ID
   - `rescheduled_by` = user_id
   - `rescheduled_at` = CURRENT_TIMESTAMP
   - `status` = `confirmed` (payment already made)
5. Update original booking:
   - `status` = `rescheduled`
   - Keep original data for reference
6. Send confirmation to both parties

---

### 5. Artist Reschedules Booking

**Flow:**
1. Artist requests reschedule
2. Artist selects new date/time
3. Create new booking record:
   - All same data as original
   - `booking_date`, `start_time_utc`, `end_time_utc` = new values
   - `rescheduled_from_booking_id` = original booking ID
   - `rescheduled_by` = artist_user_id
   - `rescheduled_at` = CURRENT_TIMESTAMP
   - `status` = `confirmed`
4. Update original booking:
   - `status` = `rescheduled`
5. Send notification to customer with new time

---

### 6. Complete Appointment

**Flow:**
1. After appointment ends, artist marks as completed
2. Update booking:
   - `status` = `completed`
   - `notes` = optional completion notes
3. Send feedback request to customer

---

### 7. No Show

**Flow:**
1. Artist marks customer as no-show
2. Update booking:
   - `status` = `no_show`
   - `notes` = "Customer did not show up"
3. Handle payment (may keep deposit, full refund, etc. based on policy)

---

## Questions Answers JSON Structure

The `questions_answers` field stores answers in JSON format:

```json
{
  "question_id": "answer_value",
  "1": "I want a sleeve tattoo",
  "2": "Large",
  "3": "https://example.com/reference-image.jpg",
  "4": "Option B"
}
```

For image questions, store the file path or URL.
For text questions, store the text answer.
For select/radio questions, store the selected option value.

---

## Migration File Structure

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('artist_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('tattoo_id')->constrained('inkjin_tattoos')->onDelete('restrict');
            
            $table->date('booking_date');
            $table->time('start_time_utc');
            $table->time('end_time_utc');
            $table->string('timezone', 255)->default('UTC');
            
            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled',
                'completed',
                'no_show',
                'rescheduled'
            ])->default('pending');
            
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->foreignId('rescheduled_from_booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->foreignId('rescheduled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rescheduled_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            
            $table->string('payment_intent_id', 255)->nullable()->unique();
            $table->enum('payment_status', [
                'pending',
                'paid',
                'refunded',
                'failed'
            ])->default('pending');
            
            $table->decimal('deposit_amount', 10, 2)->default(0.00);
            $table->boolean('full_amount_paid')->default(false);
            $table->decimal('platform_fee', 10, 2)->default(0.00);
            $table->decimal('total_amount_paid', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            
            $table->json('questions_answers')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('artist_user_id');
            $table->index('tattoo_id');
            $table->index('booking_date');
            $table->index('status');
            $table->index('payment_intent_id');
            $table->index('cancelled_by');
            $table->index('rescheduled_from_booking_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
```

---

## Model Structure

### Booking Model (`app/Models/Booking.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'artist_user_id',
        'tattoo_id',
        'booking_date',
        'start_time_utc',
        'end_time_utc',
        'timezone',
        'status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'rescheduled_from_booking_id',
        'rescheduled_by',
        'rescheduled_at',
        'reschedule_reason',
        'payment_intent_id',
        'payment_status',
        'deposit_amount',
        'full_amount_paid',
        'platform_fee',
        'total_amount_paid',
        'currency',
        'questions_answers',
        'notes',
        'reminder_sent_at',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time_utc' => 'datetime',
        'end_time_utc' => 'datetime',
        'cancelled_at' => 'datetime',
        'rescheduled_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'deposit_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'total_amount_paid' => 'decimal:2',
        'full_amount_paid' => 'boolean',
        'questions_answers' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_user_id');
    }

    public function tattoo(): BelongsTo
    {
        return $this->belongsTo(InkJinTattoo::class, 'tattoo_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'rescheduled_from_booking_id');
    }

    public function rescheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
    }

    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'confirmed')
            ->where('booking_date', '>=', now()->toDateString());
    }

    // Helper methods
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isRescheduled(): bool
    {
        return $this->status === 'rescheduled';
    }

    public function canBeCancelled(): bool
    {
        // Check cancellation window from artist's settings
        // Implementation depends on business logic
        return true; // Placeholder
    }
}
```

---

## Additional Considerations

### 1. Conflict Prevention
- Before creating a booking, check for existing bookings with overlapping time slots for the same artist
- Query: Check if any `confirmed` or `pending` bookings exist for the artist on the same date/time

### 2. Refund Logic
- Store refund transaction IDs if needed
- Consider creating a separate `refunds` table for detailed refund tracking

### 3. Notifications
- Use `reminder_sent_at` to track reminder emails
- Send reminders 24 hours before appointment

### 4. Reporting
- Track booking completion rates
- Track cancellation rates by user/artist
- Track revenue per booking

### 5. Future Enhancements
- Add `rating` and `review` fields for customer feedback
- Add `attachments` JSON field for additional files
- Add `internal_notes` for artist-only notes

---

## Summary

This booking system supports:
- ✅ Creating bookings with payment
- ✅ User cancellation with refund logic
- ✅ Artist cancellation with refund
- ✅ User-initiated rescheduling
- ✅ Artist-initiated rescheduling
- ✅ Tracking payment status
- ✅ Storing question answers
- ✅ Maintaining booking history

The structure is flexible and can be extended as needed.

