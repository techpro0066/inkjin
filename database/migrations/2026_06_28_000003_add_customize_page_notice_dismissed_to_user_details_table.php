<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'customize_page_notice_dismissed')) {
                $table->boolean('customize_page_notice_dismissed')->default(false)->after('personal_page_name_alias');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'customize_page_notice_dismissed')) {
                $table->dropColumn('customize_page_notice_dismissed');
            }
        });
    }
};
