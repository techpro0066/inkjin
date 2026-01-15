# Google Meet Implementation - Step-by-Step Code Guide

**Based on Current Codebase**  
**Date:** January 2025

---

## ⚠️ Important Condition

**Google Meet links are ONLY created when:**
- ✅ Artist has `require_consultation = true` in `user_details` table
- ✅ Google Calendar is connected
- ✅ Booking is confirmed

**If `require_consultation = false`:**
- ❌ No Meet link will be generated
- ✅ Booking will proceed normally
- ✅ Calendar event will still be created (for scheduling)
- ✅ Emails will NOT include Meet link section

---

## Quick Overview

We're adding Google Meet link generation to the existing booking flow. The Meet link will be automatically created **only if the artist requires consultations** (`require_consultation = true`). When created, it will be sent to both artist and client via email.

---

## Step 1: Create Database Migration

**File:** `database/migrations/2025_01_XX_HHMMSS_add_google_meet_link_to_bookings_table.php`

**Command to create:**
```bash
php artisan make:migration add_google_meet_link_to_bookings_table
```

**Migration Code:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('google_meet_link', 500)->nullable()->after('google_calendar_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('google_meet_link');
        });
    }
};
```

**Run migration:**
```bash
php artisan migrate
```

---

## Step 2: Update Booking Model

**File:** `app/Models/Booking.php`

**Add to `$fillable` array (around line 63):**
```php
protected $fillable = [
    // ... existing fields ...
    'google_calendar_event_id',
    'google_meet_link',  // ← ADD THIS LINE
];
```

---

## Step 3: Update GoogleCalendarController

**File:** `app/Http/Controllers/GoogleCalendarController.php`

**Modify the `createCalendarEvent()` method (starting at line 287):**

**FIND THIS CODE (around line 396-434):**
```php
// Create Google Calendar event
$event = new \Google_Service_Calendar_Event();
$event->setSummary($eventTitle);
$event->setDescription($description);

// Set start time (in UTC, but specify timezone)
$start = new \Google_Service_Calendar_EventDateTime();
$start->setDateTime($startDateTime->toRfc3339String());
$start->setTimeZone('UTC');
$event->setStart($start);

// Set end time (in UTC, but specify timezone)
$end = new \Google_Service_Calendar_EventDateTime();
$end->setDateTime($endDateTime->toRfc3339String());
$end->setTimeZone('UTC');
$event->setEnd($end);

// Set location (artist's studio address if available)
if ($userDetail->studio_address) {
    $event->setLocation($userDetail->studio_address);
}

// Add customer email as attendee
$attendee = new \Google_Service_Calendar_EventAttendee();
$attendee->setEmail($customer->email);
$attendee->setDisplayName($customer->name);
$event->setAttendees([$attendee]);

// Set reminders (15 minutes before)
$reminder = new \Google_Service_Calendar_EventReminder();
$reminder->setMethod('email');
$reminder->setMinutes(15);
$eventReminders = new \Google_Service_Calendar_EventReminders();
$eventReminders->setUseDefault(false);
$eventReminders->setOverrides([$reminder]);
$event->setReminders($eventReminders);

// Insert event
$createdEvent = $service->events->insert($calendarId, $event);
$eventId = $createdEvent->getId();
```

**REPLACE WITH THIS CODE (includes require_consultation check):**
```php
// Create Google Calendar event
$event = new \Google_Service_Calendar_Event();
$event->setSummary($eventTitle);
$event->setDescription($description);

// Set start time (in UTC, but specify timezone)
$start = new \Google_Service_Calendar_EventDateTime();
$start->setDateTime($startDateTime->toRfc3339String());
$start->setTimeZone('UTC');
$event->setStart($start);

// Set end time (in UTC, but specify timezone)
$end = new \Google_Service_Calendar_EventDateTime();
$end->setDateTime($endDateTime->toRfc3339String());
$end->setTimeZone('UTC');
$event->setEnd($end);

// Set location (artist's studio address if available)
if ($userDetail->studio_address) {
    $event->setLocation($userDetail->studio_address);
}

// Add customer email as attendee
$attendee = new \Google_Service_Calendar_EventAttendee();
$attendee->setEmail($customer->email);
$attendee->setDisplayName($customer->name);
$event->setAttendees([$attendee]);

