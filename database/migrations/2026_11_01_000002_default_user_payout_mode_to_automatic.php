<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_payouts') && Schema::hasColumn('user_payouts', 'payout_mode')) {
            Schema::table('user_payouts', function (Blueprint $table) {
                $table->string('payout_mode', 20)->default('automatic')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_payouts') && Schema::hasColumn('user_payouts', 'payout_mode')) {
            Schema::table('user_payouts', function (Blueprint $table) {
                $table->string('payout_mode', 20)->default('manual')->change();
            });
        }
    }
};
