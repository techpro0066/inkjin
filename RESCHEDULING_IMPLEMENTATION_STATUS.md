# Rescheduling Functionality Implementation Status

## ✅ Completed

### 1. Database Migration
- ✅ Created migration: `2025_12_16_105721_add_reschedule_status_fields_to_bookings_table.php`
- ✅ Adds `reschedule_status` enum field (pending, accepted, declined, completed)
- ✅ Adds `reschedule_requested_by` enum field (client, artist)

### 2. Model Updates
- ✅ Updated `Booking` model to include new fields in `$fillable` array
- ✅ Fields: `reschedule_status`, `reschedule_requested_by`

### 3. Controller Implementation
- ✅ Created `ReschedulingController` with complete logic:
  - `checkCanReschedule()` - Validates if client can reschedule (checks limits and deadlines)
  - `artistRequestReschedule()` - Artist initiates reschedule request
  - `clientInitiateReschedule()` - Client selects new date/time
  - `showReschedulePage()` - Shows reschedule page with calendar
  - Helper methods for limit conversion and cancellation window parsing
  - Helper method to build availability data

### 4. Routes
- ✅ Added routes in `routes/web.php`:
  - `GET /api/bookings/{id}/can-reschedule` - Check reschedule eligibility
  - `POST /api/bookings/{id}/artist-request-reschedule` - Artist requests reschedule
  - `POST /api/bookings/{id}/reschedule` - Client reschedules (selects new date/time)
  - `GET /bookings/{id}/reschedule` - Reschedule page

### 5. Validation Logic
- ✅ Reschedule limit checking (never, once, twice, unlimited)
- ✅ Cancellation deadline validation
- ✅ Distinguishes between artist-initiated and client-initiated reschedules
- ✅ Artist-initiated reschedules don't count against client limit

## ⏳ Pending Implementation

### 6. Views
- ⏳ Create `resources/views/bookings/reschedule.blade.php`
  - Should reuse booking calendar from `resources/views/public/book.blade.php`
  - Show current booking details
  - Allow client to select new date/time
  - Handle both artist-requested and client-initiated reschedules

### 7. UI Integration
- ⏳ Add "Reschedule" button to booking list page (`resources/views/bookings/index.blade.php`)
  - Show for clients on their confirmed bookings
  - Show for artists to request reschedule
  - Check eligibility before showing button
  
- ⏳ Add reschedule button to booking details page (if exists)
- ⏳ Show reschedule status badges (pending, completed)
- ⏳ Show reschedule count and limit to clients

### 8. Email Notifications
- ⏳ Create `app/Mail/RescheduleRequestMail.php`
  - Sent to client when artist requests reschedule
  - Include link to reschedule page
  
- ⏳ Create `app/Mail/RescheduleConfirmationMail.php`
  - Sent to both client and artist when reschedule is completed
  - Show old and new date/time

### 9. Google Calendar Integration
- ⏳ Update Google Calendar event when booking is rescheduled
  - Update event date/time
  - Send updated calendar invite

### 10. Additional Features
- ⏳ Add reschedule history to booking action_history
- ⏳ Show reschedule requests in artist dashboard
- ⏳ Allow client to decline artist reschedule request (treat as cancellation)

## Implementation Details

### Reschedule Flow

#### Client-Initiated Reschedule:
1. Client clicks "Reschedule" on booking
2. System checks eligibility (limit, deadline)
3. If eligible: Show calendar to select new date/time
4. Client selects new date/time
5. Booking updated, reschedule_count incremented
6. Confirmation emails sent

#### Artist-Initiated Reschedule:
1. Artist clicks "Request Reschedule" on booking
2. Artist provides reason (optional)
3. Booking status set to `reschedule_status: pending`, `reschedule_requested_by: artist`
4. Client receives notification email
5. Client visits reschedule page
6. Client selects new date/time
7. Booking updated (reschedule_count NOT incremented)
8. Confirmation emails sent

### Key Logic Points

1. **Reschedule Limits**: Stored in `user_details.reschedule_times` (never, once, twice, unlimited)
2. **Cancellation Deadline**: Uses `user_details.cancellation_window` (hours before appointment)
3. **Reschedule Count**: Tracked per booking in `bookings.reschedule_count`
4. **Artist Requests**: Never count against client limit
5. **After Deadline**: Reschedule treated as cancellation

## Next Steps

1. Create reschedule view (reuse booking calendar)
2. Add reschedule buttons to booking pages
3. Create email notification classes
4. Test complete flow
5. Add Google Calendar event updates

## Testing Checklist

- [ ] Client can reschedule within limit and before deadline
- [ ] Client cannot reschedule after deadline
- [ ] Client cannot reschedule beyond limit
- [ ] Artist can request reschedule at any time
- [ ] Artist-requested reschedules don't count against client limit
- [ ] Reschedule updates booking date/time correctly
- [ ] Emails are sent correctly
- [ ] Google Calendar events are updated (when implemented)

