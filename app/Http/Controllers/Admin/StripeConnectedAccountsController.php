<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Models\UserDetail;
use App\Services\StripeConnectService;
use App\Support\AdminListPagination;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StripeConnectedAccountsController extends Controller
{
    public function __construct(
        private readonly StripeConnectService $stripeConnect,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'per_page' => AdminListPagination::perPage($request),
        ];

        $accounts = $this->buildAccountRows();
        $accounts = $this->filterAccounts($accounts, $filters['q']);
        $accounts = $accounts->sortBy([
            ['owner_name', 'asc'],
            ['account_id', 'asc'],
        ])->values();

        $summary = [
            'total' => $accounts->count(),
            'ready' => $accounts->where('status_key', 'ready')->count(),
            'action_required' => $accounts->where('status_key', 'action_required')->count(),
            'restricted' => $accounts->where('status_key', 'restricted')->count(),
        ];

        $paginator = $this->paginateAccounts($accounts, $filters['per_page'], $request);

        return view('admin.stripe-accounts.index', [
            'accounts' => $paginator,
            'filters' => $filters,
            'summary' => $summary,
            'perPage' => $filters['per_page'],
            'stripeConfigured' => $this->stripeConnect->isConfigured(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function buildAccountRows(): Collection
    {
        $rows = collect();
        $seenAccountIds = [];

        UserDetail::query()
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->with(['user', 'studio'])
            ->orderBy('id')
            ->get()
            ->each(function (UserDetail $userDetail) use (&$rows, &$seenAccountIds) {
                $accountId = trim((string) $userDetail->stripe_account_id);
                if ($accountId === '' || isset($seenAccountIds[$accountId])) {
                    return;
                }

                $seenAccountIds[$accountId] = true;
                $user = $userDetail->user;
                $ownerName = trim((string) ($userDetail->publicDisplayName() ?: ($user?->first_name.' '.$user?->last_name)));

                $rows->push($this->formatAccountRow(
                    accountId: $accountId,
                    ownerType: 'Artist',
                    ownerName: $ownerName !== '' ? $ownerName : 'Artist #'.$userDetail->user_id,
                    ownerEmail: (string) ($user?->email ?? ''),
                    ownerUsername: (string) ($userDetail->user_name ?? ''),
                    paymentType: (string) ($userDetail->payment_type ?? ''),
                    paymentStatus: (string) ($userDetail->payment_status ?? ''),
                    studioName: (string) ($userDetail->studio?->name ?? $userDetail->studio_name ?? ''),
                ));
            });

        Studio::query()
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->orderBy('name')
            ->get()
            ->each(function (Studio $studio) use (&$rows, &$seenAccountIds) {
                $accountId = trim((string) $studio->stripe_account_id);
                if ($accountId === '' || isset($seenAccountIds[$accountId])) {
                    return;
                }

                $seenAccountIds[$accountId] = true;

                $rows->push($this->formatAccountRow(
                    accountId: $accountId,
                    ownerType: 'Studio',
                    ownerName: (string) ($studio->name ?: 'Studio #'.$studio->id),
                    ownerEmail: (string) ($studio->email ?? ''),
                    ownerUsername: '',
                    paymentType: 'studio_account',
                    paymentStatus: '',
                    studioName: (string) ($studio->name ?? ''),
                ));
            });

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAccountRow(
        string $accountId,
        string $ownerType,
        string $ownerName,
        string $ownerEmail,
        string $ownerUsername,
        string $paymentType,
        string $paymentStatus,
        string $studioName,
    ): array {
        $status = $this->resolveStripeStatus($accountId);

        return array_merge([
            'account_id' => $accountId,
            'owner_type' => $ownerType,
            'owner_name' => $ownerName,
            'owner_email' => $ownerEmail,
            'owner_username' => $ownerUsername,
            'payment_type' => $paymentType,
            'payment_status' => $paymentStatus,
            'studio_name' => $studioName,
        ], $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveStripeStatus(string $accountId): array
    {
        if (! $this->stripeConnect->isConfigured()) {
            return [
                'status_key' => 'unknown',
                'status_label' => 'Stripe not configured',
                'charges_enabled' => null,
                'payouts_enabled' => null,
                'requirements_due' => null,
                'disabled_reason' => null,
                'status_error' => null,
            ];
        }

        try {
            $stripe = $this->stripeConnect->getOnboardingStatus($accountId);
        } catch (\Throwable $e) {
            return [
                'status_key' => 'error',
                'status_label' => 'Unable to load',
                'charges_enabled' => null,
                'payouts_enabled' => null,
                'requirements_due' => null,
                'disabled_reason' => null,
                'status_error' => $e->getMessage(),
            ];
        }

        $requirementsDue = count($stripe['currently_due'] ?? []);
        $disabledReason = trim((string) ($stripe['disabled_reason'] ?? ''));

        if ($disabledReason !== '') {
            $statusKey = 'restricted';
            $statusLabel = 'Restricted';
        } elseif (! empty($stripe['payout_ready'])) {
            $statusKey = 'ready';
            $statusLabel = 'Ready';
        } elseif ($requirementsDue > 0) {
            $statusKey = 'action_required';
            $statusLabel = 'Action required';
        } elseif (! empty($stripe['pending_verification'])) {
            $statusKey = 'pending';
            $statusLabel = 'Pending review';
        } elseif (! empty($stripe['complete'])) {
            $statusKey = 'pending';
            $statusLabel = 'Pending enablement';
        } else {
            $statusKey = 'pending';
            $statusLabel = 'In progress';
        }

        return [
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'charges_enabled' => (bool) ($stripe['charges_enabled'] ?? false),
            'payouts_enabled' => (bool) ($stripe['payouts_enabled'] ?? false),
            'requirements_due' => $requirementsDue,
            'disabled_reason' => $disabledReason !== '' ? $disabledReason : null,
            'status_error' => null,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     * @return Collection<int, array<string, mixed>>
     */
    private function filterAccounts(Collection $accounts, string $search): Collection
    {
        if ($search === '') {
            return $accounts;
        }

        $needle = mb_strtolower($search);

        return $accounts->filter(function (array $account) use ($needle) {
            $haystack = mb_strtolower(implode(' ', [
                $account['account_id'] ?? '',
                $account['owner_name'] ?? '',
                $account['owner_email'] ?? '',
                $account['owner_username'] ?? '',
                $account['owner_type'] ?? '',
                $account['studio_name'] ?? '',
                $account['status_label'] ?? '',
            ]));

            return str_contains($haystack, $needle);
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     */
    private function paginateAccounts(Collection $accounts, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $items = $accounts->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $accounts->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }
}
