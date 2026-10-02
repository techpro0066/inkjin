<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            if (! Schema::hasColumn('studios', 'username')) {
                $after = Schema::hasColumn('studios', 'name') ? 'name' : 'email';
                $table->string('username', 30)->nullable()->unique()->after($after);
            }
            if (! Schema::hasColumn('studios', 'links')) {
                $after = Schema::hasColumn('studios', 'contact_email')
                    ? 'contact_email'
                    : (Schema::hasColumn('studios', 'business_phone') ? 'business_phone' : 'email');
                $table->json('links')->nullable()->after($after);
            }
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            if (Schema::hasColumn('studios', 'links')) {
                $table->dropColumn('links');
            }
            if (Schema::hasColumn('studios', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
        });
    }
};
