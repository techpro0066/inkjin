<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE artist_referrals MODIFY status ENUM('pending', 'sent_to_admin', 'rewarded') NOT NULL DEFAULT 'pending'");

        Schema::table('artist_referrals', function (Blueprint $table) {
            $table->timestamp('admin_notified_at')->nullable()->after('qualified_at');
        });
    }

    public function down(): void
    {
        Schema::table('artist_referrals', function (Blueprint $table) {
            $table->dropColumn('admin_notified_at');
        });

        DB::table('artist_referrals')
            ->where('status', 'sent_to_admin')
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE artist_referrals MODIFY status ENUM('pending', 'rewarded') NOT NULL DEFAULT 'pending'");
    }
};
