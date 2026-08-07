<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            if (! Schema::hasColumn('portfolios', 'instagram_media_id')) {
                $table->string('instagram_media_id')->nullable()->after('tags');
                $table->unique(['user_id', 'instagram_media_id'], 'portfolios_user_instagram_media_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            if (Schema::hasColumn('portfolios', 'instagram_media_id')) {
                $table->dropUnique('portfolios_user_instagram_media_unique');
                $table->dropColumn('instagram_media_id');
            }
        });
    }
};
