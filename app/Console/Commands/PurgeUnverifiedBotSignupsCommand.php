<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\NotBotGmailPattern;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeUnverifiedBotSignupsCommand extends Command
{
    protected $signature = 'signups:purge-unverified-bots
                            {--min-age-hours=1 : Only delete accounts older than this many hours}
                            {--dry-run : List matches without deleting}';

    protected $description = 'Delete unverified accounts whose email matches the heavy dotted-Gmail bot pattern';

    public function handle(): int
    {
        $minAgeHours = max(0, (int) $this->option('min-age-hours'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($minAgeHours);

        $candidates = User::query()
            ->whereNull('email_verified_at')
            ->where('created_at', '<', $cutoff)
            ->where(function ($query) {
                $query->where('role', 'artist')->orWhereNull('role');
            })
            ->orderBy('id')
            ->get(['id', 'email', 'role', 'on_boarding', 'created_at']);

        $matched = $candidates->filter(
            fn (User $user) => NotBotGmailPattern::matches((string) $user->email)
        );

        if ($matched->isEmpty()) {
            $this->info('No unverified dotted-Gmail bot signups to purge.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] Would delete' : 'Deleting').' '.$matched->count().' unverified bot signup(s).');

        $deleted = 0;
        foreach ($matched as $user) {
            Log::info('Purging unverified dotted-Gmail bot signup', [
                'user_id' => $user->id,
                'email_hash' => hash('sha256', strtolower(trim((string) $user->email))),
                'email_domain' => strtolower((string) str($user->email)->afterLast('@')),
                'on_boarding' => $user->on_boarding,
                'created_at' => optional($user->created_at)?->toDateTimeString(),
                'dry_run' => $dryRun,
            ]);

            if ($dryRun) {
                $this->line(" - #{$user->id} {$user->email} (created {$user->created_at})");
                continue;
            }

            DB::transaction(function () use ($user) {
                $user->userDetail()?->delete();
                $user->delete();
            });

            $deleted++;
        }

        if (! $dryRun) {
            $this->info("Deleted {$deleted} unverified bot signup(s).");
        }

        return self::SUCCESS;
    }
}
