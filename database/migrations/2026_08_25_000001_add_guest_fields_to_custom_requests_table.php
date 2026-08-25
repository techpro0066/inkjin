<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('artist_id');
            $table->foreignId('guest_id')
                ->nullable()
                ->after('is_guest')
                ->constrained('guest_spots')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_id');
            $table->dropColumn('is_guest');
        });
    }
};
