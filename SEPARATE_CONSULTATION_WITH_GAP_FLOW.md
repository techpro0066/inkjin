# Separate Consultation Timing with Gap - Complete Booking Flow

## Overview
This document describes the complete booking flow when an artist has:
- `require_consultation = true`
- `consultation_timing = 'separate'`
- `require_gap_between_consultation_tattoo = true` (optional)
- `consultation_tattoo_gap_value` and `consultation_tattoo_gap_unit` (if gap is required)

**Important:** This is a **single continuous flow** - user books consultation, then tattoo session, then pays - all in one session. No waiting for artist to mark consultation as complete.

---

## Flow Diagram

```
Client selects Tattoo
    ↓
Check if consultation is required
    ↓
YES → Check consultation_timing
    ↓
    ├─→ Combined → (Already implemented - Option 1)
    │               Single booking with combined duration
    │
    └─→ Separate → Show Consultation Booking Step
            ↓
            Step 1: Select Consultation Date/Time Slot
            ↓
            Click "Next"
            ↓
            Step 2: Select Tattoo Session Date/Time Slot
                    (Calculate minimum date based on gap if required)
                    ↓
                    If gap required:
                    - consultation_end_time + gap_duration = minimum_tattoo_date
                    - Only show slots >= minimum_tattoo_date
                    ↓
                    If no gap:
                    - Show slots from consultation_end_time onwards
            ↓
            Click "Next"
            ↓
            Step 3: Questions (if any)
            ↓
            Step 4: Payment
            ↓
            Create Both Bookings:
            - Consultation booking (status: confirmed)
            - Tattoo session booking (status: confirmed)
            - Link tattoo session to consultation via consultation_booking_id
            ↓
            Booking Complete!
```

---

## Single Continuous Flow: Consultation → Tattoo Session → Payment

### Step 1: Tattoo Selection
- Client browses and selects a tattoo
- System checks artist's `require_consultation` and `consultation_timing`
- If `consultation_timing = 'separate'`:
  - Show notice: "⚠️ Consultation Required: This artist requires a consultation session. You'll book the consultation first, then the tattoo session, then pay."
  - Show "Book Now" button (starts the multi-step flow)

### Step 2: Consultation Slot Selection (Step 1 of Flow)
**Page Elements:**
- Progress indicator: "Step 1 of 4: Select Consultation"
- Tattoo preview/image
- Artist information
- Consultation duration display (from `session_duration_minutes`)
- Date selector (calendar)
- Time slot selector (shows available consultation slots only)
- "Next" button (disabled until slot selected)

**Time Slot Generation:**
- Generate slots based on consultation duration only (`session_duration_minutes`)
- Use same availability logic as regular booking
- Slots are for consultation session only

**User Action:**
- Client selects consultation date/time slot
- Slot is stored in session/temporary state
- "Next" button becomes enabled
- Client clicks "Next" → Proceeds to Step 2

### Step 3: Tattoo Session Slot Selection (Step 2 of Flow)

**Page Elements:**
- Progress indicator: "Step 2 of 4: Select Tattoo Session"
- Tattoo preview/image
- Selected consultation slot summary:
  - "Consultation: December 12, 2025 at 2:00 PM - 2:30 PM"
- Gap notice (if gap required):
  - "⏰ Minimum gap required: 2 days after consultation"
  - "You can book your tattoo session starting from December 14, 2025"
  - "You can select any date after December 14, 2025 based on artist availability"
- Date selector (calendar)
  - **If gap required:** Disable dates before `minimum_tattoo_session_date`
  - **Allow any date after minimum date** - user can select Dec 14, Dec 15, Dec 20, or any future date
  - **If no gap:** Allow dates from consultation end time onwards
- Time slot selector
  - **If gap required:** Show slots on dates >= `minimum_tattoo_session_date` (based on artist availability)
  - **User can select any available slot** after the minimum date
  - **If no gap:** Show slots from consultation end time onwards
