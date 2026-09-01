<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\InsufficientPlatformBalanceException;
use App\Models\ArtistReferral;
use App\Services\ArtistReferralRewardService;
use App\Support\AdminListPagination;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferralsController extends Controller
{
    public function __construct(
        private readonly ArtistReferralRewardService $referralRewards,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $perPage = $filters['per_page'];

        $query = ArtistReferral::query()
            ->with([
                'referrer.userDetail',
                'referred.userDetail',
                'qualifiedBooking',
            ])
            ->latest('created_at');

        $this->applyFilters($query, $filters);

        $total = (clone $query)->count();

        return view('admin.referrals.index', [
            'referrals' => $query->paginate($perPage)->withQueryString(),
            'filters' => $filters,
            'total' => $total,
            'perPage' => $perPage,
            'statuses' => [
                ArtistReferral::STATUS_PENDING,
                ArtistReferral::STATUS_SENT_TO_ADMIN,
                ArtistReferral::STATUS_REWARDED,
                ArtistReferral::STATUS_REJECTED,
            ],
        ]);
    }

    public function sendReward(ArtistReferral $referral): JsonResponse
    {
        try {
            $this->referralRewards->sendRewardToReferrer($referral);

            return response()->json([
                'success' => true,
                'message' => 'Reward transferred to the referring artist\'s Stripe account and they have been notified by email.',
            ]);
        } catch (InsufficientPlatformBalanceException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Platform Stripe balance is too low to send this reward. Top up the platform balance and try again.',
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reject(Request $request, ArtistReferral $referral): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        try {
            $this->referralRewards->rejectReferral($referral, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Referral rejected. The referring artist has been notified by email.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @return array{q: string, status: string, per_page: int}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                'all',
                ArtistReferral::STATUS_PENDING,
                ArtistReferral::STATUS_SENT_TO_ADMIN,
                ArtistReferral::STATUS_REWARDED,
                ArtistReferral::STATUS_REJECTED,
            ])],
            'per_page' => ['nullable', 'integer', Rule::in(AdminListPagination::OPTIONS)],
        ]);

        return [
            'q' => trim((string) ($validated['q'] ?? '')),
            'status' => (string) ($validated['status'] ?? 'all'),
            'per_page' => AdminListPagination::perPage($request),
        ];
    }

    /**
     * @param  Builder<ArtistReferral>  $query
     * @param  array{q: string, status: string, per_page: int}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['q'] === '') {
            return;
        }

        $term = '%'.$filters['q'].'%';

        $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->whereHas('referrer', function (Builder $referrer) use ($term): void {
                    $referrer
                        ->where('email', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhereHas('userDetail', fn (Builder $detail) => $detail->where('user_name', 'like', $term));
                })
                ->orWhereHas('referred', function (Builder $referred) use ($term): void {
                    $referred
                        ->where('email', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhereHas('userDetail', fn (Builder $detail) => $detail->where('user_name', 'like', $term));
                });
        });
    }
}
