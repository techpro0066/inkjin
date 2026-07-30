<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ClaudeDesignAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Throwable;

trait SuggestsTattooImageFieldsWithAi
{
    public function suggestWithAi(Request $request, ClaudeDesignAnalysisService $claude): JsonResponse
    {
        if (! $claude->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'AI suggestions are not configured.',
            ], 503);
        }

        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'image_data' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'primary_style' => ['nullable', 'string', 'max:255'],
            'other_styles' => ['nullable', 'array', 'max:2'],
            'other_styles.*' => ['string', 'max:255'],
            'suggested_placements' => ['nullable', 'array', 'max:3'],
            'suggested_placements.*' => ['string', 'max:255'],
            'color' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'array', 'max:30'],
            'tags.*' => ['string', 'max:64'],
        ]);

        if (! $request->hasFile('image') && blank($validated['image_data'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'An image is required for AI suggestions.',
            ], 422);
        }

        $fieldsNeeded = $this->emptyAiFields($validated);
        if ($fieldsNeeded === []) {
            return response()->json([
                'success' => true,
                'message' => 'Nothing to fill — those fields already have values.',
                'suggestions' => (object) [],
            ]);
        }

        $allowedPlacements = method_exists($this, 'placements') ? $this->placements() : [];

        try {
            [$binary, $mediaType] = $this->resolveAiImagePayload($request, $validated['image_data'] ?? null);
            $suggestions = $claude->suggestFields(
                $binary,
                $mediaType,
                $this->styles(),
                $fieldsNeeded,
                $allowedPlacements
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Unable to analyze the image right now.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => $suggestions === []
                ? 'AI could not suggest values for the empty fields.'
                : 'AI suggestions ready.',
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    private function emptyAiFields(array $input): array
    {
        $needed = [];

        if (trim((string) ($input['title'] ?? '')) === '') {
            $needed[] = 'title';
        }
        if (trim((string) ($input['description'] ?? '')) === '') {
            $needed[] = 'description';
        }
        if (trim((string) ($input['primary_style'] ?? '')) === '') {
            $needed[] = 'primary_style';
        }

        $other = array_values(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $input['other_styles'] ?? []
        )));
        if ($other === []) {
            $needed[] = 'other_styles';
        }

        $allowedPlacements = method_exists($this, 'placements') ? $this->placements() : [];
        if ($allowedPlacements !== []) {
            $placements = array_values(array_filter(array_map(
                fn ($v) => trim((string) $v),
                $input['suggested_placements'] ?? []
            )));
            if ($placements === []) {
                $needed[] = 'suggested_placements';
            }
        }

        if (trim((string) ($input['color'] ?? '')) === '') {
            $needed[] = 'color';
        }

        $tags = array_values(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $input['tags'] ?? []
        )));
        if ($tags === []) {
            $needed[] = 'tags';
        }

        return $needed;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveAiImagePayload(Request $request, ?string $imageData): array
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $binary = File::get($file->getRealPath());
            $mime = $file->getMimeType() ?: 'image/jpeg';

            return [$binary, $mime];
        }

        $imageData = trim((string) $imageData);
        if (! preg_match('#^data:(image/(?:jpeg|jpg|png|webp|gif));base64,#i', $imageData, $matches)) {
            throw ValidationException::withMessages([
                'image_data' => ['The image data must be a valid base64 image data URL.'],
            ]);
        }

        $mediaType = strtolower($matches[1]);
        if ($mediaType === 'image/jpg') {
            $mediaType = 'image/jpeg';
        }

        $base64 = substr($imageData, strpos($imageData, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([
                'image_data' => ['Could not decode the image data.'],
            ]);
        }

        return [$binary, $mediaType];
    }
}
