@extends('layouts.studio-dashboard-layout')

@section('title', 'Guest artists')

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
  .tabs .pill{margin-left:6px}
  .note{border:1.5px dashed #B99BE0;background:#FBF5FF;border-radius:12px;padding:10px 14px;font-size:12.5px;color:#5B2A94;display:flex;gap:8px;align-items:flex-start;margin-top:14px}
  .note .ms{font-size:18px;flex:none}
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
      <h1>Artists<a class="help-q" href="https://help.inkjin.com/en/articles/17338266-studio-dashboard-artists-guest-artists" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
      <div class="sub">Who works at {{ $studioName }}, how they're paid, and requests to join.</div>
    </div>
    <button type="button" class="btn" data-open="m-invite"><span class="ms">person_add</span>Invite artist</button>
  </div>

  @include('studio.artists.partials.tabs')

  <div class="faint" style="font-size:13px;margin-bottom:12px">Artists who set up a guest spot at your studio. They choose your studio in their own Guest spots. A guest spot never changes their home studio.</div>

  <div class="card" style="overflow:hidden">
    <table>
      <thead>
        <tr>
          <th>Guest artist</th>
          <th>Dates</th>
          <th>How they're paid</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($guests as $guest)
          <tr id="g{{ $guest['id'] }}" data-guest='@json($guest)'>
            <td data-label="Guest artist">
              <div class="row" style="gap:10px">
                <div class="avatar" style="width:34px;height:34px;@if($guest['avatar'])background-image:url('{{ $guest['avatar'] }}')@endif">
                  @unless ($guest['avatar'])
                    {{ $guest['initials'] }}
                  @endunless
                </div>
                <div>
                  <b style="font-weight:600">{{ $guest['name'] }}</b>
                  @if ($guest['subtitle'] !== '')
                    <div class="faint" style="font-size:11.5px">{{ $guest['subtitle'] }}</div>
                  @endif
                </div>
              </div>
            </td>
            <td data-label="Dates">{{ $guest['dates'] }}</td>
            <td data-label="How they're paid">
              {{ $guest['pay_label'] }}
              @if (!empty($guest['currency_mismatch']) && !empty($guest['artist_currency']))
                <div style="margin-top:4px">
                  <span class="pill k nd" title="Artist payout currency differs from yours">
                    <span class="ms" style="font-size:14px;vertical-align:-3px;margin-right:3px">currency_exchange</span>
                    Paid in {{ $guest['artist_currency'] }}
                  </span>
                </div>
              @endif
            </td>
            <td data-label="Status" class="stcell">
              <span class="pill {{ $guest['status_pill'] }}">{{ $guest['status_label'] }}</span>
            </td>
            <td>
              @if ($guest['needs_action'])
                <div class="row" style="gap:6px;justify-content:flex-end">
                  <form method="post" action="{{ route('studio.guest-spot-invite.decline', $guest['id']) }}">
                    @csrf
                    <input type="hidden" name="redirect" value="guests">
                    <button type="submit" class="btn ghost sm">Decline</button>
                  </form>
                  @if (!empty($guest['currency_mismatch']))
                    <button type="button" class="btn sm" data-open="m-cur" data-guest-id="{{ $guest['id'] }}">Confirm split</button>
                  @else
                    <form method="post" action="{{ route('studio.guest-spot-invite.accept', $guest['id']) }}">
                      @csrf
                      <input type="hidden" name="redirect" value="guests">
                      <button type="submit" class="btn sm">Confirm split</button>
                    </form>
                  @endif
                </div>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="padding:28px 22px;text-align:center">
              <div class="faint" style="font-size:13.5px;line-height:1.5">No guest artists yet. When an artist sets up a guest spot at {{ $studioName }}, it shows up here.</div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="note">
    <span class="ms">info</span>
    <div>With a split, the guest spot goes live once you confirm it. With direct payment it goes live at once. You can't counter-offer: confirm or decline.</div>
  </div>
</div>

{{-- Currency conversion confirm --}}
<div class="smodal" id="m-cur" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-cur-t">
    <div class="smh">
      <div class="ic" style="background:#F3E8FF;color:#3E007C"><span class="ms">currency_exchange</span></div>
      <div>
        <h2 id="m-cur-t">Confirm split</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px" id="m-cur-sub">Guest spot · revenue split</div>
      </div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb" style="font-size:14px;line-height:1.55">
      <p style="margin:0 0 10px" id="m-cur-body">The artist is paid in a different currency. Your share is converted when it’s paid out, minus a conversion fee (about 1–2%).</p>
      <div style="padding:10px 12px;border-radius:10px;background:#F6F3F8;font-size:13px" id="m-cur-ex">The final amount depends on the exchange rate on the payout day.</div>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <form method="post" id="m-cur-form" action="#">
        @csrf
        <input type="hidden" name="redirect" value="guests">
        <button type="submit" class="btn">Confirm split</button>
      </form>
    </div>
  </div>
</div>

@include('studio.artists.partials.invite-modal')
@endsection

@push('scripts')
@include('studio.artists.partials.invite-modal-script')
<script>
(function () {
  function $$(sel, root) { return [].slice.call((root || document).querySelectorAll(sel)); }
  function openM(id) { var m = document.getElementById(id); if (m) m.hidden = false; }
  function closeAll() { $$('.smodal').forEach(function (m) { m.hidden = true; }); }

  var guests = {};
  $$('tr[data-guest]').forEach(function (row) {
    try { guests[row.id.replace(/^g/, '')] = JSON.parse(row.getAttribute('data-guest')); } catch (e) {}
  });

  document.addEventListener('click', function (e) {
    var t;
    if (e.target.classList.contains('smodal') || (t = e.target.closest('[data-close]'))) {
      if (!e.target.closest('form')) closeAll();
      if (t) closeAll();
      return;
    }
    if ((t = e.target.closest('[data-open="m-cur"]'))) {
      e.preventDefault();
      var id = t.getAttribute('data-guest-id');
      var g = guests[id];
      if (!g) return;
      function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
      }
      var first = String(g.name || 'Artist').split(' ')[0];
      document.getElementById('m-cur-t').textContent = 'Confirm split with ' + g.name;
      document.getElementById('m-cur-sub').textContent =
        'Guest spot ' + g.dates + ' · Artist ' + g.artist_percent + '% · Studio ' + g.studio_percent + '%';
      var artistCur = g.artist_currency || 'their currency';
      var studioCur = g.studio_currency || 'your currency';
      document.getElementById('m-cur-body').innerHTML =
        esc(first) + ' is paid in <b>' + esc(artistCur) + '</b>. Clients pay in ' + esc(artistCur) +
        ', and your share is worked out in ' + esc(artistCur) +
        '. It’s converted to <b>' + esc(studioCur) + '</b> when it’s paid out to you, minus a conversion fee (about 1–2%).';
      document.getElementById('m-cur-ex').textContent =
        'The final amount depends on the exchange rate on the payout day.';
      document.getElementById('m-cur-form').action = @json(url('/studio/guest-spot-invites')) + '/' + id + '/accept';
      openM('m-cur');
    }
  });
})();
</script>
@endpush
