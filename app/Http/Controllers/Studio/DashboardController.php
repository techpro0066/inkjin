<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ConsentFormSetting;
use App\Models\Studio;
use App\Models\StudioInvite;
use App\Models\StudioPortfolio;
use App\Models\UserStudio;
use App\Services\ArtistPayoutService;
use App\Services\GuestSpotStudioInviteService;
use App\Services\StripeConnectService;
use App\Support\StudioPaymentLinkModalData;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GuestSpotStudioInviteService $guestSpotInvites,
        ArtistPayoutService $payout,
        StripeConnectService $stripeConnect,
        BookingsController $bookings,
        MoneyController $money,
    ): View|RedirectResponse {
        $user = $request->user();
        $email = strtolower(trim((string) ($user->email ?? '')));

        $studio = $user?->id
            ? Studio::query()->where('user_id', $user->id)->first()
            : null;
        if (! $studio && $email !== '') {
            $studio = Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($studio && empty($studio->user_id) && $user?->id) {
                $studio->user_id = $user->id;
                $studio->save();
            }
        }

        $stripeChoiceUrl = $studio?->inviteStripeChoiceUrl();
        if (is_string($stripeChoiceUrl) && $stripeChoiceUrl !== '') {
            return redirect()->to($stripeChoiceUrl);
        }

        $studioName = trim((string) ($studio?->name ?? $user->userDetail?->resolvedStudioName() ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $stripeConnected = (bool) ($studio?->hasStripeConnect());
        $profileComplete = (string) ($user->on_boarding ?? '') === 'yes';
        $offersSplit = $studio?->offers_revenue_split;
        if ($offersSplit === null) {
            $offersSplit = true;
        }
        $needsStripe = (bool) $offersSplit;
        $stripeConnectUrl = $profileComplete
            ? route('studio.money.payouts')
            : OnboardingController::nextIncompleteStepRoute($user, $studio);

        $relationshipLabels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];

        $activeArtistCount = 0;
        $todos = [];
        if ($studio && UserStudio::tableReady()) {
            $activeArtistCount = UserStudio::query()
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->count();

            $joinLinks = UserStudio::query()
                ->with(['user.userDetail'])
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_JOIN_PENDING)
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            foreach ($joinLinks as $link) {
                $baseName = $this->artistDisplayName($link);
                $rel = $relationshipLabels[$link->relationship ?? ''] ?? 'Artist';
                $isSplit = (bool) $link->payout && $link->revenue_split !== null;
                $artistPct = $isSplit ? max(0, min(100, (int) $link->revenue_split)) : null;
                $studioPct = $isSplit ? 100 - (int) $artistPct : null;
                $detail = $isSplit
                    ? $baseName.' · '.$rel.' · Artist '.$artistPct.'% · Studio '.$studioPct.'%'
                    : $baseName.' · '.$rel.' · Workstation rent';

                $todos[] = [
                    'icon' => 'person_add',
                    'icon_bg' => '#F3E8FF',
                    'icon_color' => '#3E007C',
                    'title' => 'Artist wants to join',
                    'detail' => $detail,
                    'cta' => 'Review',
                    'url' => route('studio.artists.join-requests'),
                ];
            }
        }

        if ($studio && StudioInvite::tableReady()) {
            $pendingInvites = StudioInvite::query()
                ->where('studio_id', $studio->id)
                ->where('status', StudioInvite::STATUS_PENDING)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            foreach ($pendingInvites as $invite) {
                $label = trim((string) ($invite->email ?? ''));
                if ($label === '') {
                    $label = 'Artist invite';
                }
                $sent = optional($invite->updated_at ?? $invite->created_at)->format('d/m') ?? '—';
                $todos[] = [
                    'icon' => 'mail',
                    'icon_bg' => '#E3F0FC',
                    'icon_color' => '#1565C0',
                    'title' => 'Invite not answered',
                    'detail' => $label.' · sent '.$sent,
                    'cta' => 'Resend',
                    'url' => route('studio.artists.invites'),
                ];
            }
        }

        $guestCount = 0;
        if ($studio) {
            $pendingGuestSpotInvites = $guestSpotInvites->pendingForStudio($studio);
            $guestCount = $guestSpotInvites->countForStudio($studio);
            foreach ($pendingGuestSpotInvites->take(5) as $spot) {
                $spot = $guestSpotInvites->prepareInvite($spot);
                $detail = $spot->user?->userDetail;
                $name = trim((string) ($detail?->display_name ?? ''));
                if ($name === '' || $name === 'Artist') {
                    $fallback = trim(($spot->user?->first_name ?? '').' '.($spot->user?->last_name ?? ''));
                    $name = $fallback !== '' ? $fallback : 'Artist';
                }
                $artistPercent = $spot->artist_percent !== null
                    ? max(0, min(100, (int) $spot->artist_percent))
                    : 60;
                $dates = '';
                if ($spot->from_date && $spot->to_date) {
                    $dates = $spot->from_date->format('M j').' – '.$spot->to_date->format('M j, Y');
                }
                $todos[] = [
                    'icon' => 'luggage',
                    'icon_bg' => '#E6F6EC',
                    'icon_color' => '#1F7A45',
                    'title' => 'Guest spot split request',
                    'detail' => trim($name.($dates !== '' ? ' · '.$dates : '').' · Artist '.$artistPercent.'% · Studio '.(100 - $artistPercent).'%'),
                    'cta' => 'Review',
                    'url' => route('studio.artists.guests'),
                ];
            }
        }

        $hasDescription = trim((string) ($studio?->description ?? '')) !== '';
        $hasBanner = trim((string) ($studio?->page_banner ?? '')) !== ''
            || trim((string) ($studio?->page_bg_desktop ?? '')) !== '';
        $hasPortfolio = $studio && Schema::hasTable('studio_portfolios')
            ? StudioPortfolio::query()->where('studio_id', $studio->id)->exists()
            : false;
        $hasConsent = (bool) ($studio?->use_studio_consent_form);
        if ($hasConsent && $user?->id) {
            $consent = ConsentFormSetting::query()->where('user_id', $user->id)->first();
            $hasConsent = $consent && trim((string) ($consent->studio_market ?? '')) !== '';
        }
        $hasStudioDetails = $profileComplete
            || (
                $studio
                && trim((string) ($studio->name ?? '')) !== ''
                && trim((string) ($studio->username ?? '')) !== ''
                && trim((string) ($studio->business_phone ?? '')) !== ''
                && trim((string) ($studio->logo_url ?? '')) !== ''
            );
        $hasArtists = $activeArtistCount > 0;

        $checklistNeed = [];
        if ($needsStripe) {
            $checklistNeed[] = [
                'label' => 'Connect payouts (Stripe)',
                'done' => $stripeConnected,
                'url' => $stripeConnectUrl,
                'hint' => 'So your share of each revenue split reaches your bank.',
            ];
        }
        $checklistNeed[] = [
            'label' => 'Add your studio details',
            'done' => $hasStudioDetails,
            'url' => $profileComplete
                ? route('studio.account.studio')
                : route('studio.onboarding.profile'),
            'hint' => null,
        ];
        $checklistNeed[] = [
            'label' => 'Invite your artists',
            'done' => $hasArtists,
            'url' => route('studio.artists'),
            'hint' => 'Your page shows your artists and their work once they join.',
        ];

        $checklistMore = [
            [
                'label' => 'Add a studio page description',
                'done' => $hasDescription,
                'url' => route('studio.page.about'),
                'hint' => null,
            ],
            [
                'label' => 'Set up your consent form',
                'done' => $hasConsent,
                'url' => route('studio.client-flow.consent'),
                'hint' => 'Until you do, your artists\' own forms are used.',
            ],
            [
                'label' => 'Upload a banner',
                'done' => $hasBanner,
                'url' => route('studio.page.about'),
                'hint' => null,
            ],
            [
                'label' => 'Add your studio portfolio (optional)',
                'done' => $hasPortfolio,
                'url' => route('studio.page.portfolio'),
                'hint' => null,
            ],
        ];

        $checklistAll = array_merge($checklistNeed, $checklistMore);
        $checklistDone = count(array_filter($checklistAll, fn (array $i) => $i['done']));
        $checklistTotal = count($checklistAll);
        $checklistPct = $checklistTotal > 0
            ? (int) round(($checklistDone / $checklistTotal) * 100)
            : 0;

        $monthStart = now()->copy()->startOfMonth();
        $monthEnd = now()->copy()->endOfMonth();
        $bookingsThisMonth = 0;
        $artistsThisMonth = 0;
        $guestsThisMonth = 0;
        $upcoming = collect();

        if ($studio) {
            $monthBookings = Booking::query()
                ->where('studio_id', $studio->id)
                ->whereDate('booking_date', '>=', $monthStart->toDateString())
                ->whereDate('booking_date', '<=', $monthEnd->toDateString())
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->get(['id', 'artist_user_id', 'guest_spot_id']);

            $bookingsThisMonth = $monthBookings->count();
            $artistsThisMonth = $monthBookings->pluck('artist_user_id')->filter()->unique()->count();
            $guestsThisMonth = $monthBookings->whereNotNull('guest_spot_id')->pluck('guest_spot_id')->unique()->count();

            $upcoming = Booking::query()
                ->with(['user', 'artist.userDetail', 'tattoo', 'guestSpot'])
                ->where('studio_id', $studio->id)
                ->whereDate('booking_date', '>=', now()->toDateString())
                ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
                ->orderBy('booking_date')
                ->orderBy('id')
                ->limit(8)
                ->get()
                ->map(function (Booking $booking) use ($bookings) {
                    $row = $bookings->dashboardBookingRow($booking);
                    $share = (string) ($row['share'] ?? '');
                    if (strcasecmp($share, 'Direct') === 0) {
                        $share = '€0 · direct';
                    }

                    $bt = $booking->booking_time;
                    $start = is_array($bt) && isset($bt['start'])
                        ? $this->to24h((string) $bt['start'])
                        : null;
                    $when = $booking->booking_date
                        ? $booking->booking_date->format('D d/m')
                        : '—';
                    if ($start) {
                        $when .= ' · '.$start;
                    }

                    $client = (string) ($row['client'] ?? 'Client');
                    if ($booking->guest_spot_id) {
                        $client .= ' (guest)';
                    }

                    return [
                        'id' => $row['id'],
                        'client' => $client,
                        'artist' => $row['artist'],
                        'service' => $row['service'],
                        'when' => $when,
                        'through' => $row['through'],
                        'share' => $share,
                        'modal' => $row['modal'],
                    ];
                })
                ->values();
        }

        $earnings = $money->dashboardEarningsSummary($studio, $payout, $stripeConnect);
        $earnedThisMonth = (float) ($earnings['earned_this_month'] ?? 0);
        $nextPayout = (float) ($earnings['next_payout'] ?? 0);
        $nextPayoutMeta = (string) ($earnings['next_payout_meta'] ?? 'Nothing queued yet');
        if (! $stripeConnected && $needsStripe) {
            $nextPayoutMeta = 'Connect Stripe to receive payouts';
        }

        $bookingsMeta = 'No bookings yet this month';
        if ($bookingsThisMonth > 0) {
            $parts = [];
            if ($artistsThisMonth > 0) {
                $parts[] = $artistsThisMonth.' '.($artistsThisMonth === 1 ? 'artist' : 'artists');
            }
            if ($guestsThisMonth > 0) {
                $parts[] = $guestsThisMonth.' '.($guestsThisMonth === 1 ? 'guest' : 'guests');
            }
            $bookingsMeta = $parts !== []
                ? 'Across '.implode(' and ', $parts)
                : 'This month';
        }

        $artistsMeta = $guestCount > 0
            ? $guestCount.' '.($guestCount === 1 ? 'guest' : 'guests').' overall'
            : ($activeArtistCount > 0 ? 'On your studio page' : 'Invite artists to get started');

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        $studioUsername = trim((string) ($studio?->username ?? ''));
        $publicPageHost = $studioUsername !== '' ? 'inkjin.com/@'.$studioUsername : '';
        $publicPageUrl = $studioUsername !== ''
            ? route('public.artist', ['username' => $studioUsername])
            : '';
        $quickLinkRows = $studioUsername !== '' ? [
            ['suffix' => '', 'hash' => ''],
            ['suffix' => '/artists', 'hash' => '#artists'],
            ['suffix' => '/portfolio', 'hash' => '#work'],
            ['suffix' => '/guest-artists', 'hash' => '#guest-artists'],
            ['suffix' => '/find-us', 'hash' => '#findus'],
        ] : [];

        $studioArtistsForModal = $studio
            ? StudioPaymentLinkModalData::artists($studio)
            : collect();
        $paymentLinkModal = StudioPaymentLinkModalData::createGate(
            $profileComplete,
            $studio,
            $studioArtistsForModal
        );

        $defaultArtistPercent = max(1, min(99, (int) ($studio?->default_revenue_artist_percent ?? 60)));
        $todayLabel = Carbon::now()->format('l d/m');

        $showWelcomePopup = $studio
            && Schema::hasColumn('studios', 'welcome_seen_at')
            && $studio->welcome_seen_at === null
            && $profileComplete;

        return view('studio.dashboard', array_merge($paymentLinkModal, [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'stripeConnected' => $stripeConnected,
            'stripeConnectUrl' => $stripeConnectUrl,
            'needsStripe' => $needsStripe,
            'profileComplete' => $profileComplete,
            'activeNav' => 'home',
            'studioArtistsForModal' => $studioArtistsForModal,
            'currencySymbol' => '€',
            'paymentLinkModalReloadOnDone' => false,
            'publicPageUrl' => $publicPageUrl,
            'publicPageHost' => $publicPageHost,
            'quickLinkRows' => $quickLinkRows,
            'todayLabel' => $todayLabel,
            'todos' => $todos,
            'checklistNeed' => $checklistNeed,
            'checklistMore' => $checklistMore,
            'checklistDone' => $checklistDone,
            'checklistTotal' => $checklistTotal,
            'checklistPct' => $checklistPct,
            'bookingsThisMonth' => $bookingsThisMonth,
            'bookingsMeta' => $bookingsMeta,
            'earnedThisMonth' => $earnedThisMonth,
            'earnedThisMonthLabel' => $this->formatEuro($earnedThisMonth),
            'monthDeltaLabel' => (string) ($earnings['month_delta_label'] ?? ''),
            'activeArtistCount' => $activeArtistCount,
            'artistsMeta' => $artistsMeta,
            'nextPayout' => $nextPayout,
            'nextPayoutLabel' => $this->formatEuro($nextPayout),
            'nextPayoutMeta' => $nextPayoutMeta,
            'upcomingBookings' => $upcoming,
            'relationshipOptions' => $relationshipLabels,
            'defaultArtistPercent' => $defaultArtistPercent,
            'showWelcomePopup' => $showWelcomePopup,
            'ownerIsArtist' => (bool) ($studio?->owner_is_artist),
            'welcomeChecklist' => [
                'need' => $checklistNeed,
                'more' => $checklistMore,
                'done' => $checklistDone,
                'total' => $checklistTotal,
            ],
        ]));
    }

    public function markWelcomeSeen(Request $request): JsonResponse
    {
        $user = $request->user();
        $studio = $user?->id
            ? Studio::query()->where('user_id', $user->id)->first()
            : null;

        if ($studio
            && Schema::hasColumn('studios', 'welcome_seen_at')
            && $studio->welcome_seen_at === null
        ) {
            $studio->forceFill(['welcome_seen_at' => now()])->save();
        }

        return response()->json(['success' => true]);
    }

    private function artistDisplayName(UserStudio $link): string
    {
        $detail = $link->user?->userDetail;
        $name = $detail ? $detail->publicDisplayName() : '';
        if ($name === '' || $name === 'Artist') {
            $fallback = trim(($link->user?->first_name ?? '').' '.($link->user?->last_name ?? ''));
            $name = $fallback !== '' ? $fallback : 'Artist';
        }

        return $name;
    }

    private function formatEuro(float $amount): string
    {
        if (abs($amount - round($amount)) < 0.005) {
            return '€'.number_format($amount, 0);
        }

        return '€'.number_format($amount, 2);
    }

    private function to24h(string $t): string
    {
        $t = trim($t);
        if ($t === '') {
            return '';
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $t)) {
            [$h, $m] = array_map('intval', explode(':', $t));

            return sprintf('%02d:%02d', $h, $m);
        }
        try {
            return Carbon::parse($t)->format('H:i');
        } catch (\Throwable) {
            return $t;
        }
    }
}
