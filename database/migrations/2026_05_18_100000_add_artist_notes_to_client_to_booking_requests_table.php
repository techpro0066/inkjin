<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_requests') || Schema::hasColumn('booking_requests', 'artist_notes_to_client')) {
            return;
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->text('artist_notes_to_client')->nullable()->after('artist_session_slots');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_requests') || ! Schema::hasColumn('booking_requests', 'artist_notes_to_client')) {
            return;
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn('artist_notes_to_client');
        });
    }
};