- "Back" button (goes to Step 1)
- "Next" button (disabled until slot selected)

**Important:** The gap is a **minimum requirement**, not a restriction. User can book on the minimum date (Dec 14) or any later date where artist has availability (Dec 15, Dec 20, etc.).

**Gap Calculation Logic:**
**Logic:**
```php
if (consultation_completed && gap_required) {
    $consultationEndTime = Carbon::parse($consultation->consultation_end_time_utc);
    
    // Convert gap to minutes for calculation
    $gapMinutes = 0;
    switch ($gapUnit) {
        case 'minutes':
            $gapMinutes = $gapValue;
            break;
        case 'hours':
            $gapMinutes = $gapValue * 60;
            break;
        case 'days':
            $gapMinutes = $gapValue * 24 * 60;
            break;
    }
    
    $minimumTattooSessionDateTime = $consultationEndTime->addMinutes($gapMinutes);
    $minimumTattooSessionDate = $minimumTattooSessionDateTime->format('Y-m-d');
}
```

### Step 3: Tattoo Session Booking Page
**URL:** `/book/{tattoo_id}/tattoo-session` or `/book/{tattoo_id}?step=tattoo_session`

**Page Elements:**
- Tattoo preview/image
- Artist information
- Consultation status badge: "✅ Consultation completed!"
- Notice about gap (if applicable):
  - If gap required: "⏰ Minimum gap required: {gap_value} {gap_unit} after consultation. You can book your tattoo session starting from {minimum_date}."
  - If no gap: "You can now book your tattoo session."
- Date selector (calendar)
  - **If gap required:** Disable dates before `minimum_tattoo_session_date`
  - **If no gap:** Allow dates from consultation completion date onwards
- Time slot selector
  - **If gap required:** Only show slots on dates >= `minimum_tattoo_session_date`
  - **If no gap:** Show slots from consultation completion date onwards
- "Book Tattoo Session" button

**User Action:**
- Client selects tattoo session date/time slot
- Slot is stored in session/temporary state
- "Next" button becomes enabled
- Client clicks "Next" → Proceeds to Step 3

### Step 4: Questions (Step 3 of Flow)
**Page Elements:**
- Progress indicator: "Step 3 of 4: Answer Questions"
- Tattoo preview/image
- Selected slots summary:
  - "Consultation: December 15, 2025 at 2:00 PM - 2:30 PM"
  - "Tattoo Session: December 17, 2025 at 10:00 AM - 1:00 PM"
- Artist's custom questions (if any)
- "Back" button (goes to Step 2)
- "Next" button (proceeds to payment)

**User Action:**
- Client answers questions (if any)
- Client clicks "Next" → Proceeds to Step 4

### Step 5: Payment (Step 4 of Flow)
**Page Elements:**
- Progress indicator: "Step 4 of 4: Payment"
- Booking summary:
  - Consultation details
  - Tattoo session details
  - Total amount (consultation + tattoo session)
- Payment form (Stripe)
- "Back" button (goes to Step 3)
- "Pay Now" button

**User Action:**
- Client enters payment details
- Client clicks "Pay Now"
- Payment processed

### Step 6: Booking Confirmation
**After Payment Success:**
- System creates **both bookings**:
  1. **Consultation Booking:**
     - `booking_type` = 'consultation'
     - `status` = 'confirmed'
     - `tattoo_id` = selected tattoo ID
     - `consultation_timing_type` = 'separate'
     - Duration = consultation duration only
     - Google Meet link included (if Google Calendar connected)
     - `consultation_completed` = false (will be marked complete after actual consultation)
  
  2. **Tattoo Session Booking:**
     - `booking_type` = 'tattoo_session'
     - `status` = 'confirmed'
     - `tattoo_id` = selected tattoo ID
     - `consultation_booking_id` = reference to consultation booking
     - `consultation_timing_type` = 'separate'
     - Duration = tattoo session duration only
     - Google Calendar event created (no Meet link)

