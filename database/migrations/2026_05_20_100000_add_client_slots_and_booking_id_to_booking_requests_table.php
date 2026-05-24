<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_requests')) {
            return;
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_requests', 'client_consultation_slots')) {
                $table->json('client_consultation_slots')->nullable()->after('artist_notes_to_client');
            }
            if (! Schema::hasColumn('booking_requests', 'client_session_slots')) {
                $table->json('client_session_slots')->nullable()->after('client_consultation_slots');
            }
            if (! Schema::hasColumn('booking_requests', 'booking_id')) {
                $table->unsignedBigInteger('booking_id')->nullable()->after('status');
            }
        });

        if (
            Schema::hasColumn('booking_requests', 'booking_id')
            && ! $this->foreignKeyExists('booking_requests', 'booking_requests_booking_id_foreign')
        ) {
            Schema::table('booking_requests', function (Blueprint $table) {
                $table->foreign('booking_id')
                    ->references('id')
                    ->on('bookings')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_requests')) {
            return;
        }

        Schema::table('booking_requests', function (Blueprint $table) {
            if ($this->foreignKeyExists('booking_requests', 'booking_requests_booking_id_foreign')) {
                $table->dropForeign(['booking_id']);
            }
            foreach (['client_consultation_slots', 'client_session_slots', 'booking_id'] as $column) {
                if (Schema::hasColumn('booking_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, $table, $constraintName, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};
