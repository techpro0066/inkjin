<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_channels')) {
            return;
        }

        // Old unique(booking_id) blocks a second studio_artist row for the same booking.
        $this->dropIndexIfExists('chat_channels', 'chat_channels_booking_id_unique');

        if (! $this->indexExists('chat_channels', 'chat_channels_booking_kind_unique')) {
            DB::statement(
                'ALTER TABLE chat_channels ADD UNIQUE INDEX chat_channels_booking_kind_unique (booking_id, channel_kind)'
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_channels')) {
            return;
        }

        $this->dropIndexIfExists('chat_channels', 'chat_channels_booking_kind_unique');

        if (! $this->indexExists('chat_channels', 'chat_channels_booking_id_unique')) {
            DB::statement(
                'ALTER TABLE chat_channels ADD UNIQUE INDEX chat_channels_booking_id_unique (booking_id)'
            );
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND index_name = ?
             LIMIT 1',
            [$table, $index]
        );

        return $rows !== [];
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
    }
};
