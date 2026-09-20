<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('artist_designs')) {
            return;
        }

        Schema::table('artist_designs', function (Blueprint $table) {
            if (! Schema::hasColumn('artist_designs', 'original_image')) {
                $table->string('original_image')->nullable()->after('image');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('artist_designs') || ! Schema::hasColumn('artist_designs', 'original_image')) {
            return;
        }

        Schema::table('artist_designs', function (Blueprint $table) {
            $table->dropColumn('original_image');
        });
    }
};
