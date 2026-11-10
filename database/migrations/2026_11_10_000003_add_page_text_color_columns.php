<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_details') && ! Schema::hasColumn('user_details', 'page_text_color')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->string('page_text_color', 16)->nullable()->after('page_text_mode');
            });
        }

        if (Schema::hasTable('studios') && ! Schema::hasColumn('studios', 'page_text_color')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->string('page_text_color', 16)->nullable()->after('page_text_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_details') && Schema::hasColumn('user_details', 'page_text_color')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->dropColumn('page_text_color');
            });
        }

        if (Schema::hasTable('studios') && Schema::hasColumn('studios', 'page_text_color')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->dropColumn('page_text_color');
            });
        }
    }
};
