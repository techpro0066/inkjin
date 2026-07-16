<?php

namespace App\Http\Controllers;

use App\Models\ArtistDesign;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Style;
use Illuminate\Support\Str;

class ArtistDesignsController extends Controller
{
    private function styleSlugs(): array
    {
        return [
            'japanese',
            'traditional',
            'neo-traditional',
            'realism',
            'fine-line',
            'blackwork',
            'geometric',
            'watercolor',
            'tribal',
            'surrealism',
            'minimalist',
            'dotwork',
        ];
    }

    private function sessionDurations(): array
    {
        return ['30min', '1h', '2h', '3h', '4h', '6h', '8h'];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function normalizedSessionCounts(Request $request): array
    {
        $maxRaw = $request->input('max_sessions');
        $max = ($maxRaw === null || $maxRaw === '') ? 1 : (int) $maxRaw;

        return [1, max(1, $max)];
    }

    private function designRules(Request $request, bool $requireImage): array
    {
        $styles = $this->styleSlugs();
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_visible' => ['required', 'boolean'],
            'is_repeatable' => ['required', 'boolean'],
            'repeat_limit' => [
                Rule::requiredIf(fn () => $request->boolean('is_repeatable')),
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],
            'is_sensitive' => ['required', 'boolean'],
            'primary_style' => ['required', 'string', Rule::in($styles)],
            'other_styles' => ['nullable', 'array', 'max:2'],
            'other_styles.*' => ['string', Rule::in($styles)],
            'color' => ['required', 'string', Rule::in(['color', 'black-grey', 'both'])],
            'tags' => ['nullable', 'array', 'max:30'],
            'tags.*' => ['string', 'max:64'],
            'min_price' => ['required', 'integer', 'min:0'],
            'max_price' => ['required', 'integer', 'min:0', 'gte:min_price'],
            // min_size = width (cm), max_size = height (cm)
            'min_size' => ['nullable', 'required_without:max_size', 'integer', 'min:1'],
            'max_size' => ['nullable', 'required_without:min_size', 'integer', 'min:1'],
            'max_sessions' => ['nullable', 'integer', 'min:1'],
            'session_duration' => ['required', 'string', Rule::in($this->sessionDurations())],
        ];
        $rules['image'] = $requireImage
            ? ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240']
            : ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];

