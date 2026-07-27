<?php

namespace App\Http\Controllers;

use App\Models\GuestSpot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuestSpotsController extends Controller
{
    public function index(): View
    {
        $guestSpots = Auth::user()
            ->guestSpots()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('artist.guest-spots.index', compact('guestSpots'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPayload($request);

        $userId = Auth::id();
        $nextOrder = (int) GuestSpot::query()
            ->where('user_id', $userId)
            ->max('sort_order') + 1;

        $guestSpot = GuestSpot::create([
            'user_id' => $userId,
            'status' => $validated['status'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'sort_order' => $nextOrder,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guest location added.',
            'guest_spot' => $this->serialize($guestSpot),
        ]);
    }

    public function update(Request $request, GuestSpot $guestSpot): JsonResponse
    {
        $this->assertOwns($guestSpot);

        $validated = $this->validatedPayload($request);

        $guestSpot->update([
            'status' => $validated['status'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guest location updated.',
            'guest_spot' => $this->serialize($guestSpot->fresh()),
        ]);
    }

    public function destroy(GuestSpot $guestSpot): JsonResponse
    {
        $this->assertOwns($guestSpot);
        $guestSpot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guest location removed.',
        ]);
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['available', 'planned'])],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ], [
            'to_date.after_or_equal' => 'The end date must be on or after the start date.',
        ]);

        $validated['city'] = trim($validated['city']);
        $validated['country'] = trim($validated['country']);

        return $validated;
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $userId = Auth::id();
        $ids = array_values($validated['ids']);

        $ownedCount = GuestSpot::query()
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->count();

        if ($ownedCount !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some guest locations were not found.',
            ], 422);
        }

        DB::transaction(function () use ($ids, $userId) {
            foreach ($ids as $index => $id) {
                GuestSpot::query()
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

    private function assertOwns(GuestSpot $guestSpot): void
    {
        abort_unless($guestSpot->user_id === Auth::id(), 403);
    }

    private function serialize(GuestSpot $guestSpot): array
    {
        return [
            'id' => $guestSpot->id,
            'status' => $guestSpot->status,
            'city' => $guestSpot->city,
            'country' => $guestSpot->country,
            'from_date' => $guestSpot->from_date->format('Y-m-d'),
            'from_label' => $guestSpot->from_date->format('M j, Y'),
            'to_date' => $guestSpot->to_date->format('Y-m-d'),
            'to_label' => $guestSpot->to_date->format('M j, Y'),
            'sort_order' => $guestSpot->sort_order,
        ];
    }
}