- Redirect to booking confirmation page
- Show both bookings with details
- Send confirmation emails for both bookings

---

## API Endpoints Needed

### 1. Get Consultation Slots (Step 1)
**Endpoint:** `GET /api/tattoos/{id}/consultation-slots`

**Query Parameters:**
- `date` (required): Date in Y-m-d format

**Response:** Same format as regular availability slots, but for consultation duration only

### 2. Get Tattoo Session Slots with Gap Filtering (Step 2)
**Endpoint:** `GET /api/tattoos/{id}/tattoo-session-slots`

**Query Parameters:**
- `date` (required): Date in Y-m-d format
- `consultation_date` (required): Consultation date in Y-m-d format
- `consultation_end_time_utc` (required): Consultation end time in H:i:s format

**Response:**
```json
{
  "success": true,
  "date": "2025-12-17",
  "timezone": "America/New_York",
  "is_unavailable": false,
  "tattoo": {
    "id": 1,
    "title": "Dragon Tattoo",
    "session_time_h": 3
  },
  "consultation_info": {
    "consultation_date": "2025-12-15",
    "consultation_end_time": "14:30:00",
    "gap_required": true,
    "gap_value": 2,
    "gap_unit": "days",
    "minimum_tattoo_session_datetime": "2025-12-17 14:30:00"
  },
  "time_slots": [
    {
      "start_time_utc": "15:00:00",
      "end_time_utc": "18:00:00",
      "start_time_display": "3:00 PM",
      "end_time_display": "6:00 PM",
      "duration_hours": 3
    }
  ]
}
```

**Logic:**
- Calculate minimum date/time from consultation end + gap
- Filter out dates before minimum date (disable in calendar)
- **Allow any date >= minimum date** - show all available dates after minimum
- Filter out time slots before minimum datetime (only for minimum date)
- For dates after minimum date, show all available slots normally
- Return available slots

**Example Response:**
```json
{
  "success": true,
  "date": "2025-12-17",
  "minimum_tattoo_session_datetime": "2025-12-14 14:30:00",
  "time_slots": [
    {
      "start_time_utc": "10:00:00",
      "end_time_utc": "13:00:00",
      "start_time_display": "10:00 AM",
      "end_time_display": "1:00 PM",
      "duration_hours": 3
    }
  ]
}
```

**Note:** User can request slots for Dec 14, Dec 15, Dec 17, or any future date. System will:
- For Dec 14: Only show slots after 2:30 PM (minimum datetime)
- For Dec 15+: Show all available slots (no time restriction, only date restriction)

### 3. Book Both Consultation and Tattoo Session (Final Step)
**Endpoint:** `POST /api/bookings/{tattoo_id}/book-separate`

**Request Body:**
```json
{
  "consultation_slot": {
    "date": "2025-12-15",
    "start_time_utc": "14:00:00",
    "end_time_utc": "14:30:00"
  },
  "tattoo_session_slot": {
    "date": "2025-12-17",
    "start_time_utc": "10:00:00",
    "end_time_utc": "13:00:00"
  },
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "amount": 500,
  "currency": "USD",
  "full_amount_paid": false,
  "payment_intent_id": "pi_xxx",
  "questions": {}
}
```

**Response:**
```json
{
  "success": true,
  "message": "Bookings confirmed successfully",
  "consultation_booking_id": 123,
  "tattoo_session_booking_id": 124,
  "consultation_time": {
    "start": "2:00 PM",
    "end": "2:30 PM",
    "date": "December 15, 2025"
  },
  "tattoo_session_time": {
    "start": "10:00 AM",
    "end": "1:00 PM",
    "date": "December 17, 2025"
  }
}
```

---

## Database Schema Updates

### Bookings Table
Already has:
- `consultation_timing_type` (enum: 'combined', 'separate')
- `consultation_booking_id` (nullable, foreign key to bookings.id)
- `consultation_completed` (boolean)

