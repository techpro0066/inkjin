<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_payouts')) {
            return;
        }

        Schema::create('user_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'disconnected'])->default('draft');
            $table->boolean('is_active')->default(false);

            $table->string('stripe_account_id')->nullable();
            $table->boolean('stripe_requirement')->default(false);
            $table->timestamp('stripe_requirement_email_sent_at')->nullable();
            $table->timestamp('payout_setup_reminder_sent_at')->nullable();
            $table->string('payout_bank_country', 2)->nullable();
            $table->string('payout_waiting_list_country')->nullable();
            $table->timestamp('payout_waiting_list_at')->nullable();
            $table->string('payout_mode', 20)->default('manual');

            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('stripe_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payouts');
    }
};
