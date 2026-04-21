<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class PersonalPageController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userDetail = $user->userDetail;
        return view('artist.personal-page.index', compact('user', 'userDetail'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $validated = $request->validate([
            'personal_page_background_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'personal_page_color' => ['required', 'string', 'max:50'],
            'personal_page_tagline' => ['required', 'string', 'max:255'],
            'personal_page_description' => ['required', 'string', 'max:255'],
            'personal_page_name_alias' => ['required', 'in:full,username,both'],
        ]);

        if (! $request->hasFile('personal_page_background_image') && empty($userDetail->personal_page_background_image)) {
            throw ValidationException::withMessages([
                'personal_page_background_image' => ['Background image is required.'],
            ]);
        }

        $backgroundPath = $userDetail->personal_page_background_image;
        if ($request->hasFile('personal_page_background_image')) {
            if ($backgroundPath && file_exists(public_path($backgroundPath))) {
                File::delete(public_path($backgroundPath));
            }

            $file = $request->file('personal_page_background_image');
            $filename = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
            $destination = public_path('uploads/personal-pages');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $backgroundPath = 'uploads/personal-pages/' . $filename;
        }

        $userDetail->update([
            'personal_page_background_image' => $backgroundPath,
            'personal_page_color' => $validated['personal_page_color'] ?? null,
            'personal_page_tagline' => trim((string) ($validated['personal_page_tagline'] ?? '')) ?: null,
            'personal_page_description' => trim((string) ($validated['personal_page_description'] ?? '')) ?: null,
            'personal_page_name_alias' => $validated['personal_page_name_alias'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Personal page updated successfully.',
            'banner' => $backgroundPath ? asset($backgroundPath) : null,
        ]);
    }
}
