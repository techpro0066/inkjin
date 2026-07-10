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
            // min_size = width (cm), max_size = height (cm); at least one required in app validation
            if (Schema::hasColumn('artist_designs', 'min_size')) {
                $table->integer('min_size')->nullable()->change();
            }
            if (Schema::hasColumn('artist_designs', 'max_size')) {
                $table->integer('max_size')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('artist_designs')) {
            return;
        }

        Schema::table('artist_designs', function (Blueprint $table) {
            if (Schema::hasColumn('artist_designs', 'min_size')) {
                $table->integer('min_size')->nullable(false)->change();
            }
            if (Schema::hasColumn('artist_designs', 'max_size')) {
                $table->integer('max_size')->nullable(false)->change();
            }
        });
    }
};
