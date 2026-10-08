<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'welcome_seen_at')) {
                $table->timestamp('welcome_seen_at')->nullable()->after('customize_page_notice_dismissed');
            }
        });

        // Artists who already finished onboarding have already had their "first visit".
        // Leave null for anyone still onboarding so they see the popup on first Home visit.
        if (Schema::hasColumn('user_details', 'welcome_seen_at') && Schema::hasTable('users')) {
            DB::table('user_details')
                ->whereNull('welcome_seen_at')
                ->whereIn('user_id', function ($query) {
                    $query->select('id')
                        ->from('users')
                        ->where('on_boarding', 'yes');
                })
                ->update(['welcome_seen_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'welcome_seen_at')) {
                $table->dropColumn('welcome_seen_at');
            }
        });
    }
};
