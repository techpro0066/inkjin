<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->json('client_consultation_slots')->nullable()->after('artist_notes_to_client');
            $table->json('client_session_slots')->nullable()->after('client_consultation_slots');
            $table->foreignId('booking_id')->nullable()->after('status')->constrained('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn(['client_consultation_slots', 'client_session_slots', 'booking_id']);
        });
    }
};
