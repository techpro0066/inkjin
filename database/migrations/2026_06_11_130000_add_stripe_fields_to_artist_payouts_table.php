<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_payouts', function (Blueprint $table) {
            $table->string('stripe_transfer_id')->nullable()->unique()->after('amount');
            $table->string('stripe_account_id')->nullable()->after('stripe_transfer_id');
            $table->string('currency', 3)->default('EUR')->after('stripe_account_id');
            $table->string('status')->default('pending')->after('currency');
            $table->text('failure_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('artist_payouts', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_transfer_id',
                'stripe_account_id',
                'currency',
                'status',
                'failure_reason',
            ]);
        });
    }
};
