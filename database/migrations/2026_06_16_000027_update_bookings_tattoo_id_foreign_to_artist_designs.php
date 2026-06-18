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

        if (! Schema::hasTable('artist_designs')) {
            return;
        }

        if ($this->foreignKeyExists('bookings', 'bookings_tattoo_id_foreign')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropForeign(['tattoo_id']);
            });
        }

        $validDesignIds = DB::table('artist_designs')->pluck('id');
        if ($validDesignIds->isNotEmpty()) {
            DB::table('bookings')
                ->whereNotNull('tattoo_id')
                ->whereNotIn('tattoo_id', $validDesignIds)
                ->update(['tattoo_id' => null]);
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('tattoo_id')
                ->references('id')
                ->on('artist_designs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'tattoo_id')) {
            return;
        }

        if ($this->foreignKeyExists('bookings', 'bookings_tattoo_id_foreign')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropForeign(['tattoo_id']);
            });
        }

        if (! Schema::hasTable('inkjin_tattoos')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('tattoo_id')
                ->references('id')
                ->on('inkjin_tattoos')
                ->restrictOnDelete();
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
