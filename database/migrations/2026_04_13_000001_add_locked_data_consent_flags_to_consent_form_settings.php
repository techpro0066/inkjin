<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dated before the create migration; skip on fresh DBs until the table exists.
        if (! Schema::hasTable('consent_form_settings')) {
            return;
        }

        Schema::table('consent_form_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('consent_form_settings', 'health_split_consent')) {
                $table->boolean('health_split_consent')->default(true)->after('ask_photo');
            }
            if (! Schema::hasColumn('consent_form_settings', 'retention_notice_enabled')) {
                $table->boolean('retention_notice_enabled')->default(true)->after('health_split_consent');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('consent_form_settings')) {
            return;
        }

        Schema::table('consent_form_settings', function (Blueprint $table) {
            if (Schema::hasColumn('consent_form_settings', 'retention_notice_enabled')) {
                $table->dropColumn('retention_notice_enabled');
            }
            if (Schema::hasColumn('consent_form_settings', 'health_split_consent')) {
                $table->dropColumn('health_split_consent');
            }
        });
    }
};
