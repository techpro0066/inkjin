<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\ChatChannel;
use App\Models\CustomRequest;
use App\Models\User;
use Carbon\Carbon;
use GetStream\StreamChat\StreamException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UserDashboardService
{
    public function __construct(
        private readonly StreamChatService $streamChat,
    ) {}

    /**
     * @return array{
     *     stats: array<string, mixed>,
     *     upcoming_bookings: array<int, array<string, mixed>>,
     *     recent_messages: array<int, array<string, mixed>>,
     *     active_requests: array<int, array<string, mixed>>
     * }
     */
    public function buildForUser(User $user): array
    {
        $upcomingQuery = Booking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('booking_date', '>=', now()->toDateString());

        $upcomingCount = (clone $upcomingQuery)->count();

        $upcomingBookings = (clone $upcomingQuery)
            ->with(['tattoo', 'artist.userDetail'])
            ->orderBy('booking_date')
            ->orderBy('start_time_utc')
            ->limit(5)
            ->get();

        $pendingBookingRequests = BookingRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $pendingCustomRequests = CustomRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $pendingRequestsCount = $pendingBookingRequests + $pendingCustomRequests;

        $paidBookings = Booking::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('total_amount_paid', '>', 0)
            ->where(function ($q) {
                $q->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['failed', 'refunded']);
            })
            ->get(['id', 'total_amount_paid', 'currency']);

        $totalSpent = round((float) $paidBookings->sum(fn (Booking $b) => (float) $b->total_amount_paid), 2);
        $sessionCount = $paidBookings->count();

        $unread = $this->streamChat->getUnreadSummaryForUser($user);
        $unreadTotal = (int) ($unread['total'] ?? 0);

        $nextBooking = $upcomingBookings->first();
        $nextLabel = $nextBooking
            ? 'Next one '.$this->formatBookingShortDate($nextBooking)
            : ($upcomingBookings->isEmpty() ? 'No upcoming sessions' : '');

        return [
            'stats' => [
                'upcoming_count' => $upcomingCount,
                'upcoming_subtitle' => $nextLabel,
                'pending_requests_count' => $pendingRequestsCount,
                'pending_requests_subtitle' => $pendingRequestsCount > 0
                    ? 'Waiting for artist reply'
                    : 'No pending requests',
                'unread_messages_count' => $unreadTotal,
                'unread_messages_subtitle' => $unreadTotal > 0
                    ? ($unreadTotal === 1 ? '1 unread conversation' : $unreadTotal.' unread')
                    : 'All caught up',
                'total_spent' => $totalSpent,
                'total_spent_label' => '€'.number_format($totalSpent, $totalSpent == floor($totalSpent) ? 0 : 2),
                'sessions_subtitle' => $sessionCount > 0
                    ? 'Across '.$sessionCount.' '.($sessionCount === 1 ? 'session' : 'sessions')
                    : 'No payments yet',
                'currency_symbol' => '€',
            ],
            'upcoming_bookings' => $upcomingBookings
                ->map(fn (Booking $booking) => $this->formatUpcomingBooking($booking))
                ->values()
                ->all(),
            'recent_messages' => $this->recentMessages($user, $unread['channels'] ?? []),
            'active_requests' => $this->activeRequests($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUpcomingBooking(Booking $booking): array
    {
        $artist = $booking->artist;
        $ud = $artist?->userDetail;
        $name = $ud ? $ud->publicDisplayName() : ($artist
            ? trim(($artist->first_name ?? '').' '.($artist->last_name ?? ''))
            : 'Artist');
        $initials = $ud ? $ud->publicDisplayInitials() : ($artist
            ? strtoupper(substr((string) $artist->first_name, 0, 1).substr((string) $artist->last_name, 0, 1))
            : 'AR');

        $studioParts = array_filter([
            $name !== '' ? $name : null,
            trim((string) ($ud->studio_name ?? '')) ?: null,
            trim((string) ($ud->city ?? '')) ?: null,
        ]);

        $status = strtolower((string) ($booking->status ?? ''));
        [$badgeLabel, $badgeClass, $dotClass] = match ($status) {
            'confirmed' => ['Confirmed', 'bg-green-50 text-green-700', 'bg-green-500'],
            'pending' => ['Pending', 'bg-amber-50 text-amber-700', 'bg-amber-500'],
            default => ['Upcoming', 'bg-purple-50 text-purple-700', 'bg-purple-500'],
        };

        $when = $this->formatBookingDateTime($booking);

        return [
            'id' => $booking->id,
            'title' => $booking->displayTitle(),
            'artist_line' => implode(' · ', $studioParts),
            'initials' => $initials !== '' ? $initials : 'AR',
            'avatar' => ($ud && filled($ud->avatar)) ? asset($ud->avatar) : null,
            'when' => $when,
            'status_label' => $badgeLabel,
            'badge_class' => $badgeClass,
            'dot_class' => $dotClass,
            'details_url' => route('user.bookings.index'),
        ];
    }

    private function formatBookingShortDate(Booking $booking): string
    {
        if (! $booking->booking_date) {
            return '';
        }

        return Carbon::parse($booking->booking_date)->format('M j');
    }

    private function formatBookingDateTime(Booking $booking): string
    {
        if (! $booking->booking_date) {
            return 'Date TBD';
        }

        $date = Carbon::parse($booking->booking_date)->format('F j');
        $time = $booking->booking_time['start'] ?? null;

        return $time ? $date.', '.$time : $date;
    }

    /**
     * @param  array<string, int>  $unreadByChannel
     * @return array<int, array<string, mixed>>
     */
    private function recentMessages(User $user, array $unreadByChannel): array
    {
        $channels = ChatChannel::query()
            ->forClient((int) $user->id)
            ->with(['artist.userDetail', 'booking.tattoo'])
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        if ($channels->isEmpty()) {
            return [];
        }

        $streamPreviews = $this->streamMessagePreviews($user, $channels);

        return $channels
            ->groupBy(fn (ChatChannel $c) => $c->pairKey())
            ->map(function (Collection $group) use ($unreadByChannel, $streamPreviews) {
                /** @var ChatChannel $latest */
                $latest = $group->sortByDesc('updated_at')->first();
                $artist = $latest->artist;
                $ud = $artist?->userDetail;
                $name = $ud ? $ud->publicDisplayName() : ($artist
                    ? trim(($artist->first_name ?? '').' '.($artist->last_name ?? ''))
                    : 'Artist');
                $initials = $ud ? $ud->publicDisplayInitials() : ($artist
                    ? strtoupper(substr((string) $artist->first_name, 0, 1).substr((string) $artist->last_name, 0, 1))
                    : 'AR');

                $unread = 0;
                $preview = null;
                $at = $latest->updated_at;

                foreach ($group as $channel) {
                    $sid = (string) $channel->stream_channel_id;
                    $unread += (int) ($unreadByChannel[$sid] ?? 0);
                    if (isset($streamPreviews[$sid])) {
                        $row = $streamPreviews[$sid];
                        if ($preview === null || ($row['at'] ?? null) > ($preview['at'] ?? null)) {
                            $preview = $row;
                        }
                    }
                }

                if ($preview) {
                    $at = $preview['at'] ?? $at;
                }

                return [
                    'artist_name' => $name !== '' ? $name : 'Artist',
                    'initials' => $initials !== '' ? $initials : 'AR',
                    'avatar' => ($artist?->userDetail && filled($artist->userDetail->avatar))
                        ? asset($artist->userDetail->avatar)
                        : null,
                    'preview' => $preview['text'] ?? ($latest->booking?->displayTitle() ?? 'Open conversation'),
                    'time_ago' => $at ? Carbon::parse($at)->diffForHumans(null, true).' ago' : '',
                    'has_unread' => $unread > 0,
                    'url' => route('user.chat.index'),
                    'sort_at' => $at ? Carbon::parse($at)->timestamp : 0,
                ];
            })
            ->sortByDesc('sort_at')
            ->take(3)
            ->values()
            ->map(function (array $row) {
                unset($row['sort_at']);

                return $row;
            })
            ->all();
    }

    /**
     * @param  Collection<int, ChatChannel>  $channels
     * @return array<string, array{text: string, at: \Carbon\Carbon|null}>
     */
    private function streamMessagePreviews(User $user, Collection $channels): array
    {
        if (! $this->streamChat->isConfigured() || $channels->isEmpty()) {
            return [];
        }

        $ids = $channels->pluck('stream_channel_id')->filter()->values()->all();
        if ($ids === []) {
            return [];
        }

        try {
            $response = $this->streamChat->getClient()->queryChannels(
                [
                    'type' => 'messaging',
                    'id' => ['$in' => $ids],
                    'members' => ['$in' => [(string) $user->id]],
                ],
                ['last_message_at' => -1],
                ['limit' => count($ids), 'state' => true, 'watch' => false, 'message_limit' => 1]
            );

            $data = is_array($response) ? $response : $response->getArrayCopy();
            $previews = [];

            foreach ($data['channels'] ?? [] as $row) {
                $row = is_array($row) ? $row : (array) $row;
                $channel = is_array($row['channel'] ?? null) ? $row['channel'] : (array) ($row['channel'] ?? []);
                $channelId = (string) ($channel['id'] ?? '');
                if ($channelId === '') {
                    continue;
                }

                $messages = $row['messages'] ?? [];
                $last = is_array($messages) && $messages !== [] ? end($messages) : null;
                $last = is_array($last) ? $last : (array) ($last ?? []);
                $text = trim((string) ($last['text'] ?? ''));
                if ($text === '') {
                    $text = 'Open conversation';
                }

                $atRaw = $last['created_at'] ?? ($channel['last_message_at'] ?? null);
                $at = $atRaw ? Carbon::parse($atRaw) : null;

                $previews[$channelId] = [
                    'text' => $text,
                    'at' => $at,
                ];
            }

            return $previews;
        } catch (StreamException|\Throwable $e) {
            Log::warning('Stream message preview failed for user dashboard', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activeRequests(User $user): array
    {
        $design = BookingRequest::query()
            ->with(['tattoo', 'artist.userDetail'])
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (BookingRequest $request) {
                $panel = $request->toUserPanelArray();
                $statusLabel = $panel['filterStatus'] ?? 'Pending';
                $isPending = (bool) ($panel['isPending'] ?? false);
                $isConfirmed = (bool) ($panel['isConfirmed'] ?? false);

                return [
                    'title' => $panel['designTitle'] ?? 'Design request',
                    'artist_line' => ($panel['artistName'] ?? 'Artist').(
                        ! empty($panel['priceLabel']) && $panel['priceLabel'] !== '—'
                            ? ' · '.$panel['priceLabel']
                            : ($isPending ? ' · Waiting for artist response' : '')
                    ),
                    'status_label' => $statusLabel,
                    'badge_class' => $isConfirmed
                        ? 'bg-blue-50 text-blue-700'
                        : ($isPending ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700'),
                    'dot_class' => $isConfirmed ? 'bg-blue-500' : ($isPending ? 'bg-amber-500' : 'bg-blue-500'),
                    'time_ago' => $request->created_at?->diffForHumans(null, true).' ago',
                    'action_label' => $panel['canSelectTimes'] ?? false
                        ? 'Pick times'
                        : (($panel['canPay'] ?? false) ? 'Pay now' : 'View details'),
                    'action_url' => $panel['confirmTimesUrl']
                        ?? $panel['paymentUrl']
                        ?? route('user.requests.index'),
                    'sort_at' => $request->created_at?->timestamp ?? 0,
                    'type' => 'design',
                ];
            });

        $custom = CustomRequest::query()
            ->with(['artist.userDetail'])
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'moved_to_booking'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (CustomRequest $request) {
                $panel = $request->toUserPanelArray();
                $statusLabel = $panel['filterStatus'] ?? 'Pending';
                $isPending = (bool) ($panel['isPending'] ?? false);
                $hasQuote = (bool) ($panel['hasQuote'] ?? false);
                $quote = $panel['estimatedPriceLabel'] ?? null;

                return [
                    'title' => ($panel['reference'] ?? 'Custom request').' — custom tattoo',
                    'artist_line' => ($panel['artistName'] ?? 'Artist').(
                        $hasQuote && $quote
                            ? ' · Quote: '.$quote
                            : ($isPending ? ' · Waiting for artist response' : '')
                    ),
                    'status_label' => $statusLabel,
                    'badge_class' => $hasQuote
                        ? 'bg-blue-50 text-blue-700'
                        : ($isPending ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700'),
                    'dot_class' => $hasQuote ? 'bg-blue-500' : ($isPending ? 'bg-amber-500' : 'bg-blue-500'),
                    'time_ago' => $request->created_at?->diffForHumans(null, true).' ago',
                    'action_label' => $panel['canSelectTimes'] ?? false
                        ? 'Pick times'
                        : (($panel['canPay'] ?? false) ? 'Pay now' : 'View details'),
                    'action_url' => $panel['confirmTimesUrl']
                        ?? $panel['paymentUrl']
                        ?? route('user.requests.index', ['tab' => 'custom']),
                    'sort_at' => $request->created_at?->timestamp ?? 0,
                    'type' => 'custom',
                ];
            });

        return $design->concat($custom)
            ->sortByDesc('sort_at')
            ->take(5)
            ->values()
            ->map(function (array $row) {
                unset($row['sort_at'], $row['type']);

                return $row;
            })
            ->all();
    }
}
