<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('studios')) {
            $columns = [
                'show_artists_flash' => fn (Blueprint $table) => $table->boolean('show_artists_flash')->default(true),
                'show_artists_portfolio' => fn (Blueprint $table) => $table->boolean('show_artists_portfolio')->default(true),
                'instagram_user_id' => fn (Blueprint $table) => $table->string('instagram_user_id')->nullable(),
                'instagram_username' => fn (Blueprint $table) => $table->string('instagram_username')->nullable(),
                'instagram_profile_picture' => fn (Blueprint $table) => $table->string('instagram_profile_picture')->nullable(),
                'instagram_access_token' => fn (Blueprint $table) => $table->text('instagram_access_token')->nullable(),
                'instagram_token_expires_at' => fn (Blueprint $table) => $table->timestamp('instagram_token_expires_at')->nullable(),
                'instagram_connected_at' => fn (Blueprint $table) => $table->timestamp('instagram_connected_at')->nullable(),
            ];

            foreach ($columns as $name => $adder) {
                if (! Schema::hasColumn('studios', $name)) {
                    Schema::table('studios', function (Blueprint $table) use ($adder) {
                        $adder($table);
                    });
                }
            }
        }

        if (! Schema::hasTable('studio_portfolios')) {
            Schema::create('studio_portfolios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('studio_id')->constrained('studios')->cascadeOnDelete();
                $table->string('title');
                $table->string('image');
                $table->string('attribution', 20)->default('pending');
                $table->foreignId('artist_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('source', 20)->default('manual');
                $table->string('instagram_media_id')->nullable();
                $table->string('instagram_media_type')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['studio_id', 'sort_order']);
                $table->index(['studio_id', 'instagram_media_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_portfolios');

        if (! Schema::hasTable('studios')) {
            return;
        }

        foreach ([
            'instagram_connected_at',
            'instagram_token_expires_at',
            'instagram_access_token',
            'instagram_profile_picture',
            'instagram_username',
            'instagram_user_id',
            'show_artists_portfolio',
            'show_artists_flash',
        ] as $column) {
            if (Schema::hasColumn('studios', $column)) {
                Schema::table('studios', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
