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
            // Check if columns exist before adding them
            if (!Schema::hasColumn('bookings', 'reschedule_status')) {
                $table->enum('reschedule_status', ['pending', 'accepted', 'declined', 'completed'])->nullable()->after('reschedule_limit');
            }
            if (!Schema::hasColumn('bookings', 'reschedule_requested_by')) {
                $table->enum('reschedule_requested_by', ['client', 'artist'])->nullable()->after('reschedule_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'reschedule_status')) {
                $table->dropColumn('reschedule_status');
            }
            if (Schema::hasColumn('bookings', 'reschedule_requested_by')) {
                $table->dropColumn('reschedule_requested_by');
            }
        });
    }
};
