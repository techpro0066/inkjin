<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\ChatChannel;
use App\Models\Studio;
use App\Services\StreamChatService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request, StreamChatService $streamChat): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $studioName = trim((string) ($studio?->name ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        return view('studio.chat.index', [
            'role' => 'studio',
            'streamConfigured' => $streamChat->isConfigured(),
            'hasOpenBooking' => $streamChat->userHasAnyOpenBooking($user),
            'hasConversations' => ChatChannel::query()->forUser((int) $user->id)
                ->where('channel_kind', ChatChannel::KIND_STUDIO_ARTIST)
                ->exists(),
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'profileComplete' => (string) ($user->on_boarding ?? '') === 'yes',
            'stripeConnected' => (bool) ($studio?->hasStripeConnect()),
            'activeNav' => 'inbox',
        ]);
    }

    private function resolveStudio($user): ?Studio
    {
        if ($user?->id) {
            $byOwner = Studio::query()->where('user_id', $user->id)->first();
            if ($byOwner) {
                return $byOwner;
            }
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email === '') {
            return null;
        }

        return Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();
    }
}
