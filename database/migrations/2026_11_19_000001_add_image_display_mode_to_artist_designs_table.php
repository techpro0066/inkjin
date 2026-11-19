<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('artist_designs') || Schema::hasColumn('artist_designs', 'image_display_mode')) {
            return;
        }

        Schema::table('artist_designs', function (Blueprint $table) {
            $table->string('image_display_mode', 16)->default('whole')->after('original_image');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('artist_designs') || ! Schema::hasColumn('artist_designs', 'image_display_mode')) {
            return;
        }

        Schema::table('artist_designs', function (Blueprint $table) {
            $table->dropColumn('image_display_mode');
        });
    }
};
