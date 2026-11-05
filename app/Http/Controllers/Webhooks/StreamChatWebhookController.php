<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\ChatMessageEmailService;
use App\Services\StreamChatService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StreamChatWebhookController extends Controller
{
    public function __construct(
        private readonly StreamChatService $streamChat,
        private readonly ChatMessageEmailService $messageEmails,
    ) {}

    public function handle(Request $request): Response
    {
        $rawBody = $request->getContent();
        $signature = (string) ($request->header('X-Signature') ?: $request->header('X-STREAM-SIGNATURE') ?: '');

        if (! $this->signatureIsValid($rawBody, $signature)) {
            Log::warning('Stream chat webhook rejected: invalid signature');

            return response('Invalid signature', 401);
        }

        $payload = $request->all();
        $type = (string) ($payload['type'] ?? '');

        if ($type !== 'message.new') {
            return response('ok', 200);
        }

        try {
            $this->handleMessageNew($payload);
        } catch (\Throwable $e) {
            Log::error('Stream chat webhook processing failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }

        return response('ok', 200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleMessageNew(array $payload): void
    {
        $message = is_array($payload['message'] ?? null) ? $payload['message'] : [];
        $senderId = (int) ($message['user']['id'] ?? $message['user_id'] ?? 0);
        if ($senderId < 1) {
            return;
        }

        $channelId = (string) ($payload['channel_id'] ?? '');
        if ($channelId === '') {
            $channelId = (string) ($payload['cid'] ?? '');
        }

        $text = trim((string) ($message['text'] ?? ''));
        if ($text === '' && ! empty($message['attachments']) && is_array($message['attachments'])) {
            $text = 'Sent an attachment';
        }

        $createdAt = null;
        $rawCreated = (string) ($message['created_at'] ?? '');
        if ($rawCreated !== '') {
            try {
                $createdAt = Carbon::parse($rawCreated);
            } catch (\Throwable) {
                $createdAt = null;
            }
        }

        $this->messageEmails->queueFromArtistMessage(
            $channelId,
            $senderId,
            $text,
            $createdAt,
        );

        $this->messageEmails->queueToArtistMessage(
            $channelId,
            $senderId,
            $text,
            $createdAt,
        );
    }

    private function signatureIsValid(string $rawBody, string $signature): bool
    {
        if (! $this->streamChat->isConfigured()) {
            return false;
        }

        $secret = (string) config('stream.api_secret');
        if ($secret === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
