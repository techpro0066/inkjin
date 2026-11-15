<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolios') && ! Schema::hasColumn('portfolios', 'is_deleted')) {
            Schema::table('portfolios', function (Blueprint $table) {
                $table->boolean('is_deleted')->default(false)->after('is_active');
                $table->index(['user_id', 'is_deleted'], 'portfolios_user_is_deleted_index');
            });
        }

        if (Schema::hasTable('artist_designs') && ! Schema::hasColumn('artist_designs', 'is_deleted')) {
            Schema::table('artist_designs', function (Blueprint $table) {
                $table->boolean('is_deleted')->default(false)->after('is_active');
                $table->index(['user_id', 'is_deleted'], 'artist_designs_user_is_deleted_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('portfolios') && Schema::hasColumn('portfolios', 'is_deleted')) {
            Schema::table('portfolios', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('portfolios'))->pluck('name');
                if ($indexes->contains('portfolios_user_is_deleted_index')) {
                    $table->dropIndex('portfolios_user_is_deleted_index');
                }
                $table->dropColumn('is_deleted');
            });
        }

        if (Schema::hasTable('artist_designs') && Schema::hasColumn('artist_designs', 'is_deleted')) {
            Schema::table('artist_designs', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('artist_designs'))->pluck('name');
                if ($indexes->contains('artist_designs_user_is_deleted_index')) {
                    $table->dropIndex('artist_designs_user_is_deleted_index');
                }
                $table->dropColumn('is_deleted');
            });
        }
    }
};