        return $rules;
    }

    private function normalizeSizeInputs(Request $request): void
    {
        $request->merge([
            'min_size' => $request->filled('min_size') ? $request->input('min_size') : null,
            'max_size' => $request->filled('max_size') ? $request->input('max_size') : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function designValidationMessages(): array
    {
        $sizeMessage = 'Enter the width or height. At least one is required. You can enter both if you\'d like.';

        return [
            'min_size.required_without' => $sizeMessage,
            'max_size.required_without' => $sizeMessage,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: int|null, 1: int|null}
     */
    private function normalizedSizeDimensions(array $validated): array
    {
        $width = $validated['min_size'] ?? null;
        $height = $validated['max_size'] ?? null;

        return [
            $width !== null && $width !== '' ? (int) $width : null,
            $height !== null && $height !== '' ? (int) $height : null,
        ];
    }

    private function normalizeArrays(array $validated): array
    {
        $primary = $validated['primary_style'];
        $other = array_values(array_unique(array_filter($validated['other_styles'] ?? [])));
        $other = array_values(array_diff($other, [$primary]));
        $other = array_slice($other, 0, 2);
        $tags = array_values(array_unique(array_filter(array_map('trim', $validated['tags'] ?? []))));

        return [$other, $tags];
    }

    private function assertOwns(ArtistDesign $artistDesign): void
    {
        abort_unless($artistDesign->user_id === Auth::id(), 403);
    }

    private function deleteUploadIfSafe(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/artist-designs/')) {
            return;
        }
        $full = public_path($relativePath);
        if (file_exists($full)) {
            File::delete($full);
        }
    }

    private function storeUploadedImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = time().'_'.uniqid().'.'.strtolower($file->getClientOriginalExtension());
        $destination = public_path('uploads/artist-designs');
        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        $file->move($destination, $filename);

        return 'uploads/artist-designs/'.$filename;
    }

    private function normalizedRepeatLimit(Request $request, ?ArtistDesign $existing = null): ?int
    {
        if (! $request->boolean('is_repeatable')) {
            return null;
        }

        $limit = max(1, (int) $request->input('repeat_limit'));

        if ($existing && $limit < $existing->claimedBookingCount()) {
            throw ValidationException::withMessages([
                'repeat_limit' => [
                    'Repeat limit cannot be lower than the number of bookings already claimed ('.$existing->claimedBookingCount().').',
                ],
            ]);
        }

        return $limit;
    }

    public function index()
    {
        $userDetail = Auth::user()->userDetail;

        $artistDesigns = Auth::user()->artistDesigns()
            ->withCount(['bookingRequests', 'bookings'])
            ->withSoldOutState()
            ->latest()
            ->get();

        $whatsIncludedItems = is_array($userDetail?->design_whats_included)
            ? array_values($userDetail->design_whats_included)
            : [];
        
        $styles = Style::where('status', 'active')->orderBy('sort_order')->get()->pluck('name');

        return view('artist.artist_designs.index', [
            'artistDesigns' => $artistDesigns,
            'whatsIncludedIsActive' => (bool) ($userDetail?->design_whats_included_is_active ?? false),
            'whatsIncludedItems' => $whatsIncludedItems,
            'styles' => $styles,
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizeSizeInputs($request);
        $validated = $request->validate($this->designRules($request, true), $this->designValidationMessages());
        [$other, $tags] = $this->normalizeArrays($validated);
        [$minSessions, $maxSessions] = $this->normalizedSessionCounts($request);
        [$width, $height] = $this->normalizedSizeDimensions($validated);
        $imagePath = $this->storeUploadedImage($request);
        $repeatLimit = $this->normalizedRepeatLimit($request);

        ArtistDesign::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
            'is_visible' => $request->boolean('is_visible'),
            'is_repeatable' => $request->boolean('is_repeatable'),
            'repeat_limit' => $repeatLimit,
            'is_sensitive' => $request->boolean('is_sensitive'),
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'color' => $validated['color'],
            'tags' => $tags,
            'min_price' => $validated['min_price'],
            'max_price' => $validated['max_price'],
            'min_size' => $width,
            'max_size' => $height,
            'min_sessions' => $minSessions,
            'max_sessions' => $maxSessions,
            'session_duration' => $validated['session_duration'],
            'slug' => Str::slug($validated['title']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Design saved.',
        ]);
    }

    public function update(Request $request, ArtistDesign $artistDesign)
    {
        $this->assertOwns($artistDesign);
        $this->normalizeSizeInputs($request);
        $validated = $request->validate($this->designRules($request, false), $this->designValidationMessages());
        [$other, $tags] = $this->normalizeArrays($validated);
        [$minSessions, $maxSessions] = $this->normalizedSessionCounts($request);
        [$width, $height] = $this->normalizedSizeDimensions($validated);
        $repeatLimit = $this->normalizedRepeatLimit($request, $artistDesign);

        $imagePath = $artistDesign->image;
        if ($request->hasFile('image')) {
            $this->deleteUploadIfSafe($artistDesign->image);
            $imagePath = $this->storeUploadedImage($request);
        }

        $artistDesign->update([
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')),
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
            'is_visible' => $request->boolean('is_visible'),
            'is_repeatable' => $request->boolean('is_repeatable'),
            'repeat_limit' => $repeatLimit,
            'is_sensitive' => $request->boolean('is_sensitive'),
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'color' => $validated['color'],
            'tags' => $tags,
            'min_price' => $validated['min_price'],
            'max_price' => $validated['max_price'],
            'min_size' => $width,
            'max_size' => $height,
            'min_sessions' => $minSessions,
            'max_sessions' => $maxSessions,
            'session_duration' => $validated['session_duration'],
            'slug' => Str::slug($validated['title']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Design updated.',
        ]);
    }

    public function updateWhatsIncluded(Request $request)
    {
        $userDetail = Auth::user()->userDetail ?? UserDetail::create(['user_id' => Auth::id()]);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'items' => ['sometimes', 'nullable', 'array', 'max:8'],
            'items.*' => ['string', 'max:255'],
        ]);

        $update = [
            'design_whats_included_is_active' => $request->boolean('is_active'),
        ];

        if ($request->has('items')) {
            $update['design_whats_included'] = array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                $validated['items'] ?? []
            ), fn (string $item) => $item !== ''));
        }

        $userDetail->update($update);

        return response()->json([
            'success' => true,
            'message' => $request->has('items') ? 'What\'s included saved.' : 'Visibility updated.',
            'is_active' => (bool) $userDetail->design_whats_included_is_active,
            'items' => is_array($userDetail->design_whats_included)
                ? array_values($userDetail->design_whats_included)
                : [],
        ]);
    }

    public function toggleAvailability(Request $request, ArtistDesign $artistDesign)
    {
        $this->assertOwns($artistDesign);

        $isActive = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $artistDesign->is_active;

        $artistDesign->update(['is_active' => $isActive]);

        return response()->json([
            'success' => true,
            'is_active' => $artistDesign->is_active,
            'message' => $artistDesign->is_active
                ? 'Design is now available for booking.'
                : 'Design is now unavailable on your page.',
        ]);
    }

    public function toggleVisibility(Request $request, ArtistDesign $artistDesign)
    {
        $this->assertOwns($artistDesign);

        $isVisible = $request->has('is_visible')
            ? $request->boolean('is_visible')
            : ! $artistDesign->is_visible;

        $artistDesign->update(['is_visible' => $isVisible]);

        return response()->json([
            'success' => true,
            'is_visible' => $artistDesign->is_visible,
            'message' => $artistDesign->is_visible
                ? 'Design is now visible on your page.'
                : 'Design is now hidden from your page.',
        ]);
    }

    public function destroy(ArtistDesign $artistDesign)
    {
        $this->assertOwns($artistDesign);

        if (! $artistDesign->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'This design has bookings or requests linked to it. Mark it unavailable instead of deleting.',
            ], 422);
        }

        $this->deleteUploadIfSafe($artistDesign->image);
        $artistDesign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Design removed.',
        ]);
    }
}
