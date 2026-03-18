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
            // Link to studios table
            $table->foreignId('studio_id')
                ->nullable()
                ->after('studio_email')
                ->constrained('studios')
                ->nullOnDelete();

            // Track studio payment status for this artist
            $table->enum('studio_payment_status', ['pending', 'approved', 'declined'])
                ->default('pending')
                ->after('studio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('studio_id');
            $table->dropColumn('studio_payment_status');
        });
    }
};
