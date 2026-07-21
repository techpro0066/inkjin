<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtistPayout;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $paidBookingsThisMonth = Booking::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $pendingPayouts = ArtistPayout::query()
            ->where('status', ArtistPayout::STATUS_PENDING);

        $stats = [
            'artists' => User::query()->where('role', 'artist')->count(),
            'clients' => User::query()->where('role', 'user')->count(),
            'active_bookings' => Booking::query()
                ->where('status', 'confirmed')
                ->whereDate('booking_date', '>=', today())
                ->count(),
            'revenue_this_month' => (float) (clone $paidBookingsThisMonth)->sum('total_amount_paid'),
            'fees_this_month' => (float) (clone $paidBookingsThisMonth)
                ->where('platform_fee_refunded', false)
                ->sum('platform_fee'),
            'pending_payouts' => (float) (clone $pendingPayouts)->sum('amount'),
        ];

        $recentUsers = User::query()
            ->with('userDetail')
            ->where('role', '!=', 'admin')
            ->latest()
            ->limit(8)
            ->get();

        $pendingOnboardingCount = User::query()
            ->where('role', 'artist')
            ->where('on_boarding', '!=', 'yes')
            ->count();
        $pendingPayoutCount = (clone $pendingPayouts)->count();
        $failedPayoutCount = ArtistPayout::query()
            ->where('status', ArtistPayout::STATUS_FAILED)
            ->count();

        $attentionItems = [
            [
                'count' => $pendingOnboardingCount,
                'title' => $pendingOnboardingCount.' '.str('artist')->plural($pendingOnboardingCount).' pending onboarding',
                'subtitle' => 'Setup is not yet complete',
                'icon' => 'person_alert',
                'color' => 'amber',
                'url' => route('admin.users.index', ['role' => 'artist', 'status' => 'pending_onboarding']),
            ],
            [
                'count' => $pendingPayoutCount,
                'title' => $pendingPayoutCount.' pending '.str('payout')->plural($pendingPayoutCount),
                'subtitle' => '€'.number_format($stats['pending_payouts'], 2).' awaiting processing',
                'icon' => 'schedule_send',
                'color' => 'purple',
                'url' => route('admin.payouts.index', ['status' => ArtistPayout::STATUS_PENDING]),
            ],
            [
                'count' => $failedPayoutCount,
                'title' => $failedPayoutCount.' failed '.str('payout')->plural($failedPayoutCount),
                'subtitle' => $failedPayoutCount > 0 ? 'Action may be required' : 'No payout failures',
                'icon' => 'error',
                'color' => 'red',
                'url' => route('admin.payouts.index', ['status' => ArtistPayout::STATUS_FAILED]),
            ],
        ];

        $attentionCount = collect($attentionItems)->sum('count');
        $trends = [
            'bookings' => $this->bookingTrend(),
            'revenue' => $this->revenueTrend(),
            'signups' => $this->signupTrend(),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'recentUsers',
            'attentionItems',
            'attentionCount',
            'trends',
        ));
    }

    /**
     * @return array{items:list<array{label:string,value:float}>,max:float,total:float}
     */
    private function bookingTrend(): array
    {
        $start = today()->startOfWeek();
        $end = today()->endOfWeek();
        $rows = Booking::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->get(['booking_date']);

        $counts = $rows->countBy(fn (Booking $booking) => $booking->booking_date->format('Y-m-d'));
        $items = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $items[] = [
                'label' => $date->format('D'),
                'value' => (float) ($counts[$date->format('Y-m-d')] ?? 0),
            ];
        }

        return $this->trendPayload($items);
    }

    /**
     * @return array{items:list<array{label:string,value:float}>,max:float,total:float}
     */
    private function revenueTrend(): array
    {
        $start = today()->subDays(29)->startOfDay();
        $rows = Booking::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'total_amount_paid']);

        $totals = $rows->groupBy(fn (Booking $booking) => $booking->created_at->format('Y-m-d'))
            ->map(fn (Collection $bookings) => (float) $bookings->sum('total_amount_paid'));

        $items = [];
        for ($date = $start->copy(); $date->lte(today()); $date->addDay()) {
            $items[] = [
                'label' => $date->format('j M'),
                'value' => (float) ($totals[$date->format('Y-m-d')] ?? 0),
            ];
        }

        return $this->trendPayload($items);
    }

    /**
     * @return array{artists:int,clients:int,total:int}
     */
    private function signupTrend(): array
    {
        $start = today()->subDays(29)->startOfDay();

        $artists = User::query()
            ->where('role', 'artist')
            ->where('created_at', '>=', $start)
            ->count();
        $clients = User::query()
            ->where('role', 'user')
            ->where('created_at', '>=', $start)
            ->count();

        return [
            'artists' => $artists,
            'clients' => $clients,
            'total' => $artists + $clients,
        ];
    }

    /**
     * @param  list<array{label:string,value:float}>  $items
     * @return array{items:list<array{label:string,value:float}>,max:float,total:float}
     */
    private function trendPayload(array $items): array
    {
        $values = collect($items)->pluck('value');

        return [
            'items' => $items,
            'max' => max(1, (float) $values->max()),
            'total' => (float) $values->sum(),
        ];
    }
}
