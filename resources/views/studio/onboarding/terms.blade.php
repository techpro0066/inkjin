@extends('layouts.studio-onboarding-layout')

@section('title', 'How you work with artists')

@push('styles')
<style>
  .bm.card{overflow:visible}
  .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer;display:inline-block}
  .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .tog.on{background:var(--pri)}.tog.on:after{left:19px}
  .split{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px;margin-top:14px;min-width:0}
  .split .box{border:1px solid var(--line);border-radius:12px;padding:10px 14px;background:#fff;min-width:0}
  .split .box span{display:block;font-size:12px;color:var(--muted);font-weight:600}
  .split input{border:0;font:inherit;font-size:20px;font-weight:800;width:70px;max-width:100%;outline:none;background:none;color:var(--ink);padding:0;min-height:0}
  .split .calc{font-size:20px;font-weight:800;display:block;margin-top:2px}
  .note.err{background:#FDECEC;border-color:#F5C2C2;color:#C62828}
  .note.err .ms{color:#C62828}
  .btn.disabled,.btn[aria-disabled="true"]{opacity:.4;pointer-events:none}
  @media (max-width:560px){
    .split{grid-template-columns:minmax(0,1fr)}
    .bm .row[style*="justify-content:space-between"]{align-items:flex-start}
  }
</style>
@endpush

@section('content')
  <h1 style="margin-top:6px">How do you work with your artists?<a class="help-q" href="https://help.inkjin.com" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
  <div class="sub" style="max-width:660px;margin-bottom:24px">Most studios do both: some artists share each payment with the studio, others rent a workstation. Both are on to start. Turn one off only if you never use it.</div>

  <form id="studioOnboardingTermsForm" method="POST" action="{{ route('studio.onboarding.terms.update') }}" novalidate>
    @csrf
    <input type="hidden" name="offers_revenue_split" id="offers_revenue_split" value="{{ $offersRevenueSplit ? '1' : '0' }}">
    <input type="hidden" name="offers_workstation_rent" id="offers_workstation_rent" value="{{ $offersWorkstationRent ? '1' : '0' }}">

    <div class="bm card" style="padding:16px 18px;margin-bottom:10px">
      <div class="row" style="justify-content:space-between;gap:14px">
        <div class="row" style="gap:12px;min-width:0">
          <div class="ic"><span class="ms">call_split</span></div>
          <div style="min-width:0">
            <b style="font-size:15px">Revenue split</b>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Each payment is split automatically between the artist's and your Stripe account, after the cancellation window.</div>
          </div>
        </div>
        <div class="tog{{ $offersRevenueSplit ? ' on' : '' }}" id="bm-split" role="switch" aria-checked="{{ $offersRevenueSplit ? 'true' : 'false' }}" aria-label="Revenue split" tabindex="0" data-shows="#bm-split-b" data-field="offers_revenue_split"></div>
      </div>
      <div id="bm-split-b" @if(! $offersRevenueSplit) hidden @endif>
        <div class="split">
          <div class="box">
            <span>Artist gets (default)</span>
            <input type="number" name="default_revenue_artist_percent" min="1" max="99" value="{{ $defaultRevenueArtistPercent }}" id="bm-artist-pct" data-pair="#so2s" aria-label="Artist gets (default)">%
          </div>
          <div class="box">
            <span>Studio gets</span>
            <b class="calc" id="so2s">{{ 100 - (int) $defaultRevenueArtistPercent }}%</b>
          </div>
        </div>
        <div class="help">Your default. Artists see it as a hint. Each artist enters the split you agreed with them, and you confirm it. You can also set a different split when you invite someone.</div>
      </div>
    </div>

    <div class="bm card" style="padding:16px 18px">
      <div class="row" style="justify-content:space-between;gap:14px">
        <div class="row" style="gap:12px;min-width:0">
          <div class="ic"><span class="ms">chair</span></div>
          <div style="min-width:0">
            <b style="font-size:15px">Workstation rent</b>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Artists get the full payment for their bookings and pay you rent for their workstation outside Bookpay. You still see their bookings at your studio.</div>
          </div>
        </div>
        <div class="tog{{ $offersWorkstationRent ? ' on' : '' }}" id="bm-rent" role="switch" aria-checked="{{ $offersWorkstationRent ? 'true' : 'false' }}" aria-label="Workstation rent" tabindex="0" data-field="offers_workstation_rent"></div>
      </div>
    </div>

    <div class="note err" id="bm-err" @if($offersRevenueSplit || $offersWorkstationRent) hidden @endif style="margin-top:12px"><span class="ms">error</span>Keep at least one on.</div>
    <div class="note" style="margin-top:14px"><span class="ms">info</span>Payouts are always automatic. A split changes only new bookings. Bookings already made keep the split they were made with. You can change these later in Money &gt; Payouts.</div>

    <div class="obfoot">
      <a class="btn ghost" href="{{ route('studio.onboarding.location') }}"><span class="ms">arrow_back</span>Back</a>
      <div class="row" style="gap:0">
        <button type="submit" class="btn" id="nextStepBtn">Next step<span class="ms">arrow_forward</span></button>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('studioOnboardingTermsForm');
  var btn = document.getElementById('nextStepBtn');
  var defaultBtnHtml = btn ? btn.innerHTML : '';
  var artistPct = document.getElementById('bm-artist-pct');

  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return [].slice.call((r || document).querySelectorAll(s)); }

  function syncPair(i) {
    if (!i) return;
    var v = Math.max(1, Math.min(99, parseInt(i.value || 0, 10) || 0));
    if (String(i.value) !== String(v) && i.value !== '') i.value = v;
    var o = document.querySelector(i.dataset.pair);
    if (o) o.textContent = (100 - v) + '%';
  }

  if (artistPct) {
    artistPct.addEventListener('input', function () { syncPair(artistPct); });
    syncPair(artistPct);
  }

  function setField(name, on) {
    var el = document.getElementById(name);
    if (el) el.value = on ? '1' : '0';
  }

  function toggleTog(t) {
    t.classList.toggle('on');
    var on = t.classList.contains('on');
    t.setAttribute('aria-checked', on ? 'true' : 'false');
    if (t.dataset.field) setField(t.dataset.field, on);
    var sh = t.dataset.shows;
    if (sh) $$(sh).forEach(function (x) { x.hidden = !on; });
    syncState();
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('.tog');
    if (!t || !form || !form.contains(t)) return;
    toggleTog(t);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var t = e.target.closest('.tog');
    if (!t || !form || !form.contains(t)) return;
    e.preventDefault();
    toggleTog(t);
  });

  function syncState() {
    var s = $('#bm-split').classList.contains('on');
    var r = $('#bm-rent').classList.contains('on');
    var ok = s || r;
    var err = $('#bm-err');
    if (err) err.hidden = ok;
    if (btn) {
      btn.classList.toggle('disabled', !ok);
      btn.disabled = !ok;
      btn.setAttribute('aria-disabled', ok ? 'false' : 'true');
    }
    return ok;
  }

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!syncState()) {
      if (window.bpToast) window.bpToast('Keep at least one on.', true);
      return;
    }

    setField('offers_revenue_split', $('#bm-split').classList.contains('on'));
    setField('offers_workstation_rent', $('#bm-rent').classList.contains('on'));
    syncPair(artistPct);

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
    }

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]').value,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: new FormData(form)
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, status: response.status, data: data };
        }).catch(function () {
          return { ok: false, status: response.status, data: {} };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          if (window.bpToast) window.bpToast('Changes saved');
          window.location.href = @json(route('studio.onboarding.payouts'));
          return;
        }

        var msg = (result.data && result.data.message) || 'Could not save.';
        var errors = (result.data && result.data.errors) || {};
        if (errors.offers_revenue_split || errors.offers_workstation_rent) {
          var err = $('#bm-err');
          if (err) err.hidden = false;
        }
        if (window.bpToast) window.bpToast(msg, true);
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
      })
      .finally(function () {
        if (btn) {
          btn.innerHTML = defaultBtnHtml;
          syncState();
        }
      });
  });

  syncState();
})();
</script>
@endpush
