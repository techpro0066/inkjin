# InkJin Website - Complete Flow Review

## Overview
InkJin is a Laravel-based tattoo artist management platform that allows artists to manage their profiles, availability, bookings, and integrates with Google Calendar and Stripe for payments.

---

## 1. Application Entry Point & Routing

### Entry Point
- **File**: `public/index.php`
- Bootstrap Laravel application and handle incoming requests
- Routes defined in `routes/web.php` and `routes/auth.php`

### Root Route
- `/` → Redirects to `/login`

---

## 2. Authentication Flow

### 2.1 Registration (`/register`)
**Controller**: `RegisteredUserController`

**Flow**:
1. User fills registration form (name, email, password, role)
2. System validates:
   - Email uniqueness
   - Password confirmation
   - Role must be: `admin`, `artist`, or `user`
3. **Special Logic**: Checks if email exists in `inkjin_artists` table
   - If found: Sets `on_app = 1` and `app_id = artist_id`
   - If not: Sets `on_app = 0` and `app_id = null`
4. **Onboarding Status**:
   - Artists: `on_boarding = 'no'` (must complete onboarding)
   - Others: `on_boarding = 'yes'` (skip onboarding)
5. User is logged in automatically
6. Email verification notification sent
7. Redirected to email verification page (`/verify-email`)

**Key Files**:
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `resources/views/auth/register.blade.php`

### 2.2 Login (`/login`)
**Controller**: `AuthenticatedSessionController`

**Flow**:
1. User enters email and password
2. Laravel authentication validates credentials
3. If successful:
   - User session created
   - Redirect based on onboarding status (see middleware section)

**Key Files**:
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `resources/views/auth/login.blade.php`

### 2.3 Email Verification
**Flow**:
1. After registration, user receives verification email
2. User clicks verification link: `/verify-email/{id}/{hash}`
3. Email verified → User can proceed
4. If not verified, user sees verification notice page

**Middleware**: `verified` - Ensures email is verified before accessing protected routes

---

## 3. Middleware Chain

### 3.1 CheckOnboarding Middleware
**File**: `app/Http/Middleware/CheckOnboarding.php`

**Logic**:
- Checks if user is authenticated
- If `user.on_boarding !== 'yes'` AND not on onboarding page → Redirect to `/onboarding`
- Allows access to onboarding routes even if not completed

**Applied to**: Most authenticated routes (except onboarding routes themselves)

### 3.2 CheckArtist Middleware
**File**: `app/Http/Middleware/CheckArtist.php`

**Logic**:
- Checks if user is authenticated
- Verifies `user.role === 'artist'`
- If not artist → 403 Forbidden

**Applied to**: Artist-specific routes

### 3.3 CheckAdmin Middleware
**File**: `app/Http/Middleware/CheckAdmin.php`

**Logic**:
- Checks if user is authenticated
- Verifies `user.role === 'admin'`
- If not admin → 403 Forbidden

**Applied to**: Admin-specific routes

---

## 4. Onboarding Flow (Artists Only)

**Route**: `/onboarding`
**Controller**: `OnboardingController`

### Step 1: Complete Profile
**Route**: `POST /onboarding/step/1`

**Fields**:
- Avatar (image upload)
- Username (unique)
- Mobile number (unique)
- Country
- City

**Actions**:
- Creates/updates `UserDetail` record
- Uploads avatar to `public/uploads/avatars/`
- Sets `current_step = 2`
- Marks step 1 as completed

### Step 2: Studio Information
**Route**: `POST /onboarding/step/2`

**Fields**:
- Studio name
- Studio address
- Google Maps link (optional)

**Actions**:
- Updates `UserDetail` with studio info
- Sets `current_step = 3`
- Marks step 2 as completed

### Step 3: Calendar Connection
**Route**: `POST /onboarding/step/3`

