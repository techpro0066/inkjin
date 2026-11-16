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

        if (! Schema::hasColumn('guest_spots', 'invite_studio')) {
            Schema::table('guest_spots', function (Blueprint $table) {
                $table->boolean('invite_studio')->default(false)->after('payout_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('guest_spots') && Schema::hasColumn('guest_spots', 'invite_studio')) {
            Schema::table('guest_spots', function (Blueprint $table) {
                $table->dropColumn('invite_studio');
            });
        }
    }
};
