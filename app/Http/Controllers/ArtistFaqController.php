<?php

namespace App\Http\Controllers;

use App\Models\ArtistFaq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArtistFaqController extends Controller
{
    public function index(): View
    {
        $faqs = Auth::user()
            ->artistFaqs()
            ->ordered()
            ->get();

        return view('artist.faq.index', compact('faqs'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);

        $userId = Auth::id();
        $nextOrder = (int) ArtistFaq::query()
            ->where('user_id', $userId)
            ->max('sort_order') + 1;

        $faq = ArtistFaq::create([
            'user_id' => $userId,
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'is_active' => $validated['is_active'],
            'sort_order' => $nextOrder,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'FAQ added.',
            'faq' => $this->serialize($faq),
        ], 201);
    }

    public function update(Request $request, ArtistFaq $faq): JsonResponse
    {
        $this->assertOwns($faq);

        $validated = $this->validatedPayload($request, partial: $request->has('is_active') && ! $request->has('question'));

        $updates = [];
        if (array_key_exists('question', $validated)) {
            $updates['question'] = $validated['question'];
        }
        if (array_key_exists('answer', $validated)) {
            $updates['answer'] = $validated['answer'];
        }
        if (array_key_exists('is_active', $validated)) {
            $updates['is_active'] = $validated['is_active'];
        }

        if ($updates !== []) {
            $faq->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated.',
            'faq' => $this->serialize($faq->fresh()),
        ]);
    }

    public function destroy(ArtistFaq $faq): JsonResponse
    {
        $this->assertOwns($faq);
        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ removed.',
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

        $ownedCount = ArtistFaq::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->count();

        if ($ownedCount !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some FAQs were not found.',
            ], 422);
        }

        DB::transaction(function () use ($ids, $userId) {
            foreach ($ids as $index => $id) {
                ArtistFaq::query()
                    ->where('user_id', $userId)
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'FAQ order updated.',
        ]);
    }

    private function validatedPayload(Request $request, bool $partial = false): array
    {
        if ($partial) {
            $validated = $request->validate([
                'is_active' => ['required', 'boolean'],
            ]);

            return [
                'is_active' => $request->boolean('is_active'),
            ];
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
            'is_active' => array_key_exists('is_active', $validated)
                ? $request->boolean('is_active')
                : true,
        ];
    }

    private function assertOwns(ArtistFaq $faq): void
    {
        abort_unless((int) $faq->user_id === (int) Auth::id(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ArtistFaq $faq): array
    {
        return [
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'is_active' => (bool) $faq->is_active,
            'sort_order' => (int) $faq->sort_order,
            'update_url' => route('artist.faq.update', $faq),
            'delete_url' => route('artist.faq.destroy', $faq),
        ];
    }
}
