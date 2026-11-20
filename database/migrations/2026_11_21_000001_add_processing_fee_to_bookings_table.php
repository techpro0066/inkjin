<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'processing_fee')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->decimal('processing_fee', 10, 2)->default(0)->after('platform_fee');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'processing_fee')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('processing_fee');
            });
        }
    }
};
