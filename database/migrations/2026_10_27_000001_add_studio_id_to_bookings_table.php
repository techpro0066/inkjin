<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'studio_id')) {
                $table->foreignId('studio_id')
                    ->nullable()
                    ->after('artist_user_id')
                    ->constrained('studios')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'studio_id')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('studio_id');
        });
    }
};
