<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $after = Schema::hasColumn('studios', 'owner_is_artist')
                ? 'owner_is_artist'
                : (Schema::hasColumn('studios', 'studio_type') ? 'studio_type' : 'email');

            if (! Schema::hasColumn('studios', 'offers_revenue_split')) {
                $table->boolean('offers_revenue_split')->default(true)->after($after);
            }
            if (! Schema::hasColumn('studios', 'offers_workstation_rent')) {
                $table->boolean('offers_workstation_rent')->default(true)->after('offers_revenue_split');
            }
            if (! Schema::hasColumn('studios', 'default_revenue_artist_percent')) {
                $table->unsignedTinyInteger('default_revenue_artist_percent')->default(60)->after('offers_workstation_rent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            foreach (['default_revenue_artist_percent', 'offers_workstation_rent', 'offers_revenue_split'] as $column) {
                if (Schema::hasColumn('studios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
