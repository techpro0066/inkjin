@extends('layouts.user_dashboard_layout')

@php
  $artistName = $bookingRequest->artistDisplayName();
  $designImage = $bookingRequest->designImageUrl();
  $artistNotes = trim((string) ($bookingRequest->artist_notes_to_client ?? ''));
  $hasSessionSlots = !empty($offeredSession);
@endphp

@section('title', 'Choose your appointment times')

@section('styles')
<style>
  .picker-card {
    background: white; border-radius: 1rem; border: 1px solid #e6e0ea;
    box-shadow: 0 4px 24px rgba(49,15,122,0.06); padding: 1rem;
  }
  .picker-split {
    display: flex; flex-direction: column; gap: 1rem;
  }
  @media (min-width: 640px) {
    .picker-split {
      flex-direction: row; align-items: stretch; gap: 0;
      min-height: 11rem;
    }
    .picker-dates-col {
      flex: 0 0 38%; max-width: 15rem;
      padding-right: 1rem;
      border-right: 1px solid rgba(202, 196, 211, 0.45);
    }
    .picker-times-col {
      flex: 1; min-width: 0; padding-left: 1rem;
      display: flex; flex-direction: column;
    }
  }
  .picker-step-label {
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: #7a7583; margin-bottom: 0.65rem;
  }
  .offered-dates-list {
    display: flex; flex-direction: column; gap: 0.45rem;
    max-height: 11rem; overflow-y: auto; padding-right: 0.25rem;
  }
  .offered-date-btn {
    display: flex; flex-direction: column; align-items: flex-start;
    width: 100%; padding: 0.6rem 0.85rem; border-radius: 0.75rem;
    border: 1.5px solid #cac4d3; background: white; cursor: pointer;
    transition: all 0.15s; text-align: left; flex-shrink: 0;
  }
  .offered-date-btn:hover { border-color: #310f7a; background: #f8f1fb; }
  .offered-date-btn.selected {
    background: #310f7a; border-color: #310f7a; color: white;
  }
  .offered-date-btn.selected .offered-date-sub { color: rgba(255,255,255,0.85); }
  .offered-date-main { font-size: 0.85rem; font-weight: 700; line-height: 1.2; }
  .offered-date-sub { font-size: 0.68rem; font-weight: 500; color: #7a7583; margin-top: 0.1rem; }
  .picker-times-filled {
    display: flex; flex-direction: column; flex: 1; min-height: 0;
  }
  .picker-times-scroll {
    max-height: 9.5rem; overflow-y: auto; padding-right: 0.25rem;
  }
  .picker-times-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    flex: 1; min-height: 9rem; text-align: center; color: #7a7583;
    padding: 0.5rem;
  }
  @media (min-width: 640px) {
    .picker-times-empty { min-height: 8.5rem; }
  }
  .time-slot-card {
    padding: 0.75rem 1.25rem; border-radius: 0.75rem; border: 1.5px solid #cac4d3;
    font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.15s;
    text-align: center; color: #310f7a; background: white; width: 100%;
  }
  .time-slot-card:hover { border-color: #310f7a; background: #f8f1fb; }
  .time-slot-card.selected { background: #310f7a; color: white; border-color: #310f7a; }
  .time-slot-card:disabled { opacity: 0.45; cursor: not-allowed; }
  .confirm-chip {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.5rem 0.85rem; border-radius: 9999px;
    background: #f0fdf4; color: #15803d; border: 1px solid rgba(34, 197, 94, 0.35);
    font-size: 0.8rem; font-weight: 600;
  }
  .offer-section--consult {
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border: 1px solid rgba(139, 92, 246, 0.28);
    border-radius: 1rem; padding: 1.25rem;
  }
  .offer-section--session {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 1px solid rgba(34, 197, 94, 0.28);
    border-radius: 1rem; padding: 1.25rem;
  }
  .offer-section.is-locked { opacity: 0.55; pointer-events: none; position: relative; }
  .offer-section.is-locked::after {
    content: 'Complete consultation above first';
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.65); border-radius: 1rem;
    font-size: 0.875rem; font-weight: 600; color: #310f7a;
  }
  .progress-step { flex: 1; max-width: 8rem; }
  .progress-step .step-dot {
    width: 2rem; height: 2rem; border-radius: 50%; border: 2px solid #cac4d3;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 700; color: #7a7583; background: #fff;
  }
  .progress-step.active .step-dot { border-color: #310f7a; background: #310f7a; color: #fff; }
  .progress-step.done .step-dot { border-color: #22c55e; background: #22c55e; color: #fff; }
  .progress-line { flex: 1; height: 2px; background: #e6e0ea; margin-top: 1rem; max-width: 4rem; }
  .progress-line.done { background: #22c55e; }
  .slide-in-right { animation: slideInRight 0.25s ease-out; }
  @keyframes slideInRight { from { opacity: 0; transform: translateX(8px); } to { opacity: 1; transform: translateX(0); } }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-4xl mx-auto">

    <div class="flex flex-wrap items-center gap-4 mb-6">
      @if ($canPay ?? false)
        <a href="{{ route('user.requests.payment', $bookingRequest) }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to payment
        </a>
      @endif
      <a href="{{ route('user.requests.index') }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> My Requests
      </a>
    </div>

    <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-6 flex flex-col sm:flex-row gap-4">
      <div class="w-20 h-20 rounded-xl bg-surface-container overflow-hidden flex-shrink-0 border border-outline-variant/20 flex items-center justify-center">
        @if ($designImage)
          <img src="{{ $designImage }}" alt="" class="w-full h-full object-cover">
        @else
          <span class="material-symbols-outlined text-outline text-3xl">palette</span>
        @endif
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-1">
          <span class="inline-flex items-center gap-1.5 status-confirmed text-xs font-semibold px-3 py-1 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full status-dot"></span> Pick your times
          </span>
          <span class="text-xs text-outline">{{ $bookingRequest->referenceLabel() }}</span>
        </div>
        <h1 class="text-xl font-extrabold text-on-surface tracking-tight">{{ $bookingRequest->tattoo?->title ?? 'Design' }}</h1>
        <p class="text-sm text-on-surface-variant mt-1">with <strong>{{ $artistName }}</strong> · {{ $bookingRequest->schedulingLabel() }} · {{ $bookingRequest->priceLabel() }}</p>
      </div>
    </div>

    <p class="text-on-surface-variant text-sm mb-6 max-w-2xl">
      {{ $artistName }} shared times that work for them.
      @if ($hasConsult)
        Choose one window for your consultation, then one for your tattoo session.
      @else
        Choose a date and time for your tattoo session.
      @endif
    </p>

    @unless ($hasSessionSlots)
      <div class="mb-6 rounded-xl border border-outline-variant/30 bg-surface-container-low px-4 py-3 text-sm text-on-surface-variant">
        {{ $artistName }} has not added session time windows yet. Check back on My Requests once times are available.
      </div>
    @endunless

    <div class="flex items-start justify-center gap-2 mb-8 max-w-md mx-auto @if(!$hasConsult) hidden @endif" id="stepProgress">
      <div class="progress-step active text-center" data-step="consult">
        <div class="step-dot mx-auto">1</div>
        <p class="text-xs font-semibold text-on-surface mt-2">Consultation</p>
      </div>
      <div class="progress-line" data-line="1"></div>
      <div class="progress-step text-center" data-step="session">
        <div class="step-dot mx-auto">2</div>
        <p class="text-xs font-semibold text-on-surface-variant mt-2">Tattoo session</p>
      </div>
    </div>

    <section class="offer-section offer-section--consult mb-6 @if(!$hasConsult) hidden @endif" id="sectionConsult" data-kind="consult">
      <div class="flex items-center gap-2 mb-1">
        <span class="material-symbols-outlined text-primary">groups</span>
        <h2 class="text-lg font-bold text-on-surface">Consultation</h2>
        <span class="text-xs font-semibold text-on-surface-variant ml-auto">{{ $bookingRequest->consultationTypeLabel() }}</span>
      </div>
      <p class="text-sm text-on-surface-variant mb-4">Pick a date and time from {{ $artistName }}'s offered consultation windows.</p>

      @include('user.requests.partials.time-picker', ['kind' => 'consult'])
    </section>

    <section class="offer-section offer-section--session mb-6 @if($hasConsult) is-locked @endif @unless($hasSessionSlots) hidden @endunless" id="sectionSession" data-kind="session">
      <div class="flex items-center gap-2 mb-1">
        <span class="material-symbols-outlined text-primary">brush</span>
        <h2 class="text-lg font-bold text-on-surface">Tattoo session</h2>
        <span class="text-xs font-semibold text-on-surface-variant ml-auto">{{ $bookingRequest->sessionDurationLabel() }}</span>
      </div>
      <p class="text-sm text-on-surface-variant mb-4">Pick a date and time from {{ $artistName }}'s offered session windows.</p>

      @include('user.requests.partials.time-picker', ['kind' => 'session'])
    </section>

    @if ($artistNotes !== '')
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-6">
        <h3 class="text-sm font-bold text-on-surface mb-2 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary text-lg">chat</span> Message from artist
        </h3>
        <p class="text-sm text-on-surface-variant leading-relaxed whitespace-pre-line">{{ $artistNotes }}</p>
      </div>
    @endif

    <form id="confirmTimesForm" method="POST" action="{{ route('user.requests.confirm-times.store', $bookingRequest) }}">
      @csrf
      <input type="hidden" name="client_session_slots[0][date]" id="inputSessionDate" value="">
      <input type="hidden" name="client_session_slots[0][ranges][0][from]" id="inputSessionFrom" value="">
      <input type="hidden" name="client_session_slots[0][ranges][0][to]" id="inputSessionTo" value="">
      @if ($hasConsult)
        <input type="hidden" name="client_consultation_slots[0][date]" id="inputConsultDate" value="">
        <input type="hidden" name="client_consultation_slots[0][ranges][0][from]" id="inputConsultFrom" value="">
        <input type="hidden" name="client_consultation_slots[0][ranges][0][to]" id="inputConsultTo" value="">
      @endif

    <div id="confirmBar" class="hidden sticky bottom-4 z-10 bg-white rounded-2xl border border-primary/20 p-4 sm:p-5 shadow-lg shadow-primary/10">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="space-y-2">
          <p class="text-sm font-bold text-on-surface">Your selections</p>
          <div id="confirmSummaryList" class="flex flex-col gap-1 text-sm text-on-surface-variant"></div>
        </div>
        <button type="submit" form="confirmTimesForm" class="w-full sm:w-auto px-8 py-3 bg-primary text-white rounded-xl font-bold text-sm hover:bg-primary-container transition-colors shadow-md shadow-primary/20 flex items-center justify-center gap-2 opacity-60 cursor-not-allowed" id="btnConfirmTimes" disabled>
          Continue to payment
          <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </button>
      </div>
    </div>
    </form>

  </div>
</main>
@endsection

@section('scripts')
<script>
(function() {
  var OFFERED = {
    consult: @json($offeredConsult),
    session: @json($offeredSession)
  };
  var HAS_CONSULT = @json($hasConsult);
  var SAVED = @json($savedSelections ?? ['consult' => null, 'session' => null]);
  var state = {
    consult: { date: null, slot: null },
    session: { date: null, slot: null }
  };

  function restoreSavedSelection(kind) {
    var saved = SAVED[kind];
    if (!saved || !saved.date || !saved.from || !saved.to) return;

    var slots = OFFERED[kind][saved.date] || [];
    var idx = slots.findIndex(function(s) {
      return s.from === saved.from && s.to === saved.to;
    });
    if (idx < 0) return;

    selectDate(kind, saved.date);
    selectSlot(kind, saved.date, idx);
  }

  function formatDateLabel(ymdStr) {
    var p = ymdStr.split('-');
    var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    return d.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
  }

  function formatDateShort(ymdStr) {
    var p = ymdStr.split('-');
    var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
  }

  function formatDateYear(ymdStr) {
    var p = ymdStr.split('-');
    var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    return d.toLocaleDateString(undefined, { year: 'numeric' });
  }

  function offeredDateKeys(kind) {
    return Object.keys(OFFERED[kind] || {}).sort();
  }

  function renderOfferedDates(kind) {
    var listEl = document.querySelector('[data-offered-dates="' + kind + '"]');
    var noDatesEl = document.querySelector('[data-no-dates="' + kind + '"]');
    if (!listEl) return;

    var dates = offeredDateKeys(kind);
    if (!dates.length) {
      listEl.innerHTML = '';
      noDatesEl?.classList.remove('hidden');
      return;
    }
    noDatesEl?.classList.add('hidden');

    listEl.innerHTML = dates.map(function(dateKey) {
      var selected = state[kind].date === dateKey;
      return '<button type="button" class="offered-date-btn' + (selected ? ' selected' : '') + '" data-kind="' + kind + '" data-date="' + dateKey + '">' +
        '<span class="offered-date-main">' + formatDateShort(dateKey) + '</span>' +
        '<span class="offered-date-sub">' + formatDateYear(dateKey) + '</span>' +
      '</button>';
    }).join('');

    listEl.querySelectorAll('.offered-date-btn').forEach(function(btn) {
      btn.addEventListener('click', function() { selectDate(kind, btn.dataset.date); });
    });
  }

  function selectDate(kind, dateKey) {
    state[kind].date = dateKey;
    state[kind].slot = null;
    renderOfferedDates(kind);

    var slots = OFFERED[kind][dateKey] || [];
    var emptyEl = document.querySelector('[data-time-empty="' + kind + '"]');
    var contentEl = document.querySelector('[data-time-content="' + kind + '"]');
    var slotsEl = document.querySelector('[data-time-slots="' + kind + '"]');
    var dateLabel = document.querySelector('[data-selected-date-label="' + kind + '"]');

    if (!slots.length) {
      emptyEl?.classList.remove('hidden');
      contentEl?.classList.add('hidden');
      return;
    }

    emptyEl?.classList.add('hidden');
    contentEl?.classList.remove('hidden');
    if (dateLabel) dateLabel.textContent = formatDateLabel(dateKey);

    slotsEl.innerHTML = slots.map(function(s, idx) {
      return '<button type="button" class="time-slot-card" data-kind="' + kind + '" data-slot-idx="' + idx + '">' + s.label + '</button>';
    }).join('');

    slotsEl.querySelectorAll('.time-slot-card').forEach(function(btn) {
      btn.addEventListener('click', function() { selectSlot(kind, dateKey, parseInt(btn.dataset.slotIdx, 10)); });
    });

    document.querySelector('[data-selection-chip="' + kind + '"]')?.classList.add('hidden');
    updateUI();
  }

  function selectSlot(kind, dateKey, idx) {
    var slots = OFFERED[kind][dateKey] || [];
    state[kind].slot = slots[idx] || null;
    document.querySelectorAll('[data-time-slots="' + kind + '"] .time-slot-card').forEach(function(btn, i) {
      btn.classList.toggle('selected', i === idx);
    });
    var chip = document.querySelector('[data-selection-chip="' + kind + '"]');
    var chipText = document.querySelector('[data-chip-text="' + kind + '"]');
    if (chip && chipText && state[kind].slot) {
      chip.classList.remove('hidden');
      var prefix = kind === 'consult' ? 'Consultation' : 'Tattoo session';
      chipText.textContent = prefix + ': ' + formatDateLabel(dateKey) + ' · ' + state[kind].slot.label;
    }
    if (kind === 'consult') {
      document.getElementById('sectionSession')?.classList.remove('is-locked');
      document.querySelector('[data-step="consult"]')?.classList.add('done');
      document.querySelector('[data-step="session"]')?.classList.add('active');
      document.querySelector('[data-line="1"]')?.classList.add('done');
    }
    updateUI();
  }

  function updateUI() {
    var consultOk = !HAS_CONSULT || (state.consult.date && state.consult.slot);
    var needsSession = Object.keys(OFFERED.session || {}).length > 0;
    var sessionOk = !needsSession || (state.session.date && state.session.slot);
    var bar = document.getElementById('confirmBar');
    var btn = document.getElementById('btnConfirmTimes');
    var list = document.getElementById('confirmSummaryList');
    if (!bar || !btn || !list) return;

    if (consultOk || sessionOk) bar.classList.remove('hidden');
    var lines = [];
    if (HAS_CONSULT && state.consult.slot) {
      lines.push('<span class="flex items-center gap-2"><span class="material-symbols-outlined text-sm text-primary">groups</span> Consultation: ' + formatDateLabel(state.consult.date) + ' · ' + state.consult.slot.label + '</span>');
    }
    if (state.session.slot) {
      lines.push('<span class="flex items-center gap-2"><span class="material-symbols-outlined text-sm text-primary">brush</span> Tattoo session: ' + formatDateLabel(state.session.date) + ' · ' + state.session.slot.label + '</span>');
    }
    list.innerHTML = lines.join('');

    var allOk = consultOk && sessionOk;
    btn.disabled = !allOk;
    btn.classList.toggle('opacity-60', !allOk);
    btn.classList.toggle('cursor-not-allowed', !allOk);
    if (allOk) {
      btn.classList.remove('opacity-60', 'cursor-not-allowed');
    }
    syncHiddenInputs();
  }

  function syncHiddenInputs() {
    if (state.session.date && state.session.slot) {
      document.getElementById('inputSessionDate').value = state.session.date;
      document.getElementById('inputSessionFrom').value = state.session.slot.from;
      document.getElementById('inputSessionTo').value = state.session.slot.to;
    }
    if (HAS_CONSULT && state.consult.date && state.consult.slot) {
      var d = document.getElementById('inputConsultDate');
      var f = document.getElementById('inputConsultFrom');
      var t = document.getElementById('inputConsultTo');
      if (d) d.value = state.consult.date;
      if (f) f.value = state.consult.slot.from;
      if (t) t.value = state.consult.slot.to;
    }
  }

  document.getElementById('confirmTimesForm')?.addEventListener('submit', function(e) {
    syncHiddenInputs();
    var consultOk = !HAS_CONSULT || (state.consult.date && state.consult.slot);
    var needsSession = Object.keys(OFFERED.session || {}).length > 0;
    var sessionOk = !needsSession || (state.session.date && state.session.slot);
    if (!consultOk || !sessionOk) {
      e.preventDefault();
    }
  });

  if (!HAS_CONSULT) {
    document.getElementById('sectionConsult')?.classList.add('hidden');
    document.getElementById('sectionSession')?.classList.remove('is-locked');
    document.getElementById('stepProgress')?.classList.add('hidden');
  }

  renderOfferedDates('consult');
  renderOfferedDates('session');
  restoreSavedSelection('consult');
  restoreSavedSelection('session');
})();
</script>
@endsection
