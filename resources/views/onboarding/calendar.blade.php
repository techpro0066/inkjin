@extends('layouts.onboarding_bookpay')

@section('title', 'Calendar')

@php
  $ud = $userDetail;
  $st = $ud->scheduling_type ?? '';
  $defaultSched = $st !== '' ? $st : 'auto';
  $isAuto = $defaultSched === 'auto';
  $gcal = !empty($ud->google_calendar_token);
@endphp

@section('content')
<form id="calendarForm" class="contents">
  @csrf
  <input type="hidden" name="scheduling_type" id="scheduling_type" value="{{ $defaultSched }}" />

  <div class="flex-1 p-8 md:p-12 max-w-4xl w-full">
    <div class="mb-10">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">How do you want to manage your time?</h2>
      <p class="text-on-surface-variant mt-2 max-w-lg">Select your scheduling model. This determines how clients book appointments with you.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="schedule-card {{ $isAuto ? 'selected' : '' }}" onclick="selectSchedule('auto', this)" id="card-auto" role="button" tabindex="0">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">calendar_month</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Auto Scheduling</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Sync your Google Calendar to automatically block off busy times and let clients book into open slots instantly.</p>

        <div id="google-connect" class="mb-6" style="display: {{ $isAuto ? 'block' : 'none' }}">
          @if($gcal)
            <p class="text-sm text-green-700 mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-lg">check_circle</span> Google Calendar connected</p>
            <button type="button" id="disconnectCalendarBtn" class="inline-flex items-center gap-2 rounded-xl border border-error/40 text-error font-semibold py-2.5 px-5 text-sm hover:bg-error/5">Disconnect</button>
          @else
            <button type="button" id="connectCalendarBtn" class="inline-flex items-center gap-3 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98] text-sm">
              <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
              Connect Google Calendar
            </button>
          @endif
        </div>

        <span class="inline-block text-[10px] uppercase tracking-[1.5px] font-bold text-primary">Most Efficient</span>
      </div>

      <div class="schedule-card {{ $defaultSched === 'managed' ? 'selected' : '' }}" onclick="selectSchedule('managed', this)" id="card-managed" role="button" tabindex="0">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">edit_calendar</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Managed Scheduling</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Total manual control. Review every request before it hits your books. Ideal for high-detail custom work.</p>
        <span class="inline-block text-[10px] uppercase tracking-[1.5px] font-bold text-primary">Most Control</span>
      </div>
    </div>

    <p id="scheduling_type_error" class="text-error text-sm mt-4 hidden"></p>
    <div id="calAlert" class="hidden rounded-xl px-4 py-3 text-sm mt-4"></div>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex items-center justify-between mt-auto">
    <a href="{{ route('onboarding.preferences') }}" class="inline-flex items-center gap-1 text-on-surface font-semibold hover:text-primary transition-colors">
      <span class="material-symbols-outlined text-lg">arrow_back</span> Back
    </a>
    <button type="submit" id="calSubmit" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
      Next Step <span class="material-symbols-outlined text-lg">arrow_forward</span>
    </button>
  </div>
</form>

<div id="disconnectCalendarModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
    <h5 class="text-lg font-bold text-on-surface mb-2">Disconnect calendar?</h5>
    <p class="text-on-surface-variant text-sm mb-6">You can reconnect later.</p>
    <div class="flex justify-end gap-3">
      <button type="button" id="cancelDisconnectCal" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">Cancel</button>
      <button type="button" id="confirmDisconnectBtn" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-error text-white hover:opacity-90">Disconnect</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function selectSchedule(type, el) {
  $('.schedule-card').removeClass('selected');
  $(el).addClass('selected');
  $('#scheduling_type').val(type);
  $('#google-connect').toggle(type === 'auto');
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('scheduling_type');
  if (typeof window.clearOnboardingAlert === 'function') window.clearOnboardingAlert('calAlert');
}
$(function () {
  $('#connectCalendarBtn').on('click', function () {
    window.location.href = @json(route('google.calendar.redirect'));
  });
  function openCalModal() {
    $('#disconnectCalendarModal').removeClass('hidden');
  }
  function closeCalModal() {
    $('#disconnectCalendarModal').addClass('hidden');
  }
  $('#disconnectCalendarBtn').on('click', openCalModal);
  $('#cancelDisconnectCal').on('click', closeCalModal);
  $('#disconnectCalendarModal').on('click', function (e) {
    if (e.target === this) closeCalModal();
  });
  $('#confirmDisconnectBtn').on('click', function () {
    closeCalModal();
    $.ajax({
      url: @json(route('google.calendar.disconnect')),
      type: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json',
      },
    }).done(function (data) {
      if (data.success) window.location.reload();
    });
  });

  function showCalAlert(msg) {
    var $alertEl = $('#calAlert');
    $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
    $alertEl.text(msg).removeClass('hidden');
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('calendarForm'));
    }
  }

  $('#calendarForm').on('submit', function (e) {
    e.preventDefault();
    var st = $('#scheduling_type').val();
    var $alertEl = $('#calAlert');
    var $errEl = $('#scheduling_type_error');
    if (!st) {
      $errEl.text('Choose a scheduling model.').removeClass('hidden');
      if (typeof window.scrollToFirstOnboardingError === 'function') {
        window.scrollToFirstOnboardingError(document.getElementById('calendarForm'));
      }
      return;
    }
    $errEl.addClass('hidden');
    $alertEl.addClass('hidden');
    var $btn = $('#calSubmit');
    $btn.prop('disabled', true);
    var fd = new FormData(this);
    $.ajax({
      url: @json(route('onboarding.calendar.save')),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json',
      },
    })
      .done(function (data) {
        if (data.success && data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        var first = data.errors ? $.map(data.errors, function (v) { return v; })[0] : null;
        showCalAlert(first || data.message || 'Could not save');
      })
      .fail(function (xhr) {
        var msg = 'Network error';
        if (xhr.status === 422 && xhr.responseJSON) {
          if (xhr.responseJSON.errors) {
            var flat = [];
            $.each(xhr.responseJSON.errors, function (_, v) {
              flat.push(v[0]);
            });
            msg = flat[0] || xhr.responseJSON.message || msg;
          } else if (xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
          }
        }
        showCalAlert(msg);
      })
      .always(function () {
        $btn.prop('disabled', false);
      });
  });
});
</script>
@endpush
