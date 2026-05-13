<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Models\UserDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class UserSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $userDetail = $user->userDetail;
        $avatarUrl = ($userDetail && $userDetail->avatar)
            ? asset($userDetail->avatar)
            : asset('design/images/icons/avatar.jpg');

        return view('user.settings.index', [
            'user' => $user,
            'userDetail' => $userDetail,
            'avatarUrl' => $avatarUrl,
        ]);
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();
        $userDetail = UserDetail::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        $avatarPath = $userDetail->avatar;

        if ($avatarPath && file_exists(public_path($avatarPath))) {
            File::delete(public_path($avatarPath));
        }

        $file = $request->file('avatar');
        $filename = time().'_'.uniqid('', true).'.'.strtolower($file->getClientOriginalExtension());
        $destination = public_path('uploads/avatars');
        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        $file->move($destination, $filename);
        $avatarPath = 'uploads/avatars/'.$filename;

        $userDetail->update(['avatar' => $avatarPath]);

        return redirect()->route('user.settings')->with('status', 'avatar-updated');
    }
}
