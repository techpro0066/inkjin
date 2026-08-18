<?php

namespace App\Http\Controllers;

use App\Mail\BookingCompletionCodeMail;
use App\Models\BalanceCollection;
use App\Models\Booking;
use App\Models\PaymentLink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->with(['user', 'tattoo', 'latestBalanceCollection'])
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('artist.bookings.index', compact('bookings'));
    }

    public function paymentLinks(Request $request)
    {
        $paymentLinks = PaymentLink::query()
            ->where('artist_id', Auth::id())
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('artist.bookings.payment-links', compact('paymentLinks'));
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

    public function liveStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(50)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['bookings' => []]);
        }

        $bookings = Booking::query()
            ->where('artist_user_id', Auth::id())
            ->whereIn('id', $ids)
            ->get(['id', 'status', 'remaining_amount_released', 'full_amount_paid']);

        $paidIds = BalanceCollection::query()
            ->whereIn('booking_id', $ids)
            ->where(function ($query) {
                $query->where('status', BalanceCollection::STATUS_PAID)
                    ->orWhere('status', BalanceCollection::STATUS_CASH_CONFIRMED)
                    ->orWhere('payment_status', BalanceCollection::PAYMENT_STATUS_PAID);
            })
            ->pluck('booking_id')
            ->unique()
            ->all();

        $payload = $bookings->map(function (Booking $booking) use ($paidIds) {
            $status = strtolower((string) $booking->status);
            $completed = $status === 'completed';
            $balanceCollected = (bool) $booking->remaining_amount_released
                || in_array((int) $booking->id, array_map('intval', $paidIds), true);

            return [
                'id' => (int) $booking->id,
                'status' => $status,
                'completed' => $completed,
                'balance_collected' => $balanceCollected,
                'settled' => $completed,
                'message' => 'This booking is already completed.',
            ];
        })->values();

        return response()->json(['bookings' => $payload]);
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

        if ((string) $booking->status === 'completed') {
            return response()->json([
                'success' => true,
                'already_completed' => true,
                'message' => 'This booking is already completed.',
            ]);
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

        $this->completeArtistBooking($booking);

        return response()->json([
            'success' => true,
            'message' => 'Booking marked as completed.',
        ]);
    }

    public function storeBalanceCollection(Request $request, int $id)
    {
        $booking = Booking::query()
            ->with(['user', 'tattoo'])
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $booking->artist_user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $type = (string) $request->input('collection_type');
        $rules = [
            'collection_type' => ['required', 'in:payment_link,paid_in_cash,not_settled_yet'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];

        if ($type === BalanceCollection::TYPE_PAID_IN_CASH) {
            $rules['completion_code'] = ['required', 'string', 'min:4', 'max:32'];
        }

        if ($type === BalanceCollection::TYPE_NOT_SETTLED_YET) {
            $rules['expected_payment_type'] = ['required', 'in:3_days,1_week,pick_date,no_date'];
            $rules['expected_payment_date'] = [
                'required_if:expected_payment_type,pick_date',
                'nullable',
                'date',
                'after_or_equal:today',
            ];
        }

        $validator = Validator::make($request->all(), $rules, [
            'amount.gt' => 'Amount must be greater than 0.',
            'completion_code.required' => 'Please enter the completion code.',
            'expected_payment_type.required' => 'Please choose when you expect the payment.',
            'expected_payment_date.required_if' => 'Please pick a date.',
            'expected_payment_date.after_or_equal' => 'Please pick today or a future date.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Please fix the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $amount = round((float) $request->input('amount'), 2);
        $currency = strtoupper((string) ($booking->currency ?: 'EUR'));
        $payload = [
            'booking_id' => $booking->id,
            'artist_user_id' => (int) $booking->artist_user_id,
            'client_user_id' => $booking->user_id,
            'collection_type' => $type,
            'amount' => $amount,
            'currency' => $currency !== '' ? $currency : 'EUR',
            'payment_status' => BalanceCollection::PAYMENT_STATUS_PENDING,
            'status' => BalanceCollection::STATUS_PENDING,
        ];

        if ($type === BalanceCollection::TYPE_PAYMENT_LINK) {
            $code = $this->generateBalanceCollectionLinkCode();
            $url = url('/p/'.$code);
            $firstName = trim((string) strtok((string) ($booking->user?->first_name ?? ''), ' '));
            if ($firstName === '') {
                $firstName = 'there';
            }
            $title = $booking->displayTitle();
            $amountLabel = $this->formatBalanceEuro($amount);
            $payload['payment_link_code'] = $code;
            $payload['payment_link_url'] = $url;
            $payload['client_message'] = sprintf(
                "Hi %s — here’s the payment link for the remaining %s for your %s session: %s — you can pay in full or split it with Klarna.",
                $firstName,
                $amountLabel,
                $title !== '' ? $title : 'tattoo',
                $url
            );
            $payload['status'] = BalanceCollection::STATUS_LINK_SENT;
        } elseif ($type === BalanceCollection::TYPE_PAID_IN_CASH) {
            $inputCode = strtoupper(trim((string) $request->input('completion_code')));
            $storedCode = strtoupper(trim((string) $booking->completion_code));
            if ($storedCode === '' || ! hash_equals($storedCode, $inputCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid completion code.',
                    'errors' => ['completion_code' => ['Invalid completion code.']],
                ], 422);
            }

            $payload['completion_code'] = $inputCode;
            $payload['completion_code_entered_at'] = now();
            $payload['payment_method'] = 'cash';
            $payload['payment_status'] = BalanceCollection::PAYMENT_STATUS_PAID;
            $payload['paid_at'] = now();
            $payload['status'] = BalanceCollection::STATUS_CASH_CONFIRMED;
        } else {
            $when = (string) $request->input('expected_payment_type');
            $payload['expected_payment_type'] = $when;
            $payload['note'] = trim((string) $request->input('note')) ?: null;
            $payload['expected_payment_date'] = match ($when) {
                '3_days' => now()->addDays(3)->toDateString(),
                '1_week' => now()->addWeek()->toDateString(),
                'pick_date' => $request->input('expected_payment_date'),
                default => null,
            };
            $payload['status'] = BalanceCollection::STATUS_SETTLEMENT_DEFERRED;
        }

        $bookingCompleted = false;

        $record = DB::transaction(function () use ($booking, $payload, $type, &$bookingCompleted) {
            $saved = $this->saveBalanceCollectionForBooking($booking->id, $payload);

            if ($type === BalanceCollection::TYPE_PAID_IN_CASH) {
                $this->completeArtistBooking($booking, [
                    'remaining_amount_released' => true,
                    'remaining_amount_released_at' => now(),
                ]);
                $bookingCompleted = true;
            }

            return $saved;
        });

        return response()->json([
            'success' => true,
            'message' => match ($type) {
                BalanceCollection::TYPE_PAYMENT_LINK => 'Payment link created.',
                BalanceCollection::TYPE_PAID_IN_CASH => 'Cash payment recorded. Booking marked as completed.',
                default => 'Reminder saved.',
            },
            'booking_completed' => $bookingCompleted,
            'collection' => [
                'id' => $record->id,
                'collection_type' => $record->collection_type,
                'amount' => (float) $record->amount,
                'amount_label' => $this->formatBalanceEuro((float) $record->amount),
                'payment_link_code' => $record->payment_link_code,
                'payment_link_url' => $record->payment_link_url,
                'client_message' => $record->client_message,
                'status' => $record->status,
                'note' => $record->note,
                'expected_payment_type' => $record->expected_payment_type,
                'expected_label' => $type === BalanceCollection::TYPE_NOT_SETTLED_YET
                    ? $record->expectedDuePhrase()
                    : null,
                'nudge' => $type === BalanceCollection::TYPE_NOT_SETTLED_YET
                    ? $record->reminderNudge(
                        trim((string) strtok((string) ($booking->user?->first_name ?? ''), ' ')),
                        $this->formatBalanceEuro((float) $record->amount)
                    )
                    : null,
            ],
        ]);
    }

    private function completeArtistBooking(Booking $booking, array $extra = []): void
    {
        if (in_array((string) $booking->status, ['completed', 'cancelled', 'declined'], true)) {
            return;
        }

        $history = $booking->action_history ?? [];
        $history[] = [
            'action' => 'completed',
            'user_id' => Auth::id(),
            'user_type' => 'artist',
            'timestamp' => now()->toDateTimeString(),
        ];

        $booking->update(array_merge([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_code_entered_at' => now(),
            'action_history' => $history,
        ], $extra));
    }

    private function saveBalanceCollectionForBooking(int $bookingId, array $payload): BalanceCollection
    {
        $record = BalanceCollection::query()
            ->where('booking_id', $bookingId)
            ->latest('id')
            ->first();

        if (! $record) {
            return BalanceCollection::query()->create($payload);
        }

        BalanceCollection::query()
            ->where('booking_id', $bookingId)
            ->whereKeyNot($record->id)
            ->delete();

        $record->forceFill(array_merge($this->blankBalanceCollectionAttributes(), $payload));
        $record->save();

        return $record->refresh();
    }

    private function blankBalanceCollectionAttributes(): array
    {
        return [
            'platform_fee' => 0,
            'tax_amount' => 0,
            'tax_rate' => null,
            'tax_country' => null,
            'tax_label' => null,
            'payment_link_id' => null,
            'payment_link_code' => null,
            'payment_link_url' => null,
            'client_message' => null,
            'completion_code' => null,
            'completion_code_entered_at' => null,
            'expected_payment_type' => null,
            'expected_payment_date' => null,
            'note' => null,
            'payment_provider' => null,
            'payment_method' => null,
            'payment_intent_id' => null,
            'viva_order_code' => null,
            'viva_transaction_id' => null,
            'payment_status' => BalanceCollection::PAYMENT_STATUS_PENDING,
            'paid_at' => null,
            'status' => BalanceCollection::STATUS_PENDING,
            'meta' => null,
        ];
    }

    private function generateBalanceCollectionLinkCode(): string
    {
        $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (
            PaymentLink::query()->where('code', $code)->exists()
            || BalanceCollection::query()->where('payment_link_code', $code)->exists()
        );

        return $code;
    }

    private function formatBalanceEuro(float $value): string
    {
        $rounded = round($value, 2);
        if (fmod($rounded, 1.0) === 0.0) {
            return '€'.(string) (int) $rounded;
        }

        return '€'.number_format($rounded, 2, '.', '');
    }
}

