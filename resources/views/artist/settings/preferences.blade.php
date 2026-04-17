@extends('layouts.artist_dashboard_layout')

@section('title', 'Preferences')

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
  .buffer-btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1.5px solid #cac4d3; background: white; color: #494552; }
  .buffer-btn.active { background: #310f7a; color: white; border-color: #310f7a; }
  .toggle-switch { width: 48px; height: 26px; border-radius: 13px; background: #cac4d3; cursor: pointer; position: relative; transition: background 0.3s; }
  .toggle-switch.active { background: #310f7a; }
  .toggle-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: white; transition: transform 0.3s; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
  .toggle-switch.active::after { transform: translateX(22px); }
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
  $tz = $ud->timezone ?? '';
  $zones = \DateTimeZone::listIdentifiers();
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
        <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Preferences</a>
        <a href="{{route('settings.calendar')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
        <a href="{{route('settings.payment')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
        {{-- <a href="{{ route('settings.notifications') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a> --}}
      </div>


      <!-- Page Header -->
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Preferences Settings</h2>
        <p class="text-on-surface-variant mt-1">Fine-tune how your booking system works — scheduling, payments, and client interactions.</p>
      </div>
      <div id="prefSuccessAlert" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm"></div>
      <div id="prefErrorAlert" class="hidden mb-6 rounded-xl border border-error/30 bg-error/10 text-error px-4 py-3 text-sm"></div>

      <div class="space-y-10">
        <!-- General Settings -->
        <section>
          <h3 class="text-lg font-bold text-on-surface mb-1">General Settings</h3>
          <div class="h-px bg-outline-variant/30 mb-5"></div>
          <div class="bg-surface-container-low rounded-2xl p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
              <div>
                <label for="timezone" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Timezone <span class="text-red-600">*</span></label>
                <select id="timezone" name="timezone" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
                  <option value="" disabled {{ !$tz ? 'selected' : '' }}>Select timezone</option>
                  @foreach ($zones as $z)
                    <option value="{{ $z }}" {{ $tz === $z ? 'selected' : '' }}>{{ str_replace('_', ' ', $z) }}</option>
                  @endforeach
                </select>
                <p id="timezone_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div>
                <label for="date_time_format" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Date Format <span class="text-red-600">*</span></label>
                <select id="date_time_format" name="date_time_format" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
                  <option value="DD/MM/YYYY" {{ ($ud->date_time_format ?? '') == 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY</option>
                  <option value="MM/DD/YYYY" {{ ($ud->date_time_format ?? '') == 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY</option>
                  <option value="YYYY-MM-DD" {{ ($ud->date_time_format ?? '') == 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD</option>
                </select>
                <p id="date_time_format_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div>
                <label for="size_unit" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Design Size Unit</label>
                <div class="inline-flex bg-surface-container-highest rounded-full p-1 toggle-div">
                  <button type="button" class="toggle-segment toggle-segment-left {{ ($ud->size_unit ?? 'cm') === 'cm' ? 'active' : '' }}" id="unit_cm" onclick="setSizeUnit('cm')">Centimeters (cm)</button>
                  <button type="button" class="toggle-segment toggle-segment-right {{ ($ud->size_unit ?? 'cm') === 'in' ? 'active' : '' }}" id="unit_in" onclick="setSizeUnit('in')">Inches (in)</button>
                </div>
                <input type="hidden" id="size_unit" name="size_unit" value="{{ $ud->size_unit ?? 'cm' }}">
              </div>
            </div>
          </div>
        </section>

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

        <!-- Schedule Rules -->
        <section>
          <h3 class="text-lg font-bold text-on-surface mb-1">Schedule Rules</h3>
          <div class="h-px bg-outline-variant/30 mb-5"></div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <!-- Cancellation Window -->
            <div>
              <label for="cancellation_window" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Cancellation Window</label>
              <p class="text-on-surface-variant text-xs mb-3">How many hours before the session can clients cancel without losing their deposit?</p>
              <select id="cancellation_window" name="cancellation_window" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
                @foreach (['12h'=>'12 Hours','24h'=>'24 Hours','48h'=>'48 Hours','72h'=>'72 Hours','1w'=>'1 Week','2w'=>'2 Weeks'] as $k => $lab)
                  <option value="{{ $k }}" {{ ($ud->cancellation_window ?? '24h') === $k ? 'selected' : '' }}>{{ $lab }}</option>
                @endforeach
              </select>
              <p id="cancellation_window_error" class="text-error text-xs mt-1 hidden"></p>
            </div>

            <!-- Buffer Time -->
            <div>
              <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Buffer Time</label>
              <p class="text-on-surface-variant text-xs mb-3">Time automatically blocked off between sessions for cleanup and prep.</p>
              <div class="grid grid-cols-2 gap-2">
                @foreach ([15,30,45,60] as $m)
                <button type="button" class="buffer-btn {{ (int)($ud->session_buffer_period ?? 30) === $m ? 'active' : '' }}" onclick="setBuffer(this, {{ $m }})">{{ $m }}m</button>
                @endforeach
              </div>
              <input type="hidden" id="session_buffer_period" name="session_buffer_period" value="{{ $ud->session_buffer_period ?? 30 }}">
              <p id="session_buffer_period_error" class="text-error text-xs mt-1 hidden"></p>
            </div>

            <!-- Reschedule Policy -->
            <div>
              <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Reschedule Policy</label>
              <div class="space-y-3 mt-3">
                <label class="flex items-center gap-3 cursor-pointer">
                  <input type="radio" name="reschedule_times" value="once" {{ ($ud->reschedule_times ?? 'once') === 'once' ? 'checked' : '' }} class="w-[18px] h-[18px] text-primary border-outline-variant focus:ring-primary">
                  <span class="text-sm text-on-surface">Allow once</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                  <input type="radio" name="reschedule_times" value="twice" {{ ($ud->reschedule_times ?? '') === 'twice' ? 'checked' : '' }} class="w-[18px] h-[18px] text-primary border-outline-variant focus:ring-primary">
                  <span class="text-sm text-on-surface">Allow Twice</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                  <input type="radio" name="reschedule_times" value="unlimited" {{ ($ud->reschedule_times ?? '') === 'unlimited' ? 'checked' : '' }} class="w-[18px] h-[18px] text-primary border-outline-variant focus:ring-primary">
                  <span class="text-sm text-on-surface">Unlimited</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                  <input type="radio" name="reschedule_times" value="never" {{ ($ud->reschedule_times ?? '') === 'never' ? 'checked' : '' }} class="w-[18px] h-[18px] text-primary border-outline-variant focus:ring-primary">
                  <span class="text-sm text-on-surface">Strict (No Rescheduling)</span>
                </label>
              </div>
            </div>
            <p id="reschedule_times_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </section>

        <!-- Consultation Settings -->
        <section>
          <h3 class="text-lg font-bold text-on-surface mb-1">Consultation Settings</h3>
          <div class="h-px bg-outline-variant/30 mb-5"></div>
          <div class="bg-surface-container-low rounded-2xl p-6 flex items-center justify-between gap-6">
            <div>
              <h4 class="text-sm font-bold text-on-surface">Require Consultation Session</h4>
              <p class="text-on-surface-variant text-xs mt-1">Clients will have to book a consultation before booking a tattoo session.</p>
            </div>
            <div class="toggle-switch {{ ($ud->require_consultation ?? false) ? 'active' : '' }}" id="consultation_toggle" onclick="toggleConsultation()" role="switch" aria-checked="{{ ($ud->require_consultation ?? false) ? 'true' : 'false' }}"></div>
            <input type="hidden" id="require_consultation" name="require_consultation" value="{{ ($ud->require_consultation ?? false) ? '1' : '0' }}">
          </div>

          <!-- Consultation Settings Block -->
          <div id="consultation_settings_block" class="mt-6 bg-surface-container-low rounded-2xl p-6 border border-outline-variant/30" style="display: {{ ($ud->require_consultation ?? false) ? 'block' : 'none' }};">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="text-xs font-semibold text-on-surface-variant mb-2 block">Session Type</label>
                <select id="session_type" name="session_type" class="js-select2 w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                  <option value="">Select Session Type</option>
                  <option value="online" {{ ($ud->session_type ?? '') === 'online' ? 'selected' : '' }}>Online</option>
                  <option value="physical" {{ ($ud->session_type ?? '') === 'physical' ? 'selected' : '' }}>In person</option>
                  <option value="both" {{ ($ud->session_type ?? '') === 'both' ? 'selected' : '' }}>Both</option>
                </select>
                <p id="session_type_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div>
                <label class="text-xs font-semibold text-on-surface-variant mb-2 block">Session Duration (minutes)</label>
                <input type="number" id="session_duration_minutes" name="session_duration_minutes" value="{{ $ud->session_duration_minutes ?? '' }}" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="session_duration_minutes_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
            </div>

            <div class="mb-4">
              <label class="text-xs font-semibold text-on-surface-variant mb-3 block">Consultation Setup</label>
              <div id="consultation_setup_group" class="flex flex-col gap-3">
                <label class="flex items-start gap-3 cursor-pointer">
                  <input type="radio" name="consultation_timing" value="combined" class="mt-1 accent-primary"
                    {{ ($ud->consultation_timing ?? 'combined') === 'combined' ? 'checked' : '' }}
                    onchange="toggleConsultationGap()">
                  <div>
                    <span class="text-sm font-semibold text-on-surface block">Included in tattoo session</span>
                    <span class="text-xs text-on-surface-variant">The consultation happens during the tattoo session and counts toward the total session time.</span>
                  </div>
                </label>
                <label class="flex items-start gap-3 cursor-pointer">
                  <input type="radio" name="consultation_timing" value="separate" class="mt-1 accent-primary"
                    {{ ($ud->consultation_timing ?? '') === 'separate' ? 'checked' : '' }}
                    onchange="toggleConsultationGap()">
                  <div>
                    <span class="text-sm font-semibold text-on-surface block">Separate consultation session</span>
                    <span class="text-xs text-on-surface-variant">The consultation is booked as its own session before the tattoo session.</span>
                  </div>
                </label>
              </div>
              <p id="consultation_timing_error" class="text-error text-xs mt-1 hidden"></p>
            </div>

            <div id="consultation_gap_block" class="mt-4 pt-4 border-t border-outline-variant/20" style="display: {{ (($ud->require_consultation ?? false) && ($ud->consultation_timing ?? '') === 'separate') ? 'block' : 'none' }};">
              <input type="hidden" id="require_gap_between_consultation_tattoo" name="require_gap_between_consultation_tattoo" value="{{ (($ud->require_consultation ?? false) && ($ud->consultation_timing ?? '') === 'separate') ? '1' : '0' }}">
              <label class="text-xs font-semibold text-on-surface-variant mb-2 block">Minimum gap (in days)</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <input type="number" id="consultation_tattoo_gap_value" name="consultation_tattoo_gap_value" value="{{ $ud->consultation_tattoo_gap_value ?? '' }}" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
              </div>
              <p id="consultation_tattoo_gap_value_error" class="text-error text-xs mt-1 hidden"></p>
              <p class="text-xs text-on-surface-variant mt-2">Set the minimum time between the consultation and the tattoo session.</p>
            </div>
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

  function setBuffer(btn, value) {
    document.querySelectorAll('.buffer-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('session_buffer_period').value = value;
  }

  function toggleConsultation() {
    const toggle = document.getElementById('consultation_toggle');
    const input = document.getElementById('require_consultation');
    const isActive = toggle.classList.toggle('active');
    toggle.setAttribute('aria-checked', isActive);
    input.value = isActive ? '1' : '0';

    const block = document.getElementById('consultation_settings_block');
    if (block) {
      block.style.display = isActive ? 'block' : 'none';
    }
    if (!isActive) toggleGapFields();
  }

  function toggleGapFields() {
    var consultationRequired = $('#require_consultation').val() === '1';
    var val = $('input[name="consultation_timing"]:checked').val() || '';
    var show = consultationRequired && val === 'separate';
    $('#consultation_gap_block').css('display', show ? 'block' : 'none');
    $('#require_gap_between_consultation_tattoo').val(show ? '1' : '0');
    if (!show) {
      $('#consultation_tattoo_gap_value').val('');
    }
  }

  function toggleConsultationGap() {
    toggleGapFields();
  }

  $(function () {
    fillCurrencySelect(document.getElementById('currency'), $('#currency').data('selected') || 'USD');
    if (window.jQuery && $.fn.select2) {
      $('.js-select2').select2({ width: '100%', dropdownParent: $('body') });
    }
    toggleGapFields();
    $('#timezone, #date_time_format, #currency, #minimum_deposit_amount, #cancellation_window, #session_type, #session_duration_minutes, #consultation_tattoo_gap_value').on('change input', function () {
      $(this).removeClass('border-error');
      $('#' + this.id + '_error').text('').addClass('hidden');
    });
    $('#preferencesForm input[name="consultation_timing"]').on('change', function () {
      $('#consultation_timing_error').text('').addClass('hidden');
      $('#consultation_setup_group').removeClass('ring-2 ring-error/40 rounded-xl p-2');
      toggleGapFields();
    });
    $('#preferencesForm input[name="reschedule_times"]').on('change', function () {
      $('#reschedule_times_error').text('').addClass('hidden');
    });
    $('#preferencesForm').on('submit', function (e) {
      e.preventDefault();
      clearPrefErrors();
      $('#prefSuccessAlert').addClass('hidden').text('');
      $('#prefErrorAlert').addClass('hidden').text('');
      var fd = new FormData(this);
      if ($('#require_consultation').val() !== '1') {
        fd.delete('session_type'); fd.delete('session_duration_minutes'); fd.delete('consultation_timing');
        fd.delete('require_gap_between_consultation_tattoo'); fd.delete('consultation_tattoo_gap_value');
      } else if ((($('input[name="consultation_timing"]:checked').val()) || '') !== 'separate') {
        fd.set('require_gap_between_consultation_tattoo', '0');
        fd.delete('consultation_tattoo_gap_value');
      }
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
          $('#prefSuccessAlert').text(data.message || 'Preferences updated successfully.').removeClass('hidden');
          showSaveToast();
        } else if (data.errors) {
          $.each(data.errors, function (k, msgs) {
            $('#' + k + '_error').text(msgs[0]).removeClass('hidden');
            if (k === 'consultation_timing') {
              $('#consultation_setup_group').addClass('ring-2 ring-error/40 rounded-xl p-2');
            } else {
              $('#' + k).addClass('border-error');
            }
          });
        } else {
          $('#prefErrorAlert').text(data.message || 'Could not save preferences.').removeClass('hidden');
        }
      }).fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          $.each(xhr.responseJSON.errors, function (k, msgs) {
            $('#' + k + '_error').text(msgs[0]).removeClass('hidden');
            if (k === 'consultation_timing') {
              $('#consultation_setup_group').addClass('ring-2 ring-error/40 rounded-xl p-2');
            } else {
              $('#' + k).addClass('border-error');
            }
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
