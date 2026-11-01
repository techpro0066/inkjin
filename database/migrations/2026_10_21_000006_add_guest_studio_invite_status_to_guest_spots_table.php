<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guest_spots')) {
            return;
        }

        if (! Schema::hasColumn('guest_spots', 'guest_studio_invite_status')) {
            Schema::table('guest_spots', function (Blueprint $table) {
                // pending | accepted | rejected | null (no invite / direct payout)
                $table->string('guest_studio_invite_status', 20)
                    ->nullable()
                    ->after('guest_studio_invite_email');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('guest_spots')) {
            return;
        }

        if (Schema::hasColumn('guest_spots', 'guest_studio_invite_status')) {
            Schema::table('guest_spots', function (Blueprint $table) {
                $table->dropColumn('guest_studio_invite_status');
            });
        }
    }
};
