<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('studios') && ! Schema::hasColumn('studios', 'show_guest_artists')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->boolean('show_guest_artists')->default(true);
            });
        }

        if (! Schema::hasTable('user_studios')) {
            return;
        }

        if (! Schema::hasColumn('user_studios', 'show_on_page')) {
            Schema::table('user_studios', function (Blueprint $table) {
                $table->boolean('show_on_page')->default(true);
            });
        }

        if (! Schema::hasColumn('user_studios', 'page_sort_order')) {
            Schema::table('user_studios', function (Blueprint $table) {
                $table->unsignedInteger('page_sort_order')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_studios')) {
            Schema::table('user_studios', function (Blueprint $table) {
                if (Schema::hasColumn('user_studios', 'page_sort_order')) {
                    $table->dropColumn('page_sort_order');
                }
                if (Schema::hasColumn('user_studios', 'show_on_page')) {
                    $table->dropColumn('show_on_page');
                }
            });
        }

        if (Schema::hasTable('studios') && Schema::hasColumn('studios', 'show_guest_artists')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->dropColumn('show_guest_artists');
            });
        }
    }
};
