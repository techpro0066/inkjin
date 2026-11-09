@extends('layouts.studio-dashboard-layout')

@section('title', 'Studio Page · About')

@push('styles')
@include('partials.page-quick-links-styles')
<style>
  .studio-page textarea.in{font:inherit;font-size:13.5px;width:100%;min-height:86px;resize:vertical;outline:none;align-items:flex-start;line-height:1.45}
  .studio-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex:none;cursor:pointer;display:inline-block}
  .studio-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px}
  .studio-page .tog.on{background:var(--pri)}.studio-page .tog.on:after{left:19px}
  .studio-page .btns{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .studio-page .pgsave{opacity:.4;pointer-events:none}
  .studio-page .pgsave.is-dirty{opacity:1;pointer-events:auto}
  .studio-page .tabs a.is-soon{opacity:.55;cursor:default}
  .studio-page .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;padding:24px;z-index:200}
  .studio-page .smodal[hidden]{display:none!important}
  .studio-page .smbox{background:#fff;border-radius:18px;width:460px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);display:flex;flex-direction:column}
  .studio-page .smh{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:20px 22px 12px}
  .studio-page .smh h2{margin:0;font-size:19px;font-weight:800}
  .studio-page .smh .x{border:0;background:none;cursor:pointer;color:var(--faint);padding:2px}
  .studio-page .smf{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;padding:14px 22px;border-top:1px solid var(--line)}
  .studio-page #sps.card{overflow:visible}
  .studio-page #sps .stwrap{z-index:40}
  .studio-page #sps .stmenu{z-index:50}
  .studio-page .sps-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
  .studio-page .sps-ro{display:inline-flex;align-items:center;border:1px solid #E8E1EC;background:#FAF7FC;border-radius:20px;padding:6px 12px;font-size:12.5px;font-weight:600;color:#4A4450}
  .studio-page .sps-who{font-size:12px;color:var(--muted);margin-top:8px;line-height:1.5}
  .studio-page .sps-sep{border:0;border-top:1px solid #F0EAF3;margin:20px 0}
  .studio-page .sps-row{display:flex;justify-content:space-between;align-items:center;gap:16px}
  .studio-page .sps-row>span{flex:1;min-width:0}
  .studio-page .sps-row b{font-size:13.5px;display:block}
  .studio-page .sps-row .faint{font-size:12px}
  .studio-page .stwrap{position:relative}
  .studio-page .stsearch{cursor:text}
  .studio-page .stmenu{position:absolute;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 14px 40px rgba(40,10,60,.16);max-height:240px;overflow-y:auto;z-index:30;padding:6px}
  .studio-page .sto{display:flex;align-items:center;justify-content:space-between;width:100%;border:0;background:none;font:inherit;font-size:14px;padding:11px 12px;border-radius:9px;cursor:pointer;text-align:left}
  .studio-page .sto:hover{background:#F8F2FC}
  .studio-page .sto .ms{font-size:20px;color:#C8BFCC}
  .studio-page .sto.on .ms{color:#3E007C;font-variation-settings:"FILL" 1}
  .studio-page .sto small{font-size:12px;color:var(--muted);margin-left:auto;margin-right:10px}
  .studio-page .stchip{display:inline-flex;align-items:center;gap:6px;background:#3E007C;color:#fff;border:0;border-radius:20px;padding:6px 10px 6px 13px;font:inherit;font-size:12.5px;font-weight:600;cursor:pointer}
  .studio-page .stchip .ms{font-size:15px}
  .studio-page .stempty{padding:12px;color:var(--faint);font-size:13px}
  .studio-page .svcs{display:flex;flex-wrap:wrap;gap:8px}
  .studio-page .svc{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:12px;padding:9px 13px;font-size:13.5px;font-weight:600;cursor:pointer;background:#fff;margin:0}
  .studio-page .svc input[type=checkbox]{-webkit-appearance:none;appearance:none;width:18px;height:18px;min-width:18px;margin:0;border:1.5px solid #CFC6D4;background:#fff;border-radius:5px;display:inline-grid;place-content:center;cursor:pointer;flex:none;box-sizing:border-box}
  .studio-page .svc input[type=checkbox]:hover{border-color:#3E007C}
  .studio-page .svc input[type=checkbox]:checked{border-color:#3E007C;background:#3E007C url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center/13px 13px no-repeat}
  .studio-page .svc:has(input:checked){border-color:#3E007C;background:#FBF5FF}
  .studio-page .svc:focus-within{outline:2px solid #3E007C;outline-offset:2px}
  @media (max-width:700px){
    .studio-page .head .btns{width:100%}
  }
</style>
@endpush

@section('content')
@php
  $pageUrl = $pageUrl ?? 'inkjin.com/@username';
  $pageHandle = $pageHandle ?? 'username';
  $publicPageUrl = $publicPageUrl ?? ('https://'.$pageUrl);
  $quickLinkRows = $quickLinkRows ?? [
    ['suffix' => '', 'hash' => ''],
    ['suffix' => '/artists', 'hash' => '#artists'],
    ['suffix' => '/portfolio', 'hash' => '#work'],
    ['suffix' => '/guest-artists', 'hash' => '#guest-artists'],
    ['suffix' => '/find-us', 'hash' => '#findus'],
  ];
  $description = $description ?? '';
  $showMap = $showMap ?? true;
  $showArtistStyles = $showArtistStyles ?? true;
  $studioStyles = $studioStyles ?? [];
  $artistStyles = $artistStyles ?? [];
  $styleOptions = $styleOptions ?? [];
  $extraServiceOptions = $extraServiceOptions ?? ['Touch-ups', 'Cover-ups', 'Blackout', 'Other'];
  $extraServices = $extraServices ?? [];
  $extraServiceOther = $extraServiceOther ?? '';
  $acceptsWalkIns = $acceptsWalkIns ?? false;
  $walkInsNote = $walkInsNote ?? '';
@endphp
<div class="studio-page">
  <div class="head">
    <div>
      <h1>
        Studio Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338715-studio-dashboard-studio-page-about" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
      </h1>
      <div class="sub">What clients see at {{ $pageUrl }}.</div>
    </div>
    <div class="btns">
      @include('partials.page-quick-links', [
        'qlPublicUrl' => $publicPageUrl,
        'qlDisplayHost' => $pageUrl,
        'qlRows' => $quickLinkRows,
        'qlButtonLabel' => 'Open studio page',
        'qlButtonClass' => 'btn',
        'qlFallbackUrl' => route('studio.page.about'),
      ])
    </div>
  </div>

  @include('studio.page.partials.tabs')

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Description</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px"></div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <textarea class="in" id="studioDescription" name="description" maxlength="600" placeholder="Tell clients about your studio…">{{ $description }}</textarea>
      <div class="help">Up to 600 characters.</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px" id="sps">
    <div class="ch">
      <div>
        <h3>Styles, services &amp; walk-ins</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Helps clients find your studio by style and by what you offer</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="sps-row">
        <span>
          <b>Show your artists’ styles</b>
          <span class="faint">From your resident artists’ pages</span>
        </span>
        <div style="flex:none" class="tog {{ $showArtistStyles ? 'on' : '' }}" id="sps-show" role="switch" aria-checked="{{ $showArtistStyles ? 'true' : 'false' }}" aria-label="Show your artists' styles" tabindex="0"></div>
      </div>
      <div id="sps-art" @if(! $showArtistStyles) style="display:none" @endif>
        <div class="sps-chips">
          @foreach ($artistStyles as $style)
            <span class="sps-ro">{{ $style }}</span>
          @endforeach
        </div>
        <div class="sps-who">These come from your artists’ pages. They update when an artist joins, leaves or changes their styles. Guest artists aren’t included.</div>
      </div>

      <span class="fl" style="margin-top:18px;display:block">Studio styles <span class="faint" style="font-weight:500">(optional)</span></span>
      <div class="stwrap">
        <div class="in stsearch" id="sps-search">
          <span class="ms">search</span>
          <input id="sps-q" placeholder="Search styles..." autocomplete="off" aria-label="Search styles" style="border:0;outline:0;font:inherit;flex:1;background:none">
        </div>
        <div class="stmenu" id="sps-menu" hidden role="listbox" aria-multiselectable="true"></div>
      </div>
      <div class="sps-chips" id="sps-sel"></div>
      <div class="help">Add styles your studio is known for. They show first on your studio page, before your artists’ styles.</div>

      <hr class="sps-sep">

      <span class="fl">Services you offer <span class="faint" style="font-weight:500">(optional)</span></span>
      <div class="svcs" id="sps-svcs">
        @foreach ($extraServiceOptions as $svc)
          <label class="svc">
            <input type="checkbox" value="{{ $svc }}" @if($svc === 'Other') id="sps-other-cb" @endif @checked(in_array($svc, $extraServices, true))>
            <span>{{ $svc }}</span>
          </label>
        @endforeach
      </div>
      <div id="sps-ow" @if(! in_array('Other', $extraServices, true)) hidden @endif style="margin-top:10px;max-width:360px">
        <span class="fl">Other service</span>
        <input class="in" id="sps-ot" maxlength="40" placeholder="e.g. Scar cover-ups" value="{{ $extraServiceOther }}">
        <div class="help">Up to 40 characters.</div>
      </div>
      <div class="help">Shown on your studio page under your styles.</div>

      <hr class="sps-sep">

      <div class="sps-row">
        <span>
          <b>We take walk-ins</b>
          <span class="faint">Clients see “Walk-ins welcome” on your studio page</span>
        </span>
        <div style="flex:none" class="tog {{ $acceptsWalkIns ? 'on' : '' }}" id="sps-walk" role="switch" aria-checked="{{ $acceptsWalkIns ? 'true' : 'false' }}" aria-label="We take walk-ins" tabindex="0"></div>
      </div>
      <div id="sps-nw" @if(! $acceptsWalkIns) hidden @endif style="margin-top:12px;max-width:420px">
        <span class="fl">When (optional)</span>
        <input class="in" id="sps-note" maxlength="60" placeholder="e.g. Weekdays from 12:00" value="{{ $walkInsNote }}">
        <div class="help">Up to 60 characters.</div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Shown on the page</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Name, address, logo and contact come from Account &gt; Studio.</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="row" style="justify-content:space-between;padding:0;flex-wrap:nowrap;gap:16px;align-items:center">
        <span style="flex:1;min-width:0">
          <span style="font-size:13.5px;font-weight:600;display:block">Show map</span>
          <span class="faint" style="font-size:12px">Shows a map with your address at the bottom of your studio page.</span>
        </span>
        <div style="flex:none" class="tog {{ $showMap ? 'on' : '' }}" id="showMapTog" role="switch" aria-checked="{{ $showMap ? 'true' : 'false' }}" tabindex="0"></div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Social links</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Shown on your studio page. All optional.</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="formgrid">
        <div>
          <span class="fl" style="display:flex;align-items:center;gap:6px"><span class="ms" style="font-size:17px;color:var(--muted)">photo_camera</span>Instagram</span>
          <input class="in" type="text" id="instagram" name="instagram" value="{{ $instagram }}" placeholder="instagram.com/yourstudio">
        </div>
        <div>
          <span class="fl" style="display:flex;align-items:center;gap:6px"><span class="ms" style="font-size:17px;color:var(--muted)">language</span>Website</span>
          <input class="in" type="text" id="website" name="website" value="{{ $website }}" placeholder="yourstudio.com">
        </div>
        <div>
          <span class="fl" style="display:flex;align-items:center;gap:6px"><span class="ms" style="font-size:17px;color:var(--muted)">music_note</span>TikTok</span>
          <input class="in" type="text" id="tiktok" name="tiktok" value="{{ $tiktok }}" placeholder="tiktok.com/@yourstudio">
        </div>
        <div>
          <span class="fl" style="display:flex;align-items:center;gap:6px"><span class="ms" style="font-size:17px;color:var(--muted)">thumb_up</span>Facebook</span>
          <input class="in" type="text" id="facebook" name="facebook" value="{{ $facebook }}" placeholder="facebook.com/yourstudio">
        </div>
        <div>
          <span class="fl" style="display:flex;align-items:center;gap:6px"><span class="ms" style="font-size:17px;color:var(--muted)">smart_display</span>YouTube</span>
          <input class="in" type="text" id="youtube" name="youtube" value="{{ $youtube }}" placeholder="youtube.com/@yourstudio">
        </div>
      </div>
      <div class="help">Instagram and Website come from your studio setup. Changes show on your studio page once you save.</div>
    </div>
  </div>

  <div class="pgsaverow">
    <button class="btn pgsave" type="button" id="pageSaveBtn"><span class="ms">save</span>Save changes</button>
  </div>
</div>

<div class="smodal" id="leaveguard" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="leaveguardTitle">
    <div class="smh">
      <div>
        <h2 id="leaveguardTitle">Your changes are not saved</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px">Save them before you leave this page?</div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" id="lg-leave">Leave without saving</button>
      <button type="button" class="btn" id="lg-save">Save and leave</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var root = document.querySelector('.studio-page');
  if (!root) return;

  var CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var SAVE_URL = @json($updateAboutUrl ?? route('studio.page.about.update'));
  var ALL = @json($styleOptions);
  var AS = @json($artistStyles);
  var own = @json($studioStyles);

  var saveBtn = document.getElementById('pageSaveBtn');
  var dirty = false;
  var pendingHref = null;
  var saving = false;

  function toast(msg, ok) {
    if (typeof window.bpToast === 'function') {
      window.bpToast(msg);
      return;
    }
    var n = window.innerWidth <= 900;
    var t = document.createElement('div');
    t.setAttribute('role', 'status');
    t.style.cssText = 'position:fixed;top:' + (n ? '74px' : '22px') + ';' + (n ? 'left:16px;right:16px;' : 'right:24px;') + 'background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:99;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center';
    t.innerHTML = '<span class="ms" style="color:' + (ok === false ? '#FF6B6B' : '#3DD68C') + '">' + (ok === false ? 'error' : 'check_circle') + '</span>' + msg;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 2800);
  }

  function setDirty(v) {
    dirty = !!v;
    if (saveBtn) saveBtn.classList.toggle('is-dirty', dirty);
  }

  function openLeave() {
    var m = document.getElementById('leaveguard');
    if (m) m.hidden = false;
  }

  function closeLeave() {
    var m = document.getElementById('leaveguard');
    if (m) m.hidden = true;
  }

  function togOn(id) {
    var el = document.getElementById(id);
    return !!(el && el.classList.contains('on'));
  }

  function collectPayload() {
    var services = [];
    document.querySelectorAll('#sps-svcs input[type="checkbox"]').forEach(function (cb) {
      if (cb.checked) services.push(cb.value);
    });
    return {
      description: (document.getElementById('studioDescription')?.value || '').trim(),
      show_map: togOn('showMapTog'),
      show_artist_styles: togOn('sps-show'),
      tattoo_styles: own.slice(),
      extra_services: services,
      extra_service_other: (document.getElementById('sps-ot')?.value || '').trim(),
      accepts_walk_ins: togOn('sps-walk'),
      walk_ins_note: (document.getElementById('sps-note')?.value || '').trim(),
      social_links: {
        instagram: (document.getElementById('instagram')?.value || '').trim(),
        website: (document.getElementById('website')?.value || '').trim(),
        tiktok: (document.getElementById('tiktok')?.value || '').trim(),
        facebook: (document.getElementById('facebook')?.value || '').trim(),
        youtube: (document.getElementById('youtube')?.value || '').trim()
      }
    };
  }

  function firstError(j) {
    if (!j) return 'Could not save';
    if (j.message && typeof j.message === 'string' && !j.errors) return j.message;
    if (j.errors) {
      var keys = Object.keys(j.errors);
      if (keys.length && j.errors[keys[0]] && j.errors[keys[0]][0]) {
        return j.errors[keys[0]][0];
      }
    }
    return j.message || 'Could not save';
  }

  var saveBtnDefaultHtml = saveBtn ? saveBtn.innerHTML : '';
  var lgSave = document.getElementById('lg-save');
  var lgSaveDefaultHtml = lgSave ? lgSave.innerHTML : '';

  function setSavingUi(on) {
    saving = !!on;
    if (saveBtn) {
      saveBtn.disabled = on;
      saveBtn.setAttribute('aria-busy', on ? 'true' : 'false');
      if (on) {
        saveBtn.classList.add('is-dirty');
        saveBtn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
      } else {
        saveBtn.innerHTML = saveBtnDefaultHtml;
      }
    }
    if (lgSave) {
      lgSave.disabled = on;
      if (on) lgSave.textContent = 'Saving…';
      else lgSave.innerHTML = lgSaveDefaultHtml;
    }
  }

  function saveAbout(andLeave) {
    if (saving) return Promise.resolve(false);
    setSavingUi(true);

    return fetch(SAVE_URL, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(collectPayload())
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.j.success) throw new Error(firstError(res.j));
        setDirty(false);
        toast(res.j.message || 'Changes saved');
        if (andLeave && pendingHref) {
          try { sessionStorage.setItem('bp-toast', res.j.message || 'Changes saved'); } catch (x) {}
          location.href = pendingHref;
          return true;
        }
        setSavingUi(false);
        return true;
      })
      .catch(function (e) {
        toast(e.message || 'Could not save', false);
        setDirty(true);
        setSavingUi(false);
        return false;
      });
  }

  root.addEventListener('input', function () { setDirty(true); });
  root.addEventListener('change', function () { setDirty(true); });

  function bindTog(el, onChange) {
    if (!el) return;
    function flip() {
      el.classList.toggle('on');
      el.setAttribute('aria-checked', el.classList.contains('on') ? 'true' : 'false');
      if (typeof onChange === 'function') onChange(el.classList.contains('on'));
      setDirty(true);
    }
    el.addEventListener('click', flip);
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        flip();
      }
    });
  }

  bindTog(document.getElementById('showMapTog'));
  bindTog(document.getElementById('sps-show'), function (on) {
    var art = document.getElementById('sps-art');
    if (art) art.style.display = on ? '' : 'none';
  });
  bindTog(document.getElementById('sps-walk'), function (on) {
    var nw = document.getElementById('sps-nw');
    if (nw) nw.hidden = !on;
  });

  document.getElementById('sps-svcs')?.addEventListener('change', function () {
    var ow = document.getElementById('sps-ow');
    var cb = document.getElementById('sps-other-cb');
    if (ow) ow.hidden = !(cb && cb.checked);
  });

  (function stylesPicker() {
    var q = document.getElementById('sps-q');
    var m = document.getElementById('sps-menu');
    var s = document.getElementById('sps-sel');
    var search = document.getElementById('sps-search');
    var show = document.getElementById('sps-show');
    if (!q || !m || !s) return;
    if (!Array.isArray(ALL)) ALL = [];
    if (!Array.isArray(AS)) AS = [];
    if (!Array.isArray(own)) own = [];

    function esc(x) {
      return String(x).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
    }
    function menu() {
      var f = q.value.trim().toLowerCase();
      var L = ALL.filter(function (x) { return !f || x.toLowerCase().indexOf(f) > -1; });
      var showOn = show && show.classList.contains('on');
      m.innerHTML = L.length
        ? L.map(function (x) {
            var on = own.indexOf(x) > -1;
            var fa = AS.indexOf(x) > -1 && showOn;
            return '<button type="button" class="sto' + (on ? ' on' : '') + '" role="option" aria-selected="' + on + '" data-s="' + esc(x) + '">' + esc(x) + (fa && !on ? '<small>From your artists</small>' : '') + '<span class="ms">' + (on ? 'check_box' : 'check_box_outline_blank') + '</span></button>';
          }).join('')
        : '<div class="stempty">No styles match “' + esc(q.value) + '”</div>';
    }
    function chips() {
      s.innerHTML = own.map(function (x) {
        return '<button type="button" class="stchip" data-s="' + esc(x) + '" aria-label="Remove ' + esc(x) + '">' + esc(x) + '<span class="ms">close</span></button>';
      }).join('');
    }
    function openMenu() {
      menu();
      m.hidden = false;
    }
    function closeMenu() {
      m.hidden = true;
    }

    q.addEventListener('focus', openMenu);
    q.addEventListener('click', openMenu);
    q.addEventListener('input', openMenu);
    search?.addEventListener('click', function (e) {
      e.stopPropagation();
      q.focus();
      openMenu();
    });
    m.addEventListener('mousedown', function (e) { e.preventDefault(); });
    m.addEventListener('click', function (e) {
      var o = e.target.closest('.sto');
      if (!o) return;
      var v = o.getAttribute('data-s');
      var i = own.indexOf(v);
      if (i > -1) own.splice(i, 1); else own.push(v);
      menu(); chips(); setDirty(true);
    });
    s.addEventListener('click', function (e) {
      var c = e.target.closest('.stchip');
      if (!c) return;
      var i = own.indexOf(c.getAttribute('data-s'));
      if (i > -1) own.splice(i, 1);
      chips();
      if (!m.hidden) menu();
      setDirty(true);
    });
    document.addEventListener('click', function (e) {
      if (e.target.closest('#sps .stwrap')) return;
      closeMenu();
    });
    q.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closeMenu(); e.stopPropagation(); }
    });
    chips();
  })();

  saveBtn?.addEventListener('click', function () {
    if (!dirty || saving) return;
    saveAbout(false);
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-close]') || e.target.id === 'leaveguard') {
      closeLeave();
      return;
    }
    if (e.target.closest('#lg-leave')) {
      dirty = false;
      if (pendingHref) location.href = pendingHref;
      return;
    }
    if (e.target.closest('#lg-save')) {
      closeLeave();
      saveAbout(true);
      return;
    }
    var soon = e.target.closest('.tabs a.is-soon');
    if (soon) {
      e.preventDefault();
      return;
    }
    var link = e.target.closest('a[href]');
    if (link && dirty && !link.closest('#leaveguard') && !link.closest('#quickLinks') && !link.getAttribute('href').startsWith('#') && !link.target) {
      e.preventDefault();
      pendingHref = link.getAttribute('href');
      openLeave();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLeave();
  });

  try {
    var q = sessionStorage.getItem('bp-toast');
    if (q) {
      sessionStorage.removeItem('bp-toast');
      setTimeout(function () { toast(q); }, 120);
    }
  } catch (e) {}
})();
</script>
@include('partials.page-quick-links-script')
@endpush
