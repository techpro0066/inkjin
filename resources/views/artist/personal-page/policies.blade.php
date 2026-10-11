@extends('layouts.new-artist-dashboard-layout')

@section('title', 'My Page')

@php
  $userDetail = $userDetail ?? $user->userDetail;
  $username = trim((string) ($userDetail->user_name ?? ''));
  $publicPageUrl = $username !== '' ? url('/@'.$username) : null;
  $publicPageHost = 'inkjin.com/@'.($username !== '' ? $username : 'username');
  $quickLinkRows = $username !== '' ? [
    ['suffix' => '', 'label' => $publicPageHost],
    ['suffix' => '#designs', 'label' => $publicPageHost.'#designs'],
    ['suffix' => '#portfolio', 'label' => $publicPageHost.'#portfolio'],
    ['suffix' => '#guest-spots', 'label' => $publicPageHost.'#guest-spots'],
    ['suffix' => '#policies', 'label' => $publicPageHost.'#policies'],
    ['suffix' => '#faq', 'label' => $publicPageHost.'#faq'],
  ] : [];
  $whatsIncludedActive = (bool) ($userDetail->design_whats_included_is_active ?? false);
  $whatsIncludedItems = is_array($userDetail->design_whats_included ?? null)
      ? array_values(array_filter(array_map('strval', $userDetail->design_whats_included)))
      : [];
  $displayPolicies = (bool) ($userDetail->display_policies ?? false);
  $extraNote = (string) ($userDetail->policies_extra_note ?? '');
  $policyCopy = $policyCopy ?? \App\Support\ArtistPolicyCopy::for($userDetail);
@endphp

