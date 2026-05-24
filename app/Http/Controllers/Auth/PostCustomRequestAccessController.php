<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostCustomRequestAccessController extends Controller
{
    public function __invoke(Request $request, User $user, CustomRequest $customRequest): RedirectResponse
    {
        if ((int) $customRequest->user_id !== (int) $user->id) {
            abort(403);
        }

        if ($user->role !== 'user') {
            abort(403);
        }

        Auth::login($user, false);
        $request->session()->regenerate();

        if ($user->must_set_password) {
            return redirect()->route('user.dashboard', ['set_password' => '1']);
        }

        return redirect()->route('user.requests.index', [
            'tab' => 'custom',
            'open' => $customRequest->id,
        ]);
    }
}
