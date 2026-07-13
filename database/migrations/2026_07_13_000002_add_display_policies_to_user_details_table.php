<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'display_policies')) {
                $table->boolean('display_policies')->default(true)->after('personal_page_name_alias');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'display_policies')) {
                $table->dropColumn('display_policies');
            }
        });
    }
};