### User Details Table
Already has:
- `require_gap_between_consultation_tattoo` (boolean)
- `consultation_tattoo_gap_value` (integer)
- `consultation_tattoo_gap_unit` (enum: 'minutes', 'hours', 'days')

---

## UI/UX Flow (Single Continuous Flow)

### Step 0: Tattoo Selection
```
┌─────────────────────────────────────┐
│  Tattoo Image                       │
│  Tattoo Title                       │
│  Artist Name                        │
│                                      │
│  ⚠️ Consultation Required            │
│  This artist requires a consultation │
│  session before booking.            │
│                                      │
│  [Book Consultation]                │
└─────────────────────────────────────┘
```

### Step 1: Consultation Slot Selection
```
┌─────────────────────────────────────┐
│  Tattoo Preview                     │
│                                      │
│  Select Consultation Date & Time    │
│                                      │
│  📅 Date: [Calendar Picker]          │
│                                      │
│  ⏰ Available Consultation Slots:    │
│  ┌─────┐ ┌─────┐ ┌─────┐           │
│  │10:00│ │10:30│ │11:00│           │
│  │-    │ │-    │ │-    │           │
│  │10:30│ │11:00│ │11:30│           │
│  └─────┘ └─────┘ └─────┘           │
│                                      │
│  Duration: 30 minutes               │
│                                      │
│  [← Back]  [Next →]                  │
└─────────────────────────────────────┘
```

### Step 2: Tattoo Session Slot Selection
```
┌─────────────────────────────────────┐
│  ✅ Consultation Booked!            │
│                                      │
│  Date: December 15, 2025            │
│  Time: 2:00 PM - 2:30 PM            │
│                                      │
│  Google Meet Link:                  │
│  [Join Meeting]                     │
│                                      │
│  ⏳ Waiting for consultation...     │
│  After the artist marks the         │
│  consultation as completed, you'll  │
│  be able to book your tattoo        │
│  session.                           │
└─────────────────────────────────────┘
```

### Step 3: Questions

### Step 4: Payment

### Final: Booking Confirmation
```
┌─────────────────────────────────────┐
│  Tattoo Preview                     │
│                                      │
│  ✅ Consultation Completed!         │
│                                      │
│  ⏰ Minimum gap required: 2 days     │
│  You can book your tattoo session   │
│  starting from December 17, 2025    │
│                                      │
│  Select Tattoo Session Date & Time  │
│                                      │
│  📅 Date: [Calendar Picker]          │
│      (Dates before Dec 17 disabled) │
│                                      │
│  ⏰ Available Tattoo Session Slots: │
│  ┌─────┐ ┌─────┐ ┌─────┐           │
│  │10:00│ │13:00│ │16:00│           │
│  │-    │ │-    │ │-    │           │
│  │13:00│ │16:00│ │19:00│           │
│  └─────┘ └─────┘ └─────┘           │
│                                      │
│  Duration: 3 hours                  │
│                                      │
│  [← Back]  [Book Tattoo Session]    │
└─────────────────────────────────────┘
```

---

## Helper Functions Needed

### 1. Convert Gap to Minutes
```php
function convertGapToMinutes($value, $unit) {
    switch ($unit) {
        case 'minutes':
            return $value;
        case 'hours':
            return $value * 60;
        case 'days':
            return $value * 24 * 60;
        default:
            return 0;
    }
}
```

### 2. Calculate Minimum Tattoo Session Date
```php
function calculateMinimumTattooSessionDate($consultationBooking, $userDetail) {
    if (!$consultationBooking->consultation_completed) {
        return null;
    }
    
    $gapRequired = $userDetail->require_gap_between_consultation_tattoo ?? false;
    if (!$gapRequired) {
        // No gap required, can book immediately after consultation
        return Carbon::parse(
            $consultationBooking->consultation_date->format('Y-m-d') . ' ' . 
            $consultationBooking->consultation_end_time_utc
        );
    }
    
    $consultationEndTime = Carbon::parse(
        $consultationBooking->consultation_date->format('Y-m-d') . ' ' . 
        $consultationBooking->consultation_end_time_utc
    );
    
    $gapMinutes = convertGapToMinutes(
        $userDetail->consultation_tattoo_gap_value ?? 0,
        $userDetail->consultation_tattoo_gap_unit ?? 'days'
    );
    
    return $consultationEndTime->addMinutes($gapMinutes);
}
```

