<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'display_designs')) {
                $after = Schema::hasColumn('user_details', 'display_portfolio')
                    ? 'display_portfolio'
                    : (Schema::hasColumn('user_details', 'display_faq') ? 'display_faq' : null);

                $col = $table->boolean('display_designs')->default(true);
                if ($after) {
                    $col->after($after);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'display_designs')) {
                $table->dropColumn('display_designs');
            }
        });
    }
};
