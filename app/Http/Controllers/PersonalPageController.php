<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

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

    /**
     * @return array<string, mixed>
     */
    private function personalPageRequestContext(Request $request): array
    {
        $file = $request->file('personal_page_background_image');

        return [
            'user_id' => Auth::id(),
            'content_length' => $request->header('Content-Length'),
            'content_type' => $request->header('Content-Type'),
            'has_banner_file' => $request->hasFile('personal_page_background_image'),
            'remove_banner' => $request->boolean('remove_personal_page_background_image'),
            'banner_original_name' => $file?->getClientOriginalName(),
            'banner_mime' => $file?->getMimeType(),
            'banner_size_bytes' => $file?->getSize(),
            'banner_error_code' => $file?->getError(),
            'banner_is_valid' => $file ? $file->isValid() : null,
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_filesize' => ini_get('post_max_filesize'),
            'color' => $request->input('personal_page_color'),
            'name_alias' => $request->input('personal_page_name_alias'),
        ];
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
        $context = $this->personalPageRequestContext($request);

        try {
            if (
                empty($request->all())
                && (int) ($request->header('Content-Length') ?? 0) > 0
            ) {
                Log::warning('Personal page update: empty request body with Content-Length (likely post_max_filesize exceeded)', $context);

                return response()->json([
                    'success' => false,
                    'message' => 'Upload may be too large for the server. Please use an image under 4 MB and try again.',
                ], 413);
            }

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
                $file = $request->file('personal_page_background_image');

                if (! $file || ! $file->isValid()) {
                    Log::warning('Personal page update: invalid banner upload', array_merge($context, [
                        'upload_error_message' => $file?->getErrorMessage(),
                    ]));

                    return response()->json([
                        'success' => false,
                        'message' => 'Banner upload failed. Please try a smaller JPG, PNG, or WebP image.',
                        'errors' => [
                            'personal_page_background_image' => ['Banner upload failed. Please try a smaller JPG, PNG, or WebP image.'],
                        ],
                    ], 422);
                }

                $this->deleteBackgroundImageIfSafe($backgroundPath);

                $extension = strtolower((string) $file->getClientOriginalExtension());
                if ($extension === '') {
                    $extension = 'jpg';
                }
                $filename = time().'_'.uniqid('', true).'.'.$extension;
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

            Log::info('Personal page updated successfully', [
                'user_id' => Auth::id(),
                'banner' => $backgroundPath,
                'banner_uploaded' => $request->hasFile('personal_page_background_image'),
                'banner_removed' => $request->boolean('remove_personal_page_background_image'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Personal page updated successfully.',
                'banner' => $backgroundPath ? asset($backgroundPath) : null,
            ]);
        } catch (ValidationException $e) {
            Log::warning('Personal page update validation failed', array_merge($context, [
                'errors' => $e->errors(),
            ]));

            throw $e;
        } catch (Throwable $e) {
            Log::error('Personal page update failed', array_merge($context, [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Could not save personal page. Please try again.',
            ], 500);
        }
    }

    /**
     * Accept client-side failure details (network/non-JSON responses) into Laravel logs.
     */
    public function logClientError(Request $request)
    {
        $payload = $request->validate([
            'stage' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'integer'],
            'message' => ['nullable', 'string', 'max:2000'],
            'response_snippet' => ['nullable', 'string', 'max:4000'],
            'url' => ['nullable', 'string', 'max:500'],
            'banner_blob_size' => ['nullable', 'integer'],
            'banner_blob_type' => ['nullable', 'string', 'max:100'],
            'user_agent' => ['nullable', 'string', 'max:500'],
        ]);

        Log::warning('Personal page client error', [
            'user_id' => Auth::id(),
            'stage' => $payload['stage'] ?? null,
            'status' => $payload['status'] ?? null,
            'message' => $payload['message'] ?? null,
            'response_snippet' => $payload['response_snippet'] ?? null,
            'url' => $payload['url'] ?? null,
            'banner_blob_size' => $payload['banner_blob_size'] ?? null,
            'banner_blob_type' => $payload['banner_blob_type'] ?? null,
            'user_agent' => $payload['user_agent'] ?? $request->userAgent(),
            'content_length' => $request->header('Content-Length'),
        ]);

        return response()->json(['success' => true]);
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
