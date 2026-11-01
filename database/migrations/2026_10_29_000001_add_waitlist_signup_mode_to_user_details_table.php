<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'waitlist_signup_mode')) {
                $table->string('waitlist_signup_mode', 32)->default('name_email')->after('availability_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'waitlist_signup_mode')) {
                $table->dropColumn('waitlist_signup_mode');
            }
        });
    }
};
