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
        if (Schema::hasTable('studio_requests')) {
            return;
        }

        Schema::create('studio_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id')->constrained('studios')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_artist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('custom_request_id')->nullable()->constrained('custom_requests')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();

            $table->string('status', 40)->default('pending');
            $table->string('source', 64)->nullable()->default('studio_page');
            $table->string('source_path', 255)->nullable();

            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_phone')->nullable();

            $table->json('questions_answers')->nullable();
            $table->json('reference_images')->nullable();
            $table->json('preferences')->nullable();
            $table->json('preferred_days')->nullable();
            $table->string('avoid_dates')->nullable();
            $table->string('how_much_flexible')->nullable();
            $table->string('urgency')->nullable();
            $table->text('anything_else_notes')->nullable();
            $table->string('reason_decline')->nullable();

            $table->timestamps();

            $table->index(['studio_id', 'status']);
            $table->index(['studio_id', 'created_at']);
        });
    }

    public function down(): void
    {
        $this->dropTablesSafely('studio_requests');
    }
};
