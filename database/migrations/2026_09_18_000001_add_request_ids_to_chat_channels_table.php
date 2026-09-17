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

        Schema::table('chat_channels', function (Blueprint $table) {
            if (Schema::hasColumn('chat_channels', 'booking_id')) {
                try {
                    $table->dropForeign(['booking_id']);
                } catch (\Throwable) {
                    // Foreign key name may differ across environments.
                }
            }
        });

        DB::statement('ALTER TABLE chat_channels MODIFY booking_id BIGINT UNSIGNED NULL');

        Schema::table('chat_channels', function (Blueprint $table) {
            if (Schema::hasColumn('chat_channels', 'booking_id')) {
                $table->foreign('booking_id')
                    ->references('id')
                    ->on('bookings')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('chat_channels', 'booking_request_id')) {
                $table->foreignId('booking_request_id')
                    ->nullable()
                    ->after('booking_id')
                    ->constrained('booking_requests')
                    ->nullOnDelete();
                $table->unique('booking_request_id');
            }

            if (! Schema::hasColumn('chat_channels', 'custom_request_id')) {
                $table->foreignId('custom_request_id')
                    ->nullable()
                    ->after('booking_request_id')
                    ->constrained('custom_requests')
                    ->nullOnDelete();
                $table->unique('custom_request_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_channels')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            if (Schema::hasColumn('chat_channels', 'custom_request_id')) {
                $table->dropUnique(['custom_request_id']);
                $table->dropConstrainedForeignId('custom_request_id');
            }

            if (Schema::hasColumn('chat_channels', 'booking_request_id')) {
                $table->dropUnique(['booking_request_id']);
                $table->dropConstrainedForeignId('booking_request_id');
            }

            if (Schema::hasColumn('chat_channels', 'booking_id')) {
                try {
                    $table->dropForeign(['booking_id']);
                } catch (\Throwable) {
                    //
                }
            }
        });
    }
};
