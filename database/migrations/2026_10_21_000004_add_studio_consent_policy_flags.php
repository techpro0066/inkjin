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

        if (! Schema::hasColumn('studios', 'use_studio_consent_form')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->boolean('use_studio_consent_form')->default(false);
            });
        }
        if (! Schema::hasColumn('studios', 'allow_artist_own_consent')) {
            Schema::table('studios', function (Blueprint $table) {
                $table->boolean('allow_artist_own_consent')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }

        Schema::table('studios', function (Blueprint $table) {
            if (Schema::hasColumn('studios', 'allow_artist_own_consent')) {
                $table->dropColumn('allow_artist_own_consent');
            }
            if (Schema::hasColumn('studios', 'use_studio_consent_form')) {
                $table->dropColumn('use_studio_consent_form');
            }
        });
    }
};
