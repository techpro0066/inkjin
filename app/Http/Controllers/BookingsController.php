<?php

namespace App\Http\Controllers;

use App\Mail\BookingCompletionCodeMail;
use App\Models\BalanceCollection;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Models\PaymentLink;
use App\Services\ArtistPayoutService;
use App\Services\BookingConsentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingsController extends Controller
{
    /**
     * Display a listing of bookings for the authenticated user.
     */
    public function index(Request $request)
    {
        $artistId = (int) Auth::id();
        $today = Carbon::today()->toDateString();
        $filter = strtolower(trim((string) $request->input('filter', 'upcoming')));
        if (! in_array($filter, ['upcoming', 'past', 'pending', 'cancelled', 'all'], true)) {
            $filter = 'upcoming';
        }
        $sort = strtolower(trim((string) $request->input('sort', 'date_asc')));
        if (! in_array($sort, ['date_asc', 'date_desc', 'booking_desc', 'name_asc'], true)) {
            $sort = 'date_asc';
        }
        $search = trim((string) $request->input('q', ''));
        $dateFrom = trim((string) $request->input('from', ''));
        $dateTo = trim((string) $request->input('to', ''));

        $countBase = Booking::query()->where('artist_user_id', $artistId);
        $filterCounts = [
            'upcoming' => (clone $countBase)
                ->where('status', 'confirmed')
                ->whereDate('booking_date', '>=', $today)
                ->count(),
            'past' => (clone $countBase)
                ->where(function ($q) use ($today) {
                    $q->whereIn('status', ['completed', 'no_show'])
                        ->orWhere(function ($q2) use ($today) {
                            $q2->where('status', 'confirmed')
                                ->whereDate('booking_date', '<', $today);
                        });
                })
                ->count(),
            'pending' => (clone $countBase)->where('status', 'pending')->count(),
            'cancelled' => (clone $countBase)->whereIn('status', ['cancelled', 'declined'])->count(),
        ];

        $query = Booking::query()
            ->where('artist_user_id', $artistId)
            ->with(['user.userDetail', 'tattoo', 'latestBalanceCollection', 'consentAnswer', 'studio']);

        match ($filter) {
            'upcoming' => $query->where('status', 'confirmed')->whereDate('booking_date', '>=', $today),
            'past' => $query->where(function ($q) use ($today) {
                $q->whereIn('status', ['completed', 'no_show'])
                    ->orWhere(function ($q2) use ($today) {
                        $q2->where('status', 'confirmed')
                            ->whereDate('booking_date', '<', $today);
                    });
            }),
            'pending' => $query->where('status', 'pending'),
            'cancelled' => $query->whereIn('status', ['cancelled', 'declined']),
            default => null,
        };

        if ($dateFrom !== '') {
            $query->whereDate('booking_date', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->whereDate('booking_date', '<=', $dateTo);
        }

        if ($search !== '') {
            $digits = preg_replace('/\D+/', '', $search) ?: '';
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_strtolower($search)).'%';
            $query->where(function ($q) use ($like, $digits) {
                $q->whereHas('user', function ($uq) use ($like) {
                    $uq->whereRaw('LOWER(CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, ""))) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                });
                if ($digits !== '') {
                    $q->orWhere('id', (int) $digits)
                        ->orWhereRaw('CAST(id AS CHAR) LIKE ?', ['%'.$digits.'%']);
                }
            });
        }

        match ($sort) {
            'date_desc' => $query->orderByDesc('booking_date')->orderByDesc('id'),
            'booking_desc' => $query->orderByDesc('id'),
            'name_asc' => $query
                ->leftJoin('users', 'users.id', '=', 'bookings.user_id')
                ->orderByRaw('LOWER(CONCAT(COALESCE(users.first_name, ""), " ", COALESCE(users.last_name, ""))) ASC')
                ->orderBy('bookings.id')
                ->select('bookings.*'),
            default => $query->orderBy('booking_date')->orderBy('id'),
        };

        $bookings = $query->paginate(50)->withQueryString();

        $calendarBookings = Booking::query()
            ->where('artist_user_id', $artistId)
            ->whereNotIn('status', ['cancelled', 'declined'])
            ->whereDate('booking_date', '>=', Carbon::today()->subMonths(3)->toDateString())
            ->whereDate('booking_date', '<=', Carbon::today()->addMonths(12)->toDateString())
            ->with(['user:id,first_name,last_name', 'tattoo:id,title'])
            ->orderBy('booking_date')
            ->get()
            ->map(function (Booking $booking) {
                $clientName = trim(($booking->user?->first_name ?? '').' '.($booking->user?->last_name ?? ''));
                if ($clientName === '') {
                    $clientName = 'Client #'.($booking->user_id ?? '');
                }
                $bt = $booking->booking_time;
                $time = ($bt !== null && isset($bt['start'])) ? (string) $bt['start'] : '';
                $end = ($bt !== null && isset($bt['end'])) ? (string) $bt['end'] : '';
                $details = is_array($booking->custom_tattoo_details) ? $booking->custom_tattoo_details : [];
                $customTitle = $details['title'] ?? null;
                $service = ($booking->tattoo && filled($booking->tattoo->title))
                    ? (string) $booking->tattoo->title
                    : (filled($customTitle) ? (string) $customTitle : ucfirst((string) $booking->booking_type).' booking');

                return [
                    'id' => $booking->id,
                    'date' => $booking->booking_date?->format('Y-m-d'),
                    'status' => strtolower((string) ($booking->status ?? '')),
                    'client' => $clientName,
                    'time' => $time,
                    'end' => $end,
                    'service' => $service,
                    'ref' => $booking->referenceLabel(),
                ];
            })
            ->values()
            ->all();

        $requestsCount = BookingRequest::query()
            ->where('artist_id', $artistId)
            ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
            ->count()
            + CustomRequest::query()
                ->where('artist_id', $artistId)
                ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                ->count();

        $paymentLinksCount = $this->artistPaymentLinksTotalCount($artistId);

        $bookingsTotalCount = (int) array_sum($filterCounts);

        $userDetail = Auth::user()?->userDetail;
        $payoutService = app(ArtistPayoutService::class);
        $canCreatePaymentLinks = $userDetail
            ? $payoutService->canAcceptClientPayments($userDetail)
            : false;
        $isAutoScheduling = ($userDetail?->scheduling_type ?? '') === 'auto';
        $paymentLinksBlockedMessage = $canCreatePaymentLinks || ! $userDetail
            ? null
            : $payoutService->clientPaymentsBlockedMessage($userDetail);
        $currency = '€';

        return view('artist.bookings.index', compact(
            'bookings',
            'filter',
            'sort',
            'search',
            'dateFrom',
            'dateTo',
            'filterCounts',
            'calendarBookings',
            'requestsCount',
            'paymentLinksCount',
            'bookingsTotalCount',
            'canCreatePaymentLinks',
            'isAutoScheduling',
            'paymentLinksBlockedMessage',
            'currency',
        ));
    }

    public function paymentLinks(Request $request, ArtistPayoutService $payoutService)
    {
        $artistId = (int) Auth::id();
        $user = Auth::user();
        $userDetail = $user?->userDetail;

        $filter = strtolower(trim((string) $request->input('filter', 'all')));
        if (! in_array($filter, ['all', 'open', 'paid', 'expired'], true)) {
            $filter = 'all';
        }
        $search = trim((string) $request->input('q', ''));

        $rows = $this->artistPaymentLinkRows($artistId);

        $filterCounts = [
            'all' => $rows->count(),
            'open' => $rows->where('status', 'open')->count(),
            'paid' => $rows->where('status', 'paid')->count(),
            'expired' => $rows->where('status', 'expired')->count(),
        ];

        $filtered = $rows;
        if ($filter !== 'all') {
            $filtered = $filtered->where('status', $filter)->values();
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filtered = $filtered->filter(function ($row) use ($needle) {
                $hay = mb_strtolower(implode(' ', array_filter([
                    (string) ($row->title ?? ''),
                    (string) ($row->client ?? ''),
                    (string) ($row->code ?? ''),
                    (string) ($row->badge ?? ''),
                ])));

                return str_contains($hay, $needle);
            })->values();
        }

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $paymentLinks = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $today = Carbon::today()->toDateString();
        $bookingsTotalCount = Booking::query()
            ->where('artist_user_id', $artistId)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['pending', 'cancelled', 'declined', 'completed', 'no_show'])
                    ->orWhere(function ($q2) use ($today) {
                        $q2->where('status', 'confirmed');
                    });
            })
            ->count();
        $requestsCount = BookingRequest::query()
            ->where('artist_id', $artistId)
            ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
            ->count()
            + CustomRequest::query()
                ->where('artist_id', $artistId)
                ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                ->count();
        $paymentLinksCount = $filterCounts['all'];

        $canCreatePaymentLinks = $userDetail
            ? $payoutService->canAcceptClientPayments($userDetail)
            : false;
        $isAutoScheduling = ($userDetail?->scheduling_type ?? '') === 'auto';
        $paymentLinksBlockedMessage = $canCreatePaymentLinks || ! $userDetail
            ? null
            : $payoutService->clientPaymentsBlockedMessage($userDetail);
        $currency = '€';
        $paymentLinkModalReloadOnDone = true;

        return view('artist.bookings.payment-links', compact(
            'paymentLinks',
            'bookingsTotalCount',
            'requestsCount',
            'paymentLinksCount',
            'canCreatePaymentLinks',
            'isAutoScheduling',
            'paymentLinksBlockedMessage',
            'currency',
            'paymentLinkModalReloadOnDone',
            'filter',
            'search',
            'filterCounts',
        ));
    }

    private function artistPaymentLinksTotalCount(int $artistId): int
    {
        $standalone = PaymentLink::query()->where('artist_id', $artistId)->count();
        $bookingLinks = BalanceCollection::query()
            ->where('artist_user_id', $artistId)
            ->where('collection_type', BalanceCollection::TYPE_PAYMENT_LINK)
            ->whereNotNull('payment_link_code')
            ->where('payment_link_code', '!=', '')
            ->count();

        return $standalone + $bookingLinks;
    }

    /**
     * @return Collection<int, object>
     */
    private function artistPaymentLinkRows(int $artistId): Collection
    {
        $now = now();

        $standalone = PaymentLink::query()
            ->where('artist_id', $artistId)
            ->with(['booking.user'])
            ->orderByDesc('id')
            ->get()
            ->map(function (PaymentLink $link) use ($now) {
                $status = $link->listStatus();
                if ($status === 'pending') {
                    $status = 'open';
                }
                // listStatus already handles paid/expired; normalize pending→open
                if ($status !== 'paid' && $status !== 'expired') {
                    $status = 'open';
                }
                if ($status === 'open' && $link->expires_at && $link->expires_at->lt($now) && ! $link->isPaid()) {
                    $status = 'expired';
                }

                $clientName = trim((string) ($link->payer_name ?? ''));
                if ($clientName === '') {
                    $bookingUser = $link->booking?->user;
                    if ($bookingUser) {
                        $clientName = trim(($bookingUser->first_name ?? '').' '.($bookingUser->last_name ?? ''));
                    }
                }
                if ($clientName === '') {
                    $clientName = 'Not used yet';
                }

                return (object) [
                    'source' => 'standalone',
                    'id' => $link->id,
                    'title' => (string) $link->title,
                    'client' => $clientName,
                    'deposit' => $link->depositAmount(),
                    'total' => $link->totalAmount(),
                    'status' => $status,
                    'created_at' => $link->created_at,
                    'url' => $link->publicUrl(),
                    'code' => (string) $link->code,
                    'booking_id' => $link->booking_id ? (int) $link->booking_id : null,
                    'badge' => null,
                ];
            });

        $bookingLinks = BalanceCollection::query()
            ->where('artist_user_id', $artistId)
            ->where('collection_type', BalanceCollection::TYPE_PAYMENT_LINK)
            ->whereNotNull('payment_link_code')
            ->where('payment_link_code', '!=', '')
            ->with(['booking.tattoo', 'booking.user', 'client'])
            ->orderByDesc('id')
            ->get()
            ->map(function (BalanceCollection $collection) {
                $booking = $collection->booking;
                $sessionTitle = trim((string) ($booking?->displayTitle() ?? ''));
                $title = $sessionTitle !== ''
                    ? 'Balance · '.$sessionTitle
                    : 'Balance payment';

                $clientUser = $collection->client ?: $booking?->user;
                $clientName = $clientUser
                    ? trim(($clientUser->first_name ?? '').' '.($clientUser->last_name ?? ''))
                    : '';
                if ($clientName === '') {
                    $clientName = 'Not used yet';
                }

                $amount = round((float) $collection->amount, 2);
                $depositPaid = round((float) ($booking?->deposit_amount ?? 0), 2);
                $bookingTotal = $depositPaid > 0 ? round($depositPaid + $amount, 2) : $amount;
                $status = $collection->isPaid()
                    ? 'paid'
                    : ($collection->isPaymentLinkExpired() || $collection->status === BalanceCollection::STATUS_FAILED
                        ? 'expired'
                        : 'open');

                $url = trim((string) ($collection->payment_link_url ?? ''));
                if ($url === '' && $collection->payment_link_code) {
                    $url = url('/p/'.$collection->payment_link_code);
                }

                return (object) [
                    'source' => 'booking',
                    'id' => $collection->id,
                    'title' => $title,
                    'client' => $clientName,
                    'deposit' => $amount,
                    'total' => $bookingTotal > 0 ? $bookingTotal : $amount,
                    'status' => $status,
                    'created_at' => $collection->created_at,
                    'url' => $url,
                    'code' => (string) $collection->payment_link_code,
                    'booking_id' => $collection->booking_id ? (int) $collection->booking_id : null,
                    'badge' => 'From booking',
                ];
            });

        return $standalone
            ->concat($bookingLinks)
            ->sortByDesc(function ($row) {
                return optional($row->created_at)->getTimestamp() ?? 0;
            })
            ->values();
    }

    public function expirePaymentLink(Request $request, string $source, int $id)
    {
        $artistId = (int) Auth::id();

        if ($source === 'standalone') {
            $link = PaymentLink::query()
                ->whereKey($id)
                ->where('artist_id', $artistId)
                ->firstOrFail();

            if ($link->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This link has already been paid and can’t be expired.',
                ], 422);
            }

            if ($link->listStatus() === 'expired') {
                return response()->json([
                    'success' => true,
                    'message' => 'Link expired',
                    'status' => 'expired',
                ]);
            }

            $link->forceFill([
                'status' => PaymentLink::STATUS_EXPIRED,
                'expires_at' => now(),
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'Link expired',
                'status' => 'expired',
            ]);
        }

        if ($source === 'booking') {
            $collection = BalanceCollection::query()
                ->whereKey($id)
                ->where('artist_user_id', $artistId)
                ->where('collection_type', BalanceCollection::TYPE_PAYMENT_LINK)
                ->firstOrFail();

            if ($collection->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This link has already been paid and can’t be expired.',
                ], 422);
            }

            if ($collection->isPaymentLinkExpired()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Link expired',
                    'status' => 'expired',
                ]);
            }

            $collection->markPaymentLinkExpired();

            return response()->json([
                'success' => true,
                'message' => 'Link expired',
                'status' => 'expired',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid payment link.',
        ], 404);
    }

    public function resendConsent(Request $request, int $id, BookingConsentService $consents)
    {
        $booking = Booking::query()
            ->with(['user', 'consentAnswer', 'artist.userDetail'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ((string) $booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed bookings can receive a consent form.',
            ], 422);
        }

        $consent = $consents->syncForBooking($booking);
        if (! $consent || $consent->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => $consent?->isCompleted()
                    ? 'Consent form already completed.'
                    : 'Unable to prepare consent form.',
            ], 422);
        }

        if (! $consents->resendEmail($consent)) {
            return response()->json([
                'success' => false,
                'message' => 'Could not send consent email. Check the client has an email address.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Consent form email sent.',
            'consent_url' => $consent->fresh()->publicUrl(),
        ]);
    }

    public function consentLink(Request $request, int $id, BookingConsentService $consents)
    {
        $booking = Booking::query()
            ->with('consentAnswer')
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ((string) $booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Consent links are only available for confirmed bookings.',
            ], 422);
        }

        $consent = $consents->syncForBooking($booking);
        if (! $consent || $consent->isCompleted() || ! $consent->isOpenForClient()) {
            return response()->json([
                'success' => false,
                'message' => $consent?->isCompleted()
                    ? 'Consent form already completed.'
                    : 'Unable to prepare consent form link.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'consent_url' => $consent->publicUrl(),
        ]);
    }

    public function updateConsentAgeVerified(Request $request, int $id)
    {
        $validated = $request->validate([
            'age_verified_by_artist' => ['required', 'boolean'],
        ]);

        $booking = Booking::query()
            ->with('consentAnswer')
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $consent = $booking->consentAnswer;
        if (! $consent || ! $consent->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Consent form must be completed before verifying age.',
            ], 422);
        }

        $consent->update([
            'age_verified_by_artist' => (bool) $validated['age_verified_by_artist'],
        ]);

        return response()->json([
            'success' => true,
            'age_verified_by_artist' => (bool) $consent->age_verified_by_artist,
        ]);
    }

    public function updateConsentGuardian(Request $request, int $id)
    {
        $validated = $request->validate([
            'guardian_name' => ['required', 'string', 'min:2', 'max:255'],
            'guardian_relationship' => ['required', 'string', 'min:1', 'max:255'],
            'guardian_id_reference' => ['required', 'string', 'min:1', 'max:255'],
            'guardian_signature' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $booking = Booking::query()
            ->with('consentAnswer')
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $consent = $booking->consentAnswer;
        if (! $consent || ! $consent->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Consent form must be completed before saving guardian details.',
            ], 422);
        }

        if (! $consent->date_of_birth || $consent->date_of_birth->age >= 18) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian details are only required for clients under 18.',
            ], 422);
        }

        $consent->update([
            'guardian_name' => trim((string) $validated['guardian_name']),
            'guardian_relationship' => trim((string) $validated['guardian_relationship']),
            'guardian_id_reference' => trim((string) $validated['guardian_id_reference']),
            'guardian_signature' => trim((string) $validated['guardian_signature']),
            'guardian_signed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'guardian_name' => $consent->guardian_name,
            'guardian_relationship' => $consent->guardian_relationship,
            'guardian_id_reference' => $consent->guardian_id_reference,
            'guardian_signature' => $consent->guardian_signature,
            'guardian_signed_at' => $consent->guardian_signed_at
                ? $consent->guardian_signed_at->timezone(config('app.timezone'))->format('M j, Y · g:i A')
                : null,
        ]);
    }

    public function sendCompletionCode(Request $request, int $id)
    {
        $booking = Booking::query()
            ->with(['user', 'artist'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed bookings can be completed.',
            ], 400);
        }

        if (!$booking->completion_code) {
            do {
                $code = strtoupper(Str::random(6));
            } while (Booking::query()->where('completion_code', $code)->exists());

            $booking->completion_code = $code;
            $booking->save();
        }

        try {
            Mail::to($booking->user->email)->send(new BookingCompletionCodeMail($booking));
        } catch (\Throwable $e) {
            Log::error('Failed to send booking completion code email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not send completion code email.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Completion code sent to client email.',
        ]);
    }

    public function liveStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(50)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['bookings' => []]);
        }

        $bookings = Booking::query()
            ->where('artist_user_id', Auth::id())
            ->whereIn('id', $ids)
            ->get(['id', 'status', 'remaining_amount_released', 'full_amount_paid']);

        $paidIds = BalanceCollection::query()
            ->whereIn('booking_id', $ids)
            ->where(function ($query) {
                $query->where('status', BalanceCollection::STATUS_PAID)
                    ->orWhere('status', BalanceCollection::STATUS_CASH_CONFIRMED)
                    ->orWhere('payment_status', BalanceCollection::PAYMENT_STATUS_PAID);
            })
            ->pluck('booking_id')
            ->unique()
            ->all();

        $payload = $bookings->map(function (Booking $booking) use ($paidIds) {
            $status = strtolower((string) $booking->status);
            $completed = $status === 'completed';
            $balanceCollected = (bool) $booking->remaining_amount_released
                || in_array((int) $booking->id, array_map('intval', $paidIds), true);

            return [
                'id' => (int) $booking->id,
                'status' => $status,
                'completed' => $completed,
                'balance_collected' => $balanceCollected,
                'settled' => $completed,
                'message' => 'This booking is already completed.',
            ];
        })->values();

        return response()->json(['bookings' => $payload]);
    }

    public function markCompleted(Request $request, int $id)
    {
        $booking = Booking::query()
            ->with(['user', 'artist'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ((string) $booking->status === 'completed') {
            return response()->json([
                'success' => true,
                'already_completed' => true,
                'message' => 'This booking is already completed.',
            ]);
        }

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed bookings can be completed.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'completion_code' => 'required|string|min:4|max:32',
            'confirmed' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$request->boolean('confirmed')) {
            return response()->json([
                'success' => false,
                'message' => 'Completion must be confirmed.',
            ], 400);
        }

        $inputCode = strtoupper(trim((string) $request->input('completion_code')));
        $storedCode = strtoupper(trim((string) $booking->completion_code));
        if ($storedCode === '' || !hash_equals($storedCode, $inputCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid completion code.',
            ], 422);
        }

        $this->completeArtistBooking($booking);

        return response()->json([
            'success' => true,
            'message' => 'Booking marked as completed.',
        ]);
    }

    public function storeBalanceCollection(Request $request, int $id)
    {
        $booking = Booking::query()
            ->with(['user', 'tattoo'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $type = (string) $request->input('collection_type');
        $rules = [
            'collection_type' => ['required', 'in:payment_link,paid_in_cash,not_settled_yet'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
            'actual_duration_hours' => ['required', 'integer', 'min:0', 'max:12'],
            'actual_duration_minutes' => ['required', 'integer', 'in:0,15,30,45'],
        ];

        if ($type === BalanceCollection::TYPE_PAID_IN_CASH) {
            $rules['completion_code'] = ['required', 'string', 'min:4', 'max:32'];
        }

        if ($type === BalanceCollection::TYPE_NOT_SETTLED_YET) {
            $rules['expected_payment_type'] = ['required', 'in:3_days,1_week,pick_date,no_date'];
            $rules['expected_payment_date'] = [
                'required_if:expected_payment_type,pick_date',
                'nullable',
                'date',
                'after_or_equal:today',
            ];
        }

        $validator = Validator::make($request->all(), $rules, [
            'amount.gt' => 'Amount must be greater than 0.',
            'completion_code.required' => 'Please enter the completion code.',
            'expected_payment_type.required' => 'Please choose when you expect the payment.',
            'expected_payment_date.required_if' => 'Please pick a date.',
            'expected_payment_date.after_or_equal' => 'Please pick today or a future date.',
            'actual_duration_hours.required' => 'Please select the actual duration.',
            'actual_duration_minutes.required' => 'Please select the actual duration.',
            'actual_duration_minutes.in' => 'Minutes must be 0, 15, 30, or 45.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Please fix the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $durationHours = (int) $request->input('actual_duration_hours');
        $durationMinutesPart = (int) $request->input('actual_duration_minutes');
        $actualDurationMinutes = ($durationHours * 60) + $durationMinutesPart;

        if ($actualDurationMinutes <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Actual duration must be greater than 0.',
                'errors' => ['actual_duration_minutes' => ['Actual duration must be greater than 0.']],
            ], 422);
        }

        if ($type === BalanceCollection::TYPE_PAYMENT_LINK) {
            $userDetail = Auth::user()?->userDetail;
            $payoutService = app(ArtistPayoutService::class);
            if ($userDetail && ! $payoutService->canAcceptClientPayments($userDetail)) {
                return response()->json([
                    'success' => false,
                    'message' => $payoutService->clientPaymentsBlockedMessage($userDetail),
                ], 422);
            }
        }

        $amount = round((float) $request->input('amount'), 2);
        $currency = strtoupper((string) ($booking->currency ?: 'EUR'));
        $payload = [
            'booking_id' => $booking->id,
            'artist_user_id' => (int) $booking->artist_user_id,
            'client_user_id' => $booking->user_id,
            'collection_type' => $type,
            'amount' => $amount,
            'currency' => $currency !== '' ? $currency : 'EUR',
            'payment_status' => BalanceCollection::PAYMENT_STATUS_PENDING,
            'status' => BalanceCollection::STATUS_PENDING,
        ];

        if ($type === BalanceCollection::TYPE_PAYMENT_LINK) {
            $code = $this->generateBalanceCollectionLinkCode();
            $url = url('/p/'.$code);
            $firstName = trim((string) strtok((string) ($booking->user?->first_name ?? ''), ' '));
            if ($firstName === '') {
                $firstName = 'there';
            }
            $title = $booking->displayTitle();
            $amountLabel = $this->formatBalanceEuro($amount);
            $payload['payment_link_code'] = $code;
            $payload['payment_link_url'] = $url;
            $payload['client_message'] = sprintf(
                "Hi %s — here’s the payment link for the remaining %s for your %s session: %s — you can pay in full or split it with Klarna.",
                $firstName,
                $amountLabel,
                $title !== '' ? $title : 'tattoo',
                $url
            );
            $payload['status'] = BalanceCollection::STATUS_LINK_SENT;
        } elseif ($type === BalanceCollection::TYPE_PAID_IN_CASH) {
            $inputCode = strtoupper(trim((string) $request->input('completion_code')));
            $storedCode = strtoupper(trim((string) $booking->completion_code));
            if ($storedCode === '' || ! hash_equals($storedCode, $inputCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid completion code.',
                    'errors' => ['completion_code' => ['Invalid completion code.']],
                ], 422);
            }

            $payload['completion_code'] = $inputCode;
            $payload['completion_code_entered_at'] = now();
            $payload['payment_method'] = 'cash';
            $payload['payment_status'] = BalanceCollection::PAYMENT_STATUS_PAID;
            $payload['paid_at'] = now();
            $payload['status'] = BalanceCollection::STATUS_CASH_CONFIRMED;
        } else {
            $when = (string) $request->input('expected_payment_type');
            $payload['expected_payment_type'] = $when;
            $payload['note'] = trim((string) $request->input('note')) ?: null;
            $payload['expected_payment_date'] = match ($when) {
                '3_days' => now()->addDays(3)->toDateString(),
                '1_week' => now()->addWeek()->toDateString(),
                'pick_date' => $request->input('expected_payment_date'),
                default => null,
            };
            $payload['status'] = BalanceCollection::STATUS_SETTLEMENT_DEFERRED;
        }

        $bookingCompleted = false;

        $record = DB::transaction(function () use ($booking, $payload, $type, $actualDurationMinutes, &$bookingCompleted) {
            $booking->forceFill([
                'actual_duration_minutes' => $actualDurationMinutes,
            ])->save();

            $saved = $this->saveBalanceCollectionForBooking($booking->id, $payload);

            if ($type === BalanceCollection::TYPE_PAID_IN_CASH) {
                $this->completeArtistBooking($booking, [
                    'remaining_amount_released' => true,
                    'remaining_amount_released_at' => now(),
                ]);
                $bookingCompleted = true;
            }

            return $saved;
        });

        return response()->json([
            'success' => true,
            'message' => match ($type) {
                BalanceCollection::TYPE_PAYMENT_LINK => 'Payment link created.',
                BalanceCollection::TYPE_PAID_IN_CASH => 'Cash payment recorded. Booking marked as completed.',
                default => 'Reminder saved.',
            },
            'booking_completed' => $bookingCompleted,
            'collection' => [
                'id' => $record->id,
                'collection_type' => $record->collection_type,
                'amount' => (float) $record->amount,
                'amount_label' => $this->formatBalanceEuro((float) $record->amount),
                'payment_link_code' => $record->payment_link_code,
                'payment_link_url' => $record->payment_link_url,
                'client_message' => $record->client_message,
                'status' => $record->status,
                'note' => $record->note,
                'expected_payment_type' => $record->expected_payment_type,
                'expected_label' => $type === BalanceCollection::TYPE_NOT_SETTLED_YET
                    ? $record->expectedDuePhrase()
                    : null,
                'nudge' => $type === BalanceCollection::TYPE_NOT_SETTLED_YET
                    ? $record->reminderNudge(
                        trim((string) strtok((string) ($booking->user?->first_name ?? ''), ' ')),
                        $this->formatBalanceEuro((float) $record->amount)
                    )
                    : null,
            ],
        ]);
    }

    private function completeArtistBooking(Booking $booking, array $extra = []): void
    {
        if (in_array((string) $booking->status, ['completed', 'cancelled', 'declined'], true)) {
            return;
        }

        $history = $booking->action_history ?? [];
        $history[] = [
            'action' => 'completed',
            'user_id' => Auth::id(),
            'user_type' => 'artist',
            'timestamp' => now()->toDateTimeString(),
        ];

        $booking->update(array_merge([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_code_entered_at' => now(),
            'action_history' => $history,
        ], $extra));
    }

    private function saveBalanceCollectionForBooking(int $bookingId, array $payload): BalanceCollection
    {
        $record = BalanceCollection::query()
            ->where('booking_id', $bookingId)
            ->latest('id')
            ->first();

        if (! $record) {
            return BalanceCollection::query()->create($payload);
        }

        BalanceCollection::query()
            ->where('booking_id', $bookingId)
            ->whereKeyNot($record->id)
            ->delete();

        $record->forceFill(array_merge($this->blankBalanceCollectionAttributes(), $payload));
        $record->save();

        return $record->refresh();
    }

    private function blankBalanceCollectionAttributes(): array
    {
        return [
            'platform_fee' => 0,
            'tax_amount' => 0,
            'tax_rate' => null,
            'tax_country' => null,
            'tax_label' => null,
            'payment_link_id' => null,
            'payment_link_code' => null,
            'payment_link_url' => null,
            'client_message' => null,
            'completion_code' => null,
            'completion_code_entered_at' => null,
            'expected_payment_type' => null,
            'expected_payment_date' => null,
            'note' => null,
            'payment_provider' => null,
            'payment_method' => null,
            'payment_intent_id' => null,
            'viva_order_code' => null,
            'viva_transaction_id' => null,
            'payment_status' => BalanceCollection::PAYMENT_STATUS_PENDING,
            'paid_at' => null,
            'status' => BalanceCollection::STATUS_PENDING,
            'meta' => null,
        ];
    }

    private function generateBalanceCollectionLinkCode(): string
    {
        $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (
            PaymentLink::query()->where('code', $code)->exists()
            || BalanceCollection::query()->where('payment_link_code', $code)->exists()
        );

        return $code;
    }

    private function formatBalanceEuro(float $value): string
    {
        $rounded = round($value, 2);
        if (fmod($rounded, 1.0) === 0.0) {
            return '€'.(string) (int) $rounded;
        }

        return '€'.number_format($rounded, 2, '.', '');
    }
}

