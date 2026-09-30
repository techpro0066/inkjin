@extends('layouts.artist-onboarding-layout')

@section('title', 'Calendar — Artist onboarding')

@php
  $ud = $userDetail;
  $st = $ud->scheduling_type ?? '';
  $gcal = !empty($ud->google_calendar_token);
  $connectedCalendarEmail = null;
  if (!empty($ud->google_calendar_id) && str_contains((string) $ud->google_calendar_id, '@')) {
      $connectedCalendarEmail = (string) $ud->google_calendar_id;
  } elseif (!empty($user?->email)) {
      $connectedCalendarEmail = (string) $user->email;
  }
  $defaultSched = $st !== '' ? $st : ($gcal ? 'auto' : 'managed');
  $isAuto = $defaultSched === 'auto';
  $reqCons = (bool) ($ud->require_consultation ?? false);
  $sessionType = $ud->session_type ?? '';
  $ct = $ud->consultation_timing ?? '';
  $buffer = (int) ($ud->session_buffer_period ?? 30);
@endphp

@push('styles')
<style>
  .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .field-err.hidden{display:none}
  input.in.is-err,select.in.is-err,.in.is-err{border-color:#C62828}
  button.btn,a.btn{border:0;cursor:pointer;font:inherit;text-decoration:none}
  button.btn:disabled{opacity:.6;cursor:not-allowed}
  .po input{accent-color:#3E007C;width:18px;height:18px;margin-top:2px;flex-shrink:0}
  .bufseg>div,.bufseg>button{
    padding:9px 0;text-align:center;border-radius:10px;font-weight:600;font-size:13px;
    border:1px solid #E8DFEA;background:#fff;cursor:pointer;font:inherit;color:inherit;
  }
  .bufseg>div.on,.bufseg>button.on{background:#3E007C!important;color:#fff;border-color:#3E007C!important}
  .cal-alert{margin-top:12px;padding:12px 14px;border-radius:12px;font-size:13.5px;font-weight:500}
  .cal-alert.err{background:#FDECEC;color:#9B1C1C;border:1px solid #F5C2C2}
  .cal-alert.hidden{display:none}
  .gcal-ok{color:#0B6B3A;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px}
  .modal-ov{display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:16px}
  .modal-ov.open{display:flex}
  .modal-box{background:#fff;border-radius:16px;max-width:420px;width:100%;padding:22px;border:1px solid var(--line)}
  @media (max-width:700px){
    .rules-grid,.consult-grid{grid-template-columns:1fr!important}
  }
</style>
@endpush

@section('content')
<form id="calendarForm">
  @csrf
  <input type="hidden" name="scheduling_type" id="scheduling_type" value="{{ $defaultSched }}">
  <input type="hidden" name="session_buffer_period" id="session_buffer_period" value="{{ $buffer }}">
  <input type="hidden" name="require_consultation" id="require_consultation" value="{{ $reqCons ? '1' : '0' }}">

  <div class="wrap">
    <h1 style="margin-top:6px">How do you want to manage your time?<a class="help-q" href="https://help.inkjin.com/en/articles/17200733-setup-step-5-calendar" target="_blank" rel="noopener" data-help-article="O5-calendar" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
    <div class="sub" style="max-width:640px;margin-bottom:24px">Pick your scheduling model, then set your booking rules and consultation preferences.</div>

    <div class="tgrid" style="margin-bottom:16px" id="schedCards">
      <label class="po {{ $isAuto ? 'sel' : '' }}">
        <input type="radio" name="sched_ui" value="auto" {{ $isAuto ? 'checked' : '' }}>
        <div style="flex:1">
          <b><span class="ms" style="vertical-align:-4px">calendar_month</span> Auto scheduling</b>
          <div class="d">Sync your Google Calendar to block busy times and let clients book open slots instantly.
            <div class="lbl" style="margin-top:10px">Most efficient</div>
          </div>
        </div>
      </label>
      <label class="po {{ !$isAuto ? 'sel' : '' }}">
        <input type="radio" name="sched_ui" value="managed" {{ !$isAuto ? 'checked' : '' }}>
        <div style="flex:1">
          <b><span class="ms" style="vertical-align:-4px">edit_calendar</span> Managed scheduling</b>
          <div class="d">Review every request before it reaches your books. Ideal for detailed custom work.
            <div class="lbl" style="margin-top:10px">Most control</div>
          </div>
        </div>
      </label>
    </div>
    <p id="scheduling_type_error" class="field-err hidden" role="alert"></p>
    <div id="calAlert" class="cal-alert err hidden" role="alert"></div>

    <div id="gcal" style="margin-bottom:16px" @if(!$isAuto) hidden @endif>
      <div class="card pad row" style="gap:14px;align-items:center">
        <div class="ic"><span class="ms">event</span></div>
        <div style="flex:1;min-width:0">
          @if($gcal)
            <div class="gcal-ok"><span class="ms" style="color:#00A650;font-variation-settings:'FILL' 1">check_circle</span> Google Calendar connected</div>
            @if($connectedCalendarEmail)
              <div class="faint" style="font-size:12.5px;margin-top:4px">Connected account: <b style="color:var(--ink)">{{ $connectedCalendarEmail }}</b></div>
            @endif
          @else
            <b>Connect Google Calendar</b>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Needed for Auto scheduling. Busy times in your calendar are blocked so clients only see free slots.</div>
          @endif
        </div>
        @if($gcal)
          <button type="button" class="btn ghost" id="disconnectCalendarBtn">Disconnect</button>
        @else
          <button type="button" class="btn" id="connectCalendarBtn"><span class="ms">link</span>Connect Google Calendar</button>
        @endif
      </div>
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Booking rules</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">These also write the policies clients see on your page</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="grid rules-grid" style="grid-template-columns:1fr 1fr 1fr;gap:18px;align-items:start">
          <div>
            <label class="fl" for="cancellation_window">Cancellation window <span style="color:#C62828">*</span></label>
            <select class="in js-select2" id="cancellation_window" name="cancellation_window" data-placeholder="Select" data-searchable="false">
              @foreach (['12h'=>'12 hours','24h'=>'24 hours','48h'=>'48 hours','72h'=>'72 hours','1w'=>'1 week','2w'=>'2 weeks'] as $k => $lab)
                <option value="{{ $k }}" {{ ($ud->cancellation_window ?? '24h') === $k ? 'selected' : '' }}>{{ $lab }}</option>
              @endforeach
            </select>
            <div class="help">How long before the session clients can cancel for a full refund.</div>
            <p id="cancellation_window_error" class="field-err hidden" role="alert"></p>
          </div>

          <div>
            <span class="fl">Buffer time <span style="color:#C62828">*</span></span>
            <div class="grid bufseg" style="grid-template-columns:1fr 1fr;gap:8px" id="bufferSeg">
              @foreach ([15,30,45,60] as $m)
                <button type="button" class="{{ $buffer === $m ? 'on' : '' }}" data-buffer="{{ $m }}">{{ $m }}m</button>
              @endforeach
            </div>
            <div class="help">Time blocked between sessions.</div>
            <p id="session_buffer_period_error" class="field-err hidden" role="alert"></p>
          </div>

          <div>
            <span class="fl">Reschedule policy <span style="color:#C62828">*</span></span>
            <div id="rescheduleGroup">
              @foreach (['once'=>'Allow once','twice'=>'Allow twice','unlimited'=>'Unlimited','never'=>'Strict (no rescheduling)'] as $val => $lab)
                <label class="po {{ ($ud->reschedule_times ?? 'once') === $val ? 'sel' : '' }}">
                  <input type="radio" name="reschedule_times" value="{{ $val }}" {{ ($ud->reschedule_times ?? 'once') === $val ? 'checked' : '' }}>
                  <div style="flex:1"><b>{{ $lab }}</b></div>
                </label>
              @endforeach
            </div>
            <p id="reschedule_times_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>
      </div>
    </div>

    <div class="card pad" style="margin-bottom:14px">
      <div class="row" style="justify-content:space-between;gap:14px;align-items:center">
        <div>
          <b>Require a consultation</b>
          <div class="faint" style="font-size:12.5px">Clients must book a consultation before the tattoo session.</div>
        </div>
        <div class="tog {{ $reqCons ? 'on' : '' }}" id="consultation_toggle" role="switch" aria-checked="{{ $reqCons ? 'true' : 'false' }}" tabindex="0" style="cursor:pointer"></div>
      </div>
      <p id="require_consultation_error" class="field-err hidden" role="alert"></p>

      <div id="consultationFields" style="margin-top:16px" @if(!$reqCons) hidden @endif>
        <div class="grid consult-grid" style="grid-template-columns:1fr 1fr;gap:12px">
          <div id="session_type_container">
            <label class="fl" for="session_type">Session type <span style="color:#C62828">*</span></label>
            <select class="in js-select2" id="session_type" name="session_type" data-placeholder="Select session type" data-searchable="false">
              <option value="" {{ $sessionType === '' ? 'selected' : '' }}></option>
              <option value="physical" {{ $sessionType === 'physical' ? 'selected' : '' }}>In-person</option>
              <option value="online" {{ $sessionType === 'online' ? 'selected' : '' }}>Online</option>
              <option value="both" {{ $sessionType === 'both' ? 'selected' : '' }}>Both (in-person &amp; online)</option>
            </select>
            <div class="help">Online, in person, or both.</div>
            <p id="session_type_error" class="field-err hidden" role="alert"></p>
          </div>
          <div id="session_duration_container">
            <label class="fl" for="session_duration_minutes">Duration (minutes) <span style="color:#C62828">*</span></label>
            <input class="in" type="text" inputmode="numeric" id="session_duration_minutes" name="session_duration_minutes" value="{{ $ud->session_duration_minutes ?? '' }}" placeholder="e.g. 30, 60" autocomplete="off">
            <div class="help">Between 15 minutes and 8 hours.</div>
            <p id="session_duration_minutes_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>

        <div id="consultation_timing_container" style="margin-top:14px">
          <span class="fl">Consultation setup <span style="color:#C62828">*</span></span>
          <div id="consultation_timing_group">
            <label class="po {{ $ct === 'combined' ? 'sel' : '' }}">
              <input type="radio" name="consultation_timing" value="combined" {{ $ct === 'combined' ? 'checked' : '' }}>
              <div style="flex:1">
                <b>Included in the tattoo session</b>
                <div class="d">The consultation happens at the start of the tattoo session and counts toward its time.</div>
              </div>
            </label>
            <label class="po {{ $ct === 'separate' ? 'sel' : '' }}">
              <input type="radio" name="consultation_timing" value="separate" {{ $ct === 'separate' ? 'checked' : '' }}>
              <div style="flex:1">
                <b>Separate consultation session</b>
                <div class="d">The consultation is booked as its own session before the tattoo session.</div>
              </div>
            </label>
          </div>
          <p id="consultation_timing_error" class="field-err hidden" role="alert"></p>
        </div>

        <div id="gap_fields_container" style="margin-top:14px" @if(!($reqCons && $ct === 'separate')) hidden @endif>
          <input type="hidden" id="require_gap_between_consultation_tattoo" name="require_gap_between_consultation_tattoo" value="{{ ($reqCons && $ct === 'separate') ? '1' : '0' }}">
          <div id="gap_duration_container">
            <label class="fl" for="consultation_tattoo_gap_value">Minimum gap (in days) <span style="color:#C62828">*</span></label>
            <input class="in" type="text" inputmode="numeric" id="consultation_tattoo_gap_value" name="consultation_tattoo_gap_value" value="{{ $ud->consultation_tattoo_gap_value ?? '' }}" placeholder="e.g. 1, 2, 7" autocomplete="off">
            <div class="help">Set the minimum time between the consultation and the tattoo session.</div>
            <p id="consultation_tattoo_gap_value_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>
      </div>
    </div>

    <div class="obfoot">
      <a href="{{ route('onboarding.preferences') }}" class="btn ghost"><span class="ms">arrow_back</span>Back</a>
      <button type="submit" class="btn" id="calSubmit">Next step<span class="ms">arrow_forward</span></button>
    </div>
  </div>
</form>

<div id="disconnectCalendarModal" class="modal-ov" role="dialog" aria-modal="true" aria-labelledby="disconnectCalTitle">
  <div class="modal-box">
    <h3 id="disconnectCalTitle" style="margin-bottom:6px">Disconnect calendar?</h3>
    <p class="sub" style="margin-bottom:18px">This will switch you to Managed scheduling automatically. You can reconnect Google Calendar later to use Auto scheduling again.</p>
    <div class="row" style="justify-content:flex-end;gap:10px">
      <button type="button" id="cancelDisconnectCal" class="btn ghost">Cancel</button>
      <button type="button" id="confirmDisconnectBtn" class="btn" style="background:#C62828">Disconnect</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@include('partials.reddit-pixel', ['event' => 'Step_5'])
<script>
(function ($) {
  function clearFieldError(id) {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError(id);
    $('#' + id + '_error').addClass('hidden').text('');
    $('#' + id).removeClass('is-err').next('.select2-container').removeClass('is-err');
  }

  function syncPo($scope) {
    $scope.find('.po').each(function () {
      $(this).toggleClass('sel', $(this).find('input[type=radio]').is(':checked'));
    });
  }

  function selectSchedule(type) {
    $('#scheduling_type').val(type);
    $('input[name=sched_ui]').each(function () { this.checked = this.value === type; });
    syncPo($('#schedCards'));
    $('#gcal').prop('hidden', type !== 'auto');
    clearFieldError('scheduling_type');
    $('#calAlert').addClass('hidden').text('');
  }

  function requireConsultationEnabled() {
    return $('#require_consultation').val() === '1';
  }

  function initVisibleConsultationSelect2() {
    var $st = $('#session_type');
    if ($st.hasClass('select2-hidden-accessible')) {
      try { $st.select2('destroy'); } catch (e) {}
    }
    if (typeof window.initOnboardingSelect2 === 'function') {
      window.initOnboardingSelect2('#session_type', { placeholder: 'Select session type', searchable: false });
    }
  }

  function toggleSessionFields() {
    var show = requireConsultationEnabled();
    $('#consultationFields').prop('hidden', !show);
    if (show) {
      initVisibleConsultationSelect2();
    } else {
      $('#session_type').val('').trigger('change');
      $('#session_duration_minutes').val('');
      $('input[name="consultation_timing"]').prop('checked', false);
      syncPo($('#consultation_timing_group'));
      clearFieldError('session_type');
      clearFieldError('session_duration_minutes');
      clearFieldError('consultation_timing');
    }
    toggleGapFields();
  }

  function toggleGapFields() {
    var ct = $('input[name="consultation_timing"]:checked').val() || '';
    var show = requireConsultationEnabled() && ct === 'separate';
    $('#gap_fields_container').prop('hidden', !show);
    $('#require_gap_between_consultation_tattoo').val(show ? '1' : '0');
    if (!show) {
      $('#consultation_tattoo_gap_value').val('');
      clearFieldError('consultation_tattoo_gap_value');
    }
  }

  function toggleConsultation() {
    var $tog = $('#consultation_toggle');
    $tog.toggleClass('on');
    var on = $tog.hasClass('on');
    $('#require_consultation').val(on ? '1' : '0');
    $tog.attr('aria-checked', on ? 'true' : 'false');
    clearFieldError('require_consultation');
    toggleSessionFields();
  }

  function showCalFieldErrors(errors) {
    $.each(errors, function (k, messages) {
      var $err = $('#' + k + '_error');
      if ($err.length) $err.text(messages[0]).removeClass('hidden');
      var $f = $('#' + k);
      if ($f.length) {
        $f.addClass('is-err');
        if ($f.hasClass('select2-hidden-accessible')) $f.next('.select2-container').addClass('is-err');
      }
      if (k === 'consultation_timing') {
        $('#consultation_timing_group').css({ outline: '2px solid #C62828', outlineOffset: '2px', borderRadius: '12px' });
      }
    });
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('calendarForm'));
    }
  }

  function showCalAlert(msg) {
    $('#calAlert').text(msg).removeClass('hidden');
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('calendarForm'));
    }
  }

  $(function () {
    if (typeof window.initOnboardingSelect2 === 'function') {
      window.initOnboardingSelect2('#cancellation_window', { searchable: false });
    }
    toggleSessionFields();

    $('#schedCards').on('change', 'input[type=radio]', function () {
      selectSchedule(this.value);
    });

    $('#bufferSeg').on('click', 'button', function () {
      $('#bufferSeg button').removeClass('on');
      $(this).addClass('on');
      $('#session_buffer_period').val(this.getAttribute('data-buffer'));
      clearFieldError('session_buffer_period');
    });

    $('#rescheduleGroup').on('change', 'input[type=radio]', function () {
      syncPo($('#rescheduleGroup'));
      clearFieldError('reschedule_times');
    });

    $('#consultation_toggle').on('click keydown', function (e) {
      if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      toggleConsultation();
    });

    $('#consultation_timing_group').on('change', 'input[type=radio]', function () {
      syncPo($('#consultation_timing_group'));
      clearFieldError('consultation_timing');
      $('#consultation_timing_group').css({ outline: '', outlineOffset: '', borderRadius: '' });
      toggleGapFields();
    });

    $.each(['cancellation_window', 'session_type', 'session_duration_minutes', 'consultation_tattoo_gap_value'], function (_, id) {
      $('#' + id).on('change input', function () { clearFieldError(id); });
    });

    $(document).on('input', '#session_duration_minutes, #consultation_tattoo_gap_value', function () {
      var v = this.value.replace(/[^0-9]/g, '');
      if (this.value !== v) this.value = v;
    });

    $('#connectCalendarBtn').on('click', function () {
      window.location.href = @json(route('google.calendar.redirect'));
    });

    function openCalModal() { $('#disconnectCalendarModal').addClass('open'); }
    function closeCalModal() { $('#disconnectCalendarModal').removeClass('open'); }
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

    $('#calendarForm').on('submit', function (e) {
      e.preventDefault();
      var st = $('#scheduling_type').val();
      $('#calendarForm [id$="_error"]').text('').addClass('hidden');
      $('#consultation_timing_group').css({ outline: '', outlineOffset: '', borderRadius: '' });
      $('#calAlert').addClass('hidden').text('');
      if (!st) {
        $('#scheduling_type_error').text('Choose a scheduling model.').removeClass('hidden');
        if (typeof window.scrollToFirstOnboardingError === 'function') {
          window.scrollToFirstOnboardingError(document.getElementById('calendarForm'));
        }
        return;
      }
      var $btn = $('#calSubmit');
      var original = $btn.html();
      $btn.prop('disabled', true).text('Saving...');
      var fd = new FormData(this);
      if ($('#require_consultation').val() !== '1') {
        fd.delete('session_type');
        fd.delete('session_duration_minutes');
        fd.delete('consultation_timing');
        fd.delete('require_gap_between_consultation_tattoo');
        fd.delete('consultation_tattoo_gap_value');
      } else if (($('input[name="consultation_timing"]:checked').val() || '') !== 'separate') {
        fd.set('require_gap_between_consultation_tattoo', '0');
        fd.delete('consultation_tattoo_gap_value');
      }
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
          if (data.errors) {
            showCalFieldErrors(data.errors);
            return;
          }
          showCalAlert(data.message || 'Could not save');
        })
        .fail(function (xhr) {
          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            showCalFieldErrors(xhr.responseJSON.errors);
            return;
          }
          showCalAlert((xhr.responseJSON && xhr.responseJSON.message) || 'Network error');
        })
        .always(function () {
          $btn.prop('disabled', false).html(original);
        });
    });
  });
})(window.jQuery);
</script>
@endpush
