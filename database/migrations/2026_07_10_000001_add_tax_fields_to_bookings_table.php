<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('platform_fee');
            }
            if (! Schema::hasColumn('bookings', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_amount');
            }
            if (! Schema::hasColumn('bookings', 'tax_country')) {
                $table->string('tax_country', 2)->nullable()->after('tax_rate');
            }
            if (! Schema::hasColumn('bookings', 'tax_label')) {
                $table->string('tax_label', 100)->nullable()->after('tax_country');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            foreach (['tax_label', 'tax_country', 'tax_rate', 'tax_amount'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
