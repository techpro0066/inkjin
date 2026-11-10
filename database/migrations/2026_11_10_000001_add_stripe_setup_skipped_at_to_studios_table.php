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

        if (! Schema::hasColumn('studios', 'stripe_setup_skipped_at')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->timestamp('stripe_setup_skipped_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        if (Schema::hasColumn('studios', 'stripe_setup_skipped_at')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->dropColumn('stripe_setup_skipped_at');
            });
        }
    }
};
