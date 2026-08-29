<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'payout_setup_reminder_sent_at')) {
                $table->timestamp('payout_setup_reminder_sent_at')->nullable()->after('stripe_requirement');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'payout_setup_reminder_sent_at')) {
                $table->dropColumn('payout_setup_reminder_sent_at');
            }
        });
    }
};
