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
            
            // Profile Fields
            $table->string('user_name')->nullable()->unique();
            $table->string('mobile_number')->nullable()->unique();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            
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
            $table->integer('session_buffer_period')->nullable()->comment('Buffer period in minutes between sessions');
            $table->boolean('require_consultation')->default(false)->comment('Require consultation session when booking a tattoo');
            $table->enum('session_type', ['online', 'physical', 'both'])->nullable()->comment('Type of session: online, physical, or both');
            $table->integer('session_duration_minutes')->nullable()->comment('Default session duration in minutes');
            $table->enum('consultation_timing', ['combined', 'separate'])->nullable()->comment('Whether consultation time is combined with tattoo session or separate');
            $table->boolean('require_gap_between_consultation_tattoo')->default(false)->comment('Whether to require a gap/window time between consultation and tattoo session');
            $table->integer('consultation_tattoo_gap_value')->nullable()->comment('Gap duration value between consultation and tattoo session');
            $table->enum('consultation_tattoo_gap_unit', ['minutes', 'hours', 'days'])->nullable()->comment('Gap duration unit (minutes, hours, or days)');
            
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
