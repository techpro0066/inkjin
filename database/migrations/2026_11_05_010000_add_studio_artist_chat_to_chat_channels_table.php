<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_channels')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_channels', 'channel_kind')) {
                $table->string('channel_kind', 32)->default('client_artist')->after('stream_channel_id');
            }

            if (! Schema::hasColumn('chat_channels', 'studio_user_id')) {
                $table->foreignId('studio_user_id')
                    ->nullable()
                    ->after('client_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        DB::table('chat_channels')
            ->where(function ($q) {
                $q->whereNull('channel_kind')->orWhere('channel_kind', '');
            })
            ->update(['channel_kind' => 'client_artist']);

        Schema::table('chat_channels', function (Blueprint $table) {
            if (Schema::hasColumn('chat_channels', 'client_user_id')) {
                try {
                    $table->dropForeign(['client_user_id']);
                } catch (\Throwable) {
                    //
                }
            }
        });

        if (Schema::hasColumn('chat_channels', 'client_user_id')) {
            DB::statement('ALTER TABLE chat_channels MODIFY client_user_id BIGINT UNSIGNED NULL');
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            if (Schema::hasColumn('chat_channels', 'client_user_id')) {
                $table->foreign('client_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });

        foreach (['chat_channels_booking_id_unique', 'booking_id'] as $unique) {
            try {
                Schema::table('chat_channels', function (Blueprint $table) use ($unique) {
                    if ($unique === 'booking_id') {
                        $table->dropUnique(['booking_id']);
                    } else {
                        $table->dropUnique($unique);
                    }
                });
                break;
            } catch (\Throwable) {
                //
            }
        }

        try {
            Schema::table('chat_channels', function (Blueprint $table) {
                $table->unique(['booking_id', 'channel_kind'], 'chat_channels_booking_kind_unique');
            });
        } catch (\Throwable) {
            //
        }

        try {
            Schema::table('chat_channels', function (Blueprint $table) {
                $table->index(['studio_user_id', 'artist_user_id'], 'chat_channels_studio_pair_index');
            });
        } catch (\Throwable) {
            //
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_channels')) {
            return;
        }

        Schema::table('chat_channels', function (Blueprint $table) {
            try {
                $table->dropUnique('chat_channels_booking_kind_unique');
            } catch (\Throwable) {
                //
            }
            try {
                $table->dropIndex('chat_channels_studio_pair_index');
            } catch (\Throwable) {
                //
            }
            if (Schema::hasColumn('chat_channels', 'studio_user_id')) {
                $table->dropConstrainedForeignId('studio_user_id');
            }
            if (Schema::hasColumn('chat_channels', 'channel_kind')) {
                $table->dropColumn('channel_kind');
            }
        });
    }
};
