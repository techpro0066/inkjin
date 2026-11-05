<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_studios')) {
            return;
        }

        if (! Schema::hasColumn('user_studios', 'split_proposed_by')) {
            Schema::table('user_studios', function (Blueprint $table) {
                $table->string('split_proposed_by', 16)->nullable()->after('proposed_revenue_split');
            });
        }

        if (! Schema::hasColumn('user_studios', 'split_proposed_at')) {
            Schema::table('user_studios', function (Blueprint $table) {
                $table->timestamp('split_proposed_at')->nullable()->after('split_proposed_by');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_studios')) {
            return;
        }

        Schema::table('user_studios', function (Blueprint $table) {
            if (Schema::hasColumn('user_studios', 'split_proposed_at')) {
                $table->dropColumn('split_proposed_at');
            }
            if (Schema::hasColumn('user_studios', 'split_proposed_by')) {
                $table->dropColumn('split_proposed_by');
            }
        });
    }
};
