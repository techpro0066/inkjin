<?php

namespace App\Http\Controllers;

use App\Models\ArtistClientFeedback;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ArtistClientFeedbackController extends Controller
{
    public function show(Request $request, ArtistClientFeedback $feedback): View
    {
        $feedback->loadMissing([
            'booking.artist.userDetail',
            'booking.studio',
            'booking.tattoo',
            'booking.user',
            'client',
            'artist.userDetail',
        ]);

        $payload = $this->pagePayload($feedback);

        if ($feedback->isReviewed()) {
            return view('public.artist-client-feedback', array_merge($payload, [
                'mode' => 'readonly',
                'savedAnswers' => $this->savedAnswers($feedback),
            ]));
        }

        return view('public.artist-client-feedback', array_merge($payload, [
            'mode' => 'form',
            'submitUrl' => URL::temporarySignedRoute(
                'public.artist-client-feedback.store',
                now()->addDays(30),
                ['feedback' => $feedback->id]
            ),
            'preselectClient' => $this->scoreFromRequest($request, 'c'),
            'preselectBookpay' => $this->scoreFromRequest($request, 'b'),
        ]));
    }

    public function store(Request $request, ArtistClientFeedback $feedback): JsonResponse|RedirectResponse|View
    {
        if ($feedback->isReviewed()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'already_reviewed' => true,
                    'message' => 'Feedback already submitted.',
                    'answers' => $this->savedAnswers($feedback),
                ]);
            }

            return redirect()->to(
                URL::temporarySignedRoute(
                    'public.artist-client-feedback',
                    now()->addDays(30),
                    ['feedback' => $feedback->id]
                )
            );
        }

        $validated = $request->validate([
            'client_score' => ['required', 'integer', 'min:1', 'max:5'],
            'bookpay_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'client_comment' => ['nullable', 'string', 'max:2000'],
            'bookpay_comment' => ['nullable', 'string', 'max:2000'],
            'criteria' => ['nullable', 'array'],
            'criteria.communication' => ['nullable', 'in:up,dn'],
            'criteria.on_time' => ['nullable', 'in:up,dn'],
            'criteria.prepared' => ['nullable', 'in:up,dn'],
            'criteria.easy' => ['nullable', 'in:up,dn'],
            'criteria.payment' => ['nullable', 'in:up,dn'],
        ]);

        $clientScore = (int) $validated['client_score'];
        $bookpayScore = array_key_exists('bookpay_score', $validated) && $validated['bookpay_score'] !== null
            ? (int) $validated['bookpay_score']
            : null;
        $criteria = [];
        foreach (['communication', 'on_time', 'prepared', 'easy', 'payment'] as $key) {
            $val = $validated['criteria'][$key] ?? null;
            if (in_array($val, ['up', 'dn'], true)) {
                $criteria[$key] = $val;
            }
        }

        $clientComment = trim((string) ($validated['client_comment'] ?? ''));
        $bookpayComment = trim((string) ($validated['bookpay_comment'] ?? ''));

        $feedback->rating = $clientScore;
        $feedback->answers = [
            'client_session' => [
                'question' => 'How was your session with the client?',
                'score' => $clientScore,
                'comment' => $clientComment !== '' ? $clientComment : null,
            ],
            'criteria' => $criteria,
            'bookpay' => [
                'question' => 'How was Bookpay for this booking?',
                'score' => $bookpayScore,
                'comment' => $bookpayComment !== '' ? $bookpayComment : null,
            ],
        ];
        $feedback->status = ArtistClientFeedback::STATUS_REVIEWED;
        $feedback->save();

        $feedback->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Thanks for your feedback.',
                'answers' => $this->savedAnswers($feedback),
            ]);
        }

        $feedback->loadMissing([
            'booking.artist.userDetail',
            'booking.studio',
            'booking.tattoo',
            'booking.user',
            'client',
            'artist.userDetail',
        ]);

        return view('public.artist-client-feedback', array_merge($this->pagePayload($feedback), [
            'mode' => 'readonly',
            'savedAnswers' => $this->savedAnswers($feedback),
        ]));
    }

    /**
     * @return array{client_score:?int,bookpay_score:?int,client_comment:?string,bookpay_comment:?string,criteria:array<string,string>}
     */
    private function savedAnswers(ArtistClientFeedback $feedback): array
    {
        $answers = is_array($feedback->answers) ? $feedback->answers : [];

        $criteria = [];
        foreach ((array) ($answers['criteria'] ?? []) as $key => $value) {
            if (in_array($value, ['up', 'dn'], true)) {
                $criteria[(string) $key] = $value;
            }
        }

        $clientScore = $answers['client_session']['score'] ?? $feedback->rating;
        $bookpayScore = $answers['bookpay']['score'] ?? null;

        return [
            'client_score' => $clientScore !== null ? (int) $clientScore : null,
            'bookpay_score' => $bookpayScore !== null ? (int) $bookpayScore : null,
            'client_comment' => isset($answers['client_session']['comment'])
                ? (trim((string) $answers['client_session']['comment']) ?: null)
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
    private function pagePayload(ArtistClientFeedback $feedback): array
    {
        $booking = $feedback->booking;
        $client = $feedback->client ?? $booking?->user;

        $clientDisplay = $this->clientDisplayName($client);
        $clientFirst = $this->clientFirstName($client);

        $artistFirst = trim((string) ($booking?->artist?->first_name ?? $feedback->artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = 'there';
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

        return [
            'demo' => false,
            'feedback' => $feedback,
            'artistFirstName' => $artistFirst,
            'clientDisplayName' => $clientDisplay,
            'clientFirstName' => $clientFirst,
            'clientInitials' => $this->initials($clientDisplay),
            'sessionMeta' => implode(' · ', $metaParts),
        ];
    }

    private function clientDisplayName($client): string
    {
        $first = trim((string) ($client?->first_name ?? ''));
        $last = trim((string) ($client?->last_name ?? ''));
        if ($first === '') {
            return 'Client';
        }

        $display = ucfirst($first);
        if ($last !== '') {
            $display .= ' '.strtoupper(mb_substr($last, 0, 1)).'.';
        }

        return $display;
    }

    private function clientFirstName($client): string
    {
        $first = trim((string) ($client?->first_name ?? ''));

        return $first !== '' ? ucfirst($first) : 'the client';
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : 'CL';
    }

    private function sessionDateLabel($booking): string
    {
        if (! $booking) {
            return '';
        }

        $timezone = trim((string) ($booking->timezone ?: 'UTC')) ?: 'UTC';
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
