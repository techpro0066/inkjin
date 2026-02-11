# InkJin Platform - User Flow Diagrams

This document provides visual flow diagrams for all major user journeys in the InkJin platform.

---

## 1. Artist Onboarding Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    ARTIST ONBOARDING FLOW                    │
└─────────────────────────────────────────────────────────────┘

[Registration]
    │
    ├─→ Fill form (email, password, role=artist)
    ├─→ System checks inkjin_artists table
    ├─→ Link to existing artist (if found)
    └─→ Email verification sent
         │
         ▼
[Email Verification]
    │
    ├─→ Click verification link
    └─→ Email verified
         │
         ▼
[Onboarding Step 1: Profile]
    │
    ├─→ Upload avatar
    ├─→ Enter username (unique)
    ├─→ Enter mobile number (unique)
    ├─→ Select country & city
    └─→ Save → current_step = 2
         │
         ▼
[Onboarding Step 2: Studio]
    │
    ├─→ Enter studio name
    ├─→ Enter studio address
    ├─→ Enter Google Maps link (optional)
    └─→ Save → current_step = 3
         │
         ▼
[Onboarding Step 3: Google Calendar]
    │
    ├─→ [Optional] Connect Google Calendar
    │   │
    │   ├─→ Click "Connect"
    │   ├─→ OAuth redirect to Google
    │   ├─→ User grants permissions
    │   ├─→ Callback with tokens
    │   └─→ Tokens stored (encrypted)
    │
    └─→ Save → current_step = 4
         │
         ▼
[Onboarding Step 4: Preferences]
    │
    ├─→ Select currency
    ├─→ Select timezone
    ├─→ Select date/time format
    ├─→ Set minimum deposit (amount & type)
    ├─→ Set cancellation window
    ├─→ Set reschedule times
    ├─→ Set session buffer period
    ├─→ Set require consultation
    └─→ Save → current_step = 5
         │
         ▼
[Onboarding Step 5: Stripe Connect]
    │
    ├─→ Click "Connect Stripe"
    ├─→ Redirect to /connect-stripe
    ├─→ Stripe Express account created
    ├─→ Redirect to Stripe onboarding
    ├─→ User completes Stripe setup
    ├─→ Callback to /connect-stripe/callback
    ├─→ Verify account status
    │   │
    │   ├─→ charges_enabled = true
    │   └─→ payouts_enabled = true
    │
    ├─→ stripe_account_id saved
    ├─→ on_boarding = 'yes'
    ├─→ Default questions assigned
    └─→ Redirect to /dashboard
         │
         ▼
    [ONBOARDING COMPLETE]
```

---

## 2. Public Booking Flow (Flash Tattoo)

```
┌─────────────────────────────────────────────────────────────┐
│              PUBLIC BOOKING FLOW (FLASH TATTOO)              │
└─────────────────────────────────────────────────────────────┘

[Browse Artists]
    │
    ├─→ Visit /artists
    └─→ Select artist
         │
         ▼
[View Artist Profile]
    │
    ├─→ View portfolio
    └─→ Click on tattoo
         │
         ▼
[View Tattoo Page]
    │
    ├─→ View tattoo details
    ├─→ View price, size, duration
    └─→ Click "Book Now"
         │
         ▼
[Booking Page: Date Selection]
    │
    ├─→ Calendar shows available dates
    │   │
    │   ├─→ Check weekly availability
    │   ├─→ Check date overrides
    │   ├─→ Check existing bookings
    │   └─→ Check Google Calendar events
    │
    └─→ Select date
         │
         ▼
[Booking Page: Time Slot Selection]
    │
    ├─→ Fetch available slots via API
    │   │
    │   ├─→ Get weekly availability for day
    │   ├─→ Get date overrides
    │   ├─→ Get Google Calendar events
    │   ├─→ Get existing bookings
    │   ├─→ Generate slots (based on duration)
    │   └─→ Filter (overlaps, buffer periods)
    │
    └─→ Select time slot
         │
         ▼
[Booking Page: Consultation?]
    │
    ├─→ Check require_consultation
    │   │
    │   ├─→ NO → Skip to Questions
    │   │
    │   └─→ YES → Check consultation_timing
    │       │
    │       ├─→ Combined → Single slot (consultation + tattoo)
    │       │
    │       └─→ Separate → Book consultation first
    │           │
    │           ├─→ Select consultation date/time
    │           ├─→ Check gap requirement
    │           └─→ Select tattoo session date/time
    │
         │
         ▼
