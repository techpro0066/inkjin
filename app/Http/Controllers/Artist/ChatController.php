<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\ChatChannel;
use App\Services\ArtistPayoutService;
use App\Services\StreamChatService;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(StreamChatService $streamChat, ArtistPayoutService $payoutService): View
    {
        $user = auth()->user();
        $userDetail = $user?->userDetail;
        $canOfferSlots = $userDetail
            ? $payoutService->canAcceptClientPayments($userDetail)
            : false;
        $quotesBlockedMessage = $canOfferSlots || ! $userDetail
            ? null
            : $payoutService->clientPaymentsBlockedMessage($userDetail);

        return view('artist.chat.index', [
            'role' => 'artist',
            'streamConfigured' => $streamChat->isConfigured(),
            'hasOpenBooking' => $streamChat->userHasAnyOpenBooking($user),
            'hasConversations' => ChatChannel::query()->forUser($user->id)->exists(),
            // Empty maps — Inbox loads a single request via panel-data when opening the modal.
            'requestsPayload' => [],
            'customRequestsPayload' => [],
            'studioOffersPayload' => [],
            'canOfferSlots' => $canOfferSlots,
            'canSendQuotes' => $canOfferSlots,
            'quotesBlockedMessage' => $quotesBlockedMessage,
            'canCreatePaymentLinks' => $canOfferSlots,
            'isAutoScheduling' => ($userDetail?->scheduling_type ?? '') === 'auto',
            'paymentLinksBlockedMessage' => $quotesBlockedMessage,
            'currency' => '€',
        ]);
    }
}
