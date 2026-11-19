<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details') || Schema::hasColumn('user_details', 'not_allowed_notification')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->json('not_allowed_notification')->nullable()->after('aftercare_content');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasColumn('user_details', 'not_allowed_notification')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('not_allowed_notification');
        });
    }
};