**Flow**:
- User can optionally connect Google Calendar
- If connected: Stores OAuth tokens in `user_details.google_calendar_token`
- If not connected: Can proceed (optional step)
- Sets `current_step = 4`
- Marks step 3 as completed

**Google Calendar Integration**:
- OAuth redirect: `/auth/google-calendar`
- Callback: `/auth/google-calendar/callback`
- Stores tokens and primary calendar ID

### Step 4: Preferences
**Route**: `POST /onboarding/step/4`

**Fields**:
- Currency
- Timezone
- Date/time format
- Minimum deposit amount
- Minimum deposit type
- Cancellation window
- Reschedule times
- Session buffer period
- Require consultation (boolean)

**Actions**:
- Updates `UserDetail` with preferences
- Sets `current_step = 5`
- Marks step 4 as completed

### Step 5: Payments (Stripe Connect)
**Route**: `POST /onboarding/step/5`

**Flow**:
1. User clicks "Connect Stripe"
2. Redirected to `/connect-stripe`
3. Stripe Express account created (if not exists)
4. User redirected to Stripe onboarding/login link
5. After completion, callback to `/connect-stripe/callback`
6. System verifies account status:
   - If `charges_enabled` and `payouts_enabled` → Complete
   - Otherwise → In progress
7. On completion:
   - `stripe_account_id` saved to `UserDetail`
   - `user.on_boarding = 'yes'` (marks onboarding complete)
   - All active default questions assigned to user
   - Redirects to `/dashboard`

**Stripe Integration**:
- Creates Express account (country: AE)
- Stores `stripe_account_id` in `user_details`
- Supports account onboarding and login links

---

## 5. Dashboard

**Route**: `/dashboard`
**Controller**: `DashboardController`
**Middleware**: `auth`, `verified`, `onboarding`

**Flow**:
1. User must be authenticated, verified, and completed onboarding
2. **Admin View**:
   - Shows statistics:
     - Total users (non-admin)
     - Total regular users
     - Total artists
3. **Artist/User View**:
   - Standard dashboard (content depends on view implementation)

**Key Files**:
- `app/Http/Controllers/DashboardController.php`
- `resources/views/dashboard.blade.php`

---

## 6. Artist Features

### 6.1 Settings Pages

All settings routes require: `auth`, `verified`, `onboarding`, `artist` middleware

#### Profile Settings (`/settings/profile`)
- View: `artist.settings.profile`
- Update: `POST /settings/profile`
- Fields: Same as onboarding step 1 (avatar, username, mobile, country, city)

#### Studio Settings (`/settings/studio`)
- View: `artist.settings.studio`
- Displays studio information (read-only view)

#### Calendar Settings (`/settings/calendar`)
- View: `artist.settings.calendar`
- Shows Google Calendar connection status
- Can connect/disconnect Google Calendar

#### Preferences Settings (`/settings/preferences`)
- View: `artist.settings.preferences`
- Update: `POST /settings/preferences`
- Fields: Same as onboarding step 4
- Uses same controller method as onboarding step 3 (`saveStep3`)

### 6.2 Availability Management (`/availability`)
**Controller**: `AvailabilityController`

**Features**:
- View weekly availability schedule
- Add/edit availability slots per day of week
- Timezone-aware (converts between user timezone and UTC)
- Date overrides:
  - Mark specific dates as unavailable
  - Override availability for specific dates
  - Add notes to overrides

**API Endpoints**:
- `GET /availability` - View availability page
- `POST /availability` - Save availability
- `DELETE /availability/{id}` - Delete availability slot
- `POST /availability/override` - Create/update date override
- `GET /availability/override` - Get override for specific date
- `DELETE /availability/override/{id}` - Delete date override

**Data Storage**:
- `availabilities` table: Weekly recurring availability
- `availability_overrides` table: Date-specific overrides

### 6.3 Questions Management (`/questions`)
**Controller**: `QuestionsController`

**Features**:
- View all user questions
- Create custom questions
- Update questions
- Delete questions
- Questions have status: `active` or `inactive`

