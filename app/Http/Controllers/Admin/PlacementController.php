<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlacementController extends Controller
{
    public function index(): View
    {
        $placements = Placement::query()->ordered()->get();

        return view('admin.placements.index', compact('placements'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:placements,name'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'appear_on_question' => ['required', 'boolean'],
        ]);

        $placement = Placement::create([
            'name' => trim($data['name']),
            'status' => $data['status'],
            'sort_order' => 0,
            'appear_on_question' => $data['appear_on_question'],
        ]);

        $placement->update(['sort_order' => $placement->id]);

        return response()->json([
            'success' => true,
            'message' => 'Placement created.',
            'placement' => $placement->fresh(),
        ], 201);
    }

    public function update(Request $request, Placement $placement): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('placements', 'name')->ignore($placement->id)],
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
            $placement->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => 'Placement updated.',
            'placement' => $placement->fresh(),
        ]);
    }

    public function destroy(Placement $placement): JsonResponse
    {
        $placement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Placement deleted.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:placements,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                Placement::query()
                    ->whereKey($item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Placement order updated.',
        ]);
    }
}
