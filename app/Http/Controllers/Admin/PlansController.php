<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;

class PlansController extends Controller
{
    private const CURRENCIES = ['EUR', 'CAD', 'CHF', 'CZK', 'DKK', 'GBP', 'HUF', 'NOK', 'PLN', 'RON', 'SEK', 'USD'];

    public function index(): View
    {
        $plans = Plan::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (Plan $plan): array => $plan->toEditorArray())
            ->values()
            ->all();

        $features = PlanFeature::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();

        return view('admin.plans.index', compact('plans', 'features'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedPlan($request);

        $plan = DB::transaction(function () use ($data): Plan {
            $data['slug'] = $this->uniqueSlug($data['name']);
            $data['sort_order'] = ((int) Plan::query()->max('sort_order')) + 1;

            return Plan::query()->create($this->attributes($data));
        });

        return response()->json(['plan' => $plan->toEditorArray()], 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $data = $this->validatedPlan($request);

        $data['slug'] = $this->uniqueSlug($data['name'], $plan);
        $plan->update($this->attributes($data));

        return response()->json(['plan' => $plan->fresh()->toEditorArray()]);
    }

    public function storeFeature(Request $request): JsonResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('plan_features', 'name')],
        ]);

        $feature = PlanFeature::query()->create([
            'name' => $data['name'],
            'sort_order' => ((int) PlanFeature::query()->max('sort_order')) + 1,
        ]);

        return response()->json(['feature' => $feature->name], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::in(['On', 'Off', 'Draft'])],
            'audience' => ['required', Rule::in(['Artist', 'Studio'])],
            'visibility' => ['required', Rule::in(['Public', 'Hidden'])],
            'badge' => ['nullable', 'string', 'max:120'],
            'prices' => ['required', 'array:'.implode(',', self::CURRENCIES)],
            'prices.*' => ['required', 'numeric', 'min:0'],
            'commission' => ['required', 'numeric', 'min:0', 'max:100'],
            'bookingFee' => ['required', Rule::in(['0', '1'])],
            'includedArtists' => ['nullable', 'integer', 'min:1'],
            'extraArtist' => ['nullable', 'numeric', 'min:0'],
            'trialDays' => ['required', 'integer', 'min:0', 'max:365'],
            'trialFallback' => ['nullable', 'string', 'max:120'],
            'cutoffDate' => ['nullable', 'date'],
            'afterCutoff' => ['nullable', 'string', 'max:120'],
            'layout' => ['required', Rule::in(['card', 'terms'])],
            'tagline' => ['nullable', 'string', 'max:2000'],
            'priceNote' => ['nullable', 'string', 'max:2000'],
            'listTitle' => ['nullable', 'string', 'max:120'],
            'includedItems' => ['array', 'max:100'],
            'includedItems.*' => ['string', 'max:300'],
            'buttonLabel' => ['nullable', 'string', 'max:120'],
            'buttonNote' => ['nullable', 'string', 'max:2000'],
            'termsIntro' => ['nullable', 'string', 'max:2000'],
            'terms' => ['array', 'max:100'],
            'terms.*.label' => ['required', 'string', 'max:255'],
            'terms.*.value' => ['nullable', 'string', 'max:255'],
            'termsNote' => ['nullable', 'string', 'max:2000'],
            'trialButton' => ['nullable', 'string', 'max:120'],
            'features' => ['array', 'max:100'],
            'features.*' => ['string', 'max:120'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'slug' => $data['slug'],
            'name' => trim($data['name']),
            'status' => $data['status'],
            'audience' => $data['audience'],
            'visibility' => $data['visibility'],
            'badge' => $this->nullableString($data['badge'] ?? null),
            'prices' => $data['prices'],
            'commission_rate' => $data['commission'],
            'client_booking_fee' => $data['bookingFee'] === '1',
            'included_artist_count' => $data['includedArtists'] ?? null,
            'extra_artist_monthly_price' => $data['extraArtist'] ?? null,
            'trial_days' => $data['trialDays'],
            'trial_fallback_plan' => $this->nullableString($data['trialFallback'] ?? null),
            'signup_cutoff_date' => $data['cutoffDate'] ?? null,
            'cutoff_replacement_plan' => $this->nullableString($data['afterCutoff'] ?? null),
            'layout' => $data['layout'],
            'tagline' => $this->nullableString($data['tagline'] ?? null),
            'price_note' => $this->nullableString($data['priceNote'] ?? null),
            'list_title' => $this->nullableString($data['listTitle'] ?? null),
            'included_items' => $data['includedItems'] ?? [],
            'button_label' => $this->nullableString($data['buttonLabel'] ?? null),
            'button_note' => $this->nullableString($data['buttonNote'] ?? null),
            'terms_intro' => $this->nullableString($data['termsIntro'] ?? null),
            'terms' => $data['terms'] ?? [],
            'terms_note' => $this->nullableString($data['termsNote'] ?? null),
            'trial_button_label' => $this->nullableString($data['trialButton'] ?? null),
            'features' => $data['features'] ?? [],
        ];
    }

    private function uniqueSlug(string $name, ?Plan $plan = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a plan name that can be used to create its URL identifier.',
            ]);
        }

        $slug = $base;
        $suffix = 2;
        while (Plan::query()
            ->where('slug', $slug)
            ->when($plan, static fn ($query) => $query->where('id', '!=', $plan->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
