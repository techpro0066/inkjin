<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'cancellation_window_reminder_sent_at')) {
                $table->timestamp('cancellation_window_reminder_sent_at')->nullable()
                    ->after('reminder_24h_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'cancellation_window_reminder_sent_at')) {
                $table->dropColumn('cancellation_window_reminder_sent_at');
            }
        });
    }
};
