<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Models\Waitlist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ArtistClientService
{
    /**
     * @return array{
     *     clients: array<int, array<string, mixed>>,
     *     waitlist: array<int, array<string, mixed>>,
     *     guest_waitlist: array<int, array<string, mixed>>,
     *     stats: array<string, int|float>
     * }
     */
    public function buildForArtist(int $artistUserId): array
    {
        $bookings = Booking::query()
            ->where('artist_user_id', $artistUserId)
            ->with(['user.userDetail', 'tattoo'])
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->get();

        $userIds = $bookings->pluck('user_id')->filter()->unique()->values()->all();

        $customByUser = collect();
        $designByUser = collect();
        if ($userIds !== []) {
            $customByUser = CustomRequest::query()
                ->where('artist_id', $artistUserId)
                ->whereIn('user_id', $userIds)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy('user_id');

            $designByUser = BookingRequest::query()
                ->where('artist_id', $artistUserId)
                ->whereIn('user_id', $userIds)
                ->with('tattoo')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy('user_id');
        }

        $grouped = $bookings->groupBy('user_id');
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();

        $clients = $grouped
            ->map(function (Collection $clientBookings, $userId) use ($now, $customByUser, $designByUser) {
                $uid = (int) $userId;

                return $this->formatClient(
                    $uid,
                    $clientBookings,
                    $now,
                    $customByUser->get($uid, collect()),
                    $designByUser->get($uid, collect())
                );
            })
            ->values()
            ->all();

        $total = count($clients);
        $active = $grouped->filter(
            fn (Collection $clientBookings) => $clientBookings->contains(fn (Booking $b) => $b->status === 'confirmed')
        )->count();
        $newThisMonth = collect($clients)->filter(function (array $client) use ($monthStart) {
            $first = $client['first_visit_raw'] ?? null;
            if (! $first) {
                return false;
            }

            return Carbon::parse($first)->greaterThanOrEqualTo($monthStart);
        })->count();

        $returning = collect($clients)->filter(fn (array $c) => ($c['totalBookings'] ?? 0) >= 2)->count();
        $returningRate = $total > 0 ? (int) round(($returning / $total) * 100) : 0;

        $waitlist = $this->buildWaitlistForArtist($artistUserId);
        $guestWaitlist = $this->buildGuestWaitlistForArtist($artistUserId);

        return [
            'clients' => $clients,
            'waitlist' => $waitlist,
            'guest_waitlist' => $guestWaitlist,
            'stats' => [
                'total' => $total,
                'active' => $active,
                'new_this_month' => $newThisMonth,
                'returning_rate' => $returningRate,
                'waitlist' => count($waitlist),
                'guest_waitlist' => count($guestWaitlist),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildWaitlistForArtist(int $artistUserId): array
    {
        return Waitlist::query()
            ->booksOnly()
            ->where('user_id', $artistUserId)
            ->with('customRequest')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (Waitlist $entry) {
                $statusKey = (string) ($entry->status ?? Waitlist::STATUS_PENDING);
                $isSent = $statusKey === Waitlist::STATUS_SENT;
                $notifiedAt = $isSent ? ($entry->updated_at ?? $entry->created_at) : null;
                $customRequest = $entry->customRequest;
                $hasRequest = $customRequest !== null;
                $released = $hasRequest && $customRequest->waitlist_released_at !== null;

                if ($hasRequest && $released) {
                    $requestKind = 'moved';
                    $requestLabel = 'Moved to Requests';
                    $requestUrl = route('artist.requests.index', ['open' => $customRequest->id]);
                } elseif ($hasRequest) {
                    $requestKind = 'req';
                    $requestLabel = 'View request';
                    $requestUrl = route('artist.requests.index', ['open' => $customRequest->id]);
                } else {
                    $requestKind = 'name_email';
                    $requestLabel = 'Name and email only';
                    $requestUrl = null;
                }

                return [
                    'id' => $entry->id,
                    'name' => $entry->name,
                    'email' => $entry->email,
                    'initials' => $this->initials($entry->name),
                    'status' => $isSent
                        ? ('Notified '.($notifiedAt?->format('M j, Y') ?? ''))
                        : 'Waiting',
                    'status_key' => $statusKey,
                    'joined_at' => $entry->created_at?->format('Y-m-d') ?? '',
                    'joined_at_label' => $entry->created_at?->format('M j, Y') ?? '—',
                    'request_kind' => $requestKind,
                    'request_label' => $requestLabel,
                    'request_url' => $requestUrl,
                    'custom_request_id' => $customRequest?->id,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildGuestWaitlistForArtist(int $artistUserId): array
    {
        return Waitlist::query()
            ->guestOnly()
            ->where('user_id', $artistUserId)
            ->with('guestSpot')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (Waitlist $entry) {
                $statusKey = (string) ($entry->status ?? Waitlist::STATUS_PENDING);
                $isSent = $statusKey === Waitlist::STATUS_SENT;
                $notifiedAt = $isSent ? ($entry->updated_at ?? $entry->created_at) : null;
                $spot = $entry->guestSpot;
                if ($spot) {
                    $spot->ensureCompletedStatus();
                }
                $city = trim((string) ($spot?->city ?? ''));
                $country = trim((string) ($spot?->country ?? ''));
                $cityLabel = trim($city.($country !== '' ? ', '.$country : ''));
                if ($cityLabel === '') {
                    $cityLabel = 'Guest spot';
                }

                $isOpen = $spot !== null
                    && $spot->effectiveStatusKey() === 'available'
                    && $spot->isReservable();

                $openLabel = '';
                if ($isOpen && $spot->from_date && $spot->to_date) {
                    $openCity = $city !== '' ? $city : '';
                    $openLabel = trim(
                        ($openCity !== '' ? $openCity.', ' : '')
                        .$spot->from_date->format('M j')
                        .' to '
                        .$spot->to_date->format('j, Y')
                    );
                }

                return [
                    'id' => $entry->id,
                    'name' => $entry->name,
                    'email' => $entry->email,
                    'initials' => $this->initials($entry->name),
                    'status' => $isSent
                        ? ('Notified '.($notifiedAt?->format('M j, Y') ?? ''))
                        : 'Waiting',
                    'status_key' => $statusKey,
                    'joined_at' => $entry->created_at?->format('Y-m-d') ?? '',
                    'joined_at_label' => $entry->created_at?->format('M j, Y') ?? '—',
                    'guest_spot_id' => $entry->guest_spot_id,
                    'city_label' => $cityLabel,
                    'spot_status' => $spot?->effectiveStatusKey(),
                    'has_open_spot' => $isOpen,
                    'open_spot_label' => $openLabel,
                    'open_guest_spot_id' => $isOpen ? $spot?->id : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @param  Collection<int, CustomRequest>  $customRequests
     * @param  Collection<int, BookingRequest>  $designRequests
     * @return array<string, mixed>
     */
    private function formatClient(
        int $userId,
        Collection $bookings,
        Carbon $now,
        Collection $customRequests,
        Collection $designRequests
    ): array {
        /** @var Booking $latest */
        $latest = $bookings->first();
        $user = $latest->user;

        $name = $user
            ? trim($user->first_name.' '.$user->last_name)
            : 'Unknown client';

        $completed = $bookings->where('status', 'completed')->count();
        $cancelled = $bookings->where('status', 'cancelled')->count();
        $noShows = $bookings->where('status', 'no_show')->count();
        $open = $bookings->whereIn('status', ['pending', 'confirmed']);

        $firstBooking = $bookings->sortBy('booking_date')->first();
        $firstVisit = $firstBooking?->booking_date;

        $upcoming = $open
            ->filter(fn (Booking $b) => $b->booking_date && Carbon::parse($b->booking_date)->startOfDay()->gte($now->copy()->startOfDay()))
            ->sortBy('booking_date')
            ->first();

        $lastCompleted = $bookings
            ->where('status', 'completed')
            ->sortByDesc('booking_date')
            ->first();

        $lastPast = $bookings
            ->filter(function (Booking $b) use ($now) {
                if (! $b->booking_date) {
                    return false;
                }
                if (in_array($b->status, ['completed', 'cancelled', 'no_show'], true)) {
                    return true;
                }

                return Carbon::parse($b->booking_date)->startOfDay()->lt($now->copy()->startOfDay());
            })
            ->sortByDesc('booking_date')
            ->first();

        $lastSessionSort = $this->lastSessionSortValue($upcoming, $lastCompleted, $latest);

        $totalPaid = $bookings->sum(fn (Booking $b) => $this->bookingPaidAmount($b));
        $depositsPaid = $bookings->sum(fn (Booking $b) => max(0, (float) ($b->deposit_amount ?? 0)));
        $outstanding = $bookings
            ->reject(fn (Booking $b) => in_array($b->status, ['cancelled', 'no_show'], true))
            ->sum(fn (Booking $b) => $b->remainingBalanceAmount());

        $status = $this->resolveStatus($bookings);
        $hasUpcoming = $open
            ->contains(fn (Booking $b) => $b->booking_date && Carbon::parse($b->booking_date)->startOfDay()->gte($now->copy()->startOfDay()));
        $isReturning = $bookings->count() >= 2;
        $isNewThisMonth = $firstVisit && $firstVisit->copy()->greaterThanOrEqualTo($now->copy()->startOfMonth());

        $notes = $bookings
            ->pluck('notes')
            ->filter(fn ($note) => filled($note))
            ->unique()
            ->values()
            ->implode("\n\n");

        $bookingRows = $bookings
            ->sortByDesc('booking_date')
            ->map(fn (Booking $b) => [
                'date' => $b->booking_date?->format('Y-m-d') ?? '',
                'date_label' => $b->booking_date?->format('d/m/Y') ?? '—',
                'service' => $b->displayTitle(),
                'reference' => $b->referenceLabel(),
                'status' => $this->bookingDisplayStatus($b, $now),
                'amount' => $this->bookingPaidAmount($b),
                'paid' => $this->bookingPaidAmount($b),
                'due' => in_array($b->status, ['cancelled', 'no_show'], true)
                    ? 0.0
                    : $b->remainingBalanceAmount(),
                'time_label' => $this->bookingTimeLabel($b),
            ])
            ->values()
            ->all();

        $phone = $user?->phone_number;
        if (! filled($phone)) {
            $phone = $user?->userDetail?->mobile_number;
        }
        $phone = filled($phone) ? (string) $phone : '—';

        $instagram = trim((string) ($user?->userDetail?->instagram_username ?? ''));
        $foundVia = trim((string) ($user?->hear_about_us ?? ''));

        $requestRows = $this->formatRequestRows($customRequests, $designRequests);

        return [
            'id' => $userId,
            'name' => $name !== '' ? $name : 'Unknown client',
            'initials' => $this->initials($name),
            'email' => (string) ($user?->email ?? '—'),
            'phone' => $phone,
            'instagram' => $instagram,
            'found_via' => $foundVia !== '' ? $foundVia : 'Not recorded',
            'totalBookings' => $bookings->count(),
            'completed' => $completed,
            'cancelled' => $cancelled,
            'no_shows' => $noShows,
            'last_session_sort' => $lastSessionSort,
            'totalSpent' => round($totalPaid, 2),
            'total_paid' => round($totalPaid, 2),
            'deposits_paid' => round($depositsPaid, 2),
            'outstanding' => round((float) $outstanding, 2),
            'next_booking_label' => $upcoming
                ? trim(($upcoming->booking_date?->format('d/m/Y') ?? '').' · '.$this->bookingTimeLabel($upcoming), ' ·')
                : 'None yet',
            'last_booking_label' => $lastPast
                ? ($lastPast->booking_date?->format('d/m/Y') ?? 'None yet')
                : 'None yet',
            'client_since_label' => $firstVisit ? $firstVisit->format('d/m/Y') : '—',
            'status' => $status,
            'has_upcoming' => (bool) $hasUpcoming,
            'is_returning' => (bool) $isReturning,
            'is_new' => (bool) $isNewThisMonth || $status === 'New',
            'firstVisit' => $firstVisit ? $firstVisit->format('Y-m-d') : '—',
            'first_visit_raw' => $firstVisit?->format('Y-m-d'),
            'note' => $notes,
            'bookings' => $bookingRows,
            'requests' => $requestRows,
            'chat_url' => route('artist.chat.index', ['client' => $userId]),
            'bookings_url' => route('artist.bookings.index', ['client' => $userId]),
            'requests_url' => route('artist.requests.index'),
        ];
    }

    /**
     * @param  Collection<int, CustomRequest>  $customRequests
     * @param  Collection<int, BookingRequest>  $designRequests
     * @return array<int, array<string, mixed>>
     */
    private function formatRequestRows(Collection $customRequests, Collection $designRequests): array
    {
        $custom = $customRequests->map(function (CustomRequest $req) {
            $status = $req->filterStatusLabel();

            return [
                'id' => $req->id,
                'type' => 'custom',
                'title' => 'Custom tattoo request',
                'meta' => $req->referenceLabel().' · sent '.($req->created_at?->format('d/m/Y') ?? '—'),
                'status' => $status,
                'status_key' => (string) $req->status,
                'sort' => $req->created_at?->format('Y-m-d H:i:s') ?? '',
            ];
        });

        $design = $designRequests->map(function (BookingRequest $req) {
            $title = trim((string) ($req->tattoo?->title ?? ''));
            if ($title === '') {
                $title = 'Design request';
            }

            return [
                'id' => $req->id,
                'type' => 'design',
                'title' => $title,
                'meta' => $req->referenceLabel().' · sent '.($req->created_at?->format('d/m/Y') ?? '—'),
                'status' => $req->filterStatusLabel(),
                'status_key' => (string) $req->status,
                'sort' => $req->created_at?->format('Y-m-d H:i:s') ?? '',
            ];
        });

        return $custom
            ->concat($design)
            ->sortByDesc('sort')
            ->values()
            ->map(function (array $row) {
                unset($row['sort']);

                return $row;
            })
            ->all();
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function resolveStatus(Collection $bookings): string
    {
        if ($bookings->contains(fn (Booking $b) => $b->status === 'confirmed')) {
            return 'Active';
        }

        if ($bookings->contains(fn (Booking $b) => $b->status === 'pending')
            && $bookings->where('status', 'completed')->isEmpty()) {
            return 'New';
        }

        return 'Past';
    }

    private function bookingDisplayStatus(Booking $booking, Carbon $now): string
    {
        if ($booking->status === 'no_show') {
            return 'No-show';
        }

        if (in_array($booking->status, ['pending', 'confirmed'], true)) {
            if ($booking->booking_date && Carbon::parse($booking->booking_date)->startOfDay()->gte($now->copy()->startOfDay())) {
                return 'Upcoming';
            }

            return 'Confirmed';
        }

        if ($booking->status === 'completed') {
            return 'Completed';
        }

        if ($booking->status === 'cancelled') {
            return 'Cancelled';
        }

        return ucfirst(str_replace('_', ' ', (string) $booking->status));
    }

    private function bookingTimeLabel(Booking $booking): string
    {
        if (! $booking->booking_date || ! $booking->start_time_utc) {
            return '';
        }

        try {
            $timezone = $booking->timezone ?: 'UTC';
            $start = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $booking->booking_date->format('Y-m-d').' '.$booking->start_time_utc,
                'UTC'
            )->setTimezone($timezone);

            return $start->format('H:i');
        } catch (\Throwable) {
            return '';
        }
    }

    private function bookingPaidAmount(Booking $booking): float
    {
        $deposit = max(0, (float) ($booking->deposit_amount ?? 0));
        if ($deposit > 0) {
            return round($deposit, 2);
        }

        return $this->clientPaidToArtistAmount($booking);
    }

    private function lastSessionSortValue(?Booking $upcoming, ?Booking $lastCompleted, ?Booking $latest): string
    {
        if ($upcoming?->booking_date) {
            return '9999-12-31';
        }

        $date = $lastCompleted?->booking_date ?? $latest?->booking_date;

        return $date ? $date->format('Y-m-d') : '';
    }

    private function clientPaidToArtistAmount(Booking $booking): float
    {
        if ($booking->payment_status !== 'paid') {
            return 0.0;
        }

        return max(0, round((float) $booking->total_amount_paid - (float) $booking->platform_fee, 2));
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
