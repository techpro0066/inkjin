<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waitlists', function (Blueprint $table) {
            if (! Schema::hasColumn('waitlists', 'custom_request_id')) {
                $table->foreignId('custom_request_id')
                    ->nullable()
                    ->after('email')
                    ->constrained('custom_requests')
                    ->nullOnDelete();
            }
        });

        Schema::table('custom_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_requests', 'from_waitlist')) {
                $table->boolean('from_waitlist')->default(false)->after('is_guest');
            }
            if (! Schema::hasColumn('custom_requests', 'waitlist_released_at')) {
                $table->timestamp('waitlist_released_at')->nullable()->after('from_waitlist');
            }
        });
    }

    public function down(): void
    {
        Schema::table('waitlists', function (Blueprint $table) {
            if (Schema::hasColumn('waitlists', 'custom_request_id')) {
                $table->dropConstrainedForeignId('custom_request_id');
            }
        });

        Schema::table('custom_requests', function (Blueprint $table) {
            if (Schema::hasColumn('custom_requests', 'waitlist_released_at')) {
                $table->dropColumn('waitlist_released_at');
            }
            if (Schema::hasColumn('custom_requests', 'from_waitlist')) {
                $table->dropColumn('from_waitlist');
            }
        });
    }
};
