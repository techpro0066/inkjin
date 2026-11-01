<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('studios') || ! Schema::hasTable('studio_payouts')) {
            return;
        }

        if (! Schema::hasColumn('studios', 'stripe_account_id')) {
            return;
        }

        $studios = DB::table('studios')
            ->select(['id', 'stripe_account_id', 'stripe_requirement', 'country', 'created_at', 'updated_at'])
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->orderBy('id')
            ->get();

        $now = now();

        foreach ($studios as $studio) {
            $accountId = trim((string) ($studio->stripe_account_id ?? ''));
            if ($accountId === '') {
                continue;
            }

            $exists = DB::table('studio_payouts')
                ->where('studio_id', $studio->id)
                ->where('stripe_account_id', $accountId)
                ->exists();

            if ($exists) {
                continue;
            }

            $hasActive = DB::table('studio_payouts')
                ->where('studio_id', $studio->id)
                ->where('is_active', true)
                ->exists();

            $country = strtoupper(trim((string) ($studio->country ?? '')));
            if (strlen($country) !== 2) {
                $country = null;
            }

            DB::table('studio_payouts')->insert([
                'studio_id' => $studio->id,
                'status' => ! empty($studio->stripe_requirement) ? 'pending' : 'approved',
                'is_active' => ! $hasActive,
                'stripe_account_id' => $accountId,
                'stripe_requirement' => (bool) ($studio->stripe_requirement ?? false),
                'stripe_requirement_email_sent_at' => null,
                'payout_bank_country' => $country,
                'connected_at' => $studio->updated_at ?? $studio->created_at ?? $now,
                'disconnected_at' => null,
                'created_at' => $studio->created_at ?? $now,
                'updated_at' => $studio->updated_at ?? $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('studio_payouts') || ! Schema::hasTable('studios')) {
            return;
        }

        if (! Schema::hasColumn('studios', 'stripe_account_id')) {
            return;
        }

        $actives = DB::table('studio_payouts')
            ->where('is_active', true)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->orderBy('id')
            ->get();

        foreach ($actives as $payout) {
            DB::table('studios')
                ->where('id', $payout->studio_id)
                ->update([
                    'stripe_account_id' => $payout->stripe_account_id,
                    'stripe_requirement' => (bool) ($payout->stripe_requirement ?? false),
                    'updated_at' => now(),
                ]);
        }
    }
};
