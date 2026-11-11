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

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'clients_nav_seen_at')) {
                $table->timestamp('clients_nav_seen_at')->nullable()->after('welcome_seen_at');
            }
        });

        // Baseline so existing waitlists/clients don't all light the info dot on deploy.
        if (Schema::hasColumn('user_details', 'clients_nav_seen_at')) {
            DB::table('user_details')
                ->whereNull('clients_nav_seen_at')
                ->update(['clients_nav_seen_at' => now()]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'clients_nav_seen_at')) {
                $table->dropColumn('clients_nav_seen_at');
            }
        });
    }
};
