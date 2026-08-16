<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_links', 'payer_name')) {
                $table->string('payer_name')->nullable()->after('scheduling_type');
            }
            if (! Schema::hasColumn('payment_links', 'payer_email')) {
                $table->string('payer_email')->nullable()->after('payer_name');
            }
            if (! Schema::hasColumn('payment_links', 'payer_phone')) {
                $table->string('payer_phone', 50)->nullable()->after('payer_email');
            }
            if (! Schema::hasColumn('payment_links', 'slot_ymd')) {
                $table->string('slot_ymd', 16)->nullable()->after('payer_phone');
            }
            if (! Schema::hasColumn('payment_links', 'slot_time')) {
                $table->string('slot_time', 20)->nullable()->after('slot_ymd');
            }
            if (! Schema::hasColumn('payment_links', 'payment_intent_id')) {
                $table->string('payment_intent_id')->nullable()->after('slot_time');
            }
            if (! Schema::hasColumn('payment_links', 'viva_order_code')) {
                $table->string('viva_order_code')->nullable()->after('payment_intent_id');
            }
            if (! Schema::hasColumn('payment_links', 'payment_method')) {
                $table->string('payment_method', 32)->nullable()->after('viva_order_code');
            }
            if (! Schema::hasColumn('payment_links', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('payment_links', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->after('paid_at')->constrained('bookings')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            if (Schema::hasColumn('payment_links', 'booking_id')) {
                $table->dropConstrainedForeignId('booking_id');
            }

            $columns = [
                'payer_name',
                'payer_email',
                'payer_phone',
                'slot_ymd',
                'slot_time',
                'payment_intent_id',
                'viva_order_code',
                'payment_method',
                'paid_at',
            ];

            $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('payment_links', $column)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
