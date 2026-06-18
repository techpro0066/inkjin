<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ChatChannel;
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
        if ($user->role === 'artist') {
            return Booking::query()->open()->where('artist_user_id', $user->id)->exists();
        }

        if ($user->role === 'user') {
            return Booking::query()->open()->where('user_id', $user->id)->exists();
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
        $clientId = (int) $booking->user_id;
        $artistId = (int) $booking->artist_user_id;

        $existing = ChatChannel::query()->where('booking_id', $booking->id)->first();

        if (! $booking->isOpenForChat()) {
            if ($existing) {
                $this->syncChannelForBooking($booking);
            }

            return $existing;
        }

        if (! $this->isConfigured()) {
            return ChatChannel::query()->firstOrCreate(
                ['booking_id' => $booking->id],
                [
                    'client_user_id' => $clientId,
                    'artist_user_id' => $artistId,
                    'stream_channel_id' => ChatChannel::channelIdForBooking($clientId, $artistId, $booking->id),
                ]
            );
        }

        $channelId = ChatChannel::channelIdForBooking($clientId, $artistId, $booking->id);

        $chatChannel = ChatChannel::query()->firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'client_user_id' => $clientId,
                'artist_user_id' => $artistId,
                'stream_channel_id' => $channelId,
            ]
        );

        $client = User::query()->find($clientId);
        $artist = User::query()->find($artistId);

        if ($client) {
            $this->upsertStreamUser($client);
        }
        if ($artist) {
            $this->upsertStreamUser($artist);
        }

        try {
            $streamChannel = $this->getClient()->Channel('messaging', $channelId, [
                'members' => [(string) $clientId, (string) $artistId],
                'client_user_id' => $clientId,
                'artist_user_id' => $artistId,
                'booking_id' => $booking->id,
                'booking_ref' => $booking->referenceLabel(),
            ]);

            $streamChannel->create((string) $clientId, [(string) $clientId, (string) $artistId]);
            $this->unfreezeChannel($chatChannel);
        } catch (StreamException $e) {
            Log::warning('Stream channel ensure failed', [
                'channel_id' => $channelId,
                'message' => $e->getMessage(),
            ]);
        }

        return $chatChannel;
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

        if ($booking->isOpenForChat()) {
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
        $query = Booking::query()->open();

        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'artist') {
            $query->where('artist_user_id', $user->id);
        } else {
            return;
        }

        foreach ($query->get() as $booking) {
            $this->ensureChannelForBooking($booking);
        }

        ChatChannel::query()
            ->forUser($user->id)
            ->with('booking')
            ->get()
            ->each(function (ChatChannel $chatChannel) {
                if ($chatChannel->booking) {
                    $this->syncChannelForBooking($chatChannel->booking);
                }
            });
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
}
