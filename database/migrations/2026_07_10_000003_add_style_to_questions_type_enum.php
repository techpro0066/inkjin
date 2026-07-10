<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('questions') || ! Schema::hasColumn('questions', 'type')) {
            return;
        }

        DB::statement("ALTER TABLE questions MODIFY COLUMN type ENUM('input', 'textarea', 'toggle', 'select', 'image', 'radio', 'style') NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('questions') || ! Schema::hasColumn('questions', 'type')) {
            return;
        }

        DB::table('questions')->where('type', 'style')->update(['type' => 'select']);

        DB::statement("ALTER TABLE questions MODIFY COLUMN type ENUM('input', 'textarea', 'toggle', 'select', 'image', 'radio') NOT NULL");
    }
};
