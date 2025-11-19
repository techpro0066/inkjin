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
        Schema::create('inkjin_artists', function (Blueprint $table) {
            $table->id();
            $table->string('artist_handle')->unique();
            $table->string('visibility')->nullable();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('nickname')->nullable();
            $table->string('profile_name')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('country')->nullable();
            $table->string('style')->nullable();
            $table->text('other_styles')->nullable();
            $table->string('since')->nullable();
            $table->text('studio')->nullable();
            $table->string('instagram')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('website')->nullable();
            $table->text('artist_dashboard_signup')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inkjin_artists');
    }
};
