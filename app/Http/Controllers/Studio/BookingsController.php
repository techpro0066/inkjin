<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CustomRequest;
use App\Models\Studio;
use App\Models\StudioPortfolio;
use App\Models\StudioRequest;
use App\Models\UserStudio;
use App\Services\ArtistPayoutService;
use App\Support\QuestionAnswerPresenter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingsController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->viewData($request, 'bookings');
        $studio = $data['studio'];

        $filter = strtolower(trim((string) $request->query('f', 'all')));
        $q = trim((string) $request->query('q', ''));

        $bookings = collect();
        $artistFilters = [];

        if ($studio) {
            $query = Booking::query()
                ->with(['user', 'artist.userDetail', 'tattoo', 'guestSpot'])
                ->where('studio_id', $studio->id)
                ->orderByDesc('booking_date')
                ->orderByDesc('id');

            if ($filter === 'upcoming') {
                $query->whereDate('booking_date', '>=', now()->toDateString())
                    ->whereNotIn('status', ['cancelled', 'completed', 'no_show']);
            } elseif ($filter === 'past') {
                $query->where(function ($inner) {
                    $inner->whereDate('booking_date', '<', now()->toDateString())
                        ->orWhereIn('status', ['completed', 'cancelled', 'no_show']);
                });
            } elseif (str_starts_with($filter, 'artist:')) {
                $artistId = (int) substr($filter, 7);
                if ($artistId > 0) {
                    $query->where('artist_user_id', $artistId);
                }
            }

            if ($q !== '') {
                $query->where(function ($inner) use ($q) {
                    $inner->where('id', 'like', '%'.$q.'%')
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('first_name', 'like', '%'.$q.'%')
                                ->orWhere('last_name', 'like', '%'.$q.'%')
                                ->orWhere('email', 'like', '%'.$q.'%');
                        });
                });
            }

            $bookings = $query->limit(200)->get()->map(fn (Booking $b) => $this->mapBookingRow($b));

            $artistFilters = $this->studioArtists($studio)->map(fn (array $a) => [
                'id' => $a['user_id'],
                'name' => $a['first_name'] !== '' ? $a['first_name'] : $a['name'],
                'slug' => 'artist:'.$a['user_id'],
            ])->all();
        }

        return view('studio.bookings.index', array_merge($data, [
            'bookings' => $bookings,
            'artistFilters' => $artistFilters,
            'activeFilter' => $filter,
            'searchQuery' => $q,
        ]));
    }

    public function requests(Request $request): View
    {
        $data = $this->viewData($request, 'requests');
        $studio = $data['studio'];
        $rows = collect();
        $artists = [];

        if ($studio) {
            $artists = $this->studioArtists($studio)->all();
            $rows = StudioRequest::query()
                ->with(['assignedArtist.userDetail', 'user', 'customRequest'])
                ->where('studio_id', $studio->id)
                ->whereNotIn('status', [
                    StudioRequest::STATUS_CANCELLED,
                    StudioRequest::STATUS_DECLINED,
                    StudioRequest::STATUS_MOVED_TO_BOOKING,
                ])
                ->orderByDesc('created_at')
                ->limit(200)
                ->get()
                ->map(fn (StudioRequest $r) => $this->mapRequestCard($r));
            // Keep cancelled / booked out of list; withdrawn removed from active board.
        }

        return view('studio.bookings.requests', array_merge($data, [
            'requestCards' => $rows,
            'assignArtists' => $artists,
        ]));
    }

    public function paymentLinks(Request $request): View
    {
        return view('studio.bookings.payment-links', $this->viewData($request, 'payment-links'));
    }

    public function assign(Request $request, StudioRequest $studioRequest): RedirectResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        abort_unless($studio && (int) $studioRequest->studio_id === (int) $studio->id, 404);

        $validated = $request->validate([
            'artist_user_id' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $artistId = (int) $validated['artist_user_id'];
        $allowed = $this->studioArtists($studio)->pluck('user_id')->all();
        if (! in_array($artistId, $allowed, true)) {
            return back()->with('error', 'That artist is not linked to your studio.');
        }

        if (! $studioRequest->canStudioAssign()) {
            return back()->with('error', 'This request can no longer be assigned.');
        }

        // Offer only — CustomRequest is created when the artist accepts.
        $answers = is_array($studioRequest->questions_answers) ? $studioRequest->questions_answers : [];
        if (! empty($validated['note'])) {
            $answers['_studio_assign_note'] = trim((string) $validated['note']);
        }

        $studioRequest->forceFill([
            'assigned_artist_id' => $artistId,
            'custom_request_id' => null,
            'status' => StudioRequest::STATUS_ASSIGNED,
            'reason_decline' => null,
            'questions_answers' => $answers,
        ])->save();

        return redirect()
            ->route('studio.bookings.requests')
            ->with('success', 'Offer sent. Waiting for the artist to accept.');
    }

    public function withdraw(Request $request, StudioRequest $studioRequest): RedirectResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        abort_unless($studio && (int) $studioRequest->studio_id === (int) $studio->id, 404);

        if (! $studioRequest->canStudioWithdraw()) {
            return back()->with('error', 'This request can no longer be withdrawn.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $reason = trim((string) ($validated['reason'] ?? '')) ?: 'Withdrawn by studio';

        $studioName = trim((string) ($studio->name ?? 'Studio'));
        if ($studioName === '') {
            $studioName = 'Studio';
        }

        $studioRequest->appendSendBackHistory(
            'studio_withdraw',
            $reason,
            null,
            null,
            $studioName,
        );
        $studioRequest->forceFill([
            'status' => StudioRequest::STATUS_CANCELLED,
            'assigned_artist_id' => null,
            'custom_request_id' => null,
            'reason_decline' => $reason,
        ])->save();

        return redirect()
            ->route('studio.bookings.requests')
            ->with('success', 'Request withdrawn.');
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Request $request, string $bookingsTab): array
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $studioName = trim((string) ($studio?->name ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        $bookingsCount = 0;
        $requestsCount = 0;
        if ($studio) {
            $bookingsCount = Booking::query()->where('studio_id', $studio->id)->count();
            $requestsCount = StudioRequest::query()
                ->where('studio_id', $studio->id)
                ->whereNotIn('status', [
                    StudioRequest::STATUS_CANCELLED,
                    StudioRequest::STATUS_DECLINED,
                    StudioRequest::STATUS_MOVED_TO_BOOKING,
                ])
                ->count();
        }

        return [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'profileComplete' => (string) ($user->on_boarding ?? '') === 'yes',
            'stripeConnected' => (bool) ($studio?->hasStripeConnect()),
            'activeNav' => 'bookings',
            'bookingsTab' => $bookingsTab,
            'bookingsCount' => $bookingsCount,
            'requestsCount' => $requestsCount,
            'paymentLinksCount' => 0,
        ];
    }

    private function resolveStudio($user): ?Studio
    {
        if ($user?->id) {
            $byOwner = Studio::query()->where('user_id', $user->id)->first();
            if ($byOwner) {
                return $byOwner;
            }
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email === '') {
            return null;
        }

        $studio = Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($studio && empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
            $studio->save();
        }

        return $studio;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{user_id:int,name:string,first_name:string}>
     */
    private function studioArtists(Studio $studio)
    {
        return UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->orderBy('id')
            ->get()
            ->map(function (UserStudio $link) {
                $artist = $link->user;
                if (! $artist || (string) ($artist->role ?? '') !== 'artist') {
                    return null;
                }
                $detail = $artist->userDetail;
                $name = $detail?->publicDisplayName();
                if (! $name) {
                    $name = trim(($artist->first_name ?? '').' '.($artist->last_name ?? '')) ?: 'Artist';
                }
                $styles = $this->formatTattooStyles($detail?->tattoo_styles ?? null);
                $optionLabel = $styles !== '' ? $name.' · '.$styles : $name;

                return [
                    'user_id' => (int) $artist->id,
                    'name' => $name,
                    'first_name' => trim((string) ($artist->first_name ?? '')) ?: explode(' ', $name)[0],
                    'styles' => $styles,
                    'option_label' => $optionLabel,
                ];
            })
            ->filter()
            ->values();
    }

    private function formatTattooStyles(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        $labels = [];
        if (array_key_exists('primary_style', $payload) || array_key_exists('other_styles', $payload)) {
            $primary = trim((string) ($payload['primary_style'] ?? ''));
            if ($primary !== '') {
                $labels[] = $primary;
            }
            foreach ((array) ($payload['other_styles'] ?? []) as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style !== '') {
                    $labels[] = $style;
                }
            }
        } else {
            foreach ($payload as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style !== '') {
                    $labels[] = $style;
                }
            }
        }

        return implode(', ', array_slice(array_values(array_unique($labels)), 0, 3));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBookingRow(Booking $booking): array
    {
        $client = $booking->user;
        $clientName = trim(($client?->first_name ?? '').' '.($client?->last_name ?? ''));
        if ($clientName === '') {
            $clientName = (string) ($client?->email ?? 'Client');
        }
        $shortClient = $this->shortName($clientName);

        $artist = $booking->artist;
        $artistDetail = $artist?->userDetail;
        $artistName = $artistDetail?->publicDisplayName()
            ?: trim(($artist?->first_name ?? '').' '.($artist?->last_name ?? ''))
            ?: 'Artist';

        $status = strtolower((string) $booking->status);
        $statusLabel = match ($status) {
            'confirmed' => 'Confirmed',
            'pending' => 'Pending deposit',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No show',
            'rescheduled' => 'Rescheduled',
            default => ucfirst($status ?: 'Unknown'),
        };
        $statusPill = match ($status) {
            'confirmed', 'completed' => 'g',
            'pending' => 'a',
            'cancelled', 'no_show' => 'r',
            default => 'k',
        };

        $through = $this->bookedThroughLabel($booking);
        $share = $this->studioShareLabel($booking);

        $dateLabel = $booking->booking_date
            ? $booking->booking_date->format('d/m/Y')
            : '—';

        $isUpcoming = $booking->booking_date
            && $booking->booking_date->toDateString() >= now()->toDateString()
            && ! in_array($status, ['cancelled', 'completed', 'no_show'], true);

        $paidAmount = (float) ($booking->total_amount_paid ?? $booking->deposit_amount ?? 0);
        $currencyPrefix = strtoupper((string) ($booking->currency ?? 'EUR')) === 'EUR'
            ? '€'
            : strtoupper((string) ($booking->currency ?? 'EUR')).' ';

        return [
            'id' => $booking->id,
            'client' => $shortClient,
            'client_full' => $clientName,
            'artist' => $artistName,
            'artist_id' => (int) $booking->artist_user_id,
            'service' => $booking->displayTitle(),
            'date' => $dateLabel,
            'status' => $statusLabel,
            'status_pill' => $statusPill,
            'through' => $through,
            'share' => $share,
            'reference' => $booking->referenceLabel(),
            'filter_tokens' => trim(
                ($isUpcoming ? 'upcoming' : 'past').' artist:'.$booking->artist_user_id
            ),
            'paid' => number_format($paidAmount, 0),
            'currency' => $currencyPrefix,
            'modal' => $this->bookingModalPayload(
                $booking,
                $clientName,
                $artistName,
                $statusLabel,
                $statusPill,
                $through,
            ),
        ];
    }

    /**
     * Studio booking detail modal (matches artist A02 / S04 detail layout).
     *
     * @return array<string, mixed>
     */
    private function bookingModalPayload(
        Booking $booking,
        string $clientName,
        string $artistName,
        string $statusLabel,
        string $statusPill,
        string $through,
    ): array {
        $payout = app(ArtistPayoutService::class);
        $userDetail = $booking->artist?->userDetail;
        $money = $payout->bookingDetailsMoneyBreakdown($booking, $userDetail);

        $deposit = max(0, round((float) ($booking->deposit_amount ?? 0), 2));
        $stripeFee = (float) ($money['stripe_fee_amount'] ?? $payout->estimateStripeFee($deposit));
        $artistPercent = $payout->resolvePayoutArtistPercent($booking, $userDetail);
        $paymentType = $payout->resolvePayoutPaymentType($booking, $userDetail);
        $hasSplit = $paymentType === 'studio_account' && $artistPercent < 100;
        $studioPercent = max(0, 100 - $artistPercent);
        $clientFee = max(0, round((float) ($booking->platform_fee ?? 0), 2));
        $firstName = trim((string) strtok($artistName, ' ')) ?: $artistName;

        if ($hasSplit) {
            $artistShare = round($deposit * ($artistPercent / 100), 2);
            $artistStripe = round($stripeFee * ($artistPercent / 100), 2);
            $artistGets = max(0, round($artistShare - $artistStripe, 2));
            $studioShare = round($deposit * ($studioPercent / 100), 2);
            $studioStripe = round($stripeFee * ($studioPercent / 100), 2);
            $studioGets = max(0, round($studioShare - $studioStripe, 2));
            $splitValue = $firstName.' '.$artistPercent.'% · Studio '.$studioPercent.'%';
        } else {
            $artistShare = $deposit;
            $artistStripe = $stripeFee;
            $artistGets = max(0, round($deposit - $stripeFee, 2));
            $studioShare = 0.0;
            $studioStripe = 0.0;
            $studioGets = 0.0;
            $splitValue = 'Direct · no studio split';
        }

        $paidAt = $booking->created_at?->timezone(config('app.timezone'))->format('d/m');
        $depositMoneyLabel = 'Client paid · deposit'.($paidAt ? ', '.$paidAt : '');

        $footnote = (string) ($money['footnote'] ?? '');
        // Studio-facing footnote: bank timing for studio share.
        $deadline = $payout->cancellationDeadlineAt($booking);
        if ($hasSplit && $deadline && now()->lt($deadline)) {
            $footnote = 'Sent to your bank after '.$deadline->timezone(config('app.timezone'))->format('d/m')
                .', when the cancellation window ends.';
        } elseif ($hasSplit) {
            $footnote = 'Sent to your bank when the cancellation window ends.';
        }

        $balanceDue = max(0, round((float) $booking->remainingBalanceAmount(), 2));
        $balanceNote = null;
        if ($balanceDue > 0.009
            && ! $booking->remaining_amount_released
            && ! $booking->full_amount_paid
            && ! in_array(strtolower((string) $booking->status), ['cancelled', 'completed', 'declined'], true)
        ) {
            $balStripe = $payout->estimateStripeFee($balanceDue);
            if ($hasSplit) {
                $balArtist = max(0, round($balanceDue * ($artistPercent / 100) - $balStripe * ($artistPercent / 100), 2));
                $balStudio = max(0, round(($balanceDue - $balStripe) * ($studioPercent / 100), 2));
                $balanceNote = 'When the '.$this->formatMoney($balanceDue, $booking).' balance is paid: Stripe fee '
                    .$this->formatMoney($balStripe, $booking).', '.$firstName.' gets '
                    .$this->formatMoney($balArtist, $booking).', you get '.$this->formatMoney($balStudio, $booking).'.';
            } else {
                $balanceNote = 'When the '.$this->formatMoney($balanceDue, $booking).' balance is paid: Stripe fee '
                    .$this->formatMoney($balStripe, $booking).'.';
            }
        }

        $bt = $booking->booking_time;
        $dateLabel = $booking->booking_date?->format('D, d/m/Y') ?? '—';
        $timeLabel = ($bt && isset($bt['start'], $bt['end']))
            ? $this->to24h((string) $bt['start']).' – '.$this->to24h((string) $bt['end'])
            : '—';
        $duration = '—';
        if (is_array($bt) && isset($bt['duration_minutes'])) {
            $mins = (int) $bt['duration_minutes'];
            $hours = intdiv(max(1, $mins), 60);
            $tail = max(1, $mins) % 60;
            $duration = $mins < 60 ? $mins.' min' : ($tail ? $hours.'h '.$tail.'m' : $hours.'h');
        }

        $typeLabel = match (strtolower((string) ($booking->booking_type ?? ''))) {
            'custom' => 'Custom booking',
            'flash', 'design' => 'Design booking',
            'guest' => 'Guest spot booking',
            default => ucfirst((string) ($booking->booking_type ?: 'Booking')),
        };

        $imageUrl = null;
        if ($booking->tattoo && ! empty($booking->tattoo->image)) {
            $imgRaw = (string) $booking->tattoo->image;
            $imageUrl = str_starts_with($imgRaw, 'http://') || str_starts_with($imgRaw, 'https://')
                ? $imgRaw
                : asset(ltrim($imgRaw, '/'));
        }
        if ($imageUrl === null) {
            $qaImages = QuestionAnswerPresenter::allImageUrls($booking->questions_answers ?? []);
            if ($qaImages !== []) {
                $imageUrl = $qaImages[0];
            }
        }

        $details = is_array($booking->custom_tattoo_details) ? $booking->custom_tattoo_details : [];
        $customRequestId = (int) ($details['custom_request_id'] ?? 0);
        $customRequest = $customRequestId > 0
            ? CustomRequest::query()->find($customRequestId)
            : null;

        $qaSource = $customRequest?->questions_answers ?? $booking->questions_answers ?? [];
        $requestFields = $this->requestTabFields($qaSource);
        $answers = array_map(
            static fn (array $f) => ['q' => $f['label'], 'a' => $f['value']],
            $requestFields
        );

        $isGuest = $booking->isGuestCustomRequestBooking() || (bool) ($customRequest?->is_guest);
        $isPaymentLink = ! empty($details['payment_link_id']) || ! empty($details['from_studio_payment_link']);
        if ($isGuest) {
            $requestTitle = 'Guest spot request';
            $requestIcon = 'luggage';
        } elseif ($isPaymentLink && ! $customRequest) {
            $requestTitle = 'Payment link booking';
            $requestIcon = 'link';
        } elseif (in_array(strtolower((string) ($booking->booking_type ?? '')), ['flash', 'design'], true)) {
            $requestTitle = 'Design request';
            $requestIcon = 'palette';
        } else {
            $requestTitle = 'Custom tattoo request';
            $requestIcon = 'brush';
        }

        $sentAt = $customRequest?->created_at ?? $booking->created_at;
        $sentLabel = $sentAt?->timezone(config('app.timezone'))->format('d/m/Y');
        $requestRef = $customRequest
            ? $customRequest->referenceLabel()
            : $booking->referenceLabel();
        $requestMeta = $requestRef.($sentLabel ? ' · sent '.$sentLabel : '');

        $artistEmail = trim((string) ($booking->artist?->email ?? ''));

        return [
            'title' => $booking->displayTitle(),
            'reference' => $booking->referenceLabel(),
            'image_url' => $imageUrl,
            'status' => $statusLabel,
            'status_pill' => $statusPill,
            'type_label' => $typeLabel,
            'client' => $clientName,
            'artist' => $artistName,
            'artist_first' => $firstName,
            'service' => $booking->displayTitle(),
            'through' => $through,
            'deposit' => $this->formatMoney($deposit, $booking),
            'date' => $dateLabel,
            'time' => $timeLabel,
            'duration' => $duration,
            'timezone' => (string) ($booking->timezone ?: '—'),
            'balance' => $balanceDue > 0.009 ? $this->formatMoney($balanceDue, $booking) : '—',
            'money_pill' => (string) ($money['pill'] ?? 'Pending'),
            'money_pill_class' => (string) ($money['pill_class'] ?? 'a'),
            'deposit_money_label' => $depositMoneyLabel,
            'deposit_money' => $this->formatMoney($deposit, $booking),
            'booking_fee' => $clientFee > 0.009 ? $this->formatMoney($clientFee, $booking) : null,
            'booking_fee_note' => $clientFee > 0.009 ? 'paid by the client, goes to Bookpay' : null,
            'stripe_fee' => '−'.$this->formatMoney($stripeFee, $booking),
            'has_split' => $hasSplit,
            'split_value' => $splitValue,
            'artist_gets_label' => $firstName.' gets',
            'artist_gets_note' => 'after his part of the Stripe fee',
            'artist_gets' => $this->formatMoney($artistGets, $booking),
            'studio_share_label' => 'Studio share',
            'studio_share_note' => $hasSplit ? $studioPercent.'% of '.$this->formatMoney($deposit, $booking) : null,
            'studio_share' => $this->formatMoney($studioShare, $booking),
            'studio_stripe' => '−'.$this->formatMoney($studioStripe, $booking),
            'studio_stripe_note' => $hasSplit ? (string) $studioPercent.'%' : null,
            'studio_gets' => $this->formatMoney($studioGets, $booking),
            'footnote' => $footnote,
            'balance_note' => $balanceNote,
            'answers' => $answers,
            'request' => [
                'title' => $requestTitle,
                'icon' => $requestIcon,
                'meta' => $requestMeta,
                'fields' => $requestFields,
                'caption' => 'What the client sent. '.$firstName.' handles their questions and references.',
            ],
            'message_url' => $artistEmail !== '' ? 'mailto:'.$artistEmail : null,
            'message_label' => 'Message '.$firstName,
        ];
    }

    /**
     * Original-request tab fields (label above value, 2-column grid).
     *
     * @return list<array{label: string, value: string}>
     */
    private function requestTabFields(mixed $questionsAnswers): array
    {
        $fields = [];
        foreach (QuestionAnswerPresenter::rows($questionsAnswers) as $row) {
            $type = strtolower((string) ($row['type'] ?? ''));
            if (in_array($type, ['image', 'reference_image', 'placement_photo', 'coverup_photo'], true)) {
                continue;
            }
            $value = QuestionAnswerPresenter::formatAnswer($row['answer'] ?? null, $type);
            if ($value === '' || $value === '—') {
                continue;
            }
            // Skip pure image URL answers that slipped through without a type.
            if (QuestionAnswerPresenter::imageUrls($row['answer'] ?? null, $type) !== []) {
                continue;
            }
            $fields[] = [
                'label' => $this->shortRequestLabel((string) ($row['question'] ?? 'Answer')),
                'value' => $value,
            ];
        }

        return $fields;
    }

    private function shortRequestLabel(string $question): string
    {
        $q = strtolower(trim($question));
        if (str_contains($q, 'kind of tattoo')) {
            return 'Kind of tattoo';
        }
        if (str_contains($q, 'what style') || $q === 'style') {
            return 'Style';
        }
        if (str_contains($q, 'where on') || (str_contains($q, 'placement') && ! str_contains($q, 'photo'))) {
            return 'Where on the body';
        }
        if (str_contains($q, 'what size') || $q === 'size' || str_contains($q, 'how big')) {
            return 'Size';
        }
        if (str_contains($q, 'budget')) {
            return 'Budget';
        }
        if (str_contains($q, 'color preference') || $q === 'color') {
            return 'Color';
        }
        if (str_contains($q, 'reference')) {
            return 'References';
        }

        // Strip common question wrappers so .lbl uppercase stays readable.
        $clean = preg_replace('/^(what|where|how|do you|have you|please)\s+/i', '', trim($question)) ?: $question;
        $clean = rtrim($clean, '?');

        return $clean !== '' ? $clean : $question;
    }

    private function to24h(string $time): string
    {
        $time = trim($time);
        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return $time;
        }
    }

    private function formatMoney(float $amount, Booking $booking): string
    {
        $currency = strtoupper((string) ($booking->currency ?? 'EUR'));
        $formatted = number_format(max(0, round($amount, 2)), 2, '.', '');
        if ($currency === 'EUR') {
            return '€'.$formatted;
        }

        return $currency.' '.$formatted;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRequestCard(StudioRequest $req): array
    {
        $clientName = trim((string) ($req->client_name ?? ''));
        if ($clientName === '') {
            $u = $req->user;
            $clientName = trim(($u?->first_name ?? '').' '.($u?->last_name ?? ''))
                ?: (string) ($req->client_email ?? 'Client');
        }

        $answers = is_array($req->questions_answers) ? $req->questions_answers : [];
        $clientArtistChoice = trim((string) ($answers['_artist_choice'] ?? ''));
        if ($clientArtistChoice === '') {
            $clientArtistChoice = 'Any available artist';
        }

        $artistName = null;
        if ($req->assignedArtist) {
            $artistName = $req->assignedArtist->userDetail?->publicDisplayName()
                ?: trim(($req->assignedArtist->first_name ?? '').' '.($req->assignedArtist->last_name ?? ''));
        }

        $summary = $this->requestSummary($answers, $req->anything_else_notes);
        $hasCustom = ! empty($req->custom_request_id);
        $status = (string) $req->status;
        $sentBackReason = trim((string) ($req->reason_decline ?? ''));

        $statusKey = 'new';
        $statusLabel = 'New';
        $cta = null;
        $canWithdraw = $req->canStudioWithdraw();
        $rightHint = null;

        if ($status === StudioRequest::STATUS_SENT_BACK && ! $hasCustom) {
            $statusKey = 'sent_back';
            $sentBackBy = $req->latestSendBackArtistName() ?: $artistName;
            $statusLabel = $sentBackBy ? ('Sent back by '.$sentBackBy) : 'Sent back';
            $cta = 'Assign again';
        } elseif ($req->isAwaitingArtistDecision()) {
            $statusKey = 'waiting_artist';
            $statusLabel = 'Waiting for artist';
            $cta = 'Reassign';
            $rightHint = $artistName ? ('Offered to '.$artistName) : 'Awaiting accept';
        } elseif (! $hasCustom && ($status === StudioRequest::STATUS_PENDING || empty($req->assigned_artist_id))) {
            $statusKey = 'needs_artist';
            $statusLabel = 'Needs an artist';
            $cta = 'Assign artist';
        } else {
            $crStatus = strtolower((string) ($req->customRequest?->status ?? ''));
            if (in_array($crStatus, ['quote_sent', 'quoted', 'confirmed'], true)) {
                $statusKey = 'quote_sent';
                $statusLabel = 'Quote sent';
            } else {
                $statusKey = 'accepted';
                $statusLabel = 'Accepted';
            }
            $rightHint = 'The artist replies';
        }

        $sentLabel = $req->created_at?->format('d/m') ?? '';
        if ($statusKey === 'needs_artist') {
            $prefLabel = $clientArtistChoice === 'Any available artist'
                ? 'Any available artist'
                : 'Client chose '.$clientArtistChoice;
            $toLine = $prefLabel
                .($summary !== '' ? ' · '.$summary : '')
                .($sentLabel !== '' ? ' · sent '.$sentLabel : '');
        } elseif ($statusKey === 'sent_back') {
            $toLine = ($sentBackReason !== '' ? '"'.$sentBackReason.'" · ' : '')
                .($summary !== '' ? $summary : 'Waiting for a new artist');
        } elseif ($artistName) {
            $toLine = 'To '.$artistName.($summary !== '' ? ' · '.$summary : '');
        } else {
            $toLine = $summary !== '' ? $summary : 'Studio request · awaiting artist';
        }

        $preselectArtistId = (int) ($req->assigned_artist_id ?? 0);
        if ($preselectArtistId <= 0
            && $clientArtistChoice !== ''
            && $clientArtistChoice !== 'Any available artist'
            && $req->studio_id
        ) {
            $preselectArtistId = $this->resolveStudioArtistIdByName((int) $req->studio_id, $clientArtistChoice);
        }

        $images = $this->requestImageUrls($req);

        $idea = $summary !== '' ? $summary : 'Studio tattoo request';
        if ($images !== []) {
            $idea .= ' · '.QuestionAnswerPresenter::formatPhotoLabels(count($images)).'.';
        }

        return [
            'id' => $req->id,
            'client' => $clientName,
            'email' => (string) ($req->client_email ?? $req->user?->email ?? ''),
            'phone' => (string) ($req->client_phone ?? ''),
            'notes' => (string) ($req->anything_else_notes ?? ''),
            'artist_name' => $artistName ?: '',
            'client_artist_choice' => $clientArtistChoice,
            'idea' => $idea,
            'created_label' => $req->created_at?->format('d/m/Y · H:i') ?? '',
            'images' => $images,
            'initials' => $this->initials($clientName),
            'type_label' => 'Studio request',
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'status_pill' => match ($statusKey) {
                'needs_artist', 'waiting_artist' => 'a',
                'sent_back' => 'r',
                'quote_sent' => 'b',
                'accepted' => 'p',
                default => 'p',
            },
            'card_border' => match ($statusKey) {
                'needs_artist' => '#F3C98B',
                'sent_back' => '#F5C2C2',
                'waiting_artist' => '#E8DFEA',
                default => null,
            },
            'subtitle' => $toLine,
            'cta' => $cta,
            'can_withdraw' => $canWithdraw,
            'right_hint' => $rightHint,
            'preselect_artist_id' => $preselectArtistId,
            'answers' => $this->flattenAnswersForModal($answers),
            'send_back_history' => $this->formatSendBackHistory($req),
        ];
    }

    private function resolveStudioArtistIdByName(int $studioId, string $artistName): int
    {
        $needle = mb_strtolower(trim($artistName));
        if ($needle === '' || $studioId <= 0 || ! UserStudio::tableReady()) {
            return 0;
        }

        $links = UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studioId)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->get();

        foreach ($links as $link) {
            $user = $link->user;
            $detail = $user?->userDetail;
            if (! $user || ! $detail) {
                continue;
            }
            $name = trim((string) ($detail->publicDisplayName() ?: ''));
            if ($name === '' || $name === 'Artist') {
                $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            }
            if ($name !== '' && mb_strtolower($name) === $needle) {
                return (int) $user->id;
            }
        }

        return 0;
    }

    /**
     * @return list<array{action:string,action_label:string,artist_id:?int,artist_name:?string,actor_name:string,reason:?string,at:string,at_label:string}>
     */
    private function formatSendBackHistory(StudioRequest $req): array
    {
        $raw = is_array($req->send_back_history) ? $req->send_back_history : [];
        $out = [];
        foreach ($raw as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $at = trim((string) ($entry['at'] ?? ''));
            $atLabel = '—';
            if ($at !== '') {
                try {
                    $atLabel = \Carbon\Carbon::parse($at)->format('d/m/Y · H:i');
                } catch (\Throwable) {
                    $atLabel = $at;
                }
            }
            $action = (string) ($entry['action'] ?? 'artist_send_back');
            $actor = trim((string) ($entry['actor_name'] ?? $entry['artist_name'] ?? ''));
            if ($actor === '') {
                $actor = $action === 'studio_withdraw' ? 'Studio' : 'Artist';
            }
            $out[] = [
                'action' => $action,
                'action_label' => $action === 'studio_withdraw' ? 'Withdrawn by studio' : 'Sent back by artist',
                'artist_id' => isset($entry['artist_id']) ? (int) $entry['artist_id'] : null,
                'artist_name' => isset($entry['artist_name']) && trim((string) $entry['artist_name']) !== ''
                    ? trim((string) $entry['artist_name'])
                    : null,
                'actor_name' => $actor,
                'reason' => isset($entry['reason']) && trim((string) $entry['reason']) !== ''
                    ? trim((string) $entry['reason'])
                    : null,
                'at' => $at,
                'at_label' => $atLabel,
            ];
        }

        return $out;
    }

    private function bookedThroughLabel(Booking $booking): string
    {
        if ($booking->guest_spot_id) {
            return 'Guest spot';
        }
        $details = is_array($booking->custom_tattoo_details) ? $booking->custom_tattoo_details : [];
        if (! empty($details['payment_link_id']) || ! empty($details['from_studio_payment_link'])) {
            return 'Studio (New booking)';
        }
        if (! empty($details['from_studio_page']) || ! empty($details['studio_page'])) {
            return 'Studio page';
        }

        return 'Artist link';
    }

    private function studioShareLabel(Booking $booking): string
    {
        $type = strtolower((string) ($booking->payout_payment_type ?? ''));
        $artistPct = $booking->payout_artist_percent;
        $paid = (float) ($booking->total_amount_paid ?? $booking->deposit_amount ?? 0);

        if ($type === 'rent' || $type === 'direct' || $artistPct === null) {
            return 'Direct';
        }

        $studioPct = max(0, 100 - (int) $artistPct);
        if ($paid <= 0) {
            return '€0 so far';
        }

        $share = round($paid * ($studioPct / 100), 0);
        $currency = strtoupper((string) ($booking->currency ?? 'EUR'));
        if ($currency === 'EUR') {
            return '€'.$share;
        }

        return $currency.' '.$share;
    }

    /**
     * Collect reference image URLs from reference_images + question answers + portfolio piece.
     *
     * @return list<string>
     */
    private function requestImageUrls(StudioRequest $req): array
    {
        $images = [];
        foreach ((array) ($req->reference_images ?? []) as $img) {
            $path = is_string($img) ? trim($img) : trim((string) ($img['url'] ?? $img['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $images[] = str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
        }

        foreach (QuestionAnswerPresenter::allImageUrls($req->questions_answers ?? []) as $url) {
            $path = trim((string) $url);
            if ($path === '') {
                continue;
            }
            $images[] = str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
        }

        // Fallback: always include the studio portfolio piece that started the request.
        $answers = is_array($req->questions_answers) ? $req->questions_answers : [];
        $portfolioId = (int) ($answers['_studio_portfolio_id'] ?? 0);
        if ($portfolioId <= 0 && (string) ($req->source ?? '') === 'studio_portfolio') {
            if (preg_match('/studio_portfolio=(\d+)/', (string) ($req->source_path ?? ''), $m)) {
                $portfolioId = (int) $m[1];
            }
        }
        if ($portfolioId > 0) {
            $piece = StudioPortfolio::query()
                ->whereKey($portfolioId)
                ->where('studio_id', (int) $req->studio_id)
                ->first();
            $imageRaw = trim((string) ($piece?->image ?? ''));
            if ($imageRaw !== '') {
                $images[] = str_starts_with($imageRaw, 'http://') || str_starts_with($imageRaw, 'https://')
                    ? $imageRaw
                    : asset(ltrim($imageRaw, '/'));
            }
        }

        return array_values(array_unique($images));
    }

    private function requestSummary(array $answers, ?string $notes): string
    {
        $bits = [];
        foreach ($this->publicAnswerRows($answers) as $row) {
            $type = (string) ($row['type'] ?? '');
            if (QuestionAnswerPresenter::imageUrls($row['answer'] ?? null, $type) !== []) {
                continue;
            }
            $answer = QuestionAnswerPresenter::formatAnswer($row['answer'] ?? null, $type);
            $answer = trim((string) $answer);
            if ($answer === '' || $answer === '—' || strcasecmp($answer, 'other') === 0) {
                continue;
            }
            $bits[] = $answer;
            if (count($bits) >= 3) {
                break;
            }
        }
        if ($bits === [] && $notes) {
            $bits[] = \Illuminate\Support\Str::limit(trim($notes), 60);
        }

        return implode(', ', $bits);
    }

    /**
     * Text answers only — images are rendered as thumbnails via `images`.
     *
     * @return list<array{q:string,a:string}>
     */
    private function flattenAnswersForModal(array $answers): array
    {
        $out = [];
        foreach ($this->publicAnswerRows($answers) as $row) {
            $type = (string) ($row['type'] ?? '');
            // Keep images out of the text answers list (shown in Reference images grid).
            if (QuestionAnswerPresenter::imageUrls($row['answer'] ?? null, $type) !== []) {
                continue;
            }
            $q = trim((string) ($row['question'] ?? ''));
            $a = trim(QuestionAnswerPresenter::formatAnswer($row['answer'] ?? null, $type));
            if ($q === '' || $a === '' || $a === '—') {
                continue;
            }
            $out[] = ['q' => $q, 'a' => $a];
        }

        return $out;
    }

    /**
     * @return list<array{question: string, type: string, answer: mixed, images: array<int, string>}>
     */
    private function publicAnswerRows(array $answers): array
    {
        $public = [];
        foreach ($answers as $key => $value) {
            if (is_string($key) && str_starts_with($key, '_')) {
                continue;
            }
            $public[$key] = $value;
        }

        return QuestionAnswerPresenter::rows($public);
    }

    private function shortName(string $full): string
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        if (count($parts) < 2) {
            return $full;
        }
        $last = $parts[count($parts) - 1];

        return $parts[0].' '.mb_strtoupper(mb_substr($last, 0, 1)).'.';
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $a = mb_strtoupper(mb_substr($parts[0] ?? 'C', 0, 1));
        $b = mb_strtoupper(mb_substr($parts[1] ?? ($parts[0] ?? 'L'), 0, 1));

        return $a.$b;
    }
}
