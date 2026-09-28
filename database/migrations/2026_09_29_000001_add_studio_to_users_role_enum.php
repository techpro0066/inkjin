<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin', 'artist', 'user', 'studio') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')->where('role', 'studio')->update(['role' => 'artist']);

        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin', 'artist', 'user') NOT NULL DEFAULT 'user'");
    }
};
