<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details') || Schema::hasColumn('user_details', 'display_name')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->string('display_name', 100)->nullable()->after('user_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasColumn('user_details', 'display_name')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
