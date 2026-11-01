@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Money · Deposits & rates')

@php
  $activeNav = 'money';
  $ud = $userDetail;
  $depositType = ($ud->minimum_deposit_type ?? 'amount') === 'percentage' ? 'percentage' : 'amount';
  $feeType = in_array(($ud->booking_fee_type ?? 'client'), ['client', 'artist', 'split'], true)
    ? ($ud->booking_fee_type ?? 'client')
    : 'client';
  $currencyCode = strtoupper(trim((string) ($ud->currency ?? 'EUR'))) ?: 'EUR';
  $currencySymbol = match ($currencyCode) {
    'EUR' => '€',
    'GBP' => '£',
    'USD', 'CAD', 'AUD', 'NZD', 'SGD' => '$',
    default => $currencyCode.' ',
  };
  $fmtRate = static function ($v) {
      if ($v === null || $v === '') {
          return '';
      }

      return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
  };
@endphp

@section('styles')
<style>
  .dep-page .depsw{display:inline-flex;background:#E9E3EC;border-radius:24px;padding:4px;gap:0}
  .dep-page .depsw button{border:0;background:none;font:inherit;font-weight:600;font-size:13px;color:#6F6874;padding:9px 18px;border-radius:20px;cursor:pointer;transition:background .2s,color .2s}
  .dep-page .depsw button.on{background:#3E007C;color:#fff}
  .dep-page .depsw button:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  .dep-page .fee-card{padding:16px;cursor:pointer;border:1.5px solid var(--line);transition:border-color .15s,background .15s}
  .dep-page .fee-card.on{border:2px solid #3E007C;background:#FBF8FC}
  .dep-page .fee-card .ms.radio{color:#C8BFCC;font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
  .dep-page .fee-card.on .ms.radio{color:#3E007C;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}
  .dep-page .field-err{color:var(--red);font-size:12px;margin-top:6px;display:none}
  .dep-page .field-err.on{display:block}
  .dep-page .in.bad{border-color:#C62828}
  .dep-page button.btn{border:0;cursor:pointer;font:inherit}
  .dep-page .btn.pgsave{opacity:.4;pointer-events:none}
  .dep-page .btn.pgsave.on{opacity:1;pointer-events:auto}
  .dep-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:2000;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:none;gap:10px;align-items:center;max-width:calc(100% - 32px)
  }
  .dep-toast.on{display:flex}
  .dep-toast .ms{color:#3DD68C}
  .dep-leave{
    position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;
    z-index:520;padding:16px
  }
  .dep-leave.on{display:flex}
  .dep-leave-box{background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;position:relative}
  .dep-leave-x{position:absolute;top:14px;right:14px;border:0;background:none;cursor:pointer;padding:6px;border-radius:8px;color:var(--muted)}
  .dep-leave-x:hover{background:#F3E8FF;color:var(--pri)}
  .dep-leave-x .ms{font-size:22px}
  @media(max-width:900px){
    .dep-page .dep-grid,.dep-page .rate-grid,.dep-page .fee-grid{grid-template-columns:1fr!important}
    .dep-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
<div class="dep-page">
<form id="preferencesForm">
      @csrf
  <input type="hidden" name="timezone" value="{{ $ud->timezone ?: 'UTC' }}">
  <input type="hidden" name="date_time_format" value="{{ $ud->date_time_format ?: 'DD/MM/YYYY' }}">
  <input type="hidden" name="size_unit" value="{{ $ud->size_unit ?: 'cm' }}">
  <input type="hidden" name="currency" id="currency" value="{{ $currencyCode }}">
  <input type="hidden" id="minimum_deposit_type" name="minimum_deposit_type" value="{{ $depositType }}">
  <input type="hidden" id="booking_fee_type" name="booking_fee_type" value="{{ $feeType }}">

  <div class="head">
    <div>
      <h1>
        Money
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201197-dashboard-money-deposits-rates" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">What you've earned, how clients pay you, and where your payouts go.</div>
    </div>
      </div>

  @include('artist.partials.money-tabs', ['moneyTab' => 'deposits'])

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Deposit</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">What clients pay upfront to secure a booking</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="grid dep-grid" style="grid-template-columns:1fr 1fr;align-items:start">
            <div>
          <span class="fl">Deposit type</span>
          <div class="depsw" role="radiogroup" aria-label="Deposit type">
            <button type="button" class="{{ $depositType === 'amount' ? 'on' : '' }}" data-t="amount" aria-pressed="{{ $depositType === 'amount' ? 'true' : 'false' }}">Fixed amount</button>
            <button type="button" class="{{ $depositType === 'percentage' ? 'on' : '' }}" data-t="percentage" aria-pressed="{{ $depositType === 'percentage' ? 'true' : 'false' }}">Percentage %</button>
              </div>
            </div>
            <div>
          <label class="fl" for="minimum_deposit_amount" id="deplbl">
            {{ $depositType === 'percentage' ? 'Minimum deposit percentage' : 'Minimum deposit amount' }}
            <span style="color:#C62828">*</span>
          </label>
          <div class="in" style="padding:0 13px" id="depWrap">
            <span id="depun" style="color:#6F6874;font-weight:600">{{ $depositType === 'percentage' ? '' : $currencySymbol }}</span>
            <input type="text" id="minimum_deposit_amount" name="minimum_deposit_amount" value="{{ $ud->minimum_deposit_amount ?? '' }}" inputmode="decimal" autocomplete="off" style="border:0;outline:0;font:inherit;flex:1;padding:10px 6px;background:none;min-width:0">
            <span id="depsuf" style="color:#6F6874;font-weight:600">{{ $depositType === 'percentage' ? '%' : '' }}</span>
          </div>
          <div class="help" id="dephelp">{{ $depositType === 'percentage' ? 'Percentage of the total price the client pays to book.' : 'The smallest deposit a client pays to book.' }}</div>
          <p id="minimum_deposit_amount_error" class="field-err"></p>
        </div>
      </div>
    </div>
            </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
            <div>
        <h3>Rates</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Reference rates shown on your page. You always confirm custom work with a quote.</div>
            </div>
          </div>
    <div style="padding:18px 22px">
      <div class="grid rate-grid" style="grid-template-columns:repeat(3,1fr)">
              <div>
          <label class="fl" for="hourly_rate">Hourly rate <span class="faint" style="font-weight:500">(optional)</span></label>
          <input class="in" type="text" inputmode="decimal" autocomplete="off" id="hourly_rate" name="hourly_rate" value="{{ $fmtRate($ud->hourly_rate) }}" placeholder="e.g. 120">
          <div class="help">Before any custom quote</div>
          <p id="hourly_rate_error" class="field-err"></p>
              </div>
              <div>
          <label class="fl" for="half_day_rate">Half-day rate <span class="faint" style="font-weight:500">(optional)</span></label>
          <input class="in" type="text" inputmode="decimal" autocomplete="off" id="half_day_rate" name="half_day_rate" value="{{ $fmtRate($ud->half_day_rate) }}" placeholder="e.g. 400">
          <div class="help">For half-day sessions</div>
          <p id="half_day_rate_error" class="field-err"></p>
              </div>
              <div>
          <label class="fl" for="full_day_rate">Full-day rate <span class="faint" style="font-weight:500">(optional)</span></label>
          <input class="in" type="text" inputmode="decimal" autocomplete="off" id="full_day_rate" name="full_day_rate" value="{{ $fmtRate($ud->full_day_rate) }}" placeholder="e.g. 700">
          <div class="help">For full-day sessions</div>
          <p id="full_day_rate_error" class="field-err"></p>
        </div>
              </div>
            </div>
          </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
          <div>
        <h3>Service fee</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">How the €10 Bookpay booking fee is handled</div>
                </div>
              </div>
    <div style="padding:18px 22px">
      <div class="grid fee-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="card fee-card {{ $feeType === 'client' ? 'on' : '' }}" data-fee="client" role="button" tabindex="0">
          <div class="row" style="justify-content:space-between">
            <div class="ic"><span class="ms">person</span></div>
            <span class="ms radio">{{ $feeType === 'client' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
                </div>
          <b style="display:block;margin-top:12px;font-size:14.5px">Client pays</b>
          <div class="faint" style="font-size:12.5px;margin-top:3px;line-height:1.45">€10 is added to the client's total</div>
              </div>
        <div class="card fee-card {{ $feeType === 'artist' ? 'on' : '' }}" data-fee="artist" role="button" tabindex="0">
          <div class="row" style="justify-content:space-between">
            <div class="ic"><span class="ms">brush</span></div>
            <span class="ms radio">{{ $feeType === 'artist' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
                </div>
          <b style="display:block;margin-top:12px;font-size:14.5px">Artist pays</b>
          <div class="faint" style="font-size:12.5px;margin-top:3px;line-height:1.45">€10 is taken from your payout</div>
              </div>
        <div class="card fee-card {{ $feeType === 'split' ? 'on' : '' }}" data-fee="split" role="button" tabindex="0">
          <div class="row" style="justify-content:space-between">
            <div class="ic"><span class="ms">call_split</span></div>
            <span class="ms radio">{{ $feeType === 'split' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
          </div>
          <b style="display:block;margin-top:12px;font-size:14.5px">Split</b>
          <div class="faint" style="font-size:12.5px;margin-top:3px;line-height:1.45">Client pays €5 and you pay €5</div>
        </div>
      </div>
      <p id="booking_fee_type_error" class="field-err"></p>
    </div>
  </div>

  <div class="row pgsaverow" style="justify-content:flex-start;margin-top:4px">
    <button type="button" class="btn pgsave" id="savePrefBtn" disabled><span class="ms">save</span>Save changes</button>
  </div>
</form>

  <div class="dep-toast" id="depToast" role="status"><span class="ms">check_circle</span><span id="depToastText">Changes saved successfully</span></div>

  <div class="dep-leave" id="depLeave" role="dialog" aria-modal="true" aria-labelledby="depLeaveTitle">
    <div class="dep-leave-box">
      <button type="button" class="dep-leave-x" id="depLeaveClose" aria-label="Close"><span class="ms">close</span></button>
      <div style="padding:24px 24px 16px">
        <div style="width:48px;height:48px;border-radius:12px;background:#FFF3DC;color:#B7791F;display:flex;align-items:center;justify-content:center;margin-bottom:14px">
          <span class="ms">warning</span>
        </div>
        <h2 id="depLeaveTitle" style="font-size:20px;font-weight:800;margin-bottom:8px;padding-right:28px">Your changes are not saved</h2>
        <p style="color:#3a3a3a;line-height:1.55;margin:0">Save them before you leave, or leave without saving and lose them.</p>
      </div>
      <div style="display:flex;gap:10px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-wrap:wrap">
        <button type="button" class="btn ghost" id="depLeaveDiscard" style="flex:1;justify-content:center;border:1px solid var(--line)">Leave without saving</button>
        <button type="button" class="btn" id="depLeaveSave" style="flex:1;justify-content:center"><span class="ms">save</span>Save and leave</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  var form = document.getElementById('preferencesForm');
  if (!form) return;

  var depositTypeInput = document.getElementById('minimum_deposit_type');
  var feeTypeInput = document.getElementById('booking_fee_type');
  var depButtons = form.querySelectorAll('.depsw button');
  var feeCards = form.querySelectorAll('.fee-card');
  var depLbl = document.getElementById('deplbl');
  var depUn = document.getElementById('depun');
  var depSuf = document.getElementById('depsuf');
  var depHelp = document.getElementById('dephelp');
  var saveBtn = document.getElementById('savePrefBtn');
  var toastEl = document.getElementById('depToast');
  var leaveEl = document.getElementById('depLeave');
  var saveUrl = @json(route('settings.preferences.update'));
  var csrf = @json(csrf_token());
  var currencySym = @json($currencySymbol);
  var dirty = false;
  var saving = false;
  var pendingNav = null;
  var pendingReload = false;

  function toast(msg) {
    if (!toastEl) return;
    var text = document.getElementById('depToastText');
    if (text) text.textContent = msg || 'Changes saved successfully';
    toastEl.classList.add('on');
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(function () { toastEl.classList.remove('on'); }, 2600);
  }

  function setDirty(v) {
    dirty = !!v;
    if (!saveBtn || saving) return;
    saveBtn.disabled = !dirty;
    saveBtn.classList.toggle('on', dirty);
  }

  function markDirty() { setDirty(true); }

  function clearErrors() {
    form.querySelectorAll('.field-err').forEach(function (el) {
      el.textContent = '';
      el.classList.remove('on');
    });
    form.querySelectorAll('.in.bad').forEach(function (el) { el.classList.remove('bad'); });
  }

  function showFieldError(key, message) {
    var err = document.getElementById(key + '_error');
    var field = document.getElementById(key);
    if (err) {
      err.textContent = message;
      err.classList.add('on');
    }
    if (field) {
      var wrap = field.closest('.in') || field;
      wrap.classList.add('bad');
    }
  }

  function setDepositType(type, fromUser) {
    depositTypeInput.value = type;
    depButtons.forEach(function (btn) {
      var on = btn.getAttribute('data-t') === type;
      btn.classList.toggle('on', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    var pct = type === 'percentage';
    if (depLbl) {
      depLbl.innerHTML = (pct ? 'Minimum deposit percentage' : 'Minimum deposit amount')
        + ' <span style="color:#C62828">*</span>';
    }
    if (depUn) depUn.textContent = pct ? '' : currencySym;
    if (depSuf) depSuf.textContent = pct ? '%' : '';
    if (depHelp) {
      depHelp.textContent = pct
        ? 'Percentage of the total price the client pays to book.'
        : 'The smallest deposit a client pays to book.';
    }
    if (fromUser) markDirty();
  }

  function setFeeType(value, fromUser) {
    feeTypeInput.value = value;
    feeCards.forEach(function (card) {
      var on = card.getAttribute('data-fee') === value;
      card.classList.toggle('on', on);
      var radio = card.querySelector('.ms.radio');
      if (radio) radio.textContent = on ? 'radio_button_checked' : 'radio_button_unchecked';
    });
    if (fromUser) markDirty();
  }

  function runPendingLeave() {
    if (pendingReload) {
      pendingReload = false;
      window.location.reload();
      return;
    }
    var n = pendingNav;
    pendingNav = null;
    if (n) n.click();
  }

  function showLeaveModal(opts) {
    opts = opts || {};
    pendingNav = opts.nav || null;
    pendingReload = !!opts.reload;
    leaveEl?.classList.add('on');
  }

  function dismissLeaveModal() {
    leaveEl?.classList.remove('on');
    pendingNav = null;
    pendingReload = false;
  }

  function savePreferences(andLeave) {
    if (saving) return Promise.resolve();
    clearErrors();
    saving = true;
    if (saveBtn) {
      saveBtn.disabled = true;
      saveBtn.classList.remove('on');
      saveBtn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
    }

    return fetch(saveUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: new FormData(form),
    })
      .then(async function (res) {
        var data = await res.json().catch(function () { return {}; });
        if (!res.ok) {
          if (res.status === 422 && data.errors) {
            Object.keys(data.errors).forEach(function (k) {
              showFieldError(k, data.errors[k][0]);
            });
            throw new Error('');
          }
          throw new Error(data.message || 'Could not save payment settings.');
        }
        if (!data.success) {
          if (data.errors) {
            Object.keys(data.errors).forEach(function (k) {
              showFieldError(k, data.errors[k][0]);
            });
          }
          throw new Error(data.message || 'Could not save payment settings.');
        }
        setDirty(false);
        toast('Changes saved successfully');
        if (andLeave) runPendingLeave();
      })
      .catch(function (err) {
        if (err && err.message) toast(err.message);
        setDirty(true);
      })
      .finally(function () {
        saving = false;
        if (saveBtn) {
          saveBtn.innerHTML = '<span class="ms">save</span>Save changes';
          saveBtn.disabled = !dirty;
          saveBtn.classList.toggle('on', dirty);
        }
      });
  }

  depButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      setDepositType(btn.getAttribute('data-t'), true);
    });
  });

  feeCards.forEach(function (card) {
    card.addEventListener('click', function () {
      setFeeType(card.getAttribute('data-fee'), true);
    });
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        setFeeType(card.getAttribute('data-fee'), true);
      }
    });
  });

  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);

  saveBtn?.addEventListener('click', function () {
    if (!dirty || saving) return;
    savePreferences(false);
  });

  document.getElementById('depLeaveClose')?.addEventListener('click', dismissLeaveModal);
  leaveEl?.addEventListener('click', function (e) {
    if (e.target === leaveEl) dismissLeaveModal();
  });
  document.getElementById('depLeaveDiscard')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    setDirty(false);
    runPendingLeave();
  });
  document.getElementById('depLeaveSave')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    savePreferences(true);
  });

  document.addEventListener('click', function (e) {
    if (!dirty) return;
    var n = e.target.closest('aside a[href], .tabs a[href], a[href]:not([href="#"])');
    if (!n || n.closest('.pgsaverow') || n.closest('#depLeave')) return;
    if (n.getAttribute('target') === '_blank') return;
    if (n.closest('.head')) return;
    e.preventDefault();
    e.stopPropagation();
    showLeaveModal({ nav: n });
  }, true);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && leaveEl?.classList.contains('on')) {
      e.preventDefault();
      dismissLeaveModal();
      return;
    }
    if (!dirty) return;
    var isReloadKey = e.key === 'F5' || ((e.ctrlKey || e.metaKey) && (e.key === 'r' || e.key === 'R'));
    if (!isReloadKey) return;
    e.preventDefault();
    showLeaveModal({ reload: true });
  }, true);

  window.addEventListener('beforeunload', function (e) {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });
})();
</script>
@endsection
