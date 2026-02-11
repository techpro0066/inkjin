# Rescheduling Feature - Complete Implementation Guide

**Last Updated:** January 2025  
**Status:** Implementation Guide  
**Priority:** High

---

## Table of Contents

1. [Overview](#overview)
2. [Business Rules](#business-rules)
3. [Database Schema](#database-schema)
4. [Implementation Flow](#implementation-flow)
5. [API Endpoints](#api-endpoints)
6. [Service Layer](#service-layer)
7. [Controller Implementation](#controller-implementation)
8. [Views & UI](#views--ui)
9. [Email Notifications](#email-notifications)
10. [Google Calendar Integration](#google-calendar-integration)
11. [Testing Checklist](#testing-checklist)
12. [Edge Cases](#edge-cases)

---

## Overview

The rescheduling feature allows both clients and artists to change booking dates/times with specific rules:

- **Artist Configuration:** Artists set reschedule limits (never, once, twice, unlimited)
- **Client Rescheduling:** Clients can reschedule within their limit and before cancellation deadline
- **Artist Rescheduling:** Artists can request reschedule at any time (doesn't count against client limit)
- **Deadline Enforcement:** Rescheduling after deadline or beyond limit = cancellation

---

## Business Rules

### Rule 1: Artist Reschedule Policy

Artists configure their reschedule policy in `user_details.reschedule_times`:
- `never` - Clients cannot reschedule
- `once` - Clients can reschedule once per booking
- `twice` - Clients can reschedule twice per booking
- `unlimited` - Clients can reschedule unlimited times

**Storage:** `user_details.reschedule_times` (string)

### Rule 2: Client Rescheduling Eligibility

A client can reschedule if **ALL** of the following are true:
1. ✅ Booking status is `confirmed`
2. ✅ Current time is **before** cancellation deadline
3. ✅ Reschedule count < reschedule limit (or limit is `unlimited`)
4. ✅ Artist's policy is not `never`

**Cancellation Deadline Calculation:**
```
cancellation_deadline = booking_date + start_time_utc - cancellation_window_hours
```

**Example:**
- Booking: January 20, 2025 at 2:00 PM
- Cancellation window: 72 hours
- Deadline: January 17, 2025 at 2:00 PM

### Rule 3: Client Rescheduling After Deadline or Beyond Limit

If client tries to reschedule:
- ❌ **After cancellation deadline** → Treat as cancellation
- ❌ **Beyond allowed limit** → Treat as cancellation

**Action:** Redirect to cancellation flow with appropriate message.

### Rule 4: Artist-Initiated Rescheduling

- ✅ Artist can request reschedule at **any time** (no deadline restrictions)
- ✅ Artist-requested reschedules **do NOT count** against client's reschedule limit
- ✅ Client must select new date/time (artist doesn't select for client)
- ✅ If client declines artist's request → Treat as cancellation

### Rule 5: Reschedule Count Tracking

- ✅ Track `reschedule_count` per booking
- ✅ Increment only for **client-initiated** reschedules
- ✅ Do NOT increment for artist-requested reschedules
- ✅ Reset count when booking is cancelled and rebooked

---

## Database Schema

### Existing Fields (Already in `bookings` table)

```php
// Rescheduling fields (already exist)
$table->foreignId('rescheduled_from_booking_id')->nullable();
$table->foreignId('rescheduled_by')->nullable();
$table->timestamp('rescheduled_at')->nullable();
$table->text('reschedule_reason')->nullable();
$table->integer('reschedule_count')->default(0);
$table->integer('reschedule_limit')->nullable();
```

### Additional Fields (From Migration: `2025_12_16_105721_add_reschedule_status_fields_to_bookings_table.php`)

```php
$table->enum('reschedule_status', ['pending', 'accepted', 'declined', 'completed'])->nullable();
$table->enum('reschedule_requested_by', ['client', 'artist'])->nullable();
```

### Field Usage

| Field | Purpose | Values |
|-------|---------|--------|
| `reschedule_count` | Track number of client-initiated reschedules | Integer (0, 1, 2, ...) |
| `reschedule_limit` | Max reschedules allowed (from artist's policy) | Integer or null (unlimited) |
| `reschedule_status` | Current status of reschedule request | `pending`, `accepted`, `declined`, `completed` |
| `reschedule_requested_by` | Who initiated the reschedule | `client`, `artist` |
| `rescheduled_from_booking_id` | Link to original booking (if new booking created) | Foreign key or null |
| `rescheduled_by` | User ID who rescheduled | Foreign key or null |
| `rescheduled_at` | Timestamp of reschedule | DateTime or null |
| `reschedule_reason` | Optional reason for reschedule | Text or null |

### Action History Structure

Add to `action_history` JSON array:

```json
{
  "action": "reschedule_requested",
  "user_id": 123,
  "user_type": "artist",
  "timestamp": "2025-01-15 10:30:00",
  "reason": "Emergency conflict",
  "old_date": "2025-01-20",
  "old_time": "14:00:00",
  "status": "pending"
}
```

```json
{
  "action": "reschedule_completed",
  "user_id": 456,
  "user_type": "client",
  "timestamp": "2025-01-15 11:00:00",
  "old_date": "2025-01-20",
  "old_time": "14:00:00",
  "new_date": "2025-01-25",
  "new_time": "10:00:00",
  "reschedule_count": 1
}
```

---

## Implementation Flow

### Flow 1: Client-Initiated Reschedule (Within Limit & Before Deadline)

```
┌─────────────────────────────────────────────────────────────┐
│           CLIENT-INITIATED RESCHEDULE FLOW                   │
└─────────────────────────────────────────────────────────────┘

[Step 1: Client Clicks "Reschedule"]
    │
    ├─→ Check eligibility via API
    │   │
    │   ├─→ GET /api/bookings/{id}/can-reschedule
    │   │
    │   └─→ Response:
    │       {
    │         "can_reschedule": true,
    │         "reschedule_count": 0,
    │         "reschedule_limit": 1,
    │         "deadline": "2025-01-17 14:00:00",
    │         "deadline_passed": false
    │       }
    │
    └─→ If eligible → Show reschedule page
         │
         ▼
[Step 2: Select New Date/Time]
    │
    ├─→ GET /bookings/{id}/reschedule
    │   │
    │   ├─→ Display current booking details
    │   ├─→ Display calendar with available slots
    │   └─→ Show reschedule count and limit
    │
    ├─→ Client selects new date/time
    └─→ Client clicks "Confirm Reschedule"
         │
         ▼
[Step 3: Process Reschedule]
    │
    ├─→ POST /api/bookings/{id}/reschedule
    │   │
    │   ├─→ Validate new date/time is available
    │   ├─→ Update booking:
    │   │   │
    │   │   ├─→ booking_date = new_date
    │   │   ├─→ start_time_utc = new_start_time
    │   │   ├─→ end_time_utc = new_end_time
    │   │   ├─→ reschedule_count = reschedule_count + 1
    │   │   ├─→ rescheduled_by = client_user_id
    │   │   ├─→ rescheduled_at = now()
    │   │   ├─→ reschedule_status = 'completed'
    │   │   ├─→ reschedule_requested_by = 'client'
    │   │   └─→ action_history = [...previous, new_entry]
    │   │
    │   ├─→ Update Google Calendar event (if exists)
    │   ├─→ Send confirmation emails
    │   └─→ Return success
         │
         ▼
    [RESCHEDULE COMPLETE]
```

### Flow 2: Client Reschedule After Deadline or Beyond Limit

```
┌─────────────────────────────────────────────────────────────┐
│     CLIENT RESCHEDULE - DEADLINE/LIMIT EXCEEDED             │
└─────────────────────────────────────────────────────────────┘

[Step 1: Client Clicks "Reschedule"]
    │
    ├─→ Check eligibility via API
    │   │
    │   └─→ Response:
    │       {
    │         "can_reschedule": false,
    │         "reason": "deadline_passed" | "limit_exceeded",
    │         "message": "Cannot reschedule. This will be treated as cancellation.",
    │         "deadline": "2025-01-17 14:00:00",
    │         "deadline_passed": true,
    │         "reschedule_count": 1,
    │         "reschedule_limit": 1
    │       }
    │
    └─→ Show message: "Rescheduling is no longer available. Would you like to cancel instead?"
         │
         ▼
[Step 2: Redirect to Cancellation]
    │
    ├─→ Show cancellation modal/page
    ├─→ Apply standard cancellation policy
    └─→ Process cancellation with refund calculation
         │
         ▼
    [TREATED AS CANCELLATION]
```

### Flow 3: Artist-Initiated Reschedule Request

```
┌─────────────────────────────────────────────────────────────┐
│          ARTIST-INITIATED RESCHEDULE FLOW                   │
└─────────────────────────────────────────────────────────────┘

[Step 1: Artist Requests Reschedule]
    │
    ├─→ Artist clicks "Request Reschedule" on booking
    ├─→ Artist enters reason (optional)
    └─→ POST /api/bookings/{id}/artist-request-reschedule
         │
         │ Request Body:
         │ {
         │   "reason": "Emergency conflict - need to reschedule"
         │ }
         │
         ▼
[Step 2: Update Booking Status]
    │
    ├─→ Update booking:
    │   │
    │   ├─→ reschedule_status = 'pending'
    │   ├─→ reschedule_requested_by = 'artist'
    │   ├─→ reschedule_reason = artist_reason
    │   └─→ action_history = [...previous, new_entry]
    │
    └─→ Send email to client
         │
         ▼
[Step 3: Client Receives Notification]
    │
    ├─→ Email: "Artist has requested to reschedule your booking"
    ├─→ Email includes link: /bookings/{id}/reschedule
    └─→ Client clicks link
         │
         ▼
[Step 4: Client Selects New Date/Time]
    │
    ├─→ GET /bookings/{id}/reschedule
    │   │
    │   ├─→ Show message: "Artist has requested to reschedule"
    │   ├─→ Show artist's reason
    │   ├─→ Display calendar with available slots
    │   └─→ Client selects new date/time
    │
    ├─→ Client clicks "Confirm New Time"
    └─→ POST /api/bookings/{id}/reschedule
         │
         │ Request Body:
         │ {
         │   "new_date": "2025-01-25",
         │   "new_start_time_utc": "10:00:00",
         │   "new_end_time_utc": "14:00:00"
         │ }
         │
         ▼
[Step 5: Process Reschedule]
    │
    ├─→ Validate new date/time is available
    ├─→ Update booking:
    │   │
    │   ├─→ booking_date = new_date
    │   ├─→ start_time_utc = new_start_time
    │   ├─→ end_time_utc = new_end_time
    │   ├─→ reschedule_status = 'completed'
    │   ├─→ rescheduled_by = client_user_id (client selects, but artist requested)
    │   ├─→ rescheduled_at = now()
    │   ├─→ reschedule_count = reschedule_count (NOT incremented)
    │   └─→ action_history = [...previous, new_entry]
    │
    ├─→ Update Google Calendar event
    ├─→ Send confirmation emails to both parties
    └─→ Return success
         │
         ▼
    [RESCHEDULE COMPLETE]
```

### Flow 4: Client Declines Artist's Reschedule Request

```
┌─────────────────────────────────────────────────────────────┐
│      CLIENT DECLINES ARTIST RESCHEDULE REQUEST               │
└─────────────────────────────────────────────────────────────┘

[Step 1: Client Views Reschedule Request]
    │
    ├─→ GET /bookings/{id}/reschedule
    ├─→ Show artist's request and reason
    └─→ Show "Accept" and "Decline" buttons
         │
         ▼
[Step 2: Client Clicks "Decline"]
    │
    ├─→ POST /api/bookings/{id}/decline-reschedule
    │   │
    │   └─→ Request Body:
    │       {
    │         "reason": "Cannot accommodate new time"
    │       }
    │
    └─→ Update booking:
         │
         ├─→ reschedule_status = 'declined'
         ├─→ action_history = [...previous, decline_entry]
         └─→ Send notification to artist
              │
              ▼
[Step 3: Treat as Cancellation]
    │
    ├─→ Show message: "Since you declined the reschedule, your booking will be cancelled."
    ├─→ Redirect to cancellation flow
    └─→ Process cancellation with full refund (artist cancelled)
         │
         ▼
    [BOOKING CANCELLED]
```

---

## API Endpoints

### 1. Check Reschedule Eligibility

**Endpoint:** `GET /api/bookings/{id}/can-reschedule`

**Authentication:** Required (client or artist)

**Response:**
```json
{
  "success": true,
  "data": {
    "can_reschedule": true,
    "reschedule_count": 0,
    "reschedule_limit": 1,
    "limit_type": "once",
    "deadline": "2025-01-17 14:00:00",
    "deadline_passed": false,
    "hours_until_deadline": 48,
    "message": "You can reschedule this booking 1 more time(s)"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "data": {
    "can_reschedule": false,
    "reason": "deadline_passed",
    "message": "Cannot reschedule. The cancellation deadline has passed. This will be treated as cancellation.",
    "deadline": "2025-01-17 14:00:00",
    "deadline_passed": true
  }
}
```

### 2. Artist Request Reschedule

**Endpoint:** `POST /api/bookings/{id}/artist-request-reschedule`

**Authentication:** Required (artist only)

**Request Body:**
```json
{
  "reason": "Emergency conflict - need to reschedule"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reschedule request sent to client",
  "data": {
    "booking_id": 123,
    "reschedule_status": "pending",
    "reschedule_requested_by": "artist"
  }
}
```

### 3. Client Reschedule (Select New Date/Time)

**Endpoint:** `POST /api/bookings/{id}/reschedule`

**Authentication:** Required (client or artist)

**Request Body:**
```json
{
  "new_date": "2025-01-25",
  "new_start_time_utc": "10:00:00",
  "new_end_time_utc": "14:00:00",
  "reason": "Personal conflict" // Optional
}
```

**Response:**
```json
{
  "success": true,
  "message": "Booking rescheduled successfully",
  "data": {
    "booking_id": 123,
    "old_date": "2025-01-20",
    "old_time": "14:00:00",
    "new_date": "2025-01-25",
    "new_time": "10:00:00",
    "reschedule_count": 1,
    "reschedule_status": "completed"
  }
}
```

### 4. Client Decline Artist's Reschedule Request

**Endpoint:** `POST /api/bookings/{id}/decline-reschedule`

**Authentication:** Required (client only)

**Request Body:**
```json
{
  "reason": "Cannot accommodate new time"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reschedule request declined. Booking will be cancelled.",
  "data": {
    "booking_id": 123,
    "reschedule_status": "declined",
    "cancellation_initiated": true
  }
}
```

### 5. Get Reschedule Page

**Endpoint:** `GET /bookings/{id}/reschedule`

**Authentication:** Required (client or artist)

**Response:** HTML view with calendar

---

## Service Layer

### Create: `app/Services/ReschedulingService.php`

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReschedulingService
{
    /**
     * Check if client can reschedule a booking
     */
    public function canReschedule(Booking $booking, int $userId): array
    {
        // Get artist's preferences
        $artistDetail = $booking->artist->userDetail;
        $reschedulePolicy = $artistDetail->reschedule_times ?? 'never';
        $cancellationWindow = $this->parseCancellationWindow($artistDetail->cancellation_window ?? '24');
        
        // Check if policy allows rescheduling
        if ($reschedulePolicy === 'never') {
            return [
                'can_reschedule' => false,
                'reason' => 'policy_never',
                'message' => 'Rescheduling is not allowed for this artist.',
            ];
        }
        
        // Calculate cancellation deadline
        $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time_utc);
        $cancellationDeadline = $bookingDateTime->copy()->subHours($cancellationWindow);
        $now = now();
        
        // Check if deadline has passed
        if ($now->gte($cancellationDeadline)) {
            return [
                'can_reschedule' => false,
                'reason' => 'deadline_passed',
                'message' => 'Cannot reschedule. The cancellation deadline has passed. This will be treated as cancellation.',
                'deadline' => $cancellationDeadline->toDateTimeString(),
                'deadline_passed' => true,
            ];
        }
        
        // Check reschedule limit
        $rescheduleLimit = $this->convertLimitToInteger($reschedulePolicy);
        $currentCount = $booking->reschedule_count ?? 0;
        
        if ($rescheduleLimit !== null && $currentCount >= $rescheduleLimit) {
            return [
                'can_reschedule' => false,
                'reason' => 'limit_exceeded',
                'message' => "Cannot reschedule. You have already rescheduled {$currentCount} time(s). Maximum allowed: {$rescheduleLimit}.",
                'reschedule_count' => $currentCount,
                'reschedule_limit' => $rescheduleLimit,
            ];
        }
        
        // All checks passed
        $remainingReschedules = $rescheduleLimit === null ? 'unlimited' : ($rescheduleLimit - $currentCount);
        
        return [
            'can_reschedule' => true,
            'reschedule_count' => $currentCount,
            'reschedule_limit' => $rescheduleLimit,
            'limit_type' => $reschedulePolicy,
            'deadline' => $cancellationDeadline->toDateTimeString(),
            'deadline_passed' => false,
            'hours_until_deadline' => $now->diffInHours($cancellationDeadline),
            'remaining_reschedules' => $remainingReschedules,
            'message' => $rescheduleLimit === null 
                ? 'You can reschedule this booking unlimited times.'
                : "You can reschedule this booking {$remainingReschedules} more time(s).",
        ];
    }
    
    /**
     * Process client-initiated reschedule
     */
    public function processClientReschedule(
        Booking $booking,
        string $newDate,
        string $newStartTimeUtc,
        string $newEndTimeUtc,
        ?string $reason = null
    ): array {
        // Validate eligibility
        $eligibility = $this->canReschedule($booking, $booking->user_id);
        
        if (!$eligibility['can_reschedule']) {
            throw new \Exception($eligibility['message']);
        }
        
        // Store old values
        $oldDate = $booking->booking_date->format('Y-m-d');
        $oldStartTime = $booking->start_time_utc;
        $oldEndTime = $booking->end_time_utc;
        
        // Update booking
        $booking->update([
            'booking_date' => $newDate,
            'start_time_utc' => $newStartTimeUtc,
            'end_time_utc' => $newEndTimeUtc,
            'reschedule_count' => ($booking->reschedule_count ?? 0) + 1,
            'rescheduled_by' => $booking->user_id,
            'rescheduled_at' => now(),
            'reschedule_status' => 'completed',
            'reschedule_requested_by' => 'client',
            'reschedule_reason' => $reason,
            'action_history' => array_merge(
                $booking->action_history ?? [],
                [[
                    'action' => 'reschedule_completed',
                    'user_id' => $booking->user_id,
                    'user_type' => 'client',
                    'timestamp' => now()->toDateTimeString(),
                    'old_date' => $oldDate,
                    'old_time' => $oldStartTime,
                    'new_date' => $newDate,
                    'new_time' => $newStartTimeUtc,
                    'reschedule_count' => $booking->reschedule_count + 1,
                    'reason' => $reason,
                ]]
            ),
        ]);
        
        return [
            'booking_id' => $booking->id,
            'old_date' => $oldDate,
            'old_time' => $oldStartTime,
            'new_date' => $newDate,
            'new_time' => $newStartTimeUtc,
            'reschedule_count' => $booking->reschedule_count,
            'reschedule_status' => 'completed',
        ];
    }
    
    /**
     * Process artist-initiated reschedule request
     */
    public function processArtistRescheduleRequest(Booking $booking, ?string $reason = null): array
    {
        // Update booking status
        $booking->update([
            'reschedule_status' => 'pending',
            'reschedule_requested_by' => 'artist',
            'reschedule_reason' => $reason,
            'action_history' => array_merge(
                $booking->action_history ?? [],
                [[
                    'action' => 'reschedule_requested',
                    'user_id' => $booking->artist_user_id,
                    'user_type' => 'artist',
                    'timestamp' => now()->toDateTimeString(),
                    'reason' => $reason,
                    'old_date' => $booking->booking_date->format('Y-m-d'),
                    'old_time' => $booking->start_time_utc,
                    'status' => 'pending',
                ]]
            ),
        ]);
        
        return [
            'booking_id' => $booking->id,
            'reschedule_status' => 'pending',
            'reschedule_requested_by' => 'artist',
        ];
    }
    
    /**
     * Process client's response to artist's reschedule request
     */
    public function processArtistRescheduleResponse(
        Booking $booking,
        string $newDate,
        string $newStartTimeUtc,
        string $newEndTimeUtc
    ): array {
        // Check if this is an artist-requested reschedule
        if ($booking->reschedule_status !== 'pending' || $booking->reschedule_requested_by !== 'artist') {
            throw new \Exception('This booking does not have a pending artist reschedule request.');
        }
        
        // Store old values
        $oldDate = $booking->booking_date->format('Y-m-d');
        $oldStartTime = $booking->start_time_utc;
        $oldEndTime = $booking->end_time_utc;
        
        // Update booking (do NOT increment reschedule_count)
        $booking->update([
            'booking_date' => $newDate,
            'start_time_utc' => $newStartTimeUtc,
            'end_time_utc' => $newEndTimeUtc,
            'reschedule_status' => 'completed',
            'rescheduled_by' => $booking->user_id, // Client selects, but artist requested
            'rescheduled_at' => now(),
            'reschedule_count' => $booking->reschedule_count, // Keep same count
            'action_history' => array_merge(
                $booking->action_history ?? [],
                [[
                    'action' => 'reschedule_completed',
                    'user_id' => $booking->user_id,
                    'user_type' => 'client',
                    'timestamp' => now()->toDateTimeString(),
                    'old_date' => $oldDate,
                    'old_time' => $oldStartTime,
                    'new_date' => $newDate,
                    'new_time' => $newStartTimeUtc,
                    'reschedule_count' => $booking->reschedule_count, // Not incremented
                    'requested_by' => 'artist',
                ]]
            ),
        ]);
        
        return [
            'booking_id' => $booking->id,
            'old_date' => $oldDate,
            'old_time' => $oldStartTime,
            'new_date' => $newDate,
            'new_time' => $newStartTimeUtc,
            'reschedule_count' => $booking->reschedule_count,
            'reschedule_status' => 'completed',
        ];
    }
    
    /**
     * Process client declining artist's reschedule request
     */
    public function processDeclineReschedule(Booking $booking, ?string $reason = null): array
    {
        // Update booking
        $booking->update([
            'reschedule_status' => 'declined',
            'action_history' => array_merge(
                $booking->action_history ?? [],
                [[
                    'action' => 'reschedule_declined',
                    'user_id' => $booking->user_id,
                    'user_type' => 'client',
                    'timestamp' => now()->toDateTimeString(),
                    'reason' => $reason,
                ]]
            ),
        ]);
        
        return [
            'booking_id' => $booking->id,
            'reschedule_status' => 'declined',
            'cancellation_initiated' => true,
        ];
    }
    
    /**
     * Convert reschedule policy to integer limit
     */
    private function convertLimitToInteger(string $policy): ?int
    {
        return match($policy) {
            'never' => 0,
            'once' => 1,
            'twice' => 2,
            'unlimited' => null,
            default => 0,
        };
    }
    
    /**
     * Parse cancellation window string to hours
     */
    private function parseCancellationWindow(string $window): int
    {
        // Handle formats like "24", "48h", "72 hours", "3 days"
        $window = strtolower(trim($window));
        
        // Extract number
        preg_match('/(\d+)/', $window, $matches);
        $number = (int)($matches[1] ?? 24);
        
        // Check for days
        if (strpos($window, 'day') !== false) {
            return $number * 24;
        }
        
        // Default to hours
        return $number;
    }
}
```

---

## Controller Implementation

### Create: `app/Http/Controllers/ReschedulingController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\ReschedulingService;
use App\Http\Controllers\GoogleCalendarController;
use App\Mail\RescheduleRequestMail;
use App\Mail\RescheduleConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ReschedulingController extends Controller
{
    protected $reschedulingService;
    
    public function __construct(ReschedulingService $reschedulingService)
    {
        $this->reschedulingService = $reschedulingService;
    }
    
    /**
     * Check if booking can be rescheduled
     */
    public function checkCanReschedule($id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $user = Auth::user();
            
            // Check authorization
            if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to booking',
                ], 403);
            }
            
            // Only clients can check their own reschedule eligibility
            if ($booking->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only clients can check reschedule eligibility',
                ], 403);
            }
            
            $eligibility = $this->reschedulingService->canReschedule($booking, $user->id);
            
            return response()->json([
                'success' => true,
                'data' => $eligibility,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check reschedule eligibility', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check reschedule eligibility',
            ], 500);
        }
    }
    
    /**
     * Artist requests reschedule
     */
    public function artistRequestReschedule(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:1000',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $booking = Booking::findOrFail($id);
            $user = Auth::user();
            
            // Check authorization (artist only)
            if ($booking->artist_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the artist can request reschedule',
                ], 403);
            }
            
            // Check booking status
            if ($booking->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only confirmed bookings can be rescheduled',
                ], 400);
            }
            
            // Process artist reschedule request
            $result = $this->reschedulingService->processArtistRescheduleRequest(
                $booking,
                $request->reason
            );
            
            // Send email to client
            try {
                Mail::to($booking->user->email)->send(
                    new RescheduleRequestMail($booking, $request->reason)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send reschedule request email', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            Log::info('Artist reschedule request created', [
                'booking_id' => $booking->id,
                'artist_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Reschedule request sent to client',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create artist reschedule request', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reschedule request: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Client reschedules booking (selects new date/time)
     */
    public function reschedule(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'new_date' => 'required|date',
                'new_start_time_utc' => 'required|date_format:H:i:s',
                'new_end_time_utc' => 'required|date_format:H:i:s',
                'reason' => 'nullable|string|max:1000',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $booking = Booking::findOrFail($id);
            $user = Auth::user();
            
            // Check authorization
            if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to booking',
                ], 403);
            }
            
            // Check booking status
            if ($booking->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only confirmed bookings can be rescheduled',
                ], 400);
            }
            
            // Check if this is artist-requested reschedule
            $isArtistRequested = $booking->reschedule_status === 'pending' 
                && $booking->reschedule_requested_by === 'artist';
            
            if ($isArtistRequested) {
                // Process artist-requested reschedule (doesn't count against limit)
                $result = $this->reschedulingService->processArtistRescheduleResponse(
                    $booking,
                    $request->new_date,
                    $request->new_start_time_utc,
                    $request->new_end_time_utc
                );
            } else {
                // Process client-initiated reschedule (counts against limit)
                $result = $this->reschedulingService->processClientReschedule(
                    $booking,
                    $request->new_date,
                    $request->new_start_time_utc,
                    $request->new_end_time_utc,
                    $request->reason
                );
            }
            
            // Update Google Calendar event
            if ($booking->google_calendar_event_id) {
                try {
                    $artistUserDetail = $booking->artist->userDetail;
                    if ($artistUserDetail && $artistUserDetail->google_calendar_token) {
                        GoogleCalendarController::updateCalendarEvent(
                            $artistUserDetail,
                            $booking->google_calendar_event_id,
                            $request->new_date,
                            $request->new_start_time_utc,
                            $request->new_end_time_utc
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to update Google Calendar event (non-critical)', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Send confirmation emails
            try {
                Mail::to($booking->user->email)->send(
                    new RescheduleConfirmationMail($booking, false)
                );
                Mail::to($booking->artist->email)->send(
                    new RescheduleConfirmationMail($booking, true)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send reschedule confirmation emails', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            Log::info('Booking rescheduled successfully', [
                'booking_id' => $booking->id,
                'old_date' => $result['old_date'],
                'new_date' => $result['new_date'],
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Booking rescheduled successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reschedule booking', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reschedule booking: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Client declines artist's reschedule request
     */
    public function declineReschedule(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:1000',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $booking = Booking::findOrFail($id);
            $user = Auth::user();
            
            // Check authorization (client only)
            if ($booking->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the client can decline reschedule request',
                ], 403);
            }
            
            // Check if there's a pending artist reschedule request
            if ($booking->reschedule_status !== 'pending' || $booking->reschedule_requested_by !== 'artist') {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending artist reschedule request found',
                ], 400);
            }
            
            // Process decline
            $result = $this->reschedulingService->processDeclineReschedule(
                $booking,
                $request->reason
            );
            
            // Send notification to artist
            try {
                Mail::to($booking->artist->email)->send(
                    new RescheduleDeclinedMail($booking, $request->reason)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send reschedule declined email', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Redirect to cancellation flow (treat as artist cancellation)
            // This should be handled by the frontend
            
            Log::info('Reschedule request declined', [
                'booking_id' => $booking->id,
                'client_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Reschedule request declined. Booking will be cancelled.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to decline reschedule request', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to decline reschedule request',
            ], 500);
        }
    }
    
    /**
     * Show reschedule page
     */
    public function showReschedulePage($id)
    {
        $booking = Booking::with(['user', 'artist', 'tattoo'])->findOrFail($id);
        $user = Auth::user();
        
        // Check authorization
        if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
            abort(403, 'Unauthorized access to booking');
        }
        
        // Get eligibility (for client-initiated)
        $eligibility = null;
        if ($booking->user_id === $user->id) {
            $eligibility = $this->reschedulingService->canReschedule($booking, $user->id);
        }
        
        // Check if artist-requested reschedule
        $isArtistRequested = $booking->reschedule_status === 'pending' 
            && $booking->reschedule_requested_by === 'artist';
        
        return view('bookings.reschedule', [
            'booking' => $booking,
            'eligibility' => $eligibility,
            'isArtistRequested' => $isArtistRequested,
        ]);
    }
}
```

---

## Views & UI

### 1. Add Reschedule Button to Bookings List

**File:** `resources/views/bookings/index.blade.php`

Add reschedule button for clients:

```blade
@if($booking->user_id === auth()->id() && $booking->status === 'confirmed')
    <button 
        class="btn btn-sm btn-outline-primary reschedule-btn" 
        data-booking-id="{{ $booking->id }}"
        onclick="checkRescheduleEligibility({{ $booking->id }})"
    >
        <i class="ti ti-calendar-event"></i> Reschedule
    </button>
@endif

@if($booking->artist_user_id === auth()->id() && $booking->status === 'confirmed')
    <button 
        class="btn btn-sm btn-outline-warning request-reschedule-btn" 
        data-booking-id="{{ $booking->id }}"
        onclick="showArtistRescheduleModal({{ $booking->id }})"
    >
        <i class="ti ti-calendar-off"></i> Request Reschedule
    </button>
@endif
```

### 2. Create Reschedule Page

**File:** `resources/views/bookings/reschedule.blade.php`

```blade
@extends('layouts.dashboard_layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <h2>Reschedule Booking</h2>
            
            <!-- Current Booking Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Current Booking Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Date:</strong> {{ $booking->booking_date->format('F d, Y') }}</p>
                    <p><strong>Time:</strong> {{ $booking->booking_time['start'] }} - {{ $booking->booking_time['end'] }}</p>
                    <p><strong>Tattoo:</strong> {{ $booking->tattoo->title ?? 'N/A' }}</p>
                </div>
            </div>
            
            <!-- Artist Request Message -->
            @if($isArtistRequested)
                <div class="alert alert-info">
                    <h5><i class="ti ti-info-circle"></i> Artist Requested Reschedule</h5>
                    <p>{{ $booking->artist->name }} has requested to reschedule this booking.</p>
                    @if($booking->reschedule_reason)
                        <p><strong>Reason:</strong> {{ $booking->reschedule_reason }}</p>
                    @endif
                    <p>Please select a new date and time below.</p>
                </div>
            @endif
            
            <!-- Eligibility Info (Client-initiated) -->
            @if($eligibility && !$isArtistRequested)
                @if($eligibility['can_reschedule'])
                    <div class="alert alert-success">
                        <p>{{ $eligibility['message'] }}</p>
                        <p>Deadline: {{ \Carbon\Carbon::parse($eligibility['deadline'])->format('F d, Y g:i A') }}</p>
                    </div>
                @else
                    <div class="alert alert-danger">
                        <p>{{ $eligibility['message'] }}</p>
                        <a href="{{ route('api.bookings.cancel', $booking->id) }}" class="btn btn-danger">
                            Cancel Booking Instead
                        </a>
                    </div>
                @endif
            @endif
            
            <!-- Reschedule Calendar (Reuse from book.blade.php) -->
            <div class="card">
                <div class="card-header">
                    <h5>Select New Date & Time</h5>
                </div>
                <div class="card-body">
                    <!-- Calendar component (reuse from public/book.blade.php) -->
                    <div id="rescheduleCalendar"></div>
                    <div id="availableSlots" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Reuse calendar logic from public/book.blade.php
// Initialize calendar for rescheduling
</script>
@endsection
```

---

## Email Notifications

### 1. Reschedule Request Email (Artist → Client)

**File:** `app/Mail/RescheduleRequestMail.php`

```php
<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RescheduleRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $reason;

    public function __construct(Booking $booking, ?string $reason = null)
    {
        $this->booking = $booking;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('Reschedule Request for Your Tattoo Booking')
            ->view('emails.reschedule-request');
    }
}
```

**View:** `resources/views/emails/reschedule-request.blade.php`

```blade
<h2>Reschedule Request</h2>

<p>Hello {{ $booking->user->name }},</p>

<p>{{ $booking->artist->name }} has requested to reschedule your tattoo booking.</p>

<p><strong>Current Booking:</strong></p>
<ul>
    <li>Date: {{ $booking->booking_date->format('F d, Y') }}</li>
    <li>Time: {{ $booking->booking_time['start'] }} - {{ $booking->booking_time['end'] }}</li>
</ul>

@if($reason)
    <p><strong>Reason:</strong> {{ $reason }}</p>
@endif

<p>Please select a new date and time by clicking the link below:</p>

<a href="{{ route('bookings.reschedule', $booking->id) }}" class="button">
    Select New Date & Time
</a>

<p>If you cannot accommodate a new time, you may cancel the booking for a full refund.</p>
```

### 2. Reschedule Confirmation Email

**File:** `app/Mail/RescheduleConfirmationMail.php`

```php
<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RescheduleConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $isArtist;

    public function __construct(Booking $booking, bool $isArtist = false)
    {
        $this->booking = $booking;
        $this->isArtist = $isArtist;
    }

    public function build()
    {
        $subject = $this->isArtist 
            ? 'Booking Rescheduled - New Date Confirmed'
            : 'Your Booking Has Been Rescheduled';
            
        return $this->subject($subject)
            ->view('emails.reschedule-confirmation');
    }
}
```

---

## Google Calendar Integration

### Update Calendar Event Method

**File:** `app/Http/Controllers/GoogleCalendarController.php`

Add method:

```php
/**
 * Update Google Calendar event for rescheduled booking
 */
public static function updateCalendarEvent(
    UserDetail $userDetail,
    string $eventId,
    string $newDate,
    string $newStartTimeUtc,
    string $newEndTimeUtc
): bool {
    try {
        $client = self::getGoogleClient($userDetail);
        $service = new \Google_Service_Calendar($client);
        
        // Get existing event
        $event = $service->events->get($userDetail->google_calendar_id, $eventId);
        
        // Update date/time
        $startDateTime = new \Google_Service_Calendar_EventDateTime();
        $startDateTime->setDateTime(
            \Carbon\Carbon::parse($newDate . ' ' . $newStartTimeUtc, 'UTC')
                ->setTimezone($userDetail->timezone ?? 'UTC')
                ->toRfc3339String()
        );
        $startDateTime->setTimeZone($userDetail->timezone ?? 'UTC');
        
        $endDateTime = new \Google_Service_Calendar_EventDateTime();
        $endDateTime->setDateTime(
            \Carbon\Carbon::parse($newDate . ' ' . $newEndTimeUtc, 'UTC')
                ->setTimezone($userDetail->timezone ?? 'UTC')
                ->toRfc3339String()
        );
        $endDateTime->setTimeZone($userDetail->timezone ?? 'UTC');
        
        $event->setStart($startDateTime);
        $event->setEnd($endDateTime);
        
        // Update event
        $service->events->update($userDetail->google_calendar_id, $eventId, $event);
        
        return true;
    } catch (\Exception $e) {
        Log::error('Failed to update Google Calendar event', [
            'event_id' => $eventId,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}
```

---

## Routes

**File:** `routes/web.php`

Add routes:

```php
// Rescheduling routes
Route::middleware(['auth', 'verified', 'onboarding'])->group(function () {
    // Check reschedule eligibility
    Route::get('/api/bookings/{id}/can-reschedule', [ReschedulingController::class, 'checkCanReschedule'])
        ->name('api.bookings.can-reschedule');
    
    // Artist requests reschedule
    Route::post('/api/bookings/{id}/artist-request-reschedule', [ReschedulingController::class, 'artistRequestReschedule'])
        ->name('api.bookings.artist-request-reschedule');
    
    // Client reschedules (selects new date/time)
    Route::post('/api/bookings/{id}/reschedule', [ReschedulingController::class, 'reschedule'])
        ->name('api.bookings.reschedule');
    
    // Client declines artist's reschedule request
    Route::post('/api/bookings/{id}/decline-reschedule', [ReschedulingController::class, 'declineReschedule'])
        ->name('api.bookings.decline-reschedule');
    
    // Reschedule page
    Route::get('/bookings/{id}/reschedule', [ReschedulingController::class, 'showReschedulePage'])
        ->name('bookings.reschedule');
});
```

---

## Testing Checklist

### Client-Initiated Reschedule

- [ ] Client can reschedule within limit (once, twice)
- [ ] Client can reschedule unlimited times (if policy allows)
- [ ] Client cannot reschedule if policy is "never"
- [ ] Client cannot reschedule after cancellation deadline
- [ ] Client cannot reschedule beyond limit
- [ ] Reschedule count increments correctly
- [ ] Booking date/time updates correctly
- [ ] Action history records reschedule
- [ ] Google Calendar event updates
- [ ] Confirmation emails sent

### Artist-Initiated Reschedule

- [ ] Artist can request reschedule at any time
- [ ] Client receives notification email
- [ ] Client can select new date/time
- [ ] Reschedule count does NOT increment
- [ ] Booking updates correctly
- [ ] Confirmation emails sent to both parties
- [ ] Google Calendar event updates

### Edge Cases

- [ ] Reschedule after deadline redirects to cancellation
- [ ] Reschedule beyond limit redirects to cancellation
- [ ] Client can decline artist's reschedule request
- [ ] Declining reschedule triggers cancellation
- [ ] Multiple reschedules tracked correctly
- [ ] Consultation bookings reschedule correctly
- [ ] Combined consultation/tattoo reschedules correctly
- [ ] Separate consultation reschedules correctly

---

## Edge Cases

### Case 1: Reschedule Consultation Booking

If booking has separate consultation:
- Reschedule consultation booking separately
- Reschedule tattoo session booking separately
- Maintain link between consultation and tattoo session

### Case 2: Reschedule with Google Calendar

- Update existing calendar event (don't create new)
- Handle calendar sync errors gracefully
- Don't fail reschedule if calendar update fails

### Case 3: Reschedule Count Tracking

- Track count per booking (not per user)
- Reset count if booking cancelled and rebooked
- Don't increment for artist-requested reschedules

### Case 4: Timezone Handling

- Convert all times to UTC for storage
- Display times in user's timezone
- Handle daylight saving time changes

---

## Implementation Priority

1. **Phase 1 (Core):**
   - Service layer (`ReschedulingService`)
   - Controller (`ReschedulingController`)
   - Basic API endpoints
   - Database fields (already exist)

2. **Phase 2 (UI):**
   - Reschedule page view
   - Reschedule buttons in booking list
   - Calendar integration

3. **Phase 3 (Notifications):**
   - Email notifications
   - Google Calendar updates

4. **Phase 4 (Polish):**
   - Edge case handling
   - Testing
   - Documentation

---

**End of Implementation Guide**
