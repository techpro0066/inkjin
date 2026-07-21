{{-- Expects $ud (UserDetail). Used on Calendar (onboarding + settings). --}}
@php
  $reqCons = (bool) ($ud->require_consultation ?? false);
  $st = $ud->session_type ?? '';
  $ct = $ud->consultation_timing ?? '';
@endphp

<section class="mt-10">
  <h3 class="text-lg font-bold text-on-surface mb-1">Schedule Rules</h3>
  <div class="h-px bg-outline-variant/30 mb-5"></div>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    <div>
      <label for="cancellation_window" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Cancellation Window <span class="text-red-600">*</span></label>
      <p class="text-on-surface-variant text-xs mb-3">How long before the session can clients cancel for a full refund?</p>
      <select id="cancellation_window" name="cancellation_window" class="select js-select2 w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
        @foreach (['12h'=>'12 Hours','24h'=>'24 Hours','48h'=>'48 Hours','72h'=>'72 Hours','1w'=>'1 Week','2w'=>'2 Weeks'] as $k => $lab)
          <option value="{{ $k }}" {{ ($ud->cancellation_window ?? '24h') === $k ? 'selected' : '' }}>{{ $lab }}</option>
        @endforeach
      </select>
      <p id="cancellation_window_error" class="text-error text-xs mt-1 hidden"></p>
    </div>
    <div>
      <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Buffer Time <span class="text-red-600">*</span></label>
      <p class="text-on-surface-variant text-xs mb-3">Time blocked between sessions.</p>
      <div class="grid grid-cols-2 gap-2">
        @foreach ([15,30,45,60] as $m)
          <button type="button" class="buffer-btn {{ (int)($ud->session_buffer_period ?? 30) === $m ? 'active' : '' }}" onclick="setBuffer(this, {{ $m }})">{{ $m }}m</button>
        @endforeach
      </div>
      <input type="hidden" name="session_buffer_period" id="session_buffer_period" value="{{ $ud->session_buffer_period ?? 30 }}">
      <p id="session_buffer_period_error" class="text-error text-xs mt-1 hidden"></p>
    </div>
    <div>
      <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Reschedule Policy <span class="text-red-600">*</span></label>
      <div class="space-y-3 mt-3">
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="radio" name="reschedule_times" value="once" {{ ($ud->reschedule_times ?? 'once') === 'once' ? 'checked' : '' }} class="w-[18px] h-[18px] text-primary border-outline-variant">
          <span class="text-sm text-on-surface">Allow once</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="radio" name="reschedule_times" value="twice" {{ ($ud->reschedule_times ?? '') === 'twice' ? 'checked' : '' }} class="w-[18px] h-[18px]">
          <span class="text-sm text-on-surface">Allow Twice</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="radio" name="reschedule_times" value="unlimited" {{ ($ud->reschedule_times ?? '') === 'unlimited' ? 'checked' : '' }} class="w-[18px] h-[18px]">
          <span class="text-sm text-on-surface">Unlimited</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="radio" name="reschedule_times" value="never" {{ ($ud->reschedule_times ?? '') === 'never' ? 'checked' : '' }} class="w-[18px] h-[18px]">
          <span class="text-sm text-on-surface">Strict (no rescheduling)</span>
        </label>
      </div>
      <p id="reschedule_times_error" class="text-error text-xs mt-1 hidden"></p>
    </div>
  </div>
</section>

