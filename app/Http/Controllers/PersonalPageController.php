<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PersonalPageController extends Controller
{
    private function deleteBackgroundImageIfSafe(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/personal-pages/')) {
            return;
        }

        $full = public_path($relativePath);
        if (file_exists($full)) {
            File::delete($full);
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail;

        if (
            $request->query('from') === 'dashboard_notice'
            && $userDetail
            && ! $userDetail->customize_page_notice_dismissed
        ) {
            $userDetail->update(['customize_page_notice_dismissed' => true]);

            return redirect()->route('personal-page.index');
        }

        return view('artist.personal-page.index', [
            'user' => $user,
            'userDetail' => $userDetail,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $validated = $request->validate([
            'personal_page_background_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_personal_page_background_image' => ['sometimes', 'boolean'],
            'personal_page_color' => ['required', 'string', 'max:50'],
            'personal_page_name_alias' => ['required', 'in:full,username,display_name'],
            'display_policies' => ['sometimes', 'boolean'],
            'display_tagline' => ['sometimes', 'boolean'],
            'display_bio' => ['sometimes', 'boolean'],
            'display_guest_spots' => ['sometimes', 'boolean'],
        ]);

        $backgroundPath = $userDetail->personal_page_background_image;

        if ($request->boolean('remove_personal_page_background_image')) {
            $this->deleteBackgroundImageIfSafe($backgroundPath);
            $backgroundPath = null;
        } elseif ($request->hasFile('personal_page_background_image')) {
            $this->deleteBackgroundImageIfSafe($backgroundPath);

            $file = $request->file('personal_page_background_image');
            $filename = time().'_'.uniqid().'.'.strtolower($file->getClientOriginalExtension());
            $destination = public_path('uploads/personal-pages');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $backgroundPath = 'uploads/personal-pages/'.$filename;
        }

        $userDetail->update([
            'personal_page_background_image' => $backgroundPath,
            'personal_page_color' => $validated['personal_page_color'] ?? null,
            'personal_page_name_alias' => $validated['personal_page_name_alias'],
            'display_policies' => $request->boolean('display_policies'),
            'display_tagline' => $request->boolean('display_tagline'),
            'display_bio' => $request->boolean('display_bio'),
            'display_guest_spots' => $request->boolean('display_guest_spots'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Personal page updated successfully.',
            'banner' => $backgroundPath ? asset($backgroundPath) : null,
        ]);
    }

    public function updateDisplayPolicies(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $request->validate([
            'display_policies' => ['required', 'boolean'],
        ]);

        $displayPolicies = $request->boolean('display_policies');
        $userDetail->update([
            'display_policies' => $displayPolicies,
        ]);

        return response()->json([
            'success' => true,
            'display_policies' => $displayPolicies,
            'message' => $displayPolicies
                ? 'Policies will be shown on your public page.'
                : 'Policies are hidden from your public page.',
        ]);
    }

    public function updateDisplayGuestSpots(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $request->validate([
            'display_guest_spots' => ['required', 'boolean'],
        ]);

        $displayGuestSpots = $request->boolean('display_guest_spots');
        $userDetail->update([
            'display_guest_spots' => $displayGuestSpots,
        ]);

        return response()->json([
            'success' => true,
            'display_guest_spots' => $displayGuestSpots,
            'message' => $displayGuestSpots
                ? 'Guest spots will be shown on your public page.'
                : 'Guest spots are hidden from your public page.',
        ]);
    }

    public function updateDisplayFaq(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $request->validate([
            'display_faq' => ['required', 'boolean'],
        ]);

        $displayFaq = $request->boolean('display_faq');
        $userDetail->update([
            'display_faq' => $displayFaq,
        ]);

        return response()->json([
            'success' => true,
            'display_faq' => $displayFaq,
            'message' => $displayFaq
                ? 'FAQ will be shown on your public page.'
                : 'FAQ is hidden from your public page.',
        ]);
    }

    public function updateProfileContentVisibility(Request $request)
    {
        $validated = $request->validate([
            'field' => ['required', 'in:display_tagline,display_bio'],
            'enabled' => ['required', 'boolean'],
        ]);

        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $field = $validated['field'];
        $enabled = $request->boolean('enabled');

        $userDetail->update([$field => $enabled]);

        $label = $field === 'display_tagline' ? 'Tagline' : 'Bio';

        return response()->json([
            'success' => true,
            'field' => $field,
            'enabled' => $enabled,
            'message' => $enabled
                ? $label.' will be shown on your public page.'
                : $label.' is hidden from your public page.',
        ]);
    }
}
