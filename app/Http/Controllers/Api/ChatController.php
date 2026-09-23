<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\ChatChannel;
use App\Models\CustomRequest;
use App\Models\User;
use App\Services\StreamChatService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ChatController extends Controller
{
    public function __construct(private StreamChatService $streamChat) {}

    public function token(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->streamChat->isConfigured()) {
            return response()->json([
                'message' => 'Chat is not configured. Add STREAM_API_KEY and STREAM_API_SECRET to your environment.',
            ], 503);
        }

        return response()->json([
            'api_key' => config('stream.api_key'),
            'token' => $this->streamChat->getUserToken($user),
            'user_id' => (string) $user->id,
        ]);
    }

    public function channels(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->streamChat->syncChannelsForUser($user);

        $channels = ChatChannel::query()
            ->forUser($user->id)
            ->with(['client.userDetail', 'artist.userDetail', 'booking.tattoo', 'bookingRequest.tattoo', 'customRequest'])
            ->get();

        $conversations = $this->buildConversations($channels, $user);

        return response()->json([
            'conversations' => $conversations,
            'channels' => $this->flattenBookingThreads($conversations),
            'configured' => $this->streamChat->isConfigured(),
        ]);
    }

    public function ensureForArtist(Request $request, int $artistUserId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'user') {
            abort(403);
        }

        return $this->ensurePairResponse($user->id, $artistUserId, $user, $request);
    }

    public function ensureForClient(Request $request, int $clientUserId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'artist') {
            abort(403);
        }

        return $this->ensurePairResponse($clientUserId, $user->id, $user, $request);
    }

    public function unreadSummary(Request $request): JsonResponse
    {
        try {
            $summary = $this->streamChat->getUnreadSummaryForUser($request->user());
        } catch (\Throwable) {
            $summary = ['total' => 0, 'channels' => []];
        }

        return response()->json($summary);
    }

    public function canSend(Request $request, string $streamChannelId): JsonResponse
    {
        $user = $request->user();

        $channel = ChatChannel::query()
            ->where('stream_channel_id', $streamChannelId)
            ->with(['booking', 'bookingRequest', 'customRequest'])
            ->firstOrFail();

        if ($channel->otherPartyUserIdFor($user->id) === null) {
            abort(403);
        }

        return response()->json([
            'can_send' => $channel->isChatAllowed(),
            'locked_reason' => $channel->chatLockedReason(),
            'stream_channel_id' => $channel->stream_channel_id,
        ]);
    }

    private function ensurePairResponse(int $clientId, int $artistId, User $viewer, Request $request): JsonResponse
    {
        $bookingId = $request->integer('booking');
        $bookingRequestId = $request->integer('booking_request');
        $customRequestId = $request->integer('custom_request');

        if ($bookingRequestId > 0) {
            $bookingRequest = BookingRequest::query()
                ->whereKey($bookingRequestId)
                ->where('user_id', $clientId)
                ->where('artist_id', $artistId)
                ->first();

            if (! $bookingRequest) {
                return response()->json(['message' => 'Request not found.'], 404);
            }

            if ($bookingRequest->isBooked() && $bookingRequest->booking_id) {
                $booking = Booking::query()->find($bookingRequest->booking_id);
                if ($booking) {
                    $this->streamChat->attachBookingRequestChannel($bookingRequest, $booking);
                }
            } elseif ($bookingRequest->isOpenForChat() || $bookingRequest->canViewChat()) {
                $this->streamChat->ensureChannelForBookingRequest($bookingRequest);
            } else {
                return response()->json(['message' => 'Chat is not available for this request.'], 403);
            }
        } elseif ($customRequestId > 0) {
            $customRequest = CustomRequest::query()
                ->whereKey($customRequestId)
                ->where('user_id', $clientId)
                ->where('artist_id', $artistId)
                ->first();

            if (! $customRequest) {
                return response()->json(['message' => 'Request not found.'], 404);
            }

            if ($customRequest->isBooked() && $customRequest->booking_id) {
                $booking = Booking::query()->find($customRequest->booking_id);
                if ($booking) {
                    $this->streamChat->attachCustomRequestChannel($customRequest, $booking);
                }
            } elseif ($customRequest->isOpenForChat() || $customRequest->canViewChat()) {
                $this->streamChat->ensureChannelForCustomRequest($customRequest);
            } else {
                return response()->json(['message' => 'Chat is not available for this request.'], 403);
            }
        } elseif ($bookingId > 0) {
            $booking = Booking::query()
                ->where('id', $bookingId)
                ->where('user_id', $clientId)
                ->where('artist_user_id', $artistId)
                ->first();

            if (! $booking) {
                return response()->json([
                    'message' => 'Booking not found.',
                ], 404);
            }

            if (! $booking->isOpenForChat()) {
                $existing = ChatChannel::query()->where('booking_id', $booking->id)->first();
                if (! $existing) {
                    return response()->json([
                        'message' => 'No active booking for this conversation.',
                    ], 403);
                }

                $this->streamChat->syncChannelForBooking($booking);
            } else {
                $this->streamChat->ensureChannelForBooking($booking);
            }
        } elseif (! Booking::hasOpenChatBetween($clientId, $artistId) && ! $this->hasOpenRequestChatBetween($clientId, $artistId)) {
            $hasHistory = ChatChannel::query()->forPair($clientId, $artistId)->exists();
            if (! $hasHistory) {
                return response()->json([
                    'message' => 'No active booking between these users.',
                ], 403);
            }
        } else {
            Booking::query()
                ->open()
                ->betweenUsers($clientId, $artistId)
                ->get()
                ->each(fn (Booking $booking) => $this->streamChat->ensureChannelForBooking($booking));

            BookingRequest::query()
                ->where('user_id', $clientId)
                ->where('artist_id', $artistId)
                ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                ->get()
                ->each(fn (BookingRequest $req) => $this->streamChat->ensureChannelForBookingRequest($req));

            CustomRequest::query()
                ->where('user_id', $clientId)
                ->where('artist_id', $artistId)
                ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                ->get()
                ->each(fn (CustomRequest $req) => $this->streamChat->ensureChannelForCustomRequest($req));
        }

        $channels = ChatChannel::query()
            ->forPair($clientId, $artistId)
            ->with(['client.userDetail', 'artist.userDetail', 'booking.tattoo', 'bookingRequest.tattoo', 'customRequest'])
            ->get();

        if ($channels->isEmpty()) {
            return response()->json([
                'message' => 'Could not open chat channel.',
            ], 500);
        }

        $conversation = $this->buildConversations($channels, $viewer)->first();

        return response()->json([
            'conversation' => $conversation,
            'channels' => $this->flattenBookingThreads(collect([$conversation])),
        ]);
    }

    private function hasOpenRequestChatBetween(int $clientId, int $artistId): bool
    {
        return BookingRequest::query()
            ->where('user_id', $clientId)
            ->where('artist_id', $artistId)
            ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
            ->exists()
            || CustomRequest::query()
                ->where('user_id', $clientId)
                ->where('artist_id', $artistId)
                ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                ->exists();
    }

    /**
     * @param  Collection<int, ChatChannel>  $channels
     * @return Collection<int, array<string, mixed>>
     */
    private function buildConversations(Collection $channels, User $viewer): Collection
    {
        return $channels
            ->groupBy(fn (ChatChannel $channel) => $channel->pairKey())
            ->map(function (Collection $group) use ($viewer) {
                /** @var ChatChannel $first */
                $first = $group->first();
                $otherId = $first->otherPartyUserIdFor($viewer->id);
                $other = $otherId === $first->client_user_id ? $first->client : $first->artist;

                $initials = '';
                if ($other) {
                    $initials = strtoupper(substr($other->first_name ?? '', 0, 1).substr($other->last_name ?? '', 0, 1));
                }

                $avatar = $other?->userDetail && filled($other->userDetail->avatar)
                    ? asset($other->userDetail->avatar)
                    : asset('design/images/icons/avatar.jpg');

                $bookings = $group
                    ->map(fn (ChatChannel $channel) => $this->formatBookingThread($channel))
                    ->sort(function (array $a, array $b) {
                        if ($a['can_chat'] !== $b['can_chat']) {
                            return $b['can_chat'] <=> $a['can_chat'];
                        }

                        $dateCompare = strcmp((string) ($a['date_sort'] ?? ''), (string) ($b['date_sort'] ?? ''));
                        if ($dateCompare !== 0) {
                            return $dateCompare;
                        }

                        return ($b['booking_id'] ?? 0) <=> ($a['booking_id'] ?? 0);
                    })
                    ->values()
                    ->all();

                return [
                    'client_user_id' => $first->client_user_id,
                    'artist_user_id' => $first->artist_user_id,
                    'other_party' => [
                        'id' => $other?->id,
                        'name' => $other ? trim($other->first_name.' '.$other->last_name) : 'Unknown',
                        'initials' => $initials ?: '?',
                        'avatar' => $avatar,
                        'role' => $other?->role,
                    ],
                    'bookings' => $bookings,
                ];
            })
            ->values();
    }

    private function formatBookingThread(ChatChannel $channel): array
    {
        $booking = $channel->booking;
        $bookingRequest = $channel->bookingRequest;
        $customRequest = $channel->customRequest;
        $bookingDate = null;
        $dateSort = '';

        if ($booking?->booking_date) {
            $parsed = Carbon::parse($booking->booking_date);
            $bookingDate = $parsed->format('M j, Y');
            $dateSort = $parsed->format('Y-m-d');
        } elseif ($bookingRequest?->created_at) {
            $dateSort = $bookingRequest->created_at->format('Y-m-d');
        } elseif ($customRequest?->created_at) {
            $dateSort = $customRequest->created_at->format('Y-m-d');
        }

        if ($booking) {
            $reference = $booking->referenceLabel();
            $title = $booking->displayTitle() ?? 'Booking';
            $status = $booking->status;
            $threadType = 'booking';
        } elseif ($bookingRequest) {
            $reference = $bookingRequest->referenceLabel();
            $title = $bookingRequest->tattoo?->title ?? 'Design request';
            $status = $bookingRequest->status;
            $threadType = 'booking_request';
        } elseif ($customRequest) {
            $reference = $customRequest->referenceLabel();
            $title = $customRequest->isGuestRequest() ? 'Guest request' : 'Custom request';
            $status = $customRequest->status;
            $threadType = 'custom_request';
        } else {
            $reference = 'Conversation';
            $title = 'Conversation';
            $status = null;
            $threadType = 'unknown';
        }

        return [
            'booking_id' => $booking?->id ?? $channel->booking_id,
            'booking_request_id' => $bookingRequest?->id ?? $channel->booking_request_id,
            'custom_request_id' => $customRequest?->id ?? $channel->custom_request_id,
            'thread_type' => $threadType,
            'reference' => $reference,
            'title' => $title,
            'date' => $bookingDate,
            'date_sort' => $dateSort,
            'status' => $status,
            'stream_channel_id' => $channel->stream_channel_id,
            'can_chat' => $channel->isChatAllowed(),
            'locked_reason' => $channel->chatLockedReason(),
            'client_user_id' => $channel->client_user_id,
            'artist_user_id' => $channel->artist_user_id,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>|null>  $conversations
     * @return array<int, array<string, mixed>>
     */
    private function flattenBookingThreads(Collection $conversations): array
    {
        $threads = [];

        foreach ($conversations as $conversation) {
            if (! is_array($conversation)) {
                continue;
            }

            foreach ($conversation['bookings'] ?? [] as $booking) {
                $threads[] = array_merge($booking, [
                    'other_party' => $conversation['other_party'] ?? null,
                    'booking' => [
                        'id' => $booking['booking_id'],
                        'reference' => $booking['reference'],
                        'title' => $booking['title'],
                        'date' => $booking['date'],
                        'status' => $booking['status'],
                    ],
                ]);
            }
        }

        return $threads;
    }
}
