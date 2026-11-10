<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_details')) {
            Schema::table('user_details', function (Blueprint $table) {
                if (! Schema::hasColumn('user_details', 'page_button_mode')) {
                    $table->string('page_button_mode', 16)->nullable()->after('page_text_mode');
                }
                if (! Schema::hasColumn('user_details', 'page_button_color')) {
                    $table->string('page_button_color', 16)->nullable()->after('page_button_mode');
                }
            });
        }

        if (Schema::hasTable('studios')) {
            Schema::table('studios', function (Blueprint $table) {
                if (! Schema::hasColumn('studios', 'page_button_mode')) {
                    $table->string('page_button_mode', 16)->nullable()->after('page_text_mode');
                }
                if (! Schema::hasColumn('studios', 'page_button_color')) {
                    $table->string('page_button_color', 16)->nullable()->after('page_button_mode');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_details')) {
            Schema::table('user_details', function (Blueprint $table) {
                foreach (['page_button_color', 'page_button_mode'] as $col) {
                    if (Schema::hasColumn('user_details', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('studios')) {
            Schema::table('studios', function (Blueprint $table) {
                foreach (['page_button_color', 'page_button_mode'] as $col) {
                    if (Schema::hasColumn('studios', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
