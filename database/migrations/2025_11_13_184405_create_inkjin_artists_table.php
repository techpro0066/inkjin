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
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username')->unique();
            $table->string('display_name')->nullable();
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('instagram')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('website')->nullable();
            $table->string('studio')->nullable();
            $table->string('primary_style')->nullable();
            $table->text('style')->nullable();
            $table->string('tattooing_since')->nullable();
            $table->text('description')->nullable();
            $table->string('address_number')->nullable();
            $table->string('address_street')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->integer('followers_count')->default(0);
            $table->integer('tattoo_count')->default(0);
            $table->boolean('allow_messages')->default(true);
            $table->string('profile_picture')->nullable();
            $table->date('created_date')->nullable();
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
