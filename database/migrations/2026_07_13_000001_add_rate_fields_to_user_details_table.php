<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable()->after('minimum_deposit_amount');
            }
            if (! Schema::hasColumn('user_details', 'half_day_rate')) {
                $table->decimal('half_day_rate', 10, 2)->nullable()->after('hourly_rate');
            }
            if (! Schema::hasColumn('user_details', 'full_day_rate')) {
                $table->decimal('full_day_rate', 10, 2)->nullable()->after('half_day_rate');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            foreach (['hourly_rate', 'half_day_rate', 'full_day_rate'] as $column) {
                if (Schema::hasColumn('user_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
