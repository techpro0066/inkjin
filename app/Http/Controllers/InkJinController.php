<?php

namespace App\Http\Controllers;

use App\Models\InkJinArtist;
use App\Models\InkJinTattoo;
use App\Models\ArtistDesign;
use App\Models\User;
use App\Models\Availability;
use App\Services\InkJinApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Http\Controllers\GoogleCalendarController;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\ConsentAnswer;
use App\Services\BookingConsentService;
use App\Mail\BookingConfirmationMail;
use App\Mail\ManagedBookingRequestArtistMail;
use App\Mail\ManagedBookingRequestMail;
use App\Mail\UserWelcomeMail;
use App\Services\CancellationService;
use App\Services\GoogleCalendarBookingSyncService;
use App\Exceptions\GoogleCalendarEventRequiredException;
use App\Services\ArtistPayoutService;
use App\Services\BookingCheckoutPricingService;
use App\Models\UserDetail;
use App\Models\GuestSpot;
use App\Models\Studio;
use App\Models\UserStudio;
use App\Models\Portfolio;
use App\Models\Waitlist;
use App\Models\Question;
use Illuminate\Support\Facades\Schema;
use App\Models\QuestionSorting;
use App\Models\Placement;
use App\Models\Style;
use App\Models\UserQuestion;
use App\Support\ArtistStudioChoices;
use App\Support\PaymentMethods;
use App\Models\PendingVivaPayment;
use App\Services\VivaCheckoutService;
use App\Services\PublicBookingEmailVerificationService;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class InkJinController extends Controller
{
    private function makePostBookingAccessUrl(User $bookingUser, Booking $booking): string
    {
        return URL::temporarySignedRoute(
            'user.post-booking.access',
            now()->addDays(14),
            ['user' => $bookingUser->id, 'booking' => $booking->id]
        );
    }

    private function bookingConsentPublicUrl(Booking $booking): ?string
    {
        $consent = ConsentAnswer::query()->where('booking_id', $booking->id)->first();
        if (! $consent) {
            try {
                $consent = app(BookingConsentService::class)->syncForBooking($booking);
            } catch (\Throwable $e) {
                Log::warning('Unable to sync consent for booking confirmation URL', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        if (! $consent || in_array((string) $consent->status, [
            ConsentAnswer::STATUS_CANCELLED,
            ConsentAnswer::STATUS_EXPIRED,
        ], true)) {
            return null;
        }

        return $consent->publicUrl();
    }

    private function makePostManagedRequestAccessUrl(User $user, BookingRequest $bookingRequest): string
    {
        return URL::temporarySignedRoute(
            'user.post-managed-request.access',
            now()->addDays(14),
            ['user' => $user->id, 'bookingRequest' => $bookingRequest->id]
        );
    }

    /**
     * Artist payout preference label for PaymentIntent metadata (bookings always settle on the platform account).
     */
    private function artistPayoutPreferenceLabel(UserDetail $userDetail): string
    {
        $paymentType = (string) ($userDetail->payment_type ?? 'inkjin_account');
        if (! in_array($paymentType, ['artist_account', 'studio_account', 'inkjin_account'], true)) {
            return 'inkjin_account';
        }

        return $paymentType;
    }

    /**
     * Full-day unavailability from availability_overrides + guest spot away/buffer windows.
     */
    private function artistLocalDateIsBlocked(int $artistUserId, string $ymd, ?int $exceptGuestSpotId = null): bool
    {
        return app(\App\Services\ManagedRequestBookingService::class)
            ->artistLocalDateIsBlocked($artistUserId, $ymd, $exceptGuestSpotId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: ?\App\Models\GuestSpot, 1: ?int, 2: ?string} guest spot, studios.id, error message
     */
    private function resolveGuestSpotBookingContext(int $artistUserId, array $payload): array
    {
        $guestSpot = \App\Models\GuestSpot::resolveReservableForArtist(
            $payload['guest_spot_id'] ?? null,
            $artistUserId
        );

        if (! empty($payload['guest_spot_id']) && ! $guestSpot) {
            return [null, null, 'This guest spot is no longer available to book.'];
        }

        if (! $guestSpot) {
            return [null, ArtistStudioChoices::resolveStudiosTableIdFromPayload($artistUserId, $payload), null];
        }

        $studioId = $guestSpot->studio_id ? (int) $guestSpot->studio_id : null;

        return [$guestSpot, $studioId, null];
    }

    private function guestSpotRejectsDate(?\App\Models\GuestSpot $guestSpot, string $ymd): ?string
    {
        if (! $guestSpot || $ymd === '') {
            return null;
        }

        if (! $guestSpot->containsBookableDate($ymd)) {
            return 'Selected dates must fall within this guest spot ('
                .$guestSpot->from_date->format('M j, Y')
                .' – '
                .$guestSpot->to_date->format('M j, Y')
                .').';
        }

        return null;
    }

    /**
     * Append busy local-time intervals for one booking (confirmed sessions block new picks).
     * Extends each segment by $bufferAfterMinutes (session buffer) after the booking ends so gaps are enforced.
     *
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendBookingOccupancyToBusyMap(Booking $booking, string $artistTz, array &$map, int $bufferAfterMinutes = 0): void
    {
        $timing = strtolower((string) ($booking->consultation_timing_type ?? 'combined'));
        if ($timing !== 'separate') {
            $timing = 'combined';
        }

        $hasConsult = (bool) $booking->has_consultation;

        if ($hasConsult && $timing === 'separate'
            && $booking->consultation_date
            && $booking->consultation_start_time_utc
            && $booking->consultation_end_time_utc) {
            $cd = $booking->consultation_date instanceof \Carbon\CarbonInterface
                ? $booking->consultation_date->format('Y-m-d')
                : (string) $booking->consultation_date;
            $this->appendUtcRangeToBusyMap(
                $map,
                $cd,
                (string) $booking->consultation_start_time_utc,
                (string) $booking->consultation_end_time_utc,
                $artistTz,
                $bufferAfterMinutes
            );
        }

        if (!$booking->booking_date || !$booking->start_time_utc || !$booking->end_time_utc) {
            return;
        }

        $bd = $booking->booking_date instanceof \Carbon\CarbonInterface
            ? $booking->booking_date->format('Y-m-d')
            : (string) $booking->booking_date;

        if ($hasConsult && $timing === 'separate') {
            $this->appendUtcRangeToBusyMap($map, $bd, (string) $booking->start_time_utc, (string) $booking->end_time_utc, $artistTz, $bufferAfterMinutes);

            return;
        }

        $this->appendUtcRangeToBusyMap($map, $bd, (string) $booking->start_time_utc, (string) $booking->end_time_utc, $artistTz, $bufferAfterMinutes);
    }

    /**
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendUtcRangeToBusyMap(array &$map, string $ymd, string $startUtc, string $endUtc, string $tz, int $bufferAfterMinutes = 0): void
    {
        try {
            $startAt = Carbon::parse($ymd.' '.$startUtc, 'UTC')->timezone($tz);
            $endAt = Carbon::parse($ymd.' '.$endUtc, 'UTC')->timezone($tz);
        } catch (\Throwable) {
            return;
        }

        if ($bufferAfterMinutes > 0) {
            $endAt = $endAt->copy()->addMinutes(max(0, $bufferAfterMinutes));
        }

        if ($endAt <= $startAt) {
            return;
        }

        $d = $startAt->copy()->startOfDay();
        $lastDay = $endAt->copy()->startOfDay();
        $guard = 0;

        while ($d->lte($lastDay) && $guard++ < 14) {
            $dayStart = $d->copy()->startOfDay();
            $dayEndExclusive = $d->copy()->addDay()->startOfDay();
            $segFrom = $startAt->copy()->max($dayStart);
            $segTo = $endAt->copy()->min($dayEndExclusive);

            if ($segTo > $segFrom) {
                $key = $d->format('Y-m-d');
                $startMinutes = ($segFrom->hour * 60) + $segFrom->minute;
                $endMinutes = $startMinutes + (int) max(1, $segFrom->diffInMinutes($segTo));
                if ($endMinutes > 24 * 60) {
                    $endMinutes = 24 * 60;
                }
                if ($endMinutes > $startMinutes) {
                    if (! isset($map[$key])) {
                        $map[$key] = [];
                    }
                    $map[$key][] = ['start' => $startMinutes, 'end' => $endMinutes];
                }
            }

            $d->addDay();
        }
    }

    /**
     * Merge connected Google Calendar events into the local busy map.
     *
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendGoogleCalendarBusyToBusyMap(UserDetail $userDetail, string $artistTz, array &$map, int $bufferAfterMinutes = 0): void
    {
        if (empty($userDetail->google_calendar_token)) {
            return;
        }

        $startDate = Carbon::now($artistTz)->startOfDay();
        $endDate = $startDate->copy()->addDays(180);
        $busyBlocks = GoogleCalendarController::getBusyBlocksForDateRange(
            $userDetail,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $artistTz
        );

        foreach ($busyBlocks as $block) {
            $startUtc = $block['start_datetime_utc'] ?? null;
            $endUtc = $block['end_datetime_utc'] ?? null;
            if (!$startUtc || !$endUtc) {
                continue;
            }

            try {
                $startAt = $startUtc instanceof Carbon ? $startUtc->copy() : Carbon::parse((string) $startUtc, 'UTC');
                $endAt = $endUtc instanceof Carbon ? $endUtc->copy() : Carbon::parse((string) $endUtc, 'UTC');
            } catch (\Throwable) {
                continue;
            }

            $this->appendDateTimeRangeToBusyMap($map, $startAt, $endAt, $artistTz, $bufferAfterMinutes);
        }
    }

    /**
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendDateTimeRangeToBusyMap(array &$map, Carbon $startAtUtc, Carbon $endAtUtc, string $tz, int $bufferAfterMinutes = 0): void
    {
        $startAt = $startAtUtc->copy()->setTimezone($tz);
        $endAt = $endAtUtc->copy()->setTimezone($tz);

        if ($bufferAfterMinutes > 0) {
            $endAt = $endAt->copy()->addMinutes(max(0, $bufferAfterMinutes));
        }

        if ($endAt <= $startAt) {
            return;
        }

        $d = $startAt->copy()->startOfDay();
        $lastDay = $endAt->copy()->startOfDay();
        $guard = 0;

        while ($d->lte($lastDay) && $guard++ < 14) {
            $dayStart = $d->copy()->startOfDay();
            $dayEndExclusive = $d->copy()->addDay()->startOfDay();
            $segFrom = $startAt->copy()->max($dayStart);
            $segTo = $endAt->copy()->min($dayEndExclusive);

            if ($segTo > $segFrom) {
                $key = $d->format('Y-m-d');
                $startMinutes = ($segFrom->hour * 60) + $segFrom->minute;
                $endMinutes = $startMinutes + (int) max(1, $segFrom->diffInMinutes($segTo));
                if ($endMinutes > 24 * 60) {
                    $endMinutes = 24 * 60;
                }
                if ($endMinutes > $startMinutes) {
                    if (! isset($map[$key])) {
                        $map[$key] = [];
                    }
                    $map[$key][] = ['start' => $startMinutes, 'end' => $endMinutes];
                }
            }

            $d->addDay();
        }
    }

    public function checkEmailAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $existingUser = User::query()
            ->select(['id', 'role'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $exists = (bool) $existingUser;
        $isUserRole = $exists && $existingUser->role === 'user';
        $allowedForBooking = !$exists || $isUserRole;

        return response()->json([
            'exists' => $exists,
            'is_user' => $isUserRole,
            'allowed' => $allowedForBooking,
        ]);
    }

    public function sendBookingOtp(Request $request, PublicBookingEmailVerificationService $emailVerification): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
            'artist_username' => ['nullable', 'string', 'max:100'],
            'artist_name' => ['nullable', 'string', 'max:255'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $remaining = $emailVerification->cooldownRemainingSeconds($email);

        if ($remaining > 0) {
            return response()->json([
                'sent' => false,
                'message' => 'Please wait before requesting another code.',
                'resend_available_in_seconds' => $remaining,
            ], 429);
        }

        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cooldownSeconds = $emailVerification->storeOtp($email, $otpCode);

        $artistName = trim((string) ($validated['artist_name'] ?? ''));
        $artistUsername = trim((string) ($validated['artist_username'] ?? ''));
        if ($artistName === '' && $artistUsername !== '') {
            $detail = UserDetail::query()
                ->where('user_name', $artistUsername)
                ->first();
            if ($detail) {
                $artistName = $detail->publicDisplayName();
            }
        }

        $clientName = trim((string) ($validated['name'] ?? ''));
        $expiresInMinutes = 10;

        Mail::send('emails.booking-otp', [
            'otpCode' => $otpCode,
            'expiresInMinutes' => $expiresInMinutes,
            'clientName' => $clientName,
            'artistName' => $artistName,
        ], function ($message) use ($email, $otpCode) {
            $message->to($email)->subject('Your Bookpay code: '.$otpCode);
        });

        return response()->json([
            'sent' => true,
            'expires_in_seconds' => $emailVerification->otpTtlSeconds(),
            'resend_available_in_seconds' => $cooldownSeconds,
        ]);
    }

    public function verifyBookingOtp(Request $request, PublicBookingEmailVerificationService $emailVerification): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'name' => ['nullable', 'string', 'max:255'],
            'referral_source' => ['nullable', 'string', 'max:255'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $code = trim($validated['code']);
        $otpPayload = $emailVerification->getOtp($email);

        if (!is_array($otpPayload) || empty($otpPayload['code']) || empty($otpPayload['expires_at'])) {
            return response()->json(['verified' => false, 'message' => 'Verification code not found. Please request a new code.'], 422);
        }

        if (now()->timestamp > (int) $otpPayload['expires_at']) {
            return response()->json(['verified' => false, 'message' => 'Verification code expired. Please request a new code.'], 422);
        }

        if ((string) $otpPayload['code'] !== $code) {
            return response()->json(['verified' => false, 'message' => 'Invalid verification code.'], 422);
        }

        $existingUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $isNewUser = false;
        if (!$existingUser) {
            $name = trim((string) ($validated['name'] ?? ''));
            $parts = preg_split('/\s+/', $name) ?: [];
            $firstName = $parts[0] ?? 'Guest';
            $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

            $existingUser = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'must_set_password' => true,
                'role' => 'user',
                'on_boarding' => 'yes',
                'email_verified_at' => now(),
                'hear_about_us' => trim((string) ($validated['referral_source'] ?? '')) ?: null,
            ]);
            $isNewUser = true;
        } else {
            $existingUser->syncHearAboutUs($validated['referral_source'] ?? null);
        }

        $emailVerification->markVerified($email, [
            'user_id' => $existingUser->id,
            'verified_until' => now()->addMinutes(10)->timestamp,
            'is_new_user' => $isNewUser,
        ]);

        return response()->json([
            'verified' => true,
            'connected' => true,
            'user' => [
                'id' => $existingUser->id,
                'name' => trim(($existingUser->first_name ?? '') . ' ' . ($existingUser->last_name ?? '')),
                'email' => $existingUser->email,
            ],
        ]);
    }

    public function uploadBookingQuestionImage(Request $request): JsonResponse
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $uploadMax = $this->phpIniBytes((string) ini_get('upload_max_filesize'));
        $postMax = $this->phpIniBytes((string) ini_get('post_max_size'));

        // When post_max_size is exceeded, PHP drops the entire body (no files/input).
        if ($contentLength > 0 && empty($request->all()) && ! $request->files->count()) {
            Log::warning('Booking question image upload rejected: empty body (likely post_max_size)', [
                'content_length' => $contentLength,
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Image is too large for the server upload limit. Please use a PNG/JPG under 10MB.',
            ], 413);
        }

        $incoming = $request->file('image');
        if ($incoming && ! $incoming->isValid()) {
            $error = (int) $incoming->getError();
            if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                Log::warning('Booking question image upload rejected by PHP size limit', [
                    'php_error' => $error,
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'original_name' => $incoming->getClientOriginalName(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Image is too large for the server upload limit. Please use a PNG/JPG under 10MB.',
                ], 422);
            }
        }

        try {
            $validated = $request->validate([
                'artist_username' => ['required_without:studio_username', 'nullable', 'string'],
                'studio_username' => ['nullable', 'string', 'max:100'],
                'tattoo_slug' => ['nullable', 'string'],
                'question_id' => ['required'],
                // Client normalizes to JPEG; keep PNG accepted as fallback.
                'image' => ['required', 'file', 'max:10240'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Unable to upload image.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $e->errors(),
                'limits' => [
                    'app_max_kb' => 10240,
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'upload_max_bytes' => $uploadMax,
                    'post_max_bytes' => $postMax,
                ],
            ], 422);
        }

        $studioUploadKey = strtolower(trim((string) ($validated['studio_username'] ?? '')));
        $userDetail = null;

        if ($studioUploadKey !== '' && trim((string) ($validated['artist_username'] ?? '')) === '') {
            $uploadStudio = Studio::query()->whereRaw('LOWER(username) = ?', [$studioUploadKey])->first();
            if (! $uploadStudio || ! $uploadStudio->user || $uploadStudio->user->role !== 'studio') {
                return response()->json(['success' => false, 'message' => 'Studio not found.'], 404);
            }
        } else {
            $userDetail = UserDetail::query()
                ->where('user_name', $validated['artist_username'])
                ->first();

            if (! $userDetail || ! $userDetail->user || $userDetail->user->role !== 'artist') {
                return response()->json(['success' => false, 'message' => 'Artist not found.'], 404);
            }
        }

        $tattooSlug = $userDetail ? trim((string) ($validated['tattoo_slug'] ?? '')) : '';
        if ($tattooSlug !== '') {
            $design = $userDetail->user->artistDesigns()
                ->where('slug', $tattooSlug)
                ->where('is_active', true)
                ->first();

            if (! $design) {
                return response()->json(['success' => false, 'message' => 'Tattoo design not found.'], 404);
            }
        }

        $file = $request->file('image');
        if (! $file) {
            return response()->json(['success' => false, 'message' => 'Image file is required.'], 422);
        }

        Log::info('Booking question image upload received', [
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'ext' => $file->getClientOriginalExtension(),
            'artist' => $validated['artist_username'] ?? null,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ]);

        $imageCheck = $this->assertUploadIsSupportedImage($file);
        if ($imageCheck !== true) {
            return response()->json(['success' => false, 'message' => $imageCheck], 422);
        }

        $folder = public_path('uploads/booking-questions');
        if (! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) {
            Log::error('Booking question image upload: cannot create upload folder', [
                'folder' => $folder,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save the image on the server. Please try again.',
            ], 500);
        }

        if (! is_writable($folder)) {
            Log::error('Booking question image upload: upload folder is not writable', [
                'folder' => $folder,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save the image on the server. Please try again.',
            ], 500);
        }

        $extension = $this->resolveUploadImageExtension($file);
        $filename = 'q_'.preg_replace('/[^A-Za-z0-9_-]/', '', (string) $validated['question_id'])
            .'_'.time().'_'.Str::random(8).'.'.$extension;

        try {
            // Prefer contents copy — more reliable than move() on some hosts.
            $saved = @file_put_contents(
                $folder.DIRECTORY_SEPARATOR.$filename,
                file_get_contents($file->getRealPath() ?: $file->getPathname())
            );
            if ($saved === false) {
                $file->move($folder, $filename);
            }
        } catch (\Throwable $e) {
            Log::error('Booking question image upload: save failed', [
                'error' => $e->getMessage(),
                'folder' => $folder,
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save the image on the server. Please try again.',
            ], 500);
        }

        if (! is_file($folder.DIRECTORY_SEPARATOR.$filename)) {
            Log::error('Booking question image upload: file missing after save', [
                'folder' => $folder,
                'filename' => $filename,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save the image on the server. Please try again.',
            ], 500);
        }

        $publicPath = '/uploads/booking-questions/'.$filename;

        return response()->json([
            'success' => true,
            'file_path' => $publicPath,
            'file_url' => asset(ltrim($publicPath, '/')),
        ]);
    }

    /**
     * @return true|string
     */
    private function assertUploadIsSupportedImage(\Illuminate\Http\UploadedFile $file): bool|string
    {
        $path = $file->getRealPath() ?: $file->getPathname();
        $info = $path ? @getimagesize($path) : false;
        if ($info !== false) {
            $type = (int) ($info[2] ?? 0);
            if (in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
                return true;
            }
        }

        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowedMime = [
            'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp', 'image/gif',
        ];
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($mime, $allowedMime, true) || in_array($ext, $allowedExt, true)) {
            // Trust browser-normalized JPEG uploads even if getimagesize fails briefly.
            if (str_contains($mime, 'jpeg') || in_array($ext, ['jpg', 'jpeg'], true)) {
                return true;
            }
        }

        return 'Please upload a valid JPG or PNG image.';
    }

    private function resolveUploadImageExtension(\Illuminate\Http\UploadedFile $file): string
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        if (str_contains($mime, 'png')) {
            return 'png';
        }
        if (str_contains($mime, 'webp')) {
            return 'webp';
        }

        return 'jpg';
    }

    private function phpIniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $number,
        };
    }

    public function publicArtistProfile(string $userName)
    {
        $key = strtolower(ltrim(trim($userName), '@'));

        $userDetail = UserDetail::whereRaw('LOWER(user_name) = ?', [$key])->first();

        if ($userDetail && $userDetail->user && $userDetail->user->role === 'artist' && $userDetail->user->on_boarding === 'yes') {
            $displayDesigns = (bool) ($userDetail->display_designs ?? true);
            $artistDesigns = $displayDesigns
                ? $userDetail->user->artistDesigns()
                    ->where('is_active', true)
                    ->withSoldOutState()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                : collect();

            $displayPortfolio = (bool) ($userDetail->display_portfolio ?? true);
            $artistPortfolios = $displayPortfolio
                ? $userDetail->user->portfolios()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                : collect();

            $guestSpots = $userDetail->display_guest_spots
                ? $userDetail->user->guestSpots()->orderBy('sort_order')->orderBy('id')->get()
                : collect();

            $guestSpots->each(fn (\App\Models\GuestSpot $spot) => $spot->ensureCompletedStatus());

            $faqs = $userDetail->display_faq
                ? $userDetail->user->artistFaqs()->active()->ordered()->get()
                : collect();

            return view('public.artist', [
                'userDetail' => $userDetail,
                'artistDesigns' => $artistDesigns,
                'artistPortfolios' => $artistPortfolios,
                'guestSpots' => $guestSpots,
                'faqs' => $faqs,
            ]);
        }

        return $this->publicStudioProfile($key);
    }

    /**
     * Public studio page for /@{username} when the handle belongs to a studio.
     */
    private function publicStudioProfile(string $usernameKey)
    {
        $studio = Studio::whereRaw('LOWER(username) = ?', [$usernameKey])->first();

        if (
            ! $studio
            || trim((string) ($studio->username ?? '')) === ''
            || ! $studio->user
            || $studio->user->role !== 'studio'
            || $studio->user->on_boarding !== 'yes'
        ) {
            abort(404, 'Page not found');
        }

        $relationshipLabels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];

        $formatStyleLabel = static function (string $style): string {
            return ucwords(str_replace(['-', '_'], ' ', $style));
        };

        $studioArtists = collect();
        if (UserStudio::tableReady()) {
            $query = UserStudio::query()
                ->with(['user.userDetail'])
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_ACTIVE);

            if (Schema::hasColumn('user_studios', 'show_on_page')) {
                $query->where(function ($q) {
                    $q->whereNull('show_on_page')->orWhere('show_on_page', true);
                });
            }

            if (Schema::hasColumn('user_studios', 'page_sort_order')) {
                $query->orderBy('page_sort_order')->orderBy('id');
            } else {
                $query->orderBy('id');
            }

            $studioArtists = $query->get()->map(function (UserStudio $link) use ($relationshipLabels, $formatStyleLabel) {
                $artistUser = $link->user;
                $detail = $artistUser?->userDetail;
                if (! $detail || ! $artistUser || $artistUser->role !== 'artist' || $artistUser->on_boarding !== 'yes') {
                    return null;
                }

                $name = $detail->publicDisplayName();
                if ($name === '' || $name === 'Artist') {
                    $fallback = trim(($artistUser->first_name ?? '').' '.($artistUser->last_name ?? ''));
                    $name = $fallback !== '' ? $fallback : (string) ($detail->user_name ?? 'Artist');
                }

                $username = trim((string) ($detail->user_name ?? ''));
                $avatar = trim((string) ($detail->avatar ?? ''));
                $stylesPayload = is_array($detail->tattoo_styles ?? null) ? $detail->tattoo_styles : [];
                $styleLabels = [];
                $primary = trim((string) ($stylesPayload['primary_style'] ?? ''));
                if ($primary !== '') {
                    $styleLabels[] = $formatStyleLabel($primary);
                }
                foreach ((array) ($stylesPayload['other_styles'] ?? []) as $style) {
                    $style = trim((string) $style);
                    if ($style === '') {
                        continue;
                    }
                    $label = $formatStyleLabel($style);
                    if (! in_array($label, $styleLabels, true)) {
                        $styleLabels[] = $label;
                    }
                }

                $displayDesigns = (bool) ($detail->display_designs ?? true);
                $displayPortfolio = (bool) ($detail->display_portfolio ?? true);
                $hasDesigns = $displayDesigns && in_array($detail->availability_status, ['design_custom', 'design_only'], true);

                $currencyCode = strtoupper(trim((string) ($detail->currency ?? $studio->currency ?? 'EUR')));
                $currencySymbol = match ($currencyCode) {
                    'EUR' => '€',
                    'GBP' => '£',
                    'USD' => '$',
                    default => $currencyCode !== '' ? $currencyCode.' ' : '€',
                };

                return [
                    'user_id' => (int) $artistUser->id,
                    'name' => $name,
                    'initials' => $detail->publicDisplayInitials() ?: 'AR',
                    'avatar' => $avatar !== '' ? asset(ltrim($avatar, '/')) : null,
                    'username' => $username,
                    'handle' => $username !== '' ? '@'.$username : '',
                    'relationship' => $relationshipLabels[$link->relationship ?? ''] ?? 'Resident',
                    'styles' => array_slice($styleLabels, 0, 3),
                    'all_styles' => $styleLabels,
                    'profile_url' => $username !== '' ? route('public.artist', ['username' => $username]) : null,
                    'request_url' => $username !== '' ? route('public.request-custom', ['user_name' => $username]) : null,
                    'has_designs' => $hasDesigns,
                    'display_designs' => $displayDesigns,
                    'display_portfolio' => $displayPortfolio,
                    'currency_symbol' => $currencySymbol,
                ];
            })->filter()->values();
        }

        $showFlash = (bool) ($studio->show_artists_flash ?? true);
        $showPort = (bool) ($studio->show_artists_portfolio ?? true);
        $workItems = $this->studioPublicWorkItems($studio, $studioArtists, $showFlash, $showPort);
        $guestArtists = $this->studioPublicGuestArtists($studio, $formatStyleLabel);

        return view('public.studio', [
            'studio' => $studio,
            'studioArtists' => $studioArtists,
            'workItems' => $workItems,
            'guestArtists' => $guestArtists,
            'showFlash' => $showFlash,
            'showPort' => $showPort,
        ]);
    }

    /**
     * Confirmed / live upcoming guest spots for the public studio “Guest artists” section (S10).
     *
     * @param  callable(string): string  $formatStyleLabel
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function studioPublicGuestArtists(Studio $studio, callable $formatStyleLabel)
    {
        if (! Schema::hasTable('guest_spots') || ! (bool) ($studio->show_guest_artists ?? true)) {
            return collect();
        }

        $spots = GuestSpot::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studio->id)
            ->where(function ($q) {
                $q->whereNull('guest_studio_invite_status')
                    ->orWhere('guest_studio_invite_status', '!=', GuestSpot::INVITE_REJECTED);
            })
            ->where(function ($q) {
                $q->where(function ($direct) {
                    $direct->whereNull('payout_mode')
                        ->orWhere('payout_mode', '!=', 'split');
                })->orWhere('guest_studio_invite_status', GuestSpot::INVITE_ACCEPTED);
            })
            ->whereDate('to_date', '>=', now()->toDateString())
            ->orderBy('from_date')
            ->orderBy('id')
            ->get();

        return $spots->map(function (GuestSpot $spot) use ($formatStyleLabel) {
            $spot->ensureCompletedStatus();
            if ($spot->hasEnded() || $spot->status === 'completed') {
                return null;
            }

            $artistUser = $spot->user;
            $detail = $artistUser?->userDetail;
            if (! $artistUser || $artistUser->role !== 'artist' || $artistUser->on_boarding !== 'yes') {
                return null;
            }

            $name = $detail ? $detail->publicDisplayName() : '';
            if ($name === '' || $name === 'Artist') {
                $fallback = trim(($artistUser->first_name ?? '').' '.($artistUser->last_name ?? ''));
                $name = $fallback !== '' ? $fallback : (string) ($detail?->user_name ?? 'Artist');
            }

            $username = trim((string) ($detail?->user_name ?? ''));
            $city = trim((string) ($detail?->city ?? $spot->city ?? ''));

            $stylesPayload = is_array($detail?->tattoo_styles ?? null) ? $detail->tattoo_styles : [];
            $styleLabels = [];
            $primary = trim((string) ($stylesPayload['primary_style'] ?? ''));
            if ($primary !== '') {
                $styleLabels[] = $formatStyleLabel($primary);
            }
            foreach ((array) ($stylesPayload['other_styles'] ?? []) as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style === '') {
                    continue;
                }
                $label = $formatStyleLabel($style);
                if (! in_array($label, $styleLabels, true)) {
                    $styleLabels[] = $label;
                }
            }

            $metaParts = [];
            if ($city !== '') {
                $metaParts[] = 'From '.$city;
            }
            if ($spot->from_date && $spot->to_date) {
                $metaParts[] = $spot->from_date->format('d/m').' – '.$spot->to_date->format('d/m/Y');
            }
            if ($styleLabels !== []) {
                $metaParts[] = implode(', ', array_slice($styleLabels, 0, 3));
            }

            $url = null;
            if ($username !== '' && $spot->isReservable()) {
                $url = route('public.guest-spot', [
                    'user_name' => $username,
                    'guestSpotSlug' => $spot->publicSlug(),
                ]);
            }

            return [
                'id' => (int) $spot->id,
                'name' => $name,
                'month' => $spot->from_date ? strtoupper($spot->from_date->format('M')) : '',
                'day' => $spot->from_date ? $spot->from_date->format('j') : '',
                'meta' => implode(' · ', $metaParts),
                'url' => $url,
            ];
        })->filter()->values();
    }

    /**
     * Flash / portfolio / studio pieces for the public studio “Our work” grid.
     *
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $studioArtists
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function studioPublicWorkItems(Studio $studio, $studioArtists, bool $showFlash, bool $showPort)
    {
        $items = collect();
        $byUser = $studioArtists->keyBy('user_id');
        $formatMoney = static function ($amount, string $symbol): string {
            if ($amount === null || $amount === '') {
                return '';
            }
            $number = number_format((float) $amount, 0, '.', '');

            return $symbol.$number;
        };

        if ($showFlash) {
            $flashArtists = $studioArtists->filter(fn ($a) => ! empty($a['display_designs']) && ! empty($a['username']));
            $flashUserIds = $flashArtists->pluck('user_id')->all();
            if ($flashUserIds !== []) {
                $designs = ArtistDesign::query()
                    ->whereIn('user_id', $flashUserIds)
                    ->where('is_active', true)
                    ->withSoldOutState()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

                foreach ($designs as $design) {
                    if ($design->isSoldOut()) {
                        continue;
                    }
                    $artist = $byUser[(int) $design->user_id] ?? null;
                    if (! $artist) {
                        continue;
                    }
                    $username = (string) ($artist['username'] ?? '');
                    $slug = trim((string) ($design->slug ?? ''));
                    if ($username === '' || $slug === '') {
                        continue;
                    }
                    $symbol = (string) ($artist['currency_symbol'] ?? '€');
                    $min = $formatMoney($design->min_price, $symbol);
                    $max = $formatMoney($design->max_price, $symbol);
                    $price = $min;
                    if ($max !== '' && $max !== $min) {
                        $price = $min !== '' ? $min.' – '.$max : $max;
                    }
                    $image = trim((string) ($design->image ?? ''));

                    $items->push([
                        'kind' => 'flash',
                        'title' => (string) ($design->title ?: 'Untitled'),
                        'image' => $image !== '' ? asset(ltrim($image, '/')) : null,
                        'artist_name' => (string) ($artist['name'] ?? 'Artist'),
                        'artist_initials' => (string) ($artist['initials'] ?? 'AR'),
                        'artist_avatar' => $artist['avatar'] ?? null,
                        'price_label' => $price !== '' ? $price.' · Flash' : 'Flash',
                        'cta_url' => route('public.tattoo.book', ['user_name' => $username, 'tattoo_slug' => $slug]),
                        'cta_label' => 'Book now',
                        'cta_pri' => true,
                    ]);
                }
            }
        }

        if ($showPort) {
            $portArtists = $studioArtists->filter(fn ($a) => ! empty($a['display_portfolio']) && ! empty($a['username']));
            $portUserIds = $portArtists->pluck('user_id')->all();
            if ($portUserIds !== []) {
                $portfolios = Portfolio::query()
                    ->whereIn('user_id', $portUserIds)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

                foreach ($portfolios as $portfolio) {
                    $artist = $byUser[(int) $portfolio->user_id] ?? null;
                    if (! $artist) {
                        continue;
                    }
                    $username = (string) ($artist['username'] ?? '');
                    if ($username === '') {
                        continue;
                    }
                    $image = trim((string) ($portfolio->image ?? ''));
                    $title = (string) ($portfolio->title ?: 'Untitled');

                    $items->push([
                        'kind' => 'port',
                        'title' => $title,
                        'image' => $image !== '' ? asset(ltrim($image, '/')) : null,
                        'artist_name' => (string) ($artist['name'] ?? 'Artist'),
                        'artist_initials' => (string) ($artist['initials'] ?? 'AR'),
                        'artist_avatar' => $artist['avatar'] ?? null,
                        'price_label' => null,
                        'cta_url' => route('public.request-custom', [
                            'user_name' => $username,
                            'portfolio' => $portfolio->id,
                        ]),
                        'cta_label' => 'Request similar tattoo',
                        'cta_pri' => false,
                    ]);
                }
            }
        }

        $studioName = trim((string) ($studio->name ?? '')) ?: 'Studio';
        $studioPieces = $studio->portfolios()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn ($p) => $p->isVisibleOnPage());

        foreach ($studioPieces as $piece) {
            $image = trim((string) ($piece->image ?? ''));
            $items->push([
                'kind' => 'studio',
                'title' => trim((string) ($piece->title ?? '')) ?: 'Studio piece',
                'image' => $image !== ''
                    ? (str_starts_with($image, 'http') ? $image : asset(ltrim($image, '/')))
                    : null,
                'artist_name' => $studioName,
                'artist_initials' => null,
                'artist_avatar' => null,
                'price_label' => null,
                'cta_url' => route('public.studio-request', [
                    'user_name' => (string) ($studio->username ?? ''),
                    'studio_portfolio' => $piece->id,
                ]),
                'cta_label' => 'Request similar tattoo',
                'cta_pri' => false,
            ]);
        }

        return $items->values();
    }

    public function publicGuestSpot(string $userName, string $guestSpotSlug)
    {
        $userDetail = UserDetail::where('user_name', $userName)->first();

        if (! $userDetail || $userDetail->user->role !== 'artist' || $userDetail->user->on_boarding !== 'yes') {
            abort(404, 'Artist not found');
        }

        if (! (bool) ($userDetail->display_guest_spots ?? false)) {
            abort(404, 'Guest spot not found');
        }

        $slug = Str::lower(trim($guestSpotSlug));

        $spot = $userDetail->user->guestSpots()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->first(fn (\App\Models\GuestSpot $candidate) => $candidate->publicSlug() === $slug);

        if (! $spot) {
            abort(404, 'Guest spot not found');
        }

        $spot->ensureCompletedStatus();

        $selectedDesignIds = array_values(array_map('intval', $spot->selected_design_ids ?? []));
        $selectedPortfolioIds = array_values(array_map('intval', $spot->selected_portfolio_ids ?? []));

        $artistDesigns = ($spot->show_designs && $selectedDesignIds !== [])
            ? $userDetail->user->artistDesigns()
                ->where('is_active', true)
                ->whereIn('id', $selectedDesignIds)
                ->withSoldOutState()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->sortBy(fn ($d) => array_search((int) $d->id, $selectedDesignIds, true))
                ->values()
            : collect();

        $artistPortfolios = ($spot->show_portfolio && $selectedPortfolioIds !== [])
            ? $userDetail->user->portfolios()
                ->where('is_active', true)
                ->whereIn('id', $selectedPortfolioIds)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->sortBy(fn ($p) => array_search((int) $p->id, $selectedPortfolioIds, true))
                ->values()
            : collect();

        $faqs = ($spot->show_faq ?? true)
            ? $userDetail->user->artistFaqs()->active()->ordered()->get()
            : collect();

        return view('public.guest-spot', [
            'userDetail' => $userDetail,
            'guestSpot' => $spot,
            'artistDesigns' => $artistDesigns,
            'artistPortfolios' => $artistPortfolios,
            'faqs' => $faqs,
        ]);
    }

    public function publicArtistsList(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $artistsQuery = UserDetail::query()
            ->with([
                'user' => function ($q) {
                    $q->select('id', 'first_name', 'last_name', 'role', 'on_boarding');
                },
                'user.artistDesigns' => function ($q) {
                    $q->select('id', 'user_id');
                },
            ])
            ->whereNotNull('user_name')
            ->whereHas('user', function ($q) {
                $q->where('role', 'artist')->where('on_boarding', 'yes');
            });

        if ($search !== '') {
            $needle = '%' . mb_strtolower($search) . '%';
            $artistsQuery->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(user_name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(studio_name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(city) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(country) LIKE ?', [$needle])
                    ->orWhereHas('studio', function ($studioQuery) use ($needle) {
                        $studioQuery->whereRaw('LOWER(name) LIKE ?', [$needle]);
                    });
            });
        }

        $artists = $artistsQuery
            ->with('studio')
            ->orderByDesc('id')
            ->get()
            ->map(function (UserDetail $detail) {
                $displayName = $detail->publicDisplayName();
                $styles = is_array($detail->tattoo_styles ?? null) ? $detail->tattoo_styles : [];
                $primaryStyle = (string) ($styles['primary_style'] ?? $styles['style'] ?? '');
                $tattooCount = (int) ($detail->user?->artistDesigns?->count() ?? 0);

                return [
                    'username' => (string) $detail->user_name,
                    'display_name' => $displayName,
                    'studio_name' => $detail->resolvedStudioName(),
                    'city' => (string) ($detail->city ?? ''),
                    'country' => (string) ($detail->country ?? ''),
                    'avatar' => (string) ($detail->avatar ?? ''),
                    'tagline' => (string) ($detail->personal_page_tagline ?? ''),
                    'description' => (string) ($detail->personal_page_description ?? ''),
                    'primary_style' => $primaryStyle,
                    'availability_status' => (string) ($detail->availability_status ?? ''),
                    'tattoo_count' => $tattooCount,
                ];
            })
            ->values();

        return view('public.artists', [
            'artists' => $artists,
            'search' => $search,
        ]);
    }

    public function publicTattooPage(Request $request, string $userName, string $tattooSlug)
    {
        // Get tattoo by ID
        $userDetail = UserDetail::where('user_name', $userName)->first();

        $availabilities = Availability::where('user_id', $userDetail->user_id)->get();

        if (! $userDetail || $userDetail->user->role !== 'artist' || $userDetail->user->on_boarding !== 'yes' || $availabilities->count() === 0 || in_array($userDetail->availability_status, ['closed', 'custom_only'], true)) {
            return redirect()->route('public.artist', ['username' => $userName]);
        }

        $tattoo = $userDetail->user->artistDesigns()
            ->where('slug', $tattooSlug)
            ->where('is_active', true)
            ->withSoldOutState()
            ->first();

        if (! $tattoo) {
            return redirect()->route('public.artist', ['username' => $userName]);
        }

        $guestSpot = \App\Models\GuestSpot::resolveReservableForArtist(
            $request->query('guest_spot'),
            (int) $userDetail->user_id
        );

        $relatedTattoos = $userDetail->user->artistDesigns()
            ->where('is_active', true)
            ->where('id', '!=', $tattoo->id)
            ->withSoldOutState()
            ->latest('id')
            ->take(3)
            ->get();

        $isSoldOut = $tattoo->isSoldOut();
        $payoutService = app(ArtistPayoutService::class);
        $isAutoScheduling = ($userDetail->scheduling_type ?? '') === 'auto';
        $canBookOnline = ! $isAutoScheduling || $payoutService->canAcceptClientPayments($userDetail);

        return view('public.tattoo', [
            'userDetail' => $userDetail,
            'tattoo' => $tattoo,
            'relatedTattoos' => $relatedTattoos,
            'isSoldOut' => $isSoldOut,
            'canBookOnline' => $canBookOnline,
            'guestSpotId' => $guestSpot?->id,
            'guestSpot' => $guestSpot,
            'bookingUnavailableMessage' => $canBookOnline
                ? null
                : $payoutService->publicBookingUnavailableMessage(),
        ]);
    }

    public function bookTattoo(Request $request, string $userName, string $tattooSlug)
    {
        $userDetail = UserDetail::where('user_name', $userName)->first();

        if (! $userDetail || $userDetail->user->role !== 'artist' || $userDetail->user->on_boarding !== 'yes' || in_array($userDetail->availability_status, ['closed', 'custom_only'], true)) {
            return redirect()->route('public.artist', ['username' => $userName]);
        }

        $availabilities = Availability::where('user_id', $userDetail->user_id)->get();

        if ($availabilities->count() === 0) {
            return redirect()->route('public.artist', ['username' => $userName]);
        }

        $tattoo = $userDetail->user->artistDesigns()
            ->where('slug', $tattooSlug)
            ->where('is_active', true)
            ->withSoldOutState()
            ->first();

        if (! $tattoo) {
            return redirect()->route('public.artist', ['username' => $userName]);
        }

        if ($tattoo->isSoldOut()) {
            return redirect()->route('public.tattoo', [
                'user_name' => $userName,
                'tattoo_slug' => $tattooSlug,
            ]);
        }

        $payoutService = app(ArtistPayoutService::class);
        if (($userDetail->scheduling_type ?? '') === 'auto' && ! $payoutService->canAcceptClientPayments($userDetail)) {
            return redirect()->route('public.tattoo', [
                'user_name' => $userName,
                'tattoo_slug' => $tattooSlug,
            ]);
        }

        $guestSpot = \App\Models\GuestSpot::resolveReservableForArtist(
            $request->query('guest_spot'),
            (int) $userDetail->user_id
        );
        if ($request->has('guest_spot') && ! $guestSpot) {
            return redirect()->route('public.tattoo', [
                'user_name' => $userName,
                'tattoo_slug' => $tattooSlug,
            ]);
        }

        $questions = QuestionSorting::activeQuestionsPayloadForArtist($userDetail->user_id, 'default');
        $studioChoices = ArtistStudioChoices::forArtist((int) $userDetail->user_id, $userDetail);
        if ($guestSpot === null) {
            $questions = ArtistStudioChoices::prependQuestionIfNeeded(
                $questions,
                $studioChoices,
                $userDetail->publicDisplayName()
            );
        } else {
            $studioChoices = [[
                'id' => null,
                'name' => $guestSpot->studioDisplayName() ?: 'Guest studio',
                'address' => $guestSpot->listLocationLabel() ?: '',
                'maps_link' => trim((string) ($guestSpot->google_maps_link ?? '')),
                'studios_table_id' => $guestSpot->studio_id ? (int) $guestSpot->studio_id : null,
            ]];
        }

        $hiddenStyleOptions = Style::query()
            ->active()
            ->where('appear_on_question', false)
            ->ordered()
            ->pluck('name')
            ->values()
            ->all();

        $hiddenPlacementOptions = Placement::query()
            ->active()
            ->where('appear_on_question', false)
            ->ordered()
            ->pluck('name')
            ->values()
            ->all();

        if($userDetail->scheduling_type == 'auto'){

            $artistTimezone = $userDetail->timezone ?: 'UTC';
            $tattooDurationMinutes = $tattoo->sessionDurationMinutes();

            $calendarPayload = app(\App\Services\BookingCalendarAvailabilityService::class)
                ->calendarPayloadForArtist(
                    $userDetail,
                    $tattooDurationMinutes,
                    180,
                    $guestSpot?->id
                );
            $artistAvailabilitySchedule = $calendarPayload['artistAvailabilitySchedule'];
            $artistBlockedPeriods = $calendarPayload['artistBlockedPeriods'];
            $artistLocationOverrides = $guestSpot
                ? []
                : ($calendarPayload['artistLocationOverrides'] ?? []);
            $artistBusyIntervalsByDate = $calendarPayload['artistBusyIntervalsByDate'];
            $artistTimezone = $calendarPayload['artistTimezone'] ?: $artistTimezone;
            $tattooDurationMinutes = $calendarPayload['tattooDurationMinutes'] ?: $tattooDurationMinutes;

            $guestBookableWindow = null;
            if ($guestSpot && $guestSpot->from_date && $guestSpot->to_date) {
                $guestBookableWindow = [
                    'start' => $guestSpot->from_date->format('Y-m-d'),
                    'end' => $guestSpot->to_date->format('Y-m-d'),
                ];
                $startHi = $guestSpot->start_time
                    ? Carbon::parse($guestSpot->start_time)->format('H:i')
                    : null;
                $endHi = $guestSpot->end_time
                    ? Carbon::parse($guestSpot->end_time)->format('H:i')
                    : null;
                if ($startHi && $endHi) {
                    $guestDayRanges = [['start' => $startHi, 'end' => $endHi, 'studio_ids' => []]];
                    $artistAvailabilitySchedule = [
                        'sunday' => $guestDayRanges,
                        'monday' => $guestDayRanges,
                        'tuesday' => $guestDayRanges,
                        'wednesday' => $guestDayRanges,
                        'thursday' => $guestDayRanges,
                        'friday' => $guestDayRanges,
                        'saturday' => $guestDayRanges,
                    ];
                }
            }

            // Verified emails are stored in cache (not session) for reverse-proxy compatibility.

            $vivaRestore = null;
            if ($request->query('viva') === 'fail' && $request->filled('s')) {
                $vivaRestore = app(VivaCheckoutService::class)->publicBookingRestorePayload(
                    $request->query('s'),
                    $userName,
                    $tattooSlug,
                );
            }

            return view('public.book', [
                'userDetail' => $userDetail,
                'tattoo' => $tattoo,
                'questions' => $questions,
                'requiredBookingQuestions' => $questions,
                'hasArtistQuestions' => !empty($questions),
                'studioChoices' => $studioChoices,
                'guestSpotId' => $guestSpot?->id,
                'guestSpot' => $guestSpot,
                'guestBookableWindow' => $guestBookableWindow,
                'hiddenStyleOptions' => $hiddenStyleOptions,
                'hiddenPlacementOptions' => $hiddenPlacementOptions,
                'artistAvailabilitySchedule' => $artistAvailabilitySchedule,
                'artistTimezone' => $artistTimezone,
                'artistBlockedPeriods' => $artistBlockedPeriods,
                'artistLocationOverrides' => $artistLocationOverrides,
                'artistBusyIntervalsByDate' => $artistBusyIntervalsByDate,
                'tattooDurationMinutes' => $tattooDurationMinutes,
                'artistConsultationSettings' => [
                    'required' => (bool) ($userDetail->require_consultation ?? false),
                    'timing' => $userDetail->consultation_timing ?: 'combined',
                    'session_type' => $userDetail->session_type ?: 'both',
                    'session_duration_minutes' => (int) ($userDetail->session_duration_minutes ?: 30),
                    'require_gap' => (bool) ($userDetail->require_gap_between_consultation_tattoo ?? false),
                    'gap_value' => (int) ($userDetail->consultation_tattoo_gap_value ?? 0),
                    'gap_unit' => $userDetail->consultation_tattoo_gap_unit ?: 'hours',
                ],
                'stripePublishableKey' => env('STRIPE_KEY', ''),
                'artistPaymentType' => $userDetail->payment_type ?: 'inkjin_account',
                'minimumDepositType' => $userDetail->minimum_deposit_type ?: 'percentage',
                'minimumDepositAmount' => (float) ($userDetail->minimum_deposit_amount ?? 30),
                'bookingFeeType' => $userDetail->booking_fee_type ?: 'client',
                'artistSupportsIris' => PaymentMethods::isGreekArtist($userDetail),
                'showIrisTab' => false,
                'vivaRestore' => $vivaRestore,
            ]);
        }
        else{
            return view('public.managed-book', [
                'userDetail' => $userDetail,
                'tattoo' => $tattoo,
                'questions' => $questions,
                'requiredBookingQuestions' => $questions,
                'hasArtistQuestions' => !empty($questions),
                'studioChoices' => $studioChoices,
                'guestSpotId' => $guestSpot?->id,
                'guestSpot' => $guestSpot,
                'hiddenStyleOptions' => $hiddenStyleOptions,
                'hiddenPlacementOptions' => $hiddenPlacementOptions,
                'artistConsultationSettings' => [
                    'required' => (bool) ($userDetail->require_consultation ?? false),
                    'timing' => $userDetail->consultation_timing ?: 'combined',
                    'session_type' => $userDetail->session_type ?: 'both',
                    'session_duration_minutes' => (int) ($userDetail->session_duration_minutes ?: 30),
                    'require_gap' => (bool) ($userDetail->require_gap_between_consultation_tattoo ?? false),
                    'gap_value' => (int) ($userDetail->consultation_tattoo_gap_value ?? 0),
                    'gap_unit' => $userDetail->consultation_tattoo_gap_unit ?: 'hours',
                ],
            ]);
        }
    }

    public function createBookingPaymentIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artist_username' => ['required', 'string'],
            'tattoo_slug' => ['required', 'string'],
            'cardholder_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $userDetail = UserDetail::query()
            ->where('user_name', $validated['artist_username'])
            ->first();

        if (!$userDetail || $userDetail->user->role !== 'artist' || $userDetail->user->on_boarding !== 'yes') {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        if ($response = $this->rejectIfArtistCannotAcceptClientPayments($userDetail)) {
            return $response;
        }

        $tattoo = $userDetail->user->artistDesigns()
            ->where('slug', $validated['tattoo_slug'])
            ->where('is_active', true)
            ->first();

        if (!$tattoo) {
            return response()->json(['message' => 'Tattoo not found.'], 404);
        }

        if ($response = $this->rejectIfDesignSoldOut($tattoo)) {
            return $response;
        }

        try {
            app(GoogleCalendarBookingSyncService::class)->assertReadyForPayment($userDetail);
        } catch (GoogleCalendarEventRequiredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $stripeSecret = env('STRIPE_SECRET');
        if (!$stripeSecret) {
            return response()->json(['message' => 'Stripe is not configured.'], 500);
        }

        $pricing = app(BookingCheckoutPricingService::class);
        $totals = $pricing->checkoutTotals($userDetail, (float) $tattoo->min_price, $validated['phone'] ?? null);
        $depositMeta = $totals['deposit_meta'];
        $bookingFee = $totals['booking_fee'];
        $deposit = (float) $totals['deposit'];
        $platformFee = (float) $totals['platform_fee'];
        $taxAmount = (float) $totals['tax_amount'];
        $totalDueNow = (float) $totals['total_due'];
        $amountCents = (int) round($totalDueNow * 100);
        $vat = [
            'tax_amount' => $taxAmount,
            'rate' => $totals['tax_rate'],
            'country_code' => $totals['tax_country'],
            'label' => $totals['tax_label'],
            'is_eu' => $totals['tax_rate'] !== null,
        ];

        $payoutPreference = $this->artistPayoutPreferenceLabel($userDetail);

        try {
            Stripe::setApiKey($stripeSecret);

            $payload = [
                'amount' => $amountCents,
                'currency' => 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'always',
                ],
                'metadata' => [
                    'artist_user_id' => (string) $userDetail->user_id,
                    'tattoo_slug' => (string) $tattoo->slug,
                    'tattoo_design_id' => (string) $tattoo->id,
                    'artist_username' => (string) $userDetail->user_name,
                    'payout_type' => $payoutPreference,
                    'stripe_settlement' => 'platform',
                    'cardholder_name' => $validated['cardholder_name'],
                    'deposit_type' => (string) $depositMeta['type'],
                    'deposit_value' => (string) $depositMeta['amount'],
                    'deposit_label' => (string) $depositMeta['label'],
                    'booking_fee_type' => (string) $bookingFee['fee_type'],
                    'booking_fee_client' => (string) $bookingFee['client_fee'],
                    'booking_fee_artist' => (string) $bookingFee['artist_fee'],
                    'tax_amount' => (string) $vat['tax_amount'],
                    'tax_rate' => (string) ($vat['rate'] ?? 0),
                    'tax_country' => (string) ($vat['country_code'] ?? ''),
                    'tax_label' => (string) ($vat['label'] ?? ''),
                ],
            ];

            $intent = PaymentIntent::create($payload);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount_cents' => $amountCents,
                'currency' => 'eur',
                'payout_type' => $payoutPreference,
                'tax_amount' => $vat['tax_amount'],
                'tax_label' => $vat['label'],
                'total_due' => $totalDueNow,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Unable to initialize payment.',
            ], 422);
        }
    }

    public function confirmBookingAfterPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artist_username' => ['required', 'string'],
            'tattoo_slug' => ['required', 'string'],
            'payment_intent_id' => ['required', 'string'],
            'booking_payload' => ['required', 'array'],
            'booking_payload.email' => ['required', 'email'],
            'booking_payload.phone' => ['nullable', 'string', 'max:50'],
            'booking_payload.name' => ['nullable', 'string', 'max:255'],
            'booking_payload.consultation_required' => ['nullable', 'boolean'],
            'booking_payload.consultation_timing' => ['nullable', 'string', 'in:separate,combined'],
            'booking_payload.consultation_type' => ['nullable', 'string', 'in:video,phone,studio'],
            'booking_payload.consult_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'booking_payload.tattoo_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'booking_payload.questions_answers' => ['nullable', 'array'],
            'booking_payload.notes' => ['nullable', 'string', 'max:2000'],
            'booking_payload.date' => ['nullable', 'date'],
            'booking_payload.time' => ['nullable', 'string', 'max:20'],
            'booking_payload.tattoo_date' => ['nullable', 'date'],
            'booking_payload.tattoo_time' => ['nullable', 'string', 'max:20'],
            'booking_payload.consultation_date' => ['nullable', 'date'],
            'booking_payload.consultation_time' => ['nullable', 'string', 'max:20'],
            'booking_payload.referral_source' => ['nullable', 'string', 'max:255'],
            'booking_payload.studio_id' => ['nullable', 'integer', 'min:1'],
            'booking_payload.guest_spot_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $payload = $validated['booking_payload'];
        $userDetail = UserDetail::query()->where('user_name', $validated['artist_username'])->first();
        if (!$userDetail || !$userDetail->user || $userDetail->user->role !== 'artist') {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        if ($response = $this->rejectIfArtistCannotAcceptClientPayments($userDetail)) {
            return $response;
        }

        $design = $userDetail->user->artistDesigns()
            ->where('slug', $validated['tattoo_slug'])
            ->where('is_active', true)
            ->first();
        if (!$design) {
            return response()->json(['message' => 'Tattoo design not found.'], 404);
        }

        if ($response = $this->rejectIfDesignSoldOut($design)) {
            return $response;
        }

        $stripeSecret = env('STRIPE_SECRET');
        if (!$stripeSecret) {
            return response()->json(['message' => 'Stripe is not configured.'], 500);
        }

        Stripe::setApiKey($stripeSecret);
        $intent = PaymentIntent::retrieve($validated['payment_intent_id']);
        if (!$intent || $intent->status !== 'succeeded') {
            return response()->json(['message' => 'Payment is not completed.'], 422);
        }

        $existingByIntent = Booking::query()->where('payment_intent_id', $intent->id)->first();
        if ($existingByIntent) {
            $existingBookingUser = User::query()->find($existingByIntent->user_id);

            return response()->json([
                'saved' => true,
                'booking_id' => $existingByIntent->id,
                'booking_reference' => '#INK-' . str_pad((string) $existingByIntent->id, 6, '0', STR_PAD_LEFT),
                'post_booking_login_url' => $existingBookingUser
                    ? $this->makePostBookingAccessUrl($existingBookingUser, $existingByIntent)
                    : null,
                'consent_url' => $this->bookingConsentPublicUrl($existingByIntent),
            ]);
        }

        $bookingEmail = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $bookingUser = User::query()->whereRaw('LOWER(email) = ?', [$bookingEmail])->first();
        if (!$bookingUser) {
            return response()->json(['message' => 'Booking user not found. Please verify email again.'], 422);
        }

        $bookingUser->syncPhoneNumber($payload['phone'] ?? null);
        $bookingUser->syncHearAboutUs($payload['referral_source'] ?? null);

        $artistTimezone = $userDetail->timezone ?: 'UTC';
        $consultationRequired = (bool) ($payload['consultation_required'] ?? false);
        $consultationTiming = (string) ($payload['consultation_timing'] ?? 'combined');
        $consultDurationMinutes = (int) ($payload['consult_duration_minutes'] ?? 30);
        $tattooDurationMinutes = (int) ($payload['tattoo_duration_minutes'] ?? 120);
        [$guestSpot, $studioId, $guestSpotError] = $this->resolveGuestSpotBookingContext(
            (int) $userDetail->user_id,
            $payload
        );
        if ($guestSpotError) {
            return response()->json(['message' => $guestSpotError], 422);
        }

        $toUtcTime = function (string $date, string $time) use ($artistTimezone): string {
            return Carbon::createFromFormat('Y-m-d g:i A', $date . ' ' . $time, $artistTimezone)
                ->utc()
                ->format('H:i:s');
        };

        $bookingDate = (string) ($payload['tattoo_date'] ?? $payload['date'] ?? '');
        $bookingTime = (string) ($payload['tattoo_time'] ?? $payload['time'] ?? '');
        if ($bookingDate === '' || $bookingTime === '') {
            return response()->json(['message' => 'Booking date/time is required.'], 422);
        }

        $startUtc = $toUtcTime($bookingDate, $bookingTime);
        $bookingStart = Carbon::createFromFormat('Y-m-d H:i:s', $bookingDate . ' ' . $startUtc, 'UTC');
        $bookingEndUtc = $bookingStart->copy()->addMinutes($tattooDurationMinutes)->format('H:i:s');

        $consultDate = null;
        $consultStartUtc = null;
        $consultEndUtc = null;
        if ($consultationRequired) {
            if ($consultationTiming === 'separate') {
                $consultDate = (string) ($payload['consultation_date'] ?? '');
                $consultTime = (string) ($payload['consultation_time'] ?? '');
                if ($consultDate !== '' && $consultTime !== '') {
                    $consultStartUtc = $toUtcTime($consultDate, $consultTime);
                    $consultStart = Carbon::createFromFormat('Y-m-d H:i:s', $consultDate . ' ' . $consultStartUtc, 'UTC');
                    $consultEndUtc = $consultStart->copy()->addMinutes($consultDurationMinutes)->format('H:i:s');
                }
            } else {
                // Combined: tattoo_date/time in payload is consultation start.
                $consultDate = $bookingDate;
                $consultStartUtc = $startUtc;
                $consultStart = Carbon::createFromFormat('Y-m-d H:i:s', $consultDate . ' ' . $consultStartUtc, 'UTC');
                $consultEndUtc = $consultStart->copy()->addMinutes($consultDurationMinutes)->format('H:i:s');
                $bookingEndUtc = $consultStart->copy()->addMinutes($consultDurationMinutes + $tattooDurationMinutes)->format('H:i:s');
            }
        }

        $managedBooking = app(\App\Services\ManagedRequestBookingService::class);
        $exceptGuestSpotId = $guestSpot?->id ?: $managedBooking->guestSpotIdCoveringBookableDate(
            (int) $userDetail->user_id,
            $bookingDate,
            $studioId
        );

        if ($dateError = $this->guestSpotRejectsDate($guestSpot, $bookingDate)) {
            return response()->json(['message' => $dateError], 422);
        }

        if ($this->artistLocalDateIsBlocked((int) $userDetail->user_id, $bookingDate, $exceptGuestSpotId)) {
            return response()->json([
                'message' => 'This date is not available. The artist has blocked it — please choose another day.',
            ], 422);
        }

        if ($consultationRequired && $consultationTiming === 'separate' && $consultDate !== '') {
            if ($dateError = $this->guestSpotRejectsDate($guestSpot, $consultDate)) {
                return response()->json(['message' => $dateError], 422);
            }
            $exceptConsultGuestSpotId = $guestSpot?->id ?: $managedBooking->guestSpotIdCoveringBookableDate(
                (int) $userDetail->user_id,
                $consultDate,
                $studioId
            );
            if ($this->artistLocalDateIsBlocked((int) $userDetail->user_id, $consultDate, $exceptConsultGuestSpotId)) {
                return response()->json([
                    'message' => 'Consultation date is not available. The artist has blocked it — please choose another day.',
                ], 422);
            }
        }

        $pricing = app(BookingCheckoutPricingService::class);
        $totals = $pricing->checkoutTotals($userDetail, (float) $design->min_price, $payload['phone'] ?? null);
        $depositAmount = (float) $totals['deposit'];
        $platformFee = (float) $totals['platform_fee'];
        $taxAmount = (float) $totals['tax_amount'];
        $totalPaid = (float) $totals['total_due'];

        $booking = Booking::create([
            'user_id' => $bookingUser->id,
            'artist_user_id' => $userDetail->user_id,
            'studio_id' => $studioId,
            'guest_spot_id' => $guestSpot?->id,
            'tattoo_id' => $design->id,
            'booking_type' => 'flash',
            'cancellation_window_hours' => CancellationService::hoursFromArtistWindow($userDetail->cancellation_window ?? '48h'),
            'booking_date' => $bookingDate,
            'start_time_utc' => $startUtc,
            'end_time_utc' => $bookingEndUtc,
            'timezone' => $artistTimezone,
            'has_consultation' => $consultationRequired,
            'consultation_date' => $consultDate,
            'consultation_start_time_utc' => $consultStartUtc,
            'consultation_end_time_utc' => $consultEndUtc,
            'consultation_timing_type' => $consultationRequired ? ($consultationTiming === 'separate' ? 'separate' : 'combined') : null,
            'status' => 'confirmed',
            'payment_intent_id' => $intent->id,
            'payment_status' => 'paid',
            'deposit_amount' => $depositAmount,
            'platform_fee' => $platformFee,
            'tax_amount' => $taxAmount,
            'tax_rate' => $totals['tax_rate'],
            'tax_country' => $totals['tax_country'],
            'tax_label' => $totals['tax_label'],
            'total_amount_paid' => $totalPaid,
            'currency' => strtoupper((string) ($intent->currency ?: 'eur')),
            'questions_answers' => $payload['questions_answers'] ?? [],
            'notes' => trim((string) ($payload['notes'] ?? '')),
        ]);

        if ($guestSpot && ! $booking->consumeGuestSpotCapacity($guestSpot)) {
            $booking->delete();

            return response()->json(['message' => 'All guest spots are full.'], 422);
        }

        if (!$booking->completion_code) {
            do {
                $code = strtoupper(Str::random(6));
            } while (Booking::query()->where('completion_code', $code)->exists());
            $booking->completion_code = $code;
            $booking->save();
        }

        // Create Google Calendar event — required for auto-scheduling artists.
        try {
            $consultationType = trim((string) ($payload['consultation_type'] ?? ''));
            app(GoogleCalendarBookingSyncService::class)->syncForBooking(
                $booking,
                $consultationType !== '' ? $consultationType : null
            );
        } catch (GoogleCalendarEventRequiredException $e) {
            app(GoogleCalendarBookingSyncService::class)->abortFailedCalendarBooking($booking, $e->getMessage());

            return response()->json(['message' => $e->getMessage()], 422);
        }

        $clientEmail = (string) ($bookingUser->email ?? '');
        $artistEmail = (string) ($userDetail->user->email ?? '');

        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new BookingConfirmationMail($booking, false));
            } catch (\Throwable $e) {
                Log::error('Failed to send client booking confirmation email', [
                    'booking_id' => $booking->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new BookingConfirmationMail($booking, true, []));
            } catch (\Throwable $e) {
                Log::error('Failed to send artist booking notification email', [
                    'booking_id' => $booking->id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($bookingUser->role === 'user' && $clientEmail !== '') {
            $isFirstPaidBooking = Booking::query()
                ->where('user_id', $bookingUser->id)
                ->where('payment_status', 'paid')
                ->count() === 1;

            if ($isFirstPaidBooking) {
                try {
                    $recipientName = trim(implode(' ', array_filter([
                        (string) ($bookingUser->first_name ?? ''),
                        (string) ($bookingUser->last_name ?? ''),
                    ])));
                    Mail::to($clientEmail)->send(new UserWelcomeMail(
                        $this->makePostBookingAccessUrl($bookingUser, $booking),
                        $recipientName,
                    ));
                } catch (\Throwable $e) {
                    Log::error('Failed to send user welcome email', [
                        'booking_id' => $booking->id,
                        'user_id' => $bookingUser->id,
                        'email' => $clientEmail,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'saved' => true,
            'booking_id' => $booking->id,
            'booking_reference' => '#INK-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'post_booking_login_url' => $this->makePostBookingAccessUrl($bookingUser, $booking),
            'consent_url' => $this->bookingConsentPublicUrl($booking),
        ]);
    }

    public function submitManagedBooking(Request $request, PublicBookingEmailVerificationService $emailVerification): JsonResponse
    {
        $validated = $request->validate([
            'artist_username' => ['required', 'string'],
            'tattoo_slug' => ['required', 'string'],
            'booking_payload' => ['required', 'array'],
            'booking_payload.email' => ['required', 'email'],
            'booking_payload.phone' => ['nullable', 'string', 'max:50'],
            'booking_payload.name' => ['nullable', 'string', 'max:255'],
            'booking_payload.consultation_required' => ['nullable', 'boolean'],
            'booking_payload.consultation_type' => ['nullable', 'string', 'in:video,phone,studio'],
            'booking_payload.questions_answers' => ['nullable', 'array'],
            'booking_payload.preferences' => ['nullable', 'array'],
            'booking_payload.preferred_days' => ['required', 'array', 'min:1'],
            'booking_payload.how_much_flexible' => ['required', 'string'],
            'booking_payload.avoid_dates' => ['nullable', 'string'],
            'booking_payload.urgency' => ['nullable', 'string'],
            'booking_payload.session_gap' => ['nullable', 'string'],
            'booking_payload.referral_source' => ['nullable', 'string', 'max:255'],
            'booking_payload.studio_id' => ['nullable', 'integer', 'min:1'],
            'booking_payload.guest_spot_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $payload = $validated['booking_payload'];
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));

        $verifiedEntry = $emailVerification->getVerified($email);
        if (! $verifiedEntry) {
            return response()->json(['message' => 'Please verify your email before submitting your request.'], 422);
        }

        $userDetail = UserDetail::query()->where('user_name', $validated['artist_username'])->first();
        if (
            !$userDetail
            || !$userDetail->user
            || $userDetail->user->role !== 'artist'
            || $userDetail->user->on_boarding !== 'yes'
        ) {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        if (($userDetail->scheduling_type ?? '') !== 'managed') {
            return response()->json(['message' => 'This artist does not accept managed booking requests.'], 422);
        }

        if ($userDetail->availability_status === 'closed' || $userDetail->availability_status === 'custom_only') {
            return response()->json(['message' => 'This artist is not accepting available design bookings right now.'], 422);
        }

        $design = $userDetail->user->artistDesigns()
            ->where('slug', $validated['tattoo_slug'])
            ->where('is_active', true)
            ->first();

        if (!$design) {
            return response()->json(['message' => 'Tattoo design not found.'], 404);
        }

        if ($response = $this->rejectIfDesignSoldOut($design)) {
            return $response;
        }

        $consultationRequired = (bool) ($payload['consultation_required'] ?? false);
        if ($consultationRequired) {
            $consultType = (string) ($payload['consultation_type'] ?? '');
            if (!in_array($consultType, ['video', 'phone', 'studio'], true)) {
                return response()->json(['message' => 'Please select a consultation type.'], 422);
            }
            if (trim((string) ($payload['session_gap'] ?? '')) === '') {
                return response()->json(['message' => 'Please select your preferred gap between consultation and tattoo session.'], 422);
            }
        } elseif (trim((string) ($payload['urgency'] ?? '')) === '') {
            return response()->json(['message' => 'Please select your urgency.'], 422);
        }

        $bookingUser = User::query()->find((int) $verifiedEntry['user_id']);
        if (!$bookingUser || mb_strtolower((string) $bookingUser->email) !== $email) {
            return response()->json(['message' => 'Booking user not found. Please verify email again.'], 422);
        }

        $bookingUser->syncPhoneNumber($payload['phone'] ?? null);
        $bookingUser->syncHearAboutUs($payload['referral_source'] ?? null);

        [$guestSpot, $studioId, $guestSpotError] = $this->resolveGuestSpotBookingContext(
            (int) $userDetail->user_id,
            $payload
        );
        if ($guestSpotError) {
            return response()->json(['message' => $guestSpotError], 422);
        }
        $consultationDetails = [
            'studio_id' => $studioId,
            'guest_spot_id' => $guestSpot?->id,
        ];
        if ($consultationRequired) {
            $consultationDetails = array_merge($consultationDetails, [
                'required' => true,
                'type' => (string) ($payload['consultation_type'] ?? ''),
                'session_gap' => (string) ($payload['session_gap'] ?? ''),
                'client_phone' => trim((string) ($payload['phone'] ?? '')),
            ]);
        }
        $consultationDetails = json_encode($consultationDetails);

        $bookingRequest = BookingRequest::create([
            'user_id' => $bookingUser->id,
            'artist_id' => $userDetail->user_id,
            'tattoo_id' => $design->id,
            'status' => 'pending',
            'questions_answers' => $payload['questions_answers'] ?? [],
            'consultation_details' => $consultationDetails,
            'preferences' => $payload['preferences'] ?? [],
            'preferred_days' => $payload['preferred_days'] ?? [],
            'avoid_dates' => trim((string) ($payload['avoid_dates'] ?? '')) ?: null,
            'how_much_flexible' => (string) ($payload['how_much_flexible'] ?? ''),
            'urgency' => $consultationRequired ? null : (string) ($payload['urgency'] ?? ''),
        ]);

        app(\App\Services\MailcoachSubscriberService::class)
            ->queueSubscribeUser($bookingUser, \App\Services\MailcoachSubscriberService::TAG_USER);

        $isNewUser = ! empty($verifiedEntry['is_new_user']);
        $accessUrl = $this->makePostManagedRequestAccessUrl($bookingUser, $bookingRequest);
        $clientEmail = (string) ($bookingUser->email ?? '');

        if ($clientEmail !== '') {
            try {
                $bookingRequest->setRelation('user', $bookingUser);
                $bookingRequest->setRelation('tattoo', $design);
                Mail::to($clientEmail)->send(new ManagedBookingRequestMail(
                    $bookingRequest,
                    $userDetail,
                    $accessUrl,
                    $isNewUser,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send managed booking request email', [
                    'booking_request_id' => $bookingRequest->id,
                    'user_id' => $bookingUser->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $artistEmail = (string) ($userDetail->user->email ?? '');
        if ($artistEmail !== '') {
            try {
                $bookingRequest->load(['user', 'tattoo']);
                Mail::to($artistEmail)->send(new ManagedBookingRequestArtistMail(
                    $bookingRequest,
                    route('artist.requests.index'),
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send managed booking request artist email', [
                    'booking_request_id' => $bookingRequest->id,
                    'artist_id' => $userDetail->user_id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'saved' => true,
            'booking_request_id' => $bookingRequest->id,
            'booking_reference' => $bookingRequest->referenceLabel(),
            'post_request_access_url' => $accessUrl,
        ]);
    }

    public function submitWaitlist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artist_username' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $userDetail = UserDetail::query()
            ->where('user_name', $validated['artist_username'])
            ->first();

        if (! $userDetail || ! $userDetail->user || $userDetail->user->role !== 'artist') {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        $signupMode = $userDetail->waitlist_signup_mode ?? Waitlist::SIGNUP_MODE_NAME_EMAIL;
        if ($signupMode === Waitlist::SIGNUP_MODE_REQUEST_FORM) {
            return response()->json([
                'saved' => false,
                'requires_request_form' => true,
                'message' => 'Please complete the request form to join this waitlist.',
                'request_form_url' => route('public.request-custom', [
                    'user_name' => $userDetail->user_name,
                    'waitlist' => 1,
                ]),
            ], 422);
        }

        $email = mb_strtolower(trim($validated['email']));
        $name = trim($validated['name']);

        $existing = Waitlist::query()
            ->booksOnly()
            ->where('user_id', $userDetail->user_id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existing) {
            return response()->json([
                'saved' => true,
                'message' => 'You are already on the waitlist.',
            ]);
        }

        Waitlist::query()->create([
            'user_id' => $userDetail->user_id,
            'name' => $name,
            'email' => $email,
            'status' => Waitlist::STATUS_PENDING,
        ]);

        return response()->json([
            'saved' => true,
            'message' => 'You have been added to the waitlist.',
        ]);
    }

    public function submitGuestWaitlist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artist_username' => ['required', 'string'],
            'guest_spot_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $userDetail = UserDetail::query()
            ->where('user_name', $validated['artist_username'])
            ->first();

        if (! $userDetail || ! $userDetail->user || $userDetail->user->role !== 'artist') {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        $guestSpot = GuestSpot::query()
            ->where('id', (int) $validated['guest_spot_id'])
            ->where('user_id', $userDetail->user_id)
            ->first();

        if (! $guestSpot) {
            return response()->json(['message' => 'Guest spot not found.'], 404);
        }

        $email = mb_strtolower(trim($validated['email']));
        $name = trim($validated['name']);

        $existing = Waitlist::query()
            ->where('user_id', $userDetail->user_id)
            ->where('guest_spot_id', $guestSpot->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existing) {
            if ($existing->name !== $name) {
                $existing->update(['name' => $name]);
            }

            return response()->json([
                'saved' => true,
                'message' => 'You are already on this guest waitlist.',
            ]);
        }

        Waitlist::query()->create([
            'user_id' => $userDetail->user_id,
            'name' => $name,
            'email' => $email,
            'status' => Waitlist::STATUS_PENDING,
            'guest_spot_id' => $guestSpot->id,
        ]);

        return response()->json([
            'saved' => true,
            'message' => 'You have been added to the guest waitlist.',
        ]);
    }

    public function createPublicVivaOrder(Request $request, VivaCheckoutService $vivaCheckout): JsonResponse
    {
        $validated = $request->validate([
            'artist_username' => ['required', 'string'],
            'tattoo_slug' => ['required', 'string'],
            'booking_payload' => ['required', 'array'],
            'booking_payload.email' => ['required', 'email'],
            'booking_payload.phone' => ['required', 'string', 'max:50'],
            'booking_payload.name' => ['nullable', 'string', 'max:255'],
            'booking_payload.consultation_required' => ['nullable', 'boolean'],
            'booking_payload.consultation_timing' => ['nullable', 'string', 'in:separate,combined'],
            'booking_payload.consultation_type' => ['nullable', 'string', 'in:video,phone,studio'],
            'booking_payload.consult_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'booking_payload.tattoo_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'booking_payload.questions_answers' => ['nullable', 'array'],
            'booking_payload.notes' => ['nullable', 'string', 'max:2000'],
            'booking_payload.date' => ['nullable', 'date'],
            'booking_payload.time' => ['nullable', 'string', 'max:20'],
            'booking_payload.tattoo_date' => ['nullable', 'date'],
            'booking_payload.tattoo_time' => ['nullable', 'string', 'max:20'],
            'booking_payload.consultation_date' => ['nullable', 'date'],
            'booking_payload.consultation_time' => ['nullable', 'string', 'max:20'],
            'booking_payload.studio_id' => ['nullable', 'integer', 'min:1'],
            'booking_payload.guest_spot_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $userDetail = UserDetail::query()
            ->where('user_name', $validated['artist_username'])
            ->first();

        if (! $userDetail || $userDetail->user->role !== 'artist' || $userDetail->user->on_boarding !== 'yes') {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        if ($response = $this->rejectIfArtistCannotAcceptClientPayments($userDetail)) {
            return $response;
        }

        $tattoo = $userDetail->user->artistDesigns()
            ->where('slug', $validated['tattoo_slug'])
            ->where('is_active', true)
            ->first();

        if (! $tattoo) {
            return response()->json(['message' => 'Tattoo not found.'], 404);
        }

        if ($response = $this->rejectIfDesignSoldOut($tattoo)) {
            return $response;
        }

        try {
            app(GoogleCalendarBookingSyncService::class)->assertReadyForPayment($userDetail);
        } catch (GoogleCalendarEventRequiredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payload = $validated['booking_payload'];
        [$guestSpotCheck, , $guestSpotError] = $this->resolveGuestSpotBookingContext(
            (int) $userDetail->user_id,
            $payload
        );
        if ($guestSpotError) {
            return response()->json(['message' => $guestSpotError], 422);
        }
        unset($guestSpotCheck);

        $bookingEmail = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $bookingUser = User::query()->whereRaw('LOWER(email) = ?', [$bookingEmail])->first();

        if (! $bookingUser) {
            return response()->json(['message' => 'Booking user not found. Please verify email again.'], 422);
        }

        $clientPhone = PaymentMethods::checkoutPhoneForIris(
            $payload['phone'] ?? null,
            $bookingUser->phone_number
        );

        if (! PaymentMethods::showIrisTab($userDetail, $clientPhone)) {
            return response()->json(['message' => 'IRIS payment is not available for this checkout.'], 422);
        }

        $pricing = app(BookingCheckoutPricingService::class);
        $totals = $pricing->checkoutTotals($userDetail, (float) $tattoo->min_price, $clientPhone);
        $amountCents = (int) round(((float) $totals['total_due']) * 100);

        try {
            $order = $vivaCheckout->createOrReuseOrderForPublicBooking(
                $userDetail,
                $bookingUser,
                $validated['artist_username'],
                $validated['tattoo_slug'],
                $payload,
                $amountCents,
            );

            return response()->json($order);
        } catch (\Throwable $e) {
            Log::error('Viva order create failed (public booking)', [
                'artist_username' => $validated['artist_username'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Unable to start IRIS payment.',
            ], 422);
        }
    }

    public function publicVivaPaymentStatus(Request $request, VivaCheckoutService $vivaCheckout): JsonResponse
    {
        $validated = $request->validate([
            'order_code' => ['required'],
            'email' => ['required', 'email'],
        ]);

        $bookingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($validated['email']))])
            ->first();

        if (! $bookingUser) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $pending = PendingVivaPayment::query()
            ->where('viva_order_code', $validated['order_code'])
            ->where('client_user_id', $bookingUser->id)
            ->where('flow', PendingVivaPayment::FLOW_PUBLIC_BOOKING)
            ->first();

        if (! $pending) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $status = $vivaCheckout->statusPayload($pending, route('login'));
        if (($status['status'] ?? '') === 'paid') {
            $confirmed = Booking::query()->where('viva_order_code', $pending->viva_order_code)->first();
            if ($confirmed) {
                $status['redirect_url'] = $this->makePostBookingAccessUrl($bookingUser, $confirmed);
                $status['post_booking_login_url'] = $status['redirect_url'];
            }
        }

        return response()->json($status);
    }

    private function rejectIfArtistCannotAcceptClientPayments(UserDetail $userDetail): ?JsonResponse
    {
        $payoutService = app(ArtistPayoutService::class);
        if ($payoutService->canAcceptClientPayments($userDetail)) {
            return null;
        }

        return response()->json([
            'message' => $payoutService->clientPaymentsBlockedMessage($userDetail),
        ], 422);
    }

    private function rejectIfDesignSoldOut(ArtistDesign $design): ?JsonResponse
    {
        if ($design->isSoldOut()) {
            return response()->json([
                'message' => 'This design is sold out and is no longer available to book.',
            ], 422);
        }

        return null;
    }
}