<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferArtistSlotsRequest;
use App\Mail\ManagedBookingDeclinedArtistMail;
use App\Mail\ManagedBookingDeclinedUserMail;
use App\Mail\ManagedBookingSlotsOfferedArtistMail;
use App\Mail\ManagedBookingSlotsOfferedUserMail;
use App\Models\BookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $bookingRequest->load(['user', 'tattoo', 'artist']);

        $this->sendDeclineEmails($bookingRequest);

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
        $bookingRequest->load(['user', 'tattoo', 'artist']);

        $this->sendSlotsOfferedEmails($bookingRequest);

        return response()->json([
            'success' => true,
            'message' => 'Offered times saved. The client can review your suggestions.',
            'request' => $bookingRequest->toArtistPanelArray(),
        ]);
    }

    private function sendDeclineEmails(BookingRequest $bookingRequest): void
    {
        $userRequestsUrl = route('user.requests.index');
        $artistRequestsUrl = route('artist.requests.index');

        $clientEmail = trim((string) ($bookingRequest->user?->email ?? ''));
        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new ManagedBookingDeclinedUserMail(
                    $bookingRequest,
                    $userRequestsUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send booking request declined email to client', [
                    'booking_request_id' => $bookingRequest->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $artistEmail = trim((string) ($bookingRequest->artist?->email ?? ''));
        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new ManagedBookingDeclinedArtistMail(
                    $bookingRequest,
                    $artistRequestsUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send booking request declined email to artist', [
                    'booking_request_id' => $bookingRequest->id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendSlotsOfferedEmails(BookingRequest $bookingRequest): void
    {
        $chooseTimesUrl = route('user.requests.confirm-times', ['bookingRequest' => $bookingRequest]);
        $userRequestsUrl = route('user.requests.index');
        $artistRequestsUrl = route('artist.requests.index');

        $clientEmail = trim((string) ($bookingRequest->user?->email ?? ''));
        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new ManagedBookingSlotsOfferedUserMail(
                    $bookingRequest,
                    $chooseTimesUrl,
                    $userRequestsUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send booking request slots-offered email to client', [
                    'booking_request_id' => $bookingRequest->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $artistEmail = trim((string) ($bookingRequest->artist?->email ?? ''));
        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new ManagedBookingSlotsOfferedArtistMail(
                    $bookingRequest,
                    $artistRequestsUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send booking request slots-offered email to artist', [
                    'booking_request_id' => $bookingRequest->id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
