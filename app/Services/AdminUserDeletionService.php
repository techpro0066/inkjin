<?php

namespace App\Services;

use App\Mail\AdminAccountDeletedMail;
use App\Models\Booking;
use App\Models\Studio;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AdminUserDeletionService
{
    /**
     * Close a non-admin account: notify by email, anonymize login identity,
     * and keep bookings / payouts / request history intact.
     *
     * @return array{email:string,name:string,role:string}
     *
     * @throws \InvalidArgumentException
     * @throws Throwable
     */
    public function delete(User $user): array
    {
        if ($user->role === 'admin') {
            throw new \InvalidArgumentException('Admin accounts cannot be deleted from here.');
        }

        if ($user->deleted_at !== null) {
            throw new \InvalidArgumentException('This account is already deleted.');
        }

        if ($blocker = $this->deleteBlockReason($user)) {
            throw new \InvalidArgumentException($blocker);
        }

        $email = trim((string) $user->email);
        $firstName = trim((string) ($user->first_name ?: 'there'));
        $name = trim((string) ($user->name ?: $email));
        $role = (string) $user->role;
        $userId = (int) $user->id;

        if ($email !== '') {
            try {
                Mail::to($email)->send(new AdminAccountDeletedMail(
                    firstName: $firstName !== '' ? $firstName : 'there',
                    supportEmail: (string) (config('mail.from.address') ?: 'support@inkjin.com'),
                ));
            } catch (Throwable $e) {
                Log::warning('Admin user delete: notification email failed', [
                    'user_id' => $userId,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        DB::transaction(function () use ($user, $userId, $email) {
            $this->invalidateAccess($userId, $email);
            $this->anonymizeProfile($user);
            $this->hidePublicPresence($user);

            $user->forceFill([
                'first_name' => 'Deleted',
                'last_name' => 'User',
                'email' => 'deleted+'.$userId.'.'.Str::lower(Str::random(8)).'@accounts.inkjin.invalid',
                'phone_number' => null,
                'password' => Str::random(64),
                'must_set_password' => false,
                'remember_token' => null,
                'email_verified_at' => null,
                'deleted_at' => now(),
            ])->save();
        });

        return [
            'email' => $email,
            'name' => $name,
            'role' => $role,
        ];
    }

    /**
     * Artists/studios with customer-paid bookings that are not completed cannot be deleted.
     */
    public function deleteBlockReason(User $user): ?string
    {
        if (! in_array($user->role, ['artist', 'studio'], true) || ! Schema::hasTable('bookings')) {
            return null;
        }

        $query = Booking::query()
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'completed');

        if ($user->role === 'artist') {
            $query->where('artist_user_id', $user->id);
            $label = 'The artist has received payments for which the booking is not completed.';
        } else {
            $studioId = Studio::query()->where('user_id', $user->id)->value('id');
            if (! $studioId) {
                return null;
            }
            $query->where('studio_id', (int) $studioId);
            $label = 'The studio has received payments for which the booking is not completed.';
        }

        return $query->exists() ? $label : null;
    }

    /**
     * @param  Collection<int, User>|iterable<int, User>  $users
     * @return array<int, string> userId => block reason
     */
    public function deleteBlockReasonsForUsers(iterable $users): array
    {
        $reasons = [];
        foreach ($users as $user) {
            if (! $user instanceof User) {
                continue;
            }
            $reason = $this->deleteBlockReason($user);
            if ($reason !== null) {
                $reasons[(int) $user->id] = $reason;
            }
        }

        return $reasons;
    }

    private function invalidateAccess(int $userId, string $email): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }

        if ($email !== '' && Schema::hasTable('password_reset_tokens') && Schema::hasColumn('password_reset_tokens', 'email')) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
        }
    }

    private function anonymizeProfile(User $user): void
    {
        $detail = UserDetail::query()->where('user_id', $user->id)->first();
        if (! $detail) {
            return;
        }

        $detail->forceFill([
            'user_name' => 'deleted-'.$user->id.'-'.Str::lower(Str::random(6)),
            'avatar' => null,
            'mobile_number' => null,
            'personal_page_description' => null,
            'availability_status' => 'closed',
            'display_portfolio' => false,
            'display_designs' => false,
            'display_guest_spots' => false,
            'display_faq' => false,
            'instagram_access_token' => null,
            'instagram_username' => null,
            'google_calendar_token' => null,
            'social_links' => null,
        ])->save();
    }

    private function hidePublicPresence(User $user): void
    {
        if ($user->role === 'artist') {
            if (Schema::hasTable('artist_designs') && Schema::hasColumn('artist_designs', 'is_active')) {
                DB::table('artist_designs')->where('user_id', $user->id)->update(['is_active' => false]);
            }
            if (Schema::hasTable('portfolios') && Schema::hasColumn('portfolios', 'is_active')) {
                DB::table('portfolios')->where('user_id', $user->id)->update(['is_active' => false]);
            }
            if (Schema::hasTable('guest_spots') && Schema::hasColumn('guest_spots', 'status')) {
                DB::table('guest_spots')
                    ->where('user_id', $user->id)
                    ->where('status', 'available')
                    ->update(['status' => 'completed']);
            }
        }

        if ($user->role === 'studio' && Schema::hasTable('studios')) {
            $studioUpdate = [];
            if (Schema::hasColumn('studios', 'username')) {
                $studioUpdate['username'] = 'deleted-studio-'.$user->id.'-'.Str::lower(Str::random(6));
            }
            if (Schema::hasColumn('studios', 'instagram_access_token')) {
                $studioUpdate['instagram_access_token'] = null;
            }
            if ($studioUpdate !== []) {
                DB::table('studios')->where('user_id', $user->id)->update($studioUpdate);
            }
        }
    }
}
