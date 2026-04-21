<?php

namespace App\Http\Controllers;

use App\Models\ArtistDesign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

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
        return ['30min', '1h', '2h', '3h', '4h'];
    }

    private function designRules(bool $requireImage): array
    {
        $styles = $this->styleSlugs();
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_visible' => ['required', 'boolean'],
            'is_repeatable' => ['required', 'boolean'],
            'is_sensitive' => ['required', 'boolean'],
            'primary_style' => ['required', 'string', Rule::in($styles)],
            'other_styles' => ['nullable', 'array', 'max:2'],
            'other_styles.*' => ['string', Rule::in($styles)],
            'color' => ['required', 'string', Rule::in(['color', 'black-grey', 'both'])],
            'tags' => ['nullable', 'array', 'max:30'],
            'tags.*' => ['string', 'max:64'],
            'min_price' => ['required', 'integer', 'min:0'],
            'max_price' => ['required', 'integer', 'min:0', 'gte:min_price'],
            'min_size' => ['required', 'integer', 'min:1'],
            'max_size' => ['required', 'integer', 'min:1', 'gte:min_size'],
            'min_sessions' => ['required', 'integer', 'min:1'],
            'max_sessions' => ['required', 'integer', 'min:1', 'gte:min_sessions'],
            'session_duration' => ['required', 'string', Rule::in($this->sessionDurations())],
        ];
        $rules['image'] = $requireImage
            ? ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240']
            : ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'];

        return $rules;
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

    public function index()
    {
        $artistDesigns = Auth::user()->artistDesigns()->latest()->get();

        return view('artist.artist_designs.index', compact('artistDesigns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->designRules(true));
        [$other, $tags] = $this->normalizeArrays($validated);
        $imagePath = $this->storeUploadedImage($request);

        ArtistDesign::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
            'is_visible' => $request->boolean('is_visible'),
            'is_repeatable' => $request->boolean('is_repeatable'),
            'is_sensitive' => $request->boolean('is_sensitive'),
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'color' => $validated['color'],
            'tags' => $tags,
            'min_price' => $validated['min_price'],
            'max_price' => $validated['max_price'],
            'min_size' => $validated['min_size'],
            'max_size' => $validated['max_size'],
            'min_sessions' => $validated['min_sessions'],
            'max_sessions' => $validated['max_sessions'],
            'session_duration' => $validated['session_duration'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Design saved.',
        ]);
    }

    public function update(Request $request, ArtistDesign $artistDesign)
    {
        $this->assertOwns($artistDesign);
        $validated = $request->validate($this->designRules(false));
        [$other, $tags] = $this->normalizeArrays($validated);

        $imagePath = $artistDesign->image;
        if ($request->hasFile('image')) {
            $this->deleteUploadIfSafe($artistDesign->image);
            $imagePath = $this->storeUploadedImage($request);
        }

        $artistDesign->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
            'is_visible' => $request->boolean('is_visible'),
            'is_repeatable' => $request->boolean('is_repeatable'),
            'is_sensitive' => $request->boolean('is_sensitive'),
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'color' => $validated['color'],
            'tags' => $tags,
            'min_price' => $validated['min_price'],
            'max_price' => $validated['max_price'],
            'min_size' => $validated['min_size'],
            'max_size' => $validated['max_size'],
            'min_sessions' => $validated['min_sessions'],
            'max_sessions' => $validated['max_sessions'],
            'session_duration' => $validated['session_duration'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Design updated.',
        ]);
    }

    public function destroy(ArtistDesign $artistDesign)
    {
        $this->assertOwns($artistDesign);
        $this->deleteUploadIfSafe($artistDesign->image);
        $artistDesign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Design removed.',
        ]);
    }
}
