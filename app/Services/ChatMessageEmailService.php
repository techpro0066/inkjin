<?php

namespace App\Services;

use App\Mail\NewMessageFromArtistMail;
use App\Mail\NewMessageToArtistMail;
use App\Models\ChatChannel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChatMessageEmailService
{
    public function debounceMinutes(): int
    {
        return max(1, (int) config('stream.client_message_email_debounce_minutes', 10));
    }

    /**
     * Queue (debounce) a client email after an artist message on a client↔artist channel.
     */
    public function queueFromArtistMessage(
        string $streamChannelId,
        int $senderUserId,
        string $messageText,
        ?Carbon $messageAt = null,
    ): bool {
        $channelId = $this->normalizeChannelId($streamChannelId);
        if ($channelId === '') {
            return false;
        }

        $channel = ChatChannel::query()
            ->where('stream_channel_id', $channelId)
            ->where('channel_kind', ChatChannel::KIND_CLIENT_ARTIST)
            ->first();

        if (! $channel) {
            return false;
        }

        if ((int) $channel->artist_user_id !== $senderUserId) {
            return false;
        }

        if ((int) $channel->client_user_id < 1) {
            return false;
        }

        $text = $this->normalizeMessageText($messageText);
        if ($text === '') {
            $text = 'Sent you a message in Bookpay.';
        }

        $at = $messageAt?->copy() ?? now();
        $sendAfter = now()->addMinutes($this->debounceMinutes());

        $channel->forceFill([
            'client_email_pending_text' => $text,
            'client_email_pending_at' => $at,
            'client_email_send_after' => $sendAfter,
        ])->save();

        return true;
    }

    /**
     * Queue (debounce) an artist email after a client or studio message.
     */
    public function queueToArtistMessage(
        string $streamChannelId,
        int $senderUserId,
        string $messageText,
        ?Carbon $messageAt = null,
    ): bool {
        $channelId = $this->normalizeChannelId($streamChannelId);
        if ($channelId === '') {
            return false;
        }

        $channel = ChatChannel::query()
            ->where('stream_channel_id', $channelId)
            ->first();

        if (! $channel || (int) $channel->artist_user_id < 1) {
            return false;
        }

        $fromClient = (int) $channel->client_user_id === $senderUserId
            && ! $channel->isStudioArtist();
        $fromStudio = (int) $channel->studio_user_id === $senderUserId
            && $channel->isStudioArtist();

        if (! $fromClient && ! $fromStudio) {
            return false;
        }

        $text = $this->normalizeMessageText($messageText);
        if ($text === '') {
            $text = 'Sent you a message in Bookpay.';
        }

        $at = $messageAt?->copy() ?? now();
        $sendAfter = now()->addMinutes($this->debounceMinutes());

        $channel->forceFill([
            'artist_email_pending_text' => $text,
            'artist_email_pending_at' => $at,
            'artist_email_send_after' => $sendAfter,
        ])->save();

        return true;
    }

    /**
     * @return array{sent: int, failed: int}
     */
    public function sendDue(?Carbon $now = null): array
    {
        $now = $now?->copy() ?? now();
        $client = $this->sendDueClientEmails($now);
        $artist = $this->sendDueArtistEmails($now);

        return [
            'sent' => $client['sent'] + $artist['sent'],
            'failed' => $client['failed'] + $artist['failed'],
        ];
    }

    /**
     * @return array{sent: int, failed: int}
     */
    private function sendDueClientEmails(Carbon $now): array
    {
        $sent = 0;
        $failed = 0;

        $channels = ChatChannel::query()
            ->where('channel_kind', ChatChannel::KIND_CLIENT_ARTIST)
            ->whereNotNull('client_email_send_after')
            ->where('client_email_send_after', '<=', $now)
            ->whereNotNull('client_email_pending_text')
            ->with(['client', 'artist.userDetail', 'booking', 'bookingRequest.tattoo', 'customRequest'])
            ->orderBy('id')
            ->limit(100)
            ->get();

        foreach ($channels as $channel) {
            $text = trim((string) ($channel->client_email_pending_text ?? ''));
            $messageAt = $this->pendingAt($channel->client_email_pending_at);

            $client = $channel->client;
            $artist = $channel->artist;
            $email = trim((string) ($client?->email ?? ''));

            $cleared = ChatChannel::query()
                ->whereKey($channel->id)
                ->where('client_email_send_after', '<=', $now)
                ->where('client_email_pending_at', $messageAt)
                ->update([
                    'client_email_pending_text' => null,
                    'client_email_pending_at' => null,
                    'client_email_send_after' => null,
                ]);

            if ($cleared !== 1 || ! $client || ! $artist || $email === '' || $text === '') {
                continue;
            }

            try {
                Mail::to($email)->send(new NewMessageFromArtistMail(
                    $channel,
                    $client,
                    $artist,
                    $text,
                    $messageAt,
                ));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to send new-message-from-artist email', [
                    'chat_channel_id' => $channel->id,
                    'client_user_id' => $channel->client_user_id,
                    'artist_user_id' => $channel->artist_user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * @return array{sent: int, failed: int}
     */
    private function sendDueArtistEmails(Carbon $now): array
    {
        $sent = 0;
        $failed = 0;

        $channels = ChatChannel::query()
            ->whereNotNull('artist_email_send_after')
            ->where('artist_email_send_after', '<=', $now)
            ->whereNotNull('artist_email_pending_text')
            ->with([
                'client',
                'studioUser.ownedStudio',
                'artist.userDetail',
                'booking.tattoo',
                'booking.studio',
                'bookingRequest.tattoo',
                'customRequest',
            ])
            ->orderBy('id')
            ->limit(100)
            ->get();

        foreach ($channels as $channel) {
            $text = trim((string) ($channel->artist_email_pending_text ?? ''));
            $messageAt = $this->pendingAt($channel->artist_email_pending_at);

            $artist = $channel->artist;
            $email = trim((string) ($artist?->email ?? ''));
            $fromStudio = $channel->isStudioArtist();
            $sender = $fromStudio ? $channel->studioUser : $channel->client;

            $cleared = ChatChannel::query()
                ->whereKey($channel->id)
                ->where('artist_email_send_after', '<=', $now)
                ->where('artist_email_pending_at', $messageAt)
                ->update([
                    'artist_email_pending_text' => null,
                    'artist_email_pending_at' => null,
                    'artist_email_send_after' => null,
                ]);

            if ($cleared !== 1 || ! $artist || ! $sender || $email === '' || $text === '') {
                continue;
            }

            try {
                Mail::to($email)->send(new NewMessageToArtistMail(
                    $channel,
                    $artist,
                    $sender,
                    $text,
                    $messageAt,
                    $fromStudio,
                ));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to send new-message-to-artist email', [
                    'chat_channel_id' => $channel->id,
                    'artist_user_id' => $channel->artist_user_id,
                    'sender_user_id' => $sender->id,
                    'from_studio' => $fromStudio,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function pendingAt(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                // fall through
            }
        }

        return now();
    }

    public function normalizeChannelId(string $cidOrId): string
    {
        $value = trim($cidOrId);
        if ($value === '') {
            return '';
        }

        if (str_contains($value, ':')) {
            $parts = explode(':', $value, 2);

            return trim((string) ($parts[1] ?? ''));
        }

        return $value;
    }

    public function normalizeMessageText(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) > 500) {
            return rtrim(mb_substr($text, 0, 497)).'…';
        }

        return $text;
    }
}
