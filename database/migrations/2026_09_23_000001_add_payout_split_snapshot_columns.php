<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'payout_payment_type')) {
                $table->string('payout_payment_type', 32)->nullable()->after('payment_provider');
            }
            if (! Schema::hasColumn('bookings', 'payout_artist_percent')) {
                $table->unsignedTinyInteger('payout_artist_percent')->nullable()->after('payout_payment_type');
            }
        });

        Schema::table('artist_payouts', function (Blueprint $table) {
            if (! Schema::hasColumn('artist_payouts', 'artist_amount')) {
                $table->decimal('artist_amount', 10, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('artist_payouts', 'studio_amount')) {
                $table->decimal('studio_amount', 10, 2)->nullable()->after('artist_amount');
            }
            if (! Schema::hasColumn('artist_payouts', 'studio_stripe_transfer_id')) {
                $table->string('studio_stripe_transfer_id')->nullable()->unique()->after('stripe_account_id');
            }
            if (! Schema::hasColumn('artist_payouts', 'studio_stripe_account_id')) {
                $table->string('studio_stripe_account_id')->nullable()->after('studio_stripe_transfer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'payout_artist_percent')) {
                $table->dropColumn('payout_artist_percent');
            }
            if (Schema::hasColumn('bookings', 'payout_payment_type')) {
                $table->dropColumn('payout_payment_type');
            }
        });

        Schema::table('artist_payouts', function (Blueprint $table) {
            foreach ([
                'studio_stripe_account_id',
                'studio_stripe_transfer_id',
                'studio_amount',
                'artist_amount',
            ] as $column) {
                if (Schema::hasColumn('artist_payouts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
