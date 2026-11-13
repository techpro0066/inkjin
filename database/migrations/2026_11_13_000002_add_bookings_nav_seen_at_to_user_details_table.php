<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'bookings_nav_seen_at')) {
                $table->timestamp('bookings_nav_seen_at')->nullable()->after('clients_nav_seen_at');
            }
        });

        // Baseline ~7 days ago so only recent confirmed bookings light the badge once;
        // older history stays quiet. Opening Bookings clears via markBookingsSeen().
        if (Schema::hasColumn('user_details', 'bookings_nav_seen_at')) {
            DB::table('user_details')
                ->whereNull('bookings_nav_seen_at')
                ->update(['bookings_nav_seen_at' => now()->subDays(7)]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        if (Schema::hasColumn('user_details', 'bookings_nav_seen_at')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->dropColumn('bookings_nav_seen_at');
            });
        }
    }
};
