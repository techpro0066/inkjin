<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformFee;
use App\Support\StripeConnectCountries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlatformFeeController extends Controller
{
    public function index(): View
    {
        $supported = $this->supportedCountries();
        $this->syncCountries($supported);

        return view('admin.platform-fees.index', [
            'feeGroups' => $this->feeGroups($supported),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $supported = $this->supportedCountries();
        $groups = $this->groupCountries($supported);
        $currencyCodes = array_keys($groups);
        $submittedAmounts = $request->input('amounts', []);

        if (! is_array($submittedAmounts) || array_diff(array_keys($submittedAmounts), $currencyCodes) !== []) {
            throw ValidationException::withMessages([
                'amounts' => 'One or more submitted currencies are not supported.',
            ]);
        }

        $rules = ['amounts' => ['required', 'array']];
        foreach ($currencyCodes as $currency) {
            $rules['amounts.'.$currency] = [
                'required',
                'regex:/^(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/D',
                'numeric',
                'min:0',
                'max:99999999.99',
            ];
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($supported, $groups, $validated): void {
            $this->syncCountries($supported);

            foreach ($groups as $currency => $countries) {
                PlatformFee::query()
                    ->where('currency', $currency)
                    ->whereIn('country', array_keys($countries))
                    ->update(['amount' => round((float) $validated['amounts'][$currency], 2)]);
            }
        });

        return redirect()
            ->route('admin.platform-fees.index')
            ->with('status', 'Platform fees updated.');
    }

    /**
     * @return array<string, array{name: string, currency: string}>
     */
    private function supportedCountries(): array
    {
        $supported = StripeConnectCountries::supported();
        if ($supported === []) {
            abort(503, 'Supported payout countries are currently unavailable.');
        }

        return $supported;
    }

    /**
     * Ensure every currently supported country has a persistent row, retaining existing
     * per-currency values and using the existing default fee for newly added currencies.
     *
     * @param  array<string, array{name: string, currency: string}>  $supported
     */
    private function syncCountries(array $supported): void
    {
        $existing = PlatformFee::query()->orderBy('country')->get()->keyBy('country');
        $amountsByCurrency = [];
        foreach ($existing as $fee) {
            $amountsByCurrency[$fee->currency] ??= (float) $fee->amount;
        }

        foreach ($supported as $countryCode => $meta) {
            $countryCode = strtoupper((string) $countryCode);
            $currency = strtoupper((string) ($meta['currency'] ?? ''));
            if ($countryCode === '' || $currency === '') {
                continue;
            }

            $fee = $existing->get($countryCode);
            $amount = $amountsByCurrency[$currency]
                ?? (($fee && $fee->currency === $currency) ? (float) $fee->amount : 10.0);
            $amountsByCurrency[$currency] ??= $amount;

            PlatformFee::query()->updateOrCreate(
                ['country' => $countryCode],
                ['currency' => $currency, 'amount' => $amount],
            );
        }
    }

    /**
     * @param  array<string, array{name: string, currency: string}>  $supported
     * @return array<string, array<string, string>>
     */
    private function groupCountries(array $supported): array
    {
        $groups = [];
        foreach ($supported as $countryCode => $meta) {
            $currency = strtoupper((string) ($meta['currency'] ?? ''));
            if ($currency === '') {
                continue;
            }

            $groups[$currency][strtoupper((string) $countryCode)] = (string) $meta['name'];
        }

        ksort($groups);
        foreach ($groups as &$countries) {
            asort($countries, SORT_NATURAL | SORT_FLAG_CASE);
        }
        unset($countries);

        return $groups;
    }

    /**
     * @param  array<string, array{name: string, currency: string}>  $supported
     * @return list<array{currency: string, countries: list<string>, amount: float}>
     */
    private function feeGroups(array $supported): array
    {
        $groups = $this->groupCountries($supported);
        $amounts = PlatformFee::query()
            ->whereIn('country', array_keys($supported))
            ->get(['country', 'amount'])
            ->keyBy('country');

        $result = [];
        foreach ($groups as $currency => $countries) {
            $firstCountry = array_key_first($countries);
            $result[] = [
                'currency' => $currency,
                'countries' => array_values($countries),
                'amount' => (float) ($amounts->get($firstCountry)?->amount ?? 10),
            ];
        }

        return $result;
    }
}
