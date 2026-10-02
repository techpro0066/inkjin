<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            if (! Schema::hasColumn('studios', 'owner_is_artist')) {
                $after = Schema::hasColumn('studios', 'links')
                    ? 'links'
                    : (Schema::hasColumn('studios', 'contact_email') ? 'contact_email' : 'email');
                $table->boolean('owner_is_artist')->nullable()->after($after);
            }
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            if (Schema::hasColumn('studios', 'owner_is_artist')) {
                $table->dropColumn('owner_is_artist');
            }
        });
    }
};
