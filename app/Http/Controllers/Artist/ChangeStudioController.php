<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserStudio;
use App\Services\ArtistStudioJoinRequestNotifier;
use App\Services\StudioPayoutInviteNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChangeStudioController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $mode = $this->normalizeMode($request->query('mode', 'change'));

        $current = UserStudio::activeForUser((int) $user->id);
        $currentStudio = null;
        if ($current?->studio_id) {
            $currentStudio = Studio::query()->find($current->studio_id);
        } elseif (! empty($userDetail->resolvedStudioId())) {
            $currentStudio = Studio::query()->find($userDetail->resolvedStudioId());
        }

        $stripeLast4 = null;
        $stripeConnected = ! empty($userDetail->stripe_account_id);
        if ($stripeConnected) {
            $stripeLast4 = '••••';
        }

        $prefill = $this->resolvePrefill($request, (int) $user->id);

        return view('artist.settings.change-studio', [
            'user' => $user,
            'userDetail' => $userDetail,
            'mode' => $mode,
            'activeNav' => 'account',
            'currentLink' => $current,
            'currentStudio' => $currentStudio,
            'currentLabel' => $this->currentStudioLabel($userDetail, $current, $currentStudio),
            'currentPayoutLabel' => $this->currentPayoutLabel($userDetail, $current),
            'stripeConnected' => $stripeConnected,
            'stripeLast4' => $stripeLast4,
            'upcomingBookingsCount' => 0,
            'prefill' => $prefill,
            'relationshipOptions' => [
                'co_owner' => ['label' => 'Co-owner', 'hint' => 'Owns or partners in the studio'],
                'resident' => ['label' => 'Resident', 'hint' => 'Permanent spot with regular bookings'],
                'collective_member' => ['label' => 'Collective Member', 'hint' => 'Part of a collective studio model'],
                'apprentice' => ['label' => 'Apprentice', 'hint' => 'Learning and training under someone'],
                'other' => ['label' => 'Other (Contract Artist, Freelancer)', 'hint' => 'Contract or freelance arrangement'],
            ],
            'workspaceOptionsFound' => [
                'shop' => 'Tattoo Shop',
                'private' => 'Private Studio',
                'home' => 'Home Studio',
                'collective' => 'Collective',
            ],
            'workspaceOptionsOwn' => [
                'private' => 'Private Studio',
                'home' => 'Home Studio',
            ],
        ]);
    }

    /**
     * Prefill Change Studio from a past user_studios row and/or Bookpay studio_id.
     *
     * @return array<string, mixed>|null
     */
    private function resolvePrefill(Request $request, int $userId): ?array
    {
        $fromLinkId = (int) $request->query('from_link', 0);
        $studioId = (int) $request->query('studio_id', 0);

        $past = null;
        if ($fromLinkId > 0 && UserStudio::tableReady()) {
            $past = UserStudio::query()
                ->where('user_id', $userId)
                ->where('id', $fromLinkId)
                ->first();
        }

        if ($past) {
            $onBookpay = $past->connection_type === UserStudio::CONNECTION_ON_BOOKPAY
                && ! empty($past->studio_id);
            if ($onBookpay) {
                $studio = Studio::query()->find($past->studio_id);
                if ($studio) {
                    $prefill = $this->prefillFound($studio, $past->relationship, $past->workspace_type);
                    $prefill['from_link'] = $past->id;

                    return $prefill;
                }
            }

            // Own space: no relationship and not linked to a Bookpay studio.
            if (empty($past->relationship) && empty($past->studio_id)) {
                return [
                    'path' => 'own_space',
                    'from_link' => $past->id,
                    'workspace_type' => in_array($past->workspace_type, ['private', 'home'], true)
                        ? $past->workspace_type
                        : 'private',
                    'studio_name' => $past->studio_name,
                    'studio_address' => $past->studio_address,
                    'street_number' => $past->street_number,
                    'street_name' => $past->street_name,
                    'city' => $past->city,
                    'state' => $past->state,
                    'postal_code' => $past->postal_code,
                    'country' => $past->country,
                    'google_maps_link' => $past->google_maps_link,
                    'search_q' => $past->studio_name,
                ];
            }

            return [
                'path' => 'not_on_bookpay',
                'from_link' => $past->id,
                'relationship' => $past->relationship,
                'workspace_type' => $past->workspace_type ?: 'shop',
                'studio_name' => $past->studio_name,
                'studio_address' => $past->studio_address,
                'street_number' => $past->street_number,
                'street_name' => $past->street_name,
                'city' => $past->city,
                'state' => $past->state,
                'postal_code' => $past->postal_code,
                'country' => $past->country,
                'google_maps_link' => $past->google_maps_link,
                'payout_mode' => $past->payout ? 'split' : 'direct',
                'revenue_split' => $past->revenue_split !== null ? (int) $past->revenue_split : 60,
                'search_q' => $past->studio_name,
            ];
        }

        if ($studioId > 0) {
            $studio = Studio::query()->find($studioId);
            if ($studio) {
                return $this->prefillFound($studio, null, null);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function prefillFound(Studio $studio, ?string $relationship, ?string $workspaceType): array
    {
        $name = trim((string) $studio->name);
        $ini = strtoupper(mb_substr(preg_replace('/\s+/', '', $name) ?: 'ST', 0, 2));
        $street = trim(trim((string) ($studio->street_number ?? '')).' '.trim((string) ($studio->street_name ?? '')));
        $addrParts = array_values(array_filter([
            trim((string) ($studio->address ?? '')) !== ''
                ? trim((string) $studio->address)
                : ($street !== '' ? $street : null),
            trim((string) ($studio->city ?? '')),
            trim((string) ($studio->postal_code ?? '')),
            trim((string) ($studio->country ?? '')),
        ], fn ($p) => $p !== null && $p !== ''));

        return [
            'path' => 'found',
            'relationship' => $relationship && in_array($relationship, UserStudio::RELATIONSHIP_TYPES, true)
                ? $relationship
                : null,
            'workspace_type' => $workspaceType ?: $this->mapStudioType($studio->studio_type),
            'pick' => [
                'id' => $studio->id,
                'name' => $name,
                'ini' => $ini,
                'address' => implode(', ', $addrParts),
                'type' => $this->studioTypeLabel($studio->studio_type),
                'workspace_type' => $this->mapStudioType($studio->studio_type),
                'maps' => $studio->google_maps_link,
                'on_bookpay' => true,
            ],
            'search_q' => $name,
        ];
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', $request->input('q', '')));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $studios = Studio::query()
            ->whereNotNull('user_id')
            ->where('user_id', '>', 0)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhere('city', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
            })
            ->orderBy('name')
            ->limit(12)
            ->get([
                'id', 'name', 'address', 'street_name', 'street_number',
                'city', 'state', 'postal_code', 'country',
                'studio_type', 'google_maps_link', 'user_id',
            ]);

        $results = $studios->map(function (Studio $studio) {
            $name = trim((string) $studio->name);
            $ini = strtoupper(mb_substr(preg_replace('/\s+/', '', $name) ?: 'ST', 0, 2));
            $street = trim(trim((string) ($studio->street_number ?? '')).' '.trim((string) ($studio->street_name ?? '')));
            $addrParts = array_values(array_filter([
                trim((string) ($studio->address ?? '')) !== '' ? trim((string) $studio->address) : ($street !== '' ? $street : null),
                trim((string) ($studio->city ?? '')),
                trim((string) ($studio->postal_code ?? '')),
                trim((string) ($studio->country ?? '')),
            ], fn ($p) => $p !== null && $p !== ''));

            return [
                'id' => $studio->id,
                'name' => $name,
                'ini' => $ini,
                'address' => implode(', ', $addrParts),
                'type' => $this->studioTypeLabel($studio->studio_type),
                'workspace_type' => $this->mapStudioType($studio->studio_type),
                'maps' => $studio->google_maps_link ?: null,
                'on_bookpay' => true,
                'has_owner' => ! empty($studio->user_id),
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $mode = $this->normalizeMode($request->input('mode', 'change'));

        $validated = $request->validate([
            'path' => ['required', Rule::in(['found', 'not_on_bookpay', 'own_space', 'no_studio'])],
            'from_link' => ['nullable', 'integer'],
            'studio_id' => ['nullable', 'integer', 'exists:studios,id'],
            'studio_name' => ['nullable', 'string', 'max:255'],
            'studio_address' => ['nullable', 'string'],
            'street_name' => ['nullable', 'string', 'max:255'],
            'street_number' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],
            'google_maps_link' => ['nullable', 'string', 'max:500'],
            'workspace_type' => ['nullable', 'string', Rule::in(['private', 'shop', 'home', 'collective'])],
            'relationship' => ['nullable', 'string', Rule::in(UserStudio::RELATIONSHIP_TYPES)],
            'payout_mode' => ['nullable', Rule::in(['direct', 'split'])],
            'revenue_split' => ['nullable', 'integer', 'min:0', 'max:100'],
            'invite_studio_name' => ['nullable', 'string', 'max:255'],
            'invite_studio_email' => ['nullable', 'email', 'max:255'],
        ]);

        if (($validated['payout_mode'] ?? null) === 'split'
            && ! empty($validated['invite_studio_email'])
            && User::emailBlockedForStudioInvite((string) $validated['invite_studio_email'])) {
            throw ValidationException::withMessages([
                'invite_studio_email' => ['This email is already registered with a Bookpay account. Use a different studio email.'],
            ]);
        }

        $path = $validated['path'];
        $message = 'Studio updated.';

        if ($path === 'no_studio') {
            if ($mode === 'update') {
                // Update flow edits one workplace — don't wipe all studios.
                $message = 'Studio updated.';
            } else {
                $this->leaveStudio($userDetail, $mode);
                $message = $mode === 'add' ? 'No studio added.' : 'You left your studio.';
            }
        } elseif ($path === 'found') {
            $this->applyFoundPath($user, $userDetail, $validated, $mode);
            $message = $mode === 'update'
                ? 'Studio updated.'
                : ($mode === 'add' ? 'Join request sent.' : 'Join request sent to your new studio.');
        } elseif ($path === 'own_space') {
            $this->applyOwnSpacePath($user, $userDetail, $validated, $mode);
            $message = $mode === 'update' ? 'Studio updated.' : 'Own space added.';
        } else {
            $invited = $this->applyNotOnBookpayPath($user, $userDetail, $validated, $mode);
            $message = $mode === 'update'
                ? ($invited ? 'Studio updated and invitation sent.' : 'Studio updated.')
                : ($invited ? 'Studio added and invitation sent.' : 'Studio added.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('settings.studio'),
            ]);
        }

        return redirect()->route('settings.studio')->with('status', 'studio-changed')->with('success', $message);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyFoundPath($user, UserDetail $userDetail, array $validated, string $mode): void
    {
        $studioId = (int) ($validated['studio_id'] ?? 0);
        $studio = Studio::query()->with('user')->findOrFail($studioId);

        // Bookpay join: keep current workplace active; create/reuse a pending join row.
        if (! UserStudio::tableReady()) {
            return;
        }

        $payoutMode = $validated['payout_mode'] ?? 'direct';
        $split = isset($validated['revenue_split']) ? (int) $validated['revenue_split'] : null;
        $relationship = $validated['relationship'] ?? null;
        $workspaceType = $validated['workspace_type'] ?? $this->mapStudioType($studio->studio_type);
        $fromLinkId = ! empty($validated['from_link']) ? (int) $validated['from_link'] : null;

        // Update an existing active workplace in place — no new row / no join email.
        if ($mode === 'update') {
            $row = $this->resolveUpdateLink((int) $user->id, $fromLinkId);
            if ($row) {
                $row->fill([
                    'studio_id' => $studio->id,
                    'connection_type' => ! empty($studio->user_id)
                        ? UserStudio::CONNECTION_ON_BOOKPAY
                        : UserStudio::CONNECTION_NOT_CONNECTED,
                    'studio_name' => $studio->name,
                    'studio_address' => $studio->address,
                    'street_name' => $studio->street_name,
                    'street_number' => $studio->street_number,
                    'city' => $studio->city,
                    'state' => $studio->state,
                    'postal_code' => $studio->postal_code,
                    'country' => $studio->country,
                    'google_maps_link' => $studio->google_maps_link,
                    'workspace_type' => $workspaceType,
                    'relationship' => $relationship && in_array($relationship, UserStudio::RELATIONSHIP_TYPES, true)
                        ? $relationship
                        : null,
                    'payout' => $payoutMode === 'split',
                    'revenue_split' => $payoutMode === 'split' ? ($split ?? 60) : $split,
                ]);
                $row->save();

                $this->mirrorLegacyStudioId(
                    $userDetail,
                    (int) $studio->id,
                    $row->relationship,
                    $row->revenue_split !== null ? (int) $row->revenue_split : null
                );
                $userDetail->save();

                return;
            }
        }

        // Already active at this studio — nothing to join.
        $alreadyActive = UserStudio::query()
            ->where('user_id', (int) $user->id)
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->exists();
        if ($alreadyActive) {
            return;
        }

        // Reuse an inactive/pending row for this studio when possible; keep other pending joins.
        $reusable = UserStudio::findReusableForUser((int) $user->id, $fromLinkId, (int) $studio->id);

        $row = $reusable ?? new UserStudio([
            'user_id' => (int) $user->id,
        ]);

        $row->fill([
            'status' => UserStudio::STATUS_JOIN_PENDING,
            'studio_id' => $studio->id,
            'connection_type' => ! empty($studio->user_id)
                ? UserStudio::CONNECTION_ON_BOOKPAY
                : UserStudio::CONNECTION_NOT_CONNECTED,
            'studio_name' => $studio->name,
            'studio_address' => $studio->address,
            'street_name' => $studio->street_name,
            'street_number' => $studio->street_number,
            'city' => $studio->city,
            'state' => $studio->state,
            'postal_code' => $studio->postal_code,
            'country' => $studio->country,
            'google_maps_link' => $studio->google_maps_link,
            'workspace_type' => $workspaceType,
            'relationship' => $relationship && in_array($relationship, UserStudio::RELATIONSHIP_TYPES, true)
                ? $relationship
                : null,
            'payout' => $payoutMode === 'split',
            'revenue_split' => $payoutMode === 'split' ? ($split ?? 60) : $split,
        ]);
        $row->save();

        // Drive Account Studio hub: add → multi list (09c6), change → banner (09c4).
        session(['studio_join_mode' => $mode === 'add' ? 'add' : 'change']);

        app(ArtistStudioJoinRequestNotifier::class)->send($user, $row, $studio, false);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyOwnSpacePath($user, UserDetail $userDetail, array $validated, string $mode): void
    {
        $fromLinkId = ! empty($validated['from_link']) ? (int) $validated['from_link'] : null;
        $reusable = $mode === 'update'
            ? $this->resolveUpdateLink((int) $user->id, $fromLinkId)
            : UserStudio::findReusableForUser((int) $user->id, $fromLinkId, null);

        $data = [
            'studio_name' => $validated['studio_name'] ?? null,
            'studio_address' => $validated['studio_address'] ?? null,
            'street_name' => $validated['street_name'] ?? null,
            'street_number' => $validated['street_number'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? null,
            'google_maps_link' => $validated['google_maps_link'] ?? null,
            'workspace_type' => $validated['workspace_type'] ?? 'private',
        ];

        if ($reusable) {
            $row = UserStudio::syncWorkplaceOnto($reusable, $data, true);
        } elseif ($mode === 'update') {
            // Update mode must never create a second workplace.
            return;
        } else {
            // Artists can keep multiple active workplaces — insert a new row when no past link.
            $row = UserStudio::syncWorkplace((int) $user->id, $data, true, true);
        }

        if ($row) {
            $row->studio_id = null;
            $row->payout = false;
            $row->connection_type = UserStudio::CONNECTION_NOT_ON_BOOKPAY;
            $row->status = UserStudio::STATUS_ACTIVE;
            $row->save();
        }

        // Dual-write regional bits only; do not clear other active studios.
        if (Schema::hasColumn($userDetail->getTable(), 'city') && ! empty($validated['city'])) {
            $userDetail->city = $validated['city'];
        }
        if (Schema::hasColumn($userDetail->getTable(), 'country') && ! empty($validated['country'])) {
            $userDetail->country = $validated['country'];
        }
        if ($userDetail->isDirty()) {
            $userDetail->save();
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return bool True when a studio payout invitation email was sent.
     */
    private function applyNotOnBookpayPath($user, UserDetail $userDetail, array $validated, string $mode): bool
    {
        $workplace = [
            'studio_name' => $validated['studio_name'] ?? null,
            'studio_address' => $validated['studio_address'] ?? null,
            'street_name' => $validated['street_name'] ?? null,
            'street_number' => $validated['street_number'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? null,
            'google_maps_link' => $validated['google_maps_link'] ?? null,
            'workspace_type' => $validated['workspace_type'] ?? 'shop',
            'relationship' => $validated['relationship'] ?? null,
        ];

        $payoutMode = $validated['payout_mode'] ?? 'direct';
        $split = isset($validated['revenue_split']) ? (int) $validated['revenue_split'] : null;
        $fromLinkId = ! empty($validated['from_link']) ? (int) $validated['from_link'] : null;
        $reusable = $mode === 'update'
            ? $this->resolveUpdateLink((int) $user->id, $fromLinkId)
            : UserStudio::findReusableForUser((int) $user->id, $fromLinkId, null);
        $invited = false;

        if ($payoutMode === 'split' && ! empty($validated['invite_studio_email'])) {
            $studioEmail = strtolower(trim((string) $validated['invite_studio_email']));
            $studio = Studio::query()->firstOrCreate(
                ['email' => $studioEmail],
                ['name' => $validated['invite_studio_name'] ?: ($validated['studio_name'] ?? 'Studio')]
            );
            if (empty($studio->email)) {
                $studio->email = $studioEmail;
                $studio->save();
            }

            // Prefer updating the past/active row for this studio / from_link.
            if ($mode !== 'update') {
                $reusable = UserStudio::findReusableForUser((int) $user->id, $fromLinkId, (int) $studio->id) ?? $reusable;
            }

            if ($reusable) {
                $row = $reusable;
                static::applyStudioPayoutFields($row, $studio, [
                    'studio_revenue_artist_percent' => $split ?? 60,
                    'studio_relationship_type' => $validated['relationship'] ?? null,
                ]);
            } elseif ($mode === 'update') {
                return false;
            } else {
                $row = UserStudio::syncStudioPayout((int) $user->id, $studio, [
                    'studio_revenue_artist_percent' => $split ?? 60,
                    'studio_relationship_type' => $validated['relationship'] ?? null,
                ], true);
            }

            if ($row) {
                $row->studio_name = $validated['studio_name'] ?? $row->studio_name;
                $row->studio_address = $validated['studio_address'] ?? $row->studio_address;
                $row->street_name = $validated['street_name'] ?? $row->street_name;
                $row->street_number = $validated['street_number'] ?? $row->street_number;
                $row->city = $validated['city'] ?? $row->city;
                $row->state = $validated['state'] ?? $row->state;
                $row->postal_code = $validated['postal_code'] ?? $row->postal_code;
                $row->country = $validated['country'] ?? $row->country;
                $row->google_maps_link = $validated['google_maps_link'] ?? $row->google_maps_link;
                $row->workspace_type = $validated['workspace_type'] ?? $row->workspace_type;
                $row->connection_type = UserStudio::CONNECTION_NOT_CONNECTED;
                // Wait for studio to accept the invite before this workplace goes live.
                $row->status = UserStudio::STATUS_JOIN_PENDING;
                $row->save();

                $invited = app(StudioPayoutInviteNotifier::class)->send($user, $userDetail, $studio, $row);
            }
        } else {
            if ($reusable) {
                $row = UserStudio::syncWorkplaceOnto($reusable, $workplace, false);
            } elseif ($mode === 'update') {
                return false;
            } else {
                $row = UserStudio::syncWorkplace((int) $user->id, $workplace, false, true);
            }
            if ($row) {
                $row->studio_id = null;
                $row->payout = false;
                $row->connection_type = UserStudio::CONNECTION_NOT_ON_BOOKPAY;
                $row->status = UserStudio::STATUS_ACTIVE;
                $row->save();
            }
        }

        if (Schema::hasColumn($userDetail->getTable(), 'city') && ! empty($validated['city'])) {
            $userDetail->city = $validated['city'];
        }
        if (Schema::hasColumn($userDetail->getTable(), 'country') && ! empty($validated['country'])) {
            $userDetail->country = $validated['country'];
        }
        if ($userDetail->isDirty()) {
            $userDetail->save();
        }

        return $invited;
    }

    /**
     * @param  array{studio_revenue_artist_percent?: mixed, studio_relationship_type?: mixed, revenue_split?: mixed, relationship?: mixed}  $validated
     */
    private static function applyStudioPayoutFields(UserStudio $row, Studio $studio, array $validated): void
    {
        UserStudio::query()
            ->where('user_id', $row->user_id)
            ->where('payout', true)
            ->where('id', '!=', $row->id)
            ->update(['payout' => false]);

        $row->studio_id = $studio->id;
        $row->payout = true;
        $row->status = UserStudio::STATUS_ACTIVE;
        $row->connection_type = ! empty($studio->user_id)
            ? UserStudio::CONNECTION_ON_BOOKPAY
            : UserStudio::CONNECTION_NOT_CONNECTED;

        if (array_key_exists('revenue_split', $validated) && $validated['revenue_split'] !== null && $validated['revenue_split'] !== '') {
            $row->revenue_split = (int) $validated['revenue_split'];
        } elseif (array_key_exists('studio_revenue_artist_percent', $validated)
            && $validated['studio_revenue_artist_percent'] !== null
            && $validated['studio_revenue_artist_percent'] !== '') {
            $row->revenue_split = (int) $validated['studio_revenue_artist_percent'];
        }

        $relationship = $validated['relationship'] ?? $validated['studio_relationship_type'] ?? null;
        if (! empty($relationship) && in_array($relationship, UserStudio::RELATIONSHIP_TYPES, true)) {
            $row->relationship = $relationship;
        }
    }

    private function leaveStudio(UserDetail $userDetail, string $mode): void
    {
        if ($mode === 'add') {
            return;
        }

        UserStudio::clearStudioPayout((int) $userDetail->user_id);
        if (UserStudio::tableReady()) {
            UserStudio::query()
                ->where('user_id', $userDetail->user_id)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->update([
                    'status' => UserStudio::STATUS_INACTIVE,
                    'payout' => false,
                    'studio_id' => null,
                    'connection_type' => UserStudio::CONNECTION_NOT_CONNECTED,
                    'updated_at' => now(),
                ]);
        }

        $userDetail->payment_type = 'artist_account';
        $this->mirrorLegacyStudioId($userDetail, null, null, null);
        $userDetail->save();
    }

    private function mirrorLegacyStudioId(UserDetail $userDetail, ?int $studioId, ?string $relationship, ?int $split): void
    {
        $table = $userDetail->getTable();
        if (Schema::hasColumn($table, 'studio_id')) {
            $userDetail->studio_id = $studioId;
        }
        if (Schema::hasColumn($table, 'studio_relationship_type') && $relationship) {
            $userDetail->studio_relationship_type = $relationship;
        }
        if (Schema::hasColumn($table, 'studio_revenue_artist_percent') && $split !== null) {
            $userDetail->studio_revenue_artist_percent = $split;
        }
    }

    private function currentStudioLabel(UserDetail $userDetail, ?UserStudio $current, ?Studio $studio): string
    {
        if ($studio?->name) {
            return (string) $studio->name;
        }
        if ($current?->studio_name) {
            return (string) $current->studio_name;
        }
        $name = $userDetail->resolvedStudioName();

        return $name !== '' ? $name : 'No studio';
    }

    private function currentPayoutLabel(UserDetail $userDetail, ?UserStudio $current): string
    {
        $parts = [];
        $rel = $current?->relationship ?? $userDetail->studio_relationship_type ?? null;
        $labels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective Member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];
        if ($rel && isset($labels[$rel])) {
            $parts[] = $labels[$rel];
        }

        if ($current?->payout || ($userDetail->payment_type ?? '') === 'studio_account') {
            $split = $current?->revenue_split ?? $userDetail->resolvedStudioRevenueArtistPercent(60);
            $parts[] = 'Revenue split';
            $parts[] = 'You '.$split.'%';
            $parts[] = 'Studio '.(100 - $split).'%';
        } else {
            $parts[] = 'Direct payment';
            $parts[] = 'You 100%';
        }

        return implode(' · ', $parts);
    }

    private function mapStudioType(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return match (true) {
            in_array($type, ['private', 'private studio'], true) => 'private',
            in_array($type, ['home', 'home studio'], true) => 'home',
            in_array($type, ['collective'], true) => 'collective',
            default => 'shop',
        };
    }

    private function studioTypeLabel(?string $type): string
    {
        return match ($this->mapStudioType($type)) {
            'private' => 'Private Studio',
            'home' => 'Home Studio',
            'collective' => 'Collective',
            default => 'Tattoo Shop',
        };
    }

    private function normalizeMode(mixed $mode): string
    {
        $mode = strtolower(trim((string) $mode));

        return match ($mode) {
            'add' => 'add',
            'update' => 'update',
            default => 'change',
        };
    }

    private function resolveUpdateLink(int $userId, ?int $fromLinkId): ?UserStudio
    {
        if (! $fromLinkId || ! UserStudio::tableReady()) {
            return null;
        }

        return UserStudio::query()
            ->where('user_id', $userId)
            ->where('id', $fromLinkId)
            ->whereIn('status', [
                UserStudio::STATUS_ACTIVE,
                UserStudio::STATUS_SPLIT_PROPOSED,
            ])
            ->first();
    }
}
