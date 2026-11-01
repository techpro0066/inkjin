<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'guest_spot_id')) {
                $table->foreignId('guest_spot_id')
                    ->nullable()
                    ->after('studio_id')
                    ->constrained('guest_spots')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'guest_spot_id')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_spot_id');
        });
    }
};
