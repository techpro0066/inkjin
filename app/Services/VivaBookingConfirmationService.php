<?php

namespace App\Services;

use App\Models\BalanceCollection;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Models\PaymentLink;
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
        private readonly PaymentLinkCheckoutService $paymentLinkCheckout,
        private readonly BalanceCollectionCheckoutService $balanceCollectionCheckout,
    ) {}

    public function confirmFromWebhookPayload(array $payload): ?Booking
    {
        $event = $payload['EventData'] ?? $payload;

        $statusId = (string) ($event['StatusId'] ?? $event['statusId'] ?? '');
        $orderCode = $event['OrderCode'] ?? $event['orderCode'] ?? null;
        $transactionId = (string) ($event['TransactionId'] ?? $event['transactionId'] ?? '');
        $amount = $event['Amount'] ?? $event['amount'] ?? null;

        if ($statusId !== 'F' || ! $orderCode || $transactionId === '') {
            Log::info('Viva webhook ignored', [
                'status_id' => $statusId,
                'order_code' => $orderCode,
                'transaction_id' => $transactionId,
                'event_type_id' => $payload['EventTypeId'] ?? null,
            ]);

            return null;
        }

        $pending = PendingVivaPayment::query()
            ->where('viva_order_code', $orderCode)
            ->first();

        if (! $pending) {
            Log::warning('Viva webhook: pending payment not found', ['order_code' => $orderCode]);

            return null;
        }

        if (! $this->viva->amountMatchesCents($amount, $pending->amount_cents)) {
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
                PendingVivaPayment::FLOW_PAYMENT_LINK => $this->paymentLinkCheckout->createFromVivaPending($locked, $transactionId),
                PendingVivaPayment::FLOW_BALANCE_COLLECTION => $this->balanceCollectionCheckout->createFromVivaPending($locked, $transactionId),
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

        if ($pending->flow === PendingVivaPayment::FLOW_PAYMENT_LINK && $pending->reference_id) {
            $link = PaymentLink::query()->find($pending->reference_id);
            if ($link?->booking_id) {
                return Booking::query()->find($link->booking_id);
            }
        }

        if ($pending->flow === PendingVivaPayment::FLOW_BALANCE_COLLECTION && $pending->reference_id) {
            $collection = BalanceCollection::query()->find($pending->reference_id);
            if ($collection?->booking_id) {
                return Booking::query()->find($collection->booking_id);
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
