<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings') || Schema::hasColumn('bookings', 'pay_artist')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('pay_artist')->default(false);
        });
    }

    public function down(): void
    {
        // No-op: pay_artist is owned by 2026_06_11_112211_add_column_pay_artist_in_booking_table.
    }
};