// Set reminders (15 minutes before)
$reminder = new \Google_Service_Calendar_EventReminder();
$reminder->setMethod('email');
$reminder->setMinutes(15);
$eventReminders = new \Google_Service_Calendar_EventReminders();
$eventReminders->setUseDefault(false);
$eventReminders->setOverrides([$reminder]);
$event->setReminders($eventReminders);

// ADD THIS: Enable Google Meet ONLY if consultation is required
// $requiresConsultation parameter is passed from confirmBooking()
if ($requiresConsultation) {
    $conferenceData = new \Google_Service_Calendar_ConferenceData();
    $createRequest = new \Google_Service_Calendar_CreateConferenceRequest();
    $createRequest->setRequestId(uniqid()); // Unique request ID required
    $conferenceSolutionKey = new \Google_Service_Calendar_ConferenceSolutionKey();
    $conferenceSolutionKey->setType('hangoutsMeet');
    $createRequest->setConferenceSolutionKey($conferenceSolutionKey);
    $conferenceData->setCreateRequest($createRequest);
    $event->setConferenceData($conferenceData);
}

// Insert event (with conferenceDataVersion only if Meet is enabled)
$insertParams = [];
if ($requiresConsultation) {
    $insertParams['conferenceDataVersion'] = 1; // Required to enable Google Meet
}
$createdEvent = $service->events->insert($calendarId, $event, $insertParams);
$eventId = $createdEvent->getId();

// Extract Google Meet link from the created event (only if consultation required)
$meetLink = null;
if ($requiresConsultation && $createdEvent->getConferenceData() && $createdEvent->getConferenceData()->getEntryPoints()) {
    $entryPoints = $createdEvent->getConferenceData()->getEntryPoints();
    if (!empty($entryPoints) && isset($entryPoints[0])) {
        $meetLink = $entryPoints[0]->getUri();
    }
}
```

**ALSO UPDATE THE METHOD SIGNATURE (around line 287):**

**FIND:**
```php
public static function createCalendarEvent($userDetail, $booking)
```

**REPLACE WITH:**
```php
public static function createCalendarEvent($userDetail, $booking, $requiresConsultation = false)
```

**NOW UPDATE THE RETURN STATEMENT (around line 443):**

**FIND:**
```php
return $eventId;
```

**REPLACE WITH:**
```php
// Return both event ID and Meet link (Meet link will be null if consultation not required)
return [
    'event_id' => $eventId,
    'meet_link' => $meetLink // null if requiresConsultation is false
];
```

**ALSO UPDATE THE METHOD DOCUMENTATION (around line 280):**

**FIND:**
```php
/**
 * Create a calendar event for a booking
 * 
 * @param \App\Models\UserDetail $userDetail Artist's user detail
 * @param \App\Models\Booking $booking Booking instance
 * @return string|null Google Calendar event ID or null on failure
 */
```

**REPLACE WITH:**
```php
/**
 * Create a calendar event for a booking with Google Meet link
 * 
 * @param \App\Models\UserDetail $userDetail Artist's user detail
 * @param \App\Models\Booking $booking Booking instance
 * @return array|null Array with 'event_id' and 'meet_link' keys, or null on failure
 */
