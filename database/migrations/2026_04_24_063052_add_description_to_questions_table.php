<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('questions')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'description')) {
                $table->text('description')->nullable()->after('question');
            }
            if (! Schema::hasColumn('questions', 'placeholder')) {
                $table->text('placeholder')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('questions')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            foreach (['placeholder', 'description'] as $column) {
                if (Schema::hasColumn('questions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
