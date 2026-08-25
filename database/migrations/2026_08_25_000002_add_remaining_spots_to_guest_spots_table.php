<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_spots', function (Blueprint $table) {
            $table->unsignedSmallInteger('remaining_spots')
                ->default(0)
                ->after('number_of_spots');
        });

        DB::table('guest_spots')->update([
            'remaining_spots' => DB::raw('number_of_spots'),
        ]);
    }

    public function down(): void
    {
        Schema::table('guest_spots', function (Blueprint $table) {
            $table->dropColumn('remaining_spots');
        });
    }
};
