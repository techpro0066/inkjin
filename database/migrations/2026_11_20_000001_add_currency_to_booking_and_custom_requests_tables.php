<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_requests') && ! Schema::hasColumn('booking_requests', 'currency')) {
            Schema::table('booking_requests', function (Blueprint $table) {
                $table->string('currency', 3)->nullable()->after('urgency');
            });
        }

        if (Schema::hasTable('custom_requests') && ! Schema::hasColumn('custom_requests', 'currency')) {
            Schema::table('custom_requests', function (Blueprint $table) {
                $table->string('currency', 3)->nullable()->after('urgency');
            });
        }

        $this->backfill('booking_requests');
        $this->backfill('custom_requests');
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_requests') && Schema::hasColumn('booking_requests', 'currency')) {
            Schema::table('booking_requests', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        if (Schema::hasTable('custom_requests') && Schema::hasColumn('custom_requests', 'currency')) {
            Schema::table('custom_requests', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }

    private function backfill(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'currency')) {
            return;
        }

        // Prefer linked booking currency, else artist profile currency at backfill time.
        DB::statement("
            UPDATE {$table} AS r
            LEFT JOIN bookings AS b ON b.id = r.booking_id
            LEFT JOIN user_details AS ud ON ud.user_id = r.artist_id
            SET r.currency = UPPER(COALESCE(
                NULLIF(TRIM(b.currency), ''),
                NULLIF(TRIM(ud.currency), ''),
                'EUR'
            ))
            WHERE r.currency IS NULL OR TRIM(r.currency) = ''
        ");
    }
};
