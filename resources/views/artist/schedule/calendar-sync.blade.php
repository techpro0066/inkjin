@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Schedule')

@php
  $ud = $userDetail ?? auth()->user()?->userDetail;
  $st = $ud->scheduling_type ?? '';
  $gcal = ! empty($ud->google_calendar_token);
  $defaultSched = $st !== '' ? $st : ($gcal ? 'auto' : 'managed');
  $isAuto = $defaultSched === 'auto';
  $connectedCalendarEmail = $gcal ? $ud?->resolvedGoogleCalendarEmail() : null;
@endphp

@section('styles')
<style>
  .cs-page .po{
    display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--line);
    border-radius:14px;padding:16px 18px;cursor:pointer;margin-bottom:0;transition:border-color .15s,background .15s,padding .15s
  }
  .cs-page .po.sel{border:2px solid #3E007C;padding:15px 17px;background:#FDFAFF}
  .cs-page .po input[type="radio"]{
    -webkit-appearance:none;appearance:none;width:18px;height:18px;margin:2px 0 0;flex-shrink:0;
    border:2px solid #D5CDD9;border-radius:50%;background:#fff;cursor:pointer;position:relative
  }
  .cs-page .po input[type="radio"]:checked{
    border-color:#3E007C;background:#3E007C;box-shadow:none
  }
  .cs-page .po input[type="radio"]:checked::after{
    content:'';position:absolute;top:50%;left:50%;width:6px;height:6px;
    margin:-3px 0 0 -3px;border-radius:50%;background:#fff
  }
  .cs-page .po b{font-size:14.5px;display:block;font-weight:700}
  .cs-page .po .d{font-size:13px;color:var(--muted);margin-top:3px;line-height:1.5}
  .cs-page .tgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
  .cs-page .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .cs-page .field-err[hidden]{display:none!important}
  .cs-page .mp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .cs-page .mp-toast.on{opacity:1;transform:none}
  .cs-page .mp-toast .ms{color:#3DD68C;font-size:20px}
  .cs-page .ov{position:fixed;inset:0;background:rgba(26,26,26,.45);z-index:300;display:flex;align-items:center;justify-content:center;padding:24px}
  .cs-page .ov[hidden]{display:none!important}
  .cs-page .modal{background:#fff;border-radius:16px;border:1px solid var(--line);max-width:420px;width:100%;padding:22px 24px;box-shadow:0 18px 50px rgba(0,0,0,.18)}
  @media (max-width:700px){
    .cs-page .tgrid{grid-template-columns:1fr}
    .cs-page .mp-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
<div class="cs-page">
  <div class="head">
    <div>
      <h1>
        Schedule
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201078-dashboard-schedule-calendar-sync" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">When and how clients can book you.</div>
    </div>
  </div>

  @include('artist.partials.schedule-tabs', ['scheduleTab' => 'calendar-sync'])

  <form id="calendarSyncForm">
    @csrf
    <input type="hidden" name="scheduling_type" id="scheduling_type" value="{{ $defaultSched }}">

    <div class="tgrid">
      <label class="po {{ $isAuto ? 'sel' : '' }}">
        <input type="radio" name="sched" value="auto" {{ $isAuto ? 'checked' : '' }}>
        <div style="flex:1">
          <b><span class="ms" style="vertical-align:-4px">calendar_month</span> Auto scheduling</b>
          <div class="d">
            Sync your Google Calendar to block busy times and let clients book open slots instantly.
            <div class="lbl" style="margin-top:10px">Most efficient</div>
          </div>
        </div>
      </label>
      <label class="po {{ ! $isAuto ? 'sel' : '' }}">
        <input type="radio" name="sched" value="managed" {{ ! $isAuto ? 'checked' : '' }}>
        <div style="flex:1">
          <b><span class="ms" style="vertical-align:-4px">edit_calendar</span> Managed scheduling</b>
          <div class="d">
            Review every request before it reaches your books. Ideal for detailed custom work.
            <div class="lbl" style="margin-top:10px">Most control</div>
          </div>
        </div>
      </label>
    </div>
    <p class="field-err" id="scheduling_type_error" hidden></p>

    <div id="gcal" style="margin-bottom:16px" @if(! $isAuto) hidden @endif>
      <div class="card pad row" style="gap:14px" id="gcalCard">
        <div class="ic"><span class="ms">event</span></div>
        <div style="flex:1;min-width:0">
          <div class="row" style="gap:8px;flex-wrap:wrap">
            <b id="gcalTitle">{{ $gcal ? 'Google Calendar connected' : 'Connect Google Calendar' }}</b>
            @if($gcal)
              <span class="pill g" id="gcalBadge">Connected</span>
            @else
              <span class="pill a" id="gcalBadge">Not connected</span>
            @endif
          </div>
          <div class="faint" style="font-size:12.5px;margin-top:2px" id="gcalHelp">
            @if($gcal)
              Busy times in your calendar are blocked so clients only see free slots.
            @else
              Needed for Auto scheduling. Busy times in your calendar are blocked so clients only see free slots.
            @endif
          </div>
          @if($gcal && $connectedCalendarEmail)
            <div class="faint" style="font-size:12.5px;margin-top:6px" id="gcalEmailLine">
              Connected account: <b style="color:var(--ink);font-weight:600">{{ $connectedCalendarEmail }}</b>
            </div>
          @endif
        </div>
        <div id="gcalActionWrap">
          @if($gcal)
            <button type="button" class="btn ghost" id="disconnectCalendarBtn"><span class="ms">link_off</span>Disconnect</button>
          @else
            <button type="button" class="btn" id="connectCalendarBtn"><span class="ms">link</span>Connect Google Calendar</button>
          @endif
        </div>
      </div>
      <p class="field-err" id="google_calendar_connected_error" hidden></p>
    </div>
    <script>
    (function () {
      function syncSchedUi() {
        var checked = document.querySelector('input[name="sched"]:checked');
        var type = checked ? checked.value : 'managed';
        var auto = type === 'auto';
        var gcal = document.getElementById('gcal');
        if (gcal) gcal.hidden = !auto;
        var hidden = document.getElementById('scheduling_type');
        if (hidden) hidden.value = type;
        document.querySelectorAll('.cs-page .po').forEach(function (po) {
          var input = po.querySelector('input[name="sched"]');
          po.classList.toggle('sel', !!(input && input.checked));
        });
      }
      document.querySelectorAll('input[name="sched"]').forEach(function (radio) {
        radio.addEventListener('change', syncSchedUi);
      });
      syncSchedUi();
      window.__csSyncSchedUi = syncSchedUi;
    })();
    </script>

    <div class="row" style="justify-content:flex-start;margin-top:4px">
      <button type="submit" class="btn" id="btnSaveCalendarSync"><span class="ms">save</span>Save changes</button>
    </div>
    <p class="field-err" id="calendarSyncBanner" hidden style="margin-top:10px"></p>
  </form>

  <div class="ov" id="disconnectCalendarModal" hidden role="dialog" aria-modal="true" aria-labelledby="disconnectCalTitle">
    <div class="modal">
      <h3 id="disconnectCalTitle" style="margin-bottom:8px">Disconnect calendar?</h3>
      <p class="muted" style="font-size:13.5px;line-height:1.5;margin:0 0 18px">
        This will switch you to Managed scheduling automatically. You can reconnect Google Calendar later to use Auto scheduling again.
      </p>
      <div class="row" style="justify-content:flex-end;gap:10px">
        <button type="button" class="btn ghost" id="cancelDisconnectCal">Cancel</button>
        <button type="button" class="btn" id="confirmDisconnectBtn" style="background:#C62828"><span class="ms">link_off</span>Disconnect</button>
      </div>
    </div>
  </div>

  <div class="mp-toast" id="mpToast" role="status"><span class="ms">check_circle</span><span id="mpToastText">Saved</span></div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script>
(function () {
  var googleCalendarRedirectUrl = @json(route('google.calendar.redirect', ['return_to' => 'schedule']));
  var gcalConnected = @json($gcal);
  var connectedEmail = @json($connectedCalendarEmail);

  function mpToast(msg) {
    var $t = $('#mpToast');
    if (!$t.length) return;
    $('#mpToastText').text(msg);
    $t.addClass('on');
    clearTimeout($t.data('t'));
    $t.data('t', setTimeout(function () { $t.removeClass('on'); }, 2400));
  }

  function clearErrors() {
    $('.field-err').prop('hidden', true).text('');
  }

  function showErrors(errors) {
    Object.keys(errors || {}).forEach(function (key) {
      var msg = Array.isArray(errors[key]) ? errors[key][0] : String(errors[key]);
      var $el = $('#' + key + '_error');
      if ($el.length) {
        $el.text(msg).prop('hidden', false);
      } else {
        $('#calendarSyncBanner').text(msg).prop('hidden', false);
      }
    });
  }

  function syncPo() {
    $('input[name="sched"]').each(function () {
      $(this).closest('.po').toggleClass('sel', this.checked);
    });
  }

  function selectedType() {
    return $('input[name="sched"]:checked').val() || 'managed';
  }

  function syncGcalVisibility() {
    var auto = selectedType() === 'auto';
    $('#gcal').prop('hidden', !auto);
    $('#scheduling_type').val(selectedType());
  }

  function bindConnectButton() {
    $('#connectCalendarBtn').off('click').on('click', function () {
      window.location.href = googleCalendarRedirectUrl;
    });
  }

  function openCalModal() {
    $('#disconnectCalendarModal').prop('hidden', false);
  }

  function closeCalModal() {
    $('#disconnectCalendarModal').prop('hidden', true);
  }

  function bindDisconnectButton() {
    $('#disconnectCalendarBtn').off('click').on('click', openCalModal);
  }

  function markCalendarDisconnectedUi() {
    gcalConnected = false;
    connectedEmail = null;
    $('#gcalTitle').text('Connect Google Calendar');
    $('#gcalBadge').attr('class', 'pill a').text('Not connected');
    $('#gcalHelp').text('Needed for Auto scheduling. Busy times in your calendar are blocked so clients only see free slots.');
    $('#gcalEmailLine').remove();
    $('#gcalActionWrap').html('<button type="button" class="btn" id="connectCalendarBtn"><span class="ms">link</span>Connect Google Calendar</button>');
    bindConnectButton();
  }

  function switchToManagedUi() {
    $('input[name="sched"][value="managed"]').prop('checked', true);
    syncPo();
    syncGcalVisibility();
  }

  $('input[name="sched"]').on('change', function () {
    syncPo();
    syncGcalVisibility();
    clearErrors();
  });

  // Sync UI to whatever is already selected (e.g. user clicked Auto before scripts loaded).
  syncPo();
  syncGcalVisibility();
  if (typeof window.__csSyncSchedUi === 'function') window.__csSyncSchedUi();

  bindConnectButton();
  bindDisconnectButton();
  $('#cancelDisconnectCal').on('click', closeCalModal);
  $('#disconnectCalendarModal').on('click', function (e) {
    if (e.target === this) closeCalModal();
  });

  $('#confirmDisconnectBtn').on('click', function () {
    closeCalModal();
    var $btn = $(this);
    $btn.prop('disabled', true);
    $.ajax({
      url: @json(route('google.calendar.disconnect')),
      type: 'POST',
      data: { _token: @json(csrf_token()) },
      headers: {
        'X-CSRF-TOKEN': @json(csrf_token()),
        Accept: 'application/json'
      }
    }).done(function (data) {
      if (data.success) {
        markCalendarDisconnectedUi();
        switchToManagedUi();
        mpToast(data.message || 'Google Calendar disconnected. Managed scheduling is now active.');
      } else {
        $('#calendarSyncBanner').text(data.message || 'Failed to disconnect Google Calendar').prop('hidden', false);
      }
    }).fail(function (xhr) {
      $('#calendarSyncBanner').text((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to disconnect Google Calendar').prop('hidden', false);
    }).always(function () {
      $btn.prop('disabled', false);
    });
  });

  @if(session('success'))
    mpToast(@json(session('success')));
  @endif
  @if(session('error'))
    $('#calendarSyncBanner').text(@json(session('error'))).prop('hidden', false);
  @endif

  $('#calendarSyncForm').on('submit', function (e) {
    e.preventDefault();
    clearErrors();
    var type = selectedType();
    $('#scheduling_type').val(type);
    if (type === 'auto' && !gcalConnected) {
      showErrors({ google_calendar_connected: ['Please connect your Google Calendar for auto scheduling.'] });
      return;
    }
    var $btn = $('#btnSaveCalendarSync');
    $btn.prop('disabled', true).html('<span class="ms">hourglass_top</span>Saving…');
    $.ajax({
      url: @json(route('schedule.calendar-sync.update')),
      type: 'POST',
      data: {
        _token: @json(csrf_token()),
        scheduling_type: type
      },
      headers: {
        'X-CSRF-TOKEN': @json(csrf_token()),
        Accept: 'application/json'
      }
    }).done(function (data) {
      if (data.success) {
        mpToast(data.message || 'Calendar sync updated.');
        return;
      }
      if (data.errors) {
        showErrors(data.errors);
        return;
      }
      $('#calendarSyncBanner').text(data.message || 'Could not save').prop('hidden', false);
    }).fail(function (xhr) {
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        showErrors(xhr.responseJSON.errors);
      } else {
        $('#calendarSyncBanner').text((xhr.responseJSON && xhr.responseJSON.message) || 'Network error').prop('hidden', false);
      }
    }).always(function () {
      $btn.prop('disabled', false).html('<span class="ms">save</span>Save changes');
    });
  });
})();
</script>
@endsection
