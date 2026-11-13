<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        if (! Schema::hasColumn('studios', 'welcome_seen_at')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->timestamp('welcome_seen_at')->nullable()->after('stripe_setup_skipped_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        if (Schema::hasColumn('studios', 'welcome_seen_at')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->dropColumn('welcome_seen_at');
            });
        }
    }
};
