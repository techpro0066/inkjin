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
        if (Schema::hasTable('pending_viva_payments')) {
            return;
        }

        Schema::create('pending_viva_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viva_order_code')->unique();
            $table->string('viva_transaction_id')->nullable();
            $table->string('flow', 40);
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('artist_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('merchant_trns')->unique();
            $table->string('status', 32)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['flow', 'status']);
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('pending_viva_payments');
    }
};
