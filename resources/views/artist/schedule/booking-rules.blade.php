@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Schedule')

@php
  $ud = $userDetail ?? auth()->user()?->userDetail;
  $reqCons = (bool) ($ud->require_consultation ?? false);
  $st = $ud->session_type ?? '';
  $ct = $ud->consultation_timing ?? '';
  $buffer = (int) ($ud->session_buffer_period ?? 30);
  $cancel = $ud->cancellation_window ?? '72h';
  $reschedule = $ud->reschedule_times ?? 'once';
  $sessionTypeLabels = [
    'physical' => 'In-person',
    'online' => 'Online',
    'both' => 'Both (in-person & online)',
  ];
@endphp

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .br-page .po{
    display:flex;gap:12px;align-items:center;background:#fff;border:1px solid var(--line);
    border-radius:14px;padding:14px 16px;cursor:pointer;margin-bottom:10px;transition:border-color .15s,background .15s,padding .15s
  }
  .br-page .po.sel{border:2px solid #3E007C;padding:13px 15px;background:#FDFAFF}
  .br-page .po input[type="radio"]{
    -webkit-appearance:none;appearance:none;width:18px;height:18px;margin:0;flex-shrink:0;
    border:2px solid #D5CDD9;border-radius:50%;background:#fff;cursor:pointer;position:relative
  }
  .br-page .po input[type="radio"]:checked{
    border-color:#3E007C;background:#3E007C;box-shadow:none
  }
  .br-page .po input[type="radio"]:checked::after{
    content:'';position:absolute;top:50%;left:50%;width:6px;height:6px;
    margin:-3px 0 0 -3px;border-radius:50%;background:#fff
  }
  .br-page .po b{font-size:14.5px;display:block;font-weight:700}
  .br-page .po .d{font-size:13px;color:var(--muted);margin-top:3px;line-height:1.5}
  .br-page .po:has(.d){align-items:flex-start}
  .br-page .po:has(.d) input[type="radio"]{margin-top:2px}
  .br-page .bufseg div{
    padding:9px 0;text-align:center;border-radius:10px;font-weight:600;font-size:13px;
    border:1px solid var(--line);background:#fff;cursor:pointer;user-select:none
  }
  .br-page .bufseg div.on{background:#3E007C!important;color:#fff;border-color:#3E007C!important}
  .br-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer;border:0}
  .br-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .br-page .tog.on{background:var(--pri)}.br-page .tog.on:after{left:19px}
  .br-page input.in{display:block}
  .br-page .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .br-page .field-err[hidden]{display:none!important}
  .br-page .hidden{display:none!important}
  .br-page .select2-container{width:100%!important;z-index:1;font-family:inherit}
  .select2-container--open{z-index:10060!important}
  .br-page .select2-container--default .select2-selection--single{
    height:40px;min-height:40px;border:1px solid var(--line)!important;border-radius:10px!important;
    background:#fff!important;padding:0 8px 0 4px;box-shadow:none!important
  }
  .br-page .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:38px;padding-left:9px;color:var(--ink);font-size:13.5px;font-weight:400
  }
  .br-page .select2-container--default .select2-selection--single .select2-selection__arrow{height:38px;right:6px}
  .br-page .select2-container--default.select2-container--focus .select2-selection--single,
  .br-page .select2-container--default.select2-container--open .select2-selection--single{
    border-color:#3E007C!important;box-shadow:0 0 0 3px #F3E8FF!important
  }
  .select2-dropdown{border:1px solid var(--line);border-radius:12px;overflow:hidden;font-family:'Plus Jakarta Sans',system-ui,sans-serif}
  .select2-container--default .select2-results__option{padding:10px 12px;font-size:13.5px}
  .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
    background:#3E007C;color:#fff
  }
  .select2-container--default .select2-results__option--selected{background:#F7F3F9;color:#3E007C;font-weight:600}
  .br-page .mp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .br-page .mp-toast.on{opacity:1;transform:none}
  .br-page .mp-toast .ms{color:#3DD68C;font-size:20px}
  @media (max-width:900px){
    .br-rules-grid{grid-template-columns:1fr!important}
    .br-consult-grid{grid-template-columns:1fr!important}
    .br-page .bufseg{grid-template-columns:repeat(4,1fr)!important}
    .br-page .mp-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
<div class="br-page">
  <div class="head">
    <div>
      <h1>
        Schedule
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201100-dashboard-schedule-booking-rules" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">When and how clients can book you.</div>
    </div>
  </div>

  @include('artist.partials.schedule-tabs', ['scheduleTab' => 'booking-rules'])

  <div class="note" style="margin-bottom:16px">
    <span class="ms">auto_awesome</span>
    These rules also write the Policies text clients see on your page (My Page &gt; Policies).
  </div>

  <form id="bookingRulesForm">
    @csrf

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Booking rules</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Same settings as the Calendar step in onboarding</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="grid br-rules-grid" style="grid-template-columns:1fr 1fr 1fr;gap:18px;align-items:start">
          <div>
            <label class="fl" for="cancellation_window">Cancellation window</label>
            <select class="in js-select2" id="cancellation_window" name="cancellation_window" aria-label="Cancellation window">
              @foreach (['12h' => '12 hours', '24h' => '24 hours', '48h' => '48 hours', '72h' => '72 hours', '1w' => '1 week', '2w' => '2 weeks'] as $val => $lab)
                <option value="{{ $val }}" {{ $cancel === $val ? 'selected' : '' }}>{{ $lab }}</option>
              @endforeach
            </select>
            <div class="help">How long before the session clients can cancel for a full refund.</div>
            <p class="field-err" id="cancellation_window_error" hidden></p>
          </div>

          <div>
            <span class="fl">Buffer time</span>
            <div class="grid bufseg" style="grid-template-columns:1fr 1fr;gap:8px" role="group" aria-label="Buffer time">
              @foreach ([15, 30, 45, 60] as $m)
                <div class="{{ $buffer === $m ? 'on' : '' }}" data-buffer="{{ $m }}" role="button" tabindex="0">{{ $m }}m</div>
              @endforeach
            </div>
            <input type="hidden" name="session_buffer_period" id="session_buffer_period" value="{{ $buffer }}">
            <div class="help">Time blocked between sessions.</div>
            <p class="field-err" id="session_buffer_period_error" hidden></p>
          </div>

          <div>
            <span class="fl">Reschedule policy</span>
            @foreach ([
              'once' => 'Allow once',
              'twice' => 'Allow twice',
              'unlimited' => 'Unlimited',
              'never' => 'Strict (no rescheduling)',
            ] as $val => $lab)
              <label class="po {{ $reschedule === $val ? 'sel' : '' }}">
                <input type="radio" name="reschedule_times" value="{{ $val }}" {{ $reschedule === $val ? 'checked' : '' }}>
                <div style="flex:1"><b>{{ $lab }}</b></div>
              </label>
            @endforeach
            <p class="field-err" id="reschedule_times_error" hidden></p>
          </div>
        </div>
      </div>
    </div>

    <div class="card pad">
      <div class="row" style="justify-content:space-between;gap:14px">
        <div>
          <b>Require a consultation</b>
          <div class="faint" style="font-size:12.5px">Clients must book a consultation before the tattoo session.</div>
        </div>
        <button type="button" class="tog {{ $reqCons ? 'on' : '' }}" id="consultationTog" role="switch" aria-checked="{{ $reqCons ? 'true' : 'false' }}" aria-label="Require a consultation"></button>
        <input type="hidden" name="require_consultation" id="require_consultation" value="{{ $reqCons ? '1' : '0' }}">
      </div>
      <p class="field-err" id="require_consultation_error" hidden></p>

      <div id="consultPanel" class="{{ $reqCons ? '' : 'hidden' }}" style="margin-top:16px;border-top:1px solid #F3EEF4;padding-top:16px">
        <div class="grid br-consult-grid" style="grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="fl" for="session_type">Session type <span style="color:#C62828">*</span></label>
            <select class="in js-select2" id="session_type" name="session_type" data-placeholder="Select…">
              <option value="" disabled {{ $st === '' ? 'selected' : '' }}>Select…</option>
              <option value="physical" {{ $st === 'physical' ? 'selected' : '' }}>{{ $sessionTypeLabels['physical'] }}</option>
              <option value="online" {{ $st === 'online' ? 'selected' : '' }}>{{ $sessionTypeLabels['online'] }}</option>
              <option value="both" {{ $st === 'both' ? 'selected' : '' }}>{{ $sessionTypeLabels['both'] }}</option>
            </select>
            <div class="help">Online, in person, or both.</div>
            <p class="field-err" id="session_type_error" hidden></p>
          </div>
          <div>
            <label class="fl" for="session_duration_minutes">Duration (minutes) <span style="color:#C62828">*</span></label>
            <input class="in" type="number" id="session_duration_minutes" name="session_duration_minutes" value="{{ $ud->session_duration_minutes ?? '' }}" placeholder="e.g. 30, 60" min="15" max="480" step="15">
            <div class="help">Between 15 minutes and 8 hours.</div>
            <p class="field-err" id="session_duration_minutes_error" hidden></p>
          </div>
        </div>

        <span class="fl" style="margin-top:14px">Consultation setup <span style="color:#C62828">*</span></span>
        <label class="po {{ $ct === 'combined' || $ct === '' ? 'sel' : '' }}">
          <input type="radio" name="consultation_timing" value="combined" {{ $ct === 'combined' || ($reqCons && $ct === '') ? 'checked' : '' }}>
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
        <p class="field-err" id="consultation_timing_error" hidden></p>

        <div id="gapFields" class="{{ ($reqCons && $ct === 'separate') ? '' : 'hidden' }}" style="margin-top:12px">
          <input type="hidden" name="require_gap_between_consultation_tattoo" id="require_gap_between_consultation_tattoo" value="{{ ($reqCons && $ct === 'separate') ? '1' : '0' }}">
          <label class="fl" for="consultation_tattoo_gap_value">Minimum gap (in days) <span style="color:#C62828">*</span></label>
          <input class="in" type="number" id="consultation_tattoo_gap_value" name="consultation_tattoo_gap_value" value="{{ $ud->consultation_tattoo_gap_value ?? '' }}" placeholder="e.g. 1, 2, 7" min="1" style="max-width:200px">
          <div class="help">Minimum time between the consultation and the tattoo session.</div>
          <p class="field-err" id="consultation_tattoo_gap_value_error" hidden></p>
        </div>
      </div>
    </div>

    <div style="margin-top:14px">
      <div class="row" style="justify-content:flex-start;margin-top:4px">
        <button type="submit" class="btn" id="btnSaveBookingRules"><span class="ms">save</span>Save changes</button>
      </div>
      <p class="field-err" id="bookingRulesBanner" hidden style="margin-top:10px"></p>
    </div>
  </form>

  <div class="mp-toast" id="mpToast" role="status"><span class="ms">check_circle</span><span id="mpToastText">Saved</span></div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
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
    $('#bookingRulesBanner').prop('hidden', true).text('');
  }

  function showErrors(errors) {
    Object.keys(errors || {}).forEach(function (key) {
      var msg = Array.isArray(errors[key]) ? errors[key][0] : String(errors[key]);
      var $el = $('#' + key + '_error');
      if ($el.length) {
        $el.text(msg).prop('hidden', false);
      } else {
        $('#bookingRulesBanner').text(msg).prop('hidden', false);
      }
    });
  }

  function syncPo(name) {
    $('input[name="' + name + '"]').each(function () {
      $(this).closest('.po').toggleClass('sel', this.checked);
    });
  }

  function consultationOn() {
    return $('#require_consultation').val() === '1';
  }

  function syncConsultPanel() {
    var on = consultationOn();
    $('#consultPanel').toggleClass('hidden', !on);
    if (!on) {
      $('#gapFields').addClass('hidden');
      $('#require_gap_between_consultation_tattoo').val('0');
      return;
    }
    var ct = $('input[name="consultation_timing"]:checked').val() || '';
    var sep = ct === 'separate';
    $('#gapFields').toggleClass('hidden', !sep);
    $('#require_gap_between_consultation_tattoo').val(sep ? '1' : '0');
    if (!sep) {
      $('#consultation_tattoo_gap_value').val('');
    }
  }

  function initSelect2() {
    if (!window.jQuery || !jQuery.fn.select2) return;
    jQuery('#bookingRulesForm select.js-select2').each(function () {
      var $el = jQuery(this);
      if ($el.hasClass('select2-hidden-accessible')) return;
      $el.select2({
        width: '100%',
        minimumResultsForSearch: Infinity,
        dropdownParent: jQuery('body')
      });
    });
  }

  initSelect2();

  $('.bufseg [data-buffer]').on('click keydown', function (e) {
    if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
    e.preventDefault();
    var v = parseInt($(this).data('buffer'), 10);
    $('.bufseg [data-buffer]').removeClass('on');
    $(this).addClass('on');
    $('#session_buffer_period').val(v);
    $('#session_buffer_period_error').prop('hidden', true).text('');
  });

  $('#consultationTog').on('click', function () {
    var on = !$(this).hasClass('on');
    $(this).toggleClass('on', on).attr('aria-checked', on ? 'true' : 'false');
    $('#require_consultation').val(on ? '1' : '0');
    if (on && !$('input[name="consultation_timing"]:checked').length) {
      $('input[name="consultation_timing"][value="combined"]').prop('checked', true);
      syncPo('consultation_timing');
    }
    syncConsultPanel();
  });

  $('input[name="reschedule_times"]').on('change', function () {
    syncPo('reschedule_times');
    $('#reschedule_times_error').prop('hidden', true).text('');
  });

  $('input[name="consultation_timing"]').on('change', function () {
    syncPo('consultation_timing');
    $('#consultation_timing_error').prop('hidden', true).text('');
    syncConsultPanel();
  });

  $('#cancellation_window, #session_type, #session_duration_minutes, #consultation_tattoo_gap_value').on('change input', function () {
    $('#' + this.id + '_error').prop('hidden', true).text('');
  });

  syncConsultPanel();

  $('#bookingRulesForm').on('submit', function (e) {
    e.preventDefault();
    clearErrors();
    var $btn = $('#btnSaveBookingRules');
    var html = $btn.html();
    $btn.prop('disabled', true).html('<span class="ms">progress_activity</span>Saving…');

    var fd = new FormData(this);
    if ($('#require_consultation').val() !== '1') {
      fd.set('require_consultation', '0');
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
      url: @json(route('schedule.booking-rules.update')),
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).done(function (res) {
      mpToast((res && res.message) || 'Booking rules updated successfully.');
    }).fail(function (xhr) {
      var data = xhr.responseJSON || {};
      if (xhr.status === 422 && data.errors) {
        showErrors(data.errors);
      } else {
        $('#bookingRulesBanner').text(data.message || 'Could not save booking rules.').prop('hidden', false);
      }
    }).always(function () {
      $btn.prop('disabled', false).html(html);
    });
  });
})();
</script>
@endsection
