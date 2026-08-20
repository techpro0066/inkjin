<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtistPayout;
use App\Models\Booking;
use App\Support\AdminListPagination;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialController extends Controller
{
    public function revenue(Request $request): View
    {
        $filters = $this->validatedFilters($request, ['pending', 'paid', 'refunded', 'failed']);
        $query = Booking::query()
            ->with(['user', 'artist', 'tattoo'])
            ->where('total_amount_paid', '>', 0);

        $this->applyBookingFilters($query, $filters);

        $gross = (float) (clone $query)->sum('total_amount_paid');
        $refunds = (float) (clone $query)->sum('refund_amount');

        return $this->financialView('revenue', $query->latest()->paginate($filters['per_page'])->withQueryString(), [
            ['label' => 'Gross Revenue', 'value' => $gross, 'icon' => 'payments', 'color' => 'purple'],
            ['label' => 'Refunds', 'value' => $refunds, 'icon' => 'undo', 'color' => 'red'],
            ['label' => 'Net Revenue', 'value' => max(0, $gross - $refunds), 'icon' => 'trending_up', 'color' => 'green'],
        ], $filters, ['pending', 'paid', 'refunded', 'failed']);
    }

    public function fees(Request $request): View
    {
        $filters = $this->validatedFilters($request, ['pending', 'paid', 'refunded', 'failed']);
        $query = Booking::query()
            ->with(['user', 'artist', 'tattoo'])
            ->where('platform_fee', '>', 0);

        $this->applyBookingFilters($query, $filters);

        $gross = (float) (clone $query)->sum('platform_fee');
        $refunded = (float) (clone $query)
            ->where('platform_fee_refunded', true)
            ->sum('platform_fee');

        return $this->financialView('fees', $query->latest()->paginate($filters['per_page'])->withQueryString(), [
            ['label' => 'Total Fees', 'value' => $gross, 'icon' => 'receipt_long', 'color' => 'purple'],
            ['label' => 'Refunded Fees', 'value' => $refunded, 'icon' => 'undo', 'color' => 'red'],
            ['label' => 'Net Fees', 'value' => max(0, $gross - $refunded), 'icon' => 'account_balance', 'color' => 'green'],
        ], $filters, ['pending', 'paid', 'refunded', 'failed']);
    }

    public function payouts(Request $request): View
    {
        $filters = $this->validatedFilters($request, [
            ArtistPayout::STATUS_PENDING,
            ArtistPayout::STATUS_COMPLETED,
            ArtistPayout::STATUS_FAILED,
        ]);

        $query = ArtistPayout::query()->with(['booking.artist.userDetail', 'booking.user']);
        $this->applyPayoutFilters($query, $filters);

        $pending = (float) (clone $query)->where('status', ArtistPayout::STATUS_PENDING)->sum('amount');
        $completed = (float) (clone $query)->where('status', ArtistPayout::STATUS_COMPLETED)->sum('amount');
        $failed = (float) (clone $query)->where('status', ArtistPayout::STATUS_FAILED)->sum('amount');

        return $this->financialView('payouts', $query->latest()->paginate($filters['per_page'])->withQueryString(), [
            ['label' => 'Pending Payouts', 'value' => $pending, 'icon' => 'schedule_send', 'color' => 'amber'],
            ['label' => 'Completed Payouts', 'value' => $completed, 'icon' => 'check_circle', 'color' => 'green'],
            ['label' => 'Failed Payouts', 'value' => $failed, 'icon' => 'error', 'color' => 'red'],
        ], $filters, [
            ArtistPayout::STATUS_PENDING,
            ArtistPayout::STATUS_COMPLETED,
            ArtistPayout::STATUS_FAILED,
        ]);
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array{q:string,status:string,from:?string,to:?string,per_page:int}
     */
    private function validatedFilters(Request $request, array $statuses): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_merge(['all'], $statuses))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', Rule::in(AdminListPagination::OPTIONS)],
        ]);

        return [
            'q' => trim((string) ($validated['q'] ?? '')),
            'status' => (string) ($validated['status'] ?? 'all'),
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'per_page' => AdminListPagination::perPage($request),
        ];
    }

    /**
     * @param  Builder<Booking>  $query
     * @param  array{q:string,status:string,from:?string,to:?string,per_page:int}  $filters
     */
    private function applyBookingFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('payment_status', $filters['status']);
        }
        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function (Builder $query) use ($search) {
                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
                $query->orWhere('payment_intent_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $this->searchUser($user, $search))
                    ->orWhereHas('artist', fn (Builder $artist) => $this->searchUser($artist, $search));
            });
        }
    }

    /**
     * @param  Builder<ArtistPayout>  $query
     * @param  array{q:string,status:string,from:?string,to:?string,per_page:int}  $filters
     */
    private function applyPayoutFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function (Builder $query) use ($search) {
                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search)
                        ->orWhere('booking_id', (int) $search);
                }
                $query->orWhere('stripe_transfer_id', 'like', "%{$search}%")
                    ->orWhereHas('booking.artist', fn (Builder $artist) => $this->searchUser($artist, $search));
            });
        }
    }

    private function searchUser(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search) {
            $query->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * @param  array<int, array{label:string,value:float,icon:string,color:string}>  $summaryCards
     * @param  array{q:string,status:string,from:?string,to:?string,per_page:int}  $filters
     * @param  array<int, string>  $statuses
     */
    private function financialView(
        string $section,
        mixed $records,
        array $summaryCards,
        array $filters,
        array $statuses,
    ): View {
        $perPage = $filters['per_page'];

        return view('admin.financial.index', compact(
            'section',
            'records',
            'summaryCards',
            'filters',
            'statuses',
            'perPage',
        ));
    }
}
