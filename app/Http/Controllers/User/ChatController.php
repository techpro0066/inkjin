<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatChannel;
use App\Services\StreamChatService;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(StreamChatService $streamChat): View
    {
        $user = auth()->user();

        return view('user.chat.index', [
            'role' => 'user',
            'streamConfigured' => $streamChat->isConfigured(),
            'hasOpenBooking' => $streamChat->userHasAnyOpenBooking($user),
            'hasConversations' => ChatChannel::query()->forUser($user->id)->exists(),
        ]);
    }
}
