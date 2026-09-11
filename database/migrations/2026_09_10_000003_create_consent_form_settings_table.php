<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_form_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('studio_market', 2)->default('GR');
            $table->string('registration_number')->nullable();
            $table->boolean('allow_younger')->default(false);
            $table->unsignedTinyInteger('age_allow')->nullable();
            $table->string('language_mode', 16)->default('single'); // single | both
            $table->string('form_language', 8)->default('en');
            $table->string('other_language', 8)->nullable();
            $table->boolean('ask_photo')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_form_settings');
    }
};
