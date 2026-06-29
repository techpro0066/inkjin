<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Models\PendingVivaPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VivaBookingConfirmationService
{
    public function __construct(
        private readonly VivaPaymentsService $viva,
        private readonly ManagedRequestBookingService $managedBooking,
        private readonly CustomRequestBookingService $customBooking,
        private readonly PublicVivaBookingService $publicBooking,
    ) {}

    public function confirmFromWebhookPayload(array $payload): ?Booking
    {
        $statusId = (string) ($payload['StatusId'] ?? '');
        $orderCode = $payload['OrderCode'] ?? null;
        $transactionId = (string) ($payload['TransactionId'] ?? '');
        $amount = (int) ($payload['Amount'] ?? 0);

        if ($statusId !== 'F' || ! $orderCode || $transactionId === '') {
            return null;
        }

        $pending = PendingVivaPayment::query()
            ->where('viva_order_code', $orderCode)
            ->first();

        if (! $pending) {
            Log::warning('Viva webhook: pending payment not found', ['order_code' => $orderCode]);

            return null;
        }

        if ($pending->amount_cents !== $amount) {
            Log::error('Viva webhook amount mismatch', [
                'order_code' => $orderCode,
                'expected' => $pending->amount_cents,
                'received' => $amount,
            ]);

            return null;
        }

        return $this->confirmPaid($pending, $transactionId);
    }

    public function confirmPaid(PendingVivaPayment $pending, string $transactionId): Booking
    {
        $transaction = $this->viva->retrieveTransaction($transactionId);
        if (! $this->viva->isTransactionPaid($transaction, $pending->amount_cents, $pending->viva_order_code)) {
            throw new RuntimeException('Viva transaction verification failed.');
        }

        return DB::transaction(function () use ($pending, $transactionId) {
            /** @var PendingVivaPayment $locked */
            $locked = PendingVivaPayment::query()
                ->whereKey($pending->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isPaid()) {
                $existing = $this->findBookingForPending($locked);
                if ($existing) {
                    return $existing;
                }
            }

            $locked->update([
                'status' => PendingVivaPayment::STATUS_PAID,
                'viva_transaction_id' => $transactionId,
                'paid_at' => now(),
            ]);

            return match ($locked->flow) {
                PendingVivaPayment::FLOW_MANAGED_REQUEST => $this->confirmManagedRequest($locked, $transactionId),
                PendingVivaPayment::FLOW_CUSTOM_REQUEST => $this->confirmCustomRequest($locked, $transactionId),
                PendingVivaPayment::FLOW_PUBLIC_BOOKING => $this->publicBooking->createFromPending($locked, $transactionId),
                default => throw new RuntimeException('Unsupported Viva payment flow.'),
            };
        });
    }

    public function findBookingForPending(PendingVivaPayment $pending): ?Booking
    {
        $byOrder = Booking::query()->where('viva_order_code', $pending->viva_order_code)->first();
        if ($byOrder) {
            return $byOrder;
        }

        if ($pending->flow === PendingVivaPayment::FLOW_MANAGED_REQUEST && $pending->reference_id) {
            $request = BookingRequest::query()->find($pending->reference_id);
            if ($request?->booking_id) {
                return Booking::query()->find($request->booking_id);
            }
        }

        if ($pending->flow === PendingVivaPayment::FLOW_CUSTOM_REQUEST && $pending->reference_id) {
            $request = CustomRequest::query()->find($pending->reference_id);
            if ($request?->booking_id) {
                return Booking::query()->find($request->booking_id);
            }
        }

        return null;
    }

    private function confirmManagedRequest(PendingVivaPayment $pending, string $transactionId): Booking
    {
        $bookingRequest = BookingRequest::query()->findOrFail($pending->reference_id);

        return $this->managedBooking->createBookingFromVivaPayment(
            $bookingRequest,
            (int) $pending->viva_order_code,
            $transactionId,
        );
    }

    private function confirmCustomRequest(PendingVivaPayment $pending, string $transactionId): Booking
    {
        $customRequest = CustomRequest::query()->findOrFail($pending->reference_id);

        return $this->customBooking->createBookingFromVivaPayment(
            $customRequest,
            (int) $pending->viva_order_code,
            $transactionId,
        );
    }
}
