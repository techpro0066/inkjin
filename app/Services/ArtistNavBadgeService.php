<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Models\StudioRequest;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Waitlist;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ArtistNavBadgeService
{
    private static ?bool $hasClientsNavSeenColumn = null;

    private static ?bool $hasBookingsNavSeenColumn = null;

    /**
     * @return array{bookings: int, clients_dot: bool}
     */
    public function forArtist(User $artist): array
    {
        $artistId = (int) $artist->id;

        return [
            'bookings' => $this->bookingsActionCount($artist),
            'clients_dot' => $this->clientsInfoDot($artist),
        ];
    }

    public function bookingsActionCount(User|int $artist): int
    {
        $artistId = $artist instanceof User ? (int) $artist->id : (int) $artist;

        $pendingDesign = BookingRequest::query()
            ->where('artist_id', $artistId)
            ->where('status', 'pending')
            ->count();

        $pendingCustom = CustomRequest::query()
            ->where('artist_id', $artistId)
            ->where('status', 'pending')
            ->visibleInArtistRequests()
            ->count();

        $pendingStudioOffers = StudioRequest::query()
            ->where('assigned_artist_id', $artistId)
            ->where('status', StudioRequest::STATUS_ASSIGNED)
            ->whereNull('custom_request_id')
            ->count();

        // Pending bookings always need action.
        $pendingBookings = Booking::query()
            ->where('artist_user_id', $artistId)
            ->where('status', 'pending')
            ->count();

        // Confirmed bookings count until the artist opens Bookings (new activity).
        $newConfirmedBookings = 0;
        $seenAt = $this->bookingsSeenAt($artist instanceof User ? $artist : null, $artistId);
        if ($seenAt) {
            $newConfirmedBookings = Booking::query()
                ->where('artist_user_id', $artistId)
                ->where('status', 'confirmed')
                ->where('created_at', '>', $seenAt)
                ->count();
        }

        return $pendingDesign + $pendingCustom + $pendingStudioOffers + $pendingBookings + $newConfirmedBookings;
    }

    public function clientsInfoDot(User $artist): bool
    {
        if (! $this->hasClientsNavSeenColumn()) {
            return false;
        }

        $detail = $artist->relationLoaded('userDetail')
            ? $artist->userDetail
            : $artist->userDetail()->first();

        if (! $detail instanceof UserDetail) {
            return false;
        }

        // Null = never opened Clients — treat as "beginning of time" so new
        // waitlists / first-time clients still light the info dot.
        $seenAt = $detail->clients_nav_seen_at;
        if (! $seenAt instanceof Carbon) {
            $seenAt = Carbon::createFromTimestamp(0);
        }

        $artistId = (int) $artist->id;

        if (Waitlist::query()
            ->where('user_id', $artistId)
            ->where('created_at', '>', $seenAt)
            ->exists()) {
            return true;
        }

        // New client = first booking for that user_id happened after last Clients visit.
        return Booking::query()
            ->where('artist_user_id', $artistId)
            ->whereNotNull('user_id')
            ->where('created_at', '>', $seenAt)
            ->whereNotIn('user_id', function ($q) use ($artistId, $seenAt) {
                $q->select('user_id')
                    ->from('bookings')
                    ->where('artist_user_id', $artistId)
                    ->whereNotNull('user_id')
                    ->where('created_at', '<=', $seenAt);
            })
            ->exists();
    }

    public function markClientsSeen(User $artist): void
    {
        if (! $this->hasClientsNavSeenColumn()) {
            return;
        }

        $detail = $artist->userDetail;
        if (! $detail) {
            return;
        }

        $detail->forceFill(['clients_nav_seen_at' => now()])->save();
    }

    public function markBookingsSeen(User $artist): void
    {
        if (! $this->hasBookingsNavSeenColumn()) {
            return;
        }

        $detail = $artist->userDetail;
        if (! $detail) {
            return;
        }

        $detail->forceFill(['bookings_nav_seen_at' => now()])->save();
    }

    private function bookingsSeenAt(?User $artist, int $artistId): ?Carbon
    {
        if (! $this->hasBookingsNavSeenColumn()) {
            // Column missing: don't count historical confirmed (avoid noisy badge).
            return now();
        }

        $detail = null;
        if ($artist) {
            $detail = $artist->relationLoaded('userDetail')
                ? $artist->userDetail
                : $artist->userDetail()->first();
        } else {
            $detail = UserDetail::query()->where('user_id', $artistId)->first();
        }

        $seenAt = $detail?->bookings_nav_seen_at;
        if ($seenAt instanceof Carbon) {
            return $seenAt;
        }

        // Never opened Bookings — any confirmed booking counts as new.
        return Carbon::createFromTimestamp(0);
    }

    private function hasClientsNavSeenColumn(): bool
    {
        if (self::$hasClientsNavSeenColumn === null) {
            self::$hasClientsNavSeenColumn = Schema::hasColumn('user_details', 'clients_nav_seen_at');
        }

        return self::$hasClientsNavSeenColumn;
    }

    private function hasBookingsNavSeenColumn(): bool
    {
        if (self::$hasBookingsNavSeenColumn === null) {
            self::$hasBookingsNavSeenColumn = Schema::hasColumn('user_details', 'bookings_nav_seen_at');
        }

        return self::$hasBookingsNavSeenColumn;
    }
}
