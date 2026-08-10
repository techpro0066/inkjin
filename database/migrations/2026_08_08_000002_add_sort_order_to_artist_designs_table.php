<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_designs', function (Blueprint $table) {
            if (! Schema::hasColumn('artist_designs', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('tags');
                $table->index(['user_id', 'sort_order'], 'artist_designs_user_sort_order_index');
            }
        });

        $userIds = DB::table('artist_designs')->distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            $rows = DB::table('artist_designs')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id']);

            foreach ($rows as $index => $row) {
                DB::table('artist_designs')
                    ->where('id', $row->id)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('artist_designs', 'sort_order')) {
            return;
        }

        // MySQL may be using the composite (user_id, sort_order) index for the FK,
        // so drop the FK first, then the index/column, then restore the FK.
        Schema::table('artist_designs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('artist_designs', function (Blueprint $table) {
            $sm = Schema::getConnection()->getSchemaBuilder();
            $indexes = collect($sm->getIndexes('artist_designs'))->pluck('name');
            if ($indexes->contains('artist_designs_user_sort_order_index')) {
                $table->dropIndex('artist_designs_user_sort_order_index');
            }
            $table->dropColumn('sort_order');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
