<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'instagram_profile_picture')) {
                $table->string('instagram_profile_picture', 500)->nullable()->after('instagram_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'instagram_profile_picture')) {
                $table->dropColumn('instagram_profile_picture');
            }
        });
    }
};
