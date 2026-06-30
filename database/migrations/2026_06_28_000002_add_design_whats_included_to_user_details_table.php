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
            if (! Schema::hasColumn('user_details', 'design_whats_included')) {
                $table->json('design_whats_included')->nullable()->after('personal_page_name_alias');
            }
            if (! Schema::hasColumn('user_details', 'design_whats_included_is_active')) {
                $table->boolean('design_whats_included_is_active')->default(false)->after('design_whats_included');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'design_whats_included_is_active')) {
                $table->dropColumn('design_whats_included_is_active');
            }
            if (Schema::hasColumn('user_details', 'design_whats_included')) {
                $table->dropColumn('design_whats_included');
            }
        });
    }
};
