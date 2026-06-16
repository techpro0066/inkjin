<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('artist_payouts')) {
            return;
        }

        Schema::table('artist_payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('artist_payouts', 'stripe_transfer_id')) {
                $table->string('stripe_transfer_id')->nullable()->unique()->after('amount');
            }
            if (! Schema::hasColumn('artist_payouts', 'stripe_account_id')) {
                $table->string('stripe_account_id')->nullable()->after('stripe_transfer_id');
            }
            if (! Schema::hasColumn('artist_payouts', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('stripe_account_id');
            }
            if (! Schema::hasColumn('artist_payouts', 'status')) {
                $table->string('status')->default('pending')->after('currency');
            }
            if (! Schema::hasColumn('artist_payouts', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('artist_payouts')) {
            return;
        }

        Schema::table('artist_payouts', function (Blueprint $table) {
            foreach ([
                'stripe_transfer_id',
                'stripe_account_id',
                'currency',
                'status',
                'failure_reason',
            ] as $column) {
                if (Schema::hasColumn('artist_payouts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
