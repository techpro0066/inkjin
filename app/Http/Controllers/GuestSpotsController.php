<?php

namespace App\Http\Controllers;

use App\Models\GuestSpot;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $this->assertNoOverlappingGuestSpots($validated);

        $userId = Auth::id();
        $nextOrder = (int) GuestSpot::query()
            ->where('user_id', $userId)
            ->max('sort_order') + 1;

        $guestSpot = GuestSpot::create(array_merge(
            $this->attributesFromPayload($validated, null),
            [
                'user_id' => $userId,
                'sort_order' => $nextOrder,
            ]
        ));

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
        $this->assertNoOverlappingGuestSpots($validated, $guestSpot->id);

        $guestSpot->update($this->attributesFromPayload($validated, $guestSpot));

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

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request): array
    {
        $status = (string) $request->input('status', 'available');
        $isAvailable = $status === 'available';

        $rules = [
            'status' => ['required', 'string', Rule::in(['available', 'planned'])],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'start_time' => [Rule::requiredIf($isAvailable), 'nullable', 'date_format:H:i'],
            'end_time' => [Rule::requiredIf($isAvailable), 'nullable', 'date_format:H:i', 'after:start_time'],
            'response_deadline' => [Rule::requiredIf($isAvailable), 'nullable', 'integer', 'min:1'],
            'response_deadline_unit' => [Rule::requiredIf($isAvailable), 'nullable', 'string', Rule::in(['hours', 'days'])],
            'buffer_days_before' => [Rule::requiredIf($isAvailable), 'nullable', 'integer', 'min:0'],
            'buffer_days_after' => [Rule::requiredIf($isAvailable), 'nullable', 'integer', 'min:0'],
            'number_of_spots' => [Rule::requiredIf($isAvailable), 'nullable', 'integer', 'min:0'],
            'guest_studio_name' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_studio_address' => [Rule::requiredIf($isAvailable), 'nullable', 'string'],
            'guest_street_number' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:50'],
            'guest_street_name' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_studio_city' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_studio_state' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_postal_code' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:50'],
            'guest_studio_country' => [Rule::requiredIf($isAvailable), 'nullable', 'string', 'max:255'],
            'guest_google_maps_link' => ['nullable', 'url', 'max:500'],
            'guest_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'guest_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];

        $validated = Validator::make($request->all(), $rules, [
            'to_date.after_or_equal' => 'The end date must be on or after the start date.',
            'end_time.after' => 'The ending time must be after the starting time.',
        ])->validate();

        $validated['city'] = trim($validated['city']);
        $validated['country'] = trim($validated['country']);

        if ($isAvailable) {
            $validated['guest_studio_name'] = trim((string) ($validated['guest_studio_name'] ?? ''));
            $validated['guest_studio_address'] = trim((string) ($validated['guest_studio_address'] ?? ''));
            $validated['guest_street_number'] = trim((string) ($validated['guest_street_number'] ?? ''));
            $validated['guest_street_name'] = trim((string) ($validated['guest_street_name'] ?? ''));
            $validated['guest_studio_city'] = trim((string) ($validated['guest_studio_city'] ?? ''));
            $validated['guest_studio_state'] = trim((string) ($validated['guest_studio_state'] ?? ''));
            $validated['guest_postal_code'] = trim((string) ($validated['guest_postal_code'] ?? ''));
            $validated['guest_studio_country'] = trim((string) ($validated['guest_studio_country'] ?? ''));
            $validated['guest_google_maps_link'] = trim((string) ($validated['guest_google_maps_link'] ?? '')) ?: null;
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesFromPayload(array $validated, ?GuestSpot $existing = null): array
    {
        $attributes = [
            'status' => $validated['status'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
        ];

        if ($validated['status'] === 'available') {
            $numberOfSpots = (int) $validated['number_of_spots'];
            $attributes = array_merge($attributes, [
                'start_time' => $this->normalizeTime((string) $validated['start_time']),
                'end_time' => $this->normalizeTime((string) $validated['end_time']),
                'response_deadline' => (int) $validated['response_deadline'],
                'response_deadline_unit' => (string) $validated['response_deadline_unit'],
                'buffer_days_before' => (int) $validated['buffer_days_before'],
                'buffer_days_after' => (int) $validated['buffer_days_after'],
                'number_of_spots' => $numberOfSpots,
                'remaining_spots' => GuestSpot::remainingSpotsForCapacityChange(
                    $numberOfSpots,
                    (int) ($existing?->number_of_spots ?? 0),
                    (int) ($existing?->remaining_spots ?? 0)
                ),
                'studio_name' => $validated['guest_studio_name'],
                'studio_address' => $validated['guest_studio_address'],
                'street_number' => $validated['guest_street_number'],
                'street_name' => $validated['guest_street_name'],
                'studio_city' => $validated['guest_studio_city'],
                'studio_state' => $validated['guest_studio_state'],
                'postal_code' => $validated['guest_postal_code'],
                'studio_country' => $validated['guest_studio_country'],
                'google_maps_link' => $validated['guest_google_maps_link'] ?? null,
                'latitude' => isset($validated['guest_latitude']) && $validated['guest_latitude'] !== ''
                    ? (float) $validated['guest_latitude']
                    : null,
                'longitude' => isset($validated['guest_longitude']) && $validated['guest_longitude'] !== ''
                    ? (float) $validated['guest_longitude']
                    : null,
            ]);
        } else {
            $attributes = array_merge($attributes, [
                'start_time' => null,
                'end_time' => null,
                'response_deadline' => null,
                'response_deadline_unit' => null,
                'buffer_days_before' => 0,
                'buffer_days_after' => 0,
                'number_of_spots' => 0,
                'remaining_spots' => 0,
                'studio_name' => null,
                'studio_address' => null,
                'street_number' => null,
                'street_name' => null,
                'studio_city' => null,
                'studio_state' => null,
                'postal_code' => null,
                'studio_country' => null,
                'google_maps_link' => null,
                'latitude' => null,
                'longitude' => null,
            ]);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertNoOverlappingGuestSpots(array $validated, ?int $ignoreGuestSpotId = null): void
    {
        [$candidateFrom, $candidateTo] = $this->blockedRangeFromValidated($validated);

        $query = GuestSpot::query()->where('user_id', Auth::id());

        if ($ignoreGuestSpotId !== null) {
            $query->whereKeyNot($ignoreGuestSpotId);
        }

        foreach ($query->get() as $existing) {
            if (! $existing->overlapsBlockedRange($candidateFrom, $candidateTo)) {
                continue;
            }

            $message = sprintf(
                'These dates overlap with %s, %s (%s – %s), including travel buffer days.',
                $existing->city,
                $existing->country,
                $existing->from_date->format('M j, Y'),
                $existing->to_date->format('M j, Y'),
            );

            throw ValidationException::withMessages([
                'from_date' => [$message],
                'to_date' => [$message],
                'buffer_days_before' => [$message],
                'buffer_days_after' => [$message],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: Carbon, 1: Carbon}
     */
    private function blockedRangeFromValidated(array $validated): array
    {
        $bufferBefore = $validated['status'] === 'available'
            ? (int) ($validated['buffer_days_before'] ?? 0)
            : 0;
        $bufferAfter = $validated['status'] === 'available'
            ? (int) ($validated['buffer_days_after'] ?? 0)
            : 0;

        $from = Carbon::parse($validated['from_date'])->startOfDay()->subDays($bufferBefore);
        $to = Carbon::parse($validated['to_date'])->startOfDay()->addDays($bufferAfter);

        return [$from, $to];
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

    /**
     * @return array<string, mixed>
     */
    private function serialize(GuestSpot $guestSpot): array
    {
        return $guestSpot->toFormArray();
    }

    private function normalizeTime(string $time): string
    {
        return Carbon::createFromFormat('H:i', $time)->format('H:i:s');
    }
}
