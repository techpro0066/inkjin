<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * studios table is created after user_details; add FK once both exist.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasTable('studios')) {
            return;
        }

        if (! Schema::hasColumn('user_details', 'studio_id')) {
            return;
        }

        if ($this->foreignKeyExists('user_details', 'user_details_studio_id_foreign')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->foreign('studio_id')
                ->references('id')
                ->on('studios')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        if (! $this->foreignKeyExists('user_details', 'user_details_studio_id_foreign')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropForeign(['studio_id']);
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
