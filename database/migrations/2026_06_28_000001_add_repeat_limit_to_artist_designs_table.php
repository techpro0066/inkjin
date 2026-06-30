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
            if (! Schema::hasColumn('artist_designs', 'repeat_limit')) {
                $table->unsignedSmallInteger('repeat_limit')->nullable()->after('is_repeatable');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('artist_designs')) {
            return;
        }

        Schema::table('artist_designs', function (Blueprint $table) {
            if (Schema::hasColumn('artist_designs', 'repeat_limit')) {
                $table->dropColumn('repeat_limit');
            }
        });
    }
};
