<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'userDetail' => $request->user()->userDetail,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        
        $validated = $request->validated();
        $avatarPath = $userDetail->avatar;

        if ($request->hasFile('avatar')) {
            if ($avatarPath && file_exists(public_path($avatarPath))) {
                File::delete(public_path($avatarPath));
            }

            $file = $request->file('avatar');
            $filename = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
            $destination = public_path('uploads/avatars');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $avatarPath = 'uploads/avatars/' . $filename;
        }

        // Update basic user fields
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->save();

        // Update user detail fields
        $userDetail->update([
            'avatar' => $avatarPath,
            'user_name' => $validated['user_name'],
            'mobile_number' => $validated['mobile_number'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'status' => 'profile-updated',
                'avatar' => $avatarPath ? asset($avatarPath) : null,
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully.',
                'redirect' => url('/'),
            ]);
        }

        return Redirect::to('/');
    }
}
