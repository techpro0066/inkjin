<?php

namespace App\Services;

use App\Mail\ConsentFormMail;
use App\Mail\ConsentFormSubmittedArtistMail;
use App\Models\Booking;
use App\Models\ConsentAnswer;
use App\Models\ConsentFormSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingConsentService
{
    public function __construct(
        private ConsentFormQuestionService $consentQuestions
    ) {}

    /**
     * Ensure a consent_answers row exists for a confirmed booking and keep send_at in sync.
     */
    public function syncForBooking(Booking $booking): ?ConsentAnswer
    {
        if ((string) $booking->status === 'cancelled') {
            return $this->cancelForBooking($booking);
        }

        if ((string) $booking->status !== 'confirmed') {
            return ConsentAnswer::query()->where('booking_id', $booking->id)->first();
        }

        $existing = ConsentAnswer::query()->where('booking_id', $booking->id)->first();
        if ($existing && $existing->isCompleted()) {
            return $existing;
        }

        $sendAt = $this->computeSendAt($booking);

        if ($existing) {
            if ($existing->status === ConsentAnswer::STATUS_CANCELLED) {
                $existing->status = ConsentAnswer::STATUS_PENDING;
                $existing->sent_at = null;
            }

            if (! $existing->isCompleted()) {
                $existing->send_at = $sendAt;
                $existing->artist_user_id = (int) $booking->artist_user_id;
                $existing->client_user_id = $booking->user_id ? (int) $booking->user_id : null;
                $existing->save();
            }

            return $existing->fresh();
        }

        return ConsentAnswer::query()->create([
            'booking_id' => $booking->id,
            'artist_user_id' => (int) $booking->artist_user_id,
            'client_user_id' => $booking->user_id ? (int) $booking->user_id : null,
            'token' => $this->uniqueToken(),
            'status' => ConsentAnswer::STATUS_PENDING,
            'send_at' => $sendAt,
        ]);
    }

    public function cancelForBooking(Booking $booking): ?ConsentAnswer
    {
        $existing = ConsentAnswer::query()->where('booking_id', $booking->id)->first();
        if (! $existing || $existing->isCompleted()) {
            return $existing;
        }

        $existing->status = ConsentAnswer::STATUS_CANCELLED;
        $existing->save();

        return $existing;
    }

    /**
     * Send due consent emails (artist send_automatically must be on).
     *
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function sendDue(?\DateTimeInterface $now = null): array
    {
        $now = \Carbon\Carbon::parse($now ?? now());
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $due = ConsentAnswer::query()
            ->whereIn('status', [ConsentAnswer::STATUS_PENDING, ConsentAnswer::STATUS_SENT])
            ->whereNull('sent_at')
            ->whereNotNull('send_at')
            ->where('send_at', '<=', $now)
            ->whereHas('booking', function ($q) {
                $q->where('status', 'confirmed')
                    ->whereNull('reminder_24h_sent_at');
            })
            ->with(['booking.user', 'booking.artist.userDetail', 'artist.userDetail'])
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($due as $consent) {
            $settings = ConsentFormSetting::query()
                ->where('user_id', $consent->artist_user_id)
                ->first();

            if (! $settings || ! $settings->send_automatically) {
                $skipped++;
                continue;
            }

            $booking = $consent->booking;
            $start = $booking?->sessionStartUtc();
            if (! $booking || ! $start || $start->lte($now)) {
                $consent->status = ConsentAnswer::STATUS_EXPIRED;
                $consent->save();
                $skipped++;
                continue;
            }

            if ($this->sendEmail($consent)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return compact('sent', 'failed', 'skipped');
    }

    public function sendEmail(ConsentAnswer $consent): bool
    {
        $consent->loadMissing(['booking.user', 'artist.userDetail']);
        $booking = $consent->booking;
        $client = $booking?->user;
        $email = trim((string) ($client?->email ?? ''));

        if (! $booking || ! $client || $email === '') {
            Log::warning('Consent email skipped: missing client email', [
                'consent_id' => $consent->id,
                'booking_id' => $consent->booking_id,
            ]);

            return false;
        }

        $claimed = false;
        try {
            $claimed = DB::transaction(function () use ($consent) {
                $locked = ConsentAnswer::query()
                    ->whereKey($consent->id)
                    ->whereNull('sent_at')
                    ->whereIn('status', [ConsentAnswer::STATUS_PENDING, ConsentAnswer::STATUS_SENT])
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return false;
                }

                $locked->update([
                    'sent_at' => now(),
                    'status' => ConsentAnswer::STATUS_SENT,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to claim consent email send', [
                'consent_id' => $consent->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $claimed) {
            return false;
        }

        try {
            $artist = $consent->artist;
            $detail = $artist?->userDetail;
            if ($detail && $artist) {
                $detail->setRelation('user', $artist);
            }

            Mail::to($email)->send(new ConsentFormMail(
                $consent->fresh(['booking', 'artist.userDetail']),
                $client,
                $detail
            ));

            return true;
        } catch (\Throwable $e) {
            ConsentAnswer::query()->whereKey($consent->id)->update([
                'sent_at' => null,
                'status' => ConsentAnswer::STATUS_PENDING,
            ]);
            Log::error('Failed to send consent form email', [
                'consent_id' => $consent->id,
                'booking_id' => $consent->booking_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Manually (re)send consent email for an open consent form.
     */
    public function resendEmail(ConsentAnswer $consent): bool
    {
        if ($consent->isCompleted() || ! $consent->isOpenForClient()) {
            return false;
        }

        $consent->loadMissing(['booking.user', 'artist.userDetail']);
        $booking = $consent->booking;
        $client = $booking?->user;
        $email = trim((string) ($client?->email ?? ''));

        if (! $booking || ! $client || $email === '' || (string) $booking->status !== 'confirmed') {
            return false;
        }

        try {
            $artist = $consent->artist;
            $detail = $artist?->userDetail;
            if ($detail && $artist) {
                $detail->setRelation('user', $artist);
            }

            Mail::to($email)->send(new ConsentFormMail(
                $consent->fresh(['booking', 'artist.userDetail']),
                $client,
                $detail
            ));

            $consent->forceFill([
                'sent_at' => now(),
                'status' => ConsentAnswer::STATUS_SENT,
            ])->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to resend consent form email', [
                'consent_id' => $consent->id,
                'booking_id' => $consent->booking_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(ConsentAnswer $consent, array $data): ConsentAnswer
    {
        if ($consent->isCompleted()) {
            return $consent;
        }

        if (! $consent->isOpenForClient()) {
            throw new \RuntimeException('This consent form is no longer available.');
        }

        $consent->fill([
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'emergency_contact' => trim((string) ($data['emergency_contact'] ?? '')) ?: null,
            'guardian_name' => trim((string) ($data['guardian_name'] ?? '')) ?: null,
            'guardian_relationship' => trim((string) ($data['guardian_relationship'] ?? '')) ?: null,
            'guardian_id_reference' => trim((string) ($data['guardian_id_reference'] ?? '')) ?: null,
            'guardian_signature' => trim((string) ($data['guardian_signature'] ?? '')) ?: null,
            'accepted_risks' => (bool) ($data['accepted_risks'] ?? false),
            'accepted_health_consent' => (bool) ($data['accepted_health_consent'] ?? false),
            'accepted_aftercare' => (bool) ($data['accepted_aftercare'] ?? false),
            'accepted_data_notice' => (bool) ($data['accepted_data_notice'] ?? false),
            'accepted_photo' => (bool) ($data['accepted_photo'] ?? false),
            'typed_signature' => trim((string) ($data['typed_signature'] ?? '')),
            'form_language' => strtolower(trim((string) ($data['form_language'] ?? 'en'))) ?: 'en',
            'answers' => $data['answers'] ?? [],
            'status' => ConsentAnswer::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        $consent->save();

        $saved = $consent->fresh(['booking.user', 'artist.userDetail']);
        $this->notifyArtistOfSubmission($saved);

        return $saved;
    }

    private function notifyArtistOfSubmission(ConsentAnswer $consent): void
    {
        $artist = $consent->artist;
        $email = trim((string) ($artist?->email ?? ''));
        if (! $artist || $email === '') {
            return;
        }

        try {
            $detail = $artist->userDetail;
            if ($detail) {
                $detail->setRelation('user', $artist);
            }

            Mail::to($email)->send(new ConsentFormSubmittedArtistMail(
                $consent,
                $artist,
                $detail
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send artist consent-submitted email', [
                'consent_id' => $consent->id,
                'booking_id' => $consent->booking_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Client form payload for a live consent token.
     *
     * @return array<string, mixed>
     */
    public function clientPayload(ConsentAnswer $consent): array
    {
        $consent->loadMissing(['booking', 'artist.userDetail']);
        $artist = $consent->artist;
        if (! $artist || (string) $artist->role !== 'artist') {
            throw new \RuntimeException('Artist not found.');
        }

        $base = $this->artistFormPayload($artist);
        $booking = $consent->booking;
        $appointment = null;
        if ($booking) {
            $local = $booking->sessionStartLocal();
            $studio = $base['artist']['studio'] ?? null;
            $parts = array_values(array_filter([
                $studio,
                $local ? $local->format('D, M j') : null,
                $local ? $local->format('g:i A') : null,
            ]));
            $appointment = [
                'label' => implode(' · ', $parts),
                'date' => $local?->toDateString(),
                'time' => $local?->format('H:i'),
            ];
        }

        $base['mode'] = 'live';
        $base['consent'] = [
            'id' => $consent->id,
            'token' => $consent->token,
            'status' => $consent->status,
            'completed' => $consent->isCompleted(),
            'signed_name' => $consent->typed_signature,
            'submit_url' => route('public.consent.submit', ['token' => $consent->token]),
        ];
        $base['appointment'] = $appointment;

        return $base;
    }

    /**
     * Preview payload by artist id (no booking / no submit).
     *
     * @return array<string, mixed>
     */
    public function previewPayload(User $artist): array
    {
        $base = $this->artistFormPayload($artist);
        $base['mode'] = 'preview';
        $base['consent'] = [
            'id' => null,
            'token' => null,
            'status' => 'preview',
            'completed' => false,
            'submit_url' => null,
        ];
        $base['appointment'] = null;

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    public function artistFormPayload(User $artist): array
    {
        $detail = $artist->userDetail;
        if ($detail) {
            $detail->setRelation('user', $artist);
        }

        $defaultMarket = ConsentFormSetting::defaultMarketForArtist($artist, $detail);

        $settings = ConsentFormSetting::query()->where('user_id', $artist->id)->first()
            ?: $this->defaultSettingsStub((int) $artist->id, $defaultMarket);

        $languages = $settings->clientLanguages();
        $primaryLang = $languages[0] ?? 'en';

        $questions = $this->consentQuestions
            ->listForArtist((int) $artist->id)
            ->filter(fn (array $q) => (bool) ($q['enabled'] ?? false))
            ->values()
            ->map(function (array $q) use ($languages, $primaryLang) {
                $translations = is_array($q['translations'] ?? null) ? $q['translations'] : [];

                return [
                    'id' => (int) $q['id'],
                    'question_type' => (string) $q['question_type'],
                    'label' => $this->resolveLabel($translations, $languages, $primaryLang),
                    'translations' => $translations,
                ];
            });

        $artistName = $detail
            ? $detail->publicDisplayName()
            : (trim((string) $artist->name) !== '' ? $artist->name : 'Artist');
        $initials = $detail
            ? $detail->publicDisplayInitials()
            : strtoupper(mb_substr(preg_replace('/\s+/', '', $artistName) ?: 'AR', 0, 2));
        $studio = trim((string) ($detail?->studio_name ?? ''));
        $avatarPath = trim((string) ($detail?->avatar ?? ''));

        return [
            'artist' => [
                'id' => (int) $artist->id,
                'name' => $artistName,
                'initials' => $initials,
                'studio' => $studio !== '' ? $studio : null,
                'avatar' => $avatarPath !== '' ? asset(ltrim($avatarPath, '/')) : null,
            ],
            'settings' => [
                'studio_market' => strtoupper((string) ($settings->studio_market ?: $defaultMarket)),
                'registration_number' => $settings->registration_number,
                'allow_younger' => (bool) $settings->allow_younger,
                'age_allow' => $settings->age_allow !== null ? (int) $settings->age_allow : null,
                'language_mode' => $settings->language_mode === ConsentFormSetting::LANGUAGE_MODE_BOTH
                    ? ConsentFormSetting::LANGUAGE_MODE_BOTH
                    : ConsentFormSetting::LANGUAGE_MODE_SINGLE,
                'form_language' => $settings->form_language ?: 'en',
                'other_language' => $settings->other_language,
                'ask_photo' => (bool) $settings->ask_photo,
                'client_languages' => $languages,
            ],
            'questions' => [
                'health' => $questions->where('question_type', 'health')->values()->all(),
                'risk' => $questions->where('question_type', 'risk')->values()->all(),
                'aftercare' => $questions->where('question_type', 'aftercare')->values()->all(),
            ],
        ];
    }

    public function computeSendAt(Booking $booking): ?\Carbon\Carbon
    {
        $start = $booking->sessionStartUtc();
        if (! $start) {
            return null;
        }

        $sendAt = $start->copy()->subHours(24);

        // If already inside the 24h window, send as soon as the cron runs.
        if ($sendAt->lt(now())) {
            return now();
        }

        return $sendAt;
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (ConsentAnswer::query()->where('token', $token)->exists());

        return $token;
    }

    private function defaultSettingsStub(int $userId, string $market): ConsentFormSetting
    {
        $locale = ConsentFormSetting::localeForMarket($market);

        return new ConsentFormSetting([
            'user_id' => $userId,
            'studio_market' => $market,
            'registration_number' => null,
            'allow_younger' => false,
            'age_allow' => null,
            'language_mode' => ConsentFormSetting::LANGUAGE_MODE_SINGLE,
            'form_language' => 'en',
            'other_language' => $locale !== 'en' ? $locale : null,
            'ask_photo' => true,
            'send_automatically' => false,
        ]);
    }

    /**
     * @param  array<string, string>  $translations
     * @param  list<string>  $languages
     */
    private function resolveLabel(array $translations, array $languages, string $primaryLang): string
    {
        $try = array_values(array_unique(array_filter([
            $primaryLang,
            ...$languages,
            'en',
        ])));

        foreach ($try as $lang) {
            $text = trim((string) ($translations[$lang] ?? ''));
            if ($text !== '') {
                return $text;
            }
        }

        foreach ($translations as $text) {
            $text = trim((string) $text);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }
}
