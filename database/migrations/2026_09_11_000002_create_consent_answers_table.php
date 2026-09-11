<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('artist_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('pending'); // pending|sent|completed|cancelled|expired
            $table->timestamp('send_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('full_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone')->nullable();
            $table->string('emergency_contact')->nullable();

            $table->string('guardian_name')->nullable();
            $table->string('guardian_relationship')->nullable();
            $table->string('guardian_id_reference')->nullable();
            $table->string('guardian_signature')->nullable();

            $table->boolean('accepted_risks')->default(false);
            $table->boolean('accepted_health_consent')->default(false);
            $table->boolean('accepted_aftercare')->default(false);
            $table->boolean('accepted_data_notice')->default(false);
            $table->boolean('accepted_photo')->default(false);

            $table->string('typed_signature')->nullable();
            $table->string('form_language', 8)->nullable();
            $table->json('answers')->nullable();

            $table->timestamps();

            $table->index(['status', 'send_at']);
            $table->index(['artist_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_answers');
    }
};
