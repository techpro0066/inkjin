@extends('layouts.studio-dashboard-layout')

@section('title', 'Artists')

@push('styles')
<style>
  .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;margin-right:5px}
  .pill.nd:before{display:none}
  .pill{gap:0}
  .lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:var(--muted);text-transform:uppercase}
  .card.pad{padding:18px 22px;overflow:visible}
  .ic{width:38px;height:38px;border-radius:10px;background:var(--chip);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .ic .ms{font-size:20px}
  .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:60;padding:24px}
  .smodal[hidden]{display:none}
  .smbox{background:#fff;border-radius:18px;width:560px;max-width:100%;max-height:calc(100vh - 48px);display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .smbox.wide{width:760px}
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
  .ms.act{cursor:pointer;font-size:20px}
  .ms.act:hover{color:var(--pri)}
  @media (max-width:900px){
    .artists-page .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .artists-page table,.artists-page tbody,.artists-page tr,.artists-page td{display:block;width:100%}
    .artists-page tr:has(th){display:none}
    .artists-page tbody tr{position:relative;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px}
    .artists-page td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;font-size:13.5px;text-align:right}
    .artists-page td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
    .artists-page td:first-child{padding:0 44px 8px 0!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .artists-page td:first-child::before{display:none}
    .artists-page td:last-child{position:absolute;top:10px;right:10px;width:auto;padding:0!important}
    .artists-page td:last-child::before{display:none}
  }
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

  <div class="card" style="overflow:hidden">
    <table>
      <thead>
        <tr>
          <th>Artist</th>
          <th>Relationship</th>
          <th>How they're paid</th>
          <th>Status</th>
          <th>Since</th>
          <th>Bookings (month)</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($artists as $artist)
          <tr id="row-a{{ $artist['id'] }}"
              data-artist='@json($artist)'>
            <td data-label="Artist">
              <div class="row" style="gap:10px">
                <div class="avatar" @if ($artist['avatar']) style="background-image:url('{{ $artist['avatar'] }}')" @endif>
                  @unless ($artist['avatar'])
                    {{ $artist['initials'] }}
                  @endunless
                </div>
                <div>
                  <b style="font-weight:600">{{ $artist['name'] }}</b>
                  @if ($artist['handle'] !== '')
                    <div class="faint" style="font-size:11.5px">{{ '@'.$artist['handle'] }}</div>
                  @endif
                </div>
              </div>
            </td>
            <td data-label="Relationship">{{ $artist['relationship'] }}</td>
            <td data-label="How they're paid" style="font-size:13px">
              <b style="font-weight:600">{{ $artist['pay_label'] }}</b>
              <div class="faint" style="font-size:12px">{{ $artist['pay_detail'] }}</div>
            </td>
            <td data-label="Status" class="stcell"><span class="pill g">{{ $artist['status'] }}</span></td>
            <td data-label="Since">{{ $artist['since'] }}</td>
            <td data-label="Bookings (month)">{{ $artist['bookings_month'] }}</td>
            <td>
              <div class="row" style="gap:6px">
                <button type="button" class="btn ghost sm" data-open="m-artist" data-artist-id="{{ $artist['id'] }}">View</button>
                <button type="button" class="ms faint act" data-open="m-remove" data-artist-id="{{ $artist['id'] }}" aria-label="Remove artist" title="Remove artist">person_remove</button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="padding:28px 22px;text-align:center">
              <div class="faint" style="font-size:13.5px;line-height:1.5">No linked artists yet. Invite an artist, or wait for them to request to join.</div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="note" style="margin-top:14px">
    <span class="ms">info</span>
    <span>A split starts when the artist asks for it and you confirm. To change it, the artist sends a new request, and you accept or decline. An artist can switch to direct payment on their own. You're notified, and it applies to their new bookings.</span>
  </div>

  <div class="card pad" style="margin-top:14px">
    <div class="lbl" style="margin-bottom:8px">Recent changes</div>
    <div class="faint" style="font-size:13.5px;line-height:1.45">No recent changes yet.</div>
  </div>
</div>

@include('studio.artists.partials.invite-modal')

{{-- Remove --}}
<div class="smodal" id="m-remove" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-remove-t">
    <div class="smh">
      <div>
        <h2 id="m-remove-t">Remove this artist?</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px"><b id="rm-name">Artist</b> is told and is no longer one of your artists.</div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb">
      <ul style="padding-left:18px;font-size:13.5px;line-height:1.8;color:#3a3a3a;margin:0">
        <li>They leave your studio page at once.</li>
        <li>Their <b>upcoming bookings at your studio stay</b>, with the same split. They message clients if a session must move.</li>
        <li>New bookings at your studio stop.</li>
        <li>You keep seeing past bookings and earnings.</li>
      </ul>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="button" class="btn" style="background:#C62828" id="rm-confirm">Remove artist</button>
    </div>
  </div>
</div>

{{-- View artist --}}
<div class="smodal" id="m-artist" hidden>
  <div class="smbox wide" role="dialog" aria-modal="true" aria-labelledby="m-artist-t">
    <div class="smh">
      <div>
        <h2 id="m-artist-t">Artist</h2>
        <div class="muted" id="m-artist-sub" style="font-size:13.5px;margin-top:4px"></div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb">
      <div class="row" style="gap:14px;flex-wrap:wrap">
        <div class="avatar" id="m-artist-av" style="width:56px;height:56px;font-size:16px"></div>
        <div style="flex:1;min-width:160px">
          <div class="row" style="gap:8px;flex-wrap:wrap">
            <span class="pill g" id="m-artist-status">Active</span>
            <span class="pill nd k" id="m-artist-rel">Resident</span>
          </div>
          <div class="muted" id="m-artist-styles" style="font-size:12.5px;margin-top:4px"></div>
        </div>
        <a class="btn ghost sm" id="m-artist-page" href="#" target="_blank" rel="noopener" hidden><span class="ms">open_in_new</span>Artist page</a>
      </div>
      <div class="grid" style="grid-template-columns:repeat(3,1fr);gap:10px;margin-top:16px">
        <div class="card pad" style="padding:12px 14px">
          <div class="lbl">Bookings this month</div>
          <b style="font-size:20px" id="m-artist-bookings">0</b>
        </div>
        <div class="card pad" style="padding:12px 14px">
          <div class="lbl">How they're paid</div>
          <b style="font-size:15px" id="m-artist-pay">—</b>
        </div>
        <div class="card pad" style="padding:12px 14px">
          <div class="lbl">Since</div>
          <b style="font-size:15px" id="m-artist-since">—</b>
        </div>
      </div>
      <span class="fl" style="margin-top:16px">Split</span>
      <div class="faint" id="m-artist-split" style="font-size:13.5px;margin-top:4px"></div>
      <div class="help">The studio sees only this artist's bookings at {{ $studioName }}. Other studios and personal events show as busy.</div>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" data-open="m-remove" id="m-artist-remove" style="color:#C62828">Remove artist</button>
      <button type="button" class="btn" data-close>Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var currentId = null;
  var artists = {};
  document.querySelectorAll('tr[data-artist]').forEach(function (row) {
    try {
      var a = JSON.parse(row.getAttribute('data-artist'));
      artists[String(a.id)] = a;
    } catch (e) {}
  });

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return [].slice.call((root || document).querySelectorAll(sel)); }

  function openM(id) {
    var m = document.getElementById(id);
    if (m) m.hidden = false;
  }
  function closeAll() {
    $$('.smodal').forEach(function (m) { m.hidden = true; });
  }

  function fillArtist(a) {
    if (!a) return;
    currentId = a.id;
    $('#m-artist-t').textContent = a.name;
    $('#m-artist-sub').textContent = (a.handle ? '@' + a.handle + ' · ' : '') + a.relationship + ' since ' + a.since;
    var av = $('#m-artist-av');
    av.style.backgroundImage = a.avatar ? "url('" + a.avatar + "')" : '';
    av.textContent = a.avatar ? '' : a.initials;
    $('#m-artist-status').textContent = a.status;
    $('#m-artist-rel').textContent = a.relationship;
    $('#m-artist-styles').textContent = a.styles || '';
    $('#m-artist-bookings').textContent = String(a.bookings_month || 0);
    $('#m-artist-pay').textContent = a.pay_label;
    $('#m-artist-since').textContent = a.since;
    $('#m-artist-split').textContent = a.is_split
      ? ('Artist ' + a.artist_percent + '% · Studio ' + a.studio_percent + '%')
      : 'Workstation rent · full payment to the artist';
    var page = $('#m-artist-page');
    if (a.public_url) {
      page.href = a.public_url;
      page.hidden = false;
    } else {
      page.hidden = true;
    }
    $('#rm-name').textContent = a.name;
  }

  document.addEventListener('click', function (e) {
    var t;
    if (e.target.classList.contains('smodal') || (t = e.target.closest('[data-close]'))) {
      if (t && t.getAttribute('data-open')) return;
      closeAll();
      return;
    }
    if ((t = e.target.closest('[data-open]'))) {
      e.preventDefault();
      var id = t.getAttribute('data-artist-id') || currentId;
      if (id && artists[String(id)]) fillArtist(artists[String(id)]);
      var openId = t.getAttribute('data-open');
      if (openId === 'm-remove' && document.getElementById('m-artist') && !document.getElementById('m-artist').hidden) {
        document.getElementById('m-artist').hidden = true;
      }
      openM(openId);
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
    var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    if (window.bpInviteSetSending) window.bpInviteSetSending(btn, true);
    else { btn.disabled = true; btn.textContent = 'Sending...'; }
    fetch(@json(route('studio.artists.invites.send')), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        identifier: email,
        relationship: document.getElementById('inv-rel')?.value || 'resident',
        payout_mode: payout,
        revenue_split: parseInt(document.getElementById('inv-artist-pct')?.value || '60', 10)
      })
    }).then(function (r) {
      return r.json().then(function (data) { return { ok: r.ok, data: data }; });
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

  document.getElementById('rm-confirm')?.addEventListener('click', function () {
    if (!currentId) {
      if (window.bpToast) window.bpToast('Select an artist first', true);
      return;
    }
    var btn = this;
    btn.disabled = true;
    var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var url = @json(url('/studio/artists')) + '/' + encodeURIComponent(currentId) + '/remove';
    fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({})
    }).then(function (r) {
      return r.json().then(function (data) {
        return { ok: r.ok, data: data };
      });
    }).then(function (res) {
      if (!res.ok || !res.data || !res.data.success) {
        throw new Error((res.data && res.data.message) || 'Could not remove artist');
      }
      closeAll();
      try { sessionStorage.setItem('bp-toast', res.data.message || 'Artist removed'); } catch (e) {}
      location.reload();
    }).catch(function (err) {
      btn.disabled = false;
      if (window.bpToast) window.bpToast(err.message || 'Could not remove artist', true);
    });
  });
})();
</script>
@include('studio.artists.partials.invite-modal-script')
@endpush
