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

    /**
     * @return array{bookings: int, clients_dot: bool}
     */
    public function forArtist(User $artist): array
    {
        $artistId = (int) $artist->id;

        return [
            'bookings' => $this->bookingsActionCount($artistId),
            'clients_dot' => $this->clientsInfoDot($artist),
        ];
    }

    public function bookingsActionCount(int $artistId): int
    {
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

        $pendingBookings = Booking::query()
            ->where('artist_user_id', $artistId)
            ->where('status', 'pending')
            ->count();

        return $pendingDesign + $pendingCustom + $pendingStudioOffers + $pendingBookings;
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

        $seenAt = $detail->clients_nav_seen_at;
        if (! $seenAt instanceof Carbon) {
            return false;
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

    private function hasClientsNavSeenColumn(): bool
    {
        if (self::$hasClientsNavSeenColumn === null) {
            self::$hasClientsNavSeenColumn = Schema::hasColumn('user_details', 'clients_nav_seen_at');
        }

        return self::$hasClientsNavSeenColumn;
    }
}