**API Endpoints**:
- `GET /questions` - List questions
- `POST /questions` - Create question
- `PUT /questions/{id}` - Update question
- `DELETE /questions/{id}` - Delete question

**Data Model**:
- `user_questions` table: User-specific questions
- On onboarding completion, all active default questions are assigned

---

## 7. Admin Features

**Middleware**: `auth`, `verified`, `onboarding`, `admin`

### 7.1 Questions Management (`/admin/questions`)
**Controller**: `Admin\QuestionController`

**Features**:
- View all default questions
- Create default questions (assigned to new artists on onboarding)
- Update default questions
- Delete default questions

**API Endpoints**:
- `GET /admin/questions` - List questions
- `POST /admin/questions` - Create question
- `PUT /admin/questions/{id}` - Update question
- `DELETE /admin/questions/{id}` - Delete question

### 7.2 User Management (`/admin/users`)
**Controller**: `Admin\UserController`

**Features**:
- View all users
- View user details (`/admin/users/{id}`)

---

## 8. Public Pages

### 8.1 Public Artist Profile
**Routes**:
- `/{username}` - Uses InkJin API service
- `/artist/{username}` - Uses local database

**Controller**: `InkJinController`

**Flow**:
1. Fetches artist by username (from API or database)
2. Gets artist's tattoos
3. Displays artist profile with:
   - Profile picture
   - Display name
   - Description
   - Social links (Instagram, TikTok, Website)
   - Studio information
   - Tattoo gallery

**Key Methods**:
- `publicArtistProfile()` - API-based
- `publicArtistProfileFromDb()` - Database-based

### 8.2 Public Tattoo Page
**Routes**:
- `/{artist_name}/{tattoo_name}/{tattoo_id}` - Uses InkJin API service
- `/tattoo/{artist_display_name}/{tattoo_title}/{tattoo_id}` - Uses local database

**Controller**: `InkJinController`

**Flow**:
1. Fetches tattoo by ID
2. Validates artist name and tattoo name slugs match
3. If mismatch → 301 redirect to correct URL
4. Displays tattoo with:
   - Image
   - Title
   - Description
   - Tags
   - Style information
   - Artist information

**Key Methods**:
- `publicTattooPage()` - API-based
- `publicTattooPageFromDb()` - Database-based

### 8.3 Public Artists List
**Route**: `/artists`

**Controller**: `InkJinController::publicArtistsList()`

**Flow**:
1. Fetches all artists from database
2. Orders by display name, then username
3. Displays list with:
   - Profile picture
   - Display name
   - Studio
   - Location (city, country)
   - Tattoo count

---

## 9. API Routes

### 9.1 InkJin API Routes (External API)
- `GET /api/tattoo/{id}` - Get tattoo from InkJin API
- `GET /api/artist/{id}` - Get artist from InkJin API

**Service**: `InkJinApiService` - Handles API communication

### 9.2 Database API Routes (Local Database)
- `GET /db/tattoo/{id}` - Get tattoo from local database
- `GET /db/artist/{id}` - Get artist from local database

---

## 10. Data Models & Relationships

### 10.1 User Model
**Table**: `users`

**Key Fields**:
- `id`, `name`, `email`, `password`
- `role`: `admin`, `artist`, `user`
- `on_boarding`: `yes` or `no`
- `on_app`: boolean (linked to InkJin artists)
- `app_id`: Reference to `inkjin_artists.id`

**Relationships**:
- `hasOne(UserDetail)` - User details
- `hasMany(Availability)` - Availability slots
- `hasMany(UserQuestion)` - User questions

### 10.2 UserDetail Model
**Table**: `user_details`

**Key Fields**:
- Profile: `user_name`, `mobile_number`, `country`, `city`, `avatar`
- Studio: `studio_name`, `studio_address`, `google_maps_link`
- Calendar: `google_calendar_token`, `google_calendar_id`
- Preferences: `currency`, `timezone`, `date_time_format`, etc.
- Payments: `stripe_account_id`
- Onboarding: `current_step`, `completed_steps` (JSON array)

