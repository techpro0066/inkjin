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
            
            $table->date('booking_date');
            $table->time('start_time_utc');
            $table->time('end_time_utc');
            $table->string('timezone', 255)->default('UTC');
            
            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled',
                'completed',
                'no_show',
                'rescheduled'
            ])->default('pending');
            
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->foreignId('rescheduled_from_booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->foreignId('rescheduled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rescheduled_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            
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
            
            $table->json('questions_answers')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            
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
