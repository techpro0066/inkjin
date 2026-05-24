<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custom_requests') || Schema::hasColumn('custom_requests', 'client_consultation_slots')) {
            return;
        }

        Schema::table('custom_requests', function (Blueprint $table) {
            $table->json('client_consultation_slots')->nullable()->after('client_session_slots');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('custom_requests') || ! Schema::hasColumn('custom_requests', 'client_consultation_slots')) {
            return;
        }

        Schema::table('custom_requests', function (Blueprint $table) {
            $table->dropColumn('client_consultation_slots');
        });
    }
};
