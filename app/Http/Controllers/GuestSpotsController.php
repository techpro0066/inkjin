<?php

namespace App\Http\Controllers;

use App\Mail\GuestSpotStudioInviteMail;
use App\Models\ArtistDesign;
use App\Models\GuestSpot;
use App\Models\Portfolio;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserStudio;
use App\Models\Waitlist;
use App\Services\GuestSpotStudioInviteService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GuestSpotsController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $guestSpots = $user
            ->guestSpots()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $guestSpots->each(fn (GuestSpot $spot) => $spot->ensureCompletedStatus());

        $userDetail = $user?->userDetail;
        $payoutContext = $this->guestSpotPayoutContext($userDetail);

        $avatarPath = trim((string) ($userDetail?->avatar ?? ''));
        $avatarUrl = $avatarPath !== ''
            ? (str_starts_with($avatarPath, 'http') ? $avatarPath : asset(ltrim($avatarPath, '/')))
            : null;

        $displayName = trim((string) ($userDetail?->display_name ?? ''));
        if ($displayName === '') {
            $displayName = trim((string) ($user?->name ?? 'Artist')) ?: 'Artist';
        }

        $gsDesigns = $user->artistDesigns()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(24)
            ->get(['id', 'title', 'image', 'is_active'])
            ->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title ?: 'Design',
                'image_url' => $d->image ? asset($d->image) : null,
                'badge' => $d->is_active ? null : 'Unavailable',
            ]);

        $gsPortfolios = $user->portfolios()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(24)
            ->get(['id', 'title', 'image'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title ?: 'Portfolio',
                'image_url' => $p->image ? asset($p->image) : null,
            ]);

        return view('artist.guest-spots.index', [
            'guestSpots' => $guestSpots,
            'displayGuestSpots' => (bool) ($userDetail?->display_guest_spots ?? false),
            'bookingPageUsername' => $userDetail?->user_name ?? null,
            'gsPayoutTitle' => $payoutContext['title'],
            'gsPayoutConnected' => $payoutContext['connected'],
            'gsStripeConnected' => $payoutContext['stripe_connected'],
            'gsStripeLockbar' => $payoutContext['stripe_lockbar'],
            'gsDesigns' => $gsDesigns,
            'gsPortfolios' => $gsPortfolios,
            'gsArtistName' => $displayName,
            'gsAvatarUrl' => $avatarUrl,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);
        $this->assertNoOverlappingGuestSpots($validated);

        $userId = Auth::id();
        $nextOrder = (int) GuestSpot::query()
            ->where('user_id', $userId)
            ->max('sort_order') + 1;

        $guestSpot = GuestSpot::create(array_merge(
            $this->attributesFromPayload($validated, null),
            [
                'user_id' => $userId,
                'sort_order' => $nextOrder,
            ]
        ));

        $this->dispatchGuestStudioInvite($guestSpot, Auth::user(), false);

        return response()->json([
            'success' => true,
            'message' => 'Guest location added.',
            'guest_spot' => $this->serialize($guestSpot),
        ]);
    }

    public function update(Request $request, GuestSpot $guestSpot): JsonResponse
    {
        $this->assertOwns($guestSpot);

        $previousStatus = (string) ($guestSpot->status ?? '');
        $previousEmail = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        $previousMode = (string) ($guestSpot->payout_mode ?? 'direct');

        $validated = $this->validatedPayload($request);
        $this->assertNoOverlappingGuestSpots($validated, $guestSpot->id);

        $guestSpot->update($this->attributesFromPayload($validated, $guestSpot));
        $guestSpot = $guestSpot->fresh();
        $guestSpot->ensureCompletedStatus();

        $newEmail = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        $newMode = (string) ($guestSpot->payout_mode ?? 'direct');
        $invitePending = ($guestSpot->guest_studio_invite_status ?? null) === GuestSpot::INVITE_PENDING;
        // Send when newly split, email changed, or still pending (retry after a failed send).
        $shouldSend = $newMode === 'split'
            && $newEmail !== ''
            && ($previousMode !== 'split' || $previousEmail !== $newEmail || $invitePending);

        if ($shouldSend) {
            $this->dispatchGuestStudioInvite(
                $guestSpot,
                Auth::user(),
                $previousMode === 'split' && $previousEmail === $newEmail
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Guest location updated.',
            'guest_spot' => $this->serialize($guestSpot),
            'guest_waitlist_notify' => $this->guestWaitlistNotifyPayload(
                Auth::user(),
                $guestSpot,
                $previousStatus
            ),
        ]);
    }

    public function destroy(GuestSpot $guestSpot): JsonResponse
    {
        $this->assertOwns($guestSpot);
        $this->deleteBannerIfSafe($guestSpot->banner_path);
        $guestSpot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guest location removed.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $status = (string) $request->input('status', 'available');
        $isAvailable = $status === 'available';

        // Empty buffer / spots inputs mean 0.
        foreach (['buffer_days_before', 'buffer_days_after', 'number_of_spots'] as $numericField) {
            $raw = $request->input($numericField);
            if ($raw === null || $raw === '') {
                $request->merge([$numericField => 0]);
            }
        }

        // Empty optional strings → null so Planned can omit them without failing format rules.
        foreach ([
            'start_time',
            'end_time',
            'response_deadline',
            'response_deadline_unit',
            'guest_studio_name',
            'guest_studio_address',
            'guest_street_number',
            'guest_street_name',
            'guest_studio_city',
            'guest_studio_state',
            'guest_postal_code',
            'guest_studio_country',
            'guest_google_maps_link',
            'guest_latitude',
            'guest_longitude',
        ] as $optionalField) {
            if ($request->input($optionalField) === '') {
                $request->merge([$optionalField => null]);
            }
        }

        $endTimeRules = [Rule::requiredIf($isAvailable), 'nullable', 'date_format:H:i'];
        if ($isAvailable || $request->filled('start_time')) {
            $endTimeRules[] = 'after:start_time';
        }

        $rules = [
            'status' => ['required', 'string', Rule::in(['available', 'planned'])],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'start_time' => [Rule::requiredIf($isAvailable), 'nullable', 'date_format:H:i'],
            'end_time' => $endTimeRules,
            'response_deadline' => [Rule::requiredIf($isAvailable), 'nullable', 'integer', 'min:1'],
            'response_deadline_unit' => [Rule::requiredIf($isAvailable), 'nullable', 'string', Rule::in(['hours', 'days'])],
            'buffer_days_before' => ['nullable', 'integer', 'min:0'],
            'buffer_days_after' => ['nullable', 'integer', 'min:0'],
            'number_of_spots' => ['nullable', 'integer', 'min:0'],
            'guest_studio_name' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_studio_address' => [Rule::requiredIf($isAvailable), 'nullable', 'string'],
            'guest_street_number' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:50'],
            'guest_street_name' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_studio_city' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_studio_state' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_postal_code' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:50'],
            'guest_studio_country' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_google_maps_link' => ['nullable', 'url', 'max:500'],
            'guest_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'guest_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'payout_mode' => ['nullable', 'string', Rule::in(['direct', 'split'])],
            'artist_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'guest_studio_invite_email' => [
                Rule::requiredIf(fn () => $request->input('payout_mode') === 'split'),
                'nullable',
                'email',
                'max:255',
            ],
            'show_designs' => ['nullable', 'boolean'],
            'show_portfolio' => ['nullable', 'boolean'],
            'show_rates' => ['nullable', 'boolean'],
            'show_policies' => ['nullable', 'boolean'],
            'show_faq' => ['nullable', 'boolean'],
            'selected_design_ids' => ['nullable', 'array'],
            'selected_design_ids.*' => ['integer'],
            'selected_portfolio_ids' => ['nullable', 'array'],
            'selected_portfolio_ids.*' => ['integer'],
            'banner' => ['nullable', 'string'],
            'remove_banner' => ['nullable', 'boolean'],
            'color_scheme' => ['nullable', 'string', 'max:64'],
        ];

        $validated = Validator::make($request->all(), $rules, [
            'to_date.after_or_equal' => 'The end date must be on or after the start date.',
            'end_time.after' => 'The ending time must be after the starting time.',
            'guest_studio_invite_email.required' => 'Enter the guest studio email to invite them for a revenue split.',
            'guest_studio_invite_email.email' => 'Enter a valid guest studio email.',
        ])->validate();

        $validated['city'] = trim($validated['city']);
        $validated['country'] = trim($validated['country']);

        $validated['guest_studio_name'] = trim((string) ($validated['guest_studio_name'] ?? '')) ?: null;
        $validated['guest_studio_address'] = trim((string) ($validated['guest_studio_address'] ?? '')) ?: null;
        $validated['guest_street_number'] = trim((string) ($validated['guest_street_number'] ?? '')) ?: null;
        $validated['guest_street_name'] = trim((string) ($validated['guest_street_name'] ?? '')) ?: null;
        $validated['guest_studio_city'] = trim((string) ($validated['guest_studio_city'] ?? '')) ?: null;
        $validated['guest_studio_state'] = trim((string) ($validated['guest_studio_state'] ?? '')) ?: null;
        $validated['guest_postal_code'] = trim((string) ($validated['guest_postal_code'] ?? '')) ?: null;
        $validated['guest_studio_country'] = trim((string) ($validated['guest_studio_country'] ?? '')) ?: null;
        $validated['guest_google_maps_link'] = trim((string) ($validated['guest_google_maps_link'] ?? '')) ?: null;
        $validated['response_deadline_unit'] = trim((string) ($validated['response_deadline_unit'] ?? '')) ?: null;

        $validated['payout_mode'] = (string) ($validated['payout_mode'] ?? 'direct');
        if ($validated['payout_mode'] !== 'split') {
            $validated['payout_mode'] = 'direct';
            $validated['artist_percent'] = null;
            $validated['guest_studio_invite_email'] = null;
        } else {
            $validated['artist_percent'] = isset($validated['artist_percent'])
                ? max(0, min(100, (int) $validated['artist_percent']))
                : 60;
            $validated['guest_studio_invite_email'] = trim((string) ($validated['guest_studio_invite_email'] ?? '')) ?: null;
        }

        $validated['show_designs'] = $request->boolean('show_designs');
        $validated['show_portfolio'] = $request->boolean('show_portfolio');
        $validated['show_rates'] = $request->boolean('show_rates');
        $validated['show_policies'] = $request->boolean('show_policies', true);
        $validated['show_faq'] = $request->boolean('show_faq', true);
        $validated['remove_banner'] = $request->boolean('remove_banner');
        $validated['color_scheme'] = trim((string) ($validated['color_scheme'] ?? '')) ?: null;
        $validated['banner'] = is_string($validated['banner'] ?? null) ? trim((string) $validated['banner']) : '';

        $userId = (int) Auth::id();
        $designIds = array_values(array_unique(array_map('intval', $validated['selected_design_ids'] ?? [])));
        $portfolioIds = array_values(array_unique(array_map('intval', $validated['selected_portfolio_ids'] ?? [])));

        if ($designIds !== []) {
            $ownedDesignIds = ArtistDesign::query()
                ->where('user_id', $userId)
                ->whereIn('id', $designIds)
                ->pluck('id')
                ->all();
            $designIds = array_values(array_intersect($designIds, array_map('intval', $ownedDesignIds)));
        }
        if ($portfolioIds !== []) {
            $ownedPortfolioIds = Portfolio::query()
                ->where('user_id', $userId)
                ->whereIn('id', $portfolioIds)
                ->pluck('id')
                ->all();
            $portfolioIds = array_values(array_intersect($portfolioIds, array_map('intval', $ownedPortfolioIds)));
        }

        $validated['selected_design_ids'] = $validated['show_designs'] ? $designIds : [];
        $validated['selected_portfolio_ids'] = $validated['show_portfolio'] ? $portfolioIds : [];

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesFromPayload(array $validated, ?GuestSpot $existing = null): array
    {
        $numberOfSpots = (int) ($validated['number_of_spots'] ?? 0);
        $startTime = trim((string) ($validated['start_time'] ?? ''));
        $endTime = trim((string) ($validated['end_time'] ?? ''));
        $responseDeadline = $validated['response_deadline'] ?? null;

        $attributes = [
            'status' => $validated['status'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'start_time' => $startTime !== '' ? $this->normalizeTime($startTime) : null,
            'end_time' => $endTime !== '' ? $this->normalizeTime($endTime) : null,
            'response_deadline' => $responseDeadline !== null && $responseDeadline !== ''
                ? (int) $responseDeadline
                : null,
            'response_deadline_unit' => $validated['response_deadline_unit'] ?? null,
            'buffer_days_before' => (int) ($validated['buffer_days_before'] ?? 0),
            'buffer_days_after' => (int) ($validated['buffer_days_after'] ?? 0),
            'number_of_spots' => $numberOfSpots,
            'remaining_spots' => GuestSpot::remainingSpotsForCapacityChange(
                $numberOfSpots,
                (int) ($existing?->number_of_spots ?? 0),
                (int) ($existing?->remaining_spots ?? 0)
            ),
            'studio_name' => $validated['guest_studio_name'] ?? null,
            'studio_address' => $validated['guest_studio_address'] ?? null,
            'street_number' => $validated['guest_street_number'] ?? null,
            'street_name' => $validated['guest_street_name'] ?? null,
            'studio_city' => $validated['guest_studio_city'] ?? null,
            'studio_state' => $validated['guest_studio_state'] ?? null,
            'postal_code' => $validated['guest_postal_code'] ?? null,
            'studio_country' => $validated['guest_studio_country'] ?? null,
            'google_maps_link' => $validated['guest_google_maps_link'] ?? null,
            'latitude' => isset($validated['guest_latitude']) && $validated['guest_latitude'] !== ''
                && $validated['guest_latitude'] !== null
                ? (float) $validated['guest_latitude']
                : null,
            'longitude' => isset($validated['guest_longitude']) && $validated['guest_longitude'] !== ''
                && $validated['guest_longitude'] !== null
                ? (float) $validated['guest_longitude']
                : null,
        ];

        $bannerPath = $existing?->banner_path;
        if (! empty($validated['remove_banner'])) {
            $this->deleteBannerIfSafe($bannerPath);
            $bannerPath = null;
        } elseif (! empty($validated['banner'])) {
            [$savedPath, $bannerError] = $this->resolveBannerImage(
                (string) $validated['banner'],
                $existing?->banner_path
            );
            if ($bannerError) {
                throw ValidationException::withMessages([
                    'banner' => [$bannerError],
                ]);
            }
            $bannerPath = $savedPath;
        }

        $payoutMode = (string) ($validated['payout_mode'] ?? 'direct');
        $inviteEmail = $validated['guest_studio_invite_email'] ?? null;
        $inviteStatus = null;
        $inviteToken = $existing?->guest_studio_invite_token;
        $linkedStudioId = $existing?->studio_id;
        if ($payoutMode === 'split' && $inviteEmail) {
            $currentInviteStatus = $existing?->guest_studio_invite_status;
            $emailChanged = $existing
                && strtolower(trim((string) ($existing->guest_studio_invite_email ?? '')))
                    !== strtolower(trim((string) $inviteEmail));
            // Keep accepted/rejected unless email changes; otherwise pending for new/changed invites.
            if ($emailChanged || ! in_array($currentInviteStatus, GuestSpot::INVITE_STATUSES, true)) {
                $inviteStatus = GuestSpot::INVITE_PENDING;
                $inviteToken = null; // regenerated in prepareInvite
                $linkedStudioId = null;
            } else {
                $inviteStatus = $currentInviteStatus;
            }
        } else {
            $inviteToken = null;
            $linkedStudioId = null;
        }

        return array_merge($attributes, [
            'payout_mode' => $payoutMode,
            'artist_percent' => $validated['artist_percent'] ?? null,
            'guest_studio_invite_email' => $inviteEmail,
            'guest_studio_invite_status' => $inviteStatus,
            'guest_studio_invite_token' => $inviteToken,
            'studio_id' => $linkedStudioId,
            'show_designs' => (bool) ($validated['show_designs'] ?? false),
            'show_portfolio' => (bool) ($validated['show_portfolio'] ?? false),
            'show_rates' => (bool) ($validated['show_rates'] ?? false),
            'show_policies' => (bool) ($validated['show_policies'] ?? true),
            'show_faq' => (bool) ($validated['show_faq'] ?? true),
            'selected_design_ids' => $validated['selected_design_ids'] ?? [],
            'selected_portfolio_ids' => $validated['selected_portfolio_ids'] ?? [],
            'banner_path' => $bannerPath,
            'color_scheme' => $validated['color_scheme'] ?? null,
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?string} [relativePath, errorMessage]
     */
    private function resolveBannerImage(string $incoming, ?string $currentPath): array
    {
        $incoming = trim($incoming);
        if ($incoming === '') {
            return [$currentPath, null];
        }

        if (str_starts_with($incoming, 'data:image/')) {
            if (! preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#i', $incoming, $m)) {
                return [null, 'Use a JPG, PNG or WebP banner.'];
            }
            $binary = base64_decode(substr($incoming, strpos($incoming, ',') + 1), true);
            if ($binary === false || $binary === '') {
                return [null, 'This banner could not be read.'];
            }
            if (strlen($binary) > 10 * 1024 * 1024) {
                return [null, 'This banner is over 10 MB. Use a smaller one.'];
            }

            $ext = strtolower($m[1]) === 'jpg' ? 'jpg' : strtolower($m[1]);
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }

            $this->deleteBannerIfSafe($currentPath);

            $filename = 'banner_'.time().'_'.uniqid('', true).'.'.$ext;
            $destination = public_path('uploads/guest-spots');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            File::put($destination.DIRECTORY_SEPARATOR.$filename, $binary);

            return ['uploads/guest-spots/'.$filename, null];
        }

        if (str_starts_with($incoming, 'uploads/guest-spots/') && ! str_contains($incoming, '..')) {
            return [$incoming, null];
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $assetBase = rtrim(asset('uploads/guest-spots'), '/');
        foreach ([$appUrl.'/uploads/guest-spots/', $assetBase.'/'] as $base) {
            if ($base !== '' && str_starts_with($incoming, $base)) {
                $rel = 'uploads/guest-spots/'.ltrim(substr($incoming, strlen($base)), '/');
                if (str_starts_with($rel, 'uploads/guest-spots/') && ! str_contains($rel, '..')) {
                    return [$rel, null];
                }
            }
        }

        return [$currentPath, null];
    }

    private function deleteBannerIfSafe(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/guest-spots/')) {
            return;
        }

        $full = public_path($relativePath);
        if (file_exists($full)) {
            File::delete($full);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertNoOverlappingGuestSpots(array $validated, ?int $ignoreGuestSpotId = null): void
    {
        [$candidateFrom, $candidateTo] = $this->blockedRangeFromValidated($validated);

        $query = GuestSpot::query()->where('user_id', Auth::id());

        if ($ignoreGuestSpotId !== null) {
            $query->whereKeyNot($ignoreGuestSpotId);
        }

        foreach ($query->get() as $existing) {
            if (! $existing->overlapsBlockedRange($candidateFrom, $candidateTo)) {
                continue;
            }

            $message = sprintf(
                'These dates overlap with %s, %s (%s – %s), including travel buffer days.',
                $existing->city,
                $existing->country,
                $existing->from_date->format('M j, Y'),
                $existing->to_date->format('M j, Y'),
            );

            throw ValidationException::withMessages([
                'from_date' => [$message],
                'to_date' => [$message],
                'buffer_days_before' => [$message],
                'buffer_days_after' => [$message],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: Carbon, 1: Carbon}
     */
    private function blockedRangeFromValidated(array $validated): array
    {
        $bufferBefore = (int) ($validated['buffer_days_before'] ?? 0);
        $bufferAfter = (int) ($validated['buffer_days_after'] ?? 0);

        $from = Carbon::parse($validated['from_date'])->startOfDay()->subDays($bufferBefore);
        $to = Carbon::parse($validated['to_date'])->startOfDay()->addDays($bufferAfter);

        return [$from, $to];
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $userId = Auth::id();
        $ids = array_values($validated['ids']);

        $ownedCount = GuestSpot::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->count();

        if ($ownedCount !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some guest locations were not found.',
            ], 422);
        }

        DB::transaction(function () use ($ids, $userId) {
            foreach ($ids as $index => $id) {
                GuestSpot::query()
                    ->where('user_id', $userId)
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order updated.',
        ]);
    }

    private function assertOwns(GuestSpot $guestSpot): void
    {
        abort_unless($guestSpot->user_id === Auth::id(), 403);
    }

    /**
     * Prepare invite token / studio link, then email only when the studio is not already on Bookpay.
     */
    private function dispatchGuestStudioInvite(GuestSpot $guestSpot, ?User $artist, bool $isResend = false): void
    {
        if (($guestSpot->payout_mode ?: 'direct') !== 'split') {
            return;
        }

        $inviteEmail = trim((string) ($guestSpot->guest_studio_invite_email ?? ''));
        if ($inviteEmail === '' || ! filter_var($inviteEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $invites = app(GuestSpotStudioInviteService::class);
        $guestSpot = $invites->prepareInvite($guestSpot);

        // Already on Bookpay → confirm in studio dashboard (no join email).
        if ($invites->isStudioOnBookpay($inviteEmail)) {
            return;
        }

        $this->sendGuestStudioInviteEmail($guestSpot, $artist, $isResend, $invites->inviteUrl($guestSpot));
    }

    /**
     * Email the guest studio when revenue split + invite email are set (E2 template).
     */
    private function sendGuestStudioInviteEmail(
        GuestSpot $guestSpot,
        ?User $artist,
        bool $isResend = false,
        ?string $actionUrl = null,
    ): void {
        if (($guestSpot->payout_mode ?: 'direct') !== 'split') {
            return;
        }

        $inviteEmail = trim((string) ($guestSpot->guest_studio_invite_email ?? ''));
        if ($inviteEmail === '' || ! filter_var($inviteEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $artist = $artist ?: $guestSpot->user;
        $detail = $artist?->userDetail;

        $artistName = trim((string) ($detail?->display_name ?? ''));
        if ($artistName === '') {
            $artistName = trim((string) ($artist?->name ?? ''));
        }
        if ($artistName === '') {
            $artistName = 'An artist';
        }

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $artistName) ?: 'A', 0, 2));

        $username = trim((string) ($detail?->user_name ?? ''));
        $city = trim((string) ($detail?->city ?? $guestSpot->city ?? ''));
        $metaParts = array_values(array_filter([
            $username !== '' ? '@'.$username : null,
            $city !== '' ? $city : null,
        ]));
        $artistMeta = implode(' · ', $metaParts);

        $studioGreeting = trim((string) ($guestSpot->studio_name ?? ''));
        if ($studioGreeting === '') {
            $studioGreeting = 'there';
        }

        $dateRangeLabel = '';
        if ($guestSpot->from_date && $guestSpot->to_date) {
            $fromLabel = $guestSpot->from_date->format('M j');
            $toLabel = $guestSpot->to_date->format('M j');
            if ($guestSpot->from_date->format('Y') !== $guestSpot->to_date->format('Y')) {
                $fromLabel = $guestSpot->from_date->format('M j, Y');
                $toLabel = $guestSpot->to_date->format('M j, Y');
            } elseif ($guestSpot->from_date->format('Y') !== now()->format('Y')) {
                $toLabel = $guestSpot->to_date->format('M j, Y');
            }
            $dateRangeLabel = $fromLabel.' to '.$toLabel;
        }

        $actionUrl = $actionUrl ?: app(GuestSpotStudioInviteService::class)->inviteUrl($guestSpot);

        try {
            Mail::to($inviteEmail)->send(new GuestSpotStudioInviteMail(
                artistName: $artistName,
                artistInitials: $initials,
                artistMeta: $artistMeta,
                studioGreetingName: $studioGreeting,
                dateRangeLabel: $dateRangeLabel,
                actionUrl: $actionUrl,
                artistPercent: $guestSpot->artist_percent !== null ? (int) $guestSpot->artist_percent : null,
                isResend: $isResend,
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send guest spot studio invite email', [
                'guest_spot_id' => $guestSpot->id,
                'email' => $inviteEmail,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * When a planned spot becomes Available and auto-notify is on, prompt to email
     * pending guest waitlist sign-ups for that spot (same pattern as books waitlist).
     *
     * @return array{pending_count:int,notify_url:string,guest_spot_id:int,ids:list<int>,city:string}|null
     */
    private function guestWaitlistNotifyPayload(?User $artist, GuestSpot $guestSpot, string $previousStatus): ?array
    {
        if (! $artist) {
            return null;
        }

        $becameAvailable = $previousStatus === 'planned'
            && $guestSpot->effectiveStatusKey() === 'available'
            && $guestSpot->isReservable();

        if (! $becameAvailable) {
            return null;
        }

        $userDetail = $artist->userDetail;
        $autoNotify = (bool) ($userDetail?->guest_waitlist_auto_notify ?? true);
        if (! $autoNotify) {
            return null;
        }

        $ids = Waitlist::query()
            ->guestOnly()
            ->where('user_id', $artist->id)
            ->where('guest_spot_id', $guestSpot->id)
            ->where('status', Waitlist::STATUS_PENDING)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($ids === []) {
            return null;
        }

        $city = trim((string) ($guestSpot->city ?? ''));

        return [
            'pending_count' => count($ids),
            'notify_url' => route('artist.clients.guest-waitlist.notify'),
            'guest_spot_id' => (int) $guestSpot->id,
            'ids' => $ids,
            'city' => $city !== '' ? $city : 'this city',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(GuestSpot $guestSpot): array
    {
        return $guestSpot->toFormArray();
    }

    private function normalizeTime(string $time): string
    {
        return Carbon::createFromFormat('H:i', $time)->format('H:i:s');
    }

    /**
     * F01 “current payout setup” / Stripe lockbar for the guest spot modal (UI only).
     *
     * @return array{title: string, connected: bool, stripe_connected: bool, stripe_lockbar: string}
     */
    private function guestSpotPayoutContext(?UserDetail $userDetail): array
    {
        $stripeConnected = ! empty($userDetail?->stripe_account_id);
        $paymentType = (string) ($userDetail?->payment_type ?? '');
        $studioName = trim((string) ($userDetail?->resolvedStudioName() ?? ''));
        $link = $userDetail
            ? (UserStudio::payoutLinkForUser((int) $userDetail->user_id)
                ?? UserStudio::activeForUser((int) $userDetail->user_id))
            : null;

        $isSplit = ($link && $link->payout)
            || $paymentType === 'studio_account';

        if ($isSplit) {
            $title = $studioName !== ''
                ? 'Studio - Revenue split with '.$studioName
                : 'Studio - Revenue split';
        } else {
            $title = 'Artist - Direct payment';
        }

        return [
            'title' => $title,
            'connected' => $stripeConnected || $isSplit,
            'stripe_connected' => $stripeConnected,
            'stripe_lockbar' => $stripeConnected
                ? 'Your Stripe account is connected (••••).'
                : 'Connect Stripe in Money → Payouts if you haven’t yet.',
        ];
    }
}
