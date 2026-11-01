<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'guest_waitlist_auto_notify')) {
                $table->boolean('guest_waitlist_auto_notify')
                    ->default(true)
                    ->after('waitlist_signup_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'guest_waitlist_auto_notify')) {
                $table->dropColumn('guest_waitlist_auto_notify');
            }
        });
    }
};
