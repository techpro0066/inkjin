<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class PortfolioController extends Controller
{
    private function styleSlugRules(): array
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

    private function basePortfolioRules(bool $requireImage): array
    {
        $styleValues = $this->styleSlugRules();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
            'primary_style' => ['required', 'string', Rule::in($styleValues)],
            'other_styles' => ['nullable', 'array', 'max:2'],
            'other_styles.*' => ['string', Rule::in($styleValues)],
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

    public function index()
    {
        $portfolios = Auth::user()->portfolios()->latest()->get();

        return view('artist.portfolio.index', compact('portfolios'));
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

        $portfolio = Portfolio::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'is_active' => $request->boolean('is_active'),
            'image' => $imagePath,
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
            'color' => $validated['color'],
            'tags' => $tags,
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
            'description' => $validated['description'],
            'is_active' => $request->boolean('is_active'),
            'image' => $imagePath,
            'primary_style' => $validated['primary_style'],
            'other_styles' => $other,
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
}
