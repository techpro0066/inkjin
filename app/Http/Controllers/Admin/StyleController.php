<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Style;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StyleController extends Controller
{
    public function index(): View
    {
        $styles = Style::query()->ordered()->get();

        return view('admin.styles.index', compact('styles'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:styles,name'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'appear_on_question' => ['required', 'boolean'],
        ]);

        $style = Style::create([
            'name' => trim($data['name']),
            'status' => $data['status'],
            'sort_order' => 0,
            'appear_on_question' => $data['appear_on_question'],
        ]);

        $style->update(['sort_order' => $style->id]);

        return response()->json([
            'success' => true,
            'message' => 'Style created.',
            'style' => $style->fresh(),
        ], 201);
    }

    public function update(Request $request, Style $style): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('styles', 'name')->ignore($style->id)],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'appear_on_question' => ['sometimes', 'required', 'boolean'],
        ]);

        $updates = [];

        if (array_key_exists('name', $data)) {
            $updates['name'] = trim($data['name']);
        }
        if (array_key_exists('status', $data)) {
            $updates['status'] = $data['status'];
        }
        if (array_key_exists('appear_on_question', $data)) {
            $updates['appear_on_question'] = $data['appear_on_question'];
        }

        if ($updates !== []) {
            $style->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => 'Style updated.',
            'style' => $style->fresh(),
        ]);
    }

    public function destroy(Style $style): JsonResponse
    {
        $style->delete();

        return response()->json([
            'success' => true,
            'message' => 'Style deleted.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:styles,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                Style::query()
                    ->whereKey($item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Style order updated.',
        ]);
    }
}
