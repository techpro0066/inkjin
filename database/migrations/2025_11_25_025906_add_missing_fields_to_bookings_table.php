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
        Schema::table('bookings', function (Blueprint $table) {
            // Booking Type & Custom Details
            $table->enum('booking_type', ['custom', 'flash'])->default('flash')->after('tattoo_id');
            $table->json('custom_tattoo_details')->nullable()->after('booking_type');

            // Consultation Appointment
            $table->boolean('has_consultation')->default(false)->after('end_time_utc');
            $table->date('consultation_date')->nullable()->after('has_consultation');
            $table->time('consultation_start_time_utc')->nullable()->after('consultation_date');
            $table->time('consultation_end_time_utc')->nullable()->after('consultation_start_time_utc');
            $table->boolean('consultation_completed')->default(false)->after('consultation_end_time_utc');

            // Reschedule Tracking
            $table->integer('reschedule_count')->default(0)->after('reschedule_reason');
            $table->integer('reschedule_limit')->nullable()->after('reschedule_count');

            // Payout Tracking
            $table->boolean('deposit_released')->default(false)->after('currency');
            $table->timestamp('deposit_released_at')->nullable()->after('deposit_released');
            $table->boolean('remaining_amount_released')->default(false)->after('deposit_released_at');
            $table->timestamp('remaining_amount_released_at')->nullable()->after('remaining_amount_released');
            $table->string('completion_code', 255)->nullable()->unique()->after('remaining_amount_released_at');
            $table->timestamp('completion_code_entered_at')->nullable()->after('completion_code');

            // Refund Tracking
            $table->decimal('refund_amount', 10, 2)->default(0.00)->after('completion_code_entered_at');
            $table->string('refund_intent_id', 255)->nullable()->after('refund_amount');
            $table->timestamp('refunded_at')->nullable()->after('refund_intent_id');
            $table->text('refund_reason')->nullable()->after('refunded_at');
            $table->boolean('platform_fee_refunded')->default(false)->after('refund_reason');

            // Cancellation Window
            $table->timestamp('cancellation_deadline')->nullable()->after('platform_fee_refunded');
            $table->integer('cancellation_window_hours')->nullable()->after('cancellation_deadline');

            // Completion & No-Show
            $table->timestamp('completed_at')->nullable()->after('cancellation_window_hours');
            $table->text('completion_notes')->nullable()->after('completed_at');
            $table->timestamp('no_show_marked_at')->nullable()->after('completion_notes');

            // Action History
            $table->json('action_history')->nullable()->after('no_show_marked_at');

            // Additional Indexes
            $table->index('booking_type');
            $table->index('deposit_released');
            $table->index('remaining_amount_released');
            $table->index('completion_code');
            $table->index('cancellation_deadline');
            $table->index('consultation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['booking_type']);
            $table->dropIndex(['deposit_released']);
            $table->dropIndex(['remaining_amount_released']);
            $table->dropIndex(['completion_code']);
            $table->dropIndex(['cancellation_deadline']);
            $table->dropIndex(['consultation_date']);

            // Drop columns
            $table->dropColumn([
                'booking_type',
                'custom_tattoo_details',
                'has_consultation',
                'consultation_date',
                'consultation_start_time_utc',
                'consultation_end_time_utc',
                'consultation_completed',
                'reschedule_count',
                'reschedule_limit',
                'deposit_released',
                'deposit_released_at',
                'remaining_amount_released',
                'remaining_amount_released_at',
                'completion_code',
                'completion_code_entered_at',
                'refund_amount',
                'refund_intent_id',
                'refunded_at',
                'refund_reason',
                'platform_fee_refunded',
                'cancellation_deadline',
                'cancellation_window_hours',
                'completed_at',
                'completion_notes',
                'no_show_marked_at',
                'action_history',
            ]);
        });
    }
};
