<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Waitlist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ArtistClientService
{
    /**
     * @return array{
     *     clients: array<int, array<string, mixed>>,
     *     waitlist: array<int, array<string, mixed>>,
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

        $grouped = $bookings->groupBy('user_id');
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();

        $clients = $grouped
            ->map(fn (Collection $clientBookings, $userId) => $this->formatClient((int) $userId, $clientBookings, $now))
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

        return [
            'clients' => $clients,
            'waitlist' => $waitlist,
            'stats' => [
                'total' => $total,
                'active' => $active,
                'new_this_month' => $newThisMonth,
                'returning_rate' => $returningRate,
                'waitlist' => count($waitlist),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildWaitlistForArtist(int $artistUserId): array
    {
        return Waitlist::query()
            ->where('user_id', $artistUserId)
            ->where('status', Waitlist::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (Waitlist $entry) {
                $statusKey = (string) ($entry->status ?? Waitlist::STATUS_PENDING);

                return [
                    'id' => $entry->id,
                    'name' => $entry->name,
                    'email' => $entry->email,
                    'initials' => $this->initials($entry->name),
                    'status' => $statusKey === Waitlist::STATUS_SENT ? 'Notified' : 'Waiting',
                    'status_key' => $statusKey,
                    'joined_at' => $entry->created_at?->format('Y-m-d') ?? '',
                    'joined_at_label' => $entry->created_at?->format('M j, Y') ?? '—',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return array<string, mixed>
     */
    private function formatClient(int $userId, Collection $bookings, Carbon $now): array
    {
        /** @var Booking $latest */
        $latest = $bookings->first();
        $user = $latest->user;

        $name = $user
            ? trim($user->first_name.' '.$user->last_name)
            : 'Unknown client';

        $completed = $bookings->where('status', 'completed')->count();
        $cancelled = $bookings->where('status', 'cancelled')->count();
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

        $lastSessionSort = $this->lastSessionSortValue($upcoming, $lastCompleted, $latest);

        $totalSpent = $bookings
            ->where('payment_status', 'paid')
            ->sum(fn (Booking $b) => (float) $b->total_amount_paid);

        $status = $this->resolveStatus($bookings);

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
                'service' => $b->displayTitle(),
                'reference' => $b->referenceLabel(),
                'status' => $this->bookingDisplayStatus($b, $now),
                'amount' => $b->payment_status === 'paid' ? (float) $b->total_amount_paid : 0.0,
            ])
            ->values()
            ->all();

        $phone = $user?->phone_number;
        if (! filled($phone)) {
            $phone = $user?->userDetail?->mobile_number;
        }
        $phone = filled($phone) ? (string) $phone : '—';

        return [
            'id' => $userId,
            'name' => $name !== '' ? $name : 'Unknown client',
            'initials' => $this->initials($name),
            'email' => (string) ($user?->email ?? '—'),
            'phone' => $phone,
            'totalBookings' => $bookings->count(),
            'completed' => $completed,
            'cancelled' => $cancelled,
            'last_session_sort' => $lastSessionSort,
            'totalSpent' => round($totalSpent, 2),
            'status' => $status,
            'firstVisit' => $firstVisit ? $firstVisit->format('Y-m-d') : '—',
            'first_visit_raw' => $firstVisit?->format('Y-m-d'),
            'note' => $notes,
            'bookings' => $bookingRows,
            'chat_url' => route('artist.chat.index', ['client' => $userId]),
            'bookings_url' => route('artist.bookings.index', ['client' => $userId]),
            'requests_url' => route('artist.requests.index'),
        ];
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

    private function lastSessionSortValue(?Booking $upcoming, ?Booking $lastCompleted, ?Booking $latest): string
    {
        if ($upcoming?->booking_date) {
            return '9999-12-31';
        }

        $date = $lastCompleted?->booking_date ?? $latest?->booking_date;

        return $date ? $date->format('Y-m-d') : '';
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
