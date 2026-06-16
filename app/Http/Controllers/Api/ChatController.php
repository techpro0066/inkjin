<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChatChannel;
use App\Models\User;
use App\Services\StreamChatService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->with(['client.userDetail', 'artist.userDetail'])
            ->get()
            ->map(fn (ChatChannel $channel) => $this->formatChannel($channel, $user))
            ->values();

        return response()->json([
            'channels' => $channels,
            'configured' => $this->streamChat->isConfigured(),
        ]);
    }

    public function ensureForArtist(Request $request, int $artistUserId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'user') {
            abort(403);
        }

        return $this->ensurePairResponse($user->id, $artistUserId, $user);
    }

    public function ensureForClient(Request $request, int $clientUserId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'artist') {
            abort(403);
        }

        return $this->ensurePairResponse($clientUserId, $user->id, $user);
    }

    public function unreadSummary(Request $request): JsonResponse
    {
        $summary = $this->streamChat->getUnreadSummaryForUser($request->user());

        return response()->json($summary);
    }

    public function canSend(Request $request, string $streamChannelId): JsonResponse
    {
        $user = $request->user();

        $channel = ChatChannel::query()
            ->where('stream_channel_id', $streamChannelId)
            ->firstOrFail();

        if ($channel->otherPartyUserIdFor($user->id) === null) {
            abort(403);
        }

        return response()->json([
            'can_send' => $channel->isChatAllowed(),
            'stream_channel_id' => $channel->stream_channel_id,
        ]);
    }

    private function ensurePairResponse(int $clientId, int $artistId, User $viewer): JsonResponse
    {
        $existing = ChatChannel::query()
            ->where('client_user_id', $clientId)
            ->where('artist_user_id', $artistId)
            ->first();

        if ($existing) {
            return response()->json([
                'channel' => $this->formatChannel($existing, $viewer),
            ]);
        }

        if (! Booking::hasOpenChatBetween($clientId, $artistId)) {
            return response()->json([
                'message' => 'No active booking between these users.',
            ], 403);
        }

        $channel = $this->streamChat->ensureChannelForPair($clientId, $artistId);

        if (! $channel) {
            return response()->json([
                'message' => 'Could not open chat channel.',
            ], 500);
        }

        return response()->json([
            'channel' => $this->formatChannel($channel, $viewer),
        ]);
    }

    private function formatChannel(ChatChannel $channel, User $viewer): array
    {
        $otherId = $channel->otherPartyUserIdFor($viewer->id);
        $other = $otherId === $channel->client_user_id ? $channel->client : $channel->artist;
        $booking = $channel->latestOpenBooking();

        $initials = '';
        if ($other) {
            $initials = strtoupper(substr($other->first_name ?? '', 0, 1).substr($other->last_name ?? '', 0, 1));
        }

        $avatar = $other?->userDetail && filled($other->userDetail->avatar)
            ? asset($other->userDetail->avatar)
            : asset('design/images/icons/avatar.jpg');

        $bookingDate = null;
        if ($booking?->booking_date) {
            $bookingDate = Carbon::parse($booking->booking_date)->format('M j, Y');
        }

        return [
            'stream_channel_id' => $channel->stream_channel_id,
            'client_user_id' => $channel->client_user_id,
            'artist_user_id' => $channel->artist_user_id,
            'can_chat' => $channel->isChatAllowed(),
            'other_party' => [
                'id' => $other?->id,
                'name' => $other ? trim($other->first_name.' '.$other->last_name) : 'Unknown',
                'initials' => $initials ?: '?',
                'avatar' => $avatar,
                'role' => $other?->role,
            ],
            'booking' => $booking ? [
                'id' => $booking->id,
                'title' => $booking->displayTitle(),
                'date' => $bookingDate,
                'status' => $booking->status,
            ] : null,
        ];
    }
}
