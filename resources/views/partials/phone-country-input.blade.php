{{--
  Phone input with country-code dropdown.
  Params:
    - idPrefix: e.g. bd | tf  (creates {prefix}PhoneCountry + {prefix}Phone)
    - errorId: optional error element id
    - defaultIso: optional ISO (default GR)
--}}
@php
  $idPrefix = $idPrefix ?? 'bd';
  $countryId = $idPrefix.'PhoneCountry';
  $phoneId = $idPrefix.'Phone';
  $errorId = $errorId ?? ($phoneId.'Error');
  $defaultIso = strtoupper((string) ($defaultIso ?? \App\Support\PhoneCountryCodes::defaultIso()));
  $countries = \App\Support\PhoneCountryCodes::all();
@endphp
<div class="flex gap-2 items-stretch phone-country-field" data-phone-country-field data-prefix="{{ $idPrefix }}">
  <div class="shrink-0 w-[42%] sm:w-[11.5rem]">
    <select
      id="{{ $countryId }}"
      name="{{ $countryId }}"
      class="w-full border border-outline-variant/30 bg-white rounded-2xl px-3 py-4 text-sm sm:text-base text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
      aria-label="Country code"
    >
      @foreach ($countries as $country)
        <option
          value="{{ $country['iso'] }}"
          data-dial="{{ $country['dial'] }}"
          @selected($country['iso'] === $defaultIso)
        >+{{ $country['dial'] }} {{ $country['name'] }}</option>
      @endforeach
    </select>
  </div>
  <input
    type="tel"
    id="{{ $phoneId }}"
    name="{{ $phoneId }}"
    inputmode="tel"
    autocomplete="tel-national"
    placeholder="694 123 4567"
    class="flex-1 min-w-0 border border-outline-variant/30 bg-white rounded-2xl px-4 sm:px-6 py-4 text-lg text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
  >
</div>
<p id="{{ $errorId }}" class="text-sm text-error mt-2 hidden">This field is required.</p>
