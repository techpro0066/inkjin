<?php

namespace App\Http\Controllers;

use App\Models\InkJinArtist;
use App\Models\InkJinTattoo;
use App\Models\User;
use App\Models\Availability;
use App\Models\AvailabilityOverride;
use App\Services\InkJinApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Http\Controllers\GoogleCalendarController;
use App\Models\Booking;
use App\Mail\BookingConfirmationMail;
use App\Models\UserDetail;
use App\Models\Question;
use App\Models\QuestionSorting;
use App\Models\UserQuestion;

class InkJinController extends Controller
{
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

    public function sendBookingOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $cooldownKey = 'booking_otp_cooldown.' . $email;
        $cooldownUntil = (int) $request->session()->get($cooldownKey, 0);
        $nowTs = now()->timestamp;

        if ($cooldownUntil > $nowTs) {
            return response()->json([
                'sent' => false,
                'message' => 'Please wait before requesting another code.',
                'resend_available_in_seconds' => $cooldownUntil - $nowTs,
            ], 429);
        }

        $otpCode = (string) random_int(1000, 9999);
        $cooldownSeconds = 60;

        $request->session()->put('booking_otp.' . $email, [
            'code' => $otpCode,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);
        $request->session()->put($cooldownKey, $nowTs + $cooldownSeconds);

        Mail::send('emails.booking-otp', [
            'otpCode' => $otpCode,
            'expiresInMinutes' => 10,
        ], function ($message) use ($email) {
            $message->to($email)->subject('Inkjin verification code');
        });

        return response()->json([
            'sent' => true,
            'expires_in_seconds' => 600,
            'resend_available_in_seconds' => $cooldownSeconds,
        ]);
    }

    public function verifyBookingOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:4'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $code = trim($validated['code']);
        $otpPayload = $request->session()->get('booking_otp.' . $email);

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
        if (!$existingUser) {
            $name = trim((string) ($validated['name'] ?? ''));
            $parts = preg_split('/\s+/', $name) ?: [];
            $firstName = $parts[0] ?? 'Guest';
            $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

            $existingUser = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make(Str::random(24)),
                'role' => 'user',
                'on_boarding' => 'no',
                'email_verified_at' => now(),
            ]);
        }

        $verified = $request->session()->get('booking_verified_emails', []);
        $verified[$email] = [
            'user_id' => $existingUser->id,
            'verified_until' => now()->addMinutes(10)->timestamp,
        ];
        $request->session()->put('booking_verified_emails', $verified);
        $request->session()->forget('booking_otp.' . $email);

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

    public function publicArtistProfile(string $userName)
    {
        $userDetail = UserDetail::where('user_name', $userName)->first();

        if (!$userDetail || $userDetail->user->role !== 'artist' || $userDetail->user->on_boarding !== 'yes') {
            abort(404, 'Artist not found');
        }

        $artistDesigns = $userDetail->user->artistDesigns()->where('is_visible', true)->get();

        $artistPortfolios = $userDetail->user->portfolios()->where('is_active', true)->get();

        return view('public.artist', [
            'userDetail' => $userDetail,
            'artistDesigns' => $artistDesigns,
            'artistPortfolios' => $artistPortfolios,
        ]);
    }

    public function publicTattooPage(string $userName, string $tattooSlug)
    {
        // Get tattoo by ID
        $userDetail = UserDetail::where('user_name', $userName)->first();

        $availabilities = Availability::where('user_id', $userDetail->user_id)->get();

        if (!$userDetail || $userDetail->user->role !== 'artist' || $userDetail->user->on_boarding !== 'yes' || $availabilities->count() === 0) {
            abort(404, 'Tattoo not found');
        }
        
        $tattoo = $userDetail->user->artistDesigns()->where('slug', $tattooSlug)->first();

        $relatedTattoos = $userDetail->user->artistDesigns()->where('is_visible', true)->where('id', '!=', $tattoo->id)->take(3)->get();

        return view('public.tattoo', [
            'userDetail' => $userDetail,
            'tattoo' => $tattoo,
            'relatedTattoos' => $relatedTattoos,
        ]);
    }

    public function bookTattoo(string $userName, string $tattooSlug)
    {
        // Get tattoo by ID from database
        $userDetail = UserDetail::where('user_name', $userName)->first();

        $availabilities = Availability::where('user_id', $userDetail->user_id)->get();
        
        if (!$userDetail || $userDetail->user->role !== 'artist' || $userDetail->user->on_boarding !== 'yes' || $availabilities->count() === 0) {
            abort(404, 'Tattoo not found');
        }
        
        $tattoo = $userDetail->user->artistDesigns()->where('slug', $tattooSlug)->first();



        $questions = QuestionSorting::query()
            ->where('user_id', $userDetail->user_id)
            ->where('is_active', true)
            ->whereHas('question')
            ->with('question')
            ->orderBy('order')
            ->get()
            ->map(function ($question) {
            if (!$question->question) {
                return null;
            }

            return [
                'id' => $question->question_id,
                'question' => $question->question->question,
                'description' => $question->question->description,
                'placeholder' => $question->question->placeholder,
                'type' => $question->question->type,
                'is_required' => $question->question->is_required,
                'is_active' => $question->is_active,
                'options' => $question->question->options,
            ];
        })->filter()->values()->all();

        $artistTimezone = $userDetail->timezone ?: 'UTC';
        $tattooDurationMinutes = (int) ($tattoo->session_duration ?? 0) * 60;
        if ($tattooDurationMinutes <= 0) {
            preg_match('/(\d+)/', (string) ($tattoo->session_duration ?? ''), $durationMatch);
            $tattooDurationMinutes = isset($durationMatch[1]) ? ((int) $durationMatch[1] * 60) : 120;
        }
        $artistAvailabilitySchedule = Availability::query()
            ->where('user_id', $userDetail->user_id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week')
            ->map(function ($rows) use ($artistTimezone) {
                return $rows->map(function ($availability) use ($artistTimezone) {
                    $startLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d') . ' ' . $availability->start_time, 'UTC')
                        ->setTimezone($artistTimezone)
                        ->format('H:i');
                    $endLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d') . ' ' . $availability->end_time, 'UTC')
                        ->setTimezone($artistTimezone)
                        ->format('H:i');

                    return [
                        'start' => $startLocal,
                        'end' => $endLocal,
                    ];
                })->values()->all();
            })
            ->toArray();
            
        // Refreshing the booking page should require reconnecting again.
        session()->forget('booking_verified_emails');

        return view('public.book', [
            'userDetail' => $userDetail,
            'tattoo' => $tattoo,
            'questions' => $questions,
            'requiredBookingQuestions' => $questions,
            'hasArtistQuestions' => !empty($questions),
            'artistAvailabilitySchedule' => $artistAvailabilitySchedule,
            'artistTimezone' => $artistTimezone,
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
        ]);
    }
}

