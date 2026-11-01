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

        if (! Schema::hasColumn('guest_spots', 'guest_studio_invite_token')) {
            Schema::table('guest_spots', function (Blueprint $table) {
                $table->string('guest_studio_invite_token', 64)
                    ->nullable()
                    ->unique()
                    ->after('guest_studio_invite_status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('guest_spots')) {
            return;
        }

        if (Schema::hasColumn('guest_spots', 'guest_studio_invite_token')) {
            Schema::table('guest_spots', function (Blueprint $table) {
                $table->dropUnique(['guest_studio_invite_token']);
                $table->dropColumn('guest_studio_invite_token');
            });
        }
    }
};
