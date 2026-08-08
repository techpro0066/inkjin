<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            if (! Schema::hasColumn('portfolios', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('tags');
                $table->index(['user_id', 'sort_order'], 'portfolios_user_sort_order_index');
            }
        });

        $userIds = DB::table('portfolios')->distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            $rows = DB::table('portfolios')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id']);

            foreach ($rows as $index => $row) {
                DB::table('portfolios')
                    ->where('id', $row->id)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            if (Schema::hasColumn('portfolios', 'sort_order')) {
                $table->dropIndex('portfolios_user_sort_order_index');
                $table->dropColumn('sort_order');
            }
        });
    }
};
