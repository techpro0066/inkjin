<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_form_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('consent_form_settings', 'send_automatically')) {
                $table->boolean('send_automatically')->default(false)->after('ask_photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consent_form_settings', function (Blueprint $table) {
            if (Schema::hasColumn('consent_form_settings', 'send_automatically')) {
                $table->dropColumn('send_automatically');
            }
        });
    }
};
