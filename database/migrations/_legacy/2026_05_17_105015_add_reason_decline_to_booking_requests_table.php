<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_requests') || Schema::hasColumn('booking_requests', 'reason_decline')) {
            return;
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->text('reason_decline')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_requests') || ! Schema::hasColumn('booking_requests', 'reason_decline')) {
            return;
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn('reason_decline');
        });
    }
};
