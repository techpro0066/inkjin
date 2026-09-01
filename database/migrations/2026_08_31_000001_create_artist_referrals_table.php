<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'rewarded'])->default('pending');
            $table->foreignId('qualified_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestamp('qualified_at')->nullable();
            $table->decimal('reward_amount', 10, 2)->default(20.00);
            $table->boolean('fee_waived')->default(false);
            $table->timestamp('reward_paid_at')->nullable();
            $table->timestamps();

            $table->unique('referred_user_id');
            $table->index(['referrer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_referrals');
    }
};
