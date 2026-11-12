<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\UserDetail;
use App\Services\BookingAftercareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArtistAftercareController extends Controller
{
    public function __construct(
        private BookingAftercareService $aftercare
    ) {}

    public function preview(int $id): JsonResponse
    {
        $booking = Booking::query()
            ->with(['user', 'artist.userDetail', 'tattoo'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ((string) $booking->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Aftercare is available after the booking is completed.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'preview' => $this->aftercare->previewForBooking($booking),
        ]);
    }

    public function send(int $id): JsonResponse
    {
        $booking = Booking::query()
            ->with(['user', 'artist.userDetail', 'tattoo'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $result = $this->aftercare->sendManually($booking);

        if (! empty($result['ok'])) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'aftercare_sent' => true,
            ]);
        }

        $status = 400;
        if (! empty($result['cooldown'])) {
            $status = 429;
        } elseif (! empty($result['already_sent'])) {
            $status = 409;
        }

        return response()->json([
            'success' => false,
            'already_sent' => (bool) ($result['already_sent'] ?? false),
            'cooldown' => (bool) ($result['cooldown'] ?? false),
            'next_allowed_at' => $result['next_allowed_at'] ?? null,
            'message' => $result['message'] ?? 'Could not send aftercare.',
        ], $status);
    }

    public function index(): View
    {
        $user = Auth::user();
        $userDetail = $user?->userDetail;
        $resolved = $this->aftercare->resolveContent(
            is_array($userDetail?->aftercare_content) ? $userDetail->aftercare_content : null
        );

        $artistLabel = trim((string) ($userDetail?->studio_name ?? ''));
        if ($artistLabel === '') {
            $artistLabel = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));
        }
        if ($artistLabel === '') {
            $artistLabel = 'your artist';
        }

        return view('artist.aftercare.index', [
            'user' => $user,
            'userDetail' => $userDetail,
            'sendAutomatically' => (bool) ($userDetail?->aftercare_send_automatically ?? false),
            'sections' => $resolved['sections'],
            'textBlocks' => $resolved['text_blocks'],
            'artistLabel' => $artistLabel,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $userDetail = Auth::user()->userDetail ?? UserDetail::create(['user_id' => Auth::id()]);

        $validated = $request->validate([
            'send_automatically' => ['required', 'boolean'],
            'sections' => ['sometimes', 'array'],
            'sections.*' => ['array', 'max:20'],
            'sections.*.*' => ['string', 'max:500'],
            'text_blocks' => ['sometimes', 'array'],
            'text_blocks.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $update = [
            'aftercare_send_automatically' => $request->boolean('send_automatically'),
        ];

        $savingContent = $request->has('sections') || $request->has('text_blocks');
        if ($savingContent) {
            $update['aftercare_content'] = $this->aftercare->normalizeContent(
                $validated['sections'] ?? [],
                $validated['text_blocks'] ?? []
            );
        }

        $userDetail->update($update);
        $userDetail->refresh();

        $resolved = $this->aftercare->resolveContent(
            is_array($userDetail->aftercare_content) ? $userDetail->aftercare_content : null
        );

        return response()->json([
            'success' => true,
            'message' => $savingContent ? 'Aftercare instructions saved.' : 'Auto-send setting updated.',
            'send_automatically' => (bool) $userDetail->aftercare_send_automatically,
            'sections' => $resolved['sections'],
            'text_blocks' => $resolved['text_blocks'],
        ]);
    }
}
