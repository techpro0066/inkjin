<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_referrals', function (Blueprint $table) {
            $table->string('stripe_transfer_id')->nullable()->after('reward_paid_at');
            $table->string('stripe_account_id')->nullable()->after('stripe_transfer_id');
            $table->string('reward_currency', 3)->nullable()->after('stripe_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('artist_referrals', function (Blueprint $table) {
            $table->dropColumn(['stripe_transfer_id', 'stripe_account_id', 'reward_currency']);
        });
    }
};
