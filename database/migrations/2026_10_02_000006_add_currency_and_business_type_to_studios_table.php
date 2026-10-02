<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $after = Schema::hasColumn('studios', 'default_revenue_artist_percent')
                ? 'default_revenue_artist_percent'
                : (Schema::hasColumn('studios', 'owner_is_artist') ? 'owner_is_artist' : 'email');

            if (! Schema::hasColumn('studios', 'currency')) {
                $table->string('currency', 3)->nullable()->after($after);
            }
            if (! Schema::hasColumn('studios', 'business_type')) {
                $table->string('business_type', 20)->nullable()->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            foreach (['business_type', 'currency'] as $column) {
                if (Schema::hasColumn('studios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
