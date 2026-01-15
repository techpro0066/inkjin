# Google Meet Integration Guide - Artist-Client Meeting Feature

**Feature:** Create 30-minute Google Meet meetings between artists and clients  
**Purpose:** Enable video consultations/meetings as part of the booking process  
**Date:** January 2025

---

## Table of Contents

1. [Overview](#overview)
2. [Understanding Google Meet Integration](#understanding-google-meet-integration)
3. [Approach Options](#approach-options)
4. [Recommended Solution](#recommended-solution)
5. [Implementation Plan](#implementation-plan)
6. [Database Changes](#database-changes)
7. [Code Implementation](#code-implementation)
8. [Email Templates](#email-templates)
9. [Testing Checklist](#testing-checklist)
10. [Future Enhancements](#future-enhancements)

---

## 1. Overview

### What We're Building

A feature that automatically creates a 30-minute Google Meet link when:
- A booking is confirmed **AND** the artist has `require_consultation` enabled in their settings
- The artist's `user_details.require_consultation` field is `true`
- Google Calendar is connected (required for Meet link generation)

**Important:** Meet links are **only created** if the artist requires consultations. If `require_consultation` is `false`, no Meet link will be generated.

### Key Requirements

- ✅ Generate Google Meet link automatically
- ✅ Store meeting link in database
- ✅ Send meeting link to both artist and client via email
- ✅ Meeting duration: 30 minutes
- ✅ Meeting scheduled for booking date/time (or consultation date/time)
- ✅ Include meeting details in booking confirmation emails

---

## 2. Understanding Google Meet Integration

### How Google Meet Works

Google Meet links can be created in two ways:

#### Option 1: Simple Meet Links (No API Required)
- **How:** Generate a simple Google Meet URL format
- **Pros:** No API setup, instant, free
- **Cons:** No scheduling, no calendar integration, manual creation
- **Format:** `https://meet.google.com/xxx-xxxx-xxx`

#### Option 2: Google Calendar API (Recommended)
- **How:** Create a calendar event with Google Meet enabled
- **Pros:** Automatic scheduling, calendar integration, professional
- **Cons:** Requires Google Calendar API setup (already done!)
- **Format:** Event created with `conferenceData` → Meet link auto-generated

### Current System Status

✅ **Already Implemented:**
- Google Calendar OAuth connection (artists can connect)
- Google Calendar event creation on booking confirmation
- Calendar event stored with `google_calendar_event_id`

✅ **What We Need to Add:**
- Extract Google Meet link from calendar event
- Store Meet link in database
- Include Meet link in emails
- Create Meet link for consultations

---

## 3. Approach Options

### Approach 1: Extract Meet Link from Calendar Event ⭐ RECOMMENDED

**How it works:**
1. When creating Google Calendar event, Google automatically adds Meet link
2. After event creation, fetch the event details
3. Extract `conferenceData.entryPoints[0].uri` (the Meet link)
4. Store in database
5. Include in emails

**Pros:**
- ✅ Uses existing Google Calendar integration
- ✅ Automatic scheduling
- ✅ Professional (appears in both calendars)
- ✅ No additional API setup needed
- ✅ Meet link is permanent and reusable

**Cons:**
- ⚠️ Requires Google Calendar to be connected
- ⚠️ Slightly more complex code

**Best for:** Production-ready solution, professional appearance

---

### Approach 2: Generate Simple Meet Link (Manual)

**How it works:**
1. Generate a random Meet code: `xxx-xxxx-xxx`
2. Create URL: `https://meet.google.com/xxx-xxxx-xxx`
3. Store in database
4. Include in emails

**Pros:**
- ✅ Simple implementation
- ✅ Works without Google Calendar
- ✅ No API calls needed

**Cons:**
- ❌ No calendar integration
- ❌ Not scheduled (manual timing)
- ❌ Less professional
- ❌ Meet link may not be valid until first use

**Best for:** Quick prototype, fallback option

---

### Approach 3: Google Meet API (Advanced)

**How it works:**
1. Use Google Meet API to create scheduled meetings
2. Get Meet link from API response
3. Store in database

**Pros:**
- ✅ Full control over meeting settings
- ✅ Can set duration, participants, etc.

**Cons:**
- ❌ Requires Google Workspace account
- ❌ More complex setup
- ❌ May require additional permissions

**Best for:** Enterprise solutions with Google Workspace

---

## 4. Recommended Solution

### 🎯 **Approach 1: Extract Meet Link from Calendar Event**

**Why this approach:**
1. We already have Google Calendar integration
2. Calendar events are already being created
3. Most professional solution
4. Automatic scheduling
5. Works seamlessly with existing flow

### Implementation Flow

```
Booking Confirmed
    ↓
Check: Does artist have require_consultation = true?
    ├─ NO → Skip Meet link creation, continue with booking ✅
    └─ YES → Continue to next step
    ↓
Check: Is Google Calendar connected?
    ├─ NO → Skip Meet link creation, continue with booking ✅
    └─ YES → Continue to next step
    ↓
Create Google Calendar Event (existing code)
    ↓
Enable Google Meet in Event (add conferenceData)
    ↓
Fetch Event Details from Google Calendar API
    ↓
Extract Meet Link (conferenceData.entryPoints[0].uri)
    ↓
Store Meet Link in Database (bookings table)
    ↓
Include Meet Link in Confirmation Emails
    ↓
Done! ✅
```

---

## 5. Implementation Plan

### Phase 1: Database Changes

**Step 1.1:** Add `google_meet_link` column to `bookings` table

**Migration:**
```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_google_meet_link_to_bookings_table.php
Schema::table('bookings', function (Blueprint $table) {
    $table->string('google_meet_link')->nullable()->after('google_calendar_event_id');
});
```

**Step 1.2:** Update Booking Model

Add to `$fillable` array:
```php
'google_meet_link',
```

---

### Phase 2: Update Google Calendar Event Creation

**Step 2.1:** Modify `GoogleCalendarController::createCalendarEvent()`

**Current flow:**
- Creates event
- Returns event ID
- Stores event ID

**New flow:**
- Creates event with Meet enabled
- Fetches event details
- Extracts Meet link
- Returns both event ID and Meet link

**Key changes:**
1. Add `conferenceData` to event creation
2. Fetch event after creation
3. Extract Meet link from response

---

### Phase 3: Update Booking Confirmation

**Step 3.1:** Modify `InkJinController::confirmBooking()`

**Current code location:** `app/Http/Controllers/InkJinController.php` (around line 1500)

**Changes needed:**
1. **Check if artist requires consultation** (`user_details.require_consultation`)
2. Only create Meet link if `require_consultation` is `true`
3. Capture Meet link from calendar event creation
4. Store Meet link in booking record
5. Pass Meet link to email templates

---

### Phase 4: Update Email Templates

**Step 4.1:** Modify Booking Confirmation Emails

**Files to update:**
- `app/Mail/BookingConfirmationMail.php`
- Email templates (if separate)

**Changes:**
- Add Meet link section
- Include meeting time
- Add "Join Meeting" button/link

---

### Phase 5: Add Meeting Link Display

**Step 5.1:** Show Meet link in booking details

**Places to add:**
- Booking confirmation page
- Booking details modal
- Bookings listing page
- Email notifications

---

## 6. Database Changes

### 6.1 Migration File

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_add_google_meet_link_to_bookings_table.php`

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

### 6.2 Update Booking Model

**File:** `app/Models/Booking.php`

Add to `$fillable` array:
```php
protected $fillable = [
    // ... existing fields ...
    'google_meet_link',
];
```

---

## 7. Code Implementation

### 7.1 Update GoogleCalendarController

**File:** `app/Http/Controllers/GoogleCalendarController.php`

**Method to modify:** `createCalendarEvent()`

**Current code structure:**
```php
public static function createCalendarEvent($userDetail, $booking)
{
    // ... existing code creates event ...
    
    $event = $service->events->insert($calendarId, $event);
    
    return $event->id; // Currently returns only event ID
}
```

**Updated code:**
```php
public static function createCalendarEvent($userDetail, $booking)
{
    // ... existing code ...
    
    // Add conference data to enable Google Meet
    $event->setConferenceData(new \Google_Service_Calendar_ConferenceData([
        'createRequest' => new \Google_Service_Calendar_CreateConferenceRequest([
            'requestId' => uniqid(), // Unique request ID
            'conferenceSolutionKey' => new \Google_Service_Calendar_ConferenceSolutionKey([
                'type' => 'hangoutsMeet'
            ])
        ])
    ]));
    
    // Create event
    $event = $service->events->insert($calendarId, $event, [
        'conferenceDataVersion' => 1 // Required to enable Meet
    ]);
    
    // Extract Meet link
    $meetLink = null;
    if ($event->getConferenceData() && 
        $event->getConferenceData()->getEntryPoints()) {
        $entryPoints = $event->getConferenceData()->getEntryPoints();
        if (!empty($entryPoints) && isset($entryPoints[0])) {
            $meetLink = $entryPoints[0]->getUri();
        }
    }
    
    // Return both event ID and Meet link
    return [
        'event_id' => $event->getId(),
        'meet_link' => $meetLink
    ];
}
```

**Note:** Update return type handling in calling code.

---

### 7.2 Update InkJinController::confirmBooking()

**File:** `app/Http/Controllers/InkJinController.php`

**Location:** Around line 1500 (where calendar event is created)

**Current code:**
```php
$calendarEventId = GoogleCalendarController::createCalendarEvent($artistUserDetail, $booking);

if ($calendarEventId) {
    $booking->update(['google_calendar_event_id' => $calendarEventId]);
    // ...
}
```

**Updated code:**
```php
$calendarResult = GoogleCalendarController::createCalendarEvent($artistUserDetail, $booking);

if ($calendarResult && isset($calendarResult['event_id'])) {
    $booking->update([
        'google_calendar_event_id' => $calendarResult['event_id'],
        'google_meet_link' => $calendarResult['meet_link'] ?? null
    ]);
    
    // Add to action history
    $actionHistory = $booking->action_history ?? [];
    $actionHistory[] = [
        'action' => 'calendar_event_created',
        'user_id' => null,
        'user_type' => 'system',
        'timestamp' => now()->toDateTimeString(),
        'calendar_event_id' => $calendarResult['event_id'],
        'meet_link' => $calendarResult['meet_link'] ?? null,
        'notes' => 'Google Calendar event and Meet link created successfully',
    ];
    $booking->update(['action_history' => $actionHistory]);
    
    Log::info('Google Calendar event and Meet link created', [
        'booking_id' => $booking->id,
        'event_id' => $calendarResult['event_id'],
        'meet_link' => $calendarResult['meet_link'],
    ]);
}
```

---

### 7.3 Update Email Template

**File:** `app/Mail/BookingConfirmationMail.php`

**Add Meet link to email data:**
```php
public function build()
{
    $meetLink = $this->booking->google_meet_link;
    
    return $this->subject('Booking Confirmed - ' . $this->booking->tattoo->title)
        ->view('emails.booking-confirmation')
        ->with([
            'booking' => $this->booking,
            'meetLink' => $meetLink,
            'meetingTime' => $this->booking->booking_date->format('F j, Y') . 
                           ' at ' . Carbon::parse($this->booking->start_time_utc)
                           ->setTimezone($this->booking->timezone)
                           ->format('g:i A'),
        ]);
}
```

**Create/Update Email View:**

**File:** `resources/views/emails/booking-confirmation.blade.php`

**Add Meet link section:**
```blade
@if($meetLink)
<div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="color: #333; margin-bottom: 15px;">📹 Video Meeting</h3>
    <p style="color: #666; margin-bottom: 10px;">
        Join your 30-minute video consultation meeting:
    </p>
    <p style="margin-bottom: 15px;">
        <strong>Meeting Time:</strong> {{ $meetingTime }}<br>
        <strong>Duration:</strong> 30 minutes
    </p>
    <a href="{{ $meetLink }}" 
       style="display: inline-block; background-color: #00832d; color: white; 
              padding: 12px 24px; text-decoration: none; border-radius: 6px; 
              font-weight: bold; margin-top: 10px;">
        🎥 Join Google Meet
    </a>
    <p style="color: #999; font-size: 12px; margin-top: 15px;">
        Or copy this link: <br>
        <a href="{{ $meetLink }}" style="color: #00832d; word-break: break-all;">
            {{ $meetLink }}
        </a>
    </p>
</div>
@endif
```

---

### 7.4 Update Booking Details View

**File:** `resources/views/bookings/index.blade.php` or booking detail modal

**Add Meet link display:**
```blade
@if($booking->google_meet_link)
<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title">
            <i class="ti ti-video me-2"></i>Video Meeting Link
        </h6>
        <p class="text-muted mb-2">
            Join your 30-minute consultation meeting
        </p>
        <a href="{{ $booking->google_meet_link }}" 
           target="_blank" 
           class="btn btn-success">
            <i class="ti ti-external-link me-1"></i>Join Google Meet
        </a>
        <small class="text-muted d-block mt-2">
            Meeting scheduled for: 
            {{ $booking->booking_date->format('M j, Y') }} 
            at {{ Carbon\Carbon::parse($booking->start_time_utc)->setTimezone($booking->timezone)->format('g:i A') }}
        </small>
    </div>
</div>
@endif
```

---

## 8. Email Templates

### 8.1 Customer Confirmation Email

**Structure:**
```
Subject: Booking Confirmed - [Tattoo Name]

Hi [Customer Name],

Your booking has been confirmed!

Booking Details:
- Tattoo: [Tattoo Name]
- Artist: [Artist Name]
- Date: [Date]
- Time: [Time]
- Duration: [Duration]

📹 Video Meeting
Join your 30-minute consultation:
[Meeting Time]
[Join Google Meet Button]
[Meet Link]

[Other booking details...]
```

### 8.2 Artist Notification Email

**Structure:**
```
Subject: New Booking - [Customer Name]

Hi [Artist Name],

You have a new booking!

Booking Details:
[Details...]

📹 Video Meeting
Meeting link: [Meet Link]
[Join Google Meet Button]

[Other details...]
```

---

## 9. Testing Checklist

### 9.1 Functional Testing

- [ ] Create booking → Verify Meet link is generated
- [ ] Check Meet link is stored in database
- [ ] Verify Meet link appears in customer email
- [ ] Verify Meet link appears in artist email
- [ ] Test Meet link opens Google Meet correctly
- [ ] Verify Meet link works for both parties
- [ ] Test with Google Calendar connected
- [ ] Test with Google Calendar NOT connected (should handle gracefully)

### 9.2 Edge Cases

- [ ] What if Google Calendar API fails?
- [ ] What if Meet link is not generated?
- [ ] What if artist doesn't have Calendar connected?
- [ ] What if event creation succeeds but Meet link extraction fails?

### 9.3 UI Testing

- [ ] Meet link displays in booking details modal
- [ ] Meet link displays in bookings listing page
- [ ] Meet link button is clickable
- [ ] Meet link opens in new tab
- [ ] Mobile responsive

### 9.4 Email Testing

- [ ] Email includes Meet link
- [ ] "Join Meeting" button works
- [ ] Meet link is clickable
- [ ] Email formatting looks good
- [ ] Test on different email clients

---

## 10. Implementation Steps Summary

### Step-by-Step Guide

1. **Create Migration**
   ```bash
   php artisan make:migration add_google_meet_link_to_bookings_table
   ```
   - Add `google_meet_link` column
   - Run migration: `php artisan migrate`

2. **Update Booking Model**
   - Add `google_meet_link` to `$fillable`

3. **Update GoogleCalendarController**
   - Modify `createCalendarEvent()` to enable Meet
   - Extract Meet link from event response
   - Return both event ID and Meet link

4. **Update InkJinController**
   - Modify `confirmBooking()` to capture Meet link
   - Store Meet link in booking record

5. **Update Email Templates**
   - Add Meet link to email data
   - Create/update email view with Meet link section

6. **Update Booking Views**
   - Add Meet link display in booking details
   - Add Meet link to bookings listing

7. **Test**
   - Create test booking
   - Verify Meet link generation
   - Test email delivery
   - Test Meet link functionality

---

## 11. Code Files to Modify

### Files to Create/Modify:

1. ✅ **Migration:** `database/migrations/..._add_google_meet_link_to_bookings_table.php`
2. ✅ **Model:** `app/Models/Booking.php` (add to fillable)
3. ✅ **Controller:** `app/Http/Controllers/GoogleCalendarController.php` (modify createCalendarEvent)
4. ✅ **Controller:** `app/Http/Controllers/InkJinController.php` (modify confirmBooking)
5. ✅ **Mail:** `app/Mail/BookingConfirmationMail.php` (add Meet link)
6. ✅ **View:** `resources/views/emails/booking-confirmation.blade.php` (add Meet section)
7. ✅ **View:** `resources/views/bookings/index.blade.php` (add Meet link display)
8. ✅ **View:** `resources/views/public/book.blade.php` (add Meet link to details modal)

---

## 12. Error Handling

### Scenarios to Handle:

1. **Google Calendar Not Connected**
   - Don't create Meet link
   - Continue with booking (non-critical)
   - Log warning

2. **Meet Link Not Generated**
   - Check if event has conferenceData
   - Log error but don't fail booking
   - Continue without Meet link

3. **API Failure**
   - Catch exceptions
   - Log error
   - Continue booking (Meet link optional)

**Example Error Handling:**
```php
try {
    $calendarResult = GoogleCalendarController::createCalendarEvent(...);
    
    if ($calendarResult && isset($calendarResult['meet_link'])) {
        $booking->update(['google_meet_link' => $calendarResult['meet_link']]);
    } else {
        Log::warning('Meet link not generated for booking', [
            'booking_id' => $booking->id,
            'event_id' => $calendarResult['event_id'] ?? null,
        ]);
    }
} catch (\Exception $e) {
    Log::error('Failed to create Meet link (non-critical)', [
        'booking_id' => $booking->id,
        'error' => $e->getMessage(),
    ]);
    // Continue without Meet link - booking still succeeds
}
```

---

## 13. Future Enhancements

### Potential Improvements:

1. **Meeting Reminders**
   - Send reminder email 1 hour before meeting
   - Include Meet link in reminder

2. **Meeting Duration Configuration**
   - Allow artist to set meeting duration (not just 30 minutes)
   - Store in user_details or booking

3. **Meeting Recording**
   - Enable recording option
   - Store recording link after meeting

4. **Meeting Notes**
   - Add field for meeting notes
   - Store notes after meeting

5. **Alternative Video Platforms**
   - Support Zoom, Microsoft Teams
   - Let artist choose platform

6. **Meeting Rescheduling**
   - Update Meet link if booking is rescheduled
   - Send updated link to both parties

---

## 14. Quick Reference

### Google Meet Link Format

**Standard Format:**
```
https://meet.google.com/xxx-xxxx-xxx
```

**From Calendar Event:**
```php
$meetLink = $event->getConferenceData()
    ->getEntryPoints()[0]
    ->getUri();
// Returns: https://meet.google.com/xxx-xxxx-xxx
```

### Database Column

```php
'google_meet_link' => 'string', // nullable, max 500 chars
```

### Email Template Variables

```php
$meetLink          // The Google Meet URL
$meetingTime       // Formatted meeting time
$booking           // Booking model instance
```

---

## 15. Questions & Answers

### Q: What if artist doesn't have Google Calendar connected?

**A:** The Meet link won't be generated, but the booking will still succeed. You can:
- Show a message: "Connect Google Calendar to enable video meetings"
- Provide a manual Meet link option
- Use Approach 2 (simple Meet link generation) as fallback

### Q: What if artist has `require_consultation = false`?

**A:** No Meet link will be created. The booking will proceed normally without a video meeting link. This is the expected behavior - Meet links are only created when consultations are required.

### Q: Can we create Meet links without Calendar events?

**A:** Yes, but it's less professional. You can generate simple Meet URLs, but they won't be scheduled or appear in calendars.

### Q: How long is the Meet link valid?

**A:** Meet links from Calendar events are permanent and reusable. They remain valid as long as the calendar event exists.

### Q: Can we customize meeting duration?

**A:** Yes! The meeting duration is set by the calendar event duration. Currently bookings use the tattoo session duration, but you can set it to 30 minutes specifically for consultations.

### Q: What permissions are needed?

**A:** The existing Google Calendar OAuth scope (`https://www.googleapis.com/auth/calendar`) is sufficient. No additional permissions needed.

---

## 16. Next Steps

1. ✅ Read this guide completely
2. ✅ Review existing Google Calendar integration code
3. ✅ Create migration file
4. ✅ Update GoogleCalendarController
5. ✅ Update booking confirmation flow
6. ✅ Update email templates
7. ✅ Test with real Google account
8. ✅ Deploy to staging
9. ✅ Test end-to-end
10. ✅ Deploy to production

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Implementation

