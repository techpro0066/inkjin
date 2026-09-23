<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'studio_revenue_artist_percent')) {
                $table->unsignedTinyInteger('studio_revenue_artist_percent')->nullable()->after('studio_id');
            }
            if (! Schema::hasColumn('user_details', 'studio_relationship_type')) {
                $table->enum('studio_relationship_type', [
                    'co_owner',
                    'resident',
                    'collective_member',
                    'apprentice',
                    'other',
                ])->nullable()->after('studio_revenue_artist_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'studio_relationship_type')) {
                $table->dropColumn('studio_relationship_type');
            }
            if (Schema::hasColumn('user_details', 'studio_revenue_artist_percent')) {
                $table->dropColumn('studio_revenue_artist_percent');
            }
        });
    }
};
