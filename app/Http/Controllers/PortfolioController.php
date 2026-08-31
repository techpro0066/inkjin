<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SuggestsTattooImageFieldsWithAi;
use App\Models\Placement;
use App\Models\Portfolio;
use App\Models\Style;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class PortfolioController extends Controller
{
    use SuggestsTattooImageFieldsWithAi;

    private function styles(): array
    {
        return Style::active()->ordered()->pluck('name')->values()->all();
    }

    private function placements(): array
    {
        return Placement::active()->ordered()->pluck('name')->values()->all();
    }

    private function basePortfolioRules(bool $requireImage): array
    {
        $styleValues = $this->styles();
        $placementValues = $this->placements();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'primary_style' => ['required', 'string', Rule::in($styleValues)],
            'other_styles' => ['nullable', 'array', 'max:2'],
            'other_styles.*' => ['string', Rule::in($styleValues)],
            'placement' => ['nullable', 'string', Rule::in($placementValues)],
            'color' => ['required', 'string', Rule::in(['color', 'black-grey', 'both'])],
            'tags' => ['nullable', 'array', 'max:30'],
            'tags.*' => ['string', 'max:64'],
        ];

        if ($requireImage) {
            $rules['image'] = ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
        } else {
            $rules['image'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];
        }

        return $rules;
    }

    private function normalizeOtherAndTags(array $validated): array
    {
        $other = array_values(array_unique(array_filter($validated['other_styles'] ?? [])));
        $other = array_values(array_diff($other, [$validated['primary_style']]));
        $other = array_slice($other, 0, 2);
        $tags = array_values(array_unique(array_filter(array_map('trim', $validated['tags'] ?? []))));

        return [$other, $tags];
    }

    private function assertOwnsPortfolio(Portfolio $portfolio): void
    {
        abort_unless($portfolio->user_id === Auth::id(), 403);
    }

    private function deletePublicUploadIfSafe(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/portfolios/')) {
            return;
        }
        $full = public_path($relativePath);
        if (file_exists($full)) {
            File::delete($full);
        }
    }

    private function nextSortOrder(int $userId): int
    {
        return (int) Portfolio::query()
            ->where('user_id', $userId)
            ->max('sort_order') + 1;
    }

    public function index()
    {
        $portfolios = Auth::user()
            ->portfolios()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $styles = $this->styles();
        $placements = $this->placements();
        $userDetail = Auth::user()->userDetail;
        $instagramConnected = filled($userDetail?->instagram_access_token);
        $instagramUsername = $userDetail?->instagram_username;

        if ($instagramConnected && $userDetail && blank($userDetail->instagram_profile_picture)) {
            app(InstagramController::class)->syncProfilePictureForDetail($userDetail);
            $userDetail->refresh();
        }

        $instagramProfilePictureUrl = $this->resolveInstagramProfilePictureUrl($userDetail?->instagram_profile_picture);
        $instagramImportedCount = Auth::user()->portfolios()
            ->whereNotNull('instagram_media_id')
            ->count();
        $instagramAutoImport = (bool) session()->pull('instagram_auto_import', false);

        return view('artist.portfolio.index', compact(
            'portfolios',
            'styles',
            'placements',
            'instagramConnected',
            'instagramUsername',
            'instagramProfilePictureUrl',
            'instagramImportedCount',
            'instagramAutoImport'
        ));
    }

    private function resolveInstagramProfilePictureUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->basePortfolioRules(true));

        [$other, $tags] = $this->normalizeOtherAndTags($validated);

        $file = $request->file('image');
        $filename = time().'_'.uniqid().'.'.strtolower($file->getClientOriginalExtension());
        $destination = public_path('uploads/portfolios');
        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        $file->move($destination, $filename);
        $imagePath = 'uploads/portfolios/'.$filename;
        $userId = (int) Auth::id();

        $portfolio = Portfolio::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'is_active' => $request->boolean('is_active'),
            'image' => $imagePath,
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'placement' => $validated['placement'] ?? null,
            'color' => $validated['color'],
            'tags' => $tags,
            'sort_order' => $this->nextSortOrder($userId),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work saved to your portfolio.',
            'portfolio' => [
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'image_url' => asset($portfolio->image),
            ],
        ]);
    }

    public function update(Request $request, Portfolio $portfolio)
    {
        $this->assertOwnsPortfolio($portfolio);

        $validated = $request->validate($this->basePortfolioRules(false));

        [$other, $tags] = $this->normalizeOtherAndTags($validated);

        $imagePath = $portfolio->image;
        if ($request->hasFile('image')) {
            $this->deletePublicUploadIfSafe($portfolio->image);

            $file = $request->file('image');
            $filename = time().'_'.uniqid().'.'.strtolower($file->getClientOriginalExtension());
            $destination = public_path('uploads/portfolios');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imagePath = 'uploads/portfolios/'.$filename;
        }

        $portfolio->update([
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'is_active' => $request->boolean('is_active'),
            'image' => $imagePath,
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'placement' => $validated['placement'] ?? null,
            'color' => $validated['color'],
            'tags' => $tags,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work updated.',
            'portfolio' => [
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'image_url' => asset($portfolio->image),
            ],
        ]);
    }

    public function destroy(Portfolio $portfolio)
    {
        $this->assertOwnsPortfolio($portfolio);

        $this->deletePublicUploadIfSafe($portfolio->image);
        $portfolio->delete();

        return response()->json([
            'success' => true,
            'message' => 'Portfolio piece removed.',
        ]);
    }

    public function toggleVisibility(Request $request, Portfolio $portfolio)
    {
        $this->assertOwnsPortfolio($portfolio);

        $isActive = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $portfolio->is_active;

        $portfolio->update(['is_active' => $isActive]);

        return response()->json([
            'success' => true,
            'is_active' => $portfolio->is_active,
            'message' => $portfolio->is_active
                ? 'Work is now visible on your page.'
                : 'Work is now hidden from your page.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $userId = Auth::id();
        $ids = array_values($validated['ids']);

        $ownedCount = Portfolio::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->count();

        if ($ownedCount !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some portfolio pieces were not found.',
            ], 422);
        }

        DB::transaction(function () use ($ids, $userId) {
            foreach ($ids as $index => $id) {
                Portfolio::query()
                    ->where('user_id', $userId)
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order updated.',
        ]);
    }
}
