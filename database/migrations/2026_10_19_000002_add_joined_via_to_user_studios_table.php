<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_studios')) {
            return;
        }

        if (! Schema::hasColumn('user_studios', 'joined_via')) {
            Schema::table('user_studios', function (Blueprint $table) {
                $table->string('joined_via', 32)->nullable()->after('proposed_revenue_split');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_studios') && Schema::hasColumn('user_studios', 'joined_via')) {
            Schema::table('user_studios', function (Blueprint $table) {
                $table->dropColumn('joined_via');
            });
        }
    }
};
