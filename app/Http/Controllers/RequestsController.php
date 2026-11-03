<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferArtistSlotsRequest;
use App\Mail\ManagedBookingDeclinedArtistMail;
use App\Mail\ManagedBookingDeclinedUserMail;
use App\Mail\ManagedBookingSlotsOfferedArtistMail;
use App\Mail\ManagedBookingSlotsOfferedUserMail;
use App\Models\BalanceCollection;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Models\PaymentLink;
use App\Models\StudioRequest;
use App\Services\ArtistPayoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RequestsController extends Controller
{
    public function index(Request $request)
    {
        $artistId = (int) Auth::id();

        $designRequests = BookingRequest::query()
            ->with(['user', 'tattoo'])
            ->where('artist_id', $artistId)
            ->orderByDesc('created_at')
            ->get();

        $allCustomAndGuestRequests = CustomRequest::query()
            ->with(['user', 'guestSpot'])
            ->where('artist_id', $artistId)
            ->orderByDesc('created_at')
            ->get();

        $openCustomId = (int) $request->query('open', 0);

        // Held waitlist requests stay off the Requests feed until the artist notifies them,
        // but remain in the payload so ?open=ID from the waitlist tab still works.
        $customAndGuestRequests = $allCustomAndGuestRequests
            ->filter(function (CustomRequest $customRequest) use ($openCustomId) {
                if (! $customRequest->isHeldOnWaitlist()) {
                    return true;
                }

                return $openCustomId > 0 && (int) $customRequest->id === $openCustomId;
            })
            ->values();

        $visibleCustomAndGuestRequests = $allCustomAndGuestRequests
            ->reject(fn (CustomRequest $customRequest) => $customRequest->isHeldOnWaitlist())
            ->values();

        $customRequests = $visibleCustomAndGuestRequests->where('is_guest', false)->values();
        $guestRequests = $visibleCustomAndGuestRequests->where('is_guest', true)->values();

        $requestsPayload = $designRequests
            ->map(fn (BookingRequest $bookingRequest) => $bookingRequest->toArtistPanelArray())
            ->values()
            ->all();

        $studioByCustomId = StudioRequest::query()
            ->with('studio')
            ->whereIn('custom_request_id', $allCustomAndGuestRequests->pluck('id')->filter()->all() ?: [0])
            ->get()
            ->keyBy(fn (StudioRequest $r) => (int) $r->custom_request_id);

        $customRequestsPayload = $customAndGuestRequests
            ->map(function (CustomRequest $customRequest) use ($studioByCustomId) {
                $payload = $customRequest->toArtistPanelArray();
                $linked = $studioByCustomId->get((int) $customRequest->id);

                return array_merge($payload, $this->studioLinkMeta($customRequest, $linked));
            })
            ->values()
            ->all();

        $guestRequestsPayload = $guestRequests
            ->map(function (CustomRequest $customRequest) use ($studioByCustomId) {
                $payload = $customRequest->toArtistPanelArray();
                $linked = $studioByCustomId->get((int) $customRequest->id);

                return array_merge($payload, $this->studioLinkMeta($customRequest, $linked));
            })
            ->values()
            ->all();

        $studioOffers = StudioRequest::query()
            ->with(['studio', 'user'])
            ->where('assigned_artist_id', $artistId)
            ->where('status', StudioRequest::STATUS_ASSIGNED)
            ->whereNull('custom_request_id')
            ->orderByDesc('updated_at')
            ->get();

        $studioOffersPayload = $studioOffers
            ->map(fn (StudioRequest $offer) => $this->studioOfferPayload($offer))
            ->values()
            ->all();

        $feed = collect();

        foreach ($designRequests as $bookingRequest) {
            $feed->push($this->normalizeDesignFeedItem($bookingRequest));
        }

        foreach ($visibleCustomAndGuestRequests as $customRequest) {
            $linkedStudio = $studioByCustomId->get((int) $customRequest->id);
            $feed->push($this->normalizeCustomFeedItem($customRequest, $linkedStudio));
        }

        foreach ($studioOffers as $offer) {
            $feed->push($this->normalizeStudioOfferFeedItem($offer));
        }

        $feed = $feed->sortByDesc('dateSort')->values();

        $typeCounts = [
            'all' => $feed->count(),
            'design' => $feed->where('kind', 'design')->count(),
            'custom' => $feed->where('kind', 'custom')->count() + $feed->where('kind', 'studio_offer')->count(),
            'guest' => $feed->where('kind', 'guest')->count(),
        ];

        $statusCounts = [
            'new' => $feed->where('statusKey', 'new')->count(),
            'quote_sent' => $feed->where('statusKey', 'quote_sent')->count(),
            'booked' => $feed->where('statusKey', 'booked')->count(),
            'declined' => $feed->where('statusKey', 'declined')->count(),
        ];

        $needsReplyCount = $feed->where('needsReply', true)->count();
        $repliedCount = $feed->where('needsReply', false)->count();

        $userDetail = Auth::user()?->userDetail;
        $payoutService = app(ArtistPayoutService::class);
        $canOfferSlots = $userDetail
            ? $payoutService->canAcceptClientPayments($userDetail)
            : false;
        $canSendQuotes = $canOfferSlots;
        $quotesBlockedMessage = $canOfferSlots || ! $userDetail
            ? null
            : $payoutService->clientPaymentsBlockedMessage($userDetail);
        $canCreatePaymentLinks = $canOfferSlots;
        $isAutoScheduling = ($userDetail?->scheduling_type ?? '') === 'auto';
        $paymentLinksBlockedMessage = $quotesBlockedMessage;
        $currency = '€';

        $today = Carbon::today()->toDateString();
        $bookingsTotalCount = Booking::query()
            ->where('artist_user_id', $artistId)
            ->where(function ($q) use ($today) {
                $q->whereIn('status', ['pending', 'cancelled', 'declined', 'completed', 'no_show'])
                    ->orWhere(function ($q2) use ($today) {
                        $q2->where('status', 'confirmed');
                    });
            })
            ->count();

        $studioOfferCount = StudioRequest::query()
            ->where('assigned_artist_id', $artistId)
            ->where('status', StudioRequest::STATUS_ASSIGNED)
            ->whereNull('custom_request_id')
            ->count();

        $requestsCount = BookingRequest::query()
            ->where('artist_id', $artistId)
            ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
            ->count()
            + CustomRequest::query()
                ->where('artist_id', $artistId)
                ->visibleInArtistRequests()
                ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                ->count()
            + $studioOfferCount;

        $paymentLinksCount = PaymentLink::query()
            ->where('artist_id', $artistId)
            ->count()
            + BalanceCollection::query()
                ->where('artist_user_id', $artistId)
                ->where('collection_type', BalanceCollection::TYPE_PAYMENT_LINK)
                ->whereNotNull('payment_link_code')
                ->where('payment_link_code', '!=', '')
                ->count();

        $initialType = strtolower(trim((string) $request->query('type', 'all')));
        if (! in_array($initialType, ['all', 'design', 'custom', 'guest'], true)) {
            $initialType = 'all';
        }

        return view('artist.requests.index', [
            'requests' => $designRequests,
            'customRequests' => $customRequests,
            'guestRequests' => $guestRequests,
            'feed' => $feed,
            'typeCounts' => $typeCounts,
            'statusCounts' => $statusCounts,
            'needsReplyCount' => $needsReplyCount,
            'repliedCount' => $repliedCount,
            'requestsPayload' => $requestsPayload,
            'customRequestsPayload' => $customRequestsPayload,
            'guestRequestsPayload' => $guestRequestsPayload,
            'studioOffersPayload' => $studioOffersPayload,
            'canOfferSlots' => $canOfferSlots,
            'canSendQuotes' => $canSendQuotes,
            'quotesBlockedMessage' => $quotesBlockedMessage,
            'canCreatePaymentLinks' => $canCreatePaymentLinks,
            'isAutoScheduling' => $isAutoScheduling,
            'paymentLinksBlockedMessage' => $paymentLinksBlockedMessage,
            'currency' => $currency,
            'requestsCount' => $requestsCount,
            'bookingsTotalCount' => $bookingsTotalCount,
            'paymentLinksCount' => $paymentLinksCount,
            'initialType' => $initialType,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeDesignFeedItem(BookingRequest $request): array
    {
        [$statusKey, $statusLabel] = $this->mapDesignStatus($request);
        $isGuest = $request->isGuestSpotRequest();
        $parts = array_filter([
            (string) ($request->tattoo?->title ?? 'Design'),
            $request->designStyleLabel() !== '—' ? $request->designStyleLabel() : null,
            $request->placementLabel() !== '—' ? $request->placementLabel() : null,
            $request->priceLabel() !== '—' ? $request->priceLabel() : null,
        ]);

        if ($isGuest) {
            $spot = $request->guestSpot();
            $location = $spot
                ? trim(implode(', ', array_filter([(string) $spot->city, (string) $spot->country])))
                : '';
            array_unshift($parts, 'Guest spot'.($location !== '' ? ' · '.$location : ''));
        }

        return [
            'kind' => $isGuest ? 'guest' : 'design',
            'openAs' => 'design',
            'id' => $request->id,
            'clientName' => $request->clientArtistFacingName(),
            'clientInitials' => $request->clientInitials(),
            'typeLabel' => $isGuest ? 'Guest spot' : 'Design',
            'statusKey' => $statusKey,
            'statusLabel' => $statusLabel,
            'reference' => $request->referenceLabel(),
            'subtitle' => implode(' · ', $parts),
            'dateLabel' => $request->created_at?->format('M j') ?? '—',
            'dateSort' => $request->created_at?->format('Y-m-d H:i:s') ?? '',
            'searchText' => strtolower(trim($request->clientSearchKey().' '.$request->referenceLabel())),
            'chatUrl' => $request->artistChatUrl(),
            'needsReply' => $request->status === 'pending',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeStudioOfferFeedItem(StudioRequest $request): array
    {
        $studioName = trim((string) ($request->studio?->name ?? 'Studio'));
        $clientName = trim((string) ($request->client_name ?? ''));
        if ($clientName === '') {
            $u = $request->user;
            $clientName = trim(($u?->first_name ?? '').' '.($u?->last_name ?? ''))
                ?: (string) ($request->client_email ?? 'Client');
        }
        $parts = preg_split('/\s+/', $clientName) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? 'C', 0, 1).mb_substr($parts[1] ?? ($parts[0] ?? 'L'), 0, 1));

        $answers = is_array($request->questions_answers) ? $request->questions_answers : [];
        $summaryBits = [];
        $answerCount = 0;
        foreach ($answers as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }
            $answerCount++;
            $answer = is_array($value) ? ($value['answer'] ?? null) : $value;
            if (is_array($answer)) {
                $answer = implode(', ', array_filter(array_map('strval', $answer)));
            }
            $answer = trim((string) $answer);
            if ($answer === '') {
                continue;
            }
            $summaryBits[] = $answer;
            if (count($summaryBits) >= 3) {
                break;
            }
        }
        $summary = implode(', ', $summaryBits);
        $studioNote = trim((string) ($answers['_studio_assign_note'] ?? ''));

        $subtitleParts = array_filter([
            'Assigned by '.$studioName.($studioNote !== '' ? ': "'.$studioNote.'"' : ''),
            $summary !== '' ? $summary : null,
            $answerCount > 0 ? $answerCount.' '.($answerCount === 1 ? 'answer' : 'answers') : null,
        ]);

        return [
            'kind' => 'studio_offer',
            'openAs' => 'studio_offer',
            'id' => $request->id,
            'clientName' => $clientName,
            'clientInitials' => $initials,
            'typeLabel' => 'Custom',
            'statusKey' => 'new',
            'statusLabel' => 'New request',
            'viaStudio' => $studioName,
            'canSendBack' => true,
            'sendBackUrl' => route('artist.studio-offers.send-back', $request),
            'reference' => '#SR-'.str_pad((string) $request->id, 6, '0', STR_PAD_LEFT),
            'subtitle' => implode(' · ', $subtitleParts),
            'dateLabel' => $request->updated_at?->format('M j') ?? $request->created_at?->format('M j') ?? '—',
            'dateSort' => ($request->updated_at ?? $request->created_at)?->format('Y-m-d H:i:s') ?? '',
            'searchText' => strtolower(trim($clientName.' '.$studioName.' '.$summary)),
            'chatUrl' => '',
            'needsReply' => true,
        ];
    }

    /**
     * Panel payload shaped like CustomRequest::toArtistPanelArray() so the quote modal can open directly.
     *
     * @return array<string, mixed>
     */
    private function studioOfferPayload(StudioRequest $request): array
    {
        $studioName = trim((string) ($request->studio?->name ?? 'Studio'));
        $clientName = trim((string) ($request->client_name ?? ''));
        if ($clientName === '') {
            $u = $request->user;
            $clientName = trim(($u?->first_name ?? '').' '.($u?->last_name ?? ''))
                ?: (string) ($request->client_email ?? 'Client');
        }
        $parts = preg_split('/\s+/', $clientName) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? 'C', 0, 1).mb_substr($parts[1] ?? ($parts[0] ?? 'L'), 0, 1));

        $questionsAnswers = [];
        foreach ((array) ($request->questions_answers ?? []) as $key => $value) {
            if ($key === '_contact' || str_starts_with((string) $key, '_')) {
                continue;
            }
            if (is_array($value) && isset($value['question'])) {
                $questionsAnswers[] = $value;

                continue;
            }
            $q = is_array($value) ? (string) ($value['question'] ?? $key) : (string) $key;
            $a = is_array($value) ? ($value['answer'] ?? '') : $value;
            if (is_array($a)) {
                $a = implode(', ', array_filter(array_map('strval', $a)));
            }
            $a = trim((string) $a);
            if ($a === '') {
                continue;
            }
            $questionsAnswers[] = ['question' => $q, 'answer' => $a];
        }

        $qa = is_array($request->questions_answers) ? $request->questions_answers : [];
        $studioNote = trim((string) ($qa['_studio_assign_note'] ?? ''));

        $images = [];
        foreach ((array) ($request->reference_images ?? []) as $img) {
            $path = is_string($img) ? trim($img) : trim((string) ($img['url'] ?? $img['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $images[] = str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
        }
        foreach (\App\Support\QuestionAnswerPresenter::allImageUrls($request->questions_answers ?? []) as $url) {
            $path = trim((string) $url);
            if ($path === '') {
                continue;
            }
            $images[] = str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
        }
        $images = array_values(array_unique($images));

        $referral = null;
        $contact = $qa['_contact'] ?? null;
        if (is_array($contact)) {
            $referral = trim((string) ($contact['referral_source'] ?? '')) ?: null;
        }

        return [
            'id' => 'studio:'.$request->id,
            'studioOfferId' => $request->id,
            'isStudioOffer' => true,
            'reference' => '#SR-'.str_pad((string) $request->id, 6, '0', STR_PAD_LEFT),
            'status' => 'pending',
            'filterStatus' => 'New request',
            'statusBadgeClass' => 'bg-amber-100 text-amber-800',
            'clientName' => $clientName,
            'clientInitials' => $initials,
            'clientEmail' => (string) ($request->client_email ?? ''),
            'clientPhone' => (string) ($request->client_phone ?? ''),
            'clientSearch' => strtolower($clientName),
            'submittedAt' => $request->created_at?->format('M j, Y') ?? '—',
            'submittedIso' => $request->created_at?->format('Y-m-d') ?? '',
            'additionalNotes' => trim((string) ($request->anything_else_notes ?? '')) ?: '—',
            'referralSource' => $referral ?? '—',
            'type' => 'managed',
            'isGuest' => false,
            'guestSpot' => null,
            'guestHoldExpiresAt' => null,
            'guestHoldActive' => false,
            'schedulingLabel' => 'Managed scheduling',
            'availabilityDetails' => $request->availabilityStructured(),
            'artistSessionSlots' => [],
            'questionsAnswers' => $questionsAnswers,
            'reasonDecline' => null,
            'estimatedPrice' => null,
            'estimatedTime' => null,
            'numberOfSessions' => null,
            'messageForClient' => null,
            'isPending' => true,
            'canDecline' => false,
            'isBooked' => false,
            'chatUrl' => '',
            'viaStudio' => $studioName,
            'studioNote' => $studioNote,
            'canSendBack' => true,
            'sendBackUrl' => route('artist.studio-offers.send-back', $request),
            'sendQuoteUrl' => route('artist.studio-offers.send-quote', $request),
            'studioRequestId' => $request->id,
            'images' => $images,
        ];
    }

    /**
     * @return array{viaStudio: ?string, canSendBack: bool, sendBackUrl: ?string, studioRequestId: ?int, studioNote: string}
     */
    private function studioLinkMeta(CustomRequest $request, ?StudioRequest $linkedStudio = null): array
    {
        $studioName = trim((string) ($linkedStudio?->studio?->name ?? ''));
        $studioNote = '';
        if ($linkedStudio) {
            $qa = is_array($linkedStudio->questions_answers) ? $linkedStudio->questions_answers : [];
            $studioNote = trim((string) ($qa['_studio_assign_note'] ?? ''));
        }

        $canSendBack = $linkedStudio
            && $request->status === 'pending'
            && in_array($linkedStudio->status, [
                StudioRequest::STATUS_MOVED_TO_CUSTOM,
                StudioRequest::STATUS_ASSIGNED,
            ], true);

        return [
            'viaStudio' => $studioName !== '' ? $studioName : null,
            'canSendBack' => (bool) $canSendBack,
            'sendBackUrl' => $canSendBack
                ? route('artist.studio-offers.send-back', $linkedStudio)
                : null,
            'studioRequestId' => $linkedStudio?->id,
            'studioNote' => $studioNote,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCustomFeedItem(CustomRequest $request, ?StudioRequest $linkedStudio = null): array
    {
        $isGuest = $request->isGuestRequest();
        [$statusKey, $statusLabel] = $this->mapCustomStatus($request);
        $answerCount = count($request->formattedQuestionAnswers());
        $imageCount = $request->attachedImageCount();
        $meta = $this->studioLinkMeta($request, $linkedStudio);
        $studioName = (string) ($meta['viaStudio'] ?? '');
        $studioNote = (string) ($meta['studioNote'] ?? '');

        if ($isGuest) {
            $spot = $request->guestSpot;
            $location = $spot
                ? trim(implode(', ', array_filter([(string) $spot->city, (string) $spot->country])))
                : '';
            $parts = array_filter([
                'Guest spot request',
                $location !== '' ? $location : null,
                $answerCount > 0 ? $answerCount.' '.($answerCount === 1 ? 'answer' : 'answers') : null,
            ]);
        } elseif ($studioName !== '') {
            $parts = array_filter([
                'Assigned by '.$studioName.($studioNote !== '' ? ': "'.$studioNote.'"' : ''),
                $answerCount > 0 ? $answerCount.' '.($answerCount === 1 ? 'answer' : 'answers') : null,
                $imageCount > 0 ? $imageCount.' '.($imageCount === 1 ? 'picture' : 'pictures') : null,
            ]);
        } else {
            $parts = array_filter([
                'Custom tattoo request',
                $answerCount > 0 ? $answerCount.' '.($answerCount === 1 ? 'answer' : 'answers') : null,
                $imageCount > 0 ? $imageCount.' '.($imageCount === 1 ? 'picture' : 'pictures') : null,
            ]);
        }

        return [
            'kind' => $isGuest ? 'guest' : 'custom',
            'openAs' => 'custom',
            'id' => $request->id,
            'clientName' => $request->clientArtistFacingName(),
            'clientInitials' => $request->clientInitials(),
            'typeLabel' => $isGuest ? 'Guest spot' : 'Custom',
            'statusKey' => $statusKey,
            'statusLabel' => $statusLabel,
            'viaStudio' => $meta['viaStudio'],
            'canSendBack' => $meta['canSendBack'],
            'sendBackUrl' => $meta['sendBackUrl'],
            'studioRequestId' => $meta['studioRequestId'],
            'reference' => $request->referenceLabel(),
            'subtitle' => implode(' · ', $parts),
            'dateLabel' => $request->created_at?->format('M j') ?? '—',
            'dateSort' => $request->created_at?->format('Y-m-d H:i:s') ?? '',
            'searchText' => strtolower(trim($request->clientSearchKey().' '.$request->referenceLabel().' '.$studioName)),
            'chatUrl' => $request->artistChatUrl(),
            'needsReply' => $request->status === 'pending',
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function mapDesignStatus(BookingRequest $request): array
    {
        return match ($request->status) {
            'confirmed' => ['quote_sent', 'Quote sent'],
            'moved_to_booking' => ['booked', 'Booked'],
            'cancelled' => ['declined', 'Declined'],
            default => ['new', 'New request'],
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function mapCustomStatus(CustomRequest $request): array
    {
        return match ($request->status) {
            'confirmed' => ['quote_sent', 'Quote sent'],
            'moved_to_booking' => ['booked', 'Moved to booking'],
            'cancelled' => ['declined', 'Declined'],
            default => ['new', 'New request'],
        };
    }

    public function decline(Request $request, BookingRequest $bookingRequest)
    {
        if ((int) $bookingRequest->artist_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($bookingRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be declined.',
            ], 422);
        }

        $validated = $request->validate([
            'reason_decline' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $bookingRequest->update([
            'status' => 'cancelled',
            'reason_decline' => $validated['reason_decline'],
        ]);

        $bookingRequest->refresh();
        $bookingRequest->load(['user', 'tattoo', 'artist', 'chatChannel']);

        if ($bookingRequest->chatChannel) {
            app(\App\Services\StreamChatService::class)->syncChannelFreezeState($bookingRequest->chatChannel);
        }

        $this->sendDeclineEmails($bookingRequest);

        return response()->json([
            'success' => true,
            'message' => 'Request declined successfully.',
            'request' => $bookingRequest->toArtistPanelArray(),
        ]);
    }

    public function offerSlots(OfferArtistSlotsRequest $request, BookingRequest $bookingRequest)
    {
        if ((int) $bookingRequest->artist_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($bookingRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be updated with offered times.',
            ], 422);
        }

        $userDetail = Auth::user()?->userDetail;
        $payoutService = app(ArtistPayoutService::class);
        if ($userDetail && ! $payoutService->canAcceptClientPayments($userDetail)) {
            return response()->json([
                'success' => false,
                'message' => $payoutService->clientPaymentsBlockedMessage($userDetail),
            ], 422);
        }

        $payload = $request->normalizedPayload();

        $bookingRequest->update(array_merge($payload, [
            'status' => 'confirmed',
        ]));

        $bookingRequest->refresh();
        $bookingRequest->load(['user', 'tattoo', 'artist']);

        $this->sendSlotsOfferedEmails($bookingRequest);

        return response()->json([
            'success' => true,
            'message' => 'Offered times saved. The client can review your suggestions.',
            'request' => $bookingRequest->toArtistPanelArray(),
        ]);
    }

    private function sendDeclineEmails(BookingRequest $bookingRequest): void
    {
        $userRequestsUrl = route('user.requests.index');
        $artistRequestsUrl = route('artist.requests.index');

        $clientEmail = trim((string) ($bookingRequest->user?->email ?? ''));
        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new ManagedBookingDeclinedUserMail(
                    $bookingRequest,
                    $userRequestsUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send booking request declined email to client', [
                    'booking_request_id' => $bookingRequest->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $artistEmail = trim((string) ($bookingRequest->artist?->email ?? ''));
        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new ManagedBookingDeclinedArtistMail(
                    $bookingRequest,
                    $artistRequestsUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send booking request declined email to artist', [
                    'booking_request_id' => $bookingRequest->id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendSlotsOfferedEmails(BookingRequest $bookingRequest): void
    {
        $chooseTimesUrl = route('user.requests.confirm-times', ['bookingRequest' => $bookingRequest]);
        $userRequestsUrl = route('user.requests.index');
        $artistRequestsUrl = route('artist.requests.index');

        $clientEmail = trim((string) ($bookingRequest->user?->email ?? ''));
        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new ManagedBookingSlotsOfferedUserMail(
                    $bookingRequest,
                    $chooseTimesUrl,
                    $userRequestsUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send booking request slots-offered email to client', [
                    'booking_request_id' => $bookingRequest->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $artistEmail = trim((string) ($bookingRequest->artist?->email ?? ''));
        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new ManagedBookingSlotsOfferedArtistMail(
                    $bookingRequest,
                    $artistRequestsUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send booking request slots-offered email to artist', [
                    'booking_request_id' => $bookingRequest->id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
