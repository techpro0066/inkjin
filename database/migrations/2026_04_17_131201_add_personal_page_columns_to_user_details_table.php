<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('personal_page_background_image')->nullable()->after('availability_status');
            $table->string('personal_page_color')->nullable()->after('personal_page_background_image');
            $table->string('personal_page_tagline')->nullable()->after('personal_page_color');
            $table->string('personal_page_description')->nullable()->after('personal_page_tagline');
            $table->string('personal_page_name_alias')->nullable()->after('personal_page_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('personal_page_background_image');
            $table->dropColumn('personal_page_color');
            $table->dropColumn('personal_page_tagline');
            $table->dropColumn('personal_page_description');
            $table->dropColumn('personal_page_name_alias');
        });
    }
};
