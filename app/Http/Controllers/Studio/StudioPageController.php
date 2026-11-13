<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\SuggestsTattooImageFieldsWithAi;
use App\Models\ArtistDesign;
use App\Models\Placement;
use App\Models\Portfolio;
use App\Models\Studio;
use App\Models\StudioPortfolio;
use App\Models\Style;
use App\Models\UserStudio;
use App\Services\InstagramPortfolioImportService;
use App\Support\PageLook;
use App\Support\SocialLinks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudioPageController extends Controller
{
    use SuggestsTattooImageFieldsWithAi;

    private const EXTRA_SERVICE_OPTIONS = [
        'Touch-ups',
        'Cover-ups',
        'Blackout',
        'Other',
    ];

    private const STYLE_FALLBACK = [
        'Blackwork',
        'Color',
        'Traditional (Old School)',
        'Japanese / Irezumi',
        'Neo-Traditional',
        'New School',
        'Fine Line',
        'Realism',
        'Black & Grey',
        'Dotwork',
        'Geometric',
        'Watercolor',
        'Tribal',
        'Lettering',
        'Minimalist',
        'Illustrative',
        'Trash Polka',
        'Chicano',
        'Ornamental',
        'Anime',
    ];

    public function about(Request $request): View
    {
        return view('studio.page.about', $this->viewData($request, 'about'));
    }

    public function artists(Request $request): View
    {
        $data = $this->viewData($request, 'artists');
        $studio = $data['studio'] ?? null;

        return view('studio.page.artists', array_merge($data, [
            'showGuestArtists' => (bool) ($studio?->show_guest_artists ?? true),
            'pageArtists' => $studio ? $this->pageArtistsForStudio($studio) : collect(),
            'updateArtistsUrl' => route('studio.page.artists.update'),
        ]));
    }

    public function updateArtists(Request $request): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio not found.',
            ], 404);
        }

        $validated = $request->validate([
            'show_guest_artists' => ['sometimes', 'boolean'],
            'artists' => ['nullable', 'array'],
            'artists.*.id' => ['required', 'integer'],
            'artists.*.show_on_page' => ['required', 'boolean'],
        ]);

        $studio->update([
            'show_guest_artists' => $request->boolean('show_guest_artists'),
        ]);

        $rows = $validated['artists'] ?? [];
        $canPersistPageFields = UserStudio::tableReady()
            && Schema::hasColumn('user_studios', 'show_on_page')
            && Schema::hasColumn('user_studios', 'page_sort_order');

        if ($rows !== [] && $canPersistPageFields) {
            $ids = collect($rows)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $owned = UserStudio::query()
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->all();
            $ownedSet = array_fill_keys($owned, true);

            DB::transaction(function () use ($rows, $ownedSet) {
                foreach (array_values($rows) as $index => $row) {
                    $id = (int) ($row['id'] ?? 0);
                    if ($id < 1 || ! isset($ownedSet[$id])) {
                        continue;
                    }

                    UserStudio::query()->whereKey($id)->update([
                        'show_on_page' => (bool) ($row['show_on_page'] ?? true),
                        'page_sort_order' => $index + 1,
                    ]);
                }
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
        ]);
    }

    public function portfolio(Request $request): View
    {
        $data = $this->viewData($request, 'portfolio');
        $studio = $data['studio'] ?? null;
        $taggableArtists = $studio ? $this->taggableArtistsForStudio($studio) : collect();

        return view('studio.page.portfolio', array_merge($data, [
            'showArtistsFlash' => (bool) ($studio?->show_artists_flash ?? true),
            'showArtistsPortfolio' => (bool) ($studio?->show_artists_portfolio ?? true),
            'artistWorkPreview' => $studio ? $this->artistWorkPreviewForStudio($studio) : collect(),
            'studioPieces' => $studio ? $this->studioPiecesForStudio($studio) : collect(),
            'taggableArtists' => $taggableArtists,
            'styles' => $this->styles(),
            'placements' => $this->placements(),
            'instagramConnected' => filled($studio?->instagram_access_token),
            'instagramUsername' => trim((string) ($studio?->instagram_username ?? '')),
            'instagramImportCount' => $studio
                ? StudioPortfolio::query()
                    ->where('studio_id', $studio->id)
                    ->where('source', StudioPortfolio::SOURCE_INSTAGRAM)
                    ->count()
                : 0,
            'updatePortfolioUrl' => route('studio.page.portfolio.update'),
            'storePieceUrl' => route('studio.page.portfolio.piece.store'),
            'aiSuggestUrl' => route('studio.page.portfolio.ai-suggest'),
            'instagramConnectUrl' => route('instagram.connect', ['target' => 'studio']),
            'instagramRefreshUrl' => route('studio.page.portfolio.instagram.refresh'),
            'instagramDisconnectUrl' => route('studio.page.portfolio.instagram.disconnect'),
            'autoImportInstagram' => (bool) session()->pull('instagram_auto_import'),
        ]));
    }

    public function storePiece(Request $request): JsonResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio not found.'], 404);
        }

        $validated = $request->validate($this->studioPieceRules(true));
        [$other, $tags] = $this->normalizeOtherAndTags($validated);
        $artistIds = $this->taggableArtistsForStudio($studio)->pluck('user_id')->all();
        [$attribution, $artistUserId] = $this->normalizePieceAttribution(
            (string) ($validated['attribution'] ?? StudioPortfolio::ATTRIBUTION_STUDIO),
            isset($validated['artist_user_id']) ? (int) $validated['artist_user_id'] : null,
            $artistIds
        );

        $file = $request->file('image');
        $filename = time().'_'.uniqid('', true).'.'.strtolower($file->getClientOriginalExtension());
        $destination = public_path('uploads/studio-portfolios');
        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        $file->move($destination, $filename);

        $nextOrder = (int) StudioPortfolio::query()->where('studio_id', $studio->id)->max('sort_order') + 1;

        $piece = StudioPortfolio::create([
            'studio_id' => $studio->id,
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'is_active' => $request->boolean('is_active', true),
            'image' => 'uploads/studio-portfolios/'.$filename,
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'placement' => $validated['placement'] ?? null,
            'color' => $validated['color'],
            'tags' => $tags,
            'attribution' => $attribution,
            'artist_user_id' => $artistUserId,
            'source' => StudioPortfolio::SOURCE_MANUAL,
            'sort_order' => $nextOrder,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work saved to your studio portfolio.',
            'portfolio' => [
                'id' => $piece->id,
                'title' => $piece->title,
                'image_url' => asset($piece->image),
            ],
        ]);
    }

    public function updatePiece(Request $request, StudioPortfolio $piece): JsonResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio || (int) $piece->studio_id !== (int) $studio->id) {
            return response()->json(['success' => false, 'message' => 'Studio piece not found.'], 404);
        }

        $validated = $request->validate($this->studioPieceRules(false));
        [$other, $tags] = $this->normalizeOtherAndTags($validated);
        $artistIds = $this->taggableArtistsForStudio($studio)->pluck('user_id')->all();
        [$attribution, $artistUserId] = $this->normalizePieceAttribution(
            (string) ($validated['attribution'] ?? $piece->attribution),
            array_key_exists('artist_user_id', $validated)
                ? (isset($validated['artist_user_id']) ? (int) $validated['artist_user_id'] : null)
                : ($piece->artist_user_id ? (int) $piece->artist_user_id : null),
            $artistIds
        );

        $imagePath = $piece->image;
        if ($request->hasFile('image')) {
            $this->deleteStudioPortfolioImage($piece->image);
            $file = $request->file('image');
            $filename = time().'_'.uniqid('', true).'.'.strtolower($file->getClientOriginalExtension());
            $destination = public_path('uploads/studio-portfolios');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imagePath = 'uploads/studio-portfolios/'.$filename;
        }

        $piece->update([
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'is_active' => $request->boolean('is_active', true),
            'image' => $imagePath,
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'placement' => $validated['placement'] ?? null,
            'color' => $validated['color'],
            'tags' => $tags,
            'attribution' => $attribution,
            'artist_user_id' => $artistUserId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work updated.',
            'portfolio' => [
                'id' => $piece->id,
                'title' => $piece->title,
                'image_url' => asset($piece->image),
            ],
        ]);
    }

    public function destroyPiece(Request $request, StudioPortfolio $piece): JsonResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio || (int) $piece->studio_id !== (int) $studio->id) {
            return response()->json(['success' => false, 'message' => 'Studio piece not found.'], 404);
        }

        $this->deleteStudioPortfolioImage($piece->image);
        $piece->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work deleted from your studio portfolio.',
        ]);
    }

    public function updatePortfolio(Request $request): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio not found.',
            ], 404);
        }

        $artistIds = $this->taggableArtistsForStudio($studio)->pluck('user_id')->all();

        $validated = $request->validate([
            'show_artists_flash' => ['sometimes', 'boolean'],
            'show_artists_portfolio' => ['sometimes', 'boolean'],
            'pieces' => ['nullable', 'array'],
            'pieces.*.id' => ['required', 'integer'],
            'pieces.*.title' => ['nullable', 'string', 'max:255'],
            'pieces.*.attribution' => ['required', 'in:pending,studio,artist'],
            'pieces.*.artist_user_id' => ['nullable', 'integer'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer'],
            'new_images' => ['nullable', 'array', 'max:20'],
            'new_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'new_titles' => ['nullable', 'array'],
            'new_titles.*' => ['nullable', 'string', 'max:255'],
            'new_attributions' => ['nullable', 'array'],
            'new_attributions.*' => ['nullable', 'in:pending,studio,artist'],
            'new_artist_user_ids' => ['nullable', 'array'],
            'new_artist_user_ids.*' => ['nullable', 'integer'],
        ]);

        $studio->update([
            'show_artists_flash' => $request->boolean('show_artists_flash'),
            'show_artists_portfolio' => $request->boolean('show_artists_portfolio'),
        ]);

        $deletedIds = array_values(array_unique(array_map('intval', $validated['deleted_ids'] ?? [])));
        if ($deletedIds !== []) {
            $toDelete = StudioPortfolio::query()
                ->where('studio_id', $studio->id)
                ->whereIn('id', $deletedIds)
                ->get();
            foreach ($toDelete as $piece) {
                $this->deleteStudioPortfolioImage($piece->image);
                $piece->delete();
            }
        }

        foreach ($validated['pieces'] ?? [] as $row) {
            $id = (int) ($row['id'] ?? 0);
            $piece = StudioPortfolio::query()
                ->where('studio_id', $studio->id)
                ->whereKey($id)
                ->first();
            if (! $piece) {
                continue;
            }

            $attribution = (string) ($row['attribution'] ?? StudioPortfolio::ATTRIBUTION_PENDING);
            $artistUserId = isset($row['artist_user_id']) ? (int) $row['artist_user_id'] : null;
            if ($attribution === StudioPortfolio::ATTRIBUTION_ARTIST) {
                if (! $artistUserId || ! in_array($artistUserId, $artistIds, true)) {
                    $attribution = StudioPortfolio::ATTRIBUTION_PENDING;
                    $artistUserId = null;
                }
            } else {
                $artistUserId = null;
            }

            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                $title = $piece->title ?: 'Untitled';
            }

            $piece->update([
                'title' => $title,
                'attribution' => $attribution,
                'artist_user_id' => $artistUserId,
            ]);
        }

        $files = $request->file('new_images', []);
        if (! is_array($files)) {
            $files = [];
        }
        $titles = $validated['new_titles'] ?? [];
        $newAttributions = $validated['new_attributions'] ?? [];
        $newArtistIds = $validated['new_artist_user_ids'] ?? [];
        $nextOrder = (int) StudioPortfolio::query()->where('studio_id', $studio->id)->max('sort_order');
        $destination = public_path('uploads/studio-portfolios');
        if ($files !== [] && ! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        foreach (array_values($files) as $index => $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $ext = strtolower((string) $file->getClientOriginalExtension()) ?: 'jpg';
            $filename = time().'_'.uniqid('', true).'.'.$ext;
            $file->move($destination, $filename);
            $title = trim((string) ($titles[$index] ?? ''));
            if ($title === '') {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Studio piece';
            }
            $attribution = (string) ($newAttributions[$index] ?? StudioPortfolio::ATTRIBUTION_STUDIO);
            $artistUserId = isset($newArtistIds[$index]) ? (int) $newArtistIds[$index] : null;
            if ($attribution === StudioPortfolio::ATTRIBUTION_ARTIST) {
                if (! $artistUserId || ! in_array($artistUserId, $artistIds, true)) {
                    $attribution = StudioPortfolio::ATTRIBUTION_PENDING;
                    $artistUserId = null;
                }
            } else {
                $artistUserId = null;
            }
            $nextOrder++;
            StudioPortfolio::create([
                'studio_id' => $studio->id,
                'title' => mb_substr($title, 0, 255),
                'image' => 'uploads/studio-portfolios/'.$filename,
                'attribution' => $attribution,
                'artist_user_id' => $artistUserId,
                'source' => StudioPortfolio::SOURCE_MANUAL,
                'sort_order' => $nextOrder,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
        ]);
    }

    public function refreshInstagram(Request $request): JsonResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio not found.'], 404);
        }
        if (! filled($studio->instagram_access_token)) {
            return response()->json(['success' => false, 'message' => 'Connect Instagram first.'], 422);
        }

        try {
            $result = app(InstagramPortfolioImportService::class)->importLatestForStudio($studio);

            return response()->json([
                'success' => true,
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
                'message' => $result['message'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import Instagram images. Please try again.',
            ], 500);
        }
    }

    public function disconnectInstagram(Request $request): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Studio not found.'], 404);
            }

            return redirect()->route('studio.page.portfolio')->with('error', 'Studio not found.');
        }

        $studio->update([
            'instagram_user_id' => null,
            'instagram_username' => null,
            'instagram_profile_picture' => null,
            'instagram_access_token' => null,
            'instagram_token_expires_at' => null,
            'instagram_connected_at' => null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instagram disconnected',
            ]);
        }

        return redirect()->route('studio.page.portfolio')->with('success', 'Instagram disconnected');
    }

    public function look(Request $request): View
    {
        $data = $this->viewData($request, 'look');
        $studio = $data['studio'] ?? null;

        $logoPath = trim((string) ($studio?->logo_url ?? ''));
        if ($logoPath === '') {
            $logoPath = trim((string) ($studio?->image_url ?? ''));
        }
        $avatarUrl = $logoPath !== ''
            ? (str_starts_with($logoPath, 'http') ? $logoPath : asset(ltrim($logoPath, '/')))
            : ($data['ownerAvatarUrl'] ?? null);

        $city = trim((string) ($studio?->city ?? ''));
        $address = trim((string) ($studio?->address ?? ''));
        $location = $address !== '' ? $address : $city;
        if ($location === '' && $city !== '') {
            $location = $city;
        }
        $since = $studio?->tattooing_since;
        $metaParts = array_filter([
            $location !== '' ? $location : null,
            $since ? 'Est. '.$since : null,
        ]);

        $styleLabels = is_array($data['studioStyles'] ?? null) ? $data['studioStyles'] : [];
        if ($styleLabels === [] && ! empty($data['artistStyles'])) {
            $styleLabels = array_values(array_unique(array_map('strval', $data['artistStyles'])));
        }

        $artistCount = 0;
        if ($studio && UserStudio::tableReady()) {
            $artistCount = UserStudio::query()
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->count();
        }

        $bio = trim((string) ($studio?->description ?? ''));
        if ($bio === '') {
            $bio = 'Tattoo studio. Book ahead or walk in.';
        }

        $tagline = $styleLabels !== []
            ? implode(' · ', array_slice($styleLabels, 0, 3))
            : 'Tattoo shop';

        $lookCfg = [
            'who' => 'studio',
            'base' => '',
            'ownSave' => true,
            'square' => true,
            'name' => $data['studioName'] ?? 'Studio',
            'avatar' => $avatarUrl,
            'initials' => $data['studioInitials'] ?? 'ST',
            'tagline' => $tagline,
            'meta' => $metaParts !== [] ? implode(' · ', $metaParts) : 'Your city · Est. year',
            'styles' => $styleLabels !== [] ? implode(' · ', $styleLabels) : 'Traditional · Neo-traditional · Fine line',
            'bio' => $bio,
            'cta' => 'Book with us',
            'bookLine' => $artistCount === 1
                ? '1 artist works here'
                : ($artistCount > 1 ? $artistCount.' artists work here' : 'Artists work here'),
            'pageUrl' => ($data['publicPageUrl'] ?? '') !== ''
                ? $data['publicPageUrl']
                : ('https://'.($data['pageUrl'] ?? 'inkjin.com/@username')),
        ];

        return view('studio.page.look', array_merge($data, [
            'lookState' => $studio
                ? PageLook::stateFromStudio($studio)
                : PageLook::stateFromStudio(new Studio),
            'lookCfg' => $lookCfg,
            'updateLookUrl' => route('studio.page.look.update'),
        ]));
    }

    public function updateLook(Request $request): JsonResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio not found.',
            ], 404);
        }

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
            'c.text' => ['nullable', 'in:auto,black,white,custom'],
            'c.textC' => ['nullable', 'string', 'max:16'],
            'c.btn' => ['nullable', 'in:auto,black,white,custom'],
            'c.btnC' => ['nullable', 'string', 'max:16'],
            'c.radius' => ['nullable', 'in:none,s,m,l'],
        ]);

        [$bannerPath, $bannerErr] = $this->resolveLookImage(
            $validated['banner'] ?? null,
            $studio->page_banner,
            'banner'
        );
        if ($bannerErr) {
            return response()->json(['success' => false, 'message' => $bannerErr], 422);
        }

        [$bgDPath, $bgDErr] = $this->resolveLookImage(
            $validated['c']['bgD'] ?? null,
            $studio->page_bg_desktop,
            'bgd'
        );
        if ($bgDErr) {
            return response()->json(['success' => false, 'message' => $bgDErr], 422);
        }

        [$bgMPath, $bgMErr] = $this->resolveLookImage(
            $validated['c']['bgM'] ?? null,
            $studio->page_bg_mobile,
            'bgm'
        );
        if ($bgMErr) {
            return response()->json(['success' => false, 'message' => $bgMErr], 422);
        }

        $studio->update(PageLook::columnsFromStudioState($validated, $bannerPath, $bgDPath, $bgMPath));

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
            'look' => PageLook::stateFromStudio($studio->fresh()),
        ]);
    }

    public function updateAbout(Request $request): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio not found.',
            ], 404);
        }

        $allowedStyles = $this->styleOptionNames();

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:600'],
            'show_map' => ['sometimes', 'boolean'],
            'show_artist_styles' => ['sometimes', 'boolean'],
            'tattoo_styles' => ['nullable', 'array'],
            'tattoo_styles.*' => ['string', Rule::in($allowedStyles)],
            'extra_services' => ['nullable', 'array'],
            'extra_services.*' => ['string', Rule::in(self::EXTRA_SERVICE_OPTIONS)],
            'extra_service_other' => ['nullable', 'string', 'max:40'],
            'accepts_walk_ins' => ['sometimes', 'boolean'],
            'walk_ins_note' => ['nullable', 'string', 'max:60'],
            'social_links' => ['nullable', 'array'],
            'social_links.instagram' => ['nullable', 'string', 'max:255'],
            'social_links.tiktok' => ['nullable', 'string', 'max:255'],
            'social_links.youtube' => ['nullable', 'string', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'social_links.website' => ['nullable', 'string', 'max:500'],
        ]);

        $socialInput = $validated['social_links'] ?? [];
        if (! is_array($socialInput)) {
            $socialInput = [];
        }
        $websiteRaw = trim((string) ($socialInput['website'] ?? ''));
        if ($websiteRaw !== '' && ! preg_match('#^https?://#i', $websiteRaw)) {
            $socialInput['website'] = 'https://'.$websiteRaw;
        }

        try {
            $social = SocialLinks::normalize($socialInput);
        } catch (ValidationException $e) {
            throw $e;
        }

        $styles = array_values(array_unique(array_filter(array_map(
            'trim',
            $validated['tattoo_styles'] ?? []
        ))));

        $extraServices = $this->normalizeExtraServices($request);
        $acceptsWalkIns = $request->boolean('accepts_walk_ins');
        $walkInsNote = $acceptsWalkIns
            ? (trim((string) ($validated['walk_ins_note'] ?? '')) ?: null)
            : null;

        $studio->update([
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'show_map' => $request->boolean('show_map'),
            'show_artist_styles' => $request->boolean('show_artist_styles'),
            'tattoo_styles' => $styles !== [] ? $styles : null,
            'extra_services' => $extraServices,
            'accepts_walk_ins' => $acceptsWalkIns,
            'walk_ins_note' => $walkInsNote,
            'links' => $social !== [] ? $social : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Request $request, string $pageTab): array
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $studioName = trim((string) ($studio?->name ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $username = trim((string) ($studio?->username ?? ''));
        $pageHandle = $username !== '' ? $username : 'username';
        $pageUrl = 'inkjin.com/@'.$pageHandle;
        $publicPageUrl = $username !== '' ? 'https://'.$pageUrl : '';

        $links = is_array($studio?->links) ? $studio->links : [];
        $extra = is_array($studio?->extra_services) ? $studio->extra_services : [];
        $extraServices = is_array($extra['services'] ?? null) ? $extra['services'] : [];
        $studioStyles = is_array($studio?->tattoo_styles) ? array_values(array_filter($studio->tattoo_styles)) : [];
        $artistStyles = $studio ? $this->collectArtistStyleLabels($studio) : [];

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        return [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'profileComplete' => (string) ($user->on_boarding ?? '') === 'yes',
            'activeNav' => 'page',
            'pageTab' => $pageTab,
            'pageUrl' => $pageUrl,
            'pageHandle' => $pageHandle,
            'publicPageUrl' => $publicPageUrl,
            'quickLinkRows' => $username !== '' ? [
                ['suffix' => '', 'hash' => ''],
                ['suffix' => '/artists', 'hash' => '#artists'],
                ['suffix' => '/portfolio', 'hash' => '#work'],
                ['suffix' => '/guest-artists', 'hash' => '#guest-artists'],
                ['suffix' => '/find-us', 'hash' => '#findus'],
            ] : [],
            'description' => (string) ($studio?->description ?? ''),
            'showMap' => (bool) ($studio?->show_map ?? true),
            'showArtistStyles' => (bool) ($studio?->show_artist_styles ?? true),
            'studioStyles' => $studioStyles,
            'artistStyles' => $artistStyles,
            'styleOptions' => $this->styleOptionNames(),
            'extraServiceOptions' => self::EXTRA_SERVICE_OPTIONS,
            'extraServices' => $extraServices,
            'extraServiceOther' => trim((string) ($extra['other'] ?? '')),
            'acceptsWalkIns' => (bool) ($studio?->accepts_walk_ins ?? false),
            'walkInsNote' => (string) ($studio?->walk_ins_note ?? ''),
            'instagram' => $this->displaySocialValue('instagram', $links['instagram'] ?? null),
            'website' => $this->displaySocialValue('website', $links['website'] ?? null),
            'tiktok' => $this->displaySocialValue('tiktok', $links['tiktok'] ?? null),
            'facebook' => $this->displaySocialValue('facebook', $links['facebook'] ?? null),
            'youtube' => $this->displaySocialValue('youtube', $links['youtube'] ?? null),
            'updateAboutUrl' => route('studio.page.about.update'),
        ];
    }

    /**
     * Active studio members for Studio Page → Artists ordering.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function pageArtistsForStudio(Studio $studio)
    {
        if (! UserStudio::tableReady()) {
            return collect();
        }

        $labels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];

        $query = UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE);

        if (Schema::hasColumn('user_studios', 'page_sort_order')) {
            $query->orderBy('page_sort_order')->orderBy('id');
        } else {
            $query->orderBy('id');
        }

        return $query->get()->map(function (UserStudio $link) use ($labels) {
            $artistUser = $link->user;
            $detail = $artistUser?->userDetail;
            $name = $detail ? $detail->publicDisplayName() : '';
            if ($name === '' || $name === 'Artist') {
                $fallback = trim(($artistUser?->first_name ?? '').' '.($artistUser?->last_name ?? ''));
                $name = $fallback !== '' ? $fallback : ($detail?->user_name ?? $artistUser?->user_name ?? 'Artist');
            }
            $avatar = trim((string) ($detail?->avatar ?? ''));
            $initials = $detail
                ? $detail->publicDisplayInitials()
                : strtoupper(substr(preg_replace('/\s+/', '', $name) ?: 'AR', 0, 2));

            return [
                'id' => (int) $link->id,
                'name' => $name,
                'initials' => $initials !== '' ? $initials : 'AR',
                'avatar' => $avatar !== '' ? asset(ltrim($avatar, '/')) : null,
                'relationship' => $labels[$link->relationship ?? ''] ?? '—',
                'show_on_page' => (bool) ($link->show_on_page ?? true),
            ];
        })->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function taggableArtistsForStudio(Studio $studio)
    {
        if (! UserStudio::tableReady()) {
            return collect();
        }

        return UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->orderBy('id')
            ->get()
            ->map(function (UserStudio $link) {
                $artistUser = $link->user;
                $detail = $artistUser?->userDetail;
                $name = $detail ? $detail->publicDisplayName() : '';
                if ($name === '' || $name === 'Artist') {
                    $fallback = trim(($artistUser?->first_name ?? '').' '.($artistUser?->last_name ?? ''));
                    $name = $fallback !== '' ? $fallback : ($detail?->user_name ?? 'Artist');
                }

                return [
                    'user_id' => (int) $link->user_id,
                    'name' => $name,
                ];
            })
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function artistWorkPreviewForStudio(Studio $studio)
    {
        $artists = $this->taggableArtistsForStudio($studio);
        if ($artists->isEmpty()) {
            return collect();
        }

        $byUser = $artists->keyBy('user_id');
        $userIds = $artists->pluck('user_id')->all();
        $items = collect();

        $designs = ArtistDesign::query()
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'user_id', 'title', 'image']);

        foreach ($designs as $design) {
            $name = $byUser[(int) $design->user_id]['name'] ?? 'Artist';
            $image = trim((string) ($design->image ?? ''));
            $items->push([
                'title' => (string) ($design->title ?: 'Untitled'),
                'subtitle' => 'by '.$name.' · Flash',
                'image' => $image !== '' ? asset(ltrim($image, '/')) : null,
            ]);
        }

        $portfolios = Portfolio::query()
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'user_id', 'title', 'image']);

        foreach ($portfolios as $portfolio) {
            $name = $byUser[(int) $portfolio->user_id]['name'] ?? 'Artist';
            $image = trim((string) ($portfolio->image ?? ''));
            $items->push([
                'title' => (string) ($portfolio->title ?: 'Untitled'),
                'subtitle' => 'by '.$name.' · Portfolio',
                'image' => $image !== '' ? asset(ltrim($image, '/')) : null,
            ]);
        }

        return $items->take(10)->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function studioPiecesForStudio(Studio $studio)
    {
        return StudioPortfolio::query()
            ->where('studio_id', $studio->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (StudioPortfolio $piece) {
                $image = trim((string) ($piece->image ?? ''));

                return [
                    'id' => (int) $piece->id,
                    'title' => (string) $piece->title,
                    'description' => (string) ($piece->description ?? ''),
                    'image' => $image !== '' ? asset(ltrim($image, '/')) : null,
                    'is_active' => (bool) ($piece->is_active ?? true),
                    'primary_style' => (string) ($piece->primary_style ?? ''),
                    'other_styles' => array_values(array_filter((array) ($piece->other_styles ?? []))),
                    'placement' => (string) ($piece->placement ?? ''),
                    'color' => (string) ($piece->color ?? ''),
                    'tags' => array_values(array_filter((array) ($piece->tags ?? []))),
                    'attribution' => (string) $piece->attribution,
                    'artist_user_id' => $piece->artist_user_id ? (int) $piece->artist_user_id : null,
                    'source' => (string) $piece->source,
                    'from_instagram' => $piece->source === StudioPortfolio::SOURCE_INSTAGRAM,
                    'update_url' => route('studio.page.portfolio.piece.update', $piece),
                    'delete_url' => route('studio.page.portfolio.piece.destroy', $piece),
                ];
            })
            ->values();
    }

    /**
     * @return list<string>
     */
    private function styles(): array
    {
        $fromDb = Style::query()
            ->active()
            ->ordered()
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        return $fromDb !== [] ? $fromDb : self::STYLE_FALLBACK;
    }

    /**
     * @return list<string>
     */
    private function placements(): array
    {
        return Placement::query()
            ->active()
            ->ordered()
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function studioPieceRules(bool $requireImage): array
    {
        $styleValues = $this->styles();
        $placementValues = $this->placements();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'primary_style' => ['required', 'string', Rule::in($styleValues)],
            'other_styles' => ['nullable', 'array', 'max:2'],
            'other_styles.*' => ['string', Rule::in($styleValues)],
            'placement' => ['nullable', 'string', Rule::in($placementValues)],
            'color' => ['required', 'string', Rule::in(['color', 'black-grey', 'both'])],
            'tags' => ['nullable', 'array', 'max:30'],
            'tags.*' => ['string', 'max:64'],
            'attribution' => ['nullable', 'in:pending,studio,artist'],
            'artist_user_id' => ['nullable', 'integer'],
        ];

        if ($requireImage) {
            $rules['image'] = ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
        } else {
            $rules['image'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
        }

        return $rules;
    }

    /**
     * @param  list<int>  $artistIds
     * @return array{0: string, 1: ?int}
     */
    private function normalizePieceAttribution(string $attribution, ?int $artistUserId, array $artistIds): array
    {
        if ($attribution === StudioPortfolio::ATTRIBUTION_ARTIST) {
            if (! $artistUserId || ! in_array($artistUserId, $artistIds, true)) {
                return [StudioPortfolio::ATTRIBUTION_PENDING, null];
            }

            return [StudioPortfolio::ATTRIBUTION_ARTIST, $artistUserId];
        }

        if ($attribution === StudioPortfolio::ATTRIBUTION_PENDING) {
            return [StudioPortfolio::ATTRIBUTION_PENDING, null];
        }

        return [StudioPortfolio::ATTRIBUTION_STUDIO, null];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: list<string>, 1: list<string>}
     */
    private function normalizeOtherAndTags(array $validated): array
    {
        $other = array_values(array_unique(array_filter($validated['other_styles'] ?? [])));
        $other = array_values(array_diff($other, [$validated['primary_style']]));
        $other = array_slice($other, 0, 2);
        $tags = array_values(array_unique(array_filter(array_map('trim', $validated['tags'] ?? []))));

        return [$other, $tags];
    }

    private function deleteStudioPortfolioImage(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/studio-portfolios/')) {
            return;
        }

        $full = public_path($relativePath);
        if (file_exists($full)) {
            File::delete($full);
        }
    }

    private function displaySocialValue(string $platform, mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if ($platform === 'website') {
            return (string) preg_replace('#^https?://#i', '', $value);
        }

        $hostPath = (string) preg_replace('#^https?://(www\.)?#i', '', $value);

        return rtrim($hostPath, '/');
    }

    /**
     * @return list<string>
     */
    private function styleOptionNames(): array
    {
        $fromDb = Style::query()
            ->active()
            ->ordered()
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        return $fromDb !== [] ? $fromDb : self::STYLE_FALLBACK;
    }

    /**
     * Unique style labels from active resident artists’ pages.
     *
     * @return list<string>
     */
    private function collectArtistStyleLabels(Studio $studio): array
    {
        if (! UserStudio::tableReady()) {
            return [];
        }

        $labels = [];
        $links = UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->get();

        foreach ($links as $link) {
            $ts = $link->user?->userDetail?->tattoo_styles;
            if (! is_array($ts)) {
                continue;
            }

            if (array_key_exists('primary_style', $ts) || array_key_exists('other_styles', $ts)) {
                $primary = trim((string) ($ts['primary_style'] ?? ''));
                if ($primary !== '') {
                    $labels[] = $primary;
                }
                foreach ((array) ($ts['other_styles'] ?? []) as $style) {
                    $style = trim((string) $style);
                    if ($style !== '') {
                        $labels[] = $style;
                    }
                }

                continue;
            }

            foreach ($ts as $style) {
                if (is_string($style)) {
                    $style = trim($style);
                    if ($style !== '') {
                        $labels[] = $style;
                    }
                }
            }
        }

        return array_values(array_unique($labels));
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

    /**
     * Persist a data-URL or keep/resolve an existing studio page image path.
     *
     * @return array{0: ?string, 1: ?string} [relativePath, errorMessage]
     */
    private function resolveLookImage(?string $incoming, ?string $currentPath, string $prefix): array
    {
        $incoming = is_string($incoming) ? trim($incoming) : '';

        if ($incoming === '') {
            $this->deleteLookImageIfSafe($currentPath);

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
            $this->deleteLookImageIfSafe($currentPath);

            $filename = $prefix.'_'.time().'_'.uniqid('', true).'.'.$ext;
            $destination = public_path('uploads/studio-pages');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            File::put($destination.DIRECTORY_SEPARATOR.$filename, $binary);

            return ['uploads/studio-pages/'.$filename, null];
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $assetBase = rtrim(asset('uploads/studio-pages'), '/');
        foreach ([$appUrl.'/uploads/studio-pages/', $assetBase.'/'] as $base) {
            if ($base !== '' && str_starts_with($incoming, $base)) {
                $rel = 'uploads/studio-pages/'.ltrim(substr($incoming, strlen($base)), '/');
                if (str_starts_with($rel, 'uploads/studio-pages/') && ! str_contains($rel, '..')) {
                    return [$rel, null];
                }
            }
        }

        if (str_starts_with($incoming, 'uploads/studio-pages/') && ! str_contains($incoming, '..')) {
            return [$incoming, null];
        }

        return [$currentPath, null];
    }

    private function deleteLookImageIfSafe(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/studio-pages/')) {
            return;
        }

        $full = public_path($relativePath);
        if (file_exists($full)) {
            File::delete($full);
        }
    }

    private function resolveStudio($user): ?Studio
    {
        if ($user?->id) {
            $byOwner = Studio::query()->where('user_id', $user->id)->first();
            if ($byOwner) {
                return $byOwner;
            }
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email === '') {
            return null;
        }

        return Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();
    }
}
