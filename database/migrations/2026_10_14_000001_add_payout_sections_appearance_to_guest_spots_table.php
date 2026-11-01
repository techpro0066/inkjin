<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guest_spots')) {
            return;
        }

        Schema::table('guest_spots', function (Blueprint $table) {
            if (! Schema::hasColumn('guest_spots', 'studio_id')) {
                $table->foreignId('studio_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('studios')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('guest_spots', 'payout_mode')) {
                $table->string('payout_mode', 16)->default('direct')->after('longitude');
            }

            if (! Schema::hasColumn('guest_spots', 'artist_percent')) {
                $table->unsignedTinyInteger('artist_percent')->nullable()->after('payout_mode');
            }

            if (! Schema::hasColumn('guest_spots', 'guest_studio_invite_email')) {
                $table->string('guest_studio_invite_email')->nullable()->after('artist_percent');
            }

            if (! Schema::hasColumn('guest_spots', 'show_designs')) {
                $table->boolean('show_designs')->default(false)->after('guest_studio_invite_email');
            }

            if (! Schema::hasColumn('guest_spots', 'show_portfolio')) {
                $table->boolean('show_portfolio')->default(false)->after('show_designs');
            }

            if (! Schema::hasColumn('guest_spots', 'show_rates')) {
                $table->boolean('show_rates')->default(false)->after('show_portfolio');
            }

            if (! Schema::hasColumn('guest_spots', 'show_policies')) {
                $table->boolean('show_policies')->default(true)->after('show_rates');
            }

            if (! Schema::hasColumn('guest_spots', 'show_faq')) {
                $table->boolean('show_faq')->default(true)->after('show_policies');
            }

            if (! Schema::hasColumn('guest_spots', 'selected_design_ids')) {
                $table->json('selected_design_ids')->nullable()->after('show_faq');
            }

            if (! Schema::hasColumn('guest_spots', 'selected_portfolio_ids')) {
                $table->json('selected_portfolio_ids')->nullable()->after('selected_design_ids');
            }

            if (! Schema::hasColumn('guest_spots', 'banner_path')) {
                $table->string('banner_path', 500)->nullable()->after('selected_portfolio_ids');
            }

            if (! Schema::hasColumn('guest_spots', 'color_scheme')) {
                $table->string('color_scheme', 64)->nullable()->after('banner_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('guest_spots')) {
            return;
        }

        Schema::table('guest_spots', function (Blueprint $table) {
            if (Schema::hasColumn('guest_spots', 'studio_id')) {
                $table->dropConstrainedForeignId('studio_id');
            }

            $columns = [
                'payout_mode',
                'artist_percent',
                'guest_studio_invite_email',
                'show_designs',
                'show_portfolio',
                'show_rates',
                'show_policies',
                'show_faq',
                'selected_design_ids',
                'selected_portfolio_ids',
                'banner_path',
                'color_scheme',
            ];

            $existing = array_values(array_filter(
                $columns,
                fn (string $column) => Schema::hasColumn('guest_spots', $column)
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
