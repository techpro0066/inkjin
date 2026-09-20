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
            if (! Schema::hasColumn('bookings', 'actual_duration_minutes')) {
                $table->unsignedSmallInteger('actual_duration_minutes')
                    ->nullable()
                    ->after('completion_notes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'actual_duration_minutes')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('actual_duration_minutes');
        });
    }
};
