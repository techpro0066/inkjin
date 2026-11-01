<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_payouts') || ! Schema::hasColumn('user_payouts', 'payout_mode')) {
            return;
        }

        DB::table('user_payouts')
            ->where(function ($query) {
                $query->where('payout_mode', 'manual')
                    ->orWhereNull('payout_mode');
            })
            ->update([
                'payout_mode' => 'automatic',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible: previous manual/automatic mix is not recoverable.
    }
};
