<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('questions') || Schema::hasColumn('questions', 'question_type')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_type', 50)->default('other')->after('type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('questions') || ! Schema::hasColumn('questions', 'question_type')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });
    }
};
