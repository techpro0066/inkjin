<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'country_user_belongs_in')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('country_user_belongs_in')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'country_user_belongs_in')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('country_user_belongs_in');
        });
    }
};
