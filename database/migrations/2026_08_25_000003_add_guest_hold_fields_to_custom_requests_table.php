<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            $table->boolean('guest_spot_held')->default(false)->after('guest_id');
            $table->timestamp('guest_hold_expires_at')->nullable()->after('guest_spot_held');
        });
    }

    public function down(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            $table->dropColumn(['guest_spot_held', 'guest_hold_expires_at']);
        });
    }
};
