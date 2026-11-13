@extends('layouts.studio-onboarding-layout')

@section('title', 'Your artists')

@push('styles')
<style>
  .field-err{display:none;color:#C62828;font-size:12px;margin-top:6px}
  .in.err{border-color:#C62828!important;box-shadow:0 0 0 3px #FDECEC}
  select.in{appearance:auto}
  .inv-row{padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid #F3EEF4}
  .inv-row:last-child{border-bottom:0;margin-bottom:0;padding-bottom:0}
  .inv-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px;align-items:end}
  .split{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px}
  .split .box{border:1px solid var(--line);border-radius:12px;padding:10px 14px;background:#fff}
  .split .box span{display:block;font-size:12px;color:var(--muted);font-weight:600}
  .split input{border:0;font:inherit;font-size:20px;font-weight:800;width:70px;outline:none;background:transparent}
  .split .calc{font-size:20px;font-weight:800;display:block;margin-top:2px}
  .inv-remove{border:0;background:none;color:var(--faint);cursor:pointer;padding:4px;border-radius:8px}
  .inv-remove:hover{color:#C62828;background:#FDECEC}
  @media (max-width:800px){
    .inv-grid{grid-template-columns:1fr}
  }
</style>
@endpush

@section('content')
  @php
    $defaultSplit = (int) ($defaultRevenueArtistPercent ?? 60);
    $studioShare = max(0, min(100, 100 - $defaultSplit));
  @endphp

  <h1 style="margin-top:6px">Your artists<a class="help-q" href="https://help.inkjin.com/en/articles/17337614-studio-setup-step-6-your-artists" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
  <div class="sub" style="max-width:660px;margin-bottom:24px">Invite the artists who work at your studio. Optional — you can do this later from Artists.</div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Invite artists</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Optional · send one or many</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div id="invlist"></div>
      <button type="button" class="btn ghost sm" id="addInviteRowBtn" style="margin-top:12px"><span class="ms">add</span>Add another artist</button>
      <div class="help">The choices come from How you work with artists. Artists already on Bookpay get a request in their dashboard. Others get an email to join.</div>
    </div>
  </div>

  <div class="obfoot">
    <a class="btn ghost" href="{{ route('studio.onboarding.payouts') }}"><span class="ms">arrow_back</span>Back</a>
    <div class="row" style="gap:10px">
      <button type="button" class="btn ghost" id="skipInvitesBtn">Skip for now</button>
      <button type="button" class="btn" id="finishSetupBtn">Finish setup<span class="ms">arrow_forward</span></button>
    </div>
  </div>
@endsection

@push('scripts')
<script type="text/template" id="inviteRowTpl">
  <div class="inv-row" data-invite-row>
    <div class="inv-grid">
      <div>
        <span class="fl">Email or Bookpay username</span>
        <input class="in inv-identifier" type="text" value="" placeholder="artist@email.com or @username" autocomplete="off">
        <div class="field-err inv-error"></div>
      </div>
      <div>
        <span class="fl">Relationship</span>
        <select class="in inv-relationship">
          @foreach (($relationshipOptions ?? []) as $key => $label)
            <option value="{{ $key }}" @selected($key === 'resident')>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <div class="row" style="justify-content:space-between;align-items:flex-end;gap:8px;margin-bottom:6px">
          <span class="fl" style="margin:0">How they're paid</span>
          <button type="button" class="inv-remove" data-remove-row aria-label="Remove" hidden><span class="ms">close</span></button>
        </div>
        <select class="in inv-paid">
          @if ($offersRevenueSplit ?? true)
            <option value="def" selected>Revenue split · default {{ $defaultSplit }}/{{ $studioShare }}</option>
            <option value="cus">Revenue split · custom</option>
          @endif
          @if ($offersWorkstationRent ?? true)
            <option value="rent">Workstation rent</option>
          @endif
          @if (!($offersRevenueSplit ?? true) && !($offersWorkstationRent ?? true))
            <option value="def" selected>Revenue split · default {{ $defaultSplit }}/{{ $studioShare }}</option>
          @endif
        </select>
      </div>
    </div>
    <div class="cusrow" hidden style="margin-top:10px">
      <div class="split" style="margin-top:0">
        <div class="box">
          <span>Artist gets</span>
          <input type="number" min="1" max="99" value="{{ $defaultSplit }}" class="inv-artist-pct" aria-label="Artist gets">%
        </div>
        <div class="box">
          <span>Studio gets</span>
          <b class="calc inv-studio-pct">{{ $studioShare }}%</b>
        </div>
      </div>
    </div>
    <div class="rentrow" hidden style="margin-top:10px">
      <div class="help" style="margin-top:0">The artist gets the full payment for each booking. They pay you rent outside Bookpay.</div>
    </div>
  </div>
</script>
<script>
(function () {
  var list = document.getElementById('invlist');
  var tpl = document.getElementById('inviteRowTpl');
  var addBtn = document.getElementById('addInviteRowBtn');
  var finishBtn = document.getElementById('finishSetupBtn');
  var skipBtn = document.getElementById('skipInvitesBtn');
  var defaultSplit = @json($defaultSplit);
  var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  function syncRemoveButtons() {
    var rows = list.querySelectorAll('[data-invite-row]');
    rows.forEach(function (row, i) {
      var btn = row.querySelector('[data-remove-row]');
      if (btn) btn.hidden = rows.length <= 1;
    });
  }

  function syncPaid(row) {
    var paid = row.querySelector('.inv-paid');
    var cus = row.querySelector('.cusrow');
    var rent = row.querySelector('.rentrow');
    var v = paid ? paid.value : 'def';
    if (cus) cus.hidden = v !== 'cus';
    if (rent) rent.hidden = v !== 'rent';
  }

  function syncSplit(row) {
    var input = row.querySelector('.inv-artist-pct');
    var out = row.querySelector('.inv-studio-pct');
    if (!input || !out) return;
    var n = parseInt(input.value, 10);
    if (isNaN(n)) n = defaultSplit;
    n = Math.max(1, Math.min(99, n));
    input.value = String(n);
    out.textContent = (100 - n) + '%';
  }

  function addRow() {
    if (!list || !tpl) return;
    var wrap = document.createElement('div');
    wrap.innerHTML = tpl.innerHTML.trim();
    var row = wrap.firstElementChild;
    list.appendChild(row);
    bindRow(row);
    syncRemoveButtons();
    return row;
  }

  function bindRow(row) {
    row.querySelector('.inv-paid')?.addEventListener('change', function () { syncPaid(row); });
    row.querySelector('.inv-artist-pct')?.addEventListener('input', function () { syncSplit(row); });
    row.querySelector('[data-remove-row]')?.addEventListener('click', function () {
      if (list.querySelectorAll('[data-invite-row]').length <= 1) return;
      row.remove();
      syncRemoveButtons();
    });
    row.querySelector('.inv-identifier')?.addEventListener('input', function () {
      clearRowError(row);
    });
    syncPaid(row);
    syncSplit(row);
  }

  function clearRowError(row) {
    var err = row.querySelector('.inv-error');
    var input = row.querySelector('.inv-identifier');
    if (err) { err.textContent = ''; err.style.display = 'none'; }
    if (input) input.classList.remove('err');
  }

  function setRowError(row, message) {
    var err = row.querySelector('.inv-error');
    var input = row.querySelector('.inv-identifier');
    if (err) {
      err.textContent = message || '';
      err.style.display = message ? 'block' : 'none';
    }
    if (input) {
      if (message) input.classList.add('err');
      else input.classList.remove('err');
    }
  }

  function collectInvites(requireFilled) {
    var rows = [].slice.call(list.querySelectorAll('[data-invite-row]'));
    var invites = [];
    var ok = true;
    rows.forEach(function (row) {
      clearRowError(row);
      var identifier = (row.querySelector('.inv-identifier')?.value || '').trim();
      if (!identifier) {
        if (requireFilled) {
          setRowError(row, 'Enter an email or Bookpay username.');
          ok = false;
        }
        return;
      }
      var paid = row.querySelector('.inv-paid')?.value || 'def';
      var payoutMode = paid === 'rent' ? 'direct' : 'split';
      var split = defaultSplit;
      if (paid === 'cus') {
        split = parseInt(row.querySelector('.inv-artist-pct')?.value || String(defaultSplit), 10);
        if (isNaN(split)) split = defaultSplit;
        split = Math.max(0, Math.min(100, split));
      }
      invites.push({
        identifier: identifier,
        relationship: row.querySelector('.inv-relationship')?.value || 'resident',
        payout_mode: payoutMode,
        revenue_split: payoutMode === 'split' ? split : null
      });
    });
    return { ok: ok, invites: invites };
  }

  function finish(sendInvites) {
    var payload = { invites: [] };
    if (sendInvites) {
      var collected = collectInvites(false);
      // Only validate rows that have content; empty rows are ignored (skip allowed).
      var rows = [].slice.call(list.querySelectorAll('[data-invite-row]'));
      var ok = true;
      rows.forEach(function (row) {
        var identifier = (row.querySelector('.inv-identifier')?.value || '').trim();
        if (!identifier) return;
        clearRowError(row);
      });
      payload.invites = collected.invites;
      // If user typed something invalid-looking empty after trim, ignore.
      if (!collected.ok && collected.invites.length) {
        // re-check: collectInvites(false) never fails; validate filled rows only
      }
      rows.forEach(function (row) {
        var identifier = (row.querySelector('.inv-identifier')?.value || '').trim();
        if (!identifier) return;
        var paid = row.querySelector('.inv-paid')?.value || 'def';
        if (paid === 'cus') {
          var n = parseInt(row.querySelector('.inv-artist-pct')?.value || '', 10);
          if (isNaN(n) || n < 1 || n > 99) {
            setRowError(row, 'Artist share must be between 1 and 99.');
            ok = false;
          }
        }
      });
      if (!ok) return;
      payload.invites = collectInvites(false).invites;
    }

    var btn = sendInvites ? finishBtn : skipBtn;
    var other = sendInvites ? skipBtn : finishBtn;
    var defaultHtml = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>' + (sendInvites ? 'Finishing…' : 'Skipping…');
    }
    if (other) other.disabled = true;

    fetch(@json(route('studio.onboarding.artists.finish')), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          try {
            if (result.data.message) sessionStorage.setItem('bp-toast', result.data.message);
          } catch (e) {}
          window.location.href = result.data.redirect || @json(route('studio.dashboard'));
          return;
        }

        var errors = (result.data && result.data.errors) || {};
        var filledRows = [].slice.call(list.querySelectorAll('[data-invite-row]')).filter(function (row) {
          return (row.querySelector('.inv-identifier')?.value || '').trim() !== '';
        });
        var shown = false;
        Object.keys(errors).forEach(function (key) {
          var m = /^invites\.(\d+)\.identifier$/.exec(key);
          if (!m) return;
          var idx = parseInt(m[1], 10);
          var row = filledRows[idx];
          var msg = errors[key] && errors[key][0];
          if (row && msg) {
            setRowError(row, msg);
            shown = true;
          }
        });
        if (!shown && window.bpToast) {
          window.bpToast((result.data && result.data.message) || 'Could not finish setup.', true);
        }
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = defaultHtml;
        }
        if (other) other.disabled = false;
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = defaultHtml;
        }
        if (other) other.disabled = false;
      });
  }

  addBtn?.addEventListener('click', function () { addRow(); });
  finishBtn?.addEventListener('click', function () { finish(true); });
  skipBtn?.addEventListener('click', function () { finish(false); });

  addRow();
})();
</script>
@endpush
