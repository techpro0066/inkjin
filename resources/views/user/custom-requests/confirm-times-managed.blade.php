@extends('layouts.user_dashboard_layout')

@php
  $artistName = $customRequest->artistDisplayName();
@endphp

@section('title', 'Choose your appointment time')

@section('styles')
@include('user.requests.partials.confirm-times-styles')
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-4xl mx-auto">
    <div class="flex flex-wrap items-center gap-4 mb-6">
      @if ($fromPayment ?? false)
        <a href="{{ route('user.custom-requests.payment', $customRequest) }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to payment
        </a>
      @endif
      <a href="{{ route('user.requests.index', ['tab' => 'custom']) }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Custom requests
      </a>
    </div>

    <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-6 flex gap-4">
      <div class="w-20 h-20 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-primary/15">
        <span class="material-symbols-outlined text-primary text-3xl">brush</span>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-1">
          <span class="inline-flex items-center gap-1.5 status-confirmed text-xs font-semibold px-3 py-1 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full status-dot"></span> Pick your time
          </span>
          <span class="text-xs text-outline">{{ $customRequest->referenceLabel() }}</span>
        </div>
        <h1 class="text-xl font-extrabold text-on-surface tracking-tight">Custom tattoo · {{ $artistName }}</h1>
        <p class="text-sm text-on-surface-variant mt-1">{{ $customRequest->estimatedPriceLabel() }} · {{ $customRequest->checkoutDurationLabel() }}</p>
      </div>
    </div>

    <p class="text-on-surface-variant text-sm mb-6 max-w-2xl">
      {{ $artistName }} shared times that work for your tattoo session. Choose a date and time below — nothing is selected until you pick one.
    </p>

    @unless ($hasSessionSlots)
      <div class="mb-6 rounded-xl border border-outline-variant/30 bg-surface-container-low px-4 py-3 text-sm text-on-surface-variant">
        {{ $artistName }} has not added session time windows yet. Check back on Custom requests once times are available.
      </div>
    @endunless

    <section class="offer-section offer-section--session mb-6 @unless($hasSessionSlots) hidden @endunless" id="sectionSession" data-kind="session">
      <div class="flex items-center gap-2 mb-1">
        <span class="material-symbols-outlined text-primary">brush</span>
        <h2 class="text-lg font-bold text-on-surface">Tattoo session</h2>
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

    <form id="confirmTimesForm" method="POST" action="{{ route('user.custom-requests.confirm-times.store', $customRequest) }}">
      @csrf
      <input type="hidden" name="client_session_slots[0][date]" id="inputSessionDate" value="">
      <input type="hidden" name="client_session_slots[0][ranges][0][from]" id="inputSessionFrom" value="">
      <input type="hidden" name="client_session_slots[0][ranges][0][to]" id="inputSessionTo" value="">

      <div id="confirmBar" class="hidden sticky bottom-4 z-10 bg-white rounded-2xl border border-primary/20 p-4 sm:p-5 shadow-lg shadow-primary/10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="space-y-2">
            <p class="text-sm font-bold text-on-surface">Your selection</p>
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
  var OFFERED = { session: @json($offeredSession) };
  var SAVED = @json($savedSelection);
  var state = { session: { date: null, slot: null } };

  function restoreSavedSelection() {
    if (!SAVED || !SAVED.date || !SAVED.from || !SAVED.to) return;
    var slots = OFFERED.session[SAVED.date] || [];
    var idx = slots.findIndex(function(s) {
      return s.from === SAVED.from && s.to === SAVED.to;
    });
    if (idx < 0) return;
    selectDate('session', SAVED.date);
    selectSlot('session', SAVED.date, idx);
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

  function renderOfferedDates(kind) {
    var listEl = document.querySelector('[data-offered-dates="' + kind + '"]');
    var noDatesEl = document.querySelector('[data-no-dates="' + kind + '"]');
    if (!listEl) return;
    var dates = Object.keys(OFFERED[kind] || {}).sort();
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
        '<span class="offered-date-sub">' + formatDateYear(dateKey) + '</span></button>';
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
    updateUI();
  }

  function selectSlot(kind, dateKey, idx) {
    var slots = OFFERED[kind][dateKey] || [];
    state[kind].slot = slots[idx] || null;
    document.querySelectorAll('[data-time-slots="' + kind + '"] .time-slot-card').forEach(function(btn, i) {
      btn.classList.toggle('selected', i === idx);
    });
    updateUI();
  }

  function syncHiddenInputs() {
    if (state.session.date && state.session.slot) {
      document.getElementById('inputSessionDate').value = state.session.date;
      document.getElementById('inputSessionFrom').value = state.session.slot.from;
      document.getElementById('inputSessionTo').value = state.session.slot.to;
    }
  }

  function updateUI() {
    var sessionOk = state.session.date && state.session.slot;
    var bar = document.getElementById('confirmBar');
    var btn = document.getElementById('btnConfirmTimes');
    var list = document.getElementById('confirmSummaryList');
    if (!bar || !btn || !list) return;
    if (sessionOk) bar.classList.remove('hidden');
    list.innerHTML = sessionOk
      ? '<span class="flex items-center gap-2"><span class="material-symbols-outlined text-sm text-primary">brush</span> Tattoo session: ' + formatDateLabel(state.session.date) + ' · ' + state.session.slot.label + '</span>'
      : '';
    btn.disabled = !sessionOk;
    btn.classList.toggle('opacity-60', !sessionOk);
    btn.classList.toggle('cursor-not-allowed', !sessionOk);
    syncHiddenInputs();
  }

  renderOfferedDates('session');
  restoreSavedSelection();
})();
</script>
@endsection
