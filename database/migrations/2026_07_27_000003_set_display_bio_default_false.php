<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasColumn('user_details', 'display_bio')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->boolean('display_bio')->default(false)->change();
        });

        DB::table('user_details')
            ->where(function ($query) {
                $query->whereNull('personal_page_description')
                    ->orWhereRaw("TRIM(personal_page_description) = ''");
            })
            ->update(['display_bio' => false]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details') || ! Schema::hasColumn('user_details', 'display_bio')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            $table->boolean('display_bio')->default(true)->change();
        });
    }
};
