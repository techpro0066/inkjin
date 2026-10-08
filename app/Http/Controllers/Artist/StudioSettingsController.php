<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Models\UserDetail;
use App\Models\UserStudio;
use App\Services\ArtistStudioJoinRequestNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudioSettingsController extends Controller
{
    public const RELATIONSHIP_OPTIONS = [
        'co_owner' => ['label' => 'Co-owner', 'hint' => 'Owns or partners in the studio'],
        'resident' => ['label' => 'Resident', 'hint' => 'Permanent spot with regular bookings'],
        'collective_member' => ['label' => 'Collective Member', 'hint' => 'Part of a collective studio model'],
        'apprentice' => ['label' => 'Apprentice', 'hint' => 'Learning and training under someone'],
        'other' => ['label' => 'Other (Contract Artist, Freelancer)', 'hint' => 'Contract or freelance arrangement'],
    ];

    public const WORKSPACE_OPTIONS = [
        'private' => [
            'label' => 'Private Studio',
            'icon' => 'home',
            'hint' => 'A personal workspace. Clients visit by appointment only',
        ],
        'shop' => [
            'label' => 'Tattoo Shop',
            'icon' => 'storefront',
            'hint' => 'A shared shop with walk-ins and appointments',
        ],
        'home' => [
            'label' => 'Home Studio',
            'icon' => 'cottage',
            'hint' => 'Working from home. Address shared only after booking',
        ],
        'collective' => [
            'label' => 'Collective',
            'icon' => 'groups',
            'hint' => 'A shared space run together by a group of artists',
        ],
    ];

    public const OWN_SPACE_WORKSPACE_OPTIONS = [
        'private' => [
            'label' => 'Private Studio',
            'icon' => 'home',
            'hint' => 'A personal workspace. Clients visit by appointment only',
        ],
        'home' => [
            'label' => 'Home Studio',
            'icon' => 'cottage',
            'hint' => 'Working from home. Address shared only after booking',
        ],
    ];

    public function show(Request $request): View
    {
        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $userId = (int) $user->id;

        $actives = UserStudio::activeLinksForUser($userId);
        $pendings = UserStudio::pendingJoinsForUser($userId);
        $pending = $pendings->sortByDesc('id')->first();
        $splitProposed = UserStudio::splitProposedForUser($userId);
        $past = UserStudio::pastLinksForUser($userId);

        $viewState = $this->resolveViewState($actives, $pending, $splitProposed);
        // Newest active is still the "primary" for single-studio edit views.
        $primary = $actives->sortByDesc('id')->first();
        $multiCards = $this->presentMultiCards($actives, $pendings, $viewState);

        // Always use the multi layout when more than one workplace is in play.
        if (count($multiCards) > 1) {
            $viewState = 'multiple';
        }

        return view('artist.settings.studio', [
            'user' => $user,
            'userDetail' => $userDetail,
            'activeNav' => 'account',
            'viewState' => $viewState,
            'actives' => $actives,
            'primary' => $primary,
            'pending' => $pending,
            'splitProposed' => $splitProposed,
            'past' => $past,
            'relationshipOptions' => self::RELATIONSHIP_OPTIONS,
            'workspaceOptions' => self::WORKSPACE_OPTIONS,
            'ownSpaceWorkspaceOptions' => self::OWN_SPACE_WORKSPACE_OPTIONS,
            'primaryCard' => $primary ? $this->presentLink($primary) : null,
            'pendingCard' => $pending ? $this->presentLink($pending) : null,
            'splitCard' => $splitProposed ? $this->presentLink($splitProposed) : null,
            'multiCards' => $multiCards,
            'pastCards' => $past->map(fn (UserStudio $link) => $this->presentPastLink($link))->values(),
            'emptyMeta' => $this->emptyStateMeta($past),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'studio_name' => ['required', 'string', 'max:255'],
            'studio_address' => ['required', 'string'],
            'street_name' => ['required', 'string', 'max:255'],
            'street_number' => ['required', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:255'],
            'google_maps_link' => ['nullable', 'string', 'max:500'],
            'workspace_type' => ['required', 'string', Rule::in(['private', 'shop', 'home', 'collective'])],
            'relationship' => ['nullable', 'string', Rule::in(UserStudio::RELATIONSHIP_TYPES)],
            'studio_relationship_type' => ['nullable', 'string', Rule::in(UserStudio::RELATIONSHIP_TYPES)],
            'form_mode' => ['nullable', Rule::in(['not_on_bookpay', 'own_space'])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'workspace_type.required' => 'Please select a workspace type.',
            'workspace_type.in' => 'Please select a valid workspace type.',
        ]);

        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $mode = $validated['form_mode'] ?? null;
        $relationship = $validated['relationship']
            ?? $validated['studio_relationship_type']
            ?? null;

        if ($mode === 'own_space') {
            $relationship = null;
            if (! in_array($validated['workspace_type'], ['private', 'home'], true)) {
                return redirect()->back()
                    ->withErrors(['workspace_type' => 'Please select a valid space type.'])
                    ->withInput();
            }
        } elseif ($mode === 'not_on_bookpay' && empty($relationship)) {
            return redirect()->back()
                ->withErrors(['relationship' => 'Please select your relationship with this studio.'])
                ->withInput();
        }

        $workplace = [
            'studio_name' => $validated['studio_name'],
            'studio_address' => $validated['studio_address'],
            'street_name' => $validated['street_name'],
            'street_number' => $validated['street_number'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'country' => $validated['country'],
            'google_maps_link' => $validated['google_maps_link'] ?? null,
            'workspace_type' => $validated['workspace_type'],
            'relationship' => $relationship,
        ];

        $row = UserStudio::syncWorkplace(
            (int) $user->id,
            $workplace,
            $mode === 'own_space'
        );

        if ($row) {
            $row->studio_id = null;
            $row->payout = false;
            $row->connection_type = UserStudio::CONNECTION_NOT_ON_BOOKPAY;
            if ($mode === 'own_space') {
                $row->relationship = null;
            }
            $row->save();
        }

        $this->dualWriteUserDetail($userDetail, $workplace, $relationship, $mode === 'own_space');

        return redirect()->route('settings.studio')
            ->with('status', 'studio-changed')
            ->with('success', 'Studio information updated successfully!');
    }

    public function cancelJoinRequest(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'user_studio_id' => ['nullable', 'integer'],
        ]);

        $pending = $this->resolvePendingJoin($user, $validated['user_studio_id'] ?? null);

        if ($pending) {
            $pending->status = UserStudio::STATUS_INACTIVE;
            $pending->payout = false;
            $pending->save();
        }

        session()->forget('studio_join_mode');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Join request cancelled.',
                'user_studio_id' => $pending?->id,
            ]);
        }

        return redirect()->route('settings.studio')
            ->with('status', 'studio-changed')
            ->with('success', 'Join request cancelled.');
    }

    public function leaveStudio(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'user_studio_id' => ['required', 'integer'],
        ]);

        $link = UserStudio::query()
            ->where('id', (int) $validated['user_studio_id'])
            ->where('user_id', (int) $user->id)
            ->whereIn('status', [
                UserStudio::STATUS_ACTIVE,
                UserStudio::STATUS_SPLIT_PROPOSED,
            ])
            ->first();

        if (! $link) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio link not found.',
                ], 404);
            }

            return redirect()->route('settings.studio')
                ->with('error', 'Studio link not found.');
        }

        $wasPayout = (bool) $link->payout;
        $link->status = UserStudio::STATUS_INACTIVE;
        $link->payout = false;
        $link->save();

        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $remaining = UserStudio::query()
            ->where('user_id', (int) $user->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->orderByDesc('id')
            ->first();

        if ($remaining) {
            $this->mirrorLegacyStudioId(
                $userDetail,
                $remaining->studio_id ? (int) $remaining->studio_id : null,
                $remaining->relationship,
                $remaining->revenue_split !== null ? (int) $remaining->revenue_split : null
            );
            $userDetail->save();
        } else {
            if ($wasPayout || ($userDetail->payment_type ?? null) === 'studio_account') {
                $userDetail->payment_type = 'artist_account';
            }
            $this->mirrorLegacyStudioId($userDetail, null, null, null);
            $userDetail->save();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'You left the studio.',
                'user_studio_id' => $link->id,
                'reload' => true,
            ]);
        }

        return redirect()->route('settings.studio')
            ->with('status', 'studio-changed')
            ->with('success', 'You left the studio.');
    }

    public function resendJoinRequest(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'user_studio_id' => ['nullable', 'integer'],
        ]);

        $pending = $this->resolvePendingJoin($user, $validated['user_studio_id'] ?? null);

        if (! $pending || ! $pending->studio_id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending join request found.',
                ], 404);
            }

            return redirect()->route('settings.studio')
                ->with('error', 'No pending join request found.');
        }

        $studio = Studio::query()->with('user')->find($pending->studio_id);
        if (! $studio) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio not found for this join request.',
                ], 404);
            }

            return redirect()->route('settings.studio')
                ->with('error', 'Studio not found for this join request.');
        }

        $sent = app(ArtistStudioJoinRequestNotifier::class)->send($user, $pending, $studio, true);

        if (! $sent) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not resend the email. The studio may not have a contact email on file.',
                ], 422);
            }

            return redirect()->route('settings.studio')
                ->with('error', 'Could not resend the email. The studio may not have a contact email on file.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Join request email resent.',
            ]);
        }

        return redirect()->route('settings.studio')
            ->with('status', 'studio-changed')
            ->with('success', 'Join request email resent.');
    }

    private function resolvePendingJoin($user, mixed $userStudioId): ?UserStudio
    {
        $pending = null;
        if (! empty($userStudioId)) {
            $pending = UserStudio::query()
                ->where('id', (int) $userStudioId)
                ->where('user_id', (int) $user->id)
                ->where('status', UserStudio::STATUS_JOIN_PENDING)
                ->first();
        }

        return $pending ?: UserStudio::pendingJoinForUser((int) $user->id);
    }

    public function acceptSplit(Request $request): RedirectResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $proposed = UserStudio::splitProposedForUser($userId);
        if (! $proposed) {
            return redirect()->route('settings.studio');
        }

        $mode = $request->input('mode', 'change') === 'add' ? 'add' : 'change';

        // Keep existing active studios — joining another studio adds another active workplace.
        $proposed->status = UserStudio::STATUS_ACTIVE;
        if (Schema::hasColumn($proposed->getTable(), 'proposed_revenue_split')
            && $proposed->proposed_revenue_split !== null) {
            $proposed->revenue_split = (int) $proposed->proposed_revenue_split;
            $proposed->proposed_revenue_split = null;
        }
        $proposed->connection_type = UserStudio::CONNECTION_ON_BOOKPAY;
        $proposed->save();

        session()->forget('studio_join_mode');

        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        if ($mode === 'change') {
            $this->mirrorLegacyStudioId(
                $userDetail,
                $proposed->studio_id ? (int) $proposed->studio_id : null,
                $proposed->relationship,
                $proposed->revenue_split !== null ? (int) $proposed->revenue_split : null
            );
            if ($proposed->payout) {
                $userDetail->payment_type = 'studio_account';
            }
            $userDetail->save();
        }

        return redirect()->route('settings.studio')
            ->with('status', 'studio-changed')
            ->with('success', 'You joined the studio.');
    }

    public function declineSplit(Request $request): RedirectResponse
    {
        $user = $request->user();
        $proposed = UserStudio::splitProposedForUser((int) $user->id);
        if ($proposed) {
            $proposed->status = UserStudio::STATUS_INACTIVE;
            if (Schema::hasColumn($proposed->getTable(), 'proposed_revenue_split')) {
                $proposed->proposed_revenue_split = null;
            }
            $proposed->payout = false;
            $proposed->save();
        }

        session()->forget('studio_join_mode');

        return redirect()->route('settings.studio')
            ->with('status', 'studio-changed')
            ->with('success', 'Split proposal declined.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UserStudio>  $actives
     */
    private function resolveViewState($actives, ?UserStudio $pending, ?UserStudio $splitProposed): string
    {
        $activeCount = $actives->count();

        if ($splitProposed) {
            return 'split_proposed';
        }

        if ($pending && $activeCount >= 1) {
            // Multiple workplaces (09c6): show accepted + pending join together.
            return 'multiple';
        }

        if ($activeCount === 0) {
            if ($pending) {
                return 'multiple';
            }

            return 'none';
        }

        if ($activeCount >= 2) {
            return 'multiple';
        }

        /** @var UserStudio $link */
        $link = $actives->first();
        if ($this->isOnBookpay($link)) {
            return 'on_bookpay';
        }

        if (! empty($link->relationship)) {
            return 'not_on_bookpay';
        }

        return 'own_space';
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UserStudio>  $actives
     * @return array<int, array<string, mixed>>
     */
    /**
     * @param  \Illuminate\Support\Collection<int, UserStudio>|\Illuminate\Database\Eloquent\Collection<int, UserStudio>  $actives
     * @param  \Illuminate\Support\Collection<int, UserStudio>|\Illuminate\Database\Eloquent\Collection<int, UserStudio>|UserStudio|null  $pendings
     */
    private function presentMultiCards($actives, $pendings = null, string $viewState = ''): array
    {
        $cards = [];
        // Keep previous studios first (ascending id), matching 09c6.
        foreach ($actives->sortBy('id')->values() as $link) {
            $card = $this->presentLink($link);
            $card['dashed'] = false;
            $card['status_pill'] = ['class' => 'g', 'label' => 'Accepted'];
            $card['update_url'] = route('settings.studio.change', array_filter([
                'mode' => 'update',
                'from_link' => $card['id'] ?? null,
                'studio_id' => ! empty($card['on_bookpay']) && ! empty($card['studio_id'])
                    ? $card['studio_id']
                    : null,
            ]));
            $cards[] = $card;
        }

        $pendingLinks = collect();
        if ($pendings instanceof UserStudio) {
            $pendingLinks = collect([$pendings]);
        } elseif ($pendings) {
            $pendingLinks = collect($pendings);
        }

        foreach ($pendingLinks->sortBy('id')->values() as $pending) {
            $already = collect($cards)->contains(fn ($c) => (int) ($c['id'] ?? 0) === (int) $pending->id);
            if ($already) {
                continue;
            }
            $card = $this->presentLink($pending);
            $card['dashed'] = true;
            $card['status_pill'] = ['class' => 'a', 'label' => 'Join request sent'];
            if ($pending->payout && $pending->revenue_split !== null) {
                $card['multi_subtitle'] = $this->pendingMultiSubtitle($card);
            }
            $cards[] = $card;
        }

        return $cards;
    }

    /**
     * @param  array<string, mixed>  $card
     */
    private function pendingMultiSubtitle(array $card): string
    {
        $base = (string) ($card['multi_subtitle'] ?? '');
        if ($base === '') {
            return 'Join request sent';
        }
        if (str_contains($base, 'once they accept')) {
            return $base;
        }

        return $base.' once they accept';
    }

    /**
     * @return array<string, mixed>
     */
    private function presentLink(UserStudio $link): array
    {
        $studio = $link->studio;
        $name = trim((string) ($studio?->name ?: $link->studio_name ?: 'Studio'));
        $onBookpay = $this->isOnBookpay($link);
        $workspace = $this->workspaceMeta($link->workspace_type, $studio?->studio_type);
        $relationship = $link->relationship;
        $relMeta = $relationship && isset(self::RELATIONSHIP_OPTIONS[$relationship])
            ? self::RELATIONSHIP_OPTIONS[$relationship]
            : null;
        $address = $this->formatAddress($link, $studio);
        $maps = $this->mapsUrl($link, $studio, $address);
        $split = $link->revenue_split !== null ? (int) $link->revenue_split : null;
        $proposedSplit = Schema::hasColumn($link->getTable(), 'proposed_revenue_split')
            && $link->proposed_revenue_split !== null
            ? (int) $link->proposed_revenue_split
            : null;
        $managedBy = $studio?->name ?: $name;

        return [
            'id' => $link->id,
            'studio_id' => $link->studio_id,
            'name' => $name,
            'ini' => $this->initials($name),
            'on_bookpay' => $onBookpay,
            'bp_pill' => $onBookpay
                ? ['class' => 'g', 'label' => 'On Bookpay']
                : ['class' => 'k', 'label' => 'Not on Bookpay'],
            'workspace_type' => $workspace['key'],
            'workspace_label' => $workspace['label'],
            'relationship' => $relationship,
            'relationship_label' => $relMeta['label'] ?? null,
            'relationship_hint' => $relMeta['hint'] ?? null,
            'address' => $address,
            'maps_url' => $maps,
            'managed_by' => $managedBy,
            'payout' => (bool) $link->payout,
            'revenue_split' => $split,
            'proposed_revenue_split' => $proposedSplit,
            'studio_name' => $link->studio_name,
            'studio_address' => $link->studio_address,
            'street_name' => $link->street_name,
            'street_number' => $link->street_number,
            'city' => $link->city,
            'state' => $link->state,
            'postal_code' => $link->postal_code,
            'country' => $link->country,
            'google_maps_link' => $link->google_maps_link,
            'confirmed_at' => optional($link->updated_at)->format('d/m/Y'),
            'created_at' => optional($link->created_at)->format('d/m/Y'),
            'created_at_human' => optional($link->created_at)->format('M Y'),
            'updated_at_human' => optional($link->updated_at)->format('M Y'),
            'status' => (int) $link->status,
            'pay_summary' => $this->paySummary($link),
            'multi_subtitle' => $this->multiSubtitle($link, $workspace['label'], $address, $split),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPastLink(UserStudio $link): array
    {
        $card = $this->presentLink($link);
        $from = optional($link->created_at)->format('M Y') ?: '';
        $to = optional($link->updated_at)->format('M Y') ?: '';
        $range = trim($from.($from && $to ? ' – '.$to : ''));
        $bits = array_values(array_filter([
            $range !== '' ? $range : null,
            $card['pay_summary'],
            $card['address'] !== '' ? $card['address'] : null,
        ]));
        $card['past_line'] = implode(' · ', $bits);
        $card['change_url'] = route('settings.studio.change', array_filter([
            'from_link' => $card['id'] ?? null,
            'studio_id' => $card['on_bookpay'] && $card['studio_id'] ? $card['studio_id'] : null,
        ]));

        return $card;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UserStudio>  $past
     * @return array{left_name: ?string, left_on: ?string, upcoming: int}
     */
    private function emptyStateMeta($past): array
    {
        $last = $past->first();
        $name = $last ? trim((string) ($last->studio?->name ?: $last->studio_name)) : null;

        return [
            'left_name' => $name !== '' ? $name : null,
            'left_on' => $last?->updated_at?->format('d/m/Y'),
            'upcoming' => 0,
        ];
    }

    private function isOnBookpay(UserStudio $link): bool
    {
        if ($link->connection_type === UserStudio::CONNECTION_ON_BOOKPAY) {
            return true;
        }

        if (! empty($link->studio_id)) {
            $studio = $link->studio ?: Studio::query()->find($link->studio_id);

            return ! empty($studio?->user_id);
        }

        return false;
    }

    /**
     * @return array{key: string, label: string}
     */
    private function workspaceMeta(?string $workspaceType, ?string $studioType = null): array
    {
        $key = strtolower(trim((string) $workspaceType));
        if ($key === '' && $studioType) {
            $type = strtolower(trim((string) $studioType));
            $key = match (true) {
                in_array($type, ['private', 'private studio'], true) => 'private',
                in_array($type, ['home', 'home studio'], true) => 'home',
                in_array($type, ['collective'], true) => 'collective',
                default => 'shop',
            };
        }
        if ($key === '') {
            $key = 'shop';
        }

        return [
            'key' => $key,
            'label' => self::WORKSPACE_OPTIONS[$key]['label'] ?? 'Tattoo Shop',
        ];
    }

    private function formatAddress(UserStudio $link, ?Studio $studio): string
    {
        if ($studio) {
            $parts = array_values(array_filter([
                trim((string) ($studio->address ?? '')),
                trim((string) ($studio->city ?? '')),
                trim((string) ($studio->postal_code ?? '')),
                trim((string) ($studio->country ?? '')),
            ], fn ($p) => $p !== ''));
            if ($parts) {
                // Prefer composed street when address already has full line.
                if (! empty($studio->address)) {
                    $tail = array_values(array_filter([
                        trim((string) ($studio->city ?? '')),
                        trim((string) ($studio->postal_code ?? '')),
                        trim((string) ($studio->country ?? '')),
                    ], fn ($p) => $p !== ''));
                    $addr = trim((string) $studio->address);
                    foreach ($tail as $bit) {
                        if ($bit !== '' && stripos($addr, $bit) === false) {
                            $addr .= ', '.$bit;
                        }
                    }

                    return $addr;
                }

                return implode(', ', $parts);
            }
        }

        if (! empty($link->studio_address)) {
            return trim((string) $link->studio_address);
        }

        $street = trim(trim((string) ($link->street_number ?? '')).' '.trim((string) ($link->street_name ?? '')));

        return implode(', ', array_values(array_filter([
            $street !== '' ? $street : null,
            trim((string) ($link->city ?? '')),
            trim((string) ($link->postal_code ?? '')),
            trim((string) ($link->country ?? '')),
        ], fn ($p) => $p !== null && $p !== '')));
    }

    private function mapsUrl(UserStudio $link, ?Studio $studio, string $address): ?string
    {
        $url = trim((string) ($link->google_maps_link ?: $studio?->google_maps_link ?: ''));
        if ($url !== '') {
            return $url;
        }
        if ($address === '') {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address);
    }

    private function paySummary(UserStudio $link): string
    {
        if ($link->payout && $link->revenue_split !== null) {
            $you = (int) $link->revenue_split;

            return 'You '.$you.'% · Studio '.(100 - $you).'%';
        }

        return 'Paid to you directly';
    }

    private function multiSubtitle(UserStudio $link, string $workspaceLabel, string $address, ?int $split): string
    {
        $bits = [$workspaceLabel];
        if ($address !== '') {
            $bits[] = $address;
        }
        if ($link->status === UserStudio::STATUS_JOIN_PENDING) {
            $you = $split ?? 60;
            $bits[] = 'Revenue split You '.$you.'% · Studio '.(100 - $you).'% once they accept';
        } elseif ($link->payout && $split !== null) {
            $bits[] = 'Revenue split You '.$split.'% · Studio '.(100 - $split).'%';
        } else {
            $bits[] = 'Direct payment · You 100%';
        }

        return implode(' · ', $bits);
    }

    private function initials(string $name): string
    {
        $clean = preg_replace('/\s+/', '', $name) ?: 'ST';

        return strtoupper(mb_substr($clean, 0, 2));
    }

    /**
     * @param  array<string, mixed>  $workplace
     */
    private function dualWriteUserDetail(UserDetail $userDetail, array $workplace, ?string $relationship, bool $clearRelationship): void
    {
        $table = $userDetail->getTable();
        $data = [
            'city' => $workplace['city'],
            'country' => $workplace['country'],
        ];

        foreach ([
            'studio_name',
            'studio_address',
            'street_name',
            'street_number',
            'state',
            'postal_code',
            'google_maps_link',
            'workspace_type',
        ] as $col) {
            if (Schema::hasColumn($table, $col)) {
                $data[$col] = $workplace[$col] ?? null;
            }
        }

        if (Schema::hasColumn($table, 'studio_relationship_type')) {
            $data['studio_relationship_type'] = $clearRelationship ? null : $relationship;
        }

        if (Schema::hasColumn($table, 'studio_id')) {
            $data['studio_id'] = null;
        }

        $userDetail->update($data);
    }

    private function mirrorLegacyStudioId(UserDetail $userDetail, ?int $studioId, ?string $relationship, ?int $split): void
    {
        $table = $userDetail->getTable();
        if (Schema::hasColumn($table, 'studio_id')) {
            $userDetail->studio_id = $studioId;
        }
        if (Schema::hasColumn($table, 'studio_relationship_type')) {
            $userDetail->studio_relationship_type = $relationship;
        }
        if (Schema::hasColumn($table, 'studio_revenue_artist_percent') && $split !== null) {
            $userDetail->studio_revenue_artist_percent = $split;
        }
    }
}
