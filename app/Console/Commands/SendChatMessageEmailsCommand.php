<?php

namespace App\Console\Commands;

use App\Services\ChatMessageEmailService;
use Illuminate\Console\Command;

class SendChatMessageEmailsCommand extends Command
{
    protected $signature = 'chat:send-message-emails';

    protected $description = 'Send debounced new-message emails (artist→client and client/studio→artist)';

    public function handle(ChatMessageEmailService $service): int
    {
        $result = $service->sendDue();

        $this->info(sprintf(
            'Chat message emails: %d sent, %d failed.',
            $result['sent'],
            $result['failed'],
        ));

        return self::SUCCESS;
    }
}
