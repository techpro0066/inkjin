<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_requests')) {
            return;
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_requests', 'artist_consultation_slots')) {
                $table->json('artist_consultation_slots')->nullable()->after('reason_decline');
            }
            if (! Schema::hasColumn('booking_requests', 'artist_session_slots')) {
                $table->json('artist_session_slots')->nullable()->after('artist_consultation_slots');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_requests')) {
            return;
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            foreach (['artist_session_slots', 'artist_consultation_slots'] as $column) {
                if (Schema::hasColumn('booking_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