[Booking Page: Questions]
    │
    ├─→ Fetch artist's questions
    ├─→ Dynamic form generation
    │   │
    │   ├─→ Text input
    │   ├─→ Select dropdown
    │   ├─→ Radio buttons
    │   └─→ Image upload (multiple)
    │
    └─→ Answer questions & upload images
         │
         ▼
[Booking Page: Payment]
    │
    ├─→ Calculate payment amount
    │   │
    │   ├─→ Deposit amount (if deposit only)
    │   ├─→ Full amount (if full payment)
    │   └─→ Platform fee (£10) added
    │
    ├─→ Create Payment Intent (server-side)
    ├─→ Display Stripe Elements form
    └─→ Enter payment details
         │
         ▼
[Booking Confirmation]
    │
    ├─→ Process payment
    ├─→ Create booking record
    │   │
    │   ├─→ status = 'confirmed'
    │   ├─→ payment_status = 'paid'
    │   ├─→ questions_answers (JSON)
    │   └─→ action_history (JSON)
    │
    ├─→ Create Google Calendar event (if connected)
    ├─→ Send email to client
    ├─→ Send email to artist
    └─→ Show confirmation page
         │
         ▼
    [BOOKING COMPLETE]
```

---

## 3. Booking Cancellation Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  BOOKING CANCELLATION FLOW                   │
└─────────────────────────────────────────────────────────────┘

[User Initiates Cancellation]
    │
    ├─→ Click "Cancel Booking"
    └─→ Fetch cancellation info via API
         │
         ▼
[Calculate Cancellation Info]
    │
    ├─→ Get booking details
    ├─→ Calculate cancellation deadline
    │   │
    │   └─→ booking_time - cancellation_window_hours
    │
    ├─→ Determine cancellation type
    │   │
    │   ├─→ Client cancellation
    │   └─→ Artist cancellation
    │
    └─→ Calculate refund eligibility
         │
         ▼
[Show Cancellation Modal]
    │
    ├─→ Display cancellation deadline
    ├─→ Display refund amount (if any)
    ├─→ Display warning (if deposit forfeited)
    └─→ User confirms cancellation
         │
         ▼
[Process Cancellation]
    │
    ├─→ Calculate refund amount
    │   │
    │   ├─→ Artist cancels → Full refund
    │   ├─→ Client before deadline → Full refund
    │   └─→ Client after deadline → Deposit forfeited
    │
    ├─→ Update booking record
    │   │
    │   ├─→ status = 'cancelled'
    │   ├─→ cancelled_by = user_id
    │   ├─→ cancelled_at = now()
    │   ├─→ refund_amount
    │   ├─→ deposit_forfeited
    │   └─→ action_history updated
    │
    ├─→ Process Stripe refund (if refund_amount > 0)
    │   │
    │   ├─→ Create refund via Stripe API
    │   ├─→ Update refund_intent_id
    │   └─→ Update refund_status = 'completed'
    │
    ├─→ Delete Google Calendar event (if exists)
    ├─→ Send email to client
    └─→ Send email to artist
         │
         ▼
    [CANCELLATION COMPLETE]
```

---

## 4. Availability Calculation Flow

```
┌─────────────────────────────────────────────────────────────┐
│            AVAILABILITY SLOT CALCULATION FLOW                │
└─────────────────────────────────────────────────────────────┘

[User Selects Date]
    │
    ▼
[Fetch Weekly Availability]
    │
    ├─→ Get availabilities for day of week
    ├─→ Filter by user_id = artist_id
    └─→ Get start_time_utc, end_time_utc
         │
         ▼
[Check Date Overrides]
    │
    ├─→ Get availability_overrides for date
    ├─→ Check is_unavailable flag
    │   │
    │   ├─→ If unavailable → Return empty slots
    │   └─→ If available → Use override times
    │
         │
         ▼
[Fetch Google Calendar Events]
    │
    ├─→ Check if artist has Google Calendar connected
    ├─→ Fetch events for date
    └─→ Get busy times
         │
         ▼
[Fetch Existing Bookings]
    │
    ├─→ Get bookings for date
    ├─→ Filter by status = 'confirmed'
    └─→ Get start_time_utc, end_time_utc
         │
         ▼
[Generate Time Slots]
    │
    ├─→ Start from availability start_time
    ├─→ Create slots based on session duration
    │   │
    │   └─→ slot_end = slot_start + duration
    │
    ├─→ Apply buffer period
    │   │
    │   └─→ next_slot_start = slot_end + buffer_period
    │
    └─→ Continue until availability end_time
         │
         ▼
[Filter Slots]
    │
    ├─→ Remove slots that overlap with:
    │   │
    │   ├─→ Google Calendar events
    │   ├─→ Existing bookings
    │   └─→ Date overrides (if blocked)
    │
    ├─→ Remove slots that don't respect buffer period
    └─→ Convert to user's timezone
         │
         ▼
[Return Available Slots]
    │
    └─→ Return array of available slots
         │
         │ Format: [
         │   {
         │     "date": "2025-01-15",
         │     "start": "10:00 AM",
         │     "end": "2:00 PM",
         │     "start_utc": "08:00:00",
         │     "end_utc": "12:00:00"
         │   },
         │   ...
         │ ]
```

