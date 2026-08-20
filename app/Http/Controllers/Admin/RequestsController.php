<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class RequestsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        $rows = collect();

        if ($filters['type'] === 'all' || $filters['type'] === 'flash') {
            $flashQuery = BookingRequest::query()->with(['user', 'artist', 'tattoo', 'booking']);
            $this->applyCommonFilters($flashQuery, $filters);
            $rows = $rows->concat(
                $flashQuery->get()->map(fn (BookingRequest $item) => $this->mapFlashRequest($item))
            );
        }

        if ($filters['type'] === 'all' || $filters['type'] === 'custom') {
            $customQuery = CustomRequest::query()->with(['user', 'artist', 'booking']);
            $this->applyCommonFilters($customQuery, $filters);
            $rows = $rows->concat(
                $customQuery->get()->map(fn (CustomRequest $item) => $this->mapCustomRequest($item))
            );
        }

        $rows = $rows
            ->sortByDesc(fn (array $row) => $row['created_at_sort'])
            ->values();

        return view('admin.requests.index', [
            'requests' => $this->paginate($rows, 30),
            'filters' => $filters,
            'total' => $rows->count(),
            'statuses' => [
                'pending',
                'confirmed',
                'cancelled',
                'moved_to_booking',
            ],
            'types' => [
                'flash' => 'Flash',
                'custom' => 'Custom',
            ],
        ]);
    }

    public function showFlash(BookingRequest $bookingRequest): View
    {
        $bookingRequest->load(['user', 'artist', 'tattoo', 'booking']);

        $panel = $bookingRequest->toArtistPanelArray();
        $panel['clientPhone'] = (string) ($bookingRequest->user?->phone_number ?? '');
        $panel['clientSessionSlots'] = $bookingRequest->normalizedArtistSlots($bookingRequest->client_session_slots);
        $panel['clientConsultationSlots'] = $bookingRequest->normalizedArtistSlots($bookingRequest->client_consultation_slots);

        return view('admin.requests.show', [
            'kind' => 'flash',
            'requestModel' => $bookingRequest,
            'panel' => $panel,
        ]);
    }

    public function showCustom(CustomRequest $customRequest): View
    {
        $customRequest->load(['user', 'artist', 'booking']);

        $panel = $customRequest->toArtistPanelArray();
        $panel['estimatedPriceLabel'] = $customRequest->estimatedPriceLabel();
        $panel['clientSessionSlots'] = $customRequest->normalizedArtistSlots($customRequest->client_session_slots);
        $panel['clientConsultationSlots'] = $customRequest->normalizedArtistSlots($customRequest->client_consultation_slots);

        return view('admin.requests.show', [
            'kind' => 'custom',
            'requestModel' => $customRequest,
            'panel' => $panel,
        ]);
    }

    /**
     * @return array{q: string, status: string, type: string, from: ?string, to: ?string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'pending', 'confirmed', 'cancelled', 'moved_to_booking'])],
            'type' => ['nullable', Rule::in(['all', 'flash', 'custom'])],
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
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array{q: string, status: string, type: string, from: ?string, to: ?string}  $filters
     */
    private function applyCommonFilters(Builder $query, array $filters): void
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

    /**
     * @return array<string, mixed>
     */
    private function mapFlashRequest(BookingRequest $item): array
    {
        $amount = (float) ($item->tattoo?->min_price ?? 0);
        $deposit = (float) ($item->booking?->deposit_amount ?? 0);

        return [
            'created_at_sort' => optional($item->created_at)->timestamp ?? 0,
            'date' => $item->created_at,
            'id' => $item->referenceLabel(),
            'model_id' => $item->id,
            'view_url' => route('admin.requests.flash.show', $item),
            'client_name' => $item->user?->name ?: ('Client #'.$item->user_id),
            'client_email' => $item->user?->email,
            'artist_name' => $item->artist?->name ?: ('Artist #'.$item->artist_id),
            'artist_email' => $item->artist?->email,
            'type' => 'Flash',
            'type_key' => 'flash',
            'status' => $item->filterStatusLabel(),
            'status_key' => (string) $item->status,
            'deposit' => $deposit > 0 ? $deposit : null,
            'amount' => $amount > 0 ? $amount : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCustomRequest(CustomRequest $item): array
    {
        $amount = (float) ($item->estimated_price ?? 0);
        $deposit = (float) ($item->booking?->deposit_amount ?? 0);

        return [
            'created_at_sort' => optional($item->created_at)->timestamp ?? 0,
            'date' => $item->created_at,
            'id' => $item->referenceLabel(),
            'model_id' => $item->id,
            'view_url' => route('admin.requests.custom.show', $item),
            'client_name' => $item->clientDisplayName(),
            'client_email' => $item->user?->email,
            'artist_name' => $item->artist?->name ?: ('Artist #'.$item->artist_id),
            'artist_email' => $item->artist?->email,
            'type' => 'Custom',
            'type_key' => 'custom',
            'status' => $item->filterStatusLabel(),
            'status_key' => (string) $item->status,
            'deposit' => $deposit > 0 ? $deposit : null,
            'amount' => $amount > 0 ? $amount : null,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(Collection $rows, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        return new Paginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
