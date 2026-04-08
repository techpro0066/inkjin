# InkJin Web Application — Summary

This document is a high-level map of the **InkJin** Laravel application: what it does, how it is structured, and which integrations it uses. Use it as onboarding or reference reading; it is not a substitute for reading specific controllers or migrations when changing behavior.

---

## Purpose

**InkJin** is a web application for **tattoo artists and clients**. It:

- Pulls **artist and tattoo catalog data** from a remote **InkJin API** (OAuth2 client credentials).
- Stores **local application state** in MySQL (or your configured database): user profiles, onboarding, availability, bookings, questions, and cached InkJin entities where applicable.
- Supports **public artist and tattoo pages**, **booking flows** (including deposits via **Stripe** payment intents), **Google Calendar** connectivity for artists, **rescheduling** and **cancellation** flows with email notifications, and **role-based** access (admin, artist, end user).

The home URL `/` redirects to the **login** page.

---

## Technology Stack

| Layer | Choice |
|--------|--------|
| Backend | **PHP 8.2+**, **Laravel 12** |
| Frontend assets | **Vite 7**, **Tailwind CSS 3**, **Alpine.js** |
| Auth UI | **Laravel Breeze**-style flows (login, register, verification, password reset) |
| HTTP client | Laravel `Http` facade (InkJin API) |
| Payments | **Stripe** (`stripe/stripe-php`) — Connect and payment intents |
| Calendar | **Google API Client** — OAuth for Calendar |
| Mail | **Symfony Postmark mailer** (configurable via Laravel mail) |

Composer scripts include `dev` (concurrently: `php artisan serve`, queue worker, `npm run dev`) and `setup` (install, migrate, npm build).

---

## User Model & Access Control

### Roles (`users.role`)

- **`admin`** — Admin question management and user listing (`admin` middleware).
- **`artist`** — Onboarding, settings, availability, custom questions, dashboard as artist.
- **`user`** — Browses artists/tattoos in-app (dashboard routes under `dashboard.artists`).

Middleware aliases (see `bootstrap/app.php`): `onboarding`, `artist`, `admin`, `user`.

### Onboarding (`users.on_boarding`)

- **`on_boarding`** is `yes` or `no` (enum).
- **`CheckOnboarding`** middleware: if not `yes`, users are redirected to **`/onboarding`** (except onboarding routes).
- **Registration** (`RegisteredUserController`): **artists** start with `on_boarding = no`; **admin** and **user** start with `on_boarding = yes`.
- If the registering email exists in **`inkjin_artists`**, the user gets `on_app = 1` and `app_id` set to that artist row for linking to the InkJin ecosystem.

### Email verification

Users implement `MustVerifyEmail`. Many routes use `verified` middleware. Profile routes use `auth` + `onboarding` **without** `verified` so users can update email before verifying.

---

## Artist Onboarding (Multi-Step)

Handled by **`OnboardingController`** and views under `resources/views/onboarding/`.

| Step | Purpose |
|------|---------|
| 1 | Profile: avatar, name, username, mobile |
| 2 | Studio address and location fields |
| 3 | Scheduling type (auto vs managed) |
| 4 | Preferences (timezone, deposits, cancellation window, consultation options, etc.) |
| 5 | Payments — Stripe Connect; studio-level payment flows where applicable |

Related UX: **`/studio/waiting`**, resend studio invite, **Google Calendar** OAuth routes, **Stripe Connect** connect/callback/status/disconnect.

---

## Core Domain (Database)

Main tables (from migrations; names may vary slightly):

- **`users`** — Identity, role, onboarding flag, optional link to InkJin app (`on_app`, `app_id`).
- **`user_details`** — Extended profile, studio fields, Google tokens, Stripe account id, scheduling/payment preferences, `current_step` / `completed_steps`, optional **`studio_id`** and **`studio_payment_status`**.
- **`studios`** — Shared studio record (name, email, `stripe_account_id`).
- **`bookings`** — Full booking lifecycle: times (UTC), consultation fields, payment/deposit/refund fields, reschedule metadata, Google Calendar event id, etc.
- **`availabilities`** / **`availability_overrides`** — Artist scheduling.
- **`questions`** — Global question definitions (admin).
- **`user_questions`** — Artist-specific question instances.
- **`inkjin_artists`** / **`inkjin_tattoos`** — Local cache or mirror of InkJin entities for DB-backed routes.

