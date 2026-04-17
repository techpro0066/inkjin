@php $ud = $userDetail; @endphp
<div class="rounded-2xl bg-white/90 border border-[#e6e0ea] shadow-sm p-6 md:p-8">
  <h1 class="text-2xl font-bold text-on-surface mb-1">Preferences</h1>
  <p class="text-on-surface/70 text-sm mb-6">Currency, scheduling rules, and consultation defaults.</p>

  <form id="preferencesForm" class="space-y-6">
    @csrf

    <section class="rounded-xl border border-[#e6e0ea] p-4 md:p-5 bg-surface-container-low/50">
      <h2 class="text-sm font-semibold mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-lg">settings</span> General</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2">Timezone <span class="text-red-600">*</span></label>
          <select class="form-input w-full" id="timezone" name="timezone" data-placeholder="Search timezone"></select>
          <p id="timezone_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Date format <span class="text-red-600">*</span></label>
          <select name="date_time_format" id="date_time_format" class="form-input w-full">
            <option value="" disabled {{ !($ud->date_time_format ?? null) ? 'selected' : '' }}>Select</option>
            <option value="MM/DD/YYYY" {{ ($ud->date_time_format ?? '') == 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY</option>
            <option value="DD/MM/YYYY" {{ ($ud->date_time_format ?? '') == 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY</option>
            <option value="YYYY-MM-DD" {{ ($ud->date_time_format ?? '') == 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD</option>
          </select>
          <p id="date_time_format_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
      </div>
    </section>

    <section class="rounded-xl border border-[#e6e0ea] p-4 md:p-5 bg-surface-container-low/50">
      <h2 class="text-sm font-semibold mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-lg">payments</span> Payment</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2">Currency <span class="text-red-600">*</span></label>
          <select class="form-input w-full" id="currency" name="currency"></select>
          <p id="currency_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Deposit type <span class="text-red-600">*</span></label>
          <select name="minimum_deposit_type" id="minimum_deposit_type" class="form-input w-full">
            <option value="" disabled {{ !($ud->minimum_deposit_type ?? null) ? 'selected' : '' }}>Select</option>
            <option value="amount" {{ ($ud->minimum_deposit_type ?? '') == 'amount' ? 'selected' : '' }}>Amount</option>
            <option value="percentage" {{ ($ud->minimum_deposit_type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage</option>
          </select>
          <p id="minimum_deposit_type_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-2">Minimum deposit <span class="text-red-600">*</span></label>
          <input type="text" name="minimum_deposit_amount" id="minimum_deposit_amount" value="{{ $ud->minimum_deposit_amount ?? '' }}" class="form-input w-full" />
          <p id="minimum_deposit_amount_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
      </div>
      <div class="mt-4">
        <p class="text-sm font-medium mb-2">Inkjin booking fee <span class="text-red-600">*</span></p>
        <label class="flex items-start gap-2 mb-2 cursor-pointer"><input type="radio" name="booking_fee_type" value="client" class="mt-1" {{ ($ud->booking_fee_type ?? '') == 'client' ? 'checked' : '' }} /><span class="text-sm">Client pays — fee added to client total</span></label>
        <label class="flex items-start gap-2 mb-2 cursor-pointer"><input type="radio" name="booking_fee_type" value="artist" class="mt-1" {{ ($ud->booking_fee_type ?? '') == 'artist' ? 'checked' : '' }} /><span class="text-sm">Artist pays — fee deducted from payout</span></label>
        <label class="flex items-start gap-2 cursor-pointer"><input type="radio" name="booking_fee_type" value="split" class="mt-1" {{ ($ud->booking_fee_type ?? '') == 'split' ? 'checked' : '' }} /><span class="text-sm">Split — shared between client and artist</span></label>
        <p id="booking_fee_type_error" class="text-red-600 text-sm mt-2 hidden"></p>
      </div>
    </section>

    <section class="rounded-xl border border-[#e6e0ea] p-4 md:p-5 bg-surface-container-low/50">
      <h2 class="text-sm font-semibold mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-lg">calendar_clock</span> Scheduling</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-2">Reschedule policy <span class="text-red-600">*</span></label>
          <select name="reschedule_times" id="reschedule_times" class="form-input w-full">
            <option value="" disabled {{ !($ud->reschedule_times ?? null) ? 'selected' : '' }}>Select</option>
            @foreach (['never'=>'Never','once'=>'Once','twice'=>'Twice','unlimited'=>'Unlimited'] as $k=>$label)
              <option value="{{ $k }}" {{ ($ud->reschedule_times ?? '') == $k ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          <p id="reschedule_times_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-2">Full refund cancellation window <span class="text-red-600">*</span></label>
          <select name="cancellation_window" id="cancellation_window" class="form-input w-full">
            <option value="" disabled {{ !($ud->cancellation_window ?? null) ? 'selected' : '' }}>Select</option>
            @foreach (['24h'=>'24 hours','48h'=>'48 hours','72h'=>'72 hours','1w'=>'1 week','2w'=>'2 weeks'] as $k=>$label)
              <option value="{{ $k }}" {{ ($ud->cancellation_window ?? '') == $k ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          <p id="cancellation_window_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-2">Buffer between sessions (minutes) <span class="text-red-600">*</span></label>
          <input type="number" name="session_buffer_period" id="session_buffer_period" min="0" step="1" value="{{ $ud->session_buffer_period ?? '' }}" class="form-input w-full max-w-xs" />
          <p id="session_buffer_period_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
      </div>
    </section>

    <section class="rounded-xl border border-[#e6e0ea] p-4 md:p-5 bg-surface-container-low/50">
      <h2 class="text-sm font-semibold mb-4">Consultation</h2>
      <label class="flex items-center gap-3 cursor-pointer mb-4">
        <input type="checkbox" name="require_consultation" id="require_consultation" value="1" {{ ($ud->require_consultation ?? false) ? 'checked' : '' }} class="rounded border-[#cac4d3] w-5 h-5 text-primary" onchange="window.toggleSessionFields && window.toggleSessionFields()" />
        <span class="text-sm">Require consultation before tattoo session</span>
      </label>
      <div id="session_type_container" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" style="display:{{ ($ud->require_consultation ?? false) ? 'grid' : 'none' }}">
        <div>
          <label class="block text-sm font-medium mb-2">Session type <span class="text-red-600">*</span></label>
          <select name="session_type" id="session_type" class="form-input w-full">
            <option value="" disabled {{ !($ud->session_type ?? null) ? 'selected' : '' }}>Select</option>
            <option value="online" {{ ($ud->session_type ?? '') == 'online' ? 'selected' : '' }}>Online</option>
            <option value="physical" {{ ($ud->session_type ?? '') == 'physical' ? 'selected' : '' }}>Physical</option>
            <option value="both" {{ ($ud->session_type ?? '') == 'both' ? 'selected' : '' }}>Both</option>
          </select>
          <p id="session_type_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
        <div id="session_duration_container">
          <label class="block text-sm font-medium mb-2">Duration (minutes) <span class="text-red-600">*</span></label>
          <input type="number" name="session_duration_minutes" id="session_duration_minutes" min="15" max="480" step="15" value="{{ $ud->session_duration_minutes ?? '' }}" class="form-input w-full" />
          <p id="session_duration_minutes_error" class="text-red-600 text-sm mt-1 hidden"></p>
        </div>
      </div>
      <div id="consultation_timing_container" class="mt-2" style="display:{{ ($ud->require_consultation ?? false) ? 'block' : 'none' }}">
        <label class="block text-sm font-medium mb-2">Consultation timing <span class="text-red-600">*</span></label>
        <select name="consultation_timing" id="consultation_timing" class="form-input w-full max-w-md" onchange="window.toggleGapFields && window.toggleGapFields()">
          <option value="" disabled {{ !($ud->consultation_timing ?? null) ? 'selected' : '' }}>Select</option>
          <option value="combined" {{ ($ud->consultation_timing ?? '') == 'combined' ? 'selected' : '' }}>Combined with tattoo session</option>
          <option value="separate" {{ ($ud->consultation_timing ?? '') == 'separate' ? 'selected' : '' }}>Separate booking</option>
        </select>
        <p id="consultation_timing_error" class="text-red-600 text-sm mt-1 hidden"></p>
      </div>
      <div id="gap_fields_container" class="flex flex-col gap-3 mt-4" style="display:{{ (($ud->require_consultation ?? false) && ($ud->consultation_timing ?? '') == 'separate') ? 'flex' : 'none' }}">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="require_gap_between_consultation_tattoo" id="require_gap_between_consultation_tattoo" value="1" {{ ($ud->require_gap_between_consultation_tattoo ?? false) ? 'checked' : '' }} onchange="window.toggleGapDurationFields && window.toggleGapDurationFields()" />
          <span class="text-sm">Require gap between consultation and tattoo</span>
        </label>
        <div id="gap_duration_container" class="grid grid-cols-1 md:grid-cols-2 gap-4" style="display:{{ ($ud->require_gap_between_consultation_tattoo ?? false) ? 'grid' : 'none' }}">
          <div>
            <label class="block text-sm font-medium mb-2">Gap value <span class="text-red-600">*</span></label>
            <input type="number" min="1" name="consultation_tattoo_gap_value" id="consultation_tattoo_gap_value" value="{{ $ud->consultation_tattoo_gap_value ?? '' }}" class="form-input w-full" />
            <p id="consultation_tattoo_gap_value_error" class="text-red-600 text-sm mt-1 hidden"></p>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Gap unit <span class="text-red-600">*</span></label>
            <select name="consultation_tattoo_gap_unit" id="consultation_tattoo_gap_unit" class="form-input w-full">
              <option value="" disabled {{ !($ud->consultation_tattoo_gap_unit ?? null) ? 'selected' : '' }}>Select</option>
              @foreach (['minutes','hours','days'] as $u)
                <option value="{{ $u }}" {{ ($ud->consultation_tattoo_gap_unit ?? '') == $u ? 'selected' : '' }}>{{ ucfirst($u) }}</option>
              @endforeach
            </select>
            <p id="consultation_tattoo_gap_unit_error" class="text-red-600 text-sm mt-1 hidden"></p>
          </div>
        </div>
      </div>
    </section>

    <div id="prefAlert" class="hidden rounded-xl px-4 py-3 text-sm"></div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 pt-2">
      <a href="{{ route('onboarding.studio') }}" class="text-center sm:text-left text-primary font-semibold text-sm py-2">Back</a>
      <button type="submit" id="prefSubmit" class="btn-primary px-8 py-3 rounded-xl text-sm font-bold">Continue</button>
    </div>
  </form>
</div>
