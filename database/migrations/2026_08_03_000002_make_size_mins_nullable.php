<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sizes', function (Blueprint $table) {
            $table->decimal('cm_min', 8, 2)->nullable()->change();
            $table->decimal('in_min', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sizes', function (Blueprint $table) {
            $table->decimal('cm_min', 8, 2)->nullable(false)->change();
            $table->decimal('in_min', 8, 2)->nullable(false)->change();
        });
    }
};
