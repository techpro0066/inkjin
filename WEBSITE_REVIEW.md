# Inkjin Website Review

Date: 2026-04-26  
Scope: Current Laravel web app (`public`, `artist`, `admin`, booking/payment-related flows)

## Executive Summary

The project has a strong foundation and clear separation between public and authenticated areas, but key production flows are still partly in demo/prototype state.

Most important gaps:
- Public booking UI still uses simulated availability and confirmation behavior in the browser.
- Public routing/controller consistency has at least one likely broken route target.
- Data model transition appears incomplete (`ArtistDesign` vs `InkJinTattoo` in bookings).
- Some sensitive configuration defaults are hardcoded in repo config.

## Current Website Structure

### 1) Public Website

Primary routes:
- `/{username}` -> public artist profile
- `/{user_name}/{tattoo_slug}` -> tattoo details
- `/{user_name}/{tattoo_slug}/book` -> booking flow

Main files:
- `routes/web.php`
- `app/Http/Controllers/InkJinController.php`
- `resources/views/public/artist.blade.php`
- `resources/views/public/tattoo.blade.php`
- `resources/views/public/book.blade.php`

### 2) Authenticated Areas

#### Artist Area (`/artist/*`)
- Dashboard
- Settings (styles, studio, calendar, preferences, payment)
- Availability management
- Portfolio and available designs
- Form builder (booking/custom request questions)

#### Admin Area (`/admin/*`)
- Dashboard
- Form/questions management

Main files:
- `routes/web.php`
- `app/Http/Controllers/QuestionsController.php`
- `resources/views/artist/*`
- `resources/views/admin/*`

### 3) Booking & Payment Related

Implemented:
- Booking listing/cancellation/rescheduling APIs and views
- Stripe Connect onboarding logic for artist payouts
- Cancellation refund logic against Stripe payment intents

Partly implemented / prototype:
- Public booking page confirmation/payment submit behavior (client-side simulated in `book.blade.php`)

## Key Findings (Prioritized)

## P0 - Critical

- **Booking flow is not fully server-backed in public booking page**
  - `resources/views/public/book.blade.php` uses front-end generated availability and confirmation-like behavior.
  - Random slot generation exists (`Math.random`) and booking reference is generated in browser (`#INK-...`).
  - Impact: users can get fake confidence of successful booking/payment if backend endpoint integration is missing.

- **Hardcoded sensitive fallback values in config**
  - `config/inkjin.php` contains fallback values for:
    - `INKJIN_CLIENT_ID`
    - `INKJIN_CLIENT_SECRET`
  - Impact: credential exposure risk and poor secret hygiene.

## P1 - High

- **Potential broken public route target**
  - Route references `InkJinController::publicArtistProfile` in `routes/web.php`.
  - That method was not found in the inspected `app/Http/Controllers/InkJinController.php`.
  - Impact: public artist profile route likely breaks unless another controller copy is being loaded unexpectedly.

- **Booking model uses legacy tattoo relation**
  - `app/Models/Booking.php` references `InkJinTattoo`/`tattoo_id`.
  - Meanwhile current public flow/design management uses `ArtistDesign`.
  - Impact: data inconsistency and integration complexity between old/new design systems.

## P2 - Medium

- **Placeholder links still present on public booking page**
  - `artist-page.html` appears multiple times in `resources/views/public/book.blade.php`.
  - Impact: broken navigation and poor UX.

- **Route ordering risk with catch-all slugs**
  - Public slug routes are at bottom (good), but `/{username}` can shadow future plain routes if route order changes carelessly.
  - Impact: regression risk during future routing updates.

- **Dashboard pages still look demo-heavy**
  - Artist/admin dashboard views appear to include static/demo metric content.
  - Impact: lower operational usefulness and credibility.

## What Is Working Well

- Clean high-level role separation (`artist`, `admin`) and onboarding gating.
- Good effort on reusable form/question system for artist-configurable booking questions.
- Availability, cancellation, and rescheduling lifecycle has clear route/controller structure.
- Stripe Connect onboarding/payout logic exists and appears thoughtfully structured.

## Recommended Action Plan

### Phase 1 (Immediate: 1-3 days)
- Remove secret fallbacks from `config/inkjin.php`; require env-based secrets only.
- Fix public route/controller mismatch for artist profile.
- Replace `artist-page.html` links with proper Laravel route links.

### Phase 2 (Core Product Reliability: 3-7 days)
- Convert public booking flow from UI simulation to backend-backed operations:
  - Fetch real availability slots from database/service.
  - Persist booking request on submit.
  - Generate booking reference server-side.
  - Integrate real payment intent/confirmation path.
- Add request validation + error display path for public booking submit.

### Phase 3 (Data Consistency: 1-2 weeks)
- Complete migration strategy from `InkJinTattoo` to `ArtistDesign` in booking domain:
  - Update booking relationships/migrations or establish compatibility layer.
  - Backfill/transform historical data where needed.
- Add integration tests for:
  - Public artist -> tattoo -> book path
  - Booking creation + payment
  - Cancellation/refund

## Suggested Test Checklist

- Public URLs:
  - artist page loads
  - tattoo page loads
  - book page loads
- Booking:
  - artist questions render correctly from DB
  - required question validation blocks progression
  - submit creates booking record
  - booking ref/email are generated server-side
- Payments:
  - card path success/failure handling
  - cancellation refund behavior
