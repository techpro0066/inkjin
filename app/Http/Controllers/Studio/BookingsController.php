<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CustomRequest;
use App\Models\PaymentLink;
use App\Models\Studio;
use App\Models\StudioPortfolio;
use App\Models\StudioRequest;
use App\Models\User;
use App\Models\UserStudio;
use App\Services\ArtistPayoutService;
use App\Support\QuestionAnswerPresenter;
use App\Support\StudioPaymentLinkModalData;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingsController extends Controller
{
    public function calendar(Request $request): View
    {
        $data = $this->viewData($request, 'bookings');
        $data['activeNav'] = 'calendar';
        $studio = $data['studio'];

        $weekStart = $this->resolveCalendarWeekStart($request->query('week'));
        $weekEnd = $weekStart->copy()->addDays(5); // Mon–Sat (S03)
        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();
        $thisWeek = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $palette = [
            ['fg' => '#4B0F8F', 'bg' => '#EDE3FA'],
            ['fg' => '#1565C0', 'bg' => '#E3F0FC'],
            ['fg' => '#1F7A45', 'bg' => '#E6F6EC'],
            ['fg' => '#B86E00', 'bg' => '#FFF3DE'],
            ['fg' => '#C62828', 'bg' => '#FDECEC'],
            ['fg' => '#1E5BD8', 'bg' => '#E8F0FF'],
            ['fg' => '#7A1FC4', 'bg' => '#F3E8FF'],
        ];

        $artists = [];
        $colorByArtist = [];
        $eventsByCell = [];
        for ($d = 0; $d < 6; $d++) {
            for ($h = 0; $h < 9; $h++) {
                $eventsByCell[$d][$h] = [];
            }
        }

        if ($studio) {
            $studioArtists = $this->studioArtists($studio);
            foreach ($studioArtists->values() as $i => $a) {
                $color = $palette[$i % count($palette)];
                $artists[] = [
                    'id' => (int) $a['user_id'],
                    'name' => $a['name'],
                    'slug' => 'a'.(int) $a['user_id'],
                    'fg' => $color['fg'],
                    'bg' => $color['bg'],
                ];
                $colorByArtist[(int) $a['user_id']] = $color;
            }

            $bookings = Booking::query()
                ->with(['user', 'artist.userDetail', 'tattoo', 'guestSpot'])
                ->where('studio_id', $studio->id)
                ->whereDate('booking_date', '>=', $weekStart->toDateString())
                ->whereDate('booking_date', '<=', $weekEnd->toDateString())
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->orderBy('booking_date')
                ->orderBy('start_time_utc')
                ->get();

            $extraColorIndex = count($artists);
            foreach ($bookings as $booking) {
                $mapped = $this->mapCalendarEvent($booking, $colorByArtist, $palette, $extraColorIndex);
                if ($mapped === null) {
                    continue;
                }
                if (! isset($colorByArtist[$mapped['artist_id']])) {
                    $colorByArtist[$mapped['artist_id']] = [
                        'fg' => $mapped['fg'],
                        'bg' => $mapped['bg'],
                    ];
                    $artists[] = [
                        'id' => $mapped['artist_id'],
                        'name' => $mapped['artist'],
                        'slug' => $mapped['filter'],
                        'fg' => $mapped['fg'],
                        'bg' => $mapped['bg'],
                    ];
                    $extraColorIndex++;
                }
                $eventsByCell[$mapped['day_index']][$mapped['hour_index']][] = $mapped;
            }
        }

        $dayHeaders = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $weekStart->copy()->addDays($i);
            $dayHeaders[] = $d->format('D d/m');
        }

        $hours = [];
        for ($h = 10; $h <= 18; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        $rangeLabel = $weekStart->format('d/m').' – '.$weekEnd->format('d/m/Y');

        $hasEvents = false;
        foreach ($eventsByCell as $dayCells) {
            foreach ($dayCells as $cellEvents) {
                if ($cellEvents !== []) {
                    $hasEvents = true;
                    break 2;
                }
            }
        }

        return view('studio.calendar', array_merge($data, [
            'weekStart' => $weekStart->toDateString(),
            'prevWeek' => $prevWeek,
            'nextWeek' => $nextWeek,
            'thisWeek' => $thisWeek,
            'rangeLabel' => $rangeLabel,
            'dayHeaders' => $dayHeaders,
            'hours' => $hours,
            'artists' => $artists,
            'eventsByCell' => $eventsByCell,
            'hasEvents' => $hasEvents,
        ]));
    }

    public function index(Request $request): View
    {
        $data = $this->viewData($request, 'bookings');
        $studio = $data['studio'];

        $filter = strtolower(trim((string) $request->query('f', 'all')));
        $q = trim((string) $request->query('q', ''));

        $bookings = collect();
        $artistFilters = [];

        if ($studio) {
            $query = Booking::query()
                ->with(['user', 'artist.userDetail', 'tattoo', 'guestSpot'])
                ->where('studio_id', $studio->id)
                ->orderByDesc('booking_date')
                ->orderByDesc('id');

            if ($filter === 'upcoming') {
                $query->whereDate('booking_date', '>=', now()->toDateString())
                    ->whereNotIn('status', ['cancelled', 'completed', 'no_show']);
            } elseif ($filter === 'past') {
                $query->where(function ($inner) {
                    $inner->whereDate('booking_date', '<', now()->toDateString())
                        ->orWhereIn('status', ['completed', 'cancelled', 'no_show']);
                });
            } elseif (str_starts_with($filter, 'artist:')) {
                $artistId = (int) substr($filter, 7);
                if ($artistId > 0) {
                    $query->where('artist_user_id', $artistId);
                }
            }

            if ($q !== '') {
                $query->where(function ($inner) use ($q) {
                    $inner->where('id', 'like', '%'.$q.'%')
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('first_name', 'like', '%'.$q.'%')
                                ->orWhere('last_name', 'like', '%'.$q.'%')
                                ->orWhere('email', 'like', '%'.$q.'%');
                        });
                });
            }

            $bookings = $query->limit(200)->get()->map(fn (Booking $b) => $this->mapBookingRow($b));

            $artistFilters = $this->studioArtists($studio)->map(fn (array $a) => [
                'id' => $a['user_id'],
                'name' => $a['first_name'] !== '' ? $a['first_name'] : $a['name'],
                'slug' => 'artist:'.$a['user_id'],
            ])->all();
        }

        return view('studio.bookings.index', array_merge($data, [
            'bookings' => $bookings,
            'artistFilters' => $artistFilters,
            'activeFilter' => $filter,
            'searchQuery' => $q,
        ]));
    }

    public function requests(Request $request): View
    {
        $data = $this->viewData($request, 'requests');
        $studio = $data['studio'];
        $rows = collect();
        $artists = [];

        if ($studio) {
            $artists = $this->studioArtists($studio)->all();
            $rows = StudioRequest::query()
                ->with(['assignedArtist.userDetail', 'user', 'customRequest'])
                ->where('studio_id', $studio->id)
                ->whereNotIn('status', [
                    StudioRequest::STATUS_CANCELLED,
                    StudioRequest::STATUS_DECLINED,
                    StudioRequest::STATUS_MOVED_TO_BOOKING,
                ])
                ->orderByDesc('created_at')
                ->limit(200)
                ->get()
                ->map(fn (StudioRequest $r) => $this->mapRequestCard($r));
            // Keep cancelled / booked out of list; withdrawn removed from active board.
        }

        return view('studio.bookings.requests', array_merge($data, [
            'requestCards' => $rows,
            'assignArtists' => $artists,
        ]));
    }

    public function paymentLinks(Request $request): View
    {
        $data = $this->viewData($request, 'payment-links');
        $studio = $data['studio'];

        $filter = strtolower(trim((string) $request->input('filter', 'all')));
        if (! in_array($filter, ['all', 'open', 'paid', 'expired'], true)) {
            $filter = 'all';
        }
        $search = trim((string) $request->input('q', ''));

        $rows = $studio
            ? $this->studioPaymentLinkRows($studio)
            : collect();

        $filterCounts = [
            'all' => $rows->count(),
            'open' => $rows->where('status', 'open')->count(),
            'paid' => $rows->where('status', 'paid')->count(),
            'expired' => $rows->where('status', 'expired')->count(),
        ];

        $filtered = $this->filterStudioPaymentLinkRows($rows, $filter, $search);

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

        return view('studio.bookings.payment-links', array_merge($data, [
            'paymentLinks' => $paymentLinks,
            'filter' => $filter,
            'search' => $search,
            'filterCounts' => $filterCounts,
            'paymentLinksCount' => $filterCounts['all'],
        ]));
    }

    public function exportPaymentLinks(Request $request): StreamedResponse
    {
        $studio = $this->resolveStudio($request->user());
        abort_unless($studio, 404);

        $filter = strtolower(trim((string) $request->input('filter', 'all')));
        if (! in_array($filter, ['all', 'open', 'paid', 'expired'], true)) {
            $filter = 'all';
        }
        $search = trim((string) $request->input('q', ''));
        $rows = $this->filterStudioPaymentLinkRows(
            $this->studioPaymentLinkRows($studio),
            $filter,
            $search
        );

        $filename = 'payment-links-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['Title', 'Artist', 'Client', 'Deposit', 'Total', 'Status', 'Created', 'URL']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    (string) ($row->title ?? ''),
                    (string) ($row->artist ?? ''),
                    (string) ($row->client ?? ''),
                    number_format((float) ($row->deposit ?? 0), 2, '.', ''),
                    number_format((float) ($row->total ?? 0), 2, '.', ''),
                    ucfirst((string) ($row->status ?? '')),
                    optional($row->created_at)->format('Y-m-d H:i') ?? '',
                    (string) ($row->url ?? ''),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function validatePaymentLink(Request $request): JsonResponse
    {
        if ($response = $this->rejectIfCannotCreateStudioPaymentLinks($request)) {
            return $response;
        }

        $validator = $this->makeStudioPaymentLinkValidator($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($response = $this->rejectIfArtistCannotAcceptPayments($request)) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'preview' => $this->studioPaymentLinkPreview($request),
        ]);
    }

    public function storePaymentLink(Request $request): JsonResponse
    {
        if ($response = $this->rejectIfCannotCreateStudioPaymentLinks($request)) {
            return $response;
        }

        $validator = $this->makeStudioPaymentLinkValidator($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($response = $this->rejectIfArtistCannotAcceptPayments($request)) {
            return $response;
        }

        $studio = $this->resolveStudio($request->user());
        abort_unless($studio, 404);

        $payload = $this->normalizedStudioPaymentLinkData($request);
        $code = $this->generatePaymentLinkCode();
        $url = route('public.payment-link', ['code' => $code]);
        $displayUrl = preg_replace('#^https?://#', '', $url) ?? $url;
        $clientMessage = $this->buildClientMessage($payload, $url);

        $paymentLink = PaymentLink::query()->create([
            'artist_id' => (int) $payload['artist_id'],
            'studio_id' => (int) $studio->id,
            'status' => PaymentLink::STATUS_ACTIVE,
            'code' => $code,
            'url' => $url,
            'amount' => $payload['amount'],
            'payment_type' => $payload['payment_type'],
            'title' => $payload['title'],
            'date_time' => $payload['date_time'],
            'session_duration' => $payload['session_duration'],
            'total_price' => $payload['total_price'],
            'due_amount' => $payload['due_amount'],
            'expires' => $payload['expires'],
            'expires_at' => $payload['expires_at'],
            'client_message' => $clientMessage,
            'scheduling_type' => $payload['scheduling_type'],
        ]);

        $summaryParts = [$this->formatEuro((float) $payload['amount'])];
        if ($payload['date_time_formatted']) {
            $summaryParts[] = $payload['date_time_formatted'];
        }
        $summaryParts[] = $payload['session_duration_label'];

        return response()->json([
            'success' => true,
            'payment_link' => [
                'id' => $paymentLink->id,
                'title' => $payload['title'],
                'summary' => implode(' · ', $summaryParts),
                'url' => $url,
                'display_url' => $displayUrl,
                'client_message' => $clientMessage,
                'amount_formatted' => $this->formatEuro((float) $payload['amount']),
                'expires' => $payload['expires'],
            ],
        ]);
    }

    public function expirePaymentLink(Request $request, int $id): JsonResponse
    {
        $studio = $this->resolveStudio($request->user());
        abort_unless($studio, 404);

        $link = PaymentLink::query()
            ->whereKey($id)
            ->where('studio_id', $studio->id)
            ->firstOrFail();

        if ($link->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'This link was already paid and cannot be expired.',
            ], 422);
        }

        if ($link->isExpired() || $link->status === PaymentLink::STATUS_EXPIRED) {
            return response()->json([
                'success' => true,
                'message' => 'Link already expired.',
            ]);
        }

        $link->forceFill([
            'status' => PaymentLink::STATUS_EXPIRED,
            'expires_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Link expired.',
        ]);
    }

    public function assign(Request $request, StudioRequest $studioRequest): RedirectResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        abort_unless($studio && (int) $studioRequest->studio_id === (int) $studio->id, 404);

        $validated = $request->validate([
            'artist_user_id' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $artistId = (int) $validated['artist_user_id'];
        $allowed = $this->studioArtists($studio)->pluck('user_id')->all();
        if (! in_array($artistId, $allowed, true)) {
            return back()->with('error', 'That artist is not linked to your studio.');
        }

        if (! $studioRequest->canStudioAssign()) {
            return back()->with('error', 'This request can no longer be assigned.');
        }

        // Offer only — CustomRequest is created when the artist accepts.
        $answers = is_array($studioRequest->questions_answers) ? $studioRequest->questions_answers : [];
        if (! empty($validated['note'])) {
            $answers['_studio_assign_note'] = trim((string) $validated['note']);
        }

        $studioRequest->forceFill([
            'assigned_artist_id' => $artistId,
            'custom_request_id' => null,
            'status' => StudioRequest::STATUS_ASSIGNED,
            'reason_decline' => null,
            'questions_answers' => $answers,
        ])->save();

        return redirect()
            ->route('studio.bookings.requests')
            ->with('success', 'Offer sent. Waiting for the artist to accept.');
    }

    public function withdraw(Request $request, StudioRequest $studioRequest): RedirectResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        abort_unless($studio && (int) $studioRequest->studio_id === (int) $studio->id, 404);

        if (! $studioRequest->canStudioWithdraw()) {
            return back()->with('error', 'This request can no longer be withdrawn.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $reason = trim((string) ($validated['reason'] ?? '')) ?: 'Withdrawn by studio';

        $studioName = trim((string) ($studio->name ?? 'Studio'));
        if ($studioName === '') {
            $studioName = 'Studio';
        }

        $studioRequest->appendSendBackHistory(
            'studio_withdraw',
            $reason,
            null,
            null,
            $studioName,
        );
        $studioRequest->forceFill([
            'status' => StudioRequest::STATUS_CANCELLED,
            'assigned_artist_id' => null,
            'custom_request_id' => null,
            'reason_decline' => $reason,
        ])->save();

        return redirect()
            ->route('studio.bookings.requests')
            ->with('success', 'Request withdrawn.');
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Request $request, string $bookingsTab): array
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $studioName = trim((string) ($studio?->name ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        $bookingsCount = 0;
        $requestsCount = 0;
        $paymentLinksCount = 0;
        if ($studio) {
            $bookingsCount = Booking::query()->where('studio_id', $studio->id)->count();
            $requestsCount = StudioRequest::query()
                ->where('studio_id', $studio->id)
                ->whereNotIn('status', [
                    StudioRequest::STATUS_CANCELLED,
                    StudioRequest::STATUS_DECLINED,
                    StudioRequest::STATUS_MOVED_TO_BOOKING,
                ])
                ->count();
            $paymentLinksCount = PaymentLink::query()
                ->where('studio_id', $studio->id)
                ->count();
        }

        $profileComplete = (string) ($user->on_boarding ?? '') === 'yes';
        $studioArtistsForModal = $studio
            ? StudioPaymentLinkModalData::artists($studio)
            : collect();
        $paymentLinkModal = StudioPaymentLinkModalData::createGate(
            $profileComplete,
            $studio,
            $studioArtistsForModal
        );

        return array_merge($paymentLinkModal, [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'profileComplete' => $profileComplete,
            'stripeConnected' => (bool) ($studio?->hasStripeConnect()),
            'activeNav' => 'bookings',
            'bookingsTab' => $bookingsTab,
            'bookingsCount' => $bookingsCount,
            'requestsCount' => $requestsCount,
            'paymentLinksCount' => $paymentLinksCount,
            'studioArtistsForModal' => $studioArtistsForModal,
            'currencySymbol' => '€',
            'paymentLinkModalReloadOnDone' => true,
        ]);
    }

    /**
     * Studio-created payment links only (artist-made links stay on the artist dashboard).
     *
     * @return Collection<int, object>
     */
    private function studioPaymentLinkRows(Studio $studio): Collection
    {
        $now = now();

        return PaymentLink::query()
            ->where('studio_id', $studio->id)
            ->with(['artist.userDetail', 'booking.user'])
            ->orderByDesc('id')
            ->get()
            ->map(function (PaymentLink $link) use ($now) {
                $status = $link->listStatus();
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

                $artist = $link->artist;
                $artistName = $artist?->userDetail?->publicDisplayName();
                if (! $artistName) {
                    $artistName = $artist
                        ? (trim(($artist->first_name ?? '').' '.($artist->last_name ?? '')) ?: 'Artist')
                        : 'Artist';
                }

                return (object) [
                    'id' => $link->id,
                    'title' => (string) $link->title,
                    'artist' => $artistName,
                    'client' => $clientName,
                    'deposit' => $link->depositAmount(),
                    'total' => $link->totalAmount(),
                    'status' => $status,
                    'created_at' => $link->created_at,
                    'url' => $link->publicUrl(),
                    'code' => (string) $link->code,
                    'booking_id' => $link->booking_id ? (int) $link->booking_id : null,
                ];
            })
            ->values();
    }

    private function resolveStudio($user): ?Studio
    {
        if ($user?->id) {
            $byOwner = Studio::query()->where('user_id', $user->id)->first();
            if ($byOwner) {
                return $byOwner;
            }
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email === '') {
            return null;
        }

        $studio = Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($studio && empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
            $studio->save();
        }

        return $studio;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{user_id:int,name:string,first_name:string,styles:string,option_label:string,pay_note:string}>
     */
    private function studioArtists(Studio $studio)
    {
        $studioName = trim((string) ($studio->name ?? '')) ?: 'the studio';

        return UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->orderBy('id')
            ->get()
            ->map(function (UserStudio $link) use ($studioName) {
                $artist = $link->user;
                if (! $artist || (string) ($artist->role ?? '') !== 'artist') {
                    return null;
                }
                $detail = $artist->userDetail;
                $name = $detail?->publicDisplayName();
                if (! $name) {
                    $name = trim(($artist->first_name ?? '').' '.($artist->last_name ?? '')) ?: 'Artist';
                }
                $firstName = trim((string) ($artist->first_name ?? '')) ?: explode(' ', $name)[0];
                $styles = $this->formatTattooStyles($detail?->tattoo_styles ?? null);
                $relLabel = $this->relationshipLabel($link->relationship);
                $split = $link->revenue_split;
                $payoutType = $split !== null ? 'Revenue split' : 'Workstation rent';
                $meta = trim(implode(' · ', array_filter([$relLabel, $payoutType])));
                $optionLabel = $meta !== '' ? $name.' · '.$meta : ($styles !== '' ? $name.' · '.$styles : $name);

                if ($split !== null) {
                    $artistPct = max(0, min(100, (int) $split));
                    $studioPct = 100 - $artistPct;
                    $payNote = 'Each payment is split: '.$firstName.' '.$artistPct.'%, '.$studioName.' '.$studioPct.'%.';
                } else {
                    $payNote = $firstName.' gets the full payment. Nothing is split.';
                }

                return [
                    'user_id' => (int) $artist->id,
                    'name' => $name,
                    'first_name' => $firstName,
                    'styles' => $styles,
                    'option_label' => $optionLabel,
                    'pay_note' => $payNote,
                ];
            })
            ->filter()
            ->values();
    }

    private function relationshipLabel(?string $relationship): string
    {
        return match ($relationship) {
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
            default => '',
        };
    }

    private function formatTattooStyles(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        $labels = [];
        if (array_key_exists('primary_style', $payload) || array_key_exists('other_styles', $payload)) {
            $primary = trim((string) ($payload['primary_style'] ?? ''));
            if ($primary !== '') {
                $labels[] = $primary;
            }
            foreach ((array) ($payload['other_styles'] ?? []) as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style !== '') {
                    $labels[] = $style;
                }
            }
        } else {
            foreach ($payload as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style !== '') {
                    $labels[] = $style;
                }
            }
        }

        return implode(', ', array_slice(array_values(array_unique($labels)), 0, 3));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBookingRow(Booking $booking): array
    {
        $client = $booking->user;
        $clientName = trim(($client?->first_name ?? '').' '.($client?->last_name ?? ''));
        if ($clientName === '') {
            $clientName = (string) ($client?->email ?? 'Client');
        }
        $shortClient = $this->shortName($clientName);

        $artist = $booking->artist;
        $artistDetail = $artist?->userDetail;
        $artistName = $artistDetail?->publicDisplayName()
            ?: trim(($artist?->first_name ?? '').' '.($artist?->last_name ?? ''))
            ?: 'Artist';

        $status = strtolower((string) $booking->status);
        $statusLabel = match ($status) {
            'confirmed' => 'Confirmed',
            'pending' => 'Pending deposit',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No show',
            'rescheduled' => 'Rescheduled',
            default => ucfirst($status ?: 'Unknown'),
        };
        $statusPill = match ($status) {
            'confirmed', 'completed' => 'g',
            'pending' => 'a',
            'cancelled', 'no_show' => 'r',
            default => 'k',
        };

        $through = $this->bookedThroughLabel($booking);
        $share = $this->studioShareLabel($booking);

        $dateLabel = $booking->booking_date
            ? $booking->booking_date->format('d/m/Y')
            : '—';

        $isUpcoming = $booking->booking_date
            && $booking->booking_date->toDateString() >= now()->toDateString()
            && ! in_array($status, ['cancelled', 'completed', 'no_show'], true);

        $paidAmount = (float) ($booking->total_amount_paid ?? $booking->deposit_amount ?? 0);
        $currencyPrefix = strtoupper((string) ($booking->currency ?? 'EUR')) === 'EUR'
            ? '€'
            : strtoupper((string) ($booking->currency ?? 'EUR')).' ';

        return [
            'id' => $booking->id,
            'client' => $shortClient,
            'client_full' => $clientName,
            'artist' => $artistName,
            'artist_id' => (int) $booking->artist_user_id,
            'service' => $booking->displayTitle(),
            'date' => $dateLabel,
            'status' => $statusLabel,
            'status_pill' => $statusPill,
            'through' => $through,
            'share' => $share,
            'reference' => $booking->referenceLabel(),
            'filter_tokens' => trim(
                ($isUpcoming ? 'upcoming' : 'past').' artist:'.$booking->artist_user_id
            ),
            'paid' => number_format($paidAmount, 0),
            'currency' => $currencyPrefix,
            'modal' => $this->bookingModalPayload(
                $booking,
                $clientName,
                $artistName,
                $statusLabel,
                $statusPill,
                $through,
            ),
        ];
    }

    /**
     * Studio booking detail modal (matches artist A02 / S04 detail layout).
     *
     * @return array<string, mixed>
     */
    private function bookingModalPayload(
        Booking $booking,
        string $clientName,
        string $artistName,
        string $statusLabel,
        string $statusPill,
        string $through,
    ): array {
        $payout = app(ArtistPayoutService::class);
        $userDetail = $booking->artist?->userDetail;
        $money = $payout->bookingDetailsMoneyBreakdown($booking, $userDetail);

        $deposit = max(0, round((float) ($booking->deposit_amount ?? 0), 2));
        $stripeFee = (float) ($money['stripe_fee_amount'] ?? $payout->estimateStripeFee($deposit));
        $artistPercent = $payout->resolvePayoutArtistPercent($booking, $userDetail);
        $paymentType = $payout->resolvePayoutPaymentType($booking, $userDetail);
        $hasSplit = $paymentType === 'studio_account' && $artistPercent < 100;
        $studioPercent = max(0, 100 - $artistPercent);
        $clientFee = max(0, round((float) ($booking->platform_fee ?? 0), 2));
        $artistSideFee = max(0, round((float) ($money['artist_side_fee_amount'] ?? 0), 2));
        $firstName = trim((string) strtok($artistName, ' ')) ?: $artistName;

        $artistBookingFee = 0.0;
        $studioBookingFee = 0.0;
        if ($hasSplit) {
            $artistShare = round($deposit * ($artistPercent / 100), 2);
            $artistStripe = round($stripeFee * ($artistPercent / 100), 2);
            $artistBookingFee = max(0, round((float) ($money['your_booking_fee_amount'] ?? round($artistSideFee * ($artistPercent / 100), 2)), 2));
            $studioBookingFee = max(0, round((float) ($money['studio_booking_fee_amount'] ?? round($artistSideFee - $artistBookingFee, 2)), 2));
            $artistGets = max(0, round($artistShare - $artistStripe - $artistBookingFee, 2));
            $studioShare = round($deposit * ($studioPercent / 100), 2);
            $studioStripe = round($stripeFee * ($studioPercent / 100), 2);
            $studioGets = max(0, round($studioShare - $studioStripe - $studioBookingFee, 2));
            $splitValue = $firstName.' '.$artistPercent.'% · Studio '.$studioPercent.'%';
        } else {
            $artistShare = $deposit;
            $artistStripe = $stripeFee;
            $artistBookingFee = $artistSideFee;
            $artistGets = max(0, round($deposit - $stripeFee - $artistSideFee, 2));
            $studioShare = 0.0;
            $studioStripe = 0.0;
            $studioGets = 0.0;
            $splitValue = 'Direct · no studio split';
        }

        if ($clientFee > 0.009) {
            $bookingFeeLabel = $this->formatMoney($clientFee, $booking);
            $bookingFeeNote = 'paid by the client, goes to Bookpay';
        } elseif ($artistSideFee > 0.009) {
            $bookingFeeLabel = $this->formatMoney($artistSideFee, $booking);
            $bookingFeeNote = $hasSplit
                ? 'shared by split · '.$firstName.' '.$artistPercent.'% · studio '.$studioPercent.'%'
                : 'deducted from '.$firstName.'’s share';
        } else {
            $bookingFeeLabel = null;
            $bookingFeeNote = null;
        }

        $paidAt = $booking->created_at?->timezone(config('app.timezone'))->format('d/m');
        $depositMoneyLabel = 'Client paid · deposit'.($paidAt ? ', '.$paidAt : '');

        $footnote = (string) ($money['footnote'] ?? '');
        // Studio-facing footnote: bank timing for studio share.
        $deadline = $payout->cancellationDeadlineAt($booking);
        if ($hasSplit && $deadline && now()->lt($deadline)) {
            $footnote = 'Sent to your bank after '.$deadline->timezone(config('app.timezone'))->format('d/m')
                .', when the cancellation window ends.';
        } elseif ($hasSplit) {
            $footnote = 'Sent to your bank when the cancellation window ends.';
        }

        $balanceDue = max(0, round((float) $booking->remainingBalanceAmount(), 2));
        $balanceNote = null;
        if ($balanceDue > 0.009
            && ! $booking->remaining_amount_released
            && ! $booking->full_amount_paid
            && ! in_array(strtolower((string) $booking->status), ['cancelled', 'completed', 'declined'], true)
        ) {
            $balStripe = $payout->estimateStripeFee($balanceDue);
            if ($hasSplit) {
                $balArtist = max(0, round($balanceDue * ($artistPercent / 100) - $balStripe * ($artistPercent / 100), 2));
                $balStudio = max(0, round(($balanceDue - $balStripe) * ($studioPercent / 100), 2));
                $balanceNote = 'When the '.$this->formatMoney($balanceDue, $booking).' balance is paid: Stripe fee '
                    .$this->formatMoney($balStripe, $booking).', '.$firstName.' gets '
                    .$this->formatMoney($balArtist, $booking).', you get '.$this->formatMoney($balStudio, $booking).'.';
            } else {
                $balanceNote = 'When the '.$this->formatMoney($balanceDue, $booking).' balance is paid: Stripe fee '
                    .$this->formatMoney($balStripe, $booking).'.';
            }
        }

        $bt = $booking->booking_time;
        $dateLabel = $booking->booking_date?->format('D, d/m/Y') ?? '—';
        $timeLabel = ($bt && isset($bt['start'], $bt['end']))
            ? $this->to24h((string) $bt['start']).' – '.$this->to24h((string) $bt['end'])
            : '—';
        $duration = '—';
        if (is_array($bt) && isset($bt['duration_minutes'])) {
            $mins = (int) $bt['duration_minutes'];
            $hours = intdiv(max(1, $mins), 60);
            $tail = max(1, $mins) % 60;
            $duration = $mins < 60 ? $mins.' min' : ($tail ? $hours.'h '.$tail.'m' : $hours.'h');
        }

        $typeLabel = match (strtolower((string) ($booking->booking_type ?? ''))) {
            'custom' => 'Custom booking',
            'flash', 'design' => 'Design booking',
            'guest' => 'Guest spot booking',
            default => ucfirst((string) ($booking->booking_type ?: 'Booking')),
        };

        $imageUrl = null;
        if ($booking->tattoo && ! empty($booking->tattoo->image)) {
            $imgRaw = (string) $booking->tattoo->image;
            $imageUrl = str_starts_with($imgRaw, 'http://') || str_starts_with($imgRaw, 'https://')
                ? $imgRaw
                : asset(ltrim($imgRaw, '/'));
        }
        if ($imageUrl === null) {
            $qaImages = QuestionAnswerPresenter::allImageUrls($booking->questions_answers ?? []);
            if ($qaImages !== []) {
                $imageUrl = $qaImages[0];
            }
        }

        $details = is_array($booking->custom_tattoo_details) ? $booking->custom_tattoo_details : [];
        $customRequestId = (int) ($details['custom_request_id'] ?? 0);
        $customRequest = $customRequestId > 0
            ? CustomRequest::query()->find($customRequestId)
            : null;

        $qaSource = $customRequest?->questions_answers ?? $booking->questions_answers ?? [];
        $requestFields = $this->requestTabFields($qaSource);
        $answers = array_map(
            static fn (array $f) => ['q' => $f['label'], 'a' => $f['value']],
            $requestFields
        );

        $isGuest = $booking->isGuestCustomRequestBooking() || (bool) ($customRequest?->is_guest);
        $isPaymentLink = ! empty($details['payment_link_id']) || ! empty($details['from_studio_payment_link']);
        if ($isGuest) {
            $requestTitle = 'Guest spot request';
            $requestIcon = 'luggage';
        } elseif ($isPaymentLink && ! $customRequest) {
            $requestTitle = 'Payment link booking';
            $requestIcon = 'link';
        } elseif (in_array(strtolower((string) ($booking->booking_type ?? '')), ['flash', 'design'], true)) {
            $requestTitle = 'Design request';
            $requestIcon = 'palette';
        } else {
            $requestTitle = 'Custom tattoo request';
            $requestIcon = 'brush';
        }

        $sentAt = $customRequest?->created_at ?? $booking->created_at;
        $sentLabel = $sentAt?->timezone(config('app.timezone'))->format('d/m/Y');
        $requestRef = $customRequest
            ? $customRequest->referenceLabel()
            : $booking->referenceLabel();
        $requestMeta = $requestRef.($sentLabel ? ' · sent '.$sentLabel : '');

        $artistEmail = trim((string) ($booking->artist?->email ?? ''));

        return [
            'title' => $booking->displayTitle(),
            'reference' => $booking->referenceLabel(),
            'image_url' => $imageUrl,
            'status' => $statusLabel,
            'status_pill' => $statusPill,
            'type_label' => $typeLabel,
            'client' => $clientName,
            'artist' => $artistName,
            'artist_first' => $firstName,
            'service' => $booking->displayTitle(),
            'through' => $through,
            'deposit' => $this->formatMoney($deposit, $booking),
            'date' => $dateLabel,
            'time' => $timeLabel,
            'duration' => $duration,
            'timezone' => (string) ($booking->timezone ?: '—'),
            'balance' => $balanceDue > 0.009 ? $this->formatMoney($balanceDue, $booking) : '—',
            'money_pill' => (string) ($money['pill'] ?? 'Pending'),
            'money_pill_class' => (string) ($money['pill_class'] ?? 'a'),
            'deposit_money_label' => $depositMoneyLabel,
            'deposit_money' => $this->formatMoney($deposit, $booking),
            'booking_fee' => $bookingFeeLabel,
            'booking_fee_note' => $bookingFeeNote,
            'stripe_fee' => '−'.$this->formatMoney($stripeFee, $booking),
            'has_split' => $hasSplit,
            'split_value' => $splitValue,
            'artist_gets_label' => $firstName.' gets',
            'artist_gets_note' => $artistBookingFee > 0.009
                ? 'after Stripe + booking fee share'
                : 'after his part of the Stripe fee',
            'artist_gets' => $this->formatMoney($artistGets, $booking),
            'studio_share_label' => 'Studio share',
            'studio_share_note' => $hasSplit ? $studioPercent.'% of '.$this->formatMoney($deposit, $booking) : null,
            'studio_share' => $this->formatMoney($studioShare, $booking),
            'studio_stripe' => '−'.$this->formatMoney($studioStripe, $booking),
            'studio_stripe_note' => $hasSplit ? (string) $studioPercent.'%' : null,
            'studio_booking_fee' => ($hasSplit && $studioBookingFee > 0.009)
                ? '−'.$this->formatMoney($studioBookingFee, $booking)
                : null,
            'studio_booking_fee_note' => ($hasSplit && $studioBookingFee > 0.009)
                ? (string) $studioPercent.'%'
                : null,
            'studio_gets' => $this->formatMoney($studioGets, $booking),
            'footnote' => $footnote,
            'balance_note' => $balanceNote,
            'answers' => $answers,
            'request' => [
                'title' => $requestTitle,
                'icon' => $requestIcon,
                'meta' => $requestMeta,
                'fields' => $requestFields,
                'caption' => 'What the client sent. '.$firstName.' handles their questions and references.',
            ],
            'message_url' => $booking->artist_user_id
                ? route('studio.chat.index', [
                    'artist' => (int) $booking->artist_user_id,
                    'booking' => (int) $booking->id,
                ])
                : ($artistEmail !== '' ? 'mailto:'.$artistEmail : null),
            'message_label' => 'Message '.$firstName,
            'message_artist_id' => (int) ($booking->artist_user_id ?? 0),
            'message_artist_name' => $artistName,
            'message_artist_initials' => $this->initialsFromName($artistName),
            'message_booking_id' => (int) $booking->id,
            'message_booking_ref' => $booking->referenceLabel(),
            'message_booking_meta' => trim($statusLabel.($through !== '' ? ' · '.$through : '')),
            'message_can_chat' => $booking->isOpenForChat(),
        ];
    }

    private function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';

        $initials = strtoupper(mb_substr($first, 0, 1).mb_substr($second, 0, 1));

        return $initials !== '' ? $initials : '?';
    }

    /**
     * Original-request tab fields (label above value, 2-column grid).
     *
     * @return list<array{label: string, value: string}>
     */
    private function requestTabFields(mixed $questionsAnswers): array
    {
        $fields = [];
        foreach (QuestionAnswerPresenter::rows($questionsAnswers) as $row) {
            $type = strtolower((string) ($row['type'] ?? ''));
            if (in_array($type, ['image', 'reference_image', 'placement_photo', 'coverup_photo'], true)) {
                continue;
            }
            $value = QuestionAnswerPresenter::formatAnswer($row['answer'] ?? null, $type);
            if ($value === '' || $value === '—') {
                continue;
            }
            // Skip pure image URL answers that slipped through without a type.
            if (QuestionAnswerPresenter::imageUrls($row['answer'] ?? null, $type) !== []) {
                continue;
            }
            $fields[] = [
                'label' => $this->shortRequestLabel((string) ($row['question'] ?? 'Answer')),
                'value' => $value,
            ];
        }

        return $fields;
    }

    private function shortRequestLabel(string $question): string
    {
        $q = strtolower(trim($question));
        if (str_contains($q, 'kind of tattoo')) {
            return 'Kind of tattoo';
        }
        if (str_contains($q, 'what style') || $q === 'style') {
            return 'Style';
        }
        if (str_contains($q, 'where on') || (str_contains($q, 'placement') && ! str_contains($q, 'photo'))) {
            return 'Where on the body';
        }
        if (str_contains($q, 'what size') || $q === 'size' || str_contains($q, 'how big')) {
            return 'Size';
        }
        if (str_contains($q, 'budget')) {
            return 'Budget';
        }
        if (str_contains($q, 'color preference') || $q === 'color') {
            return 'Color';
        }
        if (str_contains($q, 'reference')) {
            return 'References';
        }

        // Strip common question wrappers so .lbl uppercase stays readable.
        $clean = preg_replace('/^(what|where|how|do you|have you|please)\s+/i', '', trim($question)) ?: $question;
        $clean = rtrim($clean, '?');

        return $clean !== '' ? $clean : $question;
    }

    private function to24h(string $time): string
    {
        $time = trim($time);
        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return $time;
        }
    }

    private function formatMoney(float $amount, Booking $booking): string
    {
        $currency = strtoupper((string) ($booking->currency ?? 'EUR'));
        $formatted = number_format(max(0, round($amount, 2)), 2, '.', '');
        if ($currency === 'EUR') {
            return '€'.$formatted;
        }

        return $currency.' '.$formatted;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRequestCard(StudioRequest $req): array
    {
        $clientName = trim((string) ($req->client_name ?? ''));
        if ($clientName === '') {
            $u = $req->user;
            $clientName = trim(($u?->first_name ?? '').' '.($u?->last_name ?? ''))
                ?: (string) ($req->client_email ?? 'Client');
        }

        $answers = is_array($req->questions_answers) ? $req->questions_answers : [];
        $clientArtistChoice = trim((string) ($answers['_artist_choice'] ?? ''));
        if ($clientArtistChoice === '') {
            $clientArtistChoice = 'Any available artist';
        }

        $artistName = null;
        if ($req->assignedArtist) {
            $artistName = $req->assignedArtist->userDetail?->publicDisplayName()
                ?: trim(($req->assignedArtist->first_name ?? '').' '.($req->assignedArtist->last_name ?? ''));
        }

        $summary = $this->requestSummary($answers, $req->anything_else_notes);
        $hasCustom = ! empty($req->custom_request_id);
        $status = (string) $req->status;
        $sentBackReason = trim((string) ($req->reason_decline ?? ''));

        $statusKey = 'new';
        $statusLabel = 'New';
        $cta = null;
        $canWithdraw = $req->canStudioWithdraw();
        $rightHint = null;

        if ($status === StudioRequest::STATUS_SENT_BACK && ! $hasCustom) {
            $statusKey = 'sent_back';
            $sentBackBy = $req->latestSendBackArtistName() ?: $artistName;
            $statusLabel = $sentBackBy ? ('Sent back by '.$sentBackBy) : 'Sent back';
            $cta = 'Assign again';
        } elseif ($req->isAwaitingArtistDecision()) {
            $statusKey = 'waiting_artist';
            $statusLabel = 'Waiting for artist';
            $cta = 'Reassign';
            $rightHint = $artistName ? ('Offered to '.$artistName) : 'Awaiting accept';
        } elseif (! $hasCustom && ($status === StudioRequest::STATUS_PENDING || empty($req->assigned_artist_id))) {
            $statusKey = 'needs_artist';
            $statusLabel = 'Needs an artist';
            $cta = 'Assign artist';
        } else {
            $crStatus = strtolower((string) ($req->customRequest?->status ?? ''));
            if (in_array($crStatus, ['quote_sent', 'quoted', 'confirmed'], true)) {
                $statusKey = 'quote_sent';
                $statusLabel = 'Quote sent';
            } else {
                $statusKey = 'accepted';
                $statusLabel = 'Accepted';
            }
            $rightHint = 'The artist replies';
        }

        $sentLabel = $req->created_at?->format('d/m') ?? '';
        if ($statusKey === 'needs_artist') {
            $prefLabel = $clientArtistChoice === 'Any available artist'
                ? 'Any available artist'
                : 'Client chose '.$clientArtistChoice;
            $toLine = $prefLabel
                .($summary !== '' ? ' · '.$summary : '')
                .($sentLabel !== '' ? ' · sent '.$sentLabel : '');
        } elseif ($statusKey === 'sent_back') {
            $toLine = ($sentBackReason !== '' ? '"'.$sentBackReason.'" · ' : '')
                .($summary !== '' ? $summary : 'Waiting for a new artist');
        } elseif ($artistName) {
            $toLine = 'To '.$artistName.($summary !== '' ? ' · '.$summary : '');
        } else {
            $toLine = $summary !== '' ? $summary : 'Studio request · awaiting artist';
        }

        $preselectArtistId = (int) ($req->assigned_artist_id ?? 0);
        if ($preselectArtistId <= 0
            && $clientArtistChoice !== ''
            && $clientArtistChoice !== 'Any available artist'
            && $req->studio_id
        ) {
            $preselectArtistId = $this->resolveStudioArtistIdByName((int) $req->studio_id, $clientArtistChoice);
        }

        $images = $this->requestImageUrls($req);

        $idea = $summary !== '' ? $summary : 'Studio tattoo request';
        if ($images !== []) {
            $idea .= ' · '.QuestionAnswerPresenter::formatPhotoLabels(count($images)).'.';
        }

        return [
            'id' => $req->id,
            'client' => $clientName,
            'email' => (string) ($req->client_email ?? $req->user?->email ?? ''),
            'phone' => (string) ($req->client_phone ?? ''),
            'notes' => (string) ($req->anything_else_notes ?? ''),
            'artist_name' => $artistName ?: '',
            'client_artist_choice' => $clientArtistChoice,
            'idea' => $idea,
            'created_label' => $req->created_at?->format('d/m/Y · H:i') ?? '',
            'images' => $images,
            'initials' => $this->initials($clientName),
            'type_label' => 'Studio request',
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'status_pill' => match ($statusKey) {
                'needs_artist', 'waiting_artist' => 'a',
                'sent_back' => 'r',
                'quote_sent' => 'b',
                'accepted' => 'p',
                default => 'p',
            },
            'card_border' => match ($statusKey) {
                'needs_artist' => '#F3C98B',
                'sent_back' => '#F5C2C2',
                'waiting_artist' => '#E8DFEA',
                default => null,
            },
            'subtitle' => $toLine,
            'cta' => $cta,
            'can_withdraw' => $canWithdraw,
            'right_hint' => $rightHint,
            'preselect_artist_id' => $preselectArtistId,
            'answers' => $this->flattenAnswersForModal($answers),
            'send_back_history' => $this->formatSendBackHistory($req),
        ];
    }

    private function resolveStudioArtistIdByName(int $studioId, string $artistName): int
    {
        $needle = mb_strtolower(trim($artistName));
        if ($needle === '' || $studioId <= 0 || ! UserStudio::tableReady()) {
            return 0;
        }

        $links = UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studioId)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->get();

        foreach ($links as $link) {
            $user = $link->user;
            $detail = $user?->userDetail;
            if (! $user || ! $detail) {
                continue;
            }
            $name = trim((string) ($detail->publicDisplayName() ?: ''));
            if ($name === '' || $name === 'Artist') {
                $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            }
            if ($name !== '' && mb_strtolower($name) === $needle) {
                return (int) $user->id;
            }
        }

        return 0;
    }

    /**
     * @return list<array{action:string,action_label:string,artist_id:?int,artist_name:?string,actor_name:string,reason:?string,at:string,at_label:string}>
     */
    private function formatSendBackHistory(StudioRequest $req): array
    {
        $raw = is_array($req->send_back_history) ? $req->send_back_history : [];
        $out = [];
        foreach ($raw as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $at = trim((string) ($entry['at'] ?? ''));
            $atLabel = '—';
            if ($at !== '') {
                try {
                    $atLabel = \Carbon\Carbon::parse($at)->format('d/m/Y · H:i');
                } catch (\Throwable) {
                    $atLabel = $at;
                }
            }
            $action = (string) ($entry['action'] ?? 'artist_send_back');
            $actor = trim((string) ($entry['actor_name'] ?? $entry['artist_name'] ?? ''));
            if ($actor === '') {
                $actor = $action === 'studio_withdraw' ? 'Studio' : 'Artist';
            }
            $out[] = [
                'action' => $action,
                'action_label' => $action === 'studio_withdraw' ? 'Withdrawn by studio' : 'Sent back by artist',
                'artist_id' => isset($entry['artist_id']) ? (int) $entry['artist_id'] : null,
                'artist_name' => isset($entry['artist_name']) && trim((string) $entry['artist_name']) !== ''
                    ? trim((string) $entry['artist_name'])
                    : null,
                'actor_name' => $actor,
                'reason' => isset($entry['reason']) && trim((string) $entry['reason']) !== ''
                    ? trim((string) $entry['reason'])
                    : null,
                'at' => $at,
                'at_label' => $atLabel,
            ];
        }

        return $out;
    }

    private function bookedThroughLabel(Booking $booking): string
    {
        if ($booking->guest_spot_id) {
            return 'Guest spot';
        }
        $details = is_array($booking->custom_tattoo_details) ? $booking->custom_tattoo_details : [];
        if (! empty($details['payment_link_id']) || ! empty($details['from_studio_payment_link'])) {
            return 'Studio (New booking)';
        }
        if (! empty($details['from_studio_page']) || ! empty($details['studio_page'])) {
            return 'Studio page';
        }

        return 'Artist link';
    }

    private function studioShareLabel(Booking $booking): string
    {
        $type = strtolower((string) ($booking->payout_payment_type ?? ''));
        $artistPct = $booking->payout_artist_percent;
        $paid = (float) ($booking->total_amount_paid ?? $booking->deposit_amount ?? 0);

        if ($type === 'rent' || $type === 'direct' || $artistPct === null) {
            return 'Direct';
        }

        $studioPct = max(0, 100 - (int) $artistPct);
        if ($paid <= 0) {
            return '€0 so far';
        }

        $share = round($paid * ($studioPct / 100), 0);
        $currency = strtoupper((string) ($booking->currency ?? 'EUR'));
        if ($currency === 'EUR') {
            return '€'.$share;
        }

        return $currency.' '.$share;
    }

    /**
     * Collect reference image URLs from reference_images + question answers + portfolio piece.
     *
     * @return list<string>
     */
    private function requestImageUrls(StudioRequest $req): array
    {
        $images = [];
        foreach ((array) ($req->reference_images ?? []) as $img) {
            $path = is_string($img) ? trim($img) : trim((string) ($img['url'] ?? $img['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $images[] = str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
        }

        foreach (QuestionAnswerPresenter::allImageUrls($req->questions_answers ?? []) as $url) {
            $path = trim((string) $url);
            if ($path === '') {
                continue;
            }
            $images[] = str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
        }

        // Fallback: always include the studio portfolio piece that started the request.
        $answers = is_array($req->questions_answers) ? $req->questions_answers : [];
        $portfolioId = (int) ($answers['_studio_portfolio_id'] ?? 0);
        if ($portfolioId <= 0 && (string) ($req->source ?? '') === 'studio_portfolio') {
            if (preg_match('/studio_portfolio=(\d+)/', (string) ($req->source_path ?? ''), $m)) {
                $portfolioId = (int) $m[1];
            }
        }
        if ($portfolioId > 0) {
            $piece = StudioPortfolio::query()
                ->whereKey($portfolioId)
                ->where('studio_id', (int) $req->studio_id)
                ->first();
            $imageRaw = trim((string) ($piece?->image ?? ''));
            if ($imageRaw !== '') {
                $images[] = str_starts_with($imageRaw, 'http://') || str_starts_with($imageRaw, 'https://')
                    ? $imageRaw
                    : asset(ltrim($imageRaw, '/'));
            }
        }

        return array_values(array_unique($images));
    }

    private function requestSummary(array $answers, ?string $notes): string
    {
        $bits = [];
        foreach ($this->publicAnswerRows($answers) as $row) {
            $type = (string) ($row['type'] ?? '');
            if (QuestionAnswerPresenter::imageUrls($row['answer'] ?? null, $type) !== []) {
                continue;
            }
            $answer = QuestionAnswerPresenter::formatAnswer($row['answer'] ?? null, $type);
            $answer = trim((string) $answer);
            if ($answer === '' || $answer === '—' || strcasecmp($answer, 'other') === 0) {
                continue;
            }
            $bits[] = $answer;
            if (count($bits) >= 3) {
                break;
            }
        }
        if ($bits === [] && $notes) {
            $bits[] = \Illuminate\Support\Str::limit(trim($notes), 60);
        }

        return implode(', ', $bits);
    }

    /**
     * Text answers only — images are rendered as thumbnails via `images`.
     *
     * @return list<array{q:string,a:string}>
     */
    private function flattenAnswersForModal(array $answers): array
    {
        $out = [];
        foreach ($this->publicAnswerRows($answers) as $row) {
            $type = (string) ($row['type'] ?? '');
            // Keep images out of the text answers list (shown in Reference images grid).
            if (QuestionAnswerPresenter::imageUrls($row['answer'] ?? null, $type) !== []) {
                continue;
            }
            $q = trim((string) ($row['question'] ?? ''));
            $a = trim(QuestionAnswerPresenter::formatAnswer($row['answer'] ?? null, $type));
            if ($q === '' || $a === '' || $a === '—') {
                continue;
            }
            $out[] = ['q' => $q, 'a' => $a];
        }

        return $out;
    }

    /**
     * @return list<array{question: string, type: string, answer: mixed, images: array<int, string>}>
     */
    private function publicAnswerRows(array $answers): array
    {
        $public = [];
        foreach ($answers as $key => $value) {
            if (is_string($key) && str_starts_with($key, '_')) {
                continue;
            }
            $public[$key] = $value;
        }

        return QuestionAnswerPresenter::rows($public);
    }

    private function resolveCalendarWeekStart(mixed $weekParam): Carbon
    {
        $raw = is_string($weekParam) ? trim($weekParam) : '';
        if ($raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                return Carbon::parse($raw)->startOfWeek(Carbon::MONDAY)->startOfDay();
            } catch (\Throwable) {
                // fall through
            }
        }

        return now()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    /**
     * Map a studio booking onto the S03 week grid (Mon–Sat, 10:00–18:00).
     *
     * @param  array<int, array{fg:string,bg:string}>  $colorByArtist
     * @param  list<array{fg:string,bg:string}>  $palette
     * @return array<string, mixed>|null
     */
    private function mapCalendarEvent(
        Booking $booking,
        array $colorByArtist,
        array $palette,
        int $extraColorIndex
    ): ?array {
        if (! $booking->booking_date) {
            return null;
        }

        $dayIndex = (int) $booking->booking_date->dayOfWeekIso - 1; // Mon=0 … Sun=6
        if ($dayIndex < 0 || $dayIndex > 5) {
            return null; // Sunday is outside the S03 grid
        }

        $bt = $booking->booking_time;
        if (! is_array($bt) || empty($bt['start']) || empty($bt['end'])) {
            return null;
        }

        try {
            $start = Carbon::parse((string) $bt['start']);
            $end = Carbon::parse((string) $bt['end']);
        } catch (\Throwable) {
            return null;
        }

        $startMin = ((int) $start->format('H')) * 60 + (int) $start->format('i');
        $endMin = ((int) $end->format('H')) * 60 + (int) $end->format('i');
        if ($endMin <= $startMin) {
            $endMin = $startMin + 60;
        }

        $gridStart = 10 * 60;
        $gridEnd = 19 * 60; // last hour row is 18:00–19:00
        if ($endMin <= $gridStart || $startMin >= $gridEnd) {
            return null;
        }

        $visStart = max($startMin, $gridStart);
        $visEnd = min($endMin, $gridEnd);
        $hourIndex = intdiv($visStart - $gridStart, 60);
        $hourIndex = max(0, min(8, $hourIndex));
        $slotStart = $gridStart + ($hourIndex * 60);

        $cellH = 52;
        $top = (int) round((($visStart - $slotStart) / 60) * $cellH) + 3;
        $height = (int) max(24, round((($visEnd - $visStart) / 60) * $cellH) - 4);

        $client = $booking->user;
        $clientName = trim(($client?->first_name ?? '').' '.($client?->last_name ?? ''));
        if ($clientName === '') {
            $clientName = (string) ($client?->email ?? 'Client');
        }
        $shortClient = $this->shortName($clientName);

        $artist = $booking->artist;
        $artistDetail = $artist?->userDetail;
        $artistName = $artistDetail?->publicDisplayName()
            ?: trim(($artist?->first_name ?? '').' '.($artist?->last_name ?? ''))
            ?: 'Artist';
        $artistId = (int) $booking->artist_user_id;

        $color = $colorByArtist[$artistId] ?? $palette[$extraColorIndex % count($palette)];

        $status = strtolower((string) $booking->status);
        $statusLabel = match ($status) {
            'confirmed' => 'Confirmed',
            'pending' => 'Pending deposit',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No show',
            'rescheduled' => 'Rescheduled',
            default => ucfirst($status ?: 'Unknown'),
        };
        $statusPill = match ($status) {
            'confirmed', 'completed' => 'g',
            'pending' => 'a',
            'cancelled', 'no_show' => 'r',
            default => 'k',
        };
        $through = $this->bookedThroughLabel($booking);

        return [
            'id' => $booking->id,
            'day_index' => $dayIndex,
            'hour_index' => $hourIndex,
            'top' => $top,
            'height' => $height,
            'artist_id' => $artistId,
            'artist' => $artistName,
            'filter' => 'a'.$artistId,
            'fg' => $color['fg'],
            'bg' => $color['bg'],
            'label' => $shortClient.' · '.$booking->displayTitle(),
            'modal' => $this->bookingModalPayload(
                $booking,
                $clientName,
                $artistName,
                $statusLabel,
                $statusPill,
                $through,
            ),
        ];
    }

    private function shortName(string $full): string
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        if (count($parts) < 2) {
            return $full;
        }
        $last = $parts[count($parts) - 1];

        return $parts[0].' '.mb_strtoupper(mb_substr($last, 0, 1)).'.';
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $a = mb_strtoupper(mb_substr($parts[0] ?? 'C', 0, 1));
        $b = mb_strtoupper(mb_substr($parts[1] ?? ($parts[0] ?? 'L'), 0, 1));

        return $a.$b;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function filterStudioPaymentLinkRows(Collection $rows, string $filter, string $search): Collection
    {
        $filtered = $rows;
        if ($filter !== 'all') {
            $filtered = $filtered->where('status', $filter)->values();
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filtered = $filtered->filter(function ($row) use ($needle) {
                $hay = mb_strtolower(implode(' ', array_filter([
                    (string) ($row->title ?? ''),
                    (string) ($row->artist ?? ''),
                    (string) ($row->client ?? ''),
                    (string) ($row->code ?? ''),
                ])));

                return str_contains($hay, $needle);
            })->values();
        }

        return $filtered;
    }

    private function rejectIfCannotCreateStudioPaymentLinks(Request $request): ?JsonResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio not found.',
            ], 422);
        }

        $user = $request->user();
        $profileComplete = (string) ($user->on_boarding ?? '') === 'yes';
        $artists = $this->studioArtists($studio);

        if (! $profileComplete) {
            return response()->json([
                'success' => false,
                'message' => 'Complete your studio profile before creating bookings.',
            ], 422);
        }

        if ($artists->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Invite or add an artist before creating a booking payment link.',
            ], 422);
        }

        return null;
    }

    private function rejectIfArtistCannotAcceptPayments(Request $request): ?JsonResponse
    {
        $artistId = (int) $request->input('artist_id');
        $artist = User::query()->with('userDetail')->find($artistId);
        $userDetail = $artist?->userDetail;
        if (! $userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['artist_id' => ['Choose a valid artist.']],
            ], 422);
        }

        $payoutService = app(ArtistPayoutService::class);
        if ($payoutService->canAcceptClientPayments($userDetail)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => $payoutService->clientPaymentsBlockedMessage($userDetail)
                ?: 'This artist cannot accept client payments yet.',
            'errors' => [
                'artist_id' => ['This artist needs payout setup before you can create a payment link.'],
            ],
        ], 422);
    }

    private function makeStudioPaymentLinkValidator(Request $request)
    {
        $studio = $this->resolveStudio($request->user());
        $artistIds = $studio
            ? $this->studioArtists($studio)->pluck('user_id')->all()
            : [];

        $isWalk = $request->input('when') === 'walk';
        $isDeposit = $request->input('payment_type') === 'deposit';

        $rules = [
            'artist_id' => ['required', 'integer', Rule::in($artistIds)],
            'amount' => ['required', 'string'],
            'payment_type' => ['required', 'in:deposit,full'],
            'title' => ['required', 'string', 'max:255'],
            'session_duration' => ['required', 'in:2h,3h,4h,half-day,full-day'],
            'when' => ['required', 'in:pick,walk'],
            'total_price' => [Rule::requiredIf($isDeposit), 'nullable', 'string'],
        ];

        if ($isWalk) {
            $rules['expires'] = ['nullable', 'string'];
        } else {
            $rules['expires'] = ['required', 'in:2 days,3 days,7 days'];
            $rules['date_time'] = ['required', 'date_format:Y-m-d H:i', 'after:now'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'artist_id.required' => 'Choose an artist.',
            'artist_id.in' => 'Choose a valid artist.',
            'amount.required' => 'Please enter an amount.',
            'payment_type.required' => 'Please select a payment type.',
            'payment_type.in' => 'Please select a valid payment type.',
            'title.required' => 'Please enter a title.',
            'date_time.required' => 'Please select a date and time.',
            'date_time.date_format' => 'Please select a valid date and time.',
            'date_time.after' => 'Please select a future date and time.',
            'session_duration.required' => 'Please select a session duration.',
            'session_duration.in' => 'Please select a valid session duration.',
            'total_price.required' => 'Please enter a total price.',
            'expires.required' => 'Please select when this link expires.',
            'expires.in' => 'Please select a valid expiration.',
            'when.required' => 'Please choose when this booking starts.',
            'when.in' => 'Please choose when this booking starts.',
        ]);

        $validator->after(function ($validator) use ($request, $isDeposit) {
            $amountRaw = trim((string) $request->input('amount', ''));
            $amount = $this->parseMoney($amountRaw);

            if ($amountRaw !== '' && $amount === null) {
                $validator->errors()->add('amount', 'Please enter a valid amount.');
            } elseif ($amount !== null && $amount <= 0) {
                $validator->errors()->add('amount', 'Amount must be greater than 0.');
            }

            if (! $isDeposit) {
                return;
            }

            $totalRaw = trim((string) $request->input('total_price', ''));
            if ($totalRaw === '') {
                return;
            }

            $total = $this->parseMoney($totalRaw);
            if ($total === null) {
                $validator->errors()->add('total_price', 'Please enter a valid total price.');

                return;
            }

            if ($amountRaw === '' || $amount === null) {
                $validator->errors()->add('total_price', 'Please enter the amount first.');

                return;
            }

            if ($total <= $amount) {
                $validator->errors()->add('total_price', 'Total price must be greater than the amount.');
            }
        });

        return $validator;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedStudioPaymentLinkData(Request $request): array
    {
        $isWalk = $request->input('when') === 'walk';
        $isDeposit = $request->input('payment_type') === 'deposit';
        $amount = (float) $this->parseMoney((string) $request->input('amount'));
        $total = $isDeposit ? $this->parseMoney((string) $request->input('total_price')) : null;
        $due = ($isDeposit && $total !== null) ? round($total - $amount, 2) : 0.0;
        $sessionDuration = (string) $request->input('session_duration');
        $dateTime = null;
        $dateTimeFormatted = null;
        $expires = $isWalk ? 'end of today' : trim((string) $request->input('expires'));
        $expiresAt = $isWalk ? now()->endOfDay() : $this->parseExpiresAt($expires);

        if ($isWalk) {
            $dateTime = now();
            $dateTimeFormatted = 'Today, now';
        } elseif ($request->filled('date_time')) {
            $dateTime = Carbon::createFromFormat('Y-m-d H:i', (string) $request->input('date_time'));
            $dateTimeFormatted = $dateTime?->format('D j M, H:i');
        }

        $title = trim((string) $request->input('title'));
        if ($title === '') {
            $title = $isDeposit ? 'Deposit' : 'Full payment';
        }

        return [
            'artist_id' => (int) $request->input('artist_id'),
            'amount' => $amount,
            'payment_type' => (string) $request->input('payment_type'),
            'title' => $title,
            'date_time' => $dateTime,
            'date_time_formatted' => $dateTimeFormatted,
            'session_duration' => $sessionDuration,
            'session_duration_label' => $this->paymentLinkDurationLabel($sessionDuration),
            'total_price' => $isDeposit ? $total : null,
            'due_amount' => $due,
            'expires' => $expires,
            'expires_at' => $expiresAt,
            'scheduling_type' => 'managed',
            'is_walk_in' => $isWalk,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function studioPaymentLinkPreview(Request $request): array
    {
        $payload = $this->normalizedStudioPaymentLinkData($request);
        $isDeposit = $payload['payment_type'] === 'deposit';

        return [
            'title' => $payload['title'],
            'payment_type' => $payload['payment_type'],
            'amount_formatted' => $this->formatEuro($payload['amount']),
            'due_formatted' => $isDeposit ? $this->formatEuro((float) $payload['due_amount']) : null,
            'expires' => $payload['expires'],
            'session_duration_label' => $payload['session_duration_label'],
            'date_time_formatted' => $payload['date_time_formatted'],
        ];
    }

    private function buildClientMessage(array $payload, string $displayUrl): string
    {
        $kind = $payload['payment_type'] === 'deposit' ? 'deposit' : 'payment';

        return sprintf(
            "Here's your secure %s link for %s — %s, valid %s: %s",
            $kind,
            $payload['title'],
            $this->formatEuro((float) $payload['amount']),
            $payload['expires'],
            $displayUrl
        );
    }

    private function generatePaymentLinkCode(): string
    {
        $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (PaymentLink::query()->where('code', $code)->exists());

        return $code;
    }

    private function parseExpiresAt(string $expires): Carbon
    {
        if (preg_match('/(\d+)\s*(week|weeks|w)\b/i', $expires, $match)) {
            return now()->addWeeks((int) $match[1]);
        }

        if (preg_match('/(\d+)\s*(day|days|d)\b/i', $expires, $match)) {
            return now()->addDays((int) $match[1]);
        }

        if (preg_match('/(\d+)\s*(hour|hours|h)\b/i', $expires, $match)) {
            return now()->addHours((int) $match[1]);
        }

        return now()->addDays(7);
    }

    private function paymentLinkDurationLabel(string $duration): string
    {
        return match ($duration) {
            'half-day' => 'Half day',
            'full-day' => 'Full day',
            default => $duration,
        };
    }

    private function formatEuro(float $value): string
    {
        $rounded = round($value, 2);
        if (fmod($rounded, 1.0) === 0.0) {
            return '€'.(string) (int) $rounded;
        }

        return '€'.number_format($rounded, 2, '.', '');
    }

    private function parseMoney(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[^\d.,-]/', '', $value) ?? '';
        $cleaned = str_replace(',', '.', $cleaned);

        if ($cleaned === '' || $cleaned === '.' || $cleaned === '-') {
            return null;
        }

        if (! is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }
}
