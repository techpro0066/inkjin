<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('artist_designs')) {
            return;
        }

        if (! Schema::hasColumn('artist_designs', 'suggested_placements')) {
            Schema::table('artist_designs', function (Blueprint $table) {
                $table->json('suggested_placements')->nullable()->after('other_styles');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('artist_designs')) {
            return;
        }

        if (Schema::hasColumn('artist_designs', 'suggested_placements')) {
            Schema::table('artist_designs', function (Blueprint $table) {
                $table->dropColumn('suggested_placements');
            });
        }
    }
};
