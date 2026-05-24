<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            $table->json('client_session_slots')->nullable()->after('artist_session_slots');
            $table->foreignId('booking_id')->nullable()->after('status')->constrained('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn(['client_session_slots', 'booking_id']);
        });
    }
};
