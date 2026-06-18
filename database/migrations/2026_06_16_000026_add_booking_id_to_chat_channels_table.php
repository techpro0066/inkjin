<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_channels')) {
            return;
        }

        if (Schema::hasColumn('chat_channels', 'booking_id')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->dropUnique(['client_user_id', 'artist_user_id']);
        });

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('artist_user_id')->constrained('bookings')->cascadeOnDelete();
            $table->index(['client_user_id', 'artist_user_id'], 'chat_channels_pair_index');
        });

        // Legacy pair-level channels cannot map cleanly to per-booking threads.
        DB::table('chat_channels')->truncate();

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable(false)->change();
            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_channels') || ! Schema::hasColumn('chat_channels', 'booking_id')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropUnique(['booking_id']);
            $table->dropIndex('chat_channels_pair_index');
            $table->dropColumn('booking_id');
            $table->unique(['client_user_id', 'artist_user_id']);
        });
    }
};
