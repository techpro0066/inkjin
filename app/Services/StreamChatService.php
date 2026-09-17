<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\ChatChannel;
use App\Models\CustomRequest;
use App\Models\User;
use GetStream\StreamChat\Client;
use GetStream\StreamChat\StreamException;
use Illuminate\Support\Facades\Log;

class StreamChatService
{
    private ?Client $client = null;

    public function isConfigured(): bool
    {
        return filled(config('stream.api_key')) && filled(config('stream.api_secret'));
    }

    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client(
                (string) config('stream.api_key'),
                (string) config('stream.api_secret')
            );
        }

        return $this->client;
    }

    public function userHasAnyOpenBooking(User $user): bool
    {
        return $this->userHasAnyOpenChat($user);
    }

    public function userHasAnyOpenChat(User $user): bool
    {
        if ($user->role === 'artist') {
            return Booking::query()->open()->where('artist_user_id', $user->id)->exists()
                || BookingRequest::query()
                    ->where('artist_id', $user->id)
                    ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                    ->exists()
                || CustomRequest::query()
                    ->where('artist_id', $user->id)
                    ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                    ->exists();
        }

        if ($user->role === 'user') {
            return Booking::query()->open()->where('user_id', $user->id)->exists()
                || BookingRequest::query()
                    ->where('user_id', $user->id)
                    ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                    ->exists()
                || CustomRequest::query()
                    ->where('user_id', $user->id)
                    ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
                    ->exists();
        }

        return false;
    }

    public function getUserToken(User $user): string
    {
        $this->upsertStreamUser($user);

        return $this->getClient()->createToken(
            (string) $user->id,
            time() + max(300, (int) config('stream.token_ttl', 86400))
        );
    }

    public function upsertStreamUser(User $user): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $user->loadMissing('userDetail');

        $avatar = $user->userDetail && filled($user->userDetail->avatar)
            ? asset($user->userDetail->avatar)
            : asset('design/images/icons/avatar.jpg');

        try {
            $this->getClient()->upsertUser([
                'id' => (string) $user->id,
                'name' => trim($user->first_name.' '.$user->last_name),
                'image' => $avatar,
                'inkjin_role' => $user->role,
            ]);
        } catch (StreamException $e) {
            Log::warning('Stream user upsert failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function ensureChannelForBooking(Booking $booking): ?ChatChannel
    {
        $existing = ChatChannel::query()->where('booking_id', $booking->id)->first();
        if ($existing) {
            $this->syncChannelForBooking($booking);

            return $existing;
        }

        $attached = $this->tryAttachExistingRequestChannel($booking);
        if ($attached) {
            return $attached;
        }

        if (! $booking->isOpenForChat()) {
            return null;
        }

        return $this->createBookingChannel($booking);
    }

    public function ensureChannelForBookingRequest(BookingRequest $request): ?ChatChannel
    {
        $clientId = (int) $request->user_id;
        $artistId = (int) $request->artist_id;

        $existing = ChatChannel::query()->where('booking_request_id', $request->id)->first();

        if ($request->isBooked() && $request->booking_id) {
            $booking = $request->booking ?: Booking::query()->find($request->booking_id);
            if ($booking) {
                return $this->attachBookingRequestChannel($request, $booking) ?? $existing;
            }
        }

        if (! $request->isOpenForChat()) {
            if ($existing) {
                $this->syncChannelFreezeState($existing);
            }

            return $existing;
        }

        $channelId = ChatChannel::channelIdForBookingRequest($clientId, $artistId, (int) $request->id);

        $chatChannel = ChatChannel::query()->firstOrCreate(
            ['booking_request_id' => $request->id],
            [
                'client_user_id' => $clientId,
                'artist_user_id' => $artistId,
                'booking_id' => null,
                'stream_channel_id' => $channelId,
            ]
        );

        $this->provisionStreamChannel($chatChannel, $clientId, $artistId, [
            'booking_request_id' => $request->id,
            'booking_ref' => $request->referenceLabel(),
            'thread_type' => 'booking_request',
        ], $request->isOpenForChat());

        return $chatChannel;
    }

    public function ensureChannelForCustomRequest(CustomRequest $request): ?ChatChannel
    {
        $clientId = (int) $request->user_id;
        $artistId = (int) $request->artist_id;

        $existing = ChatChannel::query()->where('custom_request_id', $request->id)->first();

        if ($request->isBooked() && $request->booking_id) {
            $booking = $request->booking ?: Booking::query()->find($request->booking_id);
            if ($booking) {
                return $this->attachCustomRequestChannel($request, $booking) ?? $existing;
            }
        }

        if (! $request->isOpenForChat()) {
            if ($existing) {
                $this->syncChannelFreezeState($existing);
            }

            return $existing;
        }

        $channelId = ChatChannel::channelIdForCustomRequest($clientId, $artistId, (int) $request->id);

        $chatChannel = ChatChannel::query()->firstOrCreate(
            ['custom_request_id' => $request->id],
            [
                'client_user_id' => $clientId,
                'artist_user_id' => $artistId,
                'booking_id' => null,
                'stream_channel_id' => $channelId,
            ]
        );

        $this->provisionStreamChannel($chatChannel, $clientId, $artistId, [
            'custom_request_id' => $request->id,
            'booking_ref' => $request->referenceLabel(),
            'thread_type' => 'custom_request',
        ], $request->isOpenForChat());

        return $chatChannel;
    }

    public function attachBookingRequestChannel(BookingRequest $request, Booking $booking): ?ChatChannel
    {
        $requestChannel = ChatChannel::query()->where('booking_request_id', $request->id)->first();
        $bookingChannel = ChatChannel::query()->where('booking_id', $booking->id)->first();

        if ($requestChannel) {
            if ($bookingChannel && $bookingChannel->id !== $requestChannel->id) {
                $bookingChannel->delete();
            }

            $requestChannel->forceFill([
                'booking_id' => $booking->id,
                'client_user_id' => (int) $booking->user_id,
                'artist_user_id' => (int) $booking->artist_user_id,
            ])->save();

            $this->updateStreamChannelMeta($requestChannel, [
                'booking_id' => $booking->id,
                'booking_request_id' => $request->id,
                'booking_ref' => $booking->referenceLabel(),
                'thread_type' => 'booking',
            ]);

            $this->syncChannelForBooking($booking);

            return $requestChannel->fresh(['booking', 'bookingRequest', 'customRequest']);
        }

        if ($bookingChannel) {
            $bookingChannel->forceFill(['booking_request_id' => $request->id])->save();

            return $bookingChannel;
        }

        $channel = $this->createBookingChannel($booking);
        $channel->forceFill(['booking_request_id' => $request->id])->save();

        return $channel;
    }

    public function attachCustomRequestChannel(CustomRequest $request, Booking $booking): ?ChatChannel
    {
        $requestChannel = ChatChannel::query()->where('custom_request_id', $request->id)->first();
        $bookingChannel = ChatChannel::query()->where('booking_id', $booking->id)->first();

        if ($requestChannel) {
            if ($bookingChannel && $bookingChannel->id !== $requestChannel->id) {
                $bookingChannel->delete();
            }

            $requestChannel->forceFill([
                'booking_id' => $booking->id,
                'client_user_id' => (int) $booking->user_id,
                'artist_user_id' => (int) $booking->artist_user_id,
            ])->save();

            $this->updateStreamChannelMeta($requestChannel, [
                'booking_id' => $booking->id,
                'custom_request_id' => $request->id,
                'booking_ref' => $booking->referenceLabel(),
                'thread_type' => 'booking',
            ]);

            $this->syncChannelForBooking($booking);

            return $requestChannel->fresh(['booking', 'bookingRequest', 'customRequest']);
        }

        if ($bookingChannel) {
            $bookingChannel->forceFill(['custom_request_id' => $request->id])->save();

            return $bookingChannel;
        }

        $channel = $this->createBookingChannel($booking);
        $channel->forceFill(['custom_request_id' => $request->id])->save();

        return $channel;
    }

    public function syncChannelForBooking(Booking $booking): void
    {
        $chatChannel = ChatChannel::query()->where('booking_id', $booking->id)->first();

        if (! $chatChannel) {
            if ($booking->isOpenForChat()) {
                $this->ensureChannelForBooking($booking);
            }

            return;
        }

        $this->syncChannelFreezeState($chatChannel);
    }

    public function syncChannelFreezeState(ChatChannel $chatChannel): void
    {
        if ($chatChannel->isChatAllowed()) {
            $this->unfreezeChannel($chatChannel);
        } else {
            $this->freezeChannel($chatChannel);
        }
    }

    public function userHasAnyChannel(User $user): bool
    {
        return ChatChannel::query()->forUser($user->id)->exists();
    }

    public function syncChannelsForUser(User $user): void
    {
        if (! in_array($user->role, ['user', 'artist'], true)) {
            return;
        }

        $bookingQuery = Booking::query()->open();
        $bookingRequestQuery = BookingRequest::query()->whereNotIn('status', ['cancelled', 'moved_to_booking']);
        $customRequestQuery = CustomRequest::query()->whereNotIn('status', ['cancelled', 'moved_to_booking']);

        if ($user->role === 'user') {
            $bookingQuery->where('user_id', $user->id);
            $bookingRequestQuery->where('user_id', $user->id);
            $customRequestQuery->where('user_id', $user->id);
        } else {
            $bookingQuery->where('artist_user_id', $user->id);
            $bookingRequestQuery->where('artist_id', $user->id);
            $customRequestQuery->where('artist_id', $user->id);
        }

        foreach ($bookingQuery->get() as $booking) {
            $this->ensureChannelForBooking($booking);
        }

        foreach ($bookingRequestQuery->get() as $request) {
            $this->ensureChannelForBookingRequest($request);
        }

        foreach ($customRequestQuery->get() as $request) {
            $this->ensureChannelForCustomRequest($request);
        }

        ChatChannel::query()
            ->forUser($user->id)
            ->with(['booking', 'bookingRequest', 'customRequest'])
            ->get()
            ->each(fn (ChatChannel $chatChannel) => $this->syncChannelFreezeState($chatChannel));
    }

    public function getUnreadSummaryForUser(User $user): array
    {
        $empty = ['total' => 0, 'channels' => []];

        if (! $this->isConfigured() || ! ChatChannel::query()->forUser($user->id)->exists()) {
            return $empty;
        }

        try {
            $response = $this->getClient()->unreadCounts((string) $user->id);
            $data = is_array($response) ? $response : $response->getArrayCopy();

            $total = (int) ($data['total_unread_count'] ?? 0);
            $channels = [];

            foreach ($data['channels'] ?? [] as $row) {
                $row = is_array($row) ? $row : (array) $row;
                $channelId = (string) ($row['channel_id'] ?? '');

                if (str_contains($channelId, ':')) {
                    $channelId = explode(':', $channelId, 2)[1];
                }

                $count = (int) ($row['unread_count'] ?? 0);

                if ($channelId !== '' && $count > 0) {
                    $channels[$channelId] = $count;
                }
            }

            if ($total === 0 && $channels !== []) {
                $total = array_sum($channels);
            }

            return [
                'total' => $total,
                'channels' => $channels,
            ];
        } catch (StreamException $e) {
            Log::warning('Stream unread counts failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    public function freezeChannel(ChatChannel $chatChannel): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $this->getClient()
                ->Channel('messaging', $chatChannel->stream_channel_id)
                ->updatePartial(['frozen' => true]);
        } catch (StreamException $e) {
            Log::warning('Stream channel freeze failed', [
                'channel_id' => $chatChannel->stream_channel_id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function unfreezeChannel(ChatChannel $chatChannel): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $this->getClient()
                ->Channel('messaging', $chatChannel->stream_channel_id)
                ->updatePartial(['frozen' => false]);
        } catch (StreamException $e) {
            Log::warning('Stream channel unfreeze failed', [
                'channel_id' => $chatChannel->stream_channel_id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function tryAttachExistingRequestChannel(Booking $booking): ?ChatChannel
    {
        $details = is_array($booking->custom_tattoo_details) ? $booking->custom_tattoo_details : [];
        $customRequestId = (int) ($details['custom_request_id'] ?? 0);

        if ($customRequestId > 0) {
            $requestChannel = ChatChannel::query()->where('custom_request_id', $customRequestId)->first();
            if ($requestChannel) {
                $customRequest = CustomRequest::query()->find($customRequestId);
                if ($customRequest) {
                    return $this->attachCustomRequestChannel($customRequest, $booking);
                }
            }
        }

        $bookingRequest = BookingRequest::query()->where('booking_id', $booking->id)->first();
        if ($bookingRequest) {
            $requestChannel = ChatChannel::query()->where('booking_request_id', $bookingRequest->id)->first();
            if ($requestChannel) {
                return $this->attachBookingRequestChannel($bookingRequest, $booking);
            }
        }

        $customRequest = CustomRequest::query()->where('booking_id', $booking->id)->first();
        if ($customRequest) {
            $requestChannel = ChatChannel::query()->where('custom_request_id', $customRequest->id)->first();
            if ($requestChannel) {
                return $this->attachCustomRequestChannel($customRequest, $booking);
            }
        }

        return null;
    }

    private function createBookingChannel(Booking $booking): ChatChannel
    {
        $clientId = (int) $booking->user_id;
        $artistId = (int) $booking->artist_user_id;
        $channelId = ChatChannel::channelIdForBooking($clientId, $artistId, (int) $booking->id);

        $chatChannel = ChatChannel::query()->firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'client_user_id' => $clientId,
                'artist_user_id' => $artistId,
                'stream_channel_id' => $channelId,
            ]
        );

        $this->provisionStreamChannel($chatChannel, $clientId, $artistId, [
            'booking_id' => $booking->id,
            'booking_ref' => $booking->referenceLabel(),
            'thread_type' => 'booking',
        ], $booking->isOpenForChat());

        return $chatChannel;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function provisionStreamChannel(
        ChatChannel $chatChannel,
        int $clientId,
        int $artistId,
        array $meta,
        bool $unfreeze
    ): void {
        if (! $this->isConfigured()) {
            return;
        }

        $client = User::query()->find($clientId);
        $artist = User::query()->find($artistId);

        if ($client) {
            $this->upsertStreamUser($client);
        }
        if ($artist) {
            $this->upsertStreamUser($artist);
        }

        try {
            $streamChannel = $this->getClient()->Channel('messaging', $chatChannel->stream_channel_id, array_merge([
                'members' => [(string) $clientId, (string) $artistId],
                'client_user_id' => $clientId,
                'artist_user_id' => $artistId,
            ], $meta));

            $streamChannel->create((string) $clientId, [(string) $clientId, (string) $artistId]);

            if ($unfreeze) {
                $this->unfreezeChannel($chatChannel);
            } else {
                $this->freezeChannel($chatChannel);
            }
        } catch (StreamException $e) {
            Log::warning('Stream channel ensure failed', [
                'channel_id' => $chatChannel->stream_channel_id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function updateStreamChannelMeta(ChatChannel $chatChannel, array $meta): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $this->getClient()
                ->Channel('messaging', $chatChannel->stream_channel_id)
                ->updatePartial($meta);
        } catch (StreamException $e) {
            Log::warning('Stream channel meta update failed', [
                'channel_id' => $chatChannel->stream_channel_id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
