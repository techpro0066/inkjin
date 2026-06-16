<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'must_set_password')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_set_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'must_set_password')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_set_password');
        });
    }
};
