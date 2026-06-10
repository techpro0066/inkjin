<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BANK_COLUMNS = [
        'account_holder_name',
        'bank_name',
        'account_number',
        'swift_bic',
        'bank_currency',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        Schema::table('studios', function (Blueprint $table) {
            if (! Schema::hasColumn('studios', 'stripe_account_id')) {
                $table->string('stripe_account_id')->nullable()->after('email');
            }
        });

        $columnsToDrop = array_values(array_filter(
            self::BANK_COLUMNS,
            fn (string $column) => Schema::hasColumn('studios', $column)
        ));

        if ($columnsToDrop !== []) {
            Schema::table('studios', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        Schema::table('studios', function (Blueprint $table) {
            if (! Schema::hasColumn('studios', 'account_holder_name')) {
                $table->string('account_holder_name')->nullable()->after('email');
            }
            if (! Schema::hasColumn('studios', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('account_holder_name');
            }
            if (! Schema::hasColumn('studios', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('studios', 'swift_bic')) {
                $table->string('swift_bic')->nullable()->after('account_number');
            }
            if (! Schema::hasColumn('studios', 'bank_currency')) {
                $table->string('bank_currency', 3)->nullable()->after('swift_bic');
            }
        });

        if (Schema::hasColumn('studios', 'stripe_account_id')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->dropColumn('stripe_account_id');
            });
        }
    }
};