```

---

## Step 4: Update InkJinController::confirmBooking()

**File:** `app/Http/Controllers/InkJinController.php`

**FIND THIS CODE (around line 1500-1550):**
```php
// Create Google Calendar event for the artist (if calendar is connected)
$calendarEventId = null;
try {
    $artistUserDetail = $artistUser->userDetail;
    if ($artistUserDetail && $artistUserDetail->google_calendar_token && $artistUserDetail->google_calendar_id) {
        $calendarEventId = GoogleCalendarController::createCalendarEvent($artistUserDetail, $booking);
        
        if ($calendarEventId) {
            // Update booking with calendar event ID
            $booking->update(['google_calendar_event_id' => $calendarEventId]);
            
            // Add to action history
            $actionHistory = $booking->action_history ?? []; // Retrieve existing history
            $actionHistory[] = [
                'action' => 'calendar_event_created',
                'user_id' => null,
                'user_type' => 'system',
                'timestamp' => now()->toDateTimeString(),
                'calendar_event_id' => $calendarEventId,
                'notes' => 'Google Calendar event created successfully',
            ];
            $booking->update(['action_history' => $actionHistory]); // Update with new history
            
            Log::info('Google Calendar event created for booking', [
                'booking_id' => $booking->id,
                'event_id' => $calendarEventId,
            ]);
        }
    } else {
        Log::info('Google Calendar not connected for artist, skipping event creation', [
            'booking_id' => $booking->id,
            'artist_user_id' => $artistUser->id,
        ]);
    }
} catch (\Exception $e) {
    // Don't fail the booking if calendar event creation fails
    Log::error('Failed to create Google Calendar event (non-critical)', [
        'booking_id' => $booking->id,
        'error' => $e->getMessage(),
    ]);
}
```

**REPLACE WITH THIS CODE (includes require_consultation check):**
```php
// Create Google Calendar event for the artist (if calendar is connected)
// Only create Meet link if artist requires consultation
$calendarEventId = null;
$meetLink = null;
try {
    $artistUserDetail = $artistUser->userDetail;
    
    // Check if artist requires consultation - only create Meet link if true
    $requiresConsultation = $artistUserDetail && ($artistUserDetail->require_consultation ?? false);
    
    if ($artistUserDetail && $artistUserDetail->google_calendar_token && $artistUserDetail->google_calendar_id) {
        // Always create calendar event (for scheduling), pass require_consultation flag
        $calendarResult = GoogleCalendarController::createCalendarEvent($artistUserDetail, $booking, $requiresConsultation);
        
        if ($calendarResult && isset($calendarResult['event_id'])) {
            $calendarEventId = $calendarResult['event_id'];
            
            // Only store Meet link if consultation is required
            if ($requiresConsultation) {
                $meetLink = $calendarResult['meet_link'] ?? null;
            }
            
            // Update booking with calendar event ID and Meet link (if consultation required)
            $updateData = ['google_calendar_event_id' => $calendarEventId];
            if ($requiresConsultation && $meetLink) {
                $updateData['google_meet_link'] = $meetLink;
            }
            $booking->update($updateData);
            
            // Add to action history
            $actionHistory = $booking->action_history ?? []; // Retrieve existing history
            $historyEntry = [
                'action' => 'calendar_event_created',
                'user_id' => null,
                'user_type' => 'system',
                'timestamp' => now()->toDateTimeString(),
                'calendar_event_id' => $calendarEventId,
                'notes' => 'Google Calendar event created successfully',
            ];
            
            // Add Meet link info only if consultation is required
            if ($requiresConsultation && $meetLink) {
                $historyEntry['meet_link'] = $meetLink;
                $historyEntry['notes'] = 'Google Calendar event and Meet link created successfully';
            }
            
            $actionHistory[] = $historyEntry;
            $booking->update(['action_history' => $actionHistory]); // Update with new history
            
            Log::info('Google Calendar event created for booking', [
                'booking_id' => $booking->id,
                'event_id' => $calendarEventId,
                'requires_consultation' => $requiresConsultation,
                'meet_link' => $meetLink,
            ]);
        }
    } else {
        Log::info('Google Calendar not connected for artist, skipping event creation', [
            'booking_id' => $booking->id,
            'artist_user_id' => $artistUser->id,
        ]);
    }
} catch (\Exception $e) {
    // Don't fail the booking if calendar event creation fails
    Log::error('Failed to create Google Calendar event (non-critical)', [
        'booking_id' => $booking->id,
        'error' => $e->getMessage(),
    ]);
}
```

**IMPORTANT:** Make sure `$meetLink` is available when sending emails. The booking model will have it saved, but you can also pass it directly to the email.

---

## Step 5: Update BookingConfirmationMail

**File:** `app/Mail/BookingConfirmationMail.php`

**FIND THE `getEmailData()` method (around line 62):**

**ADD Meet link to the base data (around line 98-105):**
```php
$baseData = [
    'bookingId' => $booking->id,
    'tattooTitle' => $tattoo->title,
    'bookingDate' => $bookingDate,
    'bookingTime' => $bookingTime,
    'duration' => $duration,
    'currencySymbol' => $currencySymbol,
    'meetLink' => $booking->google_meet_link,  // ← ADD THIS LINE
    'meetingTime' => $bookingDate . ' at ' . $startTime,  // ← ADD THIS LINE
];
```

---

## Step 6: Create/Update Email Templates

### 6.1 Customer Email Template

**File:** `resources/views/emails/booking-confirmation-user.blade.php`

**ADD THIS SECTION** (after booking details, before closing):

```blade
@if(!empty($meetLink))
<div style="background-color: #f8f9fa; padding: 25px; border-radius: 10px; margin: 30px 0; border-left: 4px solid #00832d;">
    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 20px;">
        📹 Video Meeting
    </h3>
    <p style="color: #666; margin-bottom: 15px; line-height: 1.6;">
        Join your 30-minute video consultation meeting with {{ $artistName }}:
    </p>
    <div style="background-color: #ffffff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <p style="margin: 0 0 10px 0; color: #333;">
            <strong>Meeting Time:</strong> {{ $meetingTime }}<br>
            <strong>Duration:</strong> 30 minutes
        </p>
    </div>
    <a href="{{ $meetLink }}" 
       target="_blank"
       style="display: inline-block; background-color: #00832d; color: #ffffff; 
              padding: 14px 28px; text-decoration: none; border-radius: 6px; 
              font-weight: bold; margin-top: 10px; font-size: 16px;">
        🎥 Join Google Meet
    </a>
    <p style="color: #999; font-size: 13px; margin-top: 20px; line-height: 1.5;">
        <strong>Meeting Link:</strong><br>
        <a href="{{ $meetLink }}" style="color: #00832d; word-break: break-all;">
            {{ $meetLink }}
        </a>
    </p>
    <p style="color: #999; font-size: 12px; margin-top: 15px;">
        💡 Tip: Click the link above or copy it to join the meeting at the scheduled time.
    </p>
