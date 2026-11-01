<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $columnsToDrop = [
        // payment_type stays on user_details; studio fields stay on user_details / user_studios.
        'payment_status',
        'stripe_account_id',
        'stripe_requirement',
        'stripe_requirement_email_sent_at',
        'payout_setup_reminder_sent_at',
        'payout_bank_country',
        'payout_waiting_list_country',
        'payout_waiting_list_at',
        'payout_mode',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('user_payouts') || ! Schema::hasTable('user_details')) {
            return;
        }

        $this->backfillFromUserDetails();
        $this->dropUserDetailPayoutColumns();
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_details')) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'payment_type')) {
                $table->enum('payment_type', ['artist_account', 'studio_account', 'inkjin_account'])->nullable();
            }
            if (! Schema::hasColumn('user_details', 'stripe_account_id')) {
                $table->string('stripe_account_id')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'stripe_requirement')) {
                $table->boolean('stripe_requirement')->default(false);
            }
            if (! Schema::hasColumn('user_details', 'payout_bank_country')) {
                $table->string('payout_bank_country', 2)->nullable();
            }
            if (! Schema::hasColumn('user_details', 'payout_waiting_list_country')) {
                $table->string('payout_waiting_list_country')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'payout_waiting_list_at')) {
                $table->timestamp('payout_waiting_list_at')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'approved', 'rejected'])->nullable();
            }
            if (! Schema::hasColumn('user_details', 'payout_mode')) {
                $table->string('payout_mode', 20)->default('manual');
            }
            if (! Schema::hasColumn('user_details', 'payout_setup_reminder_sent_at')) {
                $table->timestamp('payout_setup_reminder_sent_at')->nullable();
            }
            if (! Schema::hasColumn('user_details', 'stripe_requirement_email_sent_at')) {
                $table->timestamp('stripe_requirement_email_sent_at')->nullable();
            }
        });

        if (! Schema::hasTable('user_payouts')) {
            return;
        }

        DB::table('user_payouts')
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $payout) {
                    DB::table('user_details')
                        ->where('user_id', $payout->user_id)
                        ->update([
                            'payment_status' => in_array($payout->status, ['pending', 'approved', 'rejected'], true)
                                ? $payout->status
                                : null,
                            'stripe_account_id' => $payout->stripe_account_id,
                            'stripe_requirement' => (bool) $payout->stripe_requirement,
                            'stripe_requirement_email_sent_at' => $payout->stripe_requirement_email_sent_at,
                            'payout_setup_reminder_sent_at' => $payout->payout_setup_reminder_sent_at,
                            'payout_bank_country' => $payout->payout_bank_country,
                            'payout_waiting_list_country' => $payout->payout_waiting_list_country,
                            'payout_waiting_list_at' => $payout->payout_waiting_list_at,
                            'payout_mode' => $payout->payout_mode ?: 'manual',
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    private function backfillFromUserDetails(): void
    {
        if (! Schema::hasColumn('user_details', 'payment_type')
            && ! Schema::hasColumn('user_details', 'stripe_account_id')) {
            return;
        }

        $hasPaymentType = Schema::hasColumn('user_details', 'payment_type');
        $hasPaymentStatus = Schema::hasColumn('user_details', 'payment_status');
        $hasStripe = Schema::hasColumn('user_details', 'stripe_account_id');
        $hasStripeReq = Schema::hasColumn('user_details', 'stripe_requirement');
        $hasStripeReqEmail = Schema::hasColumn('user_details', 'stripe_requirement_email_sent_at');
        $hasReminder = Schema::hasColumn('user_details', 'payout_setup_reminder_sent_at');
        $hasBankCountry = Schema::hasColumn('user_details', 'payout_bank_country');
        $hasWaitCountry = Schema::hasColumn('user_details', 'payout_waiting_list_country');
        $hasWaitAt = Schema::hasColumn('user_details', 'payout_waiting_list_at');
        $hasMode = Schema::hasColumn('user_details', 'payout_mode');

        DB::table('user_details')
            ->orderBy('id')
            ->chunkById(100, function ($details) use (
                $hasPaymentType,
                $hasPaymentStatus,
                $hasStripe,
                $hasStripeReq,
                $hasStripeReqEmail,
                $hasReminder,
                $hasBankCountry,
                $hasWaitCountry,
                $hasWaitAt,
                $hasMode,
            ) {
                foreach ($details as $detail) {
                    $userId = (int) $detail->user_id;
                    if ($userId < 1) {
                        continue;
                    }

                    $already = DB::table('user_payouts')->where('user_id', $userId)->exists();
                    if ($already) {
                        continue;
                    }

                    $paymentType = $hasPaymentType ? ($detail->payment_type ?? null) : null;
                    $stripeAccountId = $hasStripe ? ($detail->stripe_account_id ?? null) : null;
                    $paymentStatus = $hasPaymentStatus ? ($detail->payment_status ?? null) : null;

                    $hasPayoutSignal = filled($paymentType)
                        || filled($stripeAccountId)
                        || filled($paymentStatus)
                        || ($hasBankCountry && filled($detail->payout_bank_country ?? null))
                        || ($hasWaitCountry && filled($detail->payout_waiting_list_country ?? null));

                    if (! $hasPayoutSignal) {
                        continue;
                    }

                    $status = match (true) {
                        in_array($paymentStatus, ['pending', 'approved', 'rejected'], true) => $paymentStatus,
                        filled($stripeAccountId) => 'approved',
                        default => 'draft',
                    };

                    $now = now();
                    $isConnected = filled($stripeAccountId) || $status === 'approved';

                    DB::table('user_payouts')->insert([
                        'user_id' => $userId,
                        'status' => $status,
                        'is_active' => true,
                        'stripe_account_id' => $stripeAccountId ?: null,
                        'stripe_requirement' => $hasStripeReq ? (bool) ($detail->stripe_requirement ?? false) : false,
                        'stripe_requirement_email_sent_at' => $hasStripeReqEmail ? ($detail->stripe_requirement_email_sent_at ?? null) : null,
                        'payout_setup_reminder_sent_at' => $hasReminder ? ($detail->payout_setup_reminder_sent_at ?? null) : null,
                        'payout_bank_country' => $hasBankCountry ? ($detail->payout_bank_country ?? null) : null,
                        'payout_waiting_list_country' => $hasWaitCountry ? ($detail->payout_waiting_list_country ?? null) : null,
                        'payout_waiting_list_at' => $hasWaitAt ? ($detail->payout_waiting_list_at ?? null) : null,
                        'payout_mode' => $hasMode
                            ? (in_array(($detail->payout_mode ?? 'manual'), ['manual', 'automatic'], true) ? $detail->payout_mode : 'manual')
                            : 'manual',
                        'connected_at' => $isConnected ? $now : null,
                        'disconnected_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    private function dropUserDetailPayoutColumns(): void
    {
        $existing = array_values(array_filter(
            $this->columnsToDrop,
            fn (string $col) => Schema::hasColumn('user_details', $col)
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('user_details', function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }
};
