<?php

use App\Database\SafelyDropsTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafelyDropsTables;
    /**
     * Run the migrations.
     */
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
            $table->json('questions_answers')->nullable();
            $table->string('consultation_details')->nullable();
            $table->json('preferences')->nullable();
            $table->json('preferred_days')->nullable();
            $table->string('avoid_dates')->nullable();
            $table->string('how_much_flexible')->nullable();
            $table->string('urgency')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTablesSafely('booking_requests');
    }
};
