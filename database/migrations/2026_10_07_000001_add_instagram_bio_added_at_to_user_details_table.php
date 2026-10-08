<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'instagram_bio_added_at')) {
                $table->timestamp('instagram_bio_added_at')->nullable()->after('welcome_seen_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'instagram_bio_added_at')) {
                $table->dropColumn('instagram_bio_added_at');
            }
        });
    }
};
