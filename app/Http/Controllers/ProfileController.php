<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Booking;
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
        $user = $request->user();
        $upcomingBookingsCount = 0;

        if ($user && $user->role === 'artist') {
            $upcomingBookingsCount = Booking::query()
                ->where('artist_user_id', $user->id)
                ->upcoming()
                ->count();
        }

        return view('profile.edit', [
            'user' => $user,
            'userDetail' => $user->userDetail,
            'activeNav' => 'account',
            'upcomingBookingsCount' => $upcomingBookingsCount,
        ]);
    }

    /**
     * Display the password change form (artist profile settings).
     */
    public function password(Request $request): View
    {
        return view('profile.password', [
            'user' => $request->user(),
            'activeNav' => 'account',
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

        // Account > Profile only requires core fields; don't wipe My Page content when omitted.
        $detailData = [
            'user_name' => $validated['user_name'],
            'mobile_number' => $validated['mobile_number'],
        ];

        if ($request->hasFile('avatar') || $avatarPath !== $userDetail->avatar) {
            $detailData['avatar'] = $avatarPath;
        }
        if (array_key_exists('display_name', $validated)) {
            $detailData['display_name'] = trim((string) $validated['display_name']) ?: null;
        }
        if (array_key_exists('personal_page_tagline', $validated)) {
            $detailData['personal_page_tagline'] = trim((string) $validated['personal_page_tagline']) ?: null;
        }
        if (array_key_exists('personal_page_description', $validated)) {
            $detailData['personal_page_description'] = trim((string) $validated['personal_page_description']) ?: null;
        }

        $userDetail->update($detailData);

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
     * Artist self-service: deactivate public presence (dashboard stays available).
     */
    public function deactivate(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'artist') {
            return $this->accountStatusResponse($request, false, 'Only artists can deactivate their account.', 403);
        }

        if ($user->isDeactivated()) {
            return $this->accountStatusResponse($request, true, 'Your account is already deactivated.', 200, true);
        }

        $user->forceFill(['deactivated_at' => now()])->save();

        return $this->accountStatusResponse(
            $request,
            true,
            'Account deactivated. Your page is hidden and clients can’t make new bookings.',
            200,
            true
        );
    }

    /**
     * Artist self-service: reactivate public presence.
     */
    public function reactivate(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'artist') {
            return $this->accountStatusResponse($request, false, 'Only artists can reactivate their account.', 403);
        }

        if (! $user->isDeactivated()) {
            return $this->accountStatusResponse($request, true, 'Your account is already active.', 200, false);
        }

        $user->forceFill(['deactivated_at' => null])->save();

        return $this->accountStatusResponse(
            $request,
            true,
            'Account reactivated. Your page and new bookings are available again.',
            200,
            false
        );
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

    private function accountStatusResponse(
        Request $request,
        bool $success,
        string $message,
        int $status = 200,
        ?bool $deactivated = null
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            $payload = [
                'success' => $success,
                'message' => $message,
            ];
            if ($deactivated !== null) {
                $payload['deactivated'] = $deactivated;
            }

            return response()->json($payload, $status);
        }

        $redirect = Redirect::back();

        return $success
            ? $redirect->with('status', $message)
            : $redirect->withErrors(['account' => $message]);
    }
}
