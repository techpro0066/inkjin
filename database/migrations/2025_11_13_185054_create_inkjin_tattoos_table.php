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
            $table->string('artist_handle');
            $table->string('type')->nullable();
            $table->text('visibility')->nullable();
            $table->string('filename')->nullable();
            $table->string('ink')->nullable();
            $table->string('ar')->nullable();
            $table->string('repeatable')->nullable();
            $table->string('sensitive')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('primary_style')->nullable();
            $table->text('other_styles')->nullable();
            $table->string('suggested_placement')->nullable();
            $table->string('color')->nullable();
            $table->text('tags')->nullable();
            $table->string('price')->nullable();
            $table->string('max_price')->nullable();
            $table->string('size_height')->nullable();
            $table->string('size_width')->nullable();
            $table->string('cost_per_session')->nullable();
            $table->string('min_sessions')->nullable();
            $table->string('max_sessions')->nullable();
            $table->string('session_time_h')->nullable();
            $table->string('currency')->nullable();
            $table->string('price_model')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
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