@section('styles')
<style>
  .mp-pol .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer}
  .mp-pol .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .mp-pol .tog.on{background:var(--pri)}.mp-pol .tog.on:after{left:19px}
  .mp-pol .btn.pgsave{opacity:.4;pointer-events:none}
  .mp-pol .btn.pgsave.on{opacity:1;pointer-events:auto}
  .mp-pol textarea.in{display:block;resize:vertical;min-height:70px;padding:10px 13px;line-height:1.45;align-items:flex-start}
  .mp-pol .incrow{display:flex;gap:10px;align-items:center;margin-bottom:10px}
  .mp-pol .incrow .in{flex:1}
  .mp-pol .incx{border:0;background:none;cursor:pointer;color:var(--muted);padding:6px;border-radius:8px}
  .mp-pol .incx:hover{background:#F3E8FF}
  .mp-pol .incadd{display:inline-flex;align-items:center;gap:6px;border:0;background:none;font:inherit;font-weight:700;font-size:14px;cursor:pointer;padding:10px 0;color:#1A1A1A}
  .mp-pol .incadd .ms{font-size:20px}
  .mp-pol .incsug{border:1px dashed #CFC6D3;background:#fff;border-radius:20px;padding:6px 13px;font:inherit;font-size:12.5px;font-weight:600;color:#1A1A1A;cursor:pointer;display:inline-flex;gap:6px;align-items:center}
  .mp-pol .incsug .ms{font-size:16px}
  .mp-pol .incsug:disabled{color:#C8BFCC;border-color:#E8E2EA;cursor:default}
  .mp-pol .rule-link{
    display:flex;align-items:center;gap:12px;padding:12px 10px;margin:0 -10px;
    color:inherit;text-decoration:none;border-radius:10px
  }
  .mp-pol .rule-link:hover{background:#F7F3F9}
  .mp-pol .rule-link .ms{font-size:20px}
  .mp-pol .ql{position:relative}
  .mp-pol .ql-b{display:inline-flex;align-items:center;gap:8px;font:inherit;font-weight:600;font-size:13.5px;padding:9px 12px 9px 13px;border-radius:12px;border:1px solid var(--line);background:#fff;color:var(--ink);cursor:pointer;white-space:nowrap}
  .mp-pol .ql-b:hover{background:#F7F3F9}
  .mp-pol .ql-b .ms{font-size:18px}.mp-pol .ql-b .cv{font-size:20px}
  .mp-pol .ql-p{position:absolute;right:0;top:calc(100% + 8px);width:min(400px,calc(100vw - 32px));background:#fff;border:1px solid #EAE2EE;border-radius:16px;box-shadow:0 18px 44px rgba(20,0,40,.18);padding:6px;z-index:80}
  .mp-pol .ql-p[hidden]{display:none}
  .mp-pol .ql-r{display:flex;align-items:center;gap:6px;padding:4px 4px 4px 12px;border-radius:10px}
  .mp-pol .ql-r:hover{background:#F7F3F9}
  .mp-pol .ql-u{flex:1;min-width:0;font-size:13.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .mp-pol .ql-u b{font-weight:700}
  .mp-pol .ql-i{width:34px;height:34px;border-radius:9px;border:0;background:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ink);flex-shrink:0;text-decoration:none}
  .mp-pol .ql-i:hover{background:#EFE7F3}
  .mp-pol .ql-i .ms{font-size:18px}.mp-pol .ql-i.done .ms{color:#1F8A4C}
  .mp-pol .mp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
  .mp-pol .mp-toast.on{opacity:1;transform:none}
  .mp-pol .mp-toast .ms{color:#3DD68C;font-size:20px}
  .mp-pol .mp-leave{position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;z-index:500;padding:16px}
  .mp-pol .mp-leave.on{display:flex}
  .mp-pol .mp-leave-box{background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;position:relative}
  .mp-pol .mp-leave-x{position:absolute;top:14px;right:14px;width:36px;height:36px;border:0;border-radius:10px;background:none;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0}
  .mp-pol .mp-leave-x:hover{background:#F7F3F9;color:var(--ink)}
  .mp-pol .mp-leave-x .ms{font-size:22px}
  @media (max-width:1180px){.mp-pol-grid{grid-template-columns:minmax(0,1fr)!important}}
  @media (max-width:900px){.mp-pol .mp-toast{left:16px;right:16px;top:74px}.mp-pol .ql-p{right:auto;left:0}}
</style>
@endsection

@section('content')
<div class="mp-pol">
  <div class="head">
    <div>
      <h1>
        My Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201358-dashboard-my-page-policies" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Everything clients see on your page.</div>
    </div>
    <div class="btns">
      @if ($publicPageUrl)
        <div class="ql" id="quickLinks">
          <button type="button" class="ql-b" id="quickLinksBtn" aria-haspopup="true" aria-expanded="false" aria-controls="quickLinksMenu">
            <span class="ms">link</span>Quick links<span class="ms cv">expand_more</span>
          </button>
          <div class="ql-p" id="quickLinksMenu" hidden role="menu" aria-label="Quick links">
            @foreach ($quickLinkRows as $row)
              <div class="ql-r" role="none">
                <div class="ql-u" title="{{ $row['label'] }}">
                  {{ $publicPageHost }}@if($row['suffix'] !== '')<b>{{ $row['suffix'] }}</b>@endif
                </div>
                <button type="button" class="ql-i" data-ql-copy="{{ $publicPageUrl.$row['suffix'] }}" data-ql-label="{{ $row['label'] }}" role="menuitem" title="Copy link">
                  <span class="ms">content_copy</span>
                </button>
                <a class="ql-i" href="{{ $publicPageUrl.$row['suffix'] }}" target="_blank" rel="noopener" role="menuitem" title="Open">
                  <span class="ms">open_in_new</span>
                </a>
              </div>
            @endforeach
          </div>
        </div>
        <a class="btn" href="{{ $publicPageUrl }}" target="_blank" rel="noopener">
          <span class="ms">open_in_new</span>Open my page
        </a>
      @endif
    </div>
  </div>

  @include('artist.partials.my-page-tabs', [
    'myPageTab' => 'policies',
    'designsCount' => $designsCount ?? 0,
    'portfolioCount' => $portfolioCount ?? 0,
    'faqCount' => $faqCount ?? 0,
  ])

  <form id="policiesForm">
    @csrf
    <input type="hidden" name="design_whats_included_is_active" id="whatsIncludedActive" value="{{ $whatsIncludedActive ? '1' : '0' }}">
    <input type="hidden" name="display_policies" id="displayPolicies" value="{{ $displayPolicies ? '1' : '0' }}">
    <div id="whatsIncludedInputs"></div>

    {{-- What's included --}}
    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>What’s included in the session</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Let clients know what’s part of your service: sizing, placement, touch-ups, aftercare.</div>
        </div>
        <div class="row" style="gap:8px">
          <span class="faint" style="font-size:12.5px;font-weight:600">Show on page</span>
          <div class="tog {{ $whatsIncludedActive ? 'on' : '' }}" id="inclTog" data-own="1" role="switch" aria-checked="{{ $whatsIncludedActive ? 'true' : 'false' }}" tabindex="0"></div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div id="inclist"></div>
        <input class="in" id="incnew" placeholder="e.g. Add a service detail" style="width:100%;margin-top:2px">
        <button type="button" class="incadd" id="incadd"><span class="ms">add</span>Add item</button>
        <div class="lbl" style="margin:16px 0 8px">Suggestions</div>
        <div class="chips" id="incsug" style="gap:8px;flex-wrap:wrap"></div>
      </div>
    </div>

    <div class="grid mp-pol-grid" style="grid-template-columns:1fr 340px;gap:24px;align-items:start">
      <div class="card">
        <div class="ch">
          <div>
            <h3>Your booking policies</h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Written for you from your booking rules and deposit settings</div>
          </div>
          <div class="row" style="gap:8px">
            <span class="faint" style="font-size:12.5px;font-weight:600">Show on page</span>
            <div class="tog {{ $displayPolicies ? 'on' : '' }}" id="polTog" data-own="1" role="switch" aria-checked="{{ $displayPolicies ? 'true' : 'false' }}" tabindex="0"></div>
          </div>
        </div>
        <div style="padding:18px 22px">
          <div class="tint" style="padding:16px 18px;line-height:1.7;font-size:13.5px">
            <div class="row" style="gap:8px;align-items:flex-start">
              <span class="ms" style="color:#3E007C;margin-top:3px">payments</span>
              <div><b>Deposit:</b> {{ $policyCopy['deposit'] }}</div>
            </div>
            <div class="row" style="gap:8px;align-items:flex-start;margin-top:8px">
              <span class="ms" style="color:#3E007C;margin-top:3px">event_repeat</span>
              <div><b>Rescheduling:</b> {{ $policyCopy['rescheduling'] }}</div>
            </div>
            <div class="row" style="gap:8px;align-items:flex-start;margin-top:8px">
              <span class="ms" style="color:#3E007C;margin-top:3px">event_busy</span>
              <div><b>Cancellation:</b> {{ $policyCopy['cancellation'] }}</div>
            </div>
            <div class="row" style="gap:8px;align-items:flex-start;margin-top:8px">
              <span class="ms" style="color:#3E007C;margin-top:3px">person_off</span>
              <div><b>No-show or late cancellation:</b> {{ $policyCopy['no_show'] }}</div>
            </div>
          </div>

          <label class="fl" for="extraNote" style="margin-top:18px">Anything else? <span class="faint" style="font-weight:500">(optional)</span></label>
          <textarea class="in" id="extraNote" name="policies_extra_note" maxlength="1000" placeholder="e.g. Please arrive 10 minutes early and eat before your session.">{{ $extraNote }}</textarea>

          <div class="row pgsaverow" style="justify-content:flex-start;margin-top:14px">
            <button type="button" class="btn pgsave" id="policiesSaveBtn" disabled>
              <span class="ms">save</span>Save changes
            </button>
          </div>
        </div>
      </div>

      <div>
        <div class="card pad">
          <h3 style="font-size:14.5px">Change a rule</h3>
          <div class="faint" style="font-size:12.5px;margin-top:4px;line-height:1.5">The text updates automatically when you change these settings.</div>
          <a class="rule-link" href="{{ route('settings.calendar') }}">
            <span class="ms">event_repeat</span>
            <span style="flex:1">Reschedule and cancellation</span>
            <span class="faint" style="font-size:12px">Schedule</span>
            <span class="ms faint">chevron_right</span>
          </a>
          <a class="rule-link" href="{{ route('settings.preferences') }}">
            <span class="ms">payments</span>
            <span style="flex:1">Deposit amount</span>
            <span class="faint" style="font-size:12px">Money</span>
            <span class="ms faint">chevron_right</span>
          </a>
        </div>
      </div>
    </div>
  </form>

  <div class="mp-toast" id="mpToast" role="status"><span class="ms">check_circle</span><span id="mpToastText">Changes saved</span></div>
  <div class="mp-leave" id="mpLeave" role="dialog" aria-modal="true">
    <div class="mp-leave-box">
      <button type="button" class="mp-leave-x" id="mpLeaveClose" aria-label="Close"><span class="ms">close</span></button>
      <div style="padding:24px 24px 16px">
        <div style="width:48px;height:48px;border-radius:12px;background:#FFF3DC;color:#B7791F;display:flex;align-items:center;justify-content:center;margin-bottom:14px">
          <span class="ms">warning</span>
        </div>
        <h2 style="font-size:20px;font-weight:800;margin-bottom:8px;padding-right:28px">Your changes are not saved</h2>
        <p style="color:#3a3a3a;line-height:1.55;margin:0">Save them before you leave, or leave without saving and lose them.</p>
      </div>
      <div style="display:flex;gap:10px;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-wrap:wrap">
        <button type="button" class="btn ghost" id="mpLeaveDiscard" style="flex:1;justify-content:center">Leave without saving</button>
        <button type="button" class="btn" id="mpLeaveSave" style="flex:1;justify-content:center"><span class="ms">save</span>Save and leave</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  var CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var UPDATE_URL = @json(route('personal-page.policies.update'));
  var ITEMS = @json($whatsIncludedItems);
  var SUG = [
    'Sizing consultation', 'Placement guidance', 'Color matching consultation', 'Custom adjustments',
    'Stencil preview', 'Breaks as needed', 'Reference photos provided', 'Aftercare instructions',
    'Aftercare product recommendations', 'Healing check-in (photo review)'
  ];
  var dirty = false;
  var pendingNav = null;
  var pendingReload = false;
  var toastEl = document.getElementById('mpToast');
  var leaveEl = document.getElementById('mpLeave');
  var form = document.getElementById('policiesForm');

  function toast(msg) {
    if (!toastEl) return;
    document.getElementById('mpToastText').textContent = msg || 'Changes saved';
    toastEl.classList.add('on');
    clearTimeout(toastEl._t);
    toastEl._t = setTimeout(function () { toastEl.classList.remove('on'); }, 2600);
  }
  function setDirty(v) {
    dirty = !!v;
    var b = document.getElementById('policiesSaveBtn');
    if (b) { b.disabled = !dirty; b.classList.toggle('on', dirty); }
  }
  function markDirty() { setDirty(true); }

  function runPendingLeave() {
    if (pendingReload) { pendingReload = false; window.location.reload(); return; }
    var n = pendingNav; pendingNav = null; if (n) n.click();
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

  // Quick links
  var qlWrap = document.getElementById('quickLinks');
  var qlBtn = document.getElementById('quickLinksBtn');
  var qlMenu = document.getElementById('quickLinksMenu');
  if (qlWrap && qlBtn && qlMenu) {
    function openQl(show) {
      qlMenu.hidden = !show;
      qlBtn.setAttribute('aria-expanded', show ? 'true' : 'false');
      var cv = qlBtn.querySelector('.cv');
      if (cv) cv.textContent = show ? 'expand_less' : 'expand_more';
    }
    qlBtn.addEventListener('click', function (e) { e.stopPropagation(); openQl(qlMenu.hidden); });
    qlMenu.addEventListener('click', function (e) {
      e.stopPropagation();
      var c = e.target.closest('[data-ql-copy]'); if (!c) return;
      var u = c.getAttribute('data-ql-copy') || '';
      try { navigator.clipboard.writeText(u).catch(function () {}); } catch (x) {}
      c.classList.add('done');
      var icon = c.querySelector('.ms');
      if (icon) icon.textContent = 'check';
      setTimeout(function () { c.classList.remove('done'); if (icon) icon.textContent = 'content_copy'; }, 1600);
      toast('Link copied: ' + (c.getAttribute('data-ql-label') || u));
    });
    document.addEventListener('click', function (e) {
      if (!qlMenu.hidden && !qlWrap.contains(e.target)) openQl(false);
    });
  }

  // Toggles
  function wireTog(el, hiddenId) {
    if (!el) return;
    el.addEventListener('click', function () {
      el.classList.toggle('on');
      el.setAttribute('aria-checked', el.classList.contains('on'));
      document.getElementById(hiddenId).value = el.classList.contains('on') ? '1' : '0';
      markDirty();
    });
  }
  wireTog(document.getElementById('inclTog'), 'whatsIncludedActive');
  wireTog(document.getElementById('polTog'), 'displayPolicies');

  // What's included list
  var L = document.getElementById('inclist');
  var N = document.getElementById('incnew');
  var S = document.getElementById('incsug');
  var inputsBox = document.getElementById('whatsIncludedInputs');
  function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }
  function syncInputs() {
    inputsBox.innerHTML = ITEMS.map(function (v) {
      return '<input type="hidden" name="design_whats_included[]" value="' + esc(v) + '">';
    }).join('');
  }
  function renderInc() {
    L.innerHTML = ITEMS.map(function (v, i) {
      return '<div class="incrow"><input class="in" data-i="' + i + '" value="' + esc(v) + '">' +
        '<button type="button" class="incx" data-i="' + i + '" aria-label="Remove"><span class="ms">close</span></button></div>';
    }).join('');
    var low = ITEMS.map(function (x) { return x.trim().toLowerCase(); });
    S.innerHTML = SUG.map(function (s) {
      var used = low.indexOf(s.toLowerCase()) > -1 || ITEMS.length >= 8;
      return '<button type="button" class="incsug" ' + (used ? 'disabled' : '') + ' data-s="' + esc(s) + '"><span class="ms">add</span>' + esc(s) + '</button>';
    }).join('');
    syncInputs();
  }
  function addItem(v) {
    v = (v || '').trim();
    if (!v || ITEMS.length >= 8) return;
    if (ITEMS.map(function (x) { return x.toLowerCase(); }).indexOf(v.toLowerCase()) < 0) {
      ITEMS.push(v);
      renderInc();
      markDirty();
    }
  }
  L.addEventListener('input', function (e) {
    var i = e.target.dataset.i;
    if (i == null) return;
    ITEMS[+i] = e.target.value;
    syncInputs();
    markDirty();
    var low = ITEMS.map(function (x) { return x.trim().toLowerCase(); });
    S.querySelectorAll('.incsug').forEach(function (b) {
      b.disabled = low.indexOf(b.dataset.s.toLowerCase()) > -1 || ITEMS.length >= 8;
    });
  });
  L.addEventListener('click', function (e) {
    var b = e.target.closest('.incx'); if (!b) return;
    ITEMS.splice(+b.dataset.i, 1);
    renderInc();
    markDirty();
  });
  N.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); addItem(N.value); N.value = ''; }
  });
  document.getElementById('incadd')?.addEventListener('click', function () {
    if (N.value.trim()) { addItem(N.value); N.value = ''; }
    N.focus();
  });
  S.addEventListener('click', function (e) {
    var b = e.target.closest('.incsug');
    if (b && !b.disabled) addItem(b.dataset.s);
  });
  renderInc();

  document.getElementById('extraNote')?.addEventListener('input', markDirty);

  function savePolicies(andLeave) {
    var btn = document.getElementById('policiesSaveBtn');
    if (btn) { btn.disabled = true; btn.classList.remove('on'); }
    syncInputs();
    var fd = new FormData(form);
    return fetch(UPDATE_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.j.success) throw new Error(res.j.message || 'Could not save');
        setDirty(false);
        toast(res.j.message || 'Changes saved');
        if (andLeave) runPendingLeave();
      }).catch(function (e) {
        toast(e.message || 'Could not save');
        setDirty(true);
      });
  }

  document.getElementById('policiesSaveBtn')?.addEventListener('click', function () { savePolicies(false); });
  document.getElementById('mpLeaveClose')?.addEventListener('click', dismissLeaveModal);
  leaveEl?.addEventListener('click', function (e) { if (e.target === leaveEl) dismissLeaveModal(); });
  document.getElementById('mpLeaveDiscard')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    setDirty(false);
    runPendingLeave();
  });
  document.getElementById('mpLeaveSave')?.addEventListener('click', function () {
    leaveEl.classList.remove('on');
    savePolicies(true);
  });

  document.addEventListener('click', function (e) {
    if (!dirty) return;
    var n = e.target.closest('aside a[href], .tabs a[href], a[href]:not([href="#"])');
    if (!n || n.closest('.pgsaverow') || n.closest('#mpLeave') || n.closest('#quickLinks')) return;
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
})();
</script>
@endsection
