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
        if (Schema::hasTable('balance_collections')) {
            return;
        }

        Schema::create('balance_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->foreignId('artist_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('collection_type', 32);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 10, 2)->nullable();
            $table->string('tax_country', 2)->nullable();
            $table->string('tax_label', 32)->nullable();
            $table->foreignId('payment_link_id')->nullable()->constrained('payment_links')->nullOnDelete();
            $table->string('payment_link_code', 16)->nullable();
            $table->string('payment_link_url')->nullable();
            $table->text('client_message')->nullable();
            $table->string('completion_code', 32)->nullable();
            $table->timestamp('completion_code_entered_at')->nullable();
            $table->string('expected_payment_type', 32)->nullable();
            $table->date('expected_payment_date')->nullable();
            $table->text('note')->nullable();
            $table->string('payment_provider', 20)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->unsignedBigInteger('viva_order_code')->nullable();
            $table->string('viva_transaction_id')->nullable();
            $table->string('payment_status', 32)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('artist_user_id');
            $table->index('collection_type');
            $table->index('payment_status');
            $table->index('status');
            $table->index('payment_intent_id');
            $table->index('viva_order_code');
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('balance_collections');
    }
};