- Routing:
  - ensure slug routes do not break auth/admin/artist routes

## Notes

This review is based on current repository code and route/controller/view inspection.  
If desired, a follow-up can produce a technical remediation PR plan file with task-by-task implementation breakdown.

# InkJin Website Review (Working Draft)

Reviewed on: 2026-04-17  
Reviewer: Codex (automated pass)

## Scope Reviewed

- Route surface from `routes/web.php` (`php artisan route:list` shows 109 routes)
- Core business controllers:
  - `app/Http/Controllers/InkJinController.php`
  - `app/Http/Controllers/OnboardingController.php`
  - `app/Http/Controllers/ReschedulingController.php`
- Existing test coverage under `tests/Feature`

This is a practical first-pass review for safety, reliability, and maintainability. It is not a full penetration test.

## What Is Good

- Clear domain split: onboarding, booking, rescheduling, admin, and public profile/tattoo pages.
- Role- and onboarding-based middleware is present on most protected flows.
- Booking flow has some idempotency handling (`payment_intent_id` duplicate guard).
- Error logging exists in many critical paths.

## Findings (Highest Risk First)

### 1) Critical: Debug dumps in production booking API

**Location:** `app/Http/Controllers/InkJinController.php` (`getAvailabilitySlots`)  
**Issue:** `dd(...)` is still used for error paths (tattoo/artist/user not found).  
**Impact:** A public API request can abruptly terminate request handling and expose internal debug output; this can break frontend booking UX and leak internal details.

## Recommendation

- Replace all `dd(...)` calls with structured JSON error responses (`404`/`422`) plus server-side logs.

---

### 2) Critical: Payment amount can be client-controlled

**Location:** `app/Http/Controllers/InkJinController.php` (`createPaymentIntent`)  
**Issue:** Endpoint accepts `amount` from request input and uses it directly to create Stripe PaymentIntent.  
**Impact:** A malicious client can try to pay a lower amount than expected if frontend is tampered with.

## Recommendation

- Recompute amount server-side from tattoo + artist payout configuration.
- Ignore or strictly verify any client-sent amount against server-calculated amount before creating PaymentIntent.

---

### 3) Critical: Booking confirmation does not verify payment intent from Stripe

**Location:** `app/Http/Controllers/InkJinController.php` (`confirmBooking`)  
**Issue:** Booking is confirmed from request payload without retrieving/validating Stripe PaymentIntent status/amount/currency server-side.
**Impact:** Forged requests could potentially create confirmed bookings with invalid or mismatched payment state.

## Recommendation

- Retrieve PaymentIntent from Stripe using secret key during confirmation.
- Enforce:
  - `status` is succeeded (or valid capture state),
  - amount/currency match expected values,
  - metadata ties to tattoo and intended artist/user,
  - intent was not reused for a different booking context.

---

### 4) High: Public booking/payment endpoints have no explicit throttling

**Location:** `routes/web.php` (`/api/booking/*`, `/api/availability/*`, consultation/session slot endpoints)  
**Issue:** Public API endpoints are open without explicit rate limiting middleware.
**Impact:** Abuse can cause spam, brute-force attempts, and service degradation.

## Recommendation

- Add rate limiting middleware (e.g., `throttle`) to all public booking/availability endpoints.
- Consider bot controls (captcha/challenge) where guest booking is allowed.

---

### 5) Medium: Internal exception text is returned to clients

**Location:** `app/Http/Controllers/OnboardingController.php` (multiple catch blocks)  
**Issue:** Responses include `'An error occurred: ' . $e->getMessage()`.
**Impact:** Internal details can leak to end users and attackers (stack context, SQL hints, integration details).

## Recommendation

- Return generic user-facing messages.
- Keep full exception detail only in logs.

---

### 6) Medium: Blocking `sleep(...)` in request lifecycle

**Location:** `app/Http/Controllers/InkJinController.php` (`confirmBooking`)  
**Issue:** `sleep(3)` and retry-delay sleeps run in HTTP request path.
**Impact:** Higher latency and reduced throughput under load; can amplify timeout risk.

## Recommendation

- Move email sending/retries to queued jobs.
- Return booking response immediately, process notifications async.

---

### 7) Medium: Automated tests do not cover booking/onboarding critical flows

**Location:** `tests/Feature`  
**Issue:** Current tests are mainly auth/profile defaults; booking, payment, onboarding step transitions, and public API behavior are not covered.
**Impact:** Regressions in highest-value flows are likely to reach production.

## Recommendation

- Add feature tests for:
  - booking intent creation and confirmation guards,
  - onboarding step gating and completion,
  - rescheduling authorization rules,
  - public route collision/catch-all route behavior.

## Quick Priority Plan

### Next 24 hours

1. Remove all `dd(...)` from public APIs.
2. Add Stripe PaymentIntent verification in `confirmBooking`.
3. Recompute payment amount server-side in `createPaymentIntent`.

### Next 2-4 days

1. Add throttling to public booking/availability endpoints.
2. Replace direct exception output with safe generic messages.
3. Queue booking emails and retries.

### Next 1-2 weeks

1. Add critical feature tests for booking/onboarding/rescheduling.
2. Add operational monitoring for booking failures and payment mismatch alerts.

## Notes

- This review is intentionally action-oriented and focused on revenue/security-critical paths first.
- A deeper phase-2 review can cover blade templates, JS form hardening, DB constraints, and deployment/security headers.
