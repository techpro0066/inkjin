@php
  $klarnaDefaultName = $klarnaDefaultName ?? '';
  $klarnaDefaultEmail = $klarnaDefaultEmail ?? '';
  $klarnaDefaultCountry = strtoupper((string) ($klarnaDefaultCountry ?? 'GR'));
  $klarnaCountries = [
    'AT' => 'Austria',
    'BE' => 'Belgium',
    'DE' => 'Germany',
    'DK' => 'Denmark',
    'ES' => 'Spain',
    'FI' => 'Finland',
    'FR' => 'France',
    'GB' => 'United Kingdom',
    'GR' => 'Greece',
    'IE' => 'Ireland',
    'IT' => 'Italy',
    'NL' => 'Netherlands',
    'NO' => 'Norway',
    'PL' => 'Poland',
    'PT' => 'Portugal',
    'SE' => 'Sweden',
  ];
  if (! array_key_exists($klarnaDefaultCountry, $klarnaCountries)) {
    $klarnaDefaultCountry = 'GR';
  }
@endphp
<div id="panelPayKlarna" class="hidden">
  <div class="rounded-2xl border border-[#FFB3C7]/40 bg-gradient-to-br from-[#FFF0F5] to-[#FFE8EF] p-5 mb-6">
    <div class="flex items-center gap-2 mb-2">
      <span class="text-lg font-extrabold text-[#17120F]">Klarna.</span>
      <span class="text-xs font-semibold text-[#17120F]/60 bg-[#FFB3C7]/30 px-2 py-0.5 rounded-full">Buy now, pay later</span>
    </div>
    <p class="text-sm font-semibold text-[#17120F] mb-1">Pay in 3 interest-free installments</p>
    <p class="text-lg font-bold text-[#17120F] mb-1" id="klarnaAmountLabel">3 payments of —</p>
    <p class="text-xs text-[#17120F]/70 mb-4">No interest. No fees. You'll be redirected to Klarna to complete payment.</p>

    <div class="space-y-3 mb-1">
      <div>
        <label class="text-xs font-semibold text-[#17120F]/70 mb-1.5 block" for="inputKlarnaName">Full name</label>
        <input
          type="text"
          id="inputKlarnaName"
          value="{{ $klarnaDefaultName }}"
          autocomplete="name"
          placeholder="Your full name"
          class="w-full border border-[#FFB3C7]/50 bg-white rounded-xl px-4 py-3 text-sm text-[#17120F] focus:outline-none focus:ring-2 focus:ring-[#FF9CB8]/50"
        >
      </div>
      <div>
        <label class="text-xs font-semibold text-[#17120F]/70 mb-1.5 block" for="inputKlarnaEmail">Email</label>
        <input
          type="email"
          id="inputKlarnaEmail"
          value="{{ $klarnaDefaultEmail }}"
          autocomplete="email"
          placeholder="you@example.com"
          class="w-full border border-[#FFB3C7]/50 bg-white rounded-xl px-4 py-3 text-sm text-[#17120F] focus:outline-none focus:ring-2 focus:ring-[#FF9CB8]/50"
        >
      </div>
      <div>
        <label class="text-xs font-semibold text-[#17120F]/70 mb-1.5 block" for="inputKlarnaCountry">Country</label>
        <select
          id="inputKlarnaCountry"
          class="w-full border border-[#FFB3C7]/50 bg-white rounded-xl px-4 py-3 text-sm text-[#17120F] focus:outline-none focus:ring-2 focus:ring-[#FF9CB8]/50"
        >
          @foreach ($klarnaCountries as $code => $label)
            <option value="{{ $code }}" @selected($code === $klarnaDefaultCountry)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <p class="text-[10px] text-[#17120F]/40 text-center mt-3">Subject to approval. 18+ only.</p>
  </div>
</div>
