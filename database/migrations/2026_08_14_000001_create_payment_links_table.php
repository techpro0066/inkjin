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
        if (Schema::hasTable('payment_links')) {
            return;
        }

        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('active');
            $table->string('code', 16)->unique();
            $table->string('url');
            $table->decimal('amount', 10, 2);
            $table->string('payment_type', 16);
            $table->string('title');
            $table->dateTime('date_time')->nullable();
            $table->string('session_duration', 32);
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('due_amount', 10, 2)->nullable();
            $table->string('expires');
            $table->dateTime('expires_at')->nullable();
            $table->text('client_message')->nullable();
            $table->string('scheduling_type', 16)->nullable();
            $table->timestamps();

            $table->index(['artist_id', 'status']);
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('payment_links');
    }
};