---

## 5. Payment Processing Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  PAYMENT PROCESSING FLOW                     │
└─────────────────────────────────────────────────────────────┘

[User Completes Booking Form]
    │
    ├─→ Answers questions
    └─→ Clicks "Continue to Payment"
         │
         ▼
[Calculate Payment Amount]
    │
    ├─→ Get tattoo price
    ├─→ Check payment type
    │   │
    │   ├─→ Deposit only → deposit_amount
    │   └─→ Full payment → full_amount
    │
    └─→ Add platform fee (£10)
         │
         ▼
[Create Payment Intent]
    │
    ├─→ POST /api/booking/{tattoo_id}/payment-intent
    ├─→ Server creates Stripe Payment Intent
    │   │
    │   ├─→ amount = calculated_amount
    │   ├─→ currency = artist_currency
    │   ├─→ payment_method_types = ['card']
    │   └─→ on_behalf_of = stripe_account_id
    │
    └─→ Return client_secret
         │
         ▼
[Display Payment Form]
    │
    ├─→ Initialize Stripe Elements
    ├─→ Display card input fields
    └─→ User enters card details
         │
         ▼
[Submit Payment]
    │
    ├─→ Client-side: Confirm payment with Stripe
    ├─→ Stripe processes payment
    │   │
    │   ├─→ Charge card
    │   ├─→ Transfer to artist's connected account
    │   └─→ Platform fee handled automatically
    │
    └─→ Payment success/failure
         │
         ▼
[Confirm Booking]
    │
    ├─→ POST /api/booking/{tattoo_id}/confirm
    ├─→ Verify payment status
    │   │
    │   ├─→ Check payment_intent.status = 'succeeded'
    │   └─→ If not → Return error
    │
    ├─→ Create booking record
    │   │
    │   ├─→ status = 'confirmed'
    │   ├─→ payment_status = 'paid'
    │   ├─→ payment_intent_id = payment_intent.id
    │   └─→ total_amount_paid = amount
    │
    ├─→ Create Google Calendar event
    ├─→ Send confirmation emails
    └─→ Return success
         │
         ▼
    [PAYMENT COMPLETE]
```

---

## 6. Artist Dashboard Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  ARTIST DASHBOARD FLOW                       │
└─────────────────────────────────────────────────────────────┘

[Login]
    │
    ├─→ Authenticate
    ├─→ Check onboarding status
    │   │
    │   ├─→ If incomplete → Redirect to /onboarding
    │   └─→ If complete → Continue
    │
    └─→ Redirect to /dashboard
         │
         ▼
[Dashboard]
    │
    ├─→ Display welcome message
    ├─→ Display quick stats (if any)
    └─→ Navigation menu
         │
         ▼
[Bookings Page]
    │
    ├─→ Fetch bookings where artist_user_id = user.id
    ├─→ Apply filters (if any)
    │   │
    │   ├─→ Status filter
    │   ├─→ Payment status filter
    │   └─→ Date range filter
    │
    ├─→ Calculate statistics
    │   │
    │   ├─→ Total bookings
    │   ├─→ Confirmed bookings
    │   ├─→ Pending bookings
    │   └─→ Upcoming bookings
    │
    └─→ Display bookings list
         │
         ▼
[Booking Details]
    │
    ├─→ Click on booking
    ├─→ Open modal/view
    │   │
    │   ├─→ Display booking information
    │   ├─→ Display questions/answers
    │   ├─→ Display payment details
    │   ├─→ Display action history
    │   └─→ Display cancellation options (if applicable)
    │
    └─→ Actions available
         │
         ├─→ Cancel booking
         ├─→ Mark as no-show
         └─→ View details
```

---

## 7. Settings Management Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  SETTINGS MANAGEMENT FLOW                    │
└─────────────────────────────────────────────────────────────┘