**Relationships**:
- `belongsTo(User)`

### 10.3 Availability Models
**Tables**: `availabilities`, `availability_overrides`

**Fields**:
- `availabilities`: `user_id`, `day_of_week`, `start_time`, `end_time` (UTC)
- `availability_overrides`: `user_id`, `override_date`, `start_time`, `end_time`, `is_unavailable`, `notes`

### 10.4 Question Models
**Tables**: `questions`, `user_questions`

**Fields**:
- `questions`: Default questions (managed by admin)
- `user_questions`: User-specific questions

### 10.5 InkJin Models
**Tables**: `inkjin_artists`, `inkjin_tattoos`

**Purpose**: Store imported data from InkJin API/CSV

**Relationships**:
- `InkJinArtist` hasMany `InkJinTattoo`

---

## 11. Integration Flows

### 11.1 Google Calendar Integration

**OAuth Flow**:
1. User clicks "Connect Google Calendar"
2. Redirected to `/auth/google-calendar`
3. Google OAuth consent screen
4. User grants permissions
5. Callback to `/auth/google-calendar/callback`
6. System exchanges code for tokens
7. Fetches primary calendar ID
8. Stores tokens and calendar ID in `user_details`

**Disconnect**:
- `POST /auth/google-calendar/disconnect`
- Clears tokens and calendar ID

**Token Refresh**:
- `refreshToken()` method handles expired tokens

### 11.2 Stripe Connect Integration

**Onboarding Flow**:
1. User clicks "Connect Stripe" in onboarding step 5
2. Redirected to `/connect-stripe`
3. System creates Stripe Express account (if not exists)
4. User redirected to Stripe onboarding link
5. User completes Stripe account setup
6. Callback to `/connect-stripe/callback`
7. System verifies account status
8. If complete → Onboarding marked as done

**Post-Onboarding**:
- User can access Stripe dashboard via login link
- `GET /connect-stripe/status` - Check account status
- `POST /connect-stripe/disconnect` - Disconnect account

---

## 12. Profile Management

**Route**: `/profile`
**Controller**: `ProfileController`
**Middleware**: `auth`, `onboarding` (email verification not required)

**Features**:
- Edit profile information
- Update password
- Delete account

**Key Files**:
- `app/Http/Controllers/ProfileController.php`
- `resources/views/profile/edit.blade.php`

---

## 13. Security & Access Control

### 13.1 Route Protection
- **Public Routes**: Public artist/tattoo pages, API routes
- **Guest Routes**: Login, register, password reset
- **Authenticated Routes**: Dashboard, settings, availability
- **Verified Routes**: Dashboard, admin routes (require email verification)
- **Role-Based Routes**: Artist routes, admin routes

### 13.2 Middleware Stack
1. `auth` - Must be logged in
2. `verified` - Email must be verified
3. `onboarding` - Onboarding must be completed (for artists)
4. `artist` - Must have artist role
5. `admin` - Must have admin role

### 13.3 Data Validation
- All form submissions validated
- Unique constraints on username, mobile number, email
- File upload validation (images only, size limits)
- CSRF protection on all POST requests

---

## 14. Key Features Summary

### For Artists:
1. ✅ Multi-step onboarding process
2. ✅ Profile management
3. ✅ Studio information management
4. ✅ Google Calendar integration
5. ✅ Stripe Connect for payments
6. ✅ Availability management (weekly + date overrides)
7. ✅ Custom questions management
8. ✅ Preferences/settings management

### For Admins:
1. ✅ User management
2. ✅ Default questions management
3. ✅ Dashboard with statistics

### For Public:
1. ✅ Artist profile pages
2. ✅ Tattoo detail pages
3. ✅ Artists listing page
4. ✅ API endpoints for data access

