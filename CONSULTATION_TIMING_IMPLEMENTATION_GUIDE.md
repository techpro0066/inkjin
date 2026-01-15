# Consultation Timing Implementation Guide

## Overview
This guide describes how the consultation timing system works when an artist has `require_consultation` enabled. Artists can choose between two options:
1. **Combined** - Consultation time is added to the tattoo session duration
2. **Separate** - Consultation is a standalone session that must be completed before booking the actual tattoo session

---

## Option 1: Combined Consultation Timing

### How It Works
When an artist selects "Add with Tattoo Session" (`consultation_timing = 'combined'`):

1. **Booking Flow:**
   - Client selects a tattoo and date/time slot
   - The system calculates total booking duration as: `tattoo_session_duration + consultation_duration`
   - Example: If tattoo session is 3 hours and consultation is 30 minutes, total booking time = 3.5 hours
   - The time slot must accommodate both the tattoo session AND the consultation time

2. **Time Slot Generation:**
   - Available slots are generated based on the combined duration
   - If artist has 3-hour slots available and consultation is 30 minutes, the system will:
     - Check if 3.5 hours fit in the availability window
     - Generate slots that accommodate the full 3.5 hours
     - Display the slot as "3 hours + 30 min consultation"

3. **Google Calendar Event:**
   - Single event is created with the combined duration
   - Google Meet link is included in the event
   - Event title: "Tattoo Session + Consultation - [Tattoo Name]"

4. **Booking Record:**
   - Single booking record is created
   - `booking_type` = 'tattoo_session'
   - Duration includes both tattoo and consultation time
   - Google Meet link is stored for the consultation portion

---

## Option 2: Separate Consultation Timing

### How It Works
When an artist selects "Separate from Tattoo Session" (`consultation_timing = 'separate'`):

### Phase 1: Consultation Booking (Required First)

1. **Initial Booking Flow:**
   - Client selects a tattoo
   - System shows a notice: "This artist requires a consultation session before booking the tattoo session"
   - Client is redirected to book a consultation session first
   - Consultation session uses the `session_duration_minutes` set by the artist (e.g., 30 minutes)

2. **Consultation Booking:**
   - Client selects date/time for consultation
   - Available slots are generated based on consultation duration only
   - Booking is created with:
     - `booking_type` = 'consultation'
     - `status` = 'confirmed' (or 'pending' if payment required)
     - Duration = consultation duration only
     - Google Meet link is included
     - Reference to the tattoo they want (`tattoo_id`)

3. **Consultation Status Tracking:**
   - New field in `bookings` table: `consultation_completed` (boolean)
   - Artist can mark consultation as completed
   - Artist can see consultation status in their dashboard

### Phase 2: Tattoo Session Booking (After Consultation)

1. **Post-Consultation Flow:**
   - After consultation is marked as completed by artist
   - Client receives notification that they can now book the tattoo session
   - Client can access the tattoo booking page again
   - System checks if consultation exists and is completed

2. **Tattoo Session Booking:**
   - Client selects date/time for actual tattoo session
   - Available slots are generated based on tattoo session duration only (no consultation time added)
   - Booking is created with:
     - `booking_type` = 'tattoo_session'
     - `status` = 'confirmed'
     - Duration = tattoo session duration only
     - Reference to the consultation booking (`consultation_booking_id`)
     - Reference to the tattoo (`tattoo_id`)

3. **Booking Relationship:**
   - Consultation booking and tattoo session booking are linked
   - `bookings` table has `consultation_booking_id` field to link them
   - Artist can see both bookings in their dashboard
   - Client can see consultation status and tattoo session booking

---

## Database Schema Changes

### New Fields in `bookings` Table:
```php
- consultation_booking_id (nullable, foreign key to bookings.id)
  // Links tattoo session to its consultation booking
  
- consultation_completed (boolean, default false)
  // Tracks if consultation has been completed (for separate timing)
  
- consultation_timing_type (enum: 'combined', 'separate', nullable)
  // Stores the artist's consultation timing preference at time of booking
```

---

## User Interface Changes

### For Clients:

1. **Tattoo Selection Page:**
   - If artist has `require_consultation = true` and `consultation_timing = 'separate'`:
     - Show notice: "⚠️ Consultation Required: This artist requires a consultation session before booking. You'll book the consultation first, then the tattoo session."
     - Show "Book Consultation" button instead of "Book Tattoo Session"
   
   - If artist has `require_consultation = true` and `consultation_timing = 'combined'`:
     - Show notice: "ℹ️ Consultation Included: This booking includes a consultation session."
     - Show "Book Session" button (normal flow)

