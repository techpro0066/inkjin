<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('availabilities') && ! Schema::hasColumn('availabilities', 'studio_ids')) {
            Schema::table('availabilities', function (Blueprint $table) {
                $table->json('studio_ids')->nullable()->after('end_time');
            });
        }

        if (Schema::hasTable('availability_overrides') && ! Schema::hasColumn('availability_overrides', 'studio_ids')) {
            Schema::table('availability_overrides', function (Blueprint $table) {
                // null / empty = blocked dates; non-empty = location override for that range
                $table->json('studio_ids')->nullable()->after('reason');
            });
        }

        if (Schema::hasTable('user_details') && ! Schema::hasColumn('user_details', 'travel_buffer_minutes')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->unsignedSmallInteger('travel_buffer_minutes')->nullable()->after('session_buffer_period');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('availabilities') && Schema::hasColumn('availabilities', 'studio_ids')) {
            Schema::table('availabilities', function (Blueprint $table) {
                $table->dropColumn('studio_ids');
            });
        }

        if (Schema::hasTable('availability_overrides') && Schema::hasColumn('availability_overrides', 'studio_ids')) {
            Schema::table('availability_overrides', function (Blueprint $table) {
                $table->dropColumn('studio_ids');
            });
        }

        if (Schema::hasTable('user_details') && Schema::hasColumn('user_details', 'travel_buffer_minutes')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->dropColumn('travel_buffer_minutes');
            });
        }
    }
};
