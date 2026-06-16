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
        if (Schema::hasTable('custom_requests')) {
            return;
        }

        Schema::create('custom_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('artist_id')->constrained('users');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'moved_to_booking'])->default('pending');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->enum('type', ['auto', 'managed'])->default('auto');
            $table->json('preferences')->nullable();
            $table->json('preferred_days')->nullable();
            $table->string('avoid_dates')->nullable();
            $table->string('how_much_flexible')->nullable();
            $table->string('urgency')->nullable();
            $table->json('questions_answers')->nullable();
            $table->string('anything_else_notes')->nullable();
            $table->string('reason_decline')->nullable();
            $table->decimal('estimated_price', 10, 2)->default(0.00)->nullable();
            $table->string('estimated_time')->nullable();
            $table->string('number_of_sessions')->nullable();
            $table->string('message_for_client')->nullable();
            $table->json('artist_consultation_slots')->nullable();
            $table->json('artist_session_slots')->nullable();
            $table->json('client_session_slots')->nullable();
            $table->json('client_consultation_slots')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('custom_requests');
    }
};
