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

        DB::statement("ALTER TABLE questions MODIFY COLUMN type ENUM('input', 'textarea', 'toggle', 'select', 'image', 'radio', 'style', 'placement', 'sizes') NOT NULL");

        // Convert seeded "What size?" radio questions to the catalog sizes type.
        DB::table('questions')
            ->where('question', 'What size?')
            ->where('type', 'radio')
            ->update([
                'type' => 'sizes',
                'options' => null,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('questions') || ! Schema::hasColumn('questions', 'type')) {
            return;
        }

        DB::table('questions')->where('type', 'sizes')->update(['type' => 'radio']);

        DB::statement("ALTER TABLE questions MODIFY COLUMN type ENUM('input', 'textarea', 'toggle', 'select', 'image', 'radio', 'style', 'placement') NOT NULL");
    }
};
