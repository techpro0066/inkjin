<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'tattoo_id')) {
            return;
        }

        if ($this->foreignKeyExists('bookings', 'bookings_tattoo_id_foreign')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropForeign(['tattoo_id']);
            });
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('tattoo_id')->nullable()->change();
        });

        if (! $this->foreignKeyExists('bookings', 'bookings_tattoo_id_foreign')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreign('tattoo_id')->references('id')->on('inkjin_tattoos')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'tattoo_id')) {
            return;
        }

        // Bookings without a tattoo (custom bookings) rely on nullable tattoo_id.
        // Reverting to NOT NULL would fail or corrupt existing rows.
        if (DB::table('bookings')->whereNull('tattoo_id')->exists()) {
            return;
        }

        if ($this->foreignKeyExists('bookings', 'bookings_tattoo_id_foreign')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropForeign(['tattoo_id']);
            });
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('tattoo_id')->nullable(false)->change();
        });

        if (! $this->foreignKeyExists('bookings', 'bookings_tattoo_id_foreign')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreign('tattoo_id')->references('id')->on('inkjin_tattoos')->restrictOnDelete();
            });
        }
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
