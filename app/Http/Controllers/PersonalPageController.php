<?php

namespace App\Http\Controllers;

use App\Models\ArtistDesign;
use App\Models\ArtistFaq;
use App\Models\Portfolio;
use App\Models\Style;
use App\Models\UserDetail;
use App\Support\ArtistPolicyCopy;
use App\Support\PageLook;
use App\Support\SocialLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PersonalPageController extends Controller
{
    private const EXTRA_SERVICE_OPTIONS = [
        'Touch-ups',
        'Cover-ups',
        'Blackout',
        'Other',
    ];

    private const STYLE_FALLBACK_SLUGS = [
        'traditional', 'neo-traditional', 'japanese', 'realism', 'blackwork',
        'minimalist', 'geometric', 'watercolor', 'tribal', 'dotwork', 'new-school', 'illustrative',
    ];

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
     * Persist a data-URL or keep/resolve an existing personal-page image path.
     *
     * @return array{0: ?string, 1: ?string} [relativePath, errorMessage]
     */
    private function resolveLookImage(?string $incoming, ?string $currentPath, string $prefix): array
    {
        $incoming = is_string($incoming) ? trim($incoming) : '';

        if ($incoming === '') {
            $this->deleteBackgroundImageIfSafe($currentPath);

            return [null, null];
        }

        if (str_starts_with($incoming, 'data:image/')) {
            if (! preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#i', $incoming, $m)) {
                return [null, 'Use a JPG, PNG or WebP image.'];
            }
            $binary = base64_decode(substr($incoming, strpos($incoming, ',') + 1), true);
            if ($binary === false || $binary === '') {
                return [null, 'This image could not be read.'];
            }
            if (strlen($binary) > 10 * 1024 * 1024) {
                return [null, 'This image is over 10 MB. Use a smaller one.'];
            }

            $ext = strtolower($m[1]) === 'jpg' ? 'jpg' : strtolower($m[1]);
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            $this->deleteBackgroundImageIfSafe($currentPath);

            $filename = $prefix.'_'.time().'_'.uniqid('', true).'.'.$ext;
            $destination = public_path('uploads/personal-pages');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            File::put($destination.DIRECTORY_SEPARATOR.$filename, $binary);

            return ['uploads/personal-pages/'.$filename, null];
        }

        // Absolute URL for an already-stored upload → keep relative path
        $appUrl = rtrim((string) config('app.url'), '/');
        $assetBase = rtrim(asset('uploads/personal-pages'), '/');
        foreach ([$appUrl.'/uploads/personal-pages/', $assetBase.'/'] as $base) {
            if ($base !== '' && str_starts_with($incoming, $base)) {
                $rel = 'uploads/personal-pages/'.ltrim(substr($incoming, strlen($base)), '/');
                if (str_starts_with($rel, 'uploads/personal-pages/') && ! str_contains($rel, '..')) {
                    return [$rel, null];
                }
            }
        }

        if (str_starts_with($incoming, 'uploads/personal-pages/') && ! str_contains($incoming, '..')) {
            return [$incoming, null];
        }

        // Unchanged remote/other URL: keep existing DB path
        return [$currentPath, null];
    }

    /**
     * @return array<string, string>
     */
    private function activeStyleOptions(): array
    {
        $fromDb = Style::query()
            ->active()
            ->ordered()
            ->pluck('name', 'name')
            ->toArray();

        if (! empty($fromDb)) {
            return $fromDb;
        }

        $fallback = [];
        foreach (self::STYLE_FALLBACK_SLUGS as $slug) {
            $fallback[$slug] = ucwords(str_replace('-', ' ', $slug));
        }

        return $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private function personalPageRequestContext(Request $request): array
    {
        $file = $request->file('personal_page_background_image');

        return [
            'user_id' => Auth::id(),
            'section' => $request->input('section'),
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

    /**
     * @return array{services: list<string>, other: ?string}|null
     */
    private function normalizeExtraServices(Request $request): ?array
    {
        $selected = $request->input('extra_services', []);
        if (! is_array($selected)) {
            $selected = [];
        }

        $services = [];
        foreach ($selected as $value) {
            $value = trim((string) $value);
            if ($value !== '' && in_array($value, self::EXTRA_SERVICE_OPTIONS, true)) {
                $services[] = $value;
            }
        }
        $services = array_values(array_unique($services));

        $hasOther = in_array('Other', $services, true);
        $otherText = $hasOther
            ? trim((string) $request->input('extra_service_other', ''))
            : '';

        if ($otherText !== '') {
            $otherText = mb_substr($otherText, 0, 40);
        } else {
            $otherText = null;
        }

        if ($services === [] && $otherText === null) {
            return null;
        }

        return [
            'services' => $services,
            'other' => $otherText,
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

        if ($request->query('tab') === 'appearance') {
            return redirect()->route('personal-page.appearance');
        }

        $styleOptions = $this->activeStyleOptions();

        return view('artist.personal-page.index', array_merge([
            'user' => $user,
            'userDetail' => $userDetail,
            'activeNav' => 'my-page',
            'myPageTab' => 'about',
            'styleOptions' => $styleOptions,
        ], $this->myPageTabCounts($user->id)));
    }

    public function policies()
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        return view('artist.personal-page.policies', array_merge([
            'user' => $user,
            'userDetail' => $userDetail,
            'activeNav' => 'my-page',
            'myPageTab' => 'policies',
            'policyCopy' => ArtistPolicyCopy::for($userDetail),
        ], $this->myPageTabCounts($user->id)));
    }

    public function appearance()
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        return view('artist.personal-page.appearance', array_merge([
            'user' => $user,
            'userDetail' => $userDetail,
            'activeNav' => 'my-page',
            'myPageTab' => 'appearance',
            'styleOptions' => $this->activeStyleOptions(),
            'lookState' => PageLook::stateFromUserDetail($userDetail),
        ], $this->myPageTabCounts($user->id)));
    }

    public function updateAppearanceLook(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $validated = $request->validate([
            'mode' => ['required', 'in:standard,custom'],
            'scheme' => ['required', 'string', 'max:64'],
            'banner' => ['nullable', 'string'],
            'c' => ['required', 'array'],
            'c.bgD' => ['nullable', 'string'],
            'c.bgM' => ['nullable', 'string'],
            'c.bgLum' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'c.bgAvg' => ['nullable', 'string', 'max:16'],
            'c.box' => ['nullable', 'string', 'max:16'],
            'c.effect' => ['nullable', 'in:solid,see,glass'],
            'c.op' => ['nullable', 'integer', 'min:5', 'max:95'],
            'c.title' => ['nullable', 'in:auto,black,white,custom'],
            'c.titleC' => ['nullable', 'string', 'max:16'],
            'c.text' => ['nullable', 'in:auto,black,white'],
            'c.radius' => ['nullable', 'in:none,s,m,l'],
        ]);

        [$bannerPath, $bannerErr] = $this->resolveLookImage(
            $validated['banner'] ?? null,
            $userDetail->personal_page_background_image,
            'banner'
        );
        if ($bannerErr) {
            return response()->json(['success' => false, 'message' => $bannerErr], 422);
        }

        [$bgDPath, $bgDErr] = $this->resolveLookImage(
            $validated['c']['bgD'] ?? null,
            $userDetail->page_bg_desktop,
            'bgd'
        );
        if ($bgDErr) {
            return response()->json(['success' => false, 'message' => $bgDErr], 422);
        }

        [$bgMPath, $bgMErr] = $this->resolveLookImage(
            $validated['c']['bgM'] ?? null,
            $userDetail->page_bg_mobile,
            'bgm'
        );
        if ($bgMErr) {
            return response()->json(['success' => false, 'message' => $bgMErr], 422);
        }

        $userDetail->update(PageLook::columnsFromState($validated, $bannerPath, $bgDPath, $bgMPath));

        $fresh = $userDetail->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
            'look' => PageLook::stateFromUserDetail($fresh),
        ]);
    }

    public function updatePolicies(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $validated = $request->validate([
            'design_whats_included_is_active' => ['sometimes', 'boolean'],
            'design_whats_included' => ['sometimes', 'nullable', 'array', 'max:8'],
            'design_whats_included.*' => ['string', 'max:255'],
            'display_policies' => ['sometimes', 'boolean'],
            'policies_extra_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $items = array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $validated['design_whats_included'] ?? []
        ), fn (string $item) => $item !== ''));

        $userDetail->update([
            'design_whats_included_is_active' => $request->boolean('design_whats_included_is_active'),
            'design_whats_included' => $items,
            'display_policies' => $request->boolean('display_policies'),
            'policies_extra_note' => trim((string) ($validated['policies_extra_note'] ?? '')) ?: null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
        ]);
    }

    /**
     * @return array{designsCount: int, portfolioCount: int, faqCount: int}
     */
    private function myPageTabCounts(int $userId): array
    {
        return [
            'designsCount' => ArtistDesign::query()->where('user_id', $userId)->count(),
            'portfolioCount' => Portfolio::query()->where('user_id', $userId)->count(),
            'faqCount' => ArtistFaq::query()->where('user_id', $userId)->count(),
        ];
    }

    public function update(Request $request)
    {
        $context = $this->personalPageRequestContext($request);
        $section = $request->input('section', 'appearance');

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

            if ($section === 'about') {
                return $this->updateAbout($request, $user, $userDetail);
            }

            return $this->updateAppearance($request, $userDetail, $context);
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

    private function updateAbout(Request $request, $user, UserDetail $userDetail)
    {
        $allowedStyles = array_keys($this->activeStyleOptions());
        $maxYear = (int) date('Y');

        $validated = $request->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,heif,heic', 'max:2048'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'personal_page_name_alias' => ['required', 'in:username,display_name,full'],
            'personal_page_tagline' => ['nullable', 'string', 'max:80'],
            'personal_page_description' => ['nullable', 'string', 'max:500'],
            'display_tagline' => ['sometimes', 'boolean'],
            'display_bio' => ['sometimes', 'boolean'],
            'tattooing_since' => ['nullable', 'integer', 'min:1970', 'max:'.$maxYear],
            'primary_style' => ['nullable', 'string', Rule::in($allowedStyles)],
            'other_styles' => ['nullable', 'array'],
            'other_styles.*' => ['string', Rule::in($allowedStyles)],
            'extra_services' => ['nullable', 'array'],
            'extra_services.*' => ['string', Rule::in(self::EXTRA_SERVICE_OPTIONS)],
            'extra_service_other' => ['nullable', 'string', 'max:40'],
            'social_links' => ['nullable', 'array'],
            'social_links.instagram' => ['nullable', 'string', 'max:255'],
            'social_links.tiktok' => ['nullable', 'string', 'max:255'],
            'social_links.youtube' => ['nullable', 'string', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'social_links.website' => ['nullable', 'string', 'max:500'],
        ]);

        $alias = $validated['personal_page_name_alias'];
        if ($alias === 'display_name' && trim((string) ($validated['display_name'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'display_name' => ['Add a display name before choosing it for your page.'],
            ]);
        }

        $other = array_values(array_unique(array_filter(array_map(
            'trim',
            $validated['other_styles'] ?? []
        ))));
        $primary = $validated['primary_style'] ?? null;
        if ($primary) {
            $other = array_values(array_filter($other, fn ($s) => $s !== $primary));
        }

        $social = SocialLinks::normalize($validated['social_links'] ?? []);
        $extraServices = $this->normalizeExtraServices($request);

        $avatarPath = $userDetail->avatar;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            if ($file && $file->isValid()) {
                if ($avatarPath && file_exists(public_path($avatarPath))) {
                    File::delete(public_path($avatarPath));
                }
                $extension = strtolower((string) $file->getClientOriginalExtension()) ?: 'jpg';
                $filename = time().'_'.uniqid('', true).'.'.$extension;
                $destination = public_path('uploads/avatars');
                if (! File::exists($destination)) {
                    File::makeDirectory($destination, 0755, true);
                }
                $file->move($destination, $filename);
                $avatarPath = 'uploads/avatars/'.$filename;
            }
        }

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->save();

        $stylePayload = array_filter([
            'tattooing_since' => $validated['tattooing_since'] ?? null,
            'primary_style' => $primary,
            'other_styles' => $other ?: null,
        ], fn ($v) => $v !== null && $v !== [] && $v !== '');

        $userDetail->update([
            'avatar' => $avatarPath,
            'display_name' => trim((string) ($validated['display_name'] ?? '')) ?: null,
            'personal_page_name_alias' => $alias,
            'personal_page_tagline' => trim((string) ($validated['personal_page_tagline'] ?? '')) ?: null,
            'personal_page_description' => trim((string) ($validated['personal_page_description'] ?? '')) ?: null,
            'display_tagline' => $request->boolean('display_tagline'),
            'display_bio' => $request->boolean('display_bio'),
            'tattoo_styles' => ! empty($stylePayload) ? $stylePayload : null,
            'extra_services' => $extraServices,
            'social_links' => ! empty($social) ? $social : null,
        ]);

        Log::info('Personal page about updated', ['user_id' => Auth::id()]);

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
            'avatar' => $avatarPath ? asset($avatarPath) : null,
        ]);
    }

    private function updateAppearance(Request $request, UserDetail $userDetail, array $context)
    {
        $validated = $request->validate([
            'personal_page_background_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_personal_page_background_image' => ['sometimes', 'boolean'],
            'personal_page_color' => ['required', 'string', 'max:50'],
            'personal_page_name_alias' => ['sometimes', 'in:full,username,display_name'],
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

        $data = [
            'personal_page_background_image' => $backgroundPath,
            'personal_page_color' => $validated['personal_page_color'] ?? null,
        ];

        if ($request->has('personal_page_name_alias')) {
            $data['personal_page_name_alias'] = $validated['personal_page_name_alias'];
        }
        if ($request->has('display_policies')) {
            $data['display_policies'] = $request->boolean('display_policies');
        }
        if ($request->has('display_tagline')) {
            $data['display_tagline'] = $request->boolean('display_tagline');
        }
        if ($request->has('display_bio')) {
            $data['display_bio'] = $request->boolean('display_bio');
        }
        if ($request->has('display_guest_spots')) {
            $data['display_guest_spots'] = $request->boolean('display_guest_spots');
        }

        $userDetail->update($data);

        Log::info('Personal page appearance updated', [
            'user_id' => Auth::id(),
            'banner' => $backgroundPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
            'banner' => $backgroundPath ? asset($backgroundPath) : null,
        ]);
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

    public function updateDisplayPortfolio(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $request->validate([
            'display_portfolio' => ['required', 'boolean'],
        ]);

        $displayPortfolio = $request->boolean('display_portfolio');
        $userDetail->update([
            'display_portfolio' => $displayPortfolio,
        ]);

        return response()->json([
            'success' => true,
            'display_portfolio' => $displayPortfolio,
            'message' => $displayPortfolio
                ? 'Portfolio will be shown on your public page.'
                : 'Portfolio is hidden from your public page.',
        ]);
    }

    public function updateDisplayDesigns(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $request->validate([
            'display_designs' => ['required', 'boolean'],
        ]);

        $displayDesigns = $request->boolean('display_designs');
        $userDetail->update([
            'display_designs' => $displayDesigns,
        ]);

        return response()->json([
            'success' => true,
            'display_designs' => $displayDesigns,
            'message' => $displayDesigns
                ? 'Available designs will be shown on your public page.'
                : 'Available designs are hidden from your public page.',
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
