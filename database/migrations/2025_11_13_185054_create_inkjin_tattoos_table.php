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
        Schema::create('inkjin_tattoos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tattoo_id')->nullable()->unique();
            $table->string('title');
            $table->string('image')->nullable();
            $table->text('tags')->nullable();
            $table->string('color')->nullable();
            $table->string('primary_style')->nullable();
            $table->text('style')->nullable();
            $table->string('suggested_placement')->nullable();
            $table->boolean('available_to_ink')->default(false);
            $table->boolean('available_to_ar')->default(false);
            $table->boolean('mature_content')->default(false);
            $table->string('status')->nullable();
            $table->boolean('liked_by_current_user')->default(false);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_username');
            $table->string('author_display_name')->nullable();
            $table->string('author_profile_picture')->nullable();
            $table->timestamps();
            
            // Add foreign key to artists table
            $table->foreign('author_username')->references('username')->on('inkjin_artists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inkjin_tattoos');
    }
};
