@extends('layouts.studio-dashboard-layout')

@section('title', 'Join requests')

@push('styles')
<style>
  .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;margin-right:5px}
  .pill.nd:before{display:none}
  .pill{gap:0}
  .ic{width:38px;height:38px;border-radius:10px;background:var(--chip);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .ic .ms{font-size:20px}
  .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:60;padding:24px}
  .smodal[hidden]{display:none}
  .smbox{background:#fff;border-radius:18px;width:560px;max-width:100%;max-height:calc(100vh - 48px);display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .smh{display:flex;gap:12px;align-items:flex-start;padding:20px 24px 14px;border-bottom:1px solid var(--line)}
  .smh h2{font-size:19px;font-weight:800;margin:0}
  .smh .x{margin-left:auto;border:0;background:none;cursor:pointer;padding:2px;color:var(--muted)}
  .smb{padding:18px 24px;overflow:auto;min-height:0}
  .smf{display:flex;gap:10px;justify-content:flex-end;padding:14px 24px;border-top:1px solid var(--line);flex-wrap:wrap}
  .tint{background:#FAF0FB;border:1px solid #F1E7F3;border-radius:12px}
  .artists-page .opt,#m-invite .opt{display:flex;gap:12px;align-items:flex-start;border:1px solid var(--line);border-radius:14px;padding:14px 16px;cursor:pointer;background:#fff;text-align:left;width:100%;font:inherit;color:inherit}
  .artists-page .opt.on,#m-invite .opt.on{border:2px solid #3E007C;padding:13px 15px;background:#FDFAFF}
  .artists-page .opt .rad,#m-invite .opt .rad{color:#C8BFCC;font-size:20px;flex:none;margin-top:1px}
  .artists-page .opt.on .rad,#m-invite .opt.on .rad{color:#3E007C;font-variation-settings:'FILL' 1}
  .artists-page .opt b,#m-invite .opt b{display:block;font-size:14px}
  .artists-page .opt small,#m-invite .opt small{display:block;color:var(--muted);font-size:12.5px;margin-top:3px;line-height:1.45}
  #inv-send:disabled{opacity:.65;cursor:not-allowed}
  .split{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
  .split .box{border:1px solid var(--line);border-radius:12px;padding:10px 14px;background:#fff}
  .split .box span{display:block;font-size:12px;color:var(--muted);font-weight:600}
  .split input{border:0;font:inherit;font-size:20px;font-weight:800;width:70px;outline:none;background:transparent}
  .split .calc{font-size:20px;font-weight:800;display:block;margin-top:2px}
  .tabs .pill{margin-left:6px}
  .jr-card .btn{flex-shrink:0}
  .done-line{display:flex;gap:10px;align-items:center;padding:16px 18px;font-size:14px;font-weight:600;color:#1F6B4E}
</style>
@endpush

@section('content')
<div class="artists-page">
  <div class="head">
    <div>
      <h1>Artists<a class="help-q" href="https://help.inkjin.com/en/articles/17338194-studio-dashboard-artists-artists" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
      <div class="sub">Who works at {{ $studioName }}, how they're paid, and requests to join.</div>
    </div>
    <button type="button" class="btn" data-open="m-invite"><span class="ms">person_add</span>Invite artist</button>
  </div>

  @include('studio.artists.partials.tabs')

  <div class="faint" style="font-size:13px;margin-bottom:12px">Artists who asked to join {{ $studioName }}. Nothing changes for them until you accept.</div>

  @forelse ($requests as $req)
    <div class="card jr-card" id="jr{{ $req['id'] }}" style="margin-bottom:12px" data-request='@json($req)'>
      <div class="row" style="gap:14px;padding:16px 18px;flex-wrap:wrap">
        <div class="avatar" style="width:40px;height:40px;@if($req['avatar'])background-image:url('{{ $req['avatar'] }}')@endif">
          @unless ($req['avatar'])
            {{ $req['initials'] }}
          @endunless
        </div>
        <div style="flex:1;min-width:220px">
          <div class="row" style="gap:8px;flex-wrap:wrap">
            <b>{{ $req['name'] }}</b>
            <span class="pill a">Wants to join</span>
          </div>
          <div class="muted" style="font-size:12.5px;margin-top:3px">{{ $req['summary'] }}</div>
        </div>
        <button type="button" class="btn ghost sm" data-open="m-decline" data-request-id="{{ $req['id'] }}">Decline</button>
        <button type="button" class="btn sm" data-open="m-review" data-request-id="{{ $req['id'] }}">Review</button>
      </div>
    </div>
  @empty
    <div class="card" style="padding:28px 22px;text-align:center">
      <div class="faint" style="font-size:13.5px;line-height:1.5">No join requests right now.</div>
    </div>
  @endforelse
</div>

{{-- Review / Accept --}}
<div class="smodal" id="m-review" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-review-t">
    <div class="smh">
      <div class="ic" style="background:#F3E8FF;color:#3E007C"><span class="ms">person_add</span></div>
      <div>
        <h2 id="m-review-t">Join request</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px">Accept the artist, and confirm the relationship and split.</div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb">
      <div class="row" style="gap:12px">
        <div class="avatar" id="rv-av" style="width:44px;height:44px"></div>
        <div>
          <b id="rv-name">Artist</b>
          <div class="muted" style="font-size:12.5px">Asked for: <span id="rv-ask">—</span></div>
        </div>
      </div>
      <div class="formgrid" style="margin-top:16px">
        <div>
          <span class="fl">Relationship</span>
          <select class="in" id="rv-rel-select">
            @foreach ($relationshipOptions as $key => $label)
              <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
          </select>
          <div class="help">The artist picked <span id="rv-rel">Resident</span>. You can change it.</div>
        </div>
      </div>
      <span class="fl" style="margin-top:16px">How they're paid</span>
      <div class="tint" style="padding:12px 14px">
        <div class="row" style="justify-content:space-between;font-size:13.5px;flex-wrap:wrap;gap:8px">
          <span class="muted">They asked for</span>
          <b id="rv-split">—</b>
        </div>
        <div class="faint" style="font-size:12.5px;margin-top:6px;line-height:1.5">It's locked once you accept and applies to their new bookings at your studio. If you don't agree with it, decline and tell them why.</div>
      </div>
      <div class="help">Once accepted, the artist shows on your studio page and their bookings at your studio show in your calendar.</div>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="button" class="btn" id="rv-accept">Accept artist</button>
    </div>
  </div>
</div>

{{-- Decline --}}
<div class="smodal" id="m-decline" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-decline-t">
    <div class="smh">
      <div>
        <h2 id="m-decline-t">Decline this request?</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px"><b id="dec-name">Artist</b> is told you declined. They stay where they work now.</div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb">
      <span class="fl">Message (optional)</span>
      <textarea class="in" id="dec-message" rows="3" placeholder="e.g. We don't have a free chair right now."></textarea>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="button" class="btn" style="background:#C62828" id="dec-confirm">Decline</button>
    </div>
  </div>
</div>

@include('studio.artists.partials.invite-modal')
@endsection

@push('scripts')
<script>
(function () {
  var currentId = null;
  var requests = {};
  document.querySelectorAll('[data-request]').forEach(function (el) {
    try {
      var r = JSON.parse(el.getAttribute('data-request'));
      requests[String(r.id)] = r;
    } catch (e) {}
  });

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return [].slice.call((root || document).querySelectorAll(sel)); }
  function openM(id) { var m = document.getElementById(id); if (m) m.hidden = false; }
  function closeAll() { $$('.smodal').forEach(function (m) { m.hidden = true; }); }

  function fillRequest(r) {
    if (!r) return;
    currentId = r.id;
    $('#rv-name').textContent = r.name;
    $('#rv-ask').textContent = r.ask_label || '—';
    $('#rv-rel').textContent = r.relationship || '—';
    $('#rv-split').textContent = r.split_label || '—';
    $('#dec-name').textContent = r.name;
    var av = $('#rv-av');
    av.style.backgroundImage = r.avatar ? "url('" + r.avatar + "')" : '';
    av.textContent = r.avatar ? '' : (r.initials || '');
    var sel = $('#rv-rel-select');
    if (sel && r.relationship_key) sel.value = r.relationship_key;
    var msg = $('#dec-message');
    if (msg) msg.value = '';
  }

  function postJson(url, body) {
    var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    return fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(body || {})
    }).then(function (res) {
      return res.json().then(function (data) {
        return { ok: res.ok, data: data };
      });
    });
  }

  document.addEventListener('click', function (e) {
    var t;
    if (e.target.classList.contains('smodal') || (t = e.target.closest('[data-close]'))) {
      closeAll();
      return;
    }
    if ((t = e.target.closest('[data-open]'))) {
      e.preventDefault();
      var id = t.getAttribute('data-request-id') || currentId;
      if (id && requests[String(id)]) fillRequest(requests[String(id)]);
      openM(t.getAttribute('data-open'));
      return;
    }
    if ((t = e.target.closest('.is-soon'))) {
      e.preventDefault();
      if (window.bpToast) window.bpToast('Coming soon');
      return;
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAll();
  });

  var pct = document.getElementById('inv-artist-pct');
  var them = document.getElementById('invsp');
  if (pct && them) {
    pct.addEventListener('input', function () {
      var v = parseInt(pct.value, 10);
      them.textContent = (v >= 0 && v <= 100) ? (100 - v) + '%' : '–';
    });
  }

  document.getElementById('inv-send')?.addEventListener('click', function () {
    var btn = this;
    if (btn.disabled) return;
    var email = (document.getElementById('inv-email')?.value || '').trim();
    if (!email) {
      if (window.bpToast) window.bpToast('Enter an email or username', true);
      return;
    }
    var payout = document.querySelector('#m-invite [data-group="inv"] .opt.on')?.getAttribute('data-v') || 'split';
    if (window.bpInviteSetSending) window.bpInviteSetSending(btn, true);
    else { btn.disabled = true; btn.textContent = 'Sending...'; }
    postJson(@json(route('studio.artists.invites.send')), {
      identifier: email,
      relationship: document.getElementById('inv-rel')?.value || 'resident',
      payout_mode: payout,
      revenue_split: parseInt(document.getElementById('inv-artist-pct')?.value || '60', 10)
    }).then(function (res) {
      if (!res.ok || !res.data || !res.data.success) {
        throw new Error((res.data && res.data.message) || 'Could not send invite');
      }
      closeAll();
      try { sessionStorage.setItem('bp-toast', res.data.message || 'Invitation sent'); } catch (e) {}
      location.href = @json(route('studio.artists.invites'));
    }).catch(function (err) {
      if (window.bpInviteSetSending) window.bpInviteSetSending(btn, false);
      else { btn.disabled = false; }
      if (window.bpToast) window.bpToast(err.message || 'Could not send invite', true);
    });
  });

  document.getElementById('rv-accept')?.addEventListener('click', function () {
    if (!currentId) return;
    var btn = this;
    btn.disabled = true;
    var url = @json(url('/studio/artists')) + '/' + encodeURIComponent(currentId) + '/accept';
    var relationship = document.getElementById('rv-rel-select')?.value || null;
    postJson(url, { relationship: relationship }).then(function (res) {
      if (!res.ok || !res.data || !res.data.success) {
        throw new Error((res.data && res.data.message) || 'Could not accept request');
      }
      closeAll();
      try { sessionStorage.setItem('bp-toast', res.data.message || 'Artist accepted'); } catch (e) {}
      location.reload();
    }).catch(function (err) {
      btn.disabled = false;
      if (window.bpToast) window.bpToast(err.message || 'Could not accept request', true);
    });
  });

  document.getElementById('dec-confirm')?.addEventListener('click', function () {
    if (!currentId) return;
    var btn = this;
    btn.disabled = true;
    var url = @json(url('/studio/artists')) + '/' + encodeURIComponent(currentId) + '/decline';
    var message = (document.getElementById('dec-message')?.value || '').trim();
    postJson(url, { message: message || null }).then(function (res) {
      if (!res.ok || !res.data || !res.data.success) {
        throw new Error((res.data && res.data.message) || 'Could not decline request');
      }
      closeAll();
      try { sessionStorage.setItem('bp-toast', res.data.message || 'Request declined'); } catch (e) {}
      location.reload();
    }).catch(function (err) {
      btn.disabled = false;
      if (window.bpToast) window.bpToast(err.message || 'Could not decline request', true);
    });
  });
})();
</script>
@include('studio.artists.partials.invite-modal-script')
@endpush
