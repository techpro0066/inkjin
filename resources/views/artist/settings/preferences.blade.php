@extends('layouts.artist_dashboard_layout')

@section('title', 'Payments')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .toggle-segment { padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
  .toggle-segment.active { background: #310f7a; color: white; }
  .toggle-segment:not(.active) { color: #494552; }
  .radio-card { border: 1.5px solid #cac4d3; border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; position: relative; }
  .radio-card.selected { border-color: #310f7a; background: #fdf7ff; }
  .radio-card .radio-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #cac4d3; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
  .radio-card.selected .radio-dot { border-color: #310f7a; background: #310f7a; }
  .radio-card.selected .radio-dot::after { content: ''; width: 6px; height: 6px; background: white; border-radius: 50%; }
  .select2-container { width: 100% !important; z-index: 1; }
  .select2-container--open { z-index: 10060 !important; }
  .select2-container--default .select2-selection--single {
    min-height: 48px;
    padding: 6px 12px;
    border-radius: 0.75rem;
    border: 1px solid rgba(202,196,211,0.5) !important;
    background: #fff !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 2.25rem;
    padding-left: 4px;
    color: #1c1b21;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #310f7a !important;
    box-shadow: 0 0 0 2px rgba(49,15,122,0.25);
  }
  .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
  .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #310f7a !important; }
  .select2-container--default .select2-search--dropdown .select2-search__field {
    border-radius: 0.5rem;
    border-color: rgba(202,196,211,0.5);
  }

  @media (max-width: 1023px) {
    .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    body { overflow-x: hidden; }
  }
</style>
@endsection

@section('content')
@php
  $ud = $userDetail;
@endphp
  <main class="main-content flex-1 min-h-screen flex flex-col">
    <form id="preferencesForm" class="contents">
      @csrf
    <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-4xl">

      <!-- Settings Tabs -->
      <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
        <a href="{{ route('profile.edit') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Profile</a>
        <a href="{{ route('settings.styles') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Styles &amp; Social</a>
        <a href="{{ route('settings.studio') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Studio</a>
        <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
        <a href="{{route('settings.calendar')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
        <a href="{{route('settings.payment')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Payouts</a>
        <a href="{{ route('settings.other') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Other</a>
        {{-- <a href="{{ route('settings.notifications') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a> --}}
      </div>


      <!-- Page Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Payment Settings</h2>
        <p class="text-on-surface-variant mt-1">Fine-tune how your booking system works — scheduling, payments, and client interactions.</p>
      </div>
      <div id="prefSuccessAlert" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm"></div>
      <div id="prefErrorAlert" class="hidden mb-6 rounded-xl border border-error/30 bg-error/10 text-error px-4 py-3 text-sm"></div>

      <div class="space-y-10">
        <input type="hidden" name="timezone" value="{{ $ud->timezone ?: 'UTC' }}">
        <input type="hidden" name="date_time_format" value="{{ $ud->date_time_format ?: 'DD/MM/YYYY' }}">
        <input type="hidden" id="size_unit" name="size_unit" value="{{ $ud->size_unit ?: 'cm' }}">

        <!-- Payment Logic -->
        <section>
          <h3 class="text-lg font-bold text-on-surface mb-1">Payment Logic</h3>
          <div class="h-px bg-outline-variant/30 mb-5"></div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <!-- Deposit Type -->
            <div>
              <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Deposit Type</label>
              <div class="inline-flex bg-surface-container-highest rounded-full p-1 toggle-div">
                <button type="button" class="toggle-segment toggle-segment-left {{ ($ud->minimum_deposit_type ?? 'amount') === 'amount' ? 'active' : '' }}" id="deposit_fixed" onclick="setDepositType('amount')">Fixed Amount</button>
                <button type="button" class="toggle-segment toggle-segment-right {{ ($ud->minimum_deposit_type ?? 'amount') === 'percentage' ? 'active' : '' }}" id="deposit_percent" onclick="setDepositType('percentage')">Percentage %</button>
              </div>
              <input type="hidden" id="minimum_deposit_type" name="minimum_deposit_type" value="{{ ($ud->minimum_deposit_type ?? 'amount') === 'percentage' ? 'percentage' : 'amount' }}">
            </div>
            <!-- Currency -->
            <div>
              <label for="currency" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Currency</label>
              <select id="currency" name="currency" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30" data-selected="{{ $ud->currency ?? '' }}"></select>
              <p id="currency_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <!-- Min. Deposit -->
            <div>
              <label for="min_deposit" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Min. Deposit <span class="deposit-type-selected">{{ ($ud->minimum_deposit_type ?? 'amount') === 'amount' ? 'Amount' : 'Percentage' }}</span></label>
              <input type="number" id="minimum_deposit_amount" name="minimum_deposit_amount" value="{{ $ud->minimum_deposit_amount ?? '' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p id="minimum_deposit_amount_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
          </div>

          <div class="mb-8">
            <h4 class="text-sm font-bold text-on-surface mb-1">Rates</h4>
            <p class="text-on-surface-variant text-xs mb-4">These are reference rates. Final pricing for custom work is always confirmed by you with a quote.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
              <div>
                <label for="hourly_rate" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Hourly rate <span class="text-on-surface-variant font-normal normal-case tracking-normal">(optional)</span></label>
                <input type="number" step="0.01" min="0" id="hourly_rate" name="hourly_rate" value="{{ $ud->hourly_rate !== null ? rtrim(rtrim(number_format((float) $ud->hourly_rate, 2, '.', ''), '0'), '.') : '' }}" placeholder="e.g. 120" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p class="text-on-surface-variant text-xs mt-2">Your typical rate per hour, before any custom quote</p>
                <p id="hourly_rate_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div>
                <label for="half_day_rate" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Half-day rate <span class="text-on-surface-variant font-normal normal-case tracking-normal">(optional)</span></label>
                <input type="number" step="0.01" min="0" id="half_day_rate" name="half_day_rate" value="{{ $ud->half_day_rate !== null ? rtrim(rtrim(number_format((float) $ud->half_day_rate, 2, '.', ''), '0'), '.') : '' }}" placeholder="e.g. 400" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p class="text-on-surface-variant text-xs mt-2">For sessions typically booked in half-day blocks</p>
                <p id="half_day_rate_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div>
                <label for="full_day_rate" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Full-day rate <span class="text-on-surface-variant font-normal normal-case tracking-normal">(optional)</span></label>
                <input type="number" step="0.01" min="0" id="full_day_rate" name="full_day_rate" value="{{ $ud->full_day_rate !== null ? rtrim(rtrim(number_format((float) $ud->full_day_rate, 2, '.', ''), '0'), '.') : '' }}" placeholder="e.g. 700" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p class="text-on-surface-variant text-xs mt-2">For sessions typically booked as a full day</p>
                <p id="full_day_rate_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
            </div>
          </div>

          <!-- Service Booking Fee -->
          <div>
            <h4 class="text-sm font-bold text-on-surface mb-1">Service Booking Fee</h4>
            <p class="text-on-surface-variant text-xs mb-4">How would you like to handle the 10€ platform service fee?</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="radio-card {{ ($ud->booking_fee_type ?? 'client') === 'client' ? 'selected' : '' }}" onclick="selectFee(this, 'client')" data-value="client">
                <div class="flex items-start justify-between mb-2">
                  <span class="text-sm font-bold text-on-surface">Client pays</span>
                  <div class="radio-dot"></div>
                </div>
                <p class="text-xs text-on-surface-variant">10€ will be added to the client's total</p>
              </div>
              <div class="radio-card {{ ($ud->booking_fee_type ?? 'client') === 'artist' ? 'selected' : '' }}" onclick="selectFee(this, 'artist')" data-value="artist">
                <div class="flex items-start justify-between mb-2">
                  <span class="text-sm font-bold text-on-surface">Artist pays</span>
                  <div class="radio-dot"></div>
                </div>
                <p class="text-xs text-on-surface-variant">10€ will be deducted from your payout</p>
              </div>
              <div class="radio-card {{ ($ud->booking_fee_type ?? 'client') === 'split' ? 'selected' : '' }}" onclick="selectFee(this, 'split')" data-value="split">
                <div class="flex items-start justify-between mb-2">
                  <span class="text-sm font-bold text-on-surface">Split</span>
                  <div class="radio-dot"></div>
                </div>
                <p class="text-xs text-on-surface-variant">Client pays 5€ and you pay 5€ (deducted from your payout)</p>
              </div>
            </div>
            <input type="hidden" id="booking_fee_type" name="booking_fee_type" value="{{ $ud->booking_fee_type ?? 'client' }}">
            <p id="booking_fee_type_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </section>

      </div>
    </div>

    <!-- Footer: Save Changes -->
    <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-6 md:px-10 lg:px-12 py-5 flex items-center justify-end">
      <button type="submit" id="savePrefBtn" form="preferencesForm" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
        <span class="material-symbols-outlined text-lg">save</span> Save Changes
      </button>
    </div>
    </form>
  </main>
@endsection

@section('scripts')
<script src="{{ asset('design/js/currencies.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  function clearPrefErrors() {
    $('#preferencesForm [id$="_error"]').text('').addClass('hidden');
    $('#preferencesForm input, #preferencesForm select').removeClass('border-error');
  }
  function setSizeUnit(unit) {  
    document.getElementById('size_unit').value = unit;
    document.getElementById('unit_cm').classList.toggle('active', unit === 'cm');
    document.getElementById('unit_in').classList.toggle('active', unit === 'in');
  }

  function setDepositType(type) {
    document.getElementById('minimum_deposit_type').value = type;
    $('.deposit-type-selected').text(type === 'amount' ? 'Amount' : 'Percentage');
    document.getElementById('deposit_fixed').classList.toggle('active', type === 'amount');
    document.getElementById('deposit_percent').classList.toggle('active', type === 'percentage');
  }

  function selectFee(el, value) {
    document.querySelectorAll('.radio-card[data-value]').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('booking_fee_type').value = value;
  }

  $(function () {
    fillCurrencySelect(document.getElementById('currency'), $('#currency').data('selected') || 'USD');
    if (window.jQuery && $.fn.select2) {
      $('.js-select2').select2({ width: '100%', dropdownParent: $('body') });
    }
    $('#timezone, #date_time_format, #currency, #minimum_deposit_amount, #hourly_rate, #half_day_rate, #full_day_rate').on('change input', function () {
      $(this).removeClass('border-error');
      $('#' + this.id + '_error').text('').addClass('hidden');
    });
    $('#preferencesForm').on('submit', function (e) {
      e.preventDefault();
      clearPrefErrors();
      $('#prefSuccessAlert').addClass('hidden').text('');
      $('#prefErrorAlert').addClass('hidden').text('');
      var fd = new FormData(this);
      var $btn = $('#savePrefBtn');
      $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-lg">hourglass_top</span> Saving...');
      $.ajax({
        url: @json(route('settings.preferences.update')),
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          $('#prefSuccessAlert').text(data.message || 'Payment settings updated successfully.').removeClass('hidden');
          showSaveToast();
        } else if (data.errors) {
          $.each(data.errors, function (k, msgs) {
            $('#' + k + '_error').text(msgs[0]).removeClass('hidden');
            $('#' + k).addClass('border-error');
          });
        } else {
          $('#prefErrorAlert').text(data.message || 'Could not save payment settings.').removeClass('hidden');
        }
      }).fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          $.each(xhr.responseJSON.errors, function (k, msgs) {
            $('#' + k + '_error').text(msgs[0]).removeClass('hidden');
            $('#' + k).addClass('border-error');
          });
        } else {
          $('#prefErrorAlert').text((xhr.responseJSON && xhr.responseJSON.message) || 'Network error. Please try again.').removeClass('hidden');
        }
      }).always(function () {
        $btn.prop('disabled', false).html('<span class="material-symbols-outlined text-lg">save</span> Save Changes');
      });
    });
  });
</script>
@endsection
