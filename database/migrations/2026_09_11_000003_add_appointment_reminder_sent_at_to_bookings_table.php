<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'reminder_72h_sent_at')) {
                $table->timestamp('reminder_72h_sent_at')->nullable()->after('reminder_sent_at');
            }
            if (! Schema::hasColumn('bookings', 'reminder_24h_sent_at')) {
                $table->timestamp('reminder_24h_sent_at')->nullable()->after('reminder_72h_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'reminder_24h_sent_at')) {
                $table->dropColumn('reminder_24h_sent_at');
            }
            if (Schema::hasColumn('bookings', 'reminder_72h_sent_at')) {
                $table->dropColumn('reminder_72h_sent_at');
            }
        });
    }
};
