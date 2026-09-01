<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE artist_referrals MODIFY status ENUM('pending', 'sent_to_admin', 'rewarded', 'rejected') NOT NULL DEFAULT 'pending'");

        Schema::table('artist_referrals', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('reward_paid_at');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('artist_referrals', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'rejected_at']);
        });

        DB::table('artist_referrals')
            ->where('status', 'rejected')
            ->update(['status' => 'sent_to_admin']);

        DB::statement("ALTER TABLE artist_referrals MODIFY status ENUM('pending', 'sent_to_admin', 'rewarded') NOT NULL DEFAULT 'pending'");
    }
};
