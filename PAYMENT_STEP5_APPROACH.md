# Onboarding Step 5 - Payment Setup Approach

## Overview
Modify onboarding step 5 to support three payment types: Artist, Studio, and Inkjin. Auto-send email to studio when studio option is selected.

## Database Columns (Already Exist)
- `payment_type` enum: `['artist_account', 'studio_account', 'inkjin_account']`
- `stripe_account_id` string: Stores Stripe account ID (artist's or studio's)
- `studio_email` string: Studio email address

## UI Changes

### 1. Payment Type Selection (Radio Buttons - Required)
- **Artist Account**: "Artist — Payments go directly to you"
- **Studio Account**: "Studio — Payments go to your studio"
- **Inkjin Account**: "Inkjin — Payments go to Inkjin and we pay you"

### 2. Conditional UI Based on Selection

#### If `artist_account` selected:
- Show current Stripe Connect card/UI
- Display connect/disconnect buttons
- Show validation error if Stripe not connected

#### If `studio_account` selected:
- Show info panel: "Payments will go to your studio's Stripe account"
- Display studio name (read-only, auto-filled from step 2: `studio_name`)
- Show email input field for `studio_email` (required)
- Show validation error if email is empty

#### If `inkjin_account` selected:
- Show info text: "Payments will be processed by Inkjin and paid out to you off-platform / via manual process"
- No Stripe connection required
- No additional fields

### 3. Form Structure
- Hidden input: `payment_type` (set by radio selection)
- Hidden input: `stripe_account_id` (only for artist_account)
- Text input: `studio_email` (only for studio_account, visible when studio_account selected)
- Display field: `studio_name` (read-only, from step 2 data)

## Validation Rules

### Frontend (JavaScript)
1. **Always required**: `payment_type` must be selected
2. **If `artist_account`**: `stripe_account_id` must not be empty
3. **If `studio_account`**: `studio_email` must not be empty and must be valid email format
4. **If `inkjin_account`**: No additional validation

### Backend (Laravel)
1. **Always required**: `payment_type` must be in: `['artist_account', 'studio_account', 'inkjin_account']`
2. **If `artist_account`**: `stripe_account_id` required, string, max:255
3. **If `studio_account`**: `studio_email` required, email format, max:255
4. **If `inkjin_account`**: No additional requirements

## Backend Logic (saveStep5)

### 1. Validation
- Use conditional validation based on `payment_type`
- Return appropriate error messages

### 2. Save Data
- Always save `payment_type`
- If `artist_account`: Save `stripe_account_id` (artist's account)
- If `studio_account`: Save `studio_email`, leave `stripe_account_id` as null (will be set when studio connects)
- If `inkjin_account`: Leave both fields as null/unchanged

### 3. Send Studio Invite Email
- **Trigger**: When `payment_type = studio_account` AND onboarding completes successfully
- **Email Template**: Use provided template
- **Email Content**:
  - Studio Name: `$userDetail->studio_name`
  - Artist Name: `$user->first_name . ' ' . $user->last_name` (or `$user->user_name`)
  - Connect Stripe Link: Generate signed URL/token for studio to connect (for now, can use placeholder or generate token)
- **Implementation**: Create Mailable class `StudioStripeInviteMail` or similar
- **Send**: Use `Mail::to($studioEmail)->send(new StudioStripeInviteMail(...))`

### 4. Complete Onboarding
- Mark step 5 as completed
- Set `on_boarding = 'yes'`
- Assign default questions (existing logic)

## Email Template Structure

**Subject**: "Connect Your Stripe Account - Inkjin"

**Body**:
```
Hi [Studio Name],

[Artist Name] has listed your studio as the payment recipient for bookings made through Inkjin.

To receive payments, please connect your Stripe account using the link below:

[Connect Stripe] (link to be generated)

Once your account is connected, payments for this artist's bookings will be sent to your studio through Inkjin.

If you already have a Stripe account, you can connect it in a few steps. If not, you can create one during the process.

If you were not expecting this email or believe it was sent in error, please ignore it.

Best,
The Inkjin Team
```

## Implementation Steps

1. **Update Step 5 View** (`resources/views/onboarding/index.blade.php`):
   - Replace single Stripe card with payment type radio buttons
   - Add conditional UI sections for each payment type
   - Add studio email input field (conditional)
   - Remove skip button

2. **Update JavaScript Validation** (`validateStep5()`):
   - Check `payment_type` is selected
   - Conditional validation based on selected type
   - Show appropriate error messages

3. **Update Backend Controller** (`OnboardingController::saveStep5()`):
   - Update validation rules (conditional)
   - Save `payment_type` and conditional fields
   - Send studio invite email if `studio_account`
   - Complete onboarding

4. **Create Email Mailable** (`app/Mail/StudioStripeInviteMail.php`):
   - Create mailable class
   - Create email view template
   - Include studio name, artist name, and connect link

5. **Create Email View** (`resources/views/emails/studio-stripe-invite.blade.php`):
   - Use provided email template content
   - Style appropriately

## Error Messages

### Frontend
- "Please select a payment type"
- "Please connect your Stripe account to proceed" (artist_account)
- "Studio email is required" (studio_account)
- "Please enter a valid email address" (studio_account, invalid format)

### Backend
- "The payment type field is required"
- "Please connect your Stripe account to complete onboarding" (artist_account)
- "The studio email field is required" (studio_account)
- "The studio email must be a valid email address" (studio_account)

## Notes
- Studio name is auto-filled from step 2 (`studio_name` field)
- Studio email is entered by user at step 5
- Email is auto-sent when user clicks "Complete Onboarding" (no separate button)
- No skip button - user must choose a payment type
- For studio option, Stripe connection happens later when studio receives email and connects
