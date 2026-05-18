<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferArtistSlotsRequest;
use App\Models\BookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestsController extends Controller
{
    public function index()
    {
        $requests = BookingRequest::query()
            ->with(['user', 'tattoo'])
            ->where('artist_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        $requestsPayload = $requests
            ->map(fn (BookingRequest $request) => $request->toArtistPanelArray())
            ->values()
            ->all();

        return view('artist.requests.index', [
            'requests' => $requests,
            'requestsPayload' => $requestsPayload,
        ]);
    }

    public function decline(Request $request, BookingRequest $bookingRequest)
    {
        if ((int) $bookingRequest->artist_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($bookingRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be declined.',
            ], 422);
        }

        $validated = $request->validate([
            'reason_decline' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $bookingRequest->update([
            'status' => 'cancelled',
            'reason_decline' => $validated['reason_decline'],
        ]);

        $bookingRequest->refresh();
        $bookingRequest->load(['user', 'tattoo']);

        return response()->json([
            'success' => true,
            'message' => 'Request declined successfully.',
            'request' => $bookingRequest->toArtistPanelArray(),
        ]);
    }

    public function offerSlots(OfferArtistSlotsRequest $request, BookingRequest $bookingRequest)
    {
        if ($bookingRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be updated with offered times.',
            ], 422);
        }

        $payload = $request->normalizedPayload();

        $bookingRequest->update(array_merge($payload, [
            'status' => 'confirmed',
        ]));

        $bookingRequest->refresh();
        $bookingRequest->load(['user', 'tattoo']);

        return response()->json([
            'success' => true,
            'message' => 'Offered times saved. The client can review your suggestions.',
            'request' => $bookingRequest->toArtistPanelArray(),
        ]);
    }
}
