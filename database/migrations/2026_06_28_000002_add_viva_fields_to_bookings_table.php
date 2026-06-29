<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'payment_provider')) {
                $table->string('payment_provider', 20)->default('stripe')->after('payment_intent_id');
            }
            if (! Schema::hasColumn('bookings', 'viva_order_code')) {
                $table->unsignedBigInteger('viva_order_code')->nullable()->after('payment_provider');
            }
            if (! Schema::hasColumn('bookings', 'viva_transaction_id')) {
                $table->string('viva_transaction_id')->nullable()->after('viva_order_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = ['payment_provider', 'viva_order_code', 'viva_transaction_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
