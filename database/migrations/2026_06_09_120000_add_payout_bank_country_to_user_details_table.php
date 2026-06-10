<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'payout_bank_country')) {
                $table->string('payout_bank_country', 2)->nullable()->after('stripe_account_id');
            }
            if (! Schema::hasColumn('user_details', 'payout_waiting_list_country')) {
                $table->string('payout_waiting_list_country')->nullable()->after('payout_bank_country');
            }
            if (! Schema::hasColumn('user_details', 'payout_waiting_list_at')) {
                $table->timestamp('payout_waiting_list_at')->nullable()->after('payout_waiting_list_country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $columns = ['payout_bank_country', 'payout_waiting_list_country', 'payout_waiting_list_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('user_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
