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
        Schema::create('availability_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('override_date'); // Specific date for the override
            $table->time('start_time')->nullable(); // Stored in UTC, null means unavailable for the day
            $table->time('end_time')->nullable(); // Stored in UTC, null means unavailable for the day
            $table->boolean('is_unavailable')->default(false)->comment('If true, artist is unavailable on this date');
            $table->text('notes')->nullable()->comment('Optional notes about the override');
            $table->timestamps();
            
            // Prevent duplicate entries for same user and date
            $table->unique(['user_id', 'override_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_overrides');
    }
};
