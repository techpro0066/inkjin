@extends('layouts.artist-onboarding-layout')

@section('title', 'Payments — Artist onboarding')

@php
  $ud = $userDetail;
  $depositType = ($ud->minimum_deposit_type ?? 'amount') === 'percentage' ? 'percentage' : 'amount';
  $feeType = $ud->booking_fee_type ?? 'client';
  $currency = $ud->currency ?? '';
  $fmtRate = function ($v) {
    if ($v === null || $v === '') return '';
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
  };
@endphp

@push('styles')
<style>
  .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .field-err.hidden{display:none}
  input.in{font-size: 14px !important;}
  input.in.is-err,.in.is-err{border-color:#C62828}
  button.btn,a.btn{border:0;cursor:pointer;font:inherit;text-decoration:none}
  button.btn:disabled{opacity:.6;cursor:not-allowed}
  .po input{accent-color:#3E007C;width:18px;height:18px;margin-top:2px;flex-shrink:0}
  .depsw{display:inline-flex;background:#E9E3EC;border-radius:24px;padding:4px;gap:0}
  .depsw button{border:0;background:none;font:inherit;font-weight:600;font-size:13px;color:#6F6874;padding:9px 18px;border-radius:20px;cursor:pointer;transition:background .2s,color .2s}
  .depsw button.on{background:#3E007C;color:#fff}
  .depsw button:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  .dep-in{display:flex;align-items:center;gap:4px;padding:0 13px}
  .dep-in input{border:0;outline:0;font:inherit;flex:1;padding:10px 6px;background:none;min-width:0}
  .dep-affix{color:#6F6874;font-weight:600;flex-shrink:0}
  @media (max-width:700px){
    .pay-grid-2,.pay-grid-3{grid-template-columns:1fr!important}
  }
</style>
@endpush

@section('content')
<form id="prefForm">
  @csrf
  <input type="hidden" id="timezone" name="timezone" value="{{ $ud->timezone ?: 'UTC' }}">
  <input type="hidden" id="date_time_format" name="date_time_format" value="{{ $ud->date_time_format ?: 'DD/MM/YYYY' }}">
  <input type="hidden" id="size_unit" name="size_unit" value="{{ $ud->size_unit ?: 'cm' }}">
  <input type="hidden" name="minimum_deposit_type" id="minimum_deposit_type" value="{{ $depositType }}">

  <div class="wrap">
    <h1 style="margin-top:6px">Set up your payments<a class="help-q" href="https://help.inkjin.com/en/articles/17200694-setup-step-4-payments" target="_blank" rel="noopener" data-help-article="O4-payments" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
    <div class="sub" style="max-width:640px;margin-bottom:24px">Choose your currency, the deposit clients pay to book, and your reference rates.</div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Deposit</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">What clients pay upfront to secure a booking</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="grid pay-grid-2" style="grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="fl" for="currency">Currency <span style="color:#C62828">*</span></label>
            <select class="in" id="currency" name="currency" data-placeholder="Select currency" data-selected="{{ $currency }}">
              <option value=""></option>
            </select>
            <div class="help">Should match the bank account you connect in Payouts.</div>
            <p id="currency_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>

        <div class="grid pay-grid-2" style="grid-template-columns:1fr 1fr;gap:12px;align-items:start;margin-top:16px">
          <div>
            <span class="fl">Deposit type <span style="color:#C62828">*</span></span>
            <div class="depsw" role="radiogroup" aria-label="Deposit type" id="depositTypeSwitch">
              <button type="button" class="{{ $depositType === 'amount' ? 'on' : '' }}" data-t="amount" aria-pressed="{{ $depositType === 'amount' ? 'true' : 'false' }}">Fixed amount</button>
              <button type="button" class="{{ $depositType === 'percentage' ? 'on' : '' }}" data-t="percentage" aria-pressed="{{ $depositType === 'percentage' ? 'true' : 'false' }}">Percentage %</button>
            </div>
            <p id="minimum_deposit_type_error" class="field-err hidden" role="alert"></p>
          </div>
          <div>
            <label class="fl" for="minimum_deposit_amount"><span id="deplbl">{{ $depositType === 'percentage' ? 'Minimum deposit percentage' : 'Minimum deposit amount' }}</span> <span style="color:#C62828">*</span></label>
            <div class="in dep-in" id="depInputWrap">
              <span class="dep-affix" id="depun" @if($depositType === 'percentage') hidden @endif>€</span>
              <input type="text" inputmode="decimal" id="minimum_deposit_amount" name="minimum_deposit_amount" value="{{ $ud->minimum_deposit_amount ?? '' }}" autocomplete="off">
              <span class="dep-affix" id="depsuf" @if($depositType !== 'percentage') hidden @endif>%</span>
            </div>
            <div class="help" id="dephelp">{{ $depositType === 'percentage' ? 'Percentage of the total booking price taken as deposit.' : 'The smallest deposit a client pays to book.' }}</div>
            <p id="minimum_deposit_amount_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Rates</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Reference rates. You always confirm custom work with a quote.</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="grid pay-grid-3" style="grid-template-columns:1fr 1fr 1fr;gap:12px">
          <div>
            <label class="fl" for="hourly_rate">Hourly rate <span class="faint" style="font-weight:500">(optional)</span></label>
            <input class="in js-rate" type="text" inputmode="decimal" id="hourly_rate" name="hourly_rate" value="{{ $fmtRate($ud->hourly_rate) }}" placeholder="e.g. 120" autocomplete="off">
            <div class="help">Before any custom quote</div>
            <p id="hourly_rate_error" class="field-err hidden" role="alert"></p>
          </div>
          <div>
            <label class="fl" for="half_day_rate">Half-day rate <span class="faint" style="font-weight:500">(optional)</span></label>
            <input class="in js-rate" type="text" inputmode="decimal" id="half_day_rate" name="half_day_rate" value="{{ $fmtRate($ud->half_day_rate) }}" placeholder="e.g. 400" autocomplete="off">
            <div class="help">For half-day sessions</div>
            <p id="half_day_rate_error" class="field-err hidden" role="alert"></p>
          </div>
          <div>
            <label class="fl" for="full_day_rate">Full-day rate <span class="faint" style="font-weight:500">(optional)</span></label>
            <input class="in js-rate" type="text" inputmode="decimal" id="full_day_rate" name="full_day_rate" value="{{ $fmtRate($ud->full_day_rate) }}" placeholder="e.g. 700" autocomplete="off">
            <div class="help">For full-day sessions</div>
            <p id="full_day_rate_error" class="field-err hidden" role="alert"></p>
          </div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Service fee <span style="color:#C62828">*</span></h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">How should the Bookpay booking fee be handled?</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="grid pay-grid-3" style="grid-template-columns:1fr 1fr 1fr;gap:10px" id="feeCards">
          @foreach ([
            'client' => ['Client pays', 'The fee is added to the client\'s total'],
            'artist' => ['Artist pays', 'The fee is taken from your payout'],
            'split' => ['Split', 'Shared between client and artist'],
          ] as $val => $meta)
            <label class="po {{ $feeType === $val ? 'sel' : '' }}">
              <input type="radio" name="booking_fee_type" value="{{ $val }}" {{ $feeType === $val ? 'checked' : '' }}>
              <div style="flex:1">
                <b>{{ $meta[0] }}</b>
                <div class="d">{{ $meta[1] }}</div>
              </div>
            </label>
          @endforeach
        </div>
        <p id="booking_fee_type_error" class="field-err hidden" role="alert"></p>
      </div>
    </div>

    <div class="obfoot">
      <a href="{{ route('onboarding.studio') }}" class="btn ghost"><span class="ms">arrow_back</span>Back</a>
      <button type="submit" class="btn" id="prefSubmit">Next step<span class="ms">arrow_forward</span></button>
    </div>
  </div>
</form>
@endsection

@push('scripts')
@include('partials.reddit-pixel', ['event' => 'Step_4'])
<script src="{{ asset('design/js/currencies.js') }}"></script>
<script>
(function ($) {
  var SYMBOLS = {
    EUR: '€', GBP: '£', USD: '$', CHF: 'CHF', SEK: 'kr', DKK: 'kr', NOK: 'kr',
    PLN: 'zł', CZK: 'Kč', HUF: 'Ft', RON: 'lei', BGN: 'лв', CAD: 'C$', AUD: 'A$',
  };

  function currencySymbol(code) {
    if (!code) return '€';
    if (SYMBOLS[code]) return SYMBOLS[code];
    try {
      var parts = new Intl.NumberFormat(undefined, { style: 'currency', currency: code, currencyDisplay: 'narrowSymbol' }).formatToParts(0);
      var sym = parts.find(function (p) { return p.type === 'currency'; });
      return sym ? sym.value : code;
    } catch (e) {
      return code;
    }
  }

  function setDepositType(type) {
    var pct = type === 'percentage';
    $('#minimum_deposit_type').val(pct ? 'percentage' : 'amount');
    $('#depositTypeSwitch button').each(function () {
      var on = this.getAttribute('data-t') === (pct ? 'percentage' : 'amount');
      $(this).toggleClass('on', on).attr('aria-pressed', on ? 'true' : 'false');
    });
    $('#deplbl').text(pct ? 'Minimum deposit percentage' : 'Minimum deposit amount');
    $('#depun').prop('hidden', pct);
    $('#depsuf').prop('hidden', !pct);
    $('#dephelp').text(pct
      ? 'Percentage of the total booking price taken as deposit.'
      : 'The smallest deposit a client pays to book.');
    if (!pct) syncCurrencyAffix();
  }

  function syncCurrencyAffix() {
    if ($('#minimum_deposit_type').val() === 'percentage') return;
    var code = $('#currency').val() || '';
    $('#depun').text(currencySymbol(code));
  }

  function clearErrors() {
    $('#prefForm').find('[id$="_error"]').addClass('hidden').text('');
    $('#prefForm').find('.is-err').removeClass('is-err');
    $('#currency').next('.select2-container').removeClass('is-err');
  }

  function setErr(id, msg) {
    var $e = $('#' + id + '_error');
    if ($e.length) $e.text(msg).removeClass('hidden');
    var $f = $('#' + id);
    if ($f.length) {
      $f.addClass('is-err');
      if ($f.hasClass('select2-hidden-accessible')) $f.next('.select2-container').addClass('is-err');
      if ($f.closest('.in').length) $f.closest('.in').addClass('is-err');
    }
  }

  function validateClient() {
    clearErrors();
    var ok = true;
    if (!$('#currency').val()) {
      setErr('currency', 'Please select a currency.');
      ok = false;
    }
    var dep = $.trim($('#minimum_deposit_amount').val() || '');
    if (!dep) {
      setErr('minimum_deposit_amount', 'Minimum deposit is required.');
      ok = false;
    } else if (isNaN(parseFloat(dep)) || parseFloat(dep) < 0) {
      setErr('minimum_deposit_amount', 'Enter a valid deposit value.');
      ok = false;
    } else if ($('#minimum_deposit_type').val() === 'percentage' && parseFloat(dep) > 100) {
      setErr('minimum_deposit_amount', 'Percentage cannot exceed 100.');
      ok = false;
    }
    if (!$('input[name="booking_fee_type"]:checked').val()) {
      setErr('booking_fee_type', 'Please select how the service fee is handled.');
      ok = false;
    }
    ['hourly_rate', 'half_day_rate', 'full_day_rate'].forEach(function (id) {
      var v = $.trim($('#' + id).val() || '');
      if (v && (isNaN(parseFloat(v)) || parseFloat(v) < 0)) {
        setErr(id, 'Enter a valid amount.');
        ok = false;
      }
    });
    if (!ok && typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('prefForm'));
    }
    return ok;
  }

  $(function () {
    var hasSavedTimezone = @json(!empty($ud->timezone));
    var hasSavedDateFormat = @json(!empty($ud->date_time_format));
    var hasSavedSizeUnit = @json(!empty($ud->size_unit));
    var locale = navigator.language || 'en-GB';
    var region = (locale.split('-')[1] || '').toUpperCase();

    if (!hasSavedTimezone) {
      var detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      if (detectedTimezone) $('#timezone').val(detectedTimezone);
    }
    if (!hasSavedDateFormat) {
      if (region === 'US') $('#date_time_format').val('MM/DD/YYYY');
      else if (/^(ja|zh|ko)/i.test(locale)) $('#date_time_format').val('YYYY-MM-DD');
      else $('#date_time_format').val('DD/MM/YYYY');
    }
    if (!hasSavedSizeUnit) {
      $('#size_unit').val(['US', 'LR', 'MM'].includes(region) ? 'in' : 'cm');
    }

    var sel = document.getElementById('currency');
    var selected = sel.getAttribute('data-selected') || '';
    if (sel && typeof fillCurrencySelect === 'function') {
      fillCurrencySelect(sel, selected || 'EUR');
    }
    // Always (re)init after options exist — do not rely on layout auto-init of empty select
    if (window.jQuery && window.jQuery.fn.select2) {
      var $currency = $('#currency');
      if ($currency.hasClass('select2-hidden-accessible')) {
        try { $currency.select2('destroy'); } catch (e) {}
      }
      if (typeof window.initOnboardingSelect2 === 'function') {
        window.initOnboardingSelect2('#currency', { placeholder: 'Select currency', searchable: true });
      } else {
        $currency.select2({ width: '100%', placeholder: 'Select currency', dropdownParent: $currency.closest('.card, main') });
      }
    }
    syncCurrencyAffix();

    $('#currency').on('change', function () {
      syncCurrencyAffix();
      if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('currency');
      $(this).next('.select2-container').removeClass('is-err');
    });

    $('#depositTypeSwitch').on('click', 'button', function () {
      setDepositType(this.getAttribute('data-t'));
    });

    $('#feeCards').on('change', 'input[type=radio]', function () {
      $('#feeCards .po').removeClass('sel');
      $(this).closest('.po').addClass('sel');
      if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('booking_fee_type');
      $('#booking_fee_type_error').addClass('hidden').text('');
    });

    $.each(['minimum_deposit_amount', 'hourly_rate', 'half_day_rate', 'full_day_rate'], function (_, id) {
      $('#' + id).on('input', function () {
        if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError(id);
        $(this).removeClass('is-err').closest('.in').removeClass('is-err');
      });
    });

    // Rates + deposit: text fields, digits and one decimal point only
    $(document).on('input', '#minimum_deposit_amount, .js-rate', function () {
      var v = this.value.replace(/[^0-9.]/g, '');
      var parts = v.split('.');
      if (parts.length > 2) {
        v = parts[0] + '.' + parts.slice(1).join('');
      }
      if (parts.length === 2) {
        v = parts[0] + '.' + parts[1].slice(0, 2);
      }
      if (this.value !== v) this.value = v;
    });
    $(document).on('keydown', '#minimum_deposit_amount, .js-rate', function (e) {
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      var allowed = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
      if (allowed.indexOf(e.key) !== -1) return;
      if (e.key.length === 1 && !/[0-9.]/.test(e.key)) e.preventDefault();
      if (e.key === '.' && String(this.value).indexOf('.') !== -1) e.preventDefault();
    });
    $(document).on('paste', '#minimum_deposit_amount, .js-rate', function (e) {
      e.preventDefault();
      var text = (e.originalEvent.clipboardData || window.clipboardData).getData('text') || '';
      var v = text.replace(/[^0-9.]/g, '');
      var parts = v.split('.');
      if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
      if (v.split('.').length === 2) v = v.split('.')[0] + '.' + v.split('.')[1].slice(0, 2);
      this.value = v;
      $(this).trigger('input');
    });

    $('#prefForm').on('submit', function (e) {
      e.preventDefault();
      if (!validateClient()) return;
      var $btn = $('#prefSubmit');
      var original = $btn.html();
      $btn.prop('disabled', true).text('Saving...');
      var fd = new FormData(this);
      $.ajax({
        url: @json(route('onboarding.preferences.save')),
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
            $.each(data.errors, function (k, messages) { setErr(k, messages[0]); });
            if (typeof window.scrollToFirstOnboardingError === 'function') {
              window.scrollToFirstOnboardingError(document.getElementById('prefForm'));
            }
          } else {
            alert(data.message || 'Error');
          }
        })
        .fail(function (xhr) {
          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            $.each(xhr.responseJSON.errors, function (k, messages) { setErr(k, messages[0]); });
            if (typeof window.scrollToFirstOnboardingError === 'function') {
              window.scrollToFirstOnboardingError(document.getElementById('prefForm'));
            }
          } else {
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'Error');
          }
        })
        .always(function () {
          $btn.prop('disabled', false).html(original);
        });
    });
  });
})(window.jQuery);
</script>
@endpush
