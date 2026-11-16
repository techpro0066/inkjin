<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'deactivated_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->after('deleted_at');
            $table->index('deactivated_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'deactivated_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['deactivated_at']);
            $table->dropColumn('deactivated_at');
        });
    }
};
