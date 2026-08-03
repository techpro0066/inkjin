<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SizeController extends Controller
{
    public function index(): View
    {
        $sizes = Size::query()->ordered()->get();

        return view('admin.sizes.index', compact('sizes'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedPayload($request);

        $size = Size::create([
            ...$data,
            'sort_order' => 0,
        ]);

        $size->update(['sort_order' => $size->id]);

        return response()->json([
            'success' => true,
            'message' => 'Size created.',
            'size' => $this->serializeSize($size->fresh()),
        ], 201);
    }

    public function update(Request $request, Size $size): JsonResponse
    {
        // Status-only toggle from the list row.
        if ($request->has('status') && ! $request->has('label')) {
            $data = $request->validate([
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $size->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Size updated.',
                'size' => $this->serializeSize($size->fresh()),
            ]);
        }

        $data = $this->validatedPayload($request, $size);
        $size->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Size updated.',
            'size' => $this->serializeSize($size->fresh()),
        ]);
    }

    public function destroy(Size $size): JsonResponse
    {
        $size->delete();

        return response()->json([
            'success' => true,
            'message' => 'Size deleted.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:sizes,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                Size::query()
                    ->whereKey($item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Size order updated.',
        ]);
    }

    /**
     * @return array{
     *     label: string,
     *     cm_min: float|null,
     *     cm_max: float|null,
     *     in_min: float|null,
     *     in_max: float|null,
     *     status: string
     * }
     */
    private function validatedPayload(Request $request, ?Size $size = null): array
    {
        $data = $request->validate([
            'label' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sizes', 'label')->ignore($size?->id),
            ],
            'cm_min' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'cm_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'in_min' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'in_max' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'label.unique' => 'A size with this label already exists.',
        ]);

        $cmMin = $this->nullableFloat($data['cm_min'] ?? null);
        $cmMax = $this->nullableFloat($data['cm_max'] ?? null);
        $inMin = $this->nullableFloat($data['in_min'] ?? null);
        $inMax = $this->nullableFloat($data['in_max'] ?? null);

        $errors = [];

        if ($cmMin === null && $cmMax === null) {
            $errors['cm_min'] = ['Enter min cm, max cm, or both.'];
        }
        if ($inMin === null && $inMax === null) {
            $errors['in_min'] = ['Enter min in, max in, or both.'];
        }
        if ($cmMin !== null && $cmMax !== null && $cmMax < $cmMin) {
            $errors['cm_max'] = ['Max cm must be greater than or equal to min cm.'];
        }
        if ($inMin !== null && $inMax !== null && $inMax < $inMin) {
            $errors['in_max'] = ['Max in must be greater than or equal to min in.'];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'label' => trim($data['label']),
            'cm_min' => $cmMin !== null ? round($cmMin, 2) : null,
            'cm_max' => $cmMax !== null ? round($cmMax, 2) : null,
            'in_min' => $inMin !== null ? round($inMin, 2) : null,
            'in_max' => $inMax !== null ? round($inMax, 2) : null,
            'status' => $data['status'],
        ];
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSize(Size $size): array
    {
        return [
            'id' => $size->id,
            'label' => $size->label,
            'cm_min' => $size->cm_min,
            'cm_max' => $size->cm_max,
            'in_min' => $size->in_min,
            'in_max' => $size->in_max,
            'status' => $size->status,
            'sort_order' => $size->sort_order,
            'cm_range_label' => $size->cmRangeLabel(),
            'in_range_label' => $size->inRangeLabel(),
        ];
    }
}