---

## Major Feature Areas

### InkJin API integration

- **`App\Services\InkJinApiService`** — Obtains a cached **Bearer token** (`client_credentials`), calls JSON endpoints on `config('inkjin.api_url')` with credentials from **`INKJIN_CLIENT_ID`**, **`INKJIN_CLIENT_SECRET`**, etc. (`config/inkjin.php`).
- **`InkJinController`** — Public JSON proxies (`/api/...`), DB-backed fallbacks (`/db/...`), public artist/tattoo pages, booking slot APIs, payment intent and booking confirmation, consultation vs session slot endpoints.

### Public pages (SEO / sharing)

- **`/{username}`** — Public artist profile (`public.artist`). **Registered last** in `routes/web.php` except for more specific routes — order matters.
- **`/{artist_name}/{tattoo_name}/{tattoo_id}`** — Public tattoo page (`public.tattoo`), `tattoo_id` numeric.
- Authenticated booking UI: **`/tattoo/{...}/book`** route for logged-in users who completed onboarding.

### Bookings

- **`BookingsController`** — Listing for authenticated users.
- **`BookingCancellationController`** + **`CancellationService`** — Cancel / no-show APIs.
- **`ReschedulingController`** + **`ReschedulingService`** — Reschedule checks, artist-initiated reschedule, decline, web flows (`bookings/{id}/reschedule`, `reschedule-flow`).
- Mailables: confirmation, cancellation, reschedule request/confirmation/declined, etc.

### Artist tools

- **`AvailabilityController`** — CRUD availability and overrides.
- **`QuestionsController`** — Artist questions.
- **Settings** (closures + `OnboardingController` updates): studio, calendar, preferences, payment.

### Admin

- **`Admin\QuestionController`** — CRUD global questions.
- **`Admin\UserController`** — User listing and detail.

### Profile

- **`ProfileController`** — Edit/update/delete account (`ProfileUpdateRequest`).

### Countries

- **`CountriesController`** — `/api/countries`, `/api/cities`, `/api/countries/all` for forms.

---

## Integrations (Environment)

Typical variables (see `.env.example` in your repo and `config/*.php`):

- **InkJin**: `INKJIN_API_URL`, `INKJIN_CLIENT_ID`, `INKJIN_CLIENT_SECRET`, optional pagination/token cache settings.
- **Google**: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GOOGLE_PLACE_API_KEY`.
- **Stripe**: Used in Connect and booking flows (check `StripeConnectController` and `InkJinController` / config).
- **Mail**: e.g. Postmark token in `config/services.php` / `config/mail.php`.

**Security note:** Prefer setting secrets only in `.env`. Review `config/inkjin.php` for any default credentials in source control and rotate if exposed.

---

## HTTP Routes (Conceptual)

- **`routes/web.php`** — Almost all web and public API surface; auth routes in **`routes/auth.php`** (included from `web.php`).
- Public **no-auth** APIs: availability slots, booking submit, payment intent, consultation/session slot APIs, public artist username route.
- **Auth + verified** groups: onboarding, Google/Stripe OAuth, dashboard, bookings, rescheduling, admin, artist, user dashboard sections.
- **Catch-all** `/{username}` must stay after specific routes to avoid shadowing paths like `verify-email/...`.

---

## Helpers & Assets

- **`app/helpers.php`** — Autoloaded helpers (e.g. shared formatting or upload helpers used by onboarding).
- **`resources/js`**, **`resources/css`** — Built with Vite; Tailwind forms plugin.

---

## Tests

Under **`tests/Feature`**: auth (login, registration, verification, password reset/update, confirmation), profile, and example tests. Coverage is **Breeze/auth-focused**; most booking and InkJin flows are not represented in tests from the default skeleton.

---

## Suggested Reading Order (for Developers)

1. `routes/web.php` + `routes/auth.php`
2. `app/Http/Middleware/Check*.php`
3. `app/Http/Controllers/InkJinController.php` (large — skim public actions first)
4. `app/Services/InkJinApiService.php`
5. `app/Http/Controllers/OnboardingController.php` (step saves)
6. `app/Models/Booking.php` + booking-related migrations
7. `app/Http/Controllers/StripeConnectController.php` and `GoogleCalendarController.php`

---

*Generated as a static snapshot of the repository structure and routes. Update this file when you add major modules or change integration boundaries.*
