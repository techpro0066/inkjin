<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('instagram_user_id')->nullable()->after('google_calendar_id');
            $table->string('instagram_username')->nullable()->after('instagram_user_id');
            $table->text('instagram_access_token')->nullable()->after('instagram_username');
            $table->timestamp('instagram_token_expires_at')->nullable()->after('instagram_access_token');
            $table->timestamp('instagram_connected_at')->nullable()->after('instagram_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_user_id',
                'instagram_username',
                'instagram_access_token',
                'instagram_token_expires_at',
                'instagram_connected_at',
            ]);
        });
    }
};