2. **Consultation Booking Page (Separate Mode):**
   - Display tattoo preview
   - Show consultation duration
   - Show available consultation slots
   - Note: "After consultation, you'll be able to book the tattoo session"

3. **Post-Consultation Page:**
   - Show consultation status
   - If completed: "✅ Consultation completed! You can now book your tattoo session."
   - Button: "Book Tattoo Session"
   - If pending: "⏳ Waiting for consultation..."

### For Artists:

1. **Dashboard - Bookings List:**
   - Show booking type badge: "Consultation" or "Tattoo Session"
   - For consultation bookings: Show "Mark as Completed" button
   - For tattoo sessions: Show linked consultation booking (if separate mode)

2. **Consultation Management:**
   - List of pending consultations
   - Ability to mark consultation as completed
   - After marking complete, client can book tattoo session

---

## Booking Flow Logic

### Combined Mode Flow:
```
1. Client selects tattoo
2. System checks: consultation_timing = 'combined'
3. Calculate total duration = tattoo_duration + consultation_duration
4. Generate slots with combined duration
5. Client books → Single booking created
6. Google Calendar event created with combined duration + Meet link
```

### Separate Mode Flow:
```
1. Client selects tattoo
2. System checks: consultation_timing = 'separate'
3. Check if consultation already exists and is completed
   - If YES → Show tattoo session booking page
   - If NO → Show consultation booking page
4. Consultation Booking:
   - Generate slots with consultation duration only
   - Client books consultation → Booking created (type: 'consultation')
   - Google Calendar event created with consultation duration + Meet link
5. Artist marks consultation as completed
6. Client receives notification
7. Tattoo Session Booking:
   - Generate slots with tattoo duration only
   - Client books tattoo session → Booking created (type: 'tattoo_session')
   - Link to consultation booking
   - Google Calendar event created with tattoo duration (no Meet link)
```

---

## API Endpoints Needed

1. **Check Consultation Status:**
   - `GET /api/tattoos/{id}/consultation-status`
   - Returns: consultation_required, consultation_timing, has_consultation, consultation_completed

2. **Mark Consultation Complete:**
   - `POST /api/bookings/{id}/complete-consultation`
   - Artist marks consultation as completed
   - Triggers notification to client

3. **Get Consultation Bookings:**
   - `GET /api/bookings/consultations`
   - Returns list of consultation bookings for artist

---

## Email Notifications

### Combined Mode:
- Single confirmation email with tattoo session + consultation details
- Includes Google Meet link

### Separate Mode:
1. **Consultation Booking:**
   - Consultation confirmation email
   - Includes Google Meet link
   - Note: "After consultation, you can book your tattoo session"

2. **Consultation Completed:**
   - Notification to client: "Your consultation is complete! Book your tattoo session now."

3. **Tattoo Session Booking:**
   - Tattoo session confirmation email
   - Reference to consultation booking

---

## Edge Cases & Considerations

1. **Consultation Not Completed:**
   - Client cannot book tattoo session until consultation is marked complete
   - Artist can cancel consultation (triggers refund)
   - Client can reschedule consultation

2. **Multiple Consultations:**
   - If client wants multiple tattoos, each may require separate consultation
   - Or artist can allow one consultation for multiple tattoos (to be decided)

3. **Cancellation:**
   - If consultation is cancelled, linked tattoo session (if exists) is also cancelled
   - Refund rules apply based on cancellation deadline

4. **Rescheduling:**
   - Consultation can be rescheduled independently
   - Tattoo session can be rescheduled independently (if consultation is completed)

5. **Payment:**
   - Consultation may require deposit or full payment
   - Tattoo session payment is separate
   - Or consultation can be free (to be configured)

---

## Implementation Priority

1. ✅ Database schema changes (migration)
2. ✅ Model updates
3. ⏳ Booking flow logic (combined mode)
4. ⏳ Booking flow logic (separate mode)
5. ⏳ UI updates for client booking flow
6. ⏳ UI updates for artist dashboard
7. ⏳ API endpoints
8. ⏳ Email notifications
9. ⏳ Testing

---

## Notes

- The `consultation_timing` preference is stored per artist in `user_details` table
- This preference can be changed by artist at any time
- Existing bookings maintain their original timing type
- New bookings use the current preference setting

