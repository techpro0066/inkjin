<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! Schema::hasColumn('user_details', 'studio_relationship_type')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->enum('studio_relationship_type', [
                    'co_owner',
                    'resident',
                    'collective_member',
                    'apprentice',
                    'other',
                ])->nullable()->after('workspace_type');
            });

            return;
        }

        // Column already exists (near studio_id) — place it after workspace_type.
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE user_details MODIFY COLUMN studio_relationship_type ENUM('co_owner','resident','collective_member','apprentice','other') NULL AFTER workspace_type");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasColumn('user_details', 'studio_relationship_type')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        // Restore previous placement after studio_revenue_artist_percent when possible.
        if ($driver === 'mysql' && Schema::hasColumn('user_details', 'studio_revenue_artist_percent')) {
            DB::statement("ALTER TABLE user_details MODIFY COLUMN studio_relationship_type ENUM('co_owner','resident','collective_member','apprentice','other') NULL AFTER studio_revenue_artist_percent");
        }
    }
};
