<?php

use App\Database\SafelyDropsTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafelyDropsTables;

    public function up(): void
    {
        if (Schema::hasTable('booking_requests')) {
            return;
        }

        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('artist_id')->constrained('users');
            $table->foreignId('tattoo_id')->constrained('artist_designs');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'moved_to_booking'])->default('pending');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->json('questions_answers')->nullable();
            $table->string('consultation_details')->nullable();
            $table->json('preferences')->nullable();
            $table->json('preferred_days')->nullable();
            $table->string('avoid_dates')->nullable();
            $table->string('how_much_flexible')->nullable();
            $table->string('urgency')->nullable();
            $table->text('reason_decline')->nullable();
            $table->json('artist_consultation_slots')->nullable();
            $table->json('artist_session_slots')->nullable();
            $table->text('artist_notes_to_client')->nullable();
            $table->json('client_consultation_slots')->nullable();
            $table->json('client_session_slots')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('booking_requests');
    }
};
