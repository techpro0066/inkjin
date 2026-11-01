<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            if (! Schema::hasColumn('studios', 'description')) {
                $table->text('description')->nullable()->after('links');
            }
            if (! Schema::hasColumn('studios', 'show_map')) {
                $table->boolean('show_map')->default(true)->after('description');
            }
            if (! Schema::hasColumn('studios', 'show_artist_styles')) {
                $table->boolean('show_artist_styles')->default(true)->after('show_map');
            }
            if (! Schema::hasColumn('studios', 'tattoo_styles')) {
                $table->json('tattoo_styles')->nullable()->after('show_artist_styles');
            }
            if (! Schema::hasColumn('studios', 'extra_services')) {
                $table->json('extra_services')->nullable()->after('tattoo_styles');
            }
            if (! Schema::hasColumn('studios', 'accepts_walk_ins')) {
                $table->boolean('accepts_walk_ins')->default(false)->after('extra_services');
            }
            if (! Schema::hasColumn('studios', 'walk_ins_note')) {
                $table->string('walk_ins_note', 60)->nullable()->after('accepts_walk_ins');
            }
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            foreach ([
                'walk_ins_note',
                'accepts_walk_ins',
                'extra_services',
                'tattoo_styles',
                'show_artist_styles',
                'show_map',
                'description',
            ] as $column) {
                if (Schema::hasColumn('studios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
