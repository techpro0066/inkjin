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
        if (Schema::hasTable('artist_payouts')) {
            return;
        }

        Schema::create('artist_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('restrict');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('stripe_transfer_id')->nullable()->unique();
            $table->string('stripe_account_id')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('artist_payouts');
    }
};