</div>
@endif
```

### 6.2 Artist Email Template

**File:** `resources/views/emails/booking-confirmation-artist.blade.php`

**ADD THIS SECTION** (similar to customer email):

```blade
@if(!empty($meetLink))
<div style="background-color: #f8f9fa; padding: 25px; border-radius: 10px; margin: 30px 0; border-left: 4px solid #00832d;">
    <h3 style="color: #333; margin: 0 0 15px 0; font-size: 20px;">
        📹 Video Meeting Link
    </h3>
    <p style="color: #666; margin-bottom: 15px; line-height: 1.6;">
        Meeting link for consultation with {{ $customerName }}:
    </p>
    <div style="background-color: #ffffff; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
        <p style="margin: 0 0 10px 0; color: #333;">
            <strong>Customer:</strong> {{ $customerName }} ({{ $customerEmail }})<br>
            <strong>Meeting Time:</strong> {{ $meetingTime }}<br>
            <strong>Duration:</strong> 30 minutes
        </p>
    </div>
    <a href="{{ $meetLink }}" 
       target="_blank"
       style="display: inline-block; background-color: #00832d; color: #ffffff; 
              padding: 14px 28px; text-decoration: none; border-radius: 6px; 
              font-weight: bold; margin-top: 10px; font-size: 16px;">
        🎥 Join Google Meet
    </a>
    <p style="color: #999; font-size: 13px; margin-top: 20px; line-height: 1.5;">
        <strong>Meeting Link:</strong><br>
        <a href="{{ $meetLink }}" style="color: #00832d; word-break: break-all;">
            {{ $meetLink }}
        </a>
    </p>
</div>
@endif
```

---

## Step 7: Update Booking Details View

**File:** `resources/views/public/book.blade.php` (booking details modal)

**FIND the booking details modal** and **ADD Meet link section**:

```blade
@if($booking->google_meet_link ?? null)
<div class="card mb-3 border-success">
    <div class="card-body">
        <h6 class="card-title text-success">
            <i class="ti ti-video me-2"></i>Video Meeting
        </h6>
        <p class="text-muted mb-2">
            Join your 30-minute consultation meeting
        </p>
        <a href="{{ $booking->google_meet_link }}" 
           target="_blank" 
           class="btn btn-success btn-sm">
            <i class="ti ti-external-link me-1"></i>Join Google Meet
        </a>
        <small class="text-muted d-block mt-2">
            <i class="ti ti-clock me-1"></i>
            Meeting scheduled for: 
            {{ \Carbon\Carbon::parse($booking->booking_date)->format('M j, Y') }} 
            at {{ \Carbon\Carbon::parse($booking->start_time_utc)->setTimezone($booking->timezone)->format('g:i A') }}
        </small>
    </div>
