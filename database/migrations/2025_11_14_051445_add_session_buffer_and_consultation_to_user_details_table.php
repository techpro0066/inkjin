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
            $table->integer('session_buffer_period')->nullable()->after('reschedule_times')->comment('Buffer period in minutes between sessions');
            $table->boolean('require_consultation')->default(false)->after('session_buffer_period')->comment('Require consultation session when booking a tattoo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn(['session_buffer_period', 'require_consultation']);
        });
    }
};
