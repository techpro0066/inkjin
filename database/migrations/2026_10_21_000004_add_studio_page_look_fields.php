<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        $columns = [
            'page_look_mode' => fn (Blueprint $table) => $table->string('page_look_mode', 20)->nullable(),
            'page_look_scheme' => fn (Blueprint $table) => $table->string('page_look_scheme', 64)->nullable(),
            'page_banner' => fn (Blueprint $table) => $table->string('page_banner')->nullable(),
            'page_bg_desktop' => fn (Blueprint $table) => $table->string('page_bg_desktop')->nullable(),
            'page_bg_mobile' => fn (Blueprint $table) => $table->string('page_bg_mobile')->nullable(),
            'page_bg_lum' => fn (Blueprint $table) => $table->decimal('page_bg_lum', 8, 5)->nullable(),
            'page_bg_avg' => fn (Blueprint $table) => $table->string('page_bg_avg', 16)->nullable(),
            'page_box_color' => fn (Blueprint $table) => $table->string('page_box_color', 16)->nullable(),
            'page_box_effect' => fn (Blueprint $table) => $table->string('page_box_effect', 16)->nullable(),
            'page_box_opacity' => fn (Blueprint $table) => $table->unsignedTinyInteger('page_box_opacity')->nullable(),
            'page_title_mode' => fn (Blueprint $table) => $table->string('page_title_mode', 16)->nullable(),
            'page_title_color' => fn (Blueprint $table) => $table->string('page_title_color', 16)->nullable(),
            'page_text_mode' => fn (Blueprint $table) => $table->string('page_text_mode', 16)->nullable(),
            'page_box_radius' => fn (Blueprint $table) => $table->string('page_box_radius', 8)->nullable(),
        ];

        foreach ($columns as $name => $adder) {
            if (! Schema::hasColumn('studios', $name)) {
                Schema::table('studios', function (Blueprint $table) use ($adder) {
                    $adder($table);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        $cols = [
            'page_look_mode',
            'page_look_scheme',
            'page_banner',
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
            if (Schema::hasColumn('studios', $col)) {
                Schema::table('studios', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }
};
