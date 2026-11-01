<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waitlists', function (Blueprint $table) {
            if (! Schema::hasColumn('waitlists', 'guest_spot_id')) {
                $table->foreignId('guest_spot_id')
                    ->nullable()
                    ->after('custom_request_id')
                    ->constrained('guest_spots')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('waitlists', function (Blueprint $table) {
            if (Schema::hasColumn('waitlists', 'guest_spot_id')) {
                $table->dropConstrainedForeignId('guest_spot_id');
            }
        });
    }
};
