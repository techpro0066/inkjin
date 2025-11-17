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
            
            // Step 1: Studio Information
            $table->string('studio_name')->nullable();
            $table->text('studio_address')->nullable();
            $table->string('google_maps_link')->nullable();
            
            // Step 2: Calendar Connection (Optional)
            $table->text('google_calendar_token')->nullable();
            $table->string('google_calendar_id')->nullable();
            
            // Step 3: Preferences
            $table->string('avatar')->nullable();
            $table->string('currency')->nullable();
            $table->string('timezone')->nullable();
            $table->string('date_time_format')->nullable();
            $table->decimal('minimum_deposit_amount', 10, 2)->nullable();
            $table->string('minimum_deposit_type')->nullable()->comment('fixed or percentage');
            $table->string('cancellation_window')->nullable();
            $table->string('reschedule_times')->nullable();
            
            // Step 4: Payments
            $table->string('stripe_account_id')->nullable();
            
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
