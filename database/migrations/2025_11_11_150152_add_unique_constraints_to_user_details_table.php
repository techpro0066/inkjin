<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            // Add unique indexes to user_name and mobile_number
            $table->unique('user_name');
            $table->unique('mobile_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            // Drop unique indexes
            $table->dropUnique(['user_name']);
            $table->dropUnique(['mobile_number']);
        });
    }
};
