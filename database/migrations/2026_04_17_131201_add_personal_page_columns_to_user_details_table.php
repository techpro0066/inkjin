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
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'personal_page_background_image')) {
                $table->string('personal_page_background_image')->nullable()->after('availability_status');
            }
            if (! Schema::hasColumn('user_details', 'personal_page_color')) {
                $table->string('personal_page_color')->nullable()->after('personal_page_background_image');
            }
            if (! Schema::hasColumn('user_details', 'personal_page_tagline')) {
                $table->string('personal_page_tagline')->nullable()->after('personal_page_color');
            }
            if (! Schema::hasColumn('user_details', 'personal_page_description')) {
                $table->string('personal_page_description')->nullable()->after('personal_page_tagline');
            }
            if (! Schema::hasColumn('user_details', 'personal_page_name_alias')) {
                $table->string('personal_page_name_alias')->nullable()->after('personal_page_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            foreach ([
                'personal_page_name_alias',
                'personal_page_description',
                'personal_page_tagline',
                'personal_page_color',
                'personal_page_background_image',
            ] as $column) {
                if (Schema::hasColumn('user_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
