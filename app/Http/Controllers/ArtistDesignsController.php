<?php

namespace App\Http\Controllers;

use App\Models\ArtistDesign;
use App\Models\SmartPricingSize;
use App\Models\UserDetail;
use App\Http\Controllers\Concerns\SuggestsTattooImageFieldsWithAi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Style;
use App\Models\Placement;
use Illuminate\Support\Str;

class ArtistDesignsController extends Controller
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
            'primary_style' => ['required', 'string', Rule::in($this->styles())],
            'other_styles' => ['nullable', 'array', 'max:2'],
            'other_styles.*' => ['string', Rule::in($this->styles())],
            'suggested_placements' => ['nullable', 'array', 'max:3'],
            'suggested_placements.*' => ['string', Rule::in($this->placements())],
            'color' => ['required', 'string', Rule::in(['color', 'black-grey', 'both'])],
            'tags' => ['nullable', 'array', 'max:30'],
            'tags.*' => ['string', 'max:64'],
            'min_price' => ['required', 'integer', 'min:0'],
            'max_price' => ['required', 'integer', 'min:0', 'gte:min_price'],
            'min_size' => ['required', 'integer', 'min:1'],
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
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function designValidationMessages(): array
    {
        return [
            'min_size.required' => 'You need to enter the minimum size',
            'min_size.min' => 'You need to enter the minimum size',
            'min_size.integer' => 'You need to enter the minimum size',
        ];
    }

    private function normalizedMinSize(array $validated): int
    {
        return max(1, (int) ($validated['min_size'] ?? 0));
    }

    private function normalizeArrays(array $validated): array
    {
        $primary = $validated['primary_style'];
        $other = array_values(array_unique(array_filter($validated['other_styles'] ?? [])));
        $other = array_values(array_diff($other, [$primary]));
        $other = array_slice($other, 0, 2);
        $tags = array_values(array_unique(array_filter(array_map('trim', $validated['tags'] ?? []))));
        $placements = array_values(array_unique(array_filter(array_map('trim', $validated['suggested_placements'] ?? []))));
        $placements = array_slice($placements, 0, 3);

        return [$other, $tags, $placements];
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
        
        $styles = $this->styles();
        $placements = $this->placements();

        $smartPricingRanges = Auth::user()->smartPricingSizes()
            ->get(['kind', 'size_min', 'size_max', 'min_price', 'max_price', 'sessions', 'duration', 'sort_order'])
            ->map(fn (SmartPricingSize $row) => [
                'kind' => $row->kind,
                'size_min' => $row->size_min,
                'size_max' => $row->size_max,
                'min_price' => $row->min_price,
                'max_price' => $row->max_price,
                'sessions' => $row->sessions,
                'duration' => $row->duration,
            ])
            ->values()
            ->all();

        return view('artist.artist_designs.index', [
            'artistDesigns' => $artistDesigns,
            'whatsIncludedIsActive' => (bool) ($userDetail?->design_whats_included_is_active ?? false),
            'whatsIncludedItems' => $whatsIncludedItems,
            'styles' => $styles,
            'placements' => $placements,
            'sizeUnit' => in_array(($userDetail?->size_unit ?? 'cm'), ['cm', 'in'], true)
                ? ($userDetail->size_unit ?? 'cm')
                : 'cm',
            'currencyCode' => strtoupper((string) ($userDetail?->currency ?? 'EUR')),
            'pricingType' => in_array(($userDetail?->pricing_type ?? 'manual'), ['manual', 'smart'], true)
                ? ($userDetail->pricing_type ?? 'manual')
                : 'manual',
            'smartPricingColorPercent' => $userDetail?->color_percent !== null
                ? (float) $userDetail->color_percent
                : 20.0,
            'smartPricingRanges' => $smartPricingRanges,
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizeSizeInputs($request);
        $validated = $request->validate($this->designRules($request, true), $this->designValidationMessages());
        [$other, $tags, $placements] = $this->normalizeArrays($validated);
        [$minSessions, $maxSessions] = $this->normalizedSessionCounts($request);
        $minSize = $this->normalizedMinSize($validated);
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
            'suggested_placements' => $placements,
            'color' => $validated['color'],
            'tags' => $tags,
            'min_price' => $validated['min_price'],
            'max_price' => $validated['max_price'],
            'min_size' => $minSize,
            'max_size' => null,
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
        [$other, $tags, $placements] = $this->normalizeArrays($validated);
        [$minSessions, $maxSessions] = $this->normalizedSessionCounts($request);
        $minSize = $this->normalizedMinSize($validated);
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
            'suggested_placements' => $placements,
            'color' => $validated['color'],
            'tags' => $tags,
            'min_price' => $validated['min_price'],
            'max_price' => $validated['max_price'],
            'min_size' => $minSize,
            'max_size' => null,
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

    public function updatePricingType(Request $request)
    {
        $userDetail = Auth::user()->userDetail ?? UserDetail::create(['user_id' => Auth::id()]);

        $validated = $request->validate([
            'pricing_type' => ['required', Rule::in(['manual', 'smart'])],
        ]);

        $userDetail->update([
            'pricing_type' => $validated['pricing_type'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pricing type updated.',
            'pricing_type' => $userDetail->pricing_type,
        ]);
    }

    public function validateSmartPricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'color_percent' => ['required', 'numeric', 'min:0', 'max:999'],
            'ranges' => ['nullable', 'array'],
            'ranges.*.kind' => ['required', Rule::in(['between', 'less_than', 'more_than'])],
            'ranges.*.size_min' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'ranges.*.size_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'ranges.*.min_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'ranges.*.max_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'ranges.*.sessions' => ['required', 'string', 'max:50'],
            'ranges.*.duration' => ['required', 'numeric', 'min:0', 'max:999'],
        ], [
            'color_percent.required' => 'Color percentage is required.',
            'color_percent.numeric' => 'Color percentage must be a number.',
            'ranges.*.size_min.numeric' => 'Min size must be a number.',
            'ranges.*.size_max.numeric' => 'Max size must be a number.',
            'ranges.*.min_price.required' => 'Min price is required.',
            'ranges.*.max_price.required' => 'Max price is required.',
            'ranges.*.sessions.required' => 'Sessions is required.',
            'ranges.*.duration.required' => 'Duration is required.',
        ]);

        $validator->after(function ($validator) {
            $ranges = $validator->getData()['ranges'] ?? [];
            if (! is_array($ranges)) {
                return;
            }

            $intervals = [];

            foreach ($ranges as $index => $range) {
                if (! is_array($range)) {
                    continue;
                }

                $kind = $range['kind'] ?? null;
                $sizeMin = array_key_exists('size_min', $range) && $range['size_min'] !== null && $range['size_min'] !== ''
                    ? $range['size_min']
                    : null;
                $sizeMax = array_key_exists('size_max', $range) && $range['size_max'] !== null && $range['size_max'] !== ''
                    ? $range['size_max']
                    : null;

                $sizeMinNumber = is_numeric($sizeMin) ? (float) $sizeMin : null;
                $sizeMaxNumber = is_numeric($sizeMax) ? (float) $sizeMax : null;

                if ($kind === 'between') {
                    if ($sizeMin === null) {
                        $validator->errors()->add("ranges.$index.size_min", 'Min size is required.');
                    }
                    if ($sizeMax === null) {
                        $validator->errors()->add("ranges.$index.size_max", 'Max size is required.');
                    }
                    if ($sizeMinNumber !== null && $sizeMaxNumber !== null && $sizeMaxNumber < $sizeMinNumber) {
                        $validator->errors()->add("ranges.$index.size_max", 'Max size must be greater than or equal to min size.');
                    }
                } elseif ($kind === 'less_than') {
                    if ($sizeMax === null) {
                        $validator->errors()->add("ranges.$index.size_max", 'Max size is required for a less-than range.');
                    }
                } elseif ($kind === 'more_than') {
                    if ($sizeMin === null) {
                        $validator->errors()->add("ranges.$index.size_min", 'This field is required.');
                    }
                }

                $minPrice = $range['min_price'] ?? null;
                $maxPrice = $range['max_price'] ?? null;
                if (is_numeric($minPrice) && is_numeric($maxPrice) && (float) $maxPrice < (float) $minPrice) {
                    $validator->errors()->add("ranges.$index.max_price", 'Max price must be greater than or equal to min price.');
                }

                $interval = $this->smartPricingSizeInterval($kind, $sizeMinNumber, $sizeMaxNumber);
                if ($interval !== null) {
                    $intervals[] = [
                        'index' => (int) $index,
                        'kind' => $kind,
                        'low' => $interval[0],
                        'high' => $interval[1],
                    ];
                }
            }

            $overlapMessage = 'This size range overlaps another range.';
            $count = count($intervals);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $intervals[$i];
                    $b = $intervals[$j];

                    // Inclusive ranges; shared edge (e.g. 0–5 and 5–10) is allowed.
                    if (! ($a['low'] < $b['high'] && $b['low'] < $a['high'])) {
                        continue;
                    }

                    $this->addSmartPricingSizeOverlapError($validator, $a['index'], $a['kind'], $overlapMessage);
                    $this->addSmartPricingSizeOverlapError($validator, $b['index'], $b['kind'], $overlapMessage);
                }
            }
        });

        $validated = $validator->validate();

        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $ranges = array_values($validated['ranges'] ?? []);

        DB::transaction(function () use ($user, $userDetail, $validated, $ranges) {
            $userDetail->update([
                'color_percent' => $validated['color_percent'],
            ]);

            SmartPricingSize::query()
                ->where('user_id', $user->id)
                ->delete();

            foreach ($ranges as $index => $range) {
                $kind = $range['kind'];
                $sizeMin = array_key_exists('size_min', $range) && $range['size_min'] !== null && $range['size_min'] !== ''
                    ? $range['size_min']
                    : null;
                $sizeMax = array_key_exists('size_max', $range) && $range['size_max'] !== null && $range['size_max'] !== ''
                    ? $range['size_max']
                    : null;

                if ($kind === 'less_than') {
                    $sizeMin = null;
                } elseif ($kind === 'more_than') {
                    $sizeMax = null;
                }

                SmartPricingSize::create([
                    'user_id' => $user->id,
                    'kind' => $kind,
                    'size_min' => $sizeMin,
                    'size_max' => $sizeMax,
                    'min_price' => $range['min_price'],
                    'max_price' => $range['max_price'],
                    'sessions' => trim((string) $range['sessions']),
                    'duration' => $range['duration'],
                    'sort_order' => $index,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Smart pricing saved.',
            'color_percent' => (float) $userDetail->fresh()->color_percent,
            'ranges' => $user->smartPricingSizes()
                ->get(['kind', 'size_min', 'size_max', 'min_price', 'max_price', 'sessions', 'duration'])
                ->map(fn (SmartPricingSize $row) => [
                    'kind' => $row->kind,
                    'size_min' => $row->size_min,
                    'size_max' => $row->size_max,
                    'min_price' => $row->min_price,
                    'max_price' => $row->max_price,
                    'sessions' => $row->sessions,
                    'duration' => $row->duration,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function smartPricingSizeInterval(?string $kind, ?float $sizeMin, ?float $sizeMax): ?array
    {
        return match ($kind) {
            'between' => ($sizeMin !== null && $sizeMax !== null && $sizeMax >= $sizeMin)
                ? [$sizeMin, $sizeMax]
                : null,
            'less_than' => $sizeMax !== null
                ? [0.0, $sizeMax]
                : null,
            'more_than' => $sizeMin !== null
                ? [$sizeMin, INF]
                : null,
            default => null,
        };
    }

    private function addSmartPricingSizeOverlapError($validator, int $index, ?string $kind, string $message): void
    {
        $field = $kind === 'less_than' ? 'size_max' : 'size_min';
        $key = "ranges.$index.$field";

        if ($validator->errors()->has($key)) {
            return;
        }

        $validator->errors()->add($key, $message);
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
