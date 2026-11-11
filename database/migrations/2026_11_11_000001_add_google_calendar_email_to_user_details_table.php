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
            if (! Schema::hasColumn('user_details', 'google_calendar_email')) {
                $table->string('google_calendar_email')->nullable()->after('google_calendar_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'google_calendar_email')) {
                $table->dropColumn('google_calendar_email');
            }
        });
    }
};
