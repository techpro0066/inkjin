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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('artist_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('tattoo_id')->constrained('inkjin_tattoos')->onDelete('restrict');
            
            // Booking Type & Custom Details
            $table->enum('booking_type', ['custom', 'flash'])->default('flash');
            $table->json('custom_tattoo_details')->nullable();
            
            // Booking Date & Time
            $table->date('booking_date');
            $table->time('start_time_utc');
            $table->time('end_time_utc');
            $table->string('timezone', 255)->default('UTC');
            
            // Consultation Appointment
            $table->boolean('has_consultation')->default(false);
            $table->date('consultation_date')->nullable();
            $table->time('consultation_start_time_utc')->nullable();
            $table->time('consultation_end_time_utc')->nullable();
            $table->boolean('consultation_completed')->default(false);
            $table->enum('consultation_timing_type', ['combined', 'separate'])->nullable()->comment('Stores the artist\'s consultation timing preference at time of booking');
            $table->foreignId('consultation_booking_id')->nullable()->constrained('bookings')->onDelete('set null')->comment('Links tattoo session booking to its consultation booking (for separate consultation timing)');
            
            // Booking Status
            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled',
                'completed',
                'no_show',
                'rescheduled'
            ])->default('pending');
            
            // Cancellation
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('cancellation_initiated_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->enum('cancellation_type', ['client', 'artist', 'system'])->nullable();
            $table->timestamp('cancellation_deadline')->nullable();
            $table->integer('cancellation_window_hours')->nullable();
            
            // Rescheduling
            $table->foreignId('rescheduled_from_booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->foreignId('rescheduled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rescheduled_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->integer('reschedule_count')->default(0);
            $table->integer('reschedule_limit')->nullable();
            
            // Payment
            $table->string('payment_intent_id', 255)->nullable()->unique();
            $table->enum('payment_status', [
                'pending',
                'paid',
                'refunded',
                'failed'
            ])->default('pending');
            $table->decimal('deposit_amount', 10, 2)->default(0.00);
            $table->boolean('full_amount_paid')->default(false);
            $table->decimal('platform_fee', 10, 2)->default(0.00);
            $table->decimal('total_amount_paid', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            
            // Payout Tracking
            $table->boolean('deposit_released')->default(false);
            $table->timestamp('deposit_released_at')->nullable();
            $table->boolean('remaining_amount_released')->default(false);
            $table->timestamp('remaining_amount_released_at')->nullable();
            $table->string('completion_code', 255)->nullable()->unique();
            $table->timestamp('completion_code_entered_at')->nullable();
            
            // Refund Tracking
            $table->decimal('refund_amount', 10, 2)->default(0.00);
            $table->decimal('deposit_forfeited', 10, 2)->default(0.00);
            $table->string('refund_intent_id', 255)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->enum('refund_status', ['pending', 'processing', 'completed', 'failed', 'partial'])->nullable();
            $table->boolean('platform_fee_refunded')->default(false);
            
            // Completion & No-Show
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamp('no_show_marked_at')->nullable();
            
            // Additional Data
            $table->json('questions_answers')->nullable();
            $table->json('action_history')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('google_calendar_event_id', 255)->nullable();
            $table->string('google_meet_link', 500)->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('artist_user_id');
            $table->index('tattoo_id');
            $table->index('booking_date');
            $table->index('status');
            $table->index('payment_intent_id');
            $table->index('cancelled_by');
            $table->index('rescheduled_from_booking_id');
            $table->index('booking_type');
            $table->index('deposit_released');
            $table->index('remaining_amount_released');
            $table->index('completion_code');
            $table->index('cancellation_deadline');
            $table->index('consultation_date');
            $table->index('google_calendar_event_id');
            $table->index('consultation_booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
