<?php

namespace App\Services;

use App\Mail\SessionFeedbackMail;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SessionFeedbackService
{
    /**
     * Create a pending review (once per booking) and email the client as soon as the session is completed.
     */
    public function sendForCompletedBooking(Booking $booking): bool
    {
        if ((string) $booking->status !== 'completed') {
            return false;
        }

        $booking->loadMissing(['user', 'artist.userDetail', 'studio']);

        $client = $booking->user;
        $email = trim((string) ($client?->email ?? ''));
        if (! $client || $email === '' || empty($booking->artist_user_id)) {
            return false;
        }

        $review = null;

        try {
            $review = DB::transaction(function () use ($booking, $client) {
                $existing = Review::query()
                    ->where('booking_id', $booking->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if ($existing->email_sent_at) {
                        return null;
                    }

                    return $existing;
                }

                return Review::query()->create([
                    'booking_id' => $booking->id,
                    'user_id' => (int) $client->id,
                    'artist_id' => (int) $booking->artist_user_id,
                    'studio_id' => $booking->studio_id ? (int) $booking->studio_id : null,
                    'rating' => null,
                    'answers' => null,
                    'status' => Review::STATUS_PENDING,
                    'email_sent_at' => null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create session feedback review row', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $review) {
            return false;
        }

        $claimed = false;
        try {
            $claimed = DB::transaction(function () use ($review) {
                $locked = Review::query()
                    ->whereKey($review->id)
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
            Log::error('Failed to claim session feedback email send', [
                'booking_id' => $booking->id,
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $claimed) {
            return false;
        }

        try {
            Mail::to($email)->send(new SessionFeedbackMail(
                $booking->fresh(['artist.userDetail', 'studio', 'tattoo']) ?? $booking,
                $review->fresh() ?? $review,
                $client,
            ));

            return true;
        } catch (\Throwable $e) {
            Review::query()->whereKey($review->id)->update(['email_sent_at' => null]);
            Log::error('Failed to send session feedback email', [
                'booking_id' => $booking->id,
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
