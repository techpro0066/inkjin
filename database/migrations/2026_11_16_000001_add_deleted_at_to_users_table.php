<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'deleted_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'deleted_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropColumn('deleted_at');
        });
    }
};
