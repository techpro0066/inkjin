<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'display_tagline')) {
                $table->boolean('display_tagline')->default(true)->after('display_policies');
            }
            if (! Schema::hasColumn('user_details', 'display_bio')) {
                $table->boolean('display_bio')->default(true)->after('display_tagline');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            foreach (['display_bio', 'display_tagline'] as $column) {
                if (Schema::hasColumn('user_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
