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
