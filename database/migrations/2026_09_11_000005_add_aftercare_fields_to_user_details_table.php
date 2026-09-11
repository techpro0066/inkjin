<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'aftercare_send_automatically')) {
                $table->boolean('aftercare_send_automatically')->default(false)->after('design_whats_included_is_active');
            }
            if (! Schema::hasColumn('user_details', 'aftercare_content')) {
                $table->json('aftercare_content')->nullable()->after('aftercare_send_automatically');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'aftercare_content')) {
                $table->dropColumn('aftercare_content');
            }
            if (Schema::hasColumn('user_details', 'aftercare_send_automatically')) {
                $table->dropColumn('aftercare_send_automatically');
            }
        });
    }
};
