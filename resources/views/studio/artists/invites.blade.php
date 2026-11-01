@extends('layouts.studio-dashboard-layout')

@section('title', 'Invites')

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
  @media (max-width:900px){
    .artists-page .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .artists-page table,.artists-page tbody,.artists-page tr,.artists-page td{display:block;width:100%}
    .artists-page tr:has(th){display:none}
    .artists-page tbody tr{position:relative;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px}
    .artists-page td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;font-size:13.5px;text-align:right}
    .artists-page td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
    .artists-page td:first-child{padding:0 0 8px 0!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .artists-page td:first-child::before{display:none}
    .artists-page td:last-child{justify-content:flex-end}
    .artists-page td:last-child::before{display:none}
  }
</style>
@endpush

@section('content')
<div class="artists-page">
  <div class="head">
    <div>
      <h1>Artists<a class="help-q" href="https://help.inkjin.com/en/articles/17338255-studio-dashboard-artists-invites" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
      <div class="sub">Who works at {{ $studioName }}, how they're paid, and requests to join.</div>
    </div>
    <button type="button" class="btn" data-open="m-invite"><span class="ms">person_add</span>Invite artist</button>
  </div>

  @include('studio.artists.partials.tabs')

  <div class="card" style="overflow:hidden">
    <table>
      <thead>
        <tr>
          <th>Artist</th>
          <th>Relationship</th>
          <th>Offered</th>
          <th>Sent</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($invites as $invite)
          <tr id="iv{{ $invite['id'] }}">
            <td data-label="Artist">
              <b style="font-weight:600">{{ $invite['on_bookpay'] ? $invite['name'] : $invite['email'] }}</b>
              <div class="faint" style="font-size:11.5px">{{ $invite['subtitle'] }}</div>
            </td>
            <td data-label="Relationship">{{ $invite['relationship'] }}</td>
            <td data-label="Offered">{{ $invite['offered'] }}</td>
            <td data-label="Sent">{{ $invite['sent'] }}</td>
            <td data-label="Status"><span class="pill a">{{ $invite['status'] }}</span></td>
            <td>
              <div class="row" style="gap:6px;justify-content:flex-end">
                <button type="button" class="btn ghost sm" data-resend="{{ $invite['id'] }}">Resend</button>
                <button type="button" class="btn ghost sm" data-cancel="{{ $invite['id'] }}">Cancel</button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="padding:28px 22px;text-align:center">
              <div class="faint" style="font-size:13.5px;line-height:1.5">No pending invites. Invite an artist to join {{ $studioName }}.</div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="help" style="margin-top:12px">Artists already on Bookpay get the invite by email. Others get an email and join from it.</div>
</div>

@include('studio.artists.partials.invite-modal')
@endsection

@push('scripts')
<script>
(function () {
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return [].slice.call((root || document).querySelectorAll(sel)); }
  function openM(id) { var m = document.getElementById(id); if (m) m.hidden = false; }
  function closeAll() { $$('.smodal').forEach(function (m) { m.hidden = true; }); }
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
      return res.json().then(function (data) { return { ok: res.ok, data: data }; });
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
      openM(t.getAttribute('data-open'));
      return;
    }
    if ((t = e.target.closest('.is-soon'))) {
      e.preventDefault();
      if (window.bpToast) window.bpToast('Coming soon');
      return;
    }
    if ((t = e.target.closest('[data-resend]'))) {
      var id = t.getAttribute('data-resend');
      t.disabled = true;
      postJson(@json(url('/studio/artists/invites')) + '/' + encodeURIComponent(id) + '/resend', {})
        .then(function (res) {
          if (!res.ok || !res.data || !res.data.success) throw new Error((res.data && res.data.message) || 'Could not resend');
          if (window.bpToast) window.bpToast(res.data.message || 'Invitation sent again');
        })
        .catch(function (err) {
          if (window.bpToast) window.bpToast(err.message || 'Could not resend', true);
        })
        .finally(function () { t.disabled = false; });
      return;
    }
    if ((t = e.target.closest('[data-cancel]'))) {
      var cid = t.getAttribute('data-cancel');
      t.disabled = true;
      postJson(@json(url('/studio/artists/invites')) + '/' + encodeURIComponent(cid) + '/cancel', {})
        .then(function (res) {
          if (!res.ok || !res.data || !res.data.success) throw new Error((res.data && res.data.message) || 'Could not cancel');
          try { sessionStorage.setItem('bp-toast', res.data.message || 'Invitation cancelled'); } catch (e) {}
          location.reload();
        })
        .catch(function (err) {
          t.disabled = false;
          if (window.bpToast) window.bpToast(err.message || 'Could not cancel', true);
        });
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
    var payload = {
      identifier: email,
      relationship: document.getElementById('inv-rel')?.value || 'resident',
      payout_mode: payout,
      revenue_split: parseInt(document.getElementById('inv-artist-pct')?.value || '60', 10)
    };
    if (window.bpInviteSetSending) window.bpInviteSetSending(btn, true);
    else { btn.disabled = true; btn.textContent = 'Sending...'; }
    postJson(@json(route('studio.artists.invites.send')), payload).then(function (res) {
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
})();
</script>
@include('studio.artists.partials.invite-modal-script')
@endpush