[Access Settings]
    │
    ├─→ Navigate to /settings/*
    └─→ Select settings section
         │
         ├─→ Profile Settings
         ├─→ Studio Settings
         ├─→ Calendar Settings
         └─→ Preferences Settings
         │
         ▼
[Profile Settings]
    │
    ├─→ View current profile
    ├─→ Edit avatar, username, mobile, location
    └─→ Save changes
         │
         ▼
[Studio Settings]
    │
    ├─→ View studio information
    └─→ (Read-only view)
         │
         ▼
[Calendar Settings]
    │
    ├─→ Check Google Calendar connection status
    │   │
    │   ├─→ Connected → Show disconnect option
    │   └─→ Not connected → Show connect option
    │
    └─→ Connect/Disconnect Google Calendar
         │
         ▼
[Preferences Settings]
    │
    ├─→ View current preferences
    ├─→ Edit preferences
    │   │
    │   ├─→ Currency
    │   ├─→ Timezone
    │   ├─→ Date/time format
    │   ├─→ Deposit settings
    │   ├─→ Cancellation window
    │   ├─→ Reschedule times
    │   ├─→ Buffer period
    │   └─→ Consultation settings
    │
    └─→ Save changes
```

---

## 8. Question Management Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  QUESTION MANAGEMENT FLOW                    │
└─────────────────────────────────────────────────────────────┘

[Access Questions]
    │
    ├─→ Navigate to /questions (artist)
    └─→ Navigate to /admin/questions (admin)
         │
         ▼
[View Questions]
    │
    ├─→ Fetch questions
    │   │
    │   ├─→ Artist: user_questions
    │   └─→ Admin: questions (default)
    │
    └─→ Display questions list
         │
         ▼
[Create Question]
    │
    ├─→ Click "Add Question"
    ├─→ Fill form
    │   │
    │   ├─→ Question text
    │   ├─→ Question type (text, select, radio, image)
    │   ├─→ Options (if select/radio)
    │   └─→ Max images (if image type)
    │
    └─→ Save question
         │
         ▼
[Edit Question]
    │
    ├─→ Click "Edit" on question
    ├─→ Update form fields
    └─→ Save changes
         │
         ▼
[Delete Question]
    │
    ├─→ Click "Delete" on question
    ├─→ Confirm deletion
    └─→ Delete question
         │
         ▼
[Toggle Status]
    │
    ├─→ Click "Activate/Deactivate"
    └─→ Update question status
```

---

## 9. Complete User Journey Map

```
┌─────────────────────────────────────────────────────────────┐
│                    COMPLETE USER JOURNEY                      │
└─────────────────────────────────────────────────────────────┘

ARTIST JOURNEY:
    │
    ├─→ Register → Verify Email → Onboarding → Dashboard
    │   │
    │   ├─→ Manage Availability
    │   ├─→ Manage Questions
    │   ├─→ Manage Settings
    │   └─→ View Bookings
    │
    └─→ Receive Bookings → Manage Bookings → Process Payments

CLIENT JOURNEY:
    │
    ├─→ Browse Artists → View Artist Profile → View Tattoo
    │   │
    │   └─→ Book Tattoo → Answer Questions → Pay → Confirmation
    │
    └─→ View Bookings → Cancel/Reschedule (if needed)

ADMIN JOURNEY:
    │
    ├─→ Login → Dashboard → Manage Users → Manage Questions
    │
    └─→ View Statistics → Manage Platform
```

---

## 10. Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      DATA FLOW DIAGRAM                        │
└─────────────────────────────────────────────────────────────┘

[User Input]
    │
    ▼
[Validation Layer]
    │
    ├─→ Form Request Validation
    └─→ Business Rule Validation
         │
         ▼
[Controller Layer]
    │
    ├─→ Handle HTTP Request
    ├─→ Call Service Layer
    └─→ Return Response
         │
         ▼
[Service Layer]
    │
    ├─→ Business Logic
    ├─→ External API Calls
    │   │
    │   ├─→ Stripe API
    │   ├─→ Google Calendar API
    │   └─→ InkJin API
    │
    └─→ Data Transformation
         │
         ▼
[Model Layer]
    │
    ├─→ Database Queries
    ├─→ Relationships
    └─→ Data Access
         │
         ▼
[Database]
    │
    └─→ MySQL/MariaDB
         │
         ▼
[Response]
    │
    ├─→ JSON (API)
    ├─→ View (Web)
    └─→ Redirect
```

---

**End of Flow Diagrams**
