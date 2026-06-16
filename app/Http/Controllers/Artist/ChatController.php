<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\ChatChannel;
use App\Services\StreamChatService;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(StreamChatService $streamChat): View
    {
        $user = auth()->user();

        return view('artist.chat.index', [
            'role' => 'artist',
            'streamConfigured' => $streamChat->isConfigured(),
            'hasOpenBooking' => $streamChat->userHasAnyOpenBooking($user),
            'hasConversations' => ChatChannel::query()->forUser($user->id)->exists(),
        ]);
    }
}
