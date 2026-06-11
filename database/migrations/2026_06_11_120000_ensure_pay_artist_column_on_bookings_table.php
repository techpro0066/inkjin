<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'pay_artist')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->boolean('pay_artist')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'pay_artist')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('pay_artist');
            });
        }
    }
};