<section class="mt-10">
  <h3 class="text-lg font-bold text-on-surface mb-1">Consultation Settings</h3>
  <div class="h-px bg-outline-variant/30 mb-5"></div>
  <div class="bg-surface-container-low rounded-2xl p-6 space-y-6">
    <div class="flex items-center justify-between gap-6 flex-wrap">
      <div>
        <h4 class="text-sm font-bold text-on-surface">Require Consultation Session</h4>
        <p class="text-on-surface-variant text-xs mt-1">When enabled, clients must book a consultation before booking a tattoo session.</p>
      </div>
      <div class="toggle-switch {{ $reqCons ? 'active' : '' }}" id="consultation_toggle" onclick="toggleConsultation()" role="switch" aria-checked="{{ $reqCons ? 'true' : 'false' }}"></div>
      <input type="hidden" name="require_consultation" id="require_consultation" value="{{ $reqCons ? '1' : '0' }}">
    </div>
    <p id="require_consultation_error" class="text-error text-xs hidden"></p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div id="session_type_container" style="display: {{ $reqCons ? 'block' : 'none' }};">
        <label for="session_type" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Session type <span class="text-red-500">*</span></label>
        <select id="session_type" name="session_type" class="select js-select2 w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
          <option value="" disabled {{ $st === '' ? 'selected' : '' }}>Select session type</option>
          <option value="online" {{ $st === 'online' ? 'selected' : '' }}>Online session</option>
          <option value="physical" {{ $st === 'physical' ? 'selected' : '' }}>Physical session</option>
          <option value="both" {{ $st === 'both' ? 'selected' : '' }}>Both (online &amp; physical)</option>
        </select>
        <p class="text-on-surface-variant text-xs mt-1">Online, in person, or both.</p>
        <p id="session_type_error" class="text-error text-xs mt-1 hidden"></p>
      </div>
      <div id="session_duration_container" style="display: {{ $reqCons ? 'block' : 'none' }};">
        <label for="session_duration_minutes" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Session duration (minutes) <span class="text-red-500">*</span></label>
        <input type="number" id="session_duration_minutes" name="session_duration_minutes" value="{{ $ud->session_duration_minutes ?? '' }}" placeholder="e.g. 30, 60" min="15" max="480" step="15" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
        <p class="text-on-surface-variant text-xs mt-1">Minimum 15 minutes, maximum 8 hours.</p>
        <p id="session_duration_minutes_error" class="text-error text-xs mt-1 hidden"></p>
      </div>
    </div>

    <div id="consultation_timing_container" style="display: {{ $reqCons ? 'block' : 'none' }};">
      <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-3">Consultation setup <span class="text-red-500">*</span></label>
      <div id="consultation_timing_group" class="flex flex-col gap-3">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="radio" name="consultation_timing" value="combined" class="mt-1 accent-primary" onchange="toggleGapFields()"
            {{ $ct === 'combined' ? 'checked' : '' }}>
          <div>
            <span class="text-sm font-semibold text-on-surface block">Included in tattoo session</span>
            <span class="text-xs text-on-surface-variant">The consultation happens during the tattoo session and counts toward the total session time.</span>
          </div>
        </label>
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="radio" name="consultation_timing" value="separate" class="mt-1 accent-primary" onchange="toggleGapFields()"
            {{ $ct === 'separate' ? 'checked' : '' }}>
          <div>
            <span class="text-sm font-semibold text-on-surface block">Separate consultation session</span>
            <span class="text-xs text-on-surface-variant">The consultation is booked as its own session before the tattoo session.</span>
          </div>
        </label>
      </div>
      <p id="consultation_timing_error" class="text-error text-xs mt-1 hidden"></p>
    </div>

    <div id="gap_fields_container" class="space-y-4 pt-2 border-t border-outline-variant/20" style="display: {{ ($reqCons && $ct === 'separate') ? 'block' : 'none' }};">
      <input type="hidden" id="require_gap_between_consultation_tattoo" name="require_gap_between_consultation_tattoo" value="{{ ($reqCons && $ct === 'separate') ? '1' : '0' }}">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div id="gap_duration_container" style="display: {{ ($reqCons && $ct === 'separate') ? 'block' : 'none' }};">
          <label for="consultation_tattoo_gap_value" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Minimum gap (in days) <span class="text-red-500">*</span></label>
          <input type="number" id="consultation_tattoo_gap_value" name="consultation_tattoo_gap_value" value="{{ $ud->consultation_tattoo_gap_value ?? '' }}" placeholder="e.g. 1, 2, 7" min="1" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
          <p id="consultation_tattoo_gap_value_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>
      <p class="text-on-surface-variant text-xs">Set the minimum time between the consultation and the tattoo session</p>
    </div>
  </div>
</section>
