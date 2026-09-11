<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE artist_referrals MODIFY status ENUM('signup_not_completed', 'pending', 'sent_to_admin', 'rewarded', 'rejected') NOT NULL DEFAULT 'pending'");

        // Existing pending rows for artists who never finished onboarding.
        DB::statement("
            UPDATE artist_referrals ar
            INNER JOIN users u ON u.id = ar.referred_user_id
            SET ar.status = 'signup_not_completed'
            WHERE ar.status = 'pending'
              AND (u.on_boarding IS NULL OR u.on_boarding <> 'yes')
        ");
    }

    public function down(): void
    {
        DB::table('artist_referrals')
            ->where('status', 'signup_not_completed')
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE artist_referrals MODIFY status ENUM('pending', 'sent_to_admin', 'rewarded', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
