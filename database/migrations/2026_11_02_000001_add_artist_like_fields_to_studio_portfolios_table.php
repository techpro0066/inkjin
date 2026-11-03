<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('studio_portfolios')) {
            return;
        }

        Schema::table('studio_portfolios', function (Blueprint $table) {
            if (! Schema::hasColumn('studio_portfolios', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('studio_portfolios', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('image');
            }
            if (! Schema::hasColumn('studio_portfolios', 'primary_style')) {
                $table->string('primary_style')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('studio_portfolios', 'other_styles')) {
                $table->json('other_styles')->nullable()->after('primary_style');
            }
            if (! Schema::hasColumn('studio_portfolios', 'placement')) {
                $table->string('placement')->nullable()->after('other_styles');
            }
            if (! Schema::hasColumn('studio_portfolios', 'color')) {
                $table->string('color')->nullable()->after('placement');
            }
            if (! Schema::hasColumn('studio_portfolios', 'tags')) {
                $table->json('tags')->nullable()->after('color');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('studio_portfolios')) {
            return;
        }

        Schema::table('studio_portfolios', function (Blueprint $table) {
            foreach ([
                'description',
                'is_active',
                'primary_style',
                'other_styles',
                'placement',
                'color',
                'tags',
            ] as $column) {
                if (Schema::hasColumn('studio_portfolios', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
