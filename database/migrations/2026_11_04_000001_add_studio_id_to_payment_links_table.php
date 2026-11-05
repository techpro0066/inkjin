<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_links', 'studio_id')) {
                $table->foreignId('studio_id')
                    ->nullable()
                    ->after('artist_id')
                    ->constrained('studios')
                    ->nullOnDelete();
                $table->index(['studio_id', 'status']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_links')) {
            return;
        }

        Schema::table('payment_links', function (Blueprint $table) {
            if (Schema::hasColumn('payment_links', 'studio_id')) {
                $table->dropConstrainedForeignId('studio_id');
            }
        });
    }
};
