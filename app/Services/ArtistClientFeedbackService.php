<?php

namespace App\Services;

use App\Mail\ArtistClientFeedbackMail;
use App\Models\ArtistClientFeedback;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ArtistClientFeedbackService
{
    /**
     * Create a pending artist→client feedback row (once per booking) and email the artist.
     */
    public function sendForCompletedBooking(Booking $booking): bool
    {
        if ((string) $booking->status !== 'completed') {
            return false;
        }

        $booking->loadMissing(['user', 'artist.userDetail', 'studio']);

        $artist = $booking->artist;
        $email = trim((string) ($artist?->email ?? ''));
        $client = $booking->user;
        if (! $artist || $email === '' || ! $client) {
            return false;
        }

        $feedback = null;

        try {
            $feedback = DB::transaction(function () use ($booking, $artist, $client) {
                $existing = ArtistClientFeedback::query()
                    ->where('booking_id', $booking->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if ($existing->email_sent_at) {
                        return null;
                    }

                    return $existing;
                }

                return ArtistClientFeedback::query()->create([
                    'booking_id' => $booking->id,
                    'artist_id' => (int) $artist->id,
                    'client_user_id' => (int) $client->id,
                    'studio_id' => $booking->studio_id ? (int) $booking->studio_id : null,
                    'rating' => null,
                    'answers' => null,
                    'status' => ArtistClientFeedback::STATUS_PENDING,
                    'email_sent_at' => null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create artist client feedback row', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $feedback) {
            return false;
        }

        $claimed = false;
        try {
            $claimed = DB::transaction(function () use ($feedback) {
                $locked = ArtistClientFeedback::query()
                    ->whereKey($feedback->id)
                    ->whereNull('email_sent_at')
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return false;
                }

                $locked->update(['email_sent_at' => now()]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to claim artist client feedback email send', [
                'booking_id' => $booking->id,
                'feedback_id' => $feedback->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $claimed) {
            return false;
        }

        try {
            Mail::to($email)->send(new ArtistClientFeedbackMail(
                $booking->fresh(['artist.userDetail', 'studio', 'tattoo', 'user']) ?? $booking,
                $feedback->fresh() ?? $feedback,
                $artist,
                $client,
            ));

            return true;
        } catch (\Throwable $e) {
            ArtistClientFeedback::query()->whereKey($feedback->id)->update(['email_sent_at' => null]);
            Log::error('Failed to send artist client feedback email', [
                'booking_id' => $booking->id,
                'feedback_id' => $feedback->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
