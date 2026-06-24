<?php

namespace App\Services;

use App\Models\ArtistPayout;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Waitlist;
use Carbon\Carbon;

class ArtistDashboardService
{
    public function __construct(
        private readonly ArtistPayoutService $payoutService,
    ) {}

    /**
     * @return array{
     *     stats: array<string, mixed>,
     *     recent_bookings: array<int, array<string, mixed>>
     * }
     */
    public function buildForArtist(int $artistUserId): array
    {
        $artist = User::query()->with('userDetail')->find($artistUserId);
        $userDetail = $artist?->userDetail;
        $timezone = $userDetail?->timezone ?: 'UTC';

        $today = Carbon::now($timezone)->toDateString();
        $monthStart = Carbon::now($timezone)->startOfMonth();
        $monthEnd = Carbon::now($timezone)->endOfMonth();
        $lastMonthStart = Carbon::now($timezone)->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now($timezone)->subMonth()->endOfMonth();

        $todayBookings = Booking::query()
            ->where('artist_user_id', $artistUserId)
            ->whereDate('booking_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $todayConfirmed = $todayBookings->where('status', 'confirmed')->count();
        $todayPending = $todayBookings->where('status', 'pending')->count();

        $thisMonthRevenue = $this->sumNetRevenue(
            $artistUserId,
            $userDetail,
            $monthStart,
            $monthEnd,
        );

        $lastMonthRevenue = $this->sumNetRevenue(
            $artistUserId,
            $userDetail,
            $lastMonthStart,
            $lastMonthEnd,
        );

        $revenueChangePercent = null;
        if ($lastMonthRevenue > 0) {
            $revenueChangePercent = (int) round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100);
        }

        $waitlistCount = Waitlist::query()
            ->where('user_id', $artistUserId)
            ->where('status', Waitlist::STATUS_PENDING)
            ->count();

        $booksOpen = ($userDetail->availability_status ?? '') !== 'closed';

        $recentBookings = Booking::query()
            ->where('artist_user_id', $artistUserId)
            ->with(['user', 'tattoo'])
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Booking $booking) => $this->formatRecentBooking($booking))
            ->values()
            ->all();

        return [
            'stats' => [
                'today_bookings_total' => $todayBookings->count(),
                'today_bookings_confirmed' => $todayConfirmed,
                'today_bookings_pending' => $todayPending,
                'month_revenue' => round($thisMonthRevenue, 2),
                'revenue_change_percent' => $revenueChangePercent,
                'waitlist_count' => $waitlistCount,
                'show_waitlist_notify_button' => $waitlistCount > 0 && $booksOpen,
                'currency_symbol' => '€',
            ],
            'recent_bookings' => $recentBookings,
        ];
    }

    private function sumNetRevenue(
        int $artistUserId,
        ?UserDetail $userDetail,
        Carbon $from,
        Carbon $to,
    ): float {
        $bookings = Booking::query()
            ->where('artist_user_id', $artistUserId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->with('artistPayout')
            ->get();

        return $bookings->sum(fn (Booking $booking) => $this->resolveBookingNet($booking, $userDetail));
    }

    private function resolveBookingNet(Booking $booking, ?UserDetail $userDetail): float
    {
        $payout = $booking->artistPayout;
        if ($payout?->status === ArtistPayout::STATUS_COMPLETED) {
            return (float) $payout->amount;
        }

        $deposit = (float) $booking->deposit_amount;
        if ($userDetail) {
            return max(0, round($this->payoutService->computeArtistPayoutAmount($booking, $userDetail), 2));
        }

        return max(0, round($deposit - (float) $booking->platform_fee, 2));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRecentBooking(Booking $booking): array
    {
        $clientName = trim(($booking->user?->first_name ?? '').' '.($booking->user?->last_name ?? ''));
        $bookingTime = $booking->booking_time;
        $statusKey = strtolower((string) ($booking->status ?? ''));
        $statusLabel = ucfirst(str_replace('_', ' ', $statusKey ?: 'unknown'));

        $badgeClass = match ($statusKey) {
            'confirmed' => 'bg-green-50 text-green-700',
            'pending' => 'bg-amber-50 text-amber-700',
            'completed' => 'bg-blue-50 text-blue-700',
            'cancelled' => 'bg-red-50 text-red-700',
            default => 'bg-surface-container text-on-surface',
        };

        $dotClass = match ($statusKey) {
            'confirmed' => 'bg-green-500',
            'pending' => 'bg-amber-500',
            'completed' => 'bg-blue-500',
            'cancelled' => 'bg-red-500',
            default => 'bg-outline',
        };

        return [
            'id' => $booking->id,
            'client_name' => $clientName !== '' ? $clientName : 'Client #'.$booking->user_id,
            'service' => $booking->displayTitle(),
            'date' => $booking->booking_date?->format('M j') ?? '—',
            'date_long' => $booking->booking_date?->format('l, F j, Y') ?? '—',
            'time' => is_array($bookingTime) ? ($bookingTime['start'] ?? '—') : '—',
            'status' => $statusLabel,
            'status_key' => $statusKey,
            'badge_class' => $badgeClass,
            'dot_class' => $dotClass,
        ];
    }
}
