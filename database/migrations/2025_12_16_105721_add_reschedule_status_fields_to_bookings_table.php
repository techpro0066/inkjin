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
        Schema::table('bookings', function (Blueprint $table) {
            // Add reschedule status and requested by fields
            $table->enum('reschedule_status', ['pending', 'accepted', 'declined', 'completed'])->nullable()->after('reschedule_limit');
            $table->enum('reschedule_requested_by', ['client', 'artist'])->nullable()->after('reschedule_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['reschedule_status', 'reschedule_requested_by']);
        });
    }
};
