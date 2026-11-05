<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('artist_client_feedbacks')) {
            return;
        }

        Schema::create('artist_client_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('client_user_id');
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->json('answers')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();

            $table->index(['artist_id', 'status']);
            $table->index(['client_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_client_feedbacks');
    }
};
