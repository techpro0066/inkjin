<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('studio_requests')) {
            return;
        }

        Schema::table('studio_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('studio_requests', 'send_back_history')) {
                $table->json('send_back_history')->nullable()->after('reason_decline');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('studio_requests')) {
            return;
        }

        Schema::table('studio_requests', function (Blueprint $table) {
            if (Schema::hasColumn('studio_requests', 'send_back_history')) {
                $table->dropColumn('send_back_history');
            }
        });
    }
};
