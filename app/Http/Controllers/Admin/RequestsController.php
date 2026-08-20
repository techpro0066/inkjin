<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Support\AdminListPagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RequestsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $perPage = AdminListPagination::perPage($request);

        $paginator = match ($filters['type']) {
            'flash' => $this->paginateSingleType($this->flashQuery($filters), 'flash', $perPage),
            'custom' => $this->paginateSingleType($this->customQuery($filters), 'custom', $perPage),
            default => $this->paginateCombined($filters, $perPage),
        };

        return view('admin.requests.index', [
            'requests' => $paginator,
            'filters' => $filters,
            'total' => $paginator->total(),
            'perPage' => $perPage,
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
     * @return array{q: string, status: string, type: string, from: ?string, to: ?string, per_page: int}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'pending', 'confirmed', 'cancelled', 'moved_to_booking'])],
            'type' => ['nullable', Rule::in(['all', 'flash', 'custom'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', Rule::in(AdminListPagination::OPTIONS)],
        ]);

        return [
            'q' => trim((string) ($validated['q'] ?? '')),
            'status' => (string) ($validated['status'] ?? 'all'),
            'type' => (string) ($validated['type'] ?? 'all'),
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'per_page' => AdminListPagination::perPage($request),
        ];
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array{q: string, status: string, type: string, from: ?string, to: ?string, per_page: int}  $filters
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
     * @param  array{q: string, status: string, type: string, from: ?string, to: ?string, per_page: int}  $filters
     * @return Builder<BookingRequest>
     */
    private function flashQuery(array $filters): Builder
    {
        $query = BookingRequest::query();
        $this->applyCommonFilters($query, $filters);

        return $query;
    }

    /**
     * @param  array{q: string, status: string, type: string, from: ?string, to: ?string, per_page: int}  $filters
     * @return Builder<CustomRequest>
     */
    private function customQuery(array $filters): Builder
    {
        $query = CustomRequest::query();
        $this->applyCommonFilters($query, $filters);

        return $query;
    }

    /**
     * @param  Builder<BookingRequest>|Builder<CustomRequest>  $query
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateSingleType(Builder $query, string $kind, int $perPage): LengthAwarePaginator
    {
        $relations = $kind === 'flash'
            ? ['user', 'artist', 'tattoo', 'booking']
            : ['user', 'artist', 'booking'];

        $paginator = $query->with($relations)
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn ($item) => $kind === 'flash'
                    ? $this->mapFlashRequest($item)
                    : $this->mapCustomRequest($item))
                ->values()
        );

        return $paginator;
    }

    /**
     * @param  array{q: string, status: string, type: string, from: ?string, to: ?string, per_page: int}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateCombined(array $filters, int $perPage): LengthAwarePaginator
    {
        $flashTable = (new BookingRequest)->getTable();
        $customTable = (new CustomRequest)->getTable();

        $flash = $this->flashQuery($filters)
            ->selectRaw("'flash' as request_kind, {$flashTable}.id as request_id, {$flashTable}.created_at");

        $custom = $this->customQuery($filters)
            ->selectRaw("'custom' as request_kind, {$customTable}.id as request_id, {$customTable}.created_at");

        $union = $flash->toBase()->unionAll($custom->toBase());

        $page = Paginator::resolveCurrentPage();
        $total = (int) DB::query()->fromSub(clone $union, 'combined_requests')->count();

        $pageKeys = DB::query()
            ->fromSub($union, 'combined_requests')
            ->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get(['request_kind', 'request_id']);

        $flashIds = $pageKeys->where('request_kind', 'flash')->pluck('request_id')->map(fn ($id) => (int) $id)->all();
        $customIds = $pageKeys->where('request_kind', 'custom')->pluck('request_id')->map(fn ($id) => (int) $id)->all();

        $flashMap = $flashIds === []
            ? collect()
            : BookingRequest::query()
                ->with(['user', 'artist', 'tattoo', 'booking'])
                ->whereIn('id', $flashIds)
                ->get()
                ->keyBy('id');

        $customMap = $customIds === []
            ? collect()
            : CustomRequest::query()
                ->with(['user', 'artist', 'booking'])
                ->whereIn('id', $customIds)
                ->get()
                ->keyBy('id');

        $rows = $pageKeys->map(function ($row) use ($flashMap, $customMap) {
            if ($row->request_kind === 'flash') {
                $model = $flashMap->get((int) $row->request_id);

                return $model ? $this->mapFlashRequest($model) : null;
            }

            $model = $customMap->get((int) $row->request_id);

            return $model ? $this->mapCustomRequest($model) : null;
        })->filter()->values();

        return new Paginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
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
}
