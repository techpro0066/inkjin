<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Step 1: Profile Fields
            $table->string('avatar')->nullable();
            $table->string('user_name')->nullable()->unique();
            $table->string('mobile_number')->nullable()->unique();
            
            // Step 2: Studio Information
            $table->string('studio_name')->nullable();
            $table->text('studio_address')->nullable();
            $table->string('street_name')->nullable();
            $table->string('street_number')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('google_maps_link')->nullable();

            // Step 3: Calendar Connection (Optional)
            $table->text('google_calendar_token')->nullable();
            $table->string('google_calendar_id')->nullable();
            $table->enum('scheduling_type', ['auto', 'managed'])->nullable();
            
            // Step 4: Preferences
            $table->string('timezone')->nullable();
            $table->string('date_time_format')->nullable();
            $table->string('currency')->nullable();
            $table->enum('minimum_deposit_type', ['amount', 'percentage'])->nullable();
            $table->decimal('minimum_deposit_amount', 10, 2)->nullable();
            $table->enum('booking_fee_type', ['client', 'artist', 'split'])->nullable();
            $table->string('reschedule_times')->nullable();
            $table->string('cancellation_window')->nullable();
            $table->integer('session_buffer_period')->nullable();
            $table->boolean('require_consultation')->default(false);
            $table->enum('session_type', ['online', 'physical', 'both'])->nullable();
            $table->integer('session_duration_minutes')->nullable();
            $table->enum('consultation_timing', ['combined', 'separate'])->nullable();
            $table->boolean('require_gap_between_consultation_tattoo')->default(false);
            $table->integer('consultation_tattoo_gap_value')->nullable();
            $table->enum('consultation_tattoo_gap_unit', ['minutes', 'hours', 'days'])->nullable();
            
            // Step 4: Payments
            $table->enum('payment_type', ['artist_account', 'studio_account', 'inkjin_account'])->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->string('studio_email')->nullable();
            
            // Progress tracking
            $table->integer('current_step')->default(1);
            $table->json('completed_steps')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
