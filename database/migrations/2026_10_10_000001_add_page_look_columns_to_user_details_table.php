<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'page_look_mode')) {
                $table->string('page_look_mode', 20)->nullable()->after('personal_page_color');
            }
            if (! Schema::hasColumn('user_details', 'page_look_scheme')) {
                $table->string('page_look_scheme', 64)->nullable()->after('page_look_mode');
            }
            if (! Schema::hasColumn('user_details', 'page_bg_desktop')) {
                $table->string('page_bg_desktop')->nullable()->after('page_look_scheme');
            }
            if (! Schema::hasColumn('user_details', 'page_bg_mobile')) {
                $table->string('page_bg_mobile')->nullable()->after('page_bg_desktop');
            }
            if (! Schema::hasColumn('user_details', 'page_bg_lum')) {
                $table->decimal('page_bg_lum', 8, 5)->nullable()->after('page_bg_mobile');
            }
            if (! Schema::hasColumn('user_details', 'page_bg_avg')) {
                $table->string('page_bg_avg', 16)->nullable()->after('page_bg_lum');
            }
            if (! Schema::hasColumn('user_details', 'page_box_color')) {
                $table->string('page_box_color', 16)->nullable()->after('page_bg_avg');
            }
            if (! Schema::hasColumn('user_details', 'page_box_effect')) {
                $table->string('page_box_effect', 16)->nullable()->after('page_box_color');
            }
            if (! Schema::hasColumn('user_details', 'page_box_opacity')) {
                $table->unsignedTinyInteger('page_box_opacity')->nullable()->after('page_box_effect');
            }
            if (! Schema::hasColumn('user_details', 'page_title_mode')) {
                $table->string('page_title_mode', 16)->nullable()->after('page_box_opacity');
            }
            if (! Schema::hasColumn('user_details', 'page_title_color')) {
                $table->string('page_title_color', 16)->nullable()->after('page_title_mode');
            }
            if (! Schema::hasColumn('user_details', 'page_text_mode')) {
                $table->string('page_text_mode', 16)->nullable()->after('page_title_color');
            }
            if (! Schema::hasColumn('user_details', 'page_box_radius')) {
                $table->string('page_box_radius', 8)->nullable()->after('page_text_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $cols = [
                'page_look_mode',
                'page_look_scheme',
                'page_bg_desktop',
                'page_bg_mobile',
                'page_bg_lum',
                'page_bg_avg',
                'page_box_color',
                'page_box_effect',
                'page_box_opacity',
                'page_title_mode',
                'page_title_color',
                'page_text_mode',
                'page_box_radius',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('user_details', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