### 3. Filter Slots by Minimum Date
```php
function filterSlotsByMinimumDate($slots, $minimumDateTime) {
    return array_filter($slots, function($slot) use ($minimumDateTime) {
        $slotStartTime = Carbon::parse($slot['date'] . ' ' . $slot['start_time_utc']);
        return $slotStartTime->gte($minimumDateTime);
    });
}
```

---

## Edge Cases

### 1. No Gap Required
- After selecting consultation slot, client can select tattoo session slot immediately
- No date restrictions on calendar
- All available slots shown from consultation end time onwards

### 2. Gap Required
- Calendar shows disabled dates before minimum date
- **User can select ANY date after minimum date** - not restricted to minimum date only
- For minimum date: Only show slots after minimum datetime
- For dates after minimum date: Show all available slots normally
- Clear messaging about minimum date: "You can book starting from [date], or any later date"
- Frontend validates selected slot is after minimum datetime

### 3. Session Expired/Abandoned Flow
- If user abandons flow after selecting consultation slot, slot is released after timeout
- Can restart flow from beginning
- No bookings created until payment is successful

### 4. Payment Failure
- If payment fails, no bookings are created
- User can retry payment
- Consultation slot may become unavailable if someone else books it

### 5. Slot Unavailable During Flow
- If consultation slot becomes unavailable while user is in flow, show error
- Ask user to select different consultation slot
- If tattoo session slot becomes unavailable, show error and ask to select different slot

### 6. Multiple Tattoos
- Each tattoo requires separate consultation
- Each booking flow is independent
- Can have multiple consultation + tattoo session pairs for different tattoos

---

## Implementation Priority

1. ✅ Database schema (already done)
2. ⏳ Frontend: Multi-step booking flow component
3. ⏳ API endpoint: Get consultation slots (Step 1)
4. ⏳ API endpoint: Get tattoo session slots with gap filtering (Step 2)
5. ⏳ Frontend: Step 1 - Consultation slot selection
6. ⏳ Frontend: Step 2 - Tattoo session slot selection (with gap logic)
7. ⏳ Frontend: Step 3 - Questions (reuse existing)
8. ⏳ Frontend: Step 4 - Payment (reuse existing)
9. ⏳ API endpoint: Book both consultation and tattoo session (final step)
10. ⏳ Helper functions for gap calculation
11. ⏳ Frontend: Gap calculation and date filtering logic
12. ⏳ Frontend: Calendar date disabling based on gap
13. ⏳ Email notifications for both bookings
14. ⏳ Testing

---

## Notes

- **Single Continuous Flow:** User books consultation, then tattoo session, then pays - all in one session
- **No Artist Action Required:** Consultation is automatically considered part of the booking process
- **Gap Calculation:** Gap is calculated from consultation end time, not start time
- **Gap Period:** Gap period is inclusive (can book exactly at gap end time)
- **Gap is Minimum, Not Maximum:** User can select the minimum date OR any later date - gap is a minimum requirement, not a restriction
- **Visual Indicators:** Calendar should visually indicate disabled dates before minimum date, but allow all dates after minimum
- **Clear Messaging:** Clear messaging that user can select minimum date or any later date based on artist availability
- **Separate Bookings:** Both consultation and tattoo session bookings are separate records
- **Linked Bookings:** Linked via `consultation_booking_id` foreign key
- **Session Management:** Selected slots stored in session/temporary state until payment
- **Atomic Operation:** Both bookings created together after successful payment
- **Consultation Status:** `consultation_completed` starts as `false`, can be marked complete later by artist after actual consultation happens

