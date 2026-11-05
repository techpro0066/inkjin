<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_channels')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_channels', 'client_email_pending_text')) {
                $table->text('client_email_pending_text')->nullable()->after('custom_request_id');
            }
            if (! Schema::hasColumn('chat_channels', 'client_email_pending_at')) {
                $table->timestamp('client_email_pending_at')->nullable()->after('client_email_pending_text');
            }
            if (! Schema::hasColumn('chat_channels', 'client_email_send_after')) {
                $table->timestamp('client_email_send_after')->nullable()->index()->after('client_email_pending_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_channels')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            foreach (['client_email_send_after', 'client_email_pending_at', 'client_email_pending_text'] as $column) {
                if (Schema::hasColumn('chat_channels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
