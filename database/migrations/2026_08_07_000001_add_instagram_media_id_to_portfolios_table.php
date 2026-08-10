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
        if (! Schema::hasColumn('portfolios', 'instagram_media_id')) {
            return;
        }

        // MySQL may use this composite unique index for the user_id FK.
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('portfolios', function (Blueprint $table) {
            $indexes = collect(Schema::getConnection()->getSchemaBuilder()->getIndexes('portfolios'))->pluck('name');
            if ($indexes->contains('portfolios_user_instagram_media_unique')) {
                $table->dropUnique('portfolios_user_instagram_media_unique');
            }
            $table->dropColumn('instagram_media_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
