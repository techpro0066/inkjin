@extends('layouts.onboarding_bookpay', ['hideSidebar' => true])

@section('title', 'Studio payout details')

@section('content')
<div class="flex-1 p-8 md:p-12 max-w-2xl w-full mx-auto">
  <div class="mb-8">
    <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Payout bank details</h2>
    <p class="text-on-surface-variant mt-2 text-sm md:text-base">
      {{ $studio->name }} — submit the account that should receive payouts for artist bookings linked to this studio.
    </p>
  </div>

  <form method="POST" action="{{ $storeUrl }}" novalidate class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-5">
    @csrf

    @if ($errors->any())
      <div class="rounded-xl border border-error/30 bg-error/5 px-4 py-3 mb-1" role="alert" aria-live="polite">
        <p class="text-sm font-bold text-error mb-2">Please fix the issues below (all fields are checked at once).</p>
        <ul class="list-disc list-inside text-sm text-error space-y-1">
          @foreach ($errors->all() as $message)
            <li>{{ $message }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div>
      <label for="account_holder_name" class="block text-sm font-semibold text-on-surface mb-2">Account holder full name <span class="text-red-600">*</span></label>
      <input type="text" id="account_holder_name" name="account_holder_name" value="{{ old('account_holder_name', $studio->account_holder_name) }}" maxlength="255" autocomplete="name"
        class="w-full text-sm border rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 @error('account_holder_name') border-error ring-2 ring-error/25 @else border-outline-variant/30 focus:ring-primary/30 @enderror">
      <p class="text-on-surface-variant text-xs mt-1">Letters and spaces only (hyphens, apostrophes, and periods allowed).</p>
      @error('account_holder_name')
        <p class="text-error text-xs mt-1">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="bank_name" class="block text-sm font-semibold text-on-surface mb-2">Bank name <span class="text-red-600">*</span></label>
      <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $studio->bank_name) }}" maxlength="255" autocomplete="organization"
        class="w-full text-sm border rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 @error('bank_name') border-error ring-2 ring-error/25 @else border-outline-variant/30 focus:ring-primary/30 @enderror">
      @error('bank_name')
        <p class="text-error text-xs mt-1">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="account_number" class="block text-sm font-semibold text-on-surface mb-2">Account number / IBAN <span class="text-red-600">*</span></label>
      <input type="text" id="account_number" name="account_number" value="{{ old('account_number', $studio->account_number) }}" maxlength="40" inputmode="text" spellcheck="false" autocomplete="off"
        class="w-full text-sm border rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 @error('account_number') border-error ring-2 ring-error/25 @else border-outline-variant/30 focus:ring-primary/30 @enderror">
      <p class="text-on-surface-variant text-xs mt-1">IBANs are checked for valid length and check digits. Domestic numbers: 4–34 letters or digits (spaces optional).</p>
      @error('account_number')
        <p class="text-error text-xs mt-1">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="swift_bic" class="block text-sm font-semibold text-on-surface mb-2">SWIFT / BIC <span class="text-red-600">*</span></label>
      <input type="text" id="swift_bic" name="swift_bic" value="{{ old('swift_bic', $studio->swift_bic) }}" maxlength="14" inputmode="text" spellcheck="false" autocomplete="off"
        class="w-full text-sm border rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 uppercase @error('swift_bic') border-error ring-2 ring-error/25 @else border-outline-variant/30 focus:ring-primary/30 @enderror">
      <p class="text-on-surface-variant text-xs mt-1">Exactly 8 characters, or 11 with branch code (letters and numbers only).</p>
      @error('swift_bic')
        <p class="text-error text-xs mt-1">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="currency" class="block text-sm font-semibold text-on-surface mb-2">Currency <span class="text-red-600">*</span></label>
      <select id="currency" name="currency" class="select w-full text-sm border rounded-xl px-4 py-3 bg-white text-on-surface @error('currency') border-error ring-2 ring-error/25 @else border-outline-variant/30 @enderror"
        data-selected="{{ old('currency', $studio->bank_currency ?? 'USD') }}"></select>
      @error('currency')
        <p class="text-error text-xs mt-1">{{ $message }}</p>
      @enderror
    </div>

    <div class="pt-2">
      <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all">
        Submit payout details
      </button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  var cur = document.getElementById('currency');
  if (cur && typeof fillCurrencySelect === 'function') {
    fillCurrencySelect(cur, cur.getAttribute('data-selected') || 'USD');
  }
});
</script>
@endpush
