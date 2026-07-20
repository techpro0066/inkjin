<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Style;
use App\Models\User;
use App\Support\OnboardingProgress;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $roleFilter = $request->get('role', 'all');
        $search = trim((string) $request->get('q', ''));
        $statusFilter = $request->get('status', 'all');
        $sort = $request->get('sort', 'newest');
        $expandedId = (int) $request->get('expanded', 0);

        $query = User::query()
            ->with('userDetail')
            ->withCount(['artistDesigns', 'portfolios', 'availabilities'])
            ->where('role', '!=', 'admin');

        if ($roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('userDetail', function ($detailQuery) use ($search) {
                        $detailQuery->where('user_name', 'like', "%{$search}%")
                            ->orWhere('studio_name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('country', 'like', "%{$search}%");
                    });
            });
        }

        $users = $query->get();
        $styleLabels = Style::query()->pluck('name', 'name')->all();
        $bookingStats = $this->bookingStatsForUsers($users->pluck('id')->all(), $roleFilter);

        $users = $users
            ->map(fn (User $user) => $this->mapUserForAdminList($user, $styleLabels, $bookingStats))
            ->filter(function (array $user) use ($statusFilter) {
                if ($statusFilter === 'all') {
                    return true;
                }

                return $user['status_key'] === $statusFilter;
            })
            ->values();

        $users = $this->sortUsers($users, $sort);

        $stats = [
            'total' => User::query()->where('role', '!=', 'admin')->count(),
            'artists' => User::query()->where('role', 'artist')->count(),
            'clients' => User::query()->where('role', 'user')->count(),
        ];

        $pageTitle = match ($roleFilter) {
            'artist' => 'Artists',
            'user' => 'Clients',
            default => 'Users',
        };

        $pageSubtitle = match ($roleFilter) {
            'artist' => number_format($stats['artists']).' artists on the platform',
            'user' => number_format($stats['clients']).' clients on the platform',
            default => number_format($stats['total']).' users on the platform',
        };

        return view('admin.users.index', compact(
            'users',
            'roleFilter',
            'search',
            'statusFilter',
            'sort',
            'expandedId',
            'stats',
            'pageTitle',
            'pageSubtitle',
        ));
    }

    /**
     * Get user details for modal/API.
     */
    public function show($id): JsonResponse
    {
        $user = User::with(['userDetail', 'availabilities'])->findOrFail($id);

        if ($user->role === 'admin') {
            abort(404);
        }

        $userDetail = $user->userDetail;
        $timezone = $userDetail ? ($userDetail->timezone ?? 'UTC') : 'UTC';

        $availabilities = $user->availabilities->map(function ($availability) use ($timezone, $userDetail) {
            if ($userDetail && $timezone !== 'UTC') {
                try {
                    $startTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d').' '.$availability->start_time, 'UTC')
                        ->setTimezone($timezone)
                        ->format('H:i');
                    $endTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d').' '.$availability->end_time, 'UTC')
                        ->setTimezone($timezone)
                        ->format('H:i');

                    return [
                        'id' => $availability->id,
                        'day_of_week' => $availability->day_of_week,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ];
                } catch (\Exception $e) {
                    return [
                        'id' => $availability->id,
                        'day_of_week' => $availability->day_of_week,
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                    ];
                }
            }

            return [
                'id' => $availability->id,
                'day_of_week' => $availability->day_of_week,
                'start_time' => $availability->start_time,
                'end_time' => $availability->end_time,
            ];
        });

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at,
                'on_boarding' => $user->on_boarding,
                'created_at' => $user->created_at,
            ],
            'userDetail' => $this->userDetailPayload($userDetail),
            'availabilities' => $availabilities,
        ]);
    }

    /**
     * @param  array<int|string, string>  $styleLabels
     * @param  array<int, array{bookings:int,revenue:float}>  $bookingStats
     * @return array<string, mixed>
     */
    private function mapUserForAdminList(User $user, array $styleLabels, array $bookingStats): array
    {
        $detail = $user->userDetail;
        $isArtist = $user->role === 'artist';
        $stats = $bookingStats[$user->id] ?? ['bookings' => 0, 'revenue' => 0.0];

        $locationParts = array_filter([
            trim((string) ($detail?->city ?? '')),
            trim((string) ($detail?->country ?? '')),
        ]);
        $location = $locationParts !== [] ? implode(', ', $locationParts) : '—';

        $status = $this->resolveUserStatus($user);
        $onboardingProgress = ($isArtist && $user->on_boarding !== 'yes')
            ? OnboardingProgress::for($detail)
            : null;

        return [
            'id' => $user->id,
            'name' => $user->name ?: 'N/A',
            'initials' => strtoupper(substr($user->first_name ?? 'U', 0, 1).substr($user->last_name ?? '', 0, 1)),
            'avatar' => $detail?->avatar,
            'email' => $user->email,
            'phone' => $user->phone_number ?: ($detail?->mobile_number ?: '—'),
            'role' => $user->role,
            'username' => $detail?->user_name,
            'studio' => $detail?->studio_name ?: '—',
            'location' => $location,
            'styles' => $isArtist ? $this->resolveStyleLabels($detail?->tattoo_styles, $styleLabels) : [],
            'bookings' => (int) $stats['bookings'],
            'revenue' => (float) $stats['revenue'],
            'designs' => $isArtist ? (int) $user->artist_designs_count : 0,
            'portfolio_items' => $isArtist ? (int) $user->portfolios_count : 0,
            'availability_slots' => $isArtist ? (int) $user->availabilities_count : 0,
            'status' => $status['label'],
            'status_key' => $status['key'],
            'join_date' => $user->created_at?->format('Y-m-d') ?? '—',
            'join_date_label' => $user->created_at?->format('M j, Y') ?? '—',
            'email_verified' => (bool) $user->email_verified_at,
            'on_boarding_complete' => $user->on_boarding === 'yes',
            'onboarding_progress' => $onboardingProgress,
            'scheduling_type' => $detail?->scheduling_type,
            'payment_type' => $detail?->payment_type,
            'payment_status' => $detail?->payment_status,
            'google_calendar_connected' => ! empty($detail?->google_calendar_token),
            'studio_address' => $detail?->studio_address,
            'currency' => $detail?->currency,
            'timezone' => $detail?->timezone,
        ];
    }

    /**
     * @return array{label:string,key:string}
     */
    private function resolveUserStatus(User $user): array
    {
        if ($user->role === 'artist') {
            return $user->on_boarding === 'yes'
                ? ['label' => 'Active', 'key' => 'active']
                : ['label' => 'Pending Onboarding', 'key' => 'pending_onboarding'];
        }

        return $user->email_verified_at
            ? ['label' => 'Active', 'key' => 'active']
            : ['label' => 'Pending Onboarding', 'key' => 'pending_onboarding'];
    }

    /**
     * @param  mixed  $tattooStyles
     * @param  array<int|string, string>  $styleLabels
     * @return list<string>
     */
    private function resolveStyleLabels($tattooStyles, array $styleLabels): array
    {
        if (! is_array($tattooStyles)) {
            return [];
        }

        $styles = [];
        $primary = $tattooStyles['primary_style'] ?? null;
        if (is_string($primary) && $primary !== '') {
            $styles[] = $styleLabels[$primary] ?? ucwords(str_replace('-', ' ', $primary));
        }

        $others = $tattooStyles['other_styles'] ?? [];
        if (! is_array($others) && array_is_list($tattooStyles)) {
            $others = $tattooStyles;
        }

        foreach ($others as $style) {
            if (! is_string($style) || $style === '') {
                continue;
            }
            $styles[] = $styleLabels[$style] ?? ucwords(str_replace('-', ' ', $style));
        }

        return array_values(array_unique($styles));
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, array{bookings:int,revenue:float}>
     */
    private function bookingStatsForUsers(array $userIds, string $roleFilter): array
    {
        if ($userIds === []) {
            return [];
        }

        $stats = [];

        if ($roleFilter !== 'user') {
            $artistRows = Booking::query()
                ->selectRaw('artist_user_id as user_id')
                ->selectRaw('COUNT(*) as bookings')
                ->selectRaw('COALESCE(SUM(total_amount_paid), 0) as revenue')
                ->whereIn('artist_user_id', $userIds)
                ->whereIn('status', ['confirmed', 'completed'])
                ->groupBy('artist_user_id')
                ->get();

            foreach ($artistRows as $row) {
                $stats[(int) $row->user_id] = [
                    'bookings' => (int) $row->bookings,
                    'revenue' => (float) $row->revenue,
                ];
            }
        }

        if ($roleFilter !== 'artist') {
            $clientRows = Booking::query()
                ->selectRaw('user_id')
                ->selectRaw('COUNT(*) as bookings')
                ->selectRaw('COALESCE(SUM(total_amount_paid), 0) as revenue')
                ->whereIn('user_id', $userIds)
                ->whereIn('status', ['confirmed', 'completed', 'cancelled', 'no_show'])
                ->groupBy('user_id')
                ->get();

            foreach ($clientRows as $row) {
                $userId = (int) $row->user_id;
                if (! isset($stats[$userId])) {
                    $stats[$userId] = ['bookings' => 0, 'revenue' => 0.0];
                }
                $stats[$userId]['bookings'] += (int) $row->bookings;
                $stats[$userId]['revenue'] += (float) $row->revenue;
            }
        }

        return $stats;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $users
     * @return Collection<int, array<string, mixed>>
     */
    private function sortUsers(Collection $users, string $sort): Collection
    {
        return match ($sort) {
            'bookings' => $users->sortByDesc('bookings')->values(),
            'revenue' => $users->sortByDesc('revenue')->values(),
            'name' => $users->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            default => $users->sortByDesc(fn (array $user) => $user['join_date'])->values(),
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function userDetailPayload($userDetail): ?array
    {
        if (! $userDetail) {
            return null;
        }

        return [
            'user_name' => $userDetail->user_name,
            'avatar' => $userDetail->avatar,
            'mobile_number' => $userDetail->mobile_number,
            'studio_name' => $userDetail->studio_name,
            'studio_address' => $userDetail->studio_address,
            'street_name' => $userDetail->street_name,
            'street_number' => $userDetail->street_number,
            'city' => $userDetail->city,
            'state' => $userDetail->state,
            'postal_code' => $userDetail->postal_code,
            'country' => $userDetail->country,
            'google_maps_link' => $userDetail->google_maps_link,
            'workspace_type' => $userDetail->workspace_type,
            'currency' => $userDetail->currency,
            'timezone' => $userDetail->timezone,
            'date_time_format' => $userDetail->date_time_format,
            'minimum_deposit_amount' => $userDetail->minimum_deposit_amount,
            'minimum_deposit_type' => $userDetail->minimum_deposit_type,
            'cancellation_window' => $userDetail->cancellation_window,
            'reschedule_times' => $userDetail->reschedule_times,
            'scheduling_type' => $userDetail->scheduling_type,
            'payment_type' => $userDetail->payment_type,
            'payment_status' => $userDetail->payment_status,
            'require_consultation' => (bool) $userDetail->require_consultation,
            'session_type' => $userDetail->session_type,
            'session_duration_minutes' => $userDetail->session_duration_minutes,
            'consultation_timing' => $userDetail->consultation_timing,
            'google_calendar_connected' => ! empty($userDetail->google_calendar_token),
            'current_step' => $userDetail->current_step,
            'completed_steps' => $userDetail->completed_steps,
        ];
    }
}
