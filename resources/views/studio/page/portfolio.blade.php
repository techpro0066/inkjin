@extends('layouts.studio-dashboard-layout')

@section('title', 'Studio Page · Portfolio')

@push('styles')
<style>
  .studio-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex:none;cursor:pointer;display:inline-block}
  .studio-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px}
  .studio-page .tog.on{background:var(--pri)}.studio-page .tog.on:after{left:19px}
  .studio-page .btns{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .studio-page .ql{position:relative}
  .studio-page .ql-b{display:inline-flex;align-items:center;gap:8px;font:inherit;font-weight:600;font-size:13.5px;padding:9px 12px 9px 13px;border-radius:12px;border:1px solid #E8DFEA;background:#fff;color:var(--ink);cursor:pointer;white-space:nowrap}
  .studio-page .ql-b:hover{background:#F7F3F9}
  .studio-page .ql-b:focus-visible{outline:2px solid #3E007C;outline-offset:2px}
  .studio-page .ql-b .ms{font-size:18px}.studio-page .ql-b .cv{font-size:20px}
  .studio-page .ql-p{position:absolute;right:0;top:calc(100% + 8px);width:min(400px,calc(100vw - 32px));background:#fff;border:1px solid #EAE2EE;border-radius:16px;box-shadow:0 18px 44px rgba(20,0,40,.18);padding:6px;z-index:80}
  .studio-page .ql-p[hidden]{display:none}
  .studio-page .ql-r{display:flex;align-items:center;gap:6px;padding:4px 4px 4px 12px;border-radius:10px}
  .studio-page .ql-r:hover{background:#F7F3F9}
  .studio-page .ql-u{flex:1;min-width:0;font-size:13.5px;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .studio-page .ql-u b{font-weight:700}
  .studio-page .ql-i{width:34px;height:34px;border-radius:9px;border:0;background:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ink);flex-shrink:0;text-decoration:none}
  .studio-page .ql-i:hover{background:#EFE7F3}
  .studio-page .ql-i .ms{font-size:18px}.studio-page .ql-i.done .ms{color:#1F8A4C}
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
  .studio-page .spg{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}
  .studio-page .spf{position:relative;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff}
  .studio-page .spi{aspect-ratio:1;background:#222 center/cover}
  .studio-page .spc{padding:8px 10px 10px}
  .studio-page .spc b{display:block;font-size:12.5px;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .studio-page .in.sm{padding:6px 8px;font-size:12px;min-height:0;width:100%}
  .studio-page select.in.sm.needs-tag{border-color:#F3C98B;background:#FFF6EC}
  .studio-page .spx{position:absolute;top:6px;right:6px;width:26px;height:26px;border-radius:50%;border:0;background:rgba(0,0,0,.6);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer}
  .studio-page .spx .ms{font-size:16px}
  .studio-page .spadd{border:1.5px dashed #CDBBD9;border-radius:12px;background:#FBF6FD;color:#3E007C;font:inherit;font-weight:700;font-size:13px;display:flex;flex-direction:column;gap:6px;align-items:center;justify-content:center;min-height:150px;cursor:pointer;width:100%}
  .studio-page .spadd .ms{font-size:26px}
  .studio-page .igb{position:absolute;top:6px;left:6px;background:linear-gradient(135deg,#F58529,#DD2A7B 55%,#8134AF);color:#fff;font-size:10px;font-weight:800;border-radius:6px;padding:2px 6px;z-index:2}
  .studio-page .igb.static{position:static;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;font-size:12px;padding:0}
  .studio-page .empty{font-size:13.5px;color:var(--muted);padding:4px 0 8px}
  @media (max-width:700px){.studio-page .head .btns{width:100%}}
  @media (max-width:600px){.studio-page .ql-p{right:auto;left:0}}
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
  $showArtistsFlash = $showArtistsFlash ?? true;
  $showArtistsPortfolio = $showArtistsPortfolio ?? true;
  $artistWorkPreview = $artistWorkPreview ?? collect();
  $studioPieces = $studioPieces ?? collect();
  $taggableArtists = $taggableArtists ?? collect();
  $instagramConnected = $instagramConnected ?? false;
  $instagramUsername = $instagramUsername ?? '';
  $instagramImportCount = $instagramImportCount ?? 0;
@endphp
<div class="studio-page">
  <div class="head">
    <div>
      <h1>
        Studio Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338786-studio-dashboard-studio-page-portfolio" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
      </h1>
      <div class="sub">What clients see at {{ $pageUrl }}.</div>
    </div>
    <div class="btns">
      <div class="ql" id="quickLinks">
        <button type="button" class="ql-b" id="quickLinksBtn" aria-haspopup="true" aria-expanded="false" aria-controls="quickLinksMenu">
          <span class="ms">link</span>Quick links<span class="ms cv">expand_more</span>
        </button>
        <div class="ql-p" id="quickLinksMenu" hidden role="menu" aria-label="Quick links">
          @foreach ($quickLinkRows as $row)
            @php
              $displayPath = $pageUrl.$row['suffix'];
              $copyUrl = $publicPageUrl.$row['suffix'];
              $openUrl = $publicPageUrl.($row['hash'] ?? '');
            @endphp
            <div class="ql-r" role="none">
              <div class="ql-u" title="{{ $displayPath }}">
                {{ $pageUrl }}@if($row['suffix'] !== '')<b>{{ $row['suffix'] }}</b>@endif
              </div>
              <button type="button" class="ql-i" data-ql-copy="{{ $copyUrl }}" data-ql-label="{{ $displayPath }}" role="menuitem" title="Copy link">
                <span class="ms">content_copy</span>
              </button>
              <a class="ql-i" href="{{ $openUrl }}" target="_blank" rel="noopener" role="menuitem" title="Open">
                <span class="ms">open_in_new</span>
              </a>
            </div>
          @endforeach
        </div>
      </div>
      <a class="btn" href="{{ $publicPageUrl }}" target="_blank" rel="noopener">
        <span class="ms">open_in_new</span>Open studio page
      </a>
    </div>
  </div>

  @include('studio.page.partials.tabs')

  @if (session('success'))
    <div class="note" style="margin-bottom:14px"><span class="ms">check_circle</span>{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="note" style="margin-bottom:14px;border-color:#F3C9C9;background:#FDECEC;color:#C62828"><span class="ms">error</span>{{ session('error') }}</div>
  @endif

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Your artists' work</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Shown on your studio page automatically</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="row" style="justify-content:space-between;padding:2px 0 10px">
        <span style="font-size:13.5px;font-weight:600">Show artists' flash designs</span>
        <div class="tog {{ $showArtistsFlash ? 'on' : '' }}" id="togFlash" role="switch" aria-checked="{{ $showArtistsFlash ? 'true' : 'false' }}" tabindex="0"></div>
      </div>
      <div class="row" style="justify-content:space-between;padding:0 0 14px">
        <span style="font-size:13.5px;font-weight:600">Show artists' portfolio pieces</span>
        <div class="tog {{ $showArtistsPortfolio ? 'on' : '' }}" id="togPortfolio" role="switch" aria-checked="{{ $showArtistsPortfolio ? 'true' : 'false' }}" tabindex="0"></div>
      </div>
      <div class="spg" id="artistWorkPreview">
        @forelse ($artistWorkPreview as $item)
          <div class="spf">
            <div class="spi" @if($item['image']) style="background-image:url('{{ $item['image'] }}')" @endif></div>
            <div class="spc">
              <b>{{ $item['title'] }}</b>
              <div class="faint" style="font-size:11.5px">{{ $item['subtitle'] }}</div>
            </div>
          </div>
        @empty
          <div class="empty" style="grid-column:1/-1">No flash or portfolio pieces from your artists yet.</div>
        @endforelse
      </div>
      <div class="help">Every piece shows the name of the artist who made it and links to their page. Artists add and edit their own work in their dashboard.</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Studio portfolio</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Optional. Your own pieces, like work done at the studio or by past guest artists.</div>
      </div>
    </div>
    <div style="padding:18px 22px">
      <div class="row" style="gap:10px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;flex-wrap:wrap;margin-bottom:8px" id="igBox">
        <span class="igb static">IG</span>
        @if ($instagramConnected)
          <div style="flex:1;min-width:200px">
            <b style="font-size:13.5px">@{{ ltrim($instagramUsername, '@') }}</b>
            <span class="pill g">Connected</span>
            <div class="faint" style="font-size:12px;margin-top:2px">{{ $instagramImportCount }} posts imported. New pieces come in as “Tag the artist” so each one is credited to the right person.</div>
          </div>
          <button type="button" class="btn ghost sm" id="igRefreshBtn">Refresh</button>
          <button type="button" class="btn ghost sm" id="igDisconnectBtn">Disconnect</button>
        @else
          <div style="flex:1;min-width:200px">
            <b style="font-size:13.5px">Instagram</b>
            <div class="faint" style="font-size:12px;margin-top:2px">Connect to import posts into your studio portfolio.</div>
          </div>
          <a class="btn ghost sm" href="{{ $instagramConnectUrl }}">Connect</a>
        @endif
      </div>
      <div class="help" style="margin:8px 0 14px">Tag a piece with an artist and it also shows their name, with “Request similar tattoo”. Leave it as Studio and it shows your studio name. Pieces waiting for a tag don’t show on your page.</div>

      <div class="spg" id="studioPiecesGrid">
        @foreach ($studioPieces as $piece)
          <div class="spf" data-id="{{ $piece['id'] }}">
            @if ($piece['from_instagram'])
              <span class="igb">IG</span>
            @endif
            <button type="button" class="spx" aria-label="Remove"><span class="ms">close</span></button>
            <div class="spi" @if($piece['image']) style="background-image:url('{{ $piece['image'] }}')" @endif></div>
            <div class="spc">
              <b>{{ $piece['title'] }}</b>
              <select class="in sm {{ $piece['attribution'] === 'pending' ? 'needs-tag' : '' }}" aria-label="Artist" data-attr>
                <option value="pending" @selected($piece['attribution'] === 'pending')>Tag the artist</option>
                <option value="studio" @selected($piece['attribution'] === 'studio')>Studio (no artist)</option>
                @foreach ($taggableArtists as $artist)
                  <option value="artist:{{ $artist['user_id'] }}" @selected($piece['attribution'] === 'artist' && (int) $piece['artist_user_id'] === (int) $artist['user_id'])>{{ $artist['name'] }}</option>
                @endforeach
              </select>
            </div>
          </div>
        @endforeach
        <button type="button" class="spadd" id="addPiecesBtn">
          <span class="ms">add_photo_alternate</span>
          Add pieces
        </button>
      </div>
      <input type="file" id="newImagesInput" accept="image/jpeg,image/png,image/webp" multiple hidden>
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
  var SAVE_URL = @json($updatePortfolioUrl ?? route('studio.page.portfolio.update'));
  var REFRESH_URL = @json($instagramRefreshUrl ?? route('studio.page.portfolio.instagram.refresh'));
  var DISCONNECT_URL = @json($instagramDisconnectUrl ?? route('studio.page.portfolio.instagram.disconnect'));
  var AUTO_IMPORT = @json($autoImportInstagram ?? false);
  var ARTIST_OPTIONS = @json($taggableArtists);

  var saveBtn = document.getElementById('pageSaveBtn');
  var dirty = false;
  var pendingHref = null;
  var saving = false;
  var deletedIds = [];
  var newFiles = [];
  var saveBtnDefaultHtml = saveBtn ? saveBtn.innerHTML : '';
  var lgSave = document.getElementById('lg-save');
  var lgSaveDefaultHtml = lgSave ? lgSave.innerHTML : '';

  function toast(msg, ok) {
    if (typeof window.bpToast === 'function') { window.bpToast(msg); return; }
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

  function openLeave() { var m = document.getElementById('leaveguard'); if (m) m.hidden = false; }
  function closeLeave() { var m = document.getElementById('leaveguard'); if (m) m.hidden = true; }

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

  function bindTog(el) {
    if (!el) return;
    function flip() {
      el.classList.toggle('on');
      el.setAttribute('aria-checked', el.classList.contains('on') ? 'true' : 'false');
      setDirty(true);
    }
    el.addEventListener('click', flip);
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); flip(); }
    });
  }
  bindTog(document.getElementById('togFlash'));
  bindTog(document.getElementById('togPortfolio'));

  function parseAttr(select) {
    var v = select.value || 'pending';
    if (v === 'studio') return { attribution: 'studio', artist_user_id: null };
    if (v.indexOf('artist:') === 0) {
      return { attribution: 'artist', artist_user_id: parseInt(v.slice(7), 10) || null };
    }
    return { attribution: 'pending', artist_user_id: null };
  }

  function wirePiece(card) {
    var sel = card.querySelector('[data-attr]');
    var rm = card.querySelector('.spx');
    if (sel) {
      sel.addEventListener('change', function () {
        sel.classList.toggle('needs-tag', sel.value === 'pending');
        setDirty(true);
      });
    }
    if (rm) {
      rm.addEventListener('click', function () {
        var id = parseInt(card.getAttribute('data-id'), 10);
        if (id) deletedIds.push(id);
        card.remove();
        setDirty(true);
      });
    }
  }
  document.querySelectorAll('#studioPiecesGrid .spf[data-id]').forEach(wirePiece);

  var fileInput = document.getElementById('newImagesInput');
  document.getElementById('addPiecesBtn')?.addEventListener('click', function () { fileInput?.click(); });
  fileInput?.addEventListener('change', function () {
    var files = [].slice.call(fileInput.files || []);
    files.forEach(function (file) {
      if (!file.type || file.type.indexOf('image/') !== 0) return;
      newFiles.push(file);
      var url = URL.createObjectURL(file);
      var card = document.createElement('div');
      card.className = 'spf';
      card.setAttribute('data-new', '1');
      card.innerHTML = '<button type="button" class="spx" aria-label="Remove"><span class="ms">close</span></button>'
        + '<div class="spi" style="background-image:url(\'' + url + '\')"></div>'
        + '<div class="spc"><b>' + (file.name.replace(/\.[^.]+$/, '') || 'Studio piece') + '</b>'
        + '<select class="in sm" aria-label="Artist" data-attr>'
        + '<option value="pending">Tag the artist</option>'
        + '<option value="studio" selected>Studio (no artist)</option>'
        + (ARTIST_OPTIONS || []).map(function (a) {
            return '<option value="artist:' + a.user_id + '">' + String(a.name).replace(/</g, '&lt;') + '</option>';
          }).join('')
        + '</select></div>';
      var addBtn = document.getElementById('addPiecesBtn');
      addBtn.parentNode.insertBefore(card, addBtn);
      card.querySelector('.spx').addEventListener('click', function () {
        var idx = newFiles.indexOf(file);
        if (idx > -1) newFiles.splice(idx, 1);
        card.remove();
        setDirty(true);
      });
      card.querySelector('[data-attr]').addEventListener('change', function () { setDirty(true); });
      setDirty(true);
    });
    fileInput.value = '';
  });

  function collectFormData() {
    var fd = new FormData();
    fd.append('show_artists_flash', document.getElementById('togFlash')?.classList.contains('on') ? '1' : '0');
    fd.append('show_artists_portfolio', document.getElementById('togPortfolio')?.classList.contains('on') ? '1' : '0');
    var pieces = [];
    document.querySelectorAll('#studioPiecesGrid .spf[data-id]').forEach(function (card) {
      var sel = card.querySelector('[data-attr]');
      var parsed = parseAttr(sel);
      pieces.push({
        id: parseInt(card.getAttribute('data-id'), 10),
        title: (card.querySelector('.spc b')?.textContent || '').trim(),
        attribution: parsed.attribution,
        artist_user_id: parsed.artist_user_id
      });
    });
    pieces.forEach(function (p, i) {
      fd.append('pieces[' + i + '][id]', p.id);
      fd.append('pieces[' + i + '][title]', p.title);
      fd.append('pieces[' + i + '][attribution]', p.attribution);
      if (p.artist_user_id) fd.append('pieces[' + i + '][artist_user_id]', p.artist_user_id);
    });
    deletedIds.forEach(function (id, i) { fd.append('deleted_ids[' + i + ']', id); });
    var newCards = document.querySelectorAll('#studioPiecesGrid .spf[data-new="1"]');
    newFiles.forEach(function (file, i) {
      fd.append('new_images[' + i + ']', file);
      var card = newCards[i];
      var title = card?.querySelector('.spc b')?.textContent || '';
      fd.append('new_titles[' + i + ']', title);
      var parsed = parseAttr(card?.querySelector('[data-attr]') || { value: 'studio' });
      fd.append('new_attributions[' + i + ']', parsed.attribution);
      if (parsed.artist_user_id) fd.append('new_artist_user_ids[' + i + ']', parsed.artist_user_id);
    });
    return fd;
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

  function savePortfolio(andLeave) {
    if (saving) return Promise.resolve(false);
    setSavingUi(true);
    return fetch(SAVE_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: collectFormData()
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
        location.reload();
        return true;
      })
      .catch(function (e) {
        toast(e.message || 'Could not save', false);
        setDirty(true);
        setSavingUi(false);
        return false;
      });
  }

  function refreshIg() {
    var btn = document.getElementById('igRefreshBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Importing…'; }
    return fetch(REFRESH_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.j.success) throw new Error(res.j.message || 'Import failed');
        toast(res.j.message || 'Imported');
        location.reload();
      })
      .catch(function (e) {
        toast(e.message || 'Import failed', false);
        if (btn) { btn.disabled = false; btn.textContent = 'Refresh'; }
      });
  }

  document.getElementById('igRefreshBtn')?.addEventListener('click', refreshIg);
  document.getElementById('igDisconnectBtn')?.addEventListener('click', function () {
    fetch(DISCONNECT_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function () { location.reload(); });
  });

  if (AUTO_IMPORT) setTimeout(refreshIg, 300);

  (function quickLinks() {
    var wrap = document.getElementById('quickLinks');
    var btn = document.getElementById('quickLinksBtn');
    var menu = document.getElementById('quickLinksMenu');
    if (!wrap || !btn || !menu) return;
    function openQl(show) {
      menu.hidden = !show;
      btn.setAttribute('aria-expanded', show ? 'true' : 'false');
      var cv = btn.querySelector('.cv');
      if (cv) cv.textContent = show ? 'expand_less' : 'expand_more';
    }
    btn.addEventListener('click', function (e) { e.stopPropagation(); openQl(menu.hidden); });
    menu.addEventListener('click', function (e) {
      e.stopPropagation();
      var c = e.target.closest('[data-ql-copy]');
      if (!c) return;
      var u = c.getAttribute('data-ql-copy') || '';
      var label = c.getAttribute('data-ql-label') || u;
      try { navigator.clipboard.writeText(u).catch(function () {}); } catch (x) {}
      c.classList.add('done');
      var icon = c.querySelector('.ms');
      if (icon) icon.textContent = 'check';
      setTimeout(function () { c.classList.remove('done'); if (icon) icon.textContent = 'content_copy'; }, 1600);
      toast('Link copied: ' + label);
    });
    document.addEventListener('click', function (e) { if (!menu.hidden && !wrap.contains(e.target)) openQl(false); });
  })();

  saveBtn?.addEventListener('click', function () { if (!dirty || saving) return; savePortfolio(false); });

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-close]') || e.target.id === 'leaveguard') { closeLeave(); return; }
    if (e.target.closest('#lg-leave')) { dirty = false; if (pendingHref) location.href = pendingHref; return; }
    if (e.target.closest('#lg-save')) { closeLeave(); savePortfolio(true); return; }
    var soon = e.target.closest('.tabs a.is-soon');
    if (soon) { e.preventDefault(); return; }
    var link = e.target.closest('a[href]');
    if (link && dirty && !link.closest('#leaveguard') && !link.closest('#quickLinks') && !link.getAttribute('href').startsWith('#') && !link.target) {
      e.preventDefault();
      pendingHref = link.getAttribute('href');
      openLeave();
    }
  });

  try {
    var q = sessionStorage.getItem('bp-toast');
    if (q) { sessionStorage.removeItem('bp-toast'); setTimeout(function () { toast(q); }, 120); }
  } catch (e) {}
})();
</script>
@endpush
