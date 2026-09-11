<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('question_type', 32); // health | risk | aftercare
            $table->json('translations'); // { "en": "...", "el": "...", ... }
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'question_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_form_questions');
    }
};
