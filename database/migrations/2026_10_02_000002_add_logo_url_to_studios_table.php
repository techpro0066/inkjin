<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            if (! Schema::hasColumn('studios', 'logo_url')) {
                $after = Schema::hasColumn('studios', 'image_url') ? 'image_url' : 'email';
                $table->string('logo_url')->nullable()->after($after);
            }
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            if (Schema::hasColumn('studios', 'logo_url')) {
                $table->dropColumn('logo_url');
            }
        });
    }
};
