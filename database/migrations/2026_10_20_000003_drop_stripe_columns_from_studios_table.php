<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        Schema::table('studios', function (Blueprint $table) {
            if (Schema::hasColumn('studios', 'stripe_requirement')) {
                $table->dropColumn('stripe_requirement');
            }
            if (Schema::hasColumn('studios', 'stripe_account_id')) {
                $table->dropColumn('stripe_account_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        Schema::table('studios', function (Blueprint $table) {
            if (! Schema::hasColumn('studios', 'stripe_account_id')) {
                $table->string('stripe_account_id')->nullable()->after('size_unit');
            }
            if (! Schema::hasColumn('studios', 'stripe_requirement')) {
                $table->boolean('stripe_requirement')->default(false)->after('stripe_account_id');
            }
        });
    }
};
