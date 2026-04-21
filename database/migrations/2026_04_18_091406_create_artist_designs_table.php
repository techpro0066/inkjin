<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artist_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('description');
            $table->string('image');
            $table->boolean('is_active');
            $table->boolean('is_visible');
            $table->boolean('is_repeatable');
            $table->boolean('is_sensitive');
            $table->string('primary_style');
            $table->json('other_styles');
            $table->string('color');
            $table->json('tags');
            $table->integer('min_price');
            $table->integer('max_price');
            $table->integer('min_size');
            $table->integer('max_size');
            $table->integer('min_sessions');
            $table->integer('max_sessions');
            $table->string('session_duration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.notes
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_designs');
    }
};
