<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_answers', function (Blueprint $table) {
            if (! Schema::hasColumn('consent_answers', 'age_verified_by_artist')) {
                $table->boolean('age_verified_by_artist')->default(false)->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consent_answers', function (Blueprint $table) {
            if (Schema::hasColumn('consent_answers', 'age_verified_by_artist')) {
                $table->dropColumn('age_verified_by_artist');
            }
        });
    }
};
