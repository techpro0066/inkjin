<?php

namespace App\Http\Controllers;

use App\Mail\BookingCompletionCodeMail;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingsController extends Controller
{
    /**
     * Display a listing of bookings for the authenticated user.
     */
    public function index(Request $request)
    {
        $bookings = Booking::query()
            ->where('artist_user_id', Auth::id())
            ->with(['user', 'tattoo'])
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('artist.bookings.index', compact('bookings'));
    }

    public function sendCompletionCode(Request $request, int $id)
    {
        $booking = Booking::query()
            ->with(['user', 'artist'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed bookings can be completed.',
            ], 400);
        }

        if (!$booking->completion_code) {
            do {
                $code = strtoupper(Str::random(6));
            } while (Booking::query()->where('completion_code', $code)->exists());

            $booking->completion_code = $code;
            $booking->save();
        }

        try {
            Mail::to($booking->user->email)->send(new BookingCompletionCodeMail($booking));
        } catch (\Throwable $e) {
            Log::error('Failed to send booking completion code email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not send completion code email.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Completion code sent to client email.',
        ]);
    }

    public function markCompleted(Request $request, int $id)
    {
        $booking = Booking::query()
            ->with(['user', 'artist'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed bookings can be completed.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'completion_code' => 'required|string|min:4|max:32',
            'confirmed' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$request->boolean('confirmed')) {
            return response()->json([
                'success' => false,
                'message' => 'Completion must be confirmed.',
            ], 400);
        }

        $inputCode = strtoupper(trim((string) $request->input('completion_code')));
        $storedCode = strtoupper(trim((string) $booking->completion_code));
        if ($storedCode === '' || !hash_equals($storedCode, $inputCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid completion code.',
            ], 422);
        }

        $history = $booking->action_history ?? [];
        $history[] = [
            'action' => 'completed',
            'user_id' => Auth::id(),
            'user_type' => 'artist',
            'timestamp' => now()->toDateTimeString(),
        ];

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_code_entered_at' => now(),
            'action_history' => $history,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking marked as completed.',
        ]);
    }
}

