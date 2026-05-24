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
        if (! Schema::hasTable('user_details') || Schema::hasColumn('user_details', 'availability_status')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->enum('availability_status', ['design_custom', 'design_only', 'custom_only', 'closed'])
                ->default('closed')
                ->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasColumn('user_details', 'availability_status')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('availability_status');
        });
    }
};