</div>
@endif
```

---

## Step 8: Update Bookings Listing Page

**File:** `resources/views/bookings/index.blade.php`

**FIND the booking card/row** and **ADD Meet link column or badge**:

```blade
@if($booking->google_meet_link)
<td>
    <a href="{{ $booking->google_meet_link }}" 
       target="_blank" 
       class="btn btn-sm btn-success">
        <i class="ti ti-video me-1"></i>Join Meet
    </a>
</td>
@else
<td>
    <span class="text-muted">-</span>
</td>
@endif
```

---

## Step 9: Testing Checklist

### Test Scenarios:

1. **✅ Create Booking with Consultation Required**
   - Set artist's `require_consultation` to `true`
   - Create a booking
   - Verify Meet link is generated
   - Check database: `bookings.google_meet_link` should have a URL
   - Verify Meet link format: `https://meet.google.com/xxx-xxxx-xxx`

2. **✅ Create Booking WITHOUT Consultation Required**
   - Set artist's `require_consultation` to `false`
   - Create a booking
   - Verify NO Meet link is generated
   - Check database: `bookings.google_meet_link` should be `NULL`
   - Verify emails do NOT include Meet link section

3. **✅ Email Delivery (Consultation Required)**
   - With `require_consultation = true`
   - Check customer email has Meet link
   - Check artist email has Meet link
   - Verify "Join Google Meet" button works
   - Test Meet link opens correctly

4. **✅ Email Delivery (No Consultation Required)**
   - With `require_consultation = false`
   - Check customer email does NOT have Meet link section
   - Check artist email does NOT have Meet link section

5. **✅ Meet Link Functionality**
   - Click Meet link from email
   - Verify it opens Google Meet
   - Test joining from both customer and artist side

6. **✅ Edge Cases**
   - Test with Calendar NOT connected (should handle gracefully, no Meet link)
   - Test with `require_consultation = false` (no Meet link, booking succeeds)
   - Test if Meet link generation fails (booking should still succeed)
   - Test with expired calendar token (should refresh)

7. **✅ UI Display**
   - With consultation required: Verify Meet link shows in booking details modal
   - Without consultation: Verify Meet link section does NOT show
   - Verify Meet link shows in bookings listing (only if exists)
   - Test mobile responsiveness

---

## Step 10: Error Handling

The code already handles errors gracefully:
- If calendar not connected → Booking still succeeds, no Meet link
- If Meet link generation fails → Booking still succeeds, no Meet link
- Errors are logged but don't block booking creation

**This is correct behavior** - Meet link is a nice-to-have feature, not critical.

---

## Quick Reference

### Database Column
```php
'google_meet_link' => 'string', // nullable, max 500 chars
```

### Meet Link Format
```
https://meet.google.com/xxx-xxxx-xxx
```

### Email Variables Available
```php
$meetLink      // The Google Meet URL
$meetingTime   // Formatted meeting time
$booking       // Booking model instance
```

---

## Troubleshooting

### Issue: Meet link not generated

**Possible causes:**
1. Google Calendar not connected → Check `user_details.google_calendar_token`
2. API error → Check Laravel logs
3. Conference data not enabled → Verify `conferenceDataVersion => 1` is set

**Solution:**
- Check logs: `storage/logs/laravel.log`
- Verify calendar connection in user details
- Test calendar API access

### Issue: Meet link is null

**Check:**
- Event created successfully? (check `google_calendar_event_id`)
- Conference data present in event response?
- Entry points array not empty?

**Solution:**
- Add logging to see what's returned from Google API
- Verify event has `conferenceData` in response

---

## Summary

**Files Modified:**
1. ✅ Migration: `database/migrations/..._add_google_meet_link_to_bookings_table.php`
2. ✅ Model: `app/Models/Booking.php`
3. ✅ Controller: `app/Http/Controllers/GoogleCalendarController.php`
4. ✅ Controller: `app/Http/Controllers/InkJinController.php`
5. ✅ Mail: `app/Mail/BookingConfirmationMail.php`
6. ✅ View: `resources/views/emails/booking-confirmation-user.blade.php`
7. ✅ View: `resources/views/emails/booking-confirmation-artist.blade.php`
8. ✅ View: `resources/views/public/book.blade.php`
9. ✅ View: `resources/views/bookings/index.blade.php`

**Total Changes:** ~200 lines of code added/modified

**Estimated Time:** 2-3 hours

---

**Ready to implement?** Follow the steps in order, test after each step, and you'll have Google Meet integration working! 🚀