---

## 15. Potential Issues & Recommendations

### 15.1 Issues Identified

1. **Onboarding Step 3 Logic**:
   - `saveStep3()` handles both calendar connection AND preferences update
   - Could be confusing - consider separating concerns

2. **Stripe Account Creation**:
   - Hardcoded country `'AE'` in `StripeConnectController`
   - Should use user's country from profile

3. **Timezone Handling**:
   - Availability times stored in UTC but converted for display
   - Ensure consistent timezone handling across all features

4. **Route Ordering**:
   - Public routes with dynamic segments (`/{username}`) must be at the end
   - Current order looks correct, but be careful with new routes

5. **Email Verification**:
   - Profile routes don't require email verification
   - This is intentional (allows email update), but ensure security

6. **Avatar Upload**:
   - Old avatars deleted but no cleanup of orphaned files
   - Consider implementing cleanup job

### 15.2 Recommendations

1. **Error Handling**:
   - Add more user-friendly error messages
   - Implement proper logging for debugging

2. **Validation**:
   - Add more robust validation for phone numbers
   - Validate timezone values

3. **Performance**:
   - Consider caching for public artist/tattoo pages
   - Optimize database queries (eager loading where needed)

4. **Testing**:
   - Add feature tests for onboarding flow
   - Test integration flows (Google Calendar, Stripe)

5. **Documentation**:
   - API documentation for public endpoints
   - User guides for artists

---

## 16. File Structure Summary

### Controllers
- `OnboardingController` - Handles onboarding steps
- `DashboardController` - Dashboard display
- `InkJinController` - Public pages and API endpoints
- `AvailabilityController` - Availability management
- `QuestionsController` - Artist questions
- `GoogleCalendarController` - Google Calendar OAuth
- `StripeConnectController` - Stripe Connect integration
- `ProfileController` - User profile management
- `Admin\QuestionController` - Admin questions management
- `Admin\UserController` - Admin user management

### Models
- `User` - User authentication and basic info
- `UserDetail` - Extended user information
- `Availability` - Weekly availability slots
- `AvailabilityOverride` - Date-specific overrides
- `Question` - Default questions (admin)
- `UserQuestion` - User-specific questions
- `InkJinArtist` - Imported artist data
- `InkJinTattoo` - Imported tattoo data

### Middleware
- `CheckOnboarding` - Enforces onboarding completion
- `CheckArtist` - Enforces artist role
- `CheckAdmin` - Enforces admin role

### Services
- `InkJinApiService` - Handles external API communication

---

## 17. Complete User Journey

### New Artist Registration:
1. Visit `/register`
2. Fill registration form (role: artist)
3. Email verification sent
4. Verify email
5. Redirected to `/onboarding` (step 1)
6. Complete profile (step 1)
7. Add studio information (step 2)
8. Connect Google Calendar (optional, step 3)
9. Set preferences (step 4)
10. Connect Stripe (step 5)
11. Onboarding complete → Redirected to `/dashboard`
12. Can now manage availability, questions, settings

### Existing Artist Login:
1. Visit `/login`
2. Enter credentials
3. If onboarding complete → `/dashboard`
4. If onboarding incomplete → `/onboarding` (resume from current step)

### Public User:
1. Visit `/artists` - Browse artists
2. Click artist → `/artist/{username}` - View profile
3. Click tattoo → `/tattoo/{artist}/{tattoo}/{id}` - View tattoo details

---

## Conclusion

The InkJin platform has a well-structured flow with clear separation of concerns:
- **Authentication & Authorization**: Properly implemented with middleware
- **Onboarding**: Multi-step process for artists with integrations
- **Public Pages**: Clean URL structure for SEO
- **Artist Features**: Comprehensive management tools
- **Admin Features**: User and content management
- **Integrations**: Google Calendar and Stripe Connect properly integrated

The application follows Laravel best practices and has a logical flow from registration to full feature access.

