<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        $query = Booking::query()
            ->with(['user', 'artist', 'tattoo'])
            ->latest('created_at');

        $this->applyFilters($query, $filters);

        $total = (clone $query)->count();

        return view('admin.bookings.index', [
            'bookings' => $query->paginate(30)->withQueryString(),
            'filters' => $filters,
            'total' => $total,
            'statuses' => [
                'pending',
                'confirmed',
                'completed',
                'cancelled',
                'no_show',
                'rescheduled',
            ],
            'types' => [
                'flash' => 'Flash',
                'custom' => 'Custom',
                'payment_link' => 'Payment link',
            ],
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load(['user', 'artist', 'tattoo', 'paymentLink', 'latestBalanceCollection']);

        return view('admin.bookings.show', [
            'booking' => $booking,
        ]);
    }

    /**
     * @return array{q: string, status: string, type: string, from: ?string, to: ?string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'pending', 'confirmed', 'completed', 'cancelled', 'no_show', 'rescheduled'])],
            'type' => ['nullable', Rule::in(['all', 'flash', 'custom', 'payment_link'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'q' => trim((string) ($validated['q'] ?? '')),
            'status' => (string) ($validated['status'] ?? 'all'),
            'type' => (string) ($validated['type'] ?? 'all'),
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }

    /**
     * @param  Builder<Booking>  $query
     * @param  array{q: string, status: string, type: string, from: ?string, to: ?string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if ($filters['type'] === 'payment_link') {
            $query->where('custom_tattoo_details', 'like', '%"payment_link_id"%');
        } elseif ($filters['type'] === 'custom') {
            $query->where(function (Builder $q) {
                $q->where('custom_tattoo_details', 'like', '%"custom_request_id"%')
                    ->orWhere(function (Builder $inner) {
                        $inner->where('booking_type', 'custom')
                            ->whereNull('tattoo_id')
                            ->where(function (Builder $details) {
                                $details->whereNull('custom_tattoo_details')
                                    ->orWhere('custom_tattoo_details', 'not like', '%"payment_link_id"%');
                            });
                    });
            });
        } elseif ($filters['type'] === 'flash') {
            $query->where(function (Builder $q) {
                $q->whereNull('custom_tattoo_details')
                    ->orWhere(function (Builder $details) {
                        $details->where('custom_tattoo_details', 'not like', '%"payment_link_id"%')
                            ->where('custom_tattoo_details', 'not like', '%"custom_request_id"%');
                    });
            })->where(function (Builder $q) {
                $q->where('booking_type', 'flash')
                    ->orWhereNotNull('tattoo_id');
            });
        }

        if ($filters['q'] !== '') {
            $search = $filters['q'];
            $query->where(function (Builder $query) use ($search) {
                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }

                $digits = preg_replace('/\D+/', '', $search);
                if ($digits !== null && $digits !== '' && ctype_digit($digits)) {
                    $query->orWhere('id', (int) $digits);
                }

                $query->orWhereHas('user', function (Builder $userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('artist', function (Builder $artistQuery) use ($search) {
                    $artistQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }
    }
}
