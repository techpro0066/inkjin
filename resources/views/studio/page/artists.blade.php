@extends('layouts.studio-dashboard-layout')

@section('title', 'Studio Page · Artists')

@push('styles')
@include('partials.page-quick-links-styles')
<style>
  .studio-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex:none;cursor:pointer;display:inline-block}
  .studio-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px}
  .studio-page .tog.on{background:var(--pri)}.studio-page .tog.on:after{left:19px}
  .studio-page .btns{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .studio-page .pgsave{opacity:.4;pointer-events:none}
  .studio-page .pgsave.is-dirty{opacity:1;pointer-events:auto}
  .studio-page .pgsave:disabled{cursor:wait}
  .studio-page .tabs a.is-soon{opacity:.55;cursor:default}
  .studio-page .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;padding:24px;z-index:200}
  .studio-page .smodal[hidden]{display:none!important}
  .studio-page .smbox{background:#fff;border-radius:18px;width:460px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);display:flex;flex-direction:column}
  .studio-page .smh{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:20px 22px 12px}
  .studio-page .smh h2{margin:0;font-size:19px;font-weight:800}
  .studio-page .smh .x{border:0;background:none;cursor:pointer;color:var(--faint);padding:2px}
  .studio-page .smf{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;padding:14px 22px;border-top:1px solid var(--line)}
  .studio-page .arow{gap:12px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;margin-bottom:8px;background:#fff;flex-wrap:nowrap}
  .studio-page .arow .drag{cursor:grab;user-select:none;touch-action:none}
  .studio-page .arow.dragging{opacity:.55;background:#F7F3F9}
  .studio-page .arow .avatar{width:36px;height:36px;background-size:cover;background-position:center}
  .studio-page .empty{font-size:13.5px;color:var(--muted);padding:8px 0}
  @media (max-width:700px){
    .studio-page .head .btns{width:100%}
    .studio-page .arow{flex-wrap:wrap}
  }
</style>
@endpush

@section('content')
@php
  $pageUrl = $pageUrl ?? 'inkjin.com/@username';
  $publicPageUrl = $publicPageUrl ?? ('https://'.$pageUrl);
  $quickLinkRows = $quickLinkRows ?? [
    ['suffix' => '', 'hash' => ''],
    ['suffix' => '/artists', 'hash' => '#artists'],
    ['suffix' => '/portfolio', 'hash' => '#work'],
    ['suffix' => '/guest-artists', 'hash' => '#guest-artists'],
    ['suffix' => '/find-us', 'hash' => '#findus'],
  ];
  $showGuestArtists = $showGuestArtists ?? true;
  $pageArtists = $pageArtists ?? collect();
@endphp
<div class="studio-page">
  <div class="head">
    <div>
      <h1>
        Studio Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338786-studio-dashboard-studio-page-artists" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
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
    <div class="ch" style="gap:16px">
      <div style="flex:1;min-width:0">
        <h3>Guest artists</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Shows visiting artists and their dates on your studio page.</div>
      </div>
      <div class="row" style="gap:10px;flex-shrink:0">
        <span class="faint" style="font-size:12.5px;font-weight:600">Show guest artists</span>
        <div class="tog {{ $showGuestArtists ? 'on' : '' }}" id="tguests" role="switch" aria-checked="{{ $showGuestArtists ? 'true' : 'false' }}" tabindex="0" aria-label="Show guest artists"></div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Order on your page</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Drag to change the order. Only artists you accepted can show.</div>
      </div>
    </div>
    <div style="padding:18px 22px" id="artistOrderList">
      @forelse ($pageArtists as $artist)
        <div class="row arow" data-id="{{ $artist['id'] }}">
          <span class="ms faint drag" aria-hidden="true">drag_indicator</span>
          @if (! empty($artist['avatar']))
            <div class="avatar" style="background-image:url('{{ $artist['avatar'] }}')" role="img" aria-label="{{ $artist['name'] }}"></div>
          @else
            <div class="avatar">{{ $artist['initials'] }}</div>
          @endif
          <div style="flex:1;min-width:0">
            <b>{{ $artist['name'] }}</b>
            <div class="faint" style="font-size:12px">{{ $artist['relationship'] }}</div>
          </div>
          <span class="faint" style="font-size:12.5px;font-weight:600">Show on page</span>
          <div class="tog {{ ! empty($artist['show_on_page']) ? 'on' : '' }}" role="switch" aria-checked="{{ ! empty($artist['show_on_page']) ? 'true' : 'false' }}" tabindex="0" aria-label="Show {{ $artist['name'] }} on page"></div>
        </div>
      @empty
        <div class="empty">No accepted artists yet. Invite or accept artists from Artists in the sidebar.</div>
      @endforelse
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
  var SAVE_URL = @json($updateArtistsUrl ?? route('studio.page.artists.update'));
  var saveBtn = document.getElementById('pageSaveBtn');
  var dirty = false;
  var pendingHref = null;
  var saving = false;
  var saveBtnDefaultHtml = saveBtn ? saveBtn.innerHTML : '';
  var lgSave = document.getElementById('lg-save');
  var lgSaveDefaultHtml = lgSave ? lgSave.innerHTML : '';

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

  function collectPayload() {
    var artists = [];
    document.querySelectorAll('#artistOrderList .arow').forEach(function (row) {
      var tog = row.querySelector('.tog');
      artists.push({
        id: parseInt(row.getAttribute('data-id'), 10),
        show_on_page: !!(tog && tog.classList.contains('on'))
      });
    });
    return {
      show_guest_artists: !!(document.getElementById('tguests') && document.getElementById('tguests').classList.contains('on')),
      artists: artists
    };
  }

  function firstError(j) {
    if (!j) return 'Could not save';
    if (j.message && typeof j.message === 'string' && !j.errors) return j.message;
    if (j.errors) {
      var keys = Object.keys(j.errors);
      if (keys.length && j.errors[keys[0]] && j.errors[keys[0]][0]) return j.errors[keys[0]][0];
    }
    return j.message || 'Could not save';
  }

  function saveArtists(andLeave) {
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

  function bindTog(el) {
    if (!el) return;
    function flip() {
      el.classList.toggle('on');
      el.setAttribute('aria-checked', el.classList.contains('on') ? 'true' : 'false');
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

  bindTog(document.getElementById('tguests'));
  document.querySelectorAll('#artistOrderList .arow .tog').forEach(bindTog);

  (function dragReorder() {
    var list = document.getElementById('artistOrderList');
    if (!list) return;
    var dragEl = null;

    list.addEventListener('pointerdown', function (e) {
      var handle = e.target.closest('.drag');
      if (!handle) return;
      var row = handle.closest('.arow');
      if (!row || !list.contains(row)) return;
      e.preventDefault();
      dragEl = row;
      row.classList.add('dragging');
      row.setPointerCapture?.(e.pointerId);

      function move(ev) {
        if (!dragEl) return;
        var y = ev.clientY;
        var sibs = [].filter.call(list.querySelectorAll('.arow'), function (x) { return x !== dragEl; });
        for (var i = 0; i < sibs.length; i++) {
          var r = sibs[i].getBoundingClientRect();
          if (y < r.top + r.height / 2) {
            if (dragEl.nextElementSibling !== sibs[i]) list.insertBefore(dragEl, sibs[i]);
            return;
          }
        }
        if (sibs.length && list.lastElementChild !== dragEl) {
          list.appendChild(dragEl);
        }
      }

      function up() {
        document.removeEventListener('pointermove', move);
        document.removeEventListener('pointerup', up);
        if (dragEl) {
          dragEl.classList.remove('dragging');
          dragEl = null;
          setDirty(true);
        }
      }

      document.addEventListener('pointermove', move);
      document.addEventListener('pointerup', up);
    });
  })();

  saveBtn?.addEventListener('click', function () {
    if (!dirty || saving) return;
    saveArtists(false);
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
      saveArtists(true);
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
