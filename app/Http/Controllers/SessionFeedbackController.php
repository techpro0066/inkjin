<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class SessionFeedbackController extends Controller
{
    public function show(Request $request, Review $review): View
    {
        $review->loadMissing([
            'booking.artist.userDetail',
            'booking.studio',
            'booking.tattoo',
            'booking.user',
            'artist.userDetail',
        ]);

        $payload = $this->pagePayload($review);

        if ($review->isReviewed()) {
            return view('public.feedback', array_merge($payload, [
                'mode' => 'readonly',
                'savedAnswers' => $this->savedAnswers($review),
            ]));
        }

        return view('public.feedback', array_merge($payload, [
            'mode' => 'form',
            'submitUrl' => URL::temporarySignedRoute(
                'public.session-feedback.store',
                now()->addDays(30),
                ['review' => $review->id]
            ),
            'preselectArtist' => $this->scoreFromRequest($request, 'a'),
            'preselectBookpay' => $this->scoreFromRequest($request, 'b'),
        ]));
    }

    public function store(Request $request, Review $review): JsonResponse|RedirectResponse|View
    {
        if ($review->isReviewed()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'already_reviewed' => true,
                    'message' => 'Feedback already submitted.',
                    'answers' => $this->savedAnswers($review),
                ]);
            }

            return redirect()->to(
                URL::temporarySignedRoute(
                    'public.session-feedback',
                    now()->addDays(30),
                    ['review' => $review->id]
                )
            );
        }

        $validated = $request->validate([
            'artist_score' => ['required', 'integer', 'min:1', 'max:5'],
            'bookpay_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'artist_comment' => ['nullable', 'string', 'max:2000'],
            'bookpay_comment' => ['nullable', 'string', 'max:2000'],
            'criteria' => ['nullable', 'array'],
            'criteria.communication' => ['nullable', 'in:up,dn'],
            'criteria.hygiene' => ['nullable', 'in:up,dn'],
            'criteria.tattoo' => ['nullable', 'in:up,dn'],
            'criteria.comfort' => ['nullable', 'in:up,dn'],
            'criteria.on_time' => ['nullable', 'in:up,dn'],
        ]);

        $artistScore = (int) $validated['artist_score'];
        $bookpayScore = array_key_exists('bookpay_score', $validated) && $validated['bookpay_score'] !== null
            ? (int) $validated['bookpay_score']
            : null;
        $criteria = [];
        foreach (['communication', 'hygiene', 'tattoo', 'comfort', 'on_time'] as $key) {
            $val = $validated['criteria'][$key] ?? null;
            if (in_array($val, ['up', 'dn'], true)) {
                $criteria[$key] = $val;
            }
        }

        $artistComment = trim((string) ($validated['artist_comment'] ?? ''));
        $bookpayComment = trim((string) ($validated['bookpay_comment'] ?? ''));

        $review->rating = $artistScore;
        $review->answers = [
            'artist_session' => [
                'question' => 'How was your session with the artist?',
                'score' => $artistScore,
                'comment' => $artistComment !== '' ? $artistComment : null,
            ],
            'criteria' => $criteria,
            'bookpay' => [
                'question' => 'How was booking and paying with Bookpay?',
                'score' => $bookpayScore,
                'comment' => $bookpayComment !== '' ? $bookpayComment : null,
            ],
        ];
        $review->status = Review::STATUS_REVIEWED;
        $review->save();

        $review->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Thanks for your feedback.',
                'answers' => $this->savedAnswers($review),
            ]);
        }

        $review->loadMissing([
            'booking.artist.userDetail',
            'booking.studio',
            'booking.tattoo',
            'booking.user',
            'artist.userDetail',
        ]);

        return view('public.feedback', array_merge($this->pagePayload($review), [
            'mode' => 'readonly',
            'savedAnswers' => $this->savedAnswers($review),
        ]));
    }

    /**
     * @return array{artist_score:?int,bookpay_score:?int,artist_comment:?string,bookpay_comment:?string,criteria:array<string,string>}
     */
    private function savedAnswers(Review $review): array
    {
        $answers = is_array($review->answers) ? $review->answers : [];

        $criteria = [];
        foreach ((array) ($answers['criteria'] ?? []) as $key => $value) {
            if (in_array($value, ['up', 'dn'], true)) {
                $criteria[(string) $key] = $value;
            }
        }

        $artistScore = $answers['artist_session']['score'] ?? $review->rating;
        $bookpayScore = $answers['bookpay']['score'] ?? null;

        return [
            'artist_score' => $artistScore !== null ? (int) $artistScore : null,
            'bookpay_score' => $bookpayScore !== null ? (int) $bookpayScore : null,
            'artist_comment' => isset($answers['artist_session']['comment'])
                ? (trim((string) $answers['artist_session']['comment']) ?: null)
                : null,
            'bookpay_comment' => isset($answers['bookpay']['comment'])
                ? (trim((string) $answers['bookpay']['comment']) ?: null)
                : null,
            'criteria' => $criteria,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pagePayload(Review $review): array
    {
        $booking = $review->booking;
        $client = $booking?->user;
        $clientFirst = trim((string) ($client?->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $artistName = $this->artistName($review);
        $artistFirst = trim((string) ($booking?->artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $design = trim((string) ($booking?->displayTitle() ?? ''));
        if ($design === '') {
            $design = 'Tattoo session';
        }

        $studioName = trim((string) ($booking?->studio?->name ?? ''));
        $dateLabel = $this->sessionDateLabel($booking);
        $metaParts = array_values(array_filter([
            $design,
            $dateLabel !== '' ? $dateLabel : null,
            $studioName !== '' ? $studioName : null,
        ]));

        $avatar = trim((string) ($booking?->artist?->userDetail?->avatar ?? ''));
        $avatarUrl = $avatar !== '' ? asset(ltrim($avatar, '/')) : null;
        $initials = $booking?->artist?->userDetail?->publicDisplayInitials()
            ?: strtoupper(substr(preg_replace('/\s+/', '', $artistName) ?: 'AR', 0, 2));

        return [
            'demo' => false,
            'review' => $review,
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistAvatarUrl' => $avatarUrl,
            'artistInitials' => $initials,
            'sessionMeta' => implode(' · ', $metaParts),
        ];
    }

    private function artistName(Review $review): string
    {
        $detail = $review->booking?->artist?->userDetail ?? $review->artist?->userDetail;
        $name = $detail ? trim((string) $detail->publicDisplayName()) : '';
        if ($name !== '' && $name !== 'Artist') {
            return $name;
        }

        $user = $review->booking?->artist ?? $review->artist;
        $fallback = trim(trim((string) ($user?->first_name ?? '')).' '.trim((string) ($user?->last_name ?? '')));

        return $fallback !== '' ? $fallback : 'your artist';
    }

    private function sessionDateLabel($booking): string
    {
        if (! $booking) {
            return '';
        }

        $timezone = trim((string) ($booking->timezone ?: $booking->artist?->userDetail?->timezone ?: 'UTC')) ?: 'UTC';
        $startLocal = $booking->sessionStartUtc()?->copy()->timezone($timezone);
        if ($startLocal) {
            return $startLocal->format('D j/m/Y');
        }

        if (! $booking->booking_date) {
            return '';
        }

        try {
            $date = $booking->booking_date instanceof Carbon
                ? $booking->booking_date
                : Carbon::parse((string) $booking->booking_date);

            return $date->format('D j/m/Y');
        } catch (\Throwable) {
            return '';
        }
    }

    private function scoreFromRequest(Request $request, string $key): ?int
    {
        if (! $request->filled($key)) {
            return null;
        }

        $score = (int) $request->query($key);

        return ($score >= 1 && $score <= 5) ? $score : null;
    }
}
