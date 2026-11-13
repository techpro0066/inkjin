@extends('layouts.studio-dashboard-layout')

@section('title', 'Home')

@push('styles')
@include('partials.page-quick-links-styles')
@include('studio.bookings.partials.payment-link-modal-assets')
@include('studio.bookings.partials.booking-detail-modal-styles')
<style>
  .dash-todo-row{padding:13px 0;border-bottom:1px solid #F3EEF4;gap:12px;align-items:center}
  .dash-todo-row:last-child{border-bottom:0}
  .dash-todo-row > div:nth-child(2){flex:1;min-width:0}
  .dash-todo-row .btn{flex-shrink:0}
  .dash-top{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(0,1fr);gap:16px;margin-top:26px}
  .dash-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:16px}
  .dash-stats .card.pad{min-width:0}
  .dash-stats .lbl{line-height:1.2}
  .dash-check .ch{align-items:flex-start;gap:10px}
  .dash-check .ch h3{min-width:0;flex:1;line-height:1.25}
  .dash-check .ch b{flex-shrink:0;white-space:nowrap;padding-top:2px}
  .dash-up{margin-top:16px;overflow:hidden}
  .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;margin-right:5px}
  .pill.nd:before{display:none}
  .pill{gap:0}
  .card.pad{padding:20px 22px}
  @media (max-width:900px){
    .dash-top{grid-template-columns:minmax(0,1fr)}
    .dash-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .dash-stats .card.pad{padding:16px}
    .dash-stats .card.pad > div[style*="font-size:26px"]{font-size:24px!important;margin-top:8px!important}
    .dash-up{overflow:visible;background:none;border:0}
    .dash-up > .ch{padding:0 2px 12px;border-bottom:0;margin-bottom:2px}
    .dash-up > .faint{padding:20px 2px!important}
    .dash-up table,.dash-up tbody,.dash-up tr,.dash-up td{display:block;width:100%}
    .dash-up thead{display:none}
    .dash-up tbody tr{
      display:grid;grid-template-columns:minmax(0,1fr) auto;column-gap:10px;
      background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px 16px;margin-bottom:10px
    }
    .dash-up td{
      border:0!important;padding:8px 0!important;display:flex!important;
      justify-content:space-between;align-items:center;gap:12px;text-align:right;min-width:0
    }
    .dash-up td::before{
      content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;
      color:var(--muted);text-align:left;flex-shrink:0;text-transform:uppercase
    }
    .dash-up td:first-child{
      grid-column:1;grid-row:1;padding:0 0 10px!important;margin:0;
      border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left
    }
    .dash-up td:first-child::before{display:none}
    .dash-up td:last-child{
      grid-column:2;grid-row:1;padding:0 0 10px!important;margin:0;
      border-bottom:1px solid #F3EEF4!important;justify-content:flex-end;align-items:center
    }
    .dash-up td:last-child::before{display:none}
    .dash-up td:not(:first-child):not(:last-child){
      grid-column:1 / -1;border-bottom:1px solid #F3EEF4!important
    }
    .dash-up td:nth-last-child(2){border-bottom:0!important;padding-bottom:0!important}
  }
  @media (max-width:600px){
    .dash-todo-row{flex-wrap:wrap}
    .dash-todo-row .btn{margin-left:50px}
  }
  .studio-gsb{
    display:flex;align-items:center;gap:16px;flex-wrap:wrap;
    background:linear-gradient(135deg,#F3E8FF,#FFF6FF);border:1px solid #E6D6F2;border-radius:16px;
    padding:16px 18px;margin-top:22px
  }
  .studio-gsb[hidden]{display:none!important}
  .studio-gsb .sgi{
    width:44px;height:44px;border-radius:12px;background:#3E007C;color:#fff;
    display:flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .studio-gsb .sgi .ms{font-size:24px}
  .studio-gsb .sgt{flex:1;min-width:220px}
  .studio-gsb .sgt b{display:inline;font-size:15px;font-weight:700}
  .studio-gsb .sgt>span{display:block;font-size:13px;color:#6F6874;margin-top:3px;line-height:1.45}
  .studio-gsb .sg-new{
    display:inline-block;background:#3E007C;color:#fff;font-size:10px;font-weight:800;
    letter-spacing:.6px;border-radius:5px;padding:2px 6px;vertical-align:middle;margin-right:7px
  }
  .studio-gsb a.sgb{
    display:inline-flex;align-items:center;gap:6px;background:#1A1A1A;color:#fff;
    font-weight:700;font-size:13.5px;padding:10px 14px;border-radius:10px;text-decoration:none;white-space:nowrap
  }
  .studio-gsb a.sgb .ms{font-size:16px}
  .studio-gsb .sgx{
    width:34px;height:34px;border-radius:50%;border:0;background:none;cursor:pointer;color:#9A929E;
    display:inline-flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .studio-gsb .sgx:hover{background:#F4F0F6;color:#1A1A1A}
  .studio-gsb .sgx .ms{font-size:20px}
  @media (max-width:600px){
    .studio-gsb a.sgb{width:100%;justify-content:center}
    .studio-gsb .sgx{margin-left:auto}
  }
  .lbl{font-size:11px;letter-spacing:.8px;font-weight:700;color:var(--muted);text-transform:uppercase}
  .ic{width:38px;height:38px;border-radius:10px;background:var(--chip);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .ic .ms{font-size:20px}
  #m-invite .smbox{width:560px;max-width:100%;border-radius:18px}
  #m-invite .opt{display:flex;gap:12px;align-items:flex-start;border:1px solid var(--line);border-radius:14px;padding:14px 16px;cursor:pointer;background:#fff;text-align:left;width:100%;font:inherit;color:inherit}
  #m-invite .opt.on{border:2px solid #3E007C;padding:13px 15px;background:#FDFAFF}
  #m-invite .opt .rad{color:#C8BFCC;font-size:20px;flex:none;margin-top:1px}
  #m-invite .opt.on .rad{color:#3E007C;font-variation-settings:'FILL' 1}
  #m-invite .opt b{display:block;font-size:14px}
  #m-invite .opt small{display:block;color:var(--muted);font-size:12.5px;margin-top:3px;line-height:1.45}
  #inv-send:disabled{opacity:.65;cursor:not-allowed}
  .split{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
  .split .box{border:1px solid var(--line);border-radius:12px;padding:10px 14px;background:#fff}
  .split .box span{display:block;font-size:12px;color:var(--muted);font-weight:600}
  .split input{border:0;font:inherit;font-size:20px;font-weight:800;width:70px;outline:none;background:transparent}
  .split .calc{font-size:20px;font-weight:800;display:block;margin-top:2px}
  .smodal .smh .ic{margin-right:0}
  .wl-bg{position:fixed;inset:0;background:rgba(26,16,32,.45);z-index:200;display:flex;align-items:center;justify-content:center;padding:16px}
  .wl{background:#fff;border-radius:18px;width:100%;max-width:520px;max-height:calc(100vh - 32px);overflow:auto;box-shadow:0 24px 60px rgba(30,0,60,.25);position:relative}
  .wl-h{padding:26px 28px 6px}.wl-h .wl-ic{width:44px;height:44px;border-radius:12px;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
  .wl-h .wl-ic .ms{font-size:24px}
  .wl-h h2{margin:0;font-size:21px;font-weight:800;letter-spacing:-.4px}.wl-h p{margin:6px 0 0;font-size:14px;color:#5E5763;line-height:1.5}
  .wl-x{position:absolute;top:14px;right:14px;width:34px;height:34px;border-radius:50%;border:0;background:none;cursor:pointer;color:#6F6874;display:flex;align-items:center;justify-content:center}.wl-x:hover{background:#F4F0F6}
  .wl-pr{padding:14px 28px 4px;display:flex;align-items:center;gap:12px;font-size:12.5px;font-weight:700}.wl-bar{flex:1;height:6px;border-radius:4px;background:#F0EDF2}.wl-bar i{display:block;height:6px;border-radius:4px;background:#3E007C}
  .wl-b{padding:6px 28px 8px}.wl-cap{font-size:11px;font-weight:700;letter-spacing:.6px;margin:14px 0 4px}
  .wl-r{display:flex;gap:12px;align-items:flex-start;padding:9px 10px;margin:0 -10px;border-radius:10px;text-decoration:none;color:inherit}a.wl-r:hover{background:#FAF6FC}
  .wl-r .ms{font-size:21px;flex:none}.wl-r .ok{color:#00A650;font-variation-settings:"FILL" 1}.wl-r .no{color:#C8BFCC}.wl-r .go{color:#9A929E;font-size:18px;margin-left:auto;align-self:center}
  .wl-r b{font-size:14px;font-weight:600;display:block}.wl-r small{display:block;font-size:12.5px;color:#6F6874;margin-top:1px;line-height:1.4}.wl-r.dn b{color:#9A929E;text-decoration:line-through;font-weight:500}
  .wl-help{margin:10px 28px 0;background:#F7F3F9;border-radius:12px;padding:12px 14px;display:flex;gap:10px;font-size:13px;line-height:1.5;color:#3F3A45}.wl-help .ms{color:#3E007C;font-size:19px;flex:none;margin-top:1px}.wl-help a{color:#3E007C;font-weight:700}
  .wl-pay{margin:14px 28px 0;border:1px solid #F3C98B;background:#FFF6EC;border-radius:12px;padding:14px 16px;display:flex;gap:12px}.wl-pay>.ms{color:#8A5A00;font-size:22px;flex:none}.wl-pay b{display:block;font-size:14.5px;color:#5C3B00}.wl-pay div>span{display:block;font-size:13px;color:#6B4A10;margin:3px 0 10px;line-height:1.45}.wl-pay .btn{display:inline-flex;text-decoration:none;align-items:center;gap:6px}
  .wl-f{padding:18px 28px 24px;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap}
  @media (max-width:600px){.wl-pay{margin:14px 20px 0}.wl-bg{align-items:flex-end;padding:0}.wl{border-radius:18px 18px 0 0;max-height:92vh}.wl-h,.wl-b,.wl-pr{padding-left:20px;padding-right:20px}.wl-help{margin:10px 20px 0}.wl-f{padding:16px 20px 20px}.wl-f .btn{flex:1;justify-content:center}}
</style>
@endpush

@section('content')
  <div class="head">
    <div>
      <h1>
        Welcome back, {{ $studioName }}
        <a class="help-q" href="https://help.inkjin.com/en/articles/17337922-studio-dashboard-home" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Here's what needs you today, {{ $todayLabel }}.</div>
    </div>
    <div class="btns">
      @include('partials.page-quick-links', [
        'qlPublicUrl' => $publicPageUrl ?? '',
        'qlDisplayHost' => $publicPageHost ?? '',
        'qlRows' => $quickLinkRows ?? [],
        'qlButtonLabel' => 'Open studio page',
        'qlButtonClass' => 'btn ghost',
        'qlFallbackUrl' => route('studio.page.about'),
      ])
      <button type="button" class="btn ghost" data-open="m-invite"><span class="ms">person_add</span>Invite artist</button>
      @include('studio.bookings.partials.new-booking-button')
    </div>
  </div>

  @unless ($profileComplete)
    <div class="card" style="margin-top:22px;background:linear-gradient(135deg,#3E007C,#6A1FB0);color:#fff;border:0">
      <div style="padding:22px 24px;display:flex;gap:18px;align-items:center;flex-wrap:wrap">
        <div style="width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;flex:none">
          <span class="ms" style="font-size:24px">storefront</span>
        </div>
        <div style="flex:1;min-width:220px">
          <b style="font-size:17px">Complete your studio profile</b>
          <div style="font-size:13.5px;opacity:.9;margin-top:3px;line-height:1.5">Get full access to your studio dashboard. Free for studios. No monthly fee, no setup fee, no commission.</div>
        </div>
        <div class="row" style="gap:8px;flex-wrap:wrap">
          <button type="button" class="btn ghost" data-open-profile-prompt style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.35)">See what you get</button>
          <a class="btn" href="{{ route('studio.onboarding.profile') }}" style="background:#fff;color:#3E007C">Complete profile</a>
        </div>
      </div>
    </div>
  @endunless

  <div class="studio-gsb" id="studioGsb" role="region" aria-label="Guest Spot Board" hidden>
    <div class="sgi" aria-hidden="true"><span class="ms">flight_takeoff</span></div>
    <div class="sgt">
      <div>
        <span class="sg-new">NEW</span>
        <b>The Guest Spot Board is live</b>
      </div>
      <span>Have an open chair? Post it for free, and guest artists who match your city, dates and style get an email.</span>
    </div>
    <a class="sgb" href="https://inkjin.com/en/bookpay/guest-spot-board/" target="_blank" rel="noopener">
      Post an open chair<span class="ms" aria-hidden="true">open_in_new</span>
    </a>
    <button type="button" class="sgx" id="studioGsbDismiss" aria-label="Dismiss">
      <span class="ms">close</span>
    </button>
  </div>

  <div class="dash-top">
    <div class="card">
      <div class="ch">
        <div class="row" style="gap:10px;min-width:0;flex:1">
          <h3>To do</h3>
          @if (count($todos) > 0)
            <span class="pill nd p" style="background:#F3E8FF;color:#7A1FC4">{{ count($todos) }}</span>
          @endif
        </div>
        <span class="faint" style="font-size:12.5px;flex-shrink:0">Items clear when they're done</span>
      </div>
      <div style="padding:4px 22px 6px">
        @forelse ($todos as $todo)
          <div class="row dash-todo-row">
            <div class="ic" style="background:{{ $todo['icon_bg'] }};color:{{ $todo['icon_color'] }}"><span class="ms">{{ $todo['icon'] }}</span></div>
            <div>
              <b style="font-size:14px">{{ $todo['title'] }}</b>
              <div class="faint" style="font-size:12px;margin-top:2px;overflow-wrap:anywhere">{{ $todo['detail'] }}</div>
            </div>
            <a class="btn sm ghost" href="{{ $todo['url'] }}">{{ $todo['cta'] }}</a>
          </div>
        @empty
          <div class="faint" style="font-size:13.5px;padding:18px 0 22px;line-height:1.45">You're all caught up. New join requests and invites will show up here.</div>
        @endforelse
      </div>
    </div>

    <div class="card dash-check">
      <div class="ch">
        <h3>Complete your studio</h3>
        <b style="font-size:13px">{{ $checklistDone }} of {{ $checklistTotal }}</b>
      </div>
      <div style="padding:14px 22px 16px">
        <div style="height:6px;border-radius:4px;background:#F0EDF2">
          <div style="width:{{ $checklistPct }}%;height:6px;border-radius:4px;background:var(--pri)"></div>
        </div>
        <div class="faint" style="font-size:12px;margin:8px 0 4px">Clients choose studios with complete pages.</div>

        @if (count($checklistNeed) > 0)
          <div style="font-size:11px;font-weight:700;letter-spacing:.6px;color:#B3261E;margin:6px 0 2px">NEEDED TO RECEIVE SPLITS</div>
          @foreach ($checklistNeed as $item)
            @if ($item['done'])
              <div class="row" style="padding:6px 0;gap:10px">
                <span class="ms" style="font-size:20px;color:#00A650;font-variation-settings:'FILL' 1">check_circle</span>
                <span style="font-size:13.5px;color:#9A929E;text-decoration:line-through">{{ $item['label'] }}</span>
              </div>
            @else
              <a href="{{ $item['url'] }}" class="row" style="padding:7px 0;gap:10px;text-decoration:none;color:inherit">
                <span class="ms" style="font-size:20px;color:#C8BFCC">radio_button_unchecked</span>
                <span style="flex:1;font-size:13.5px;font-weight:600">{{ $item['label'] }}</span>
                <span class="ms" style="font-size:18px;color:#9A929E">chevron_right</span>
              </a>
            @endif
          @endforeach
        @endif

        <div style="font-size:11px;font-weight:700;letter-spacing:.6px;color:#6F6874;margin:12px 0 2px">MAKE YOUR STUDIO STAND OUT</div>
        @foreach ($checklistMore as $item)
          @if ($item['done'])
            <div class="row" style="padding:6px 0;gap:10px">
              <span class="ms" style="font-size:20px;color:#00A650;font-variation-settings:'FILL' 1">check_circle</span>
              <span style="font-size:13.5px;color:#9A929E;text-decoration:line-through">{{ $item['label'] }}</span>
            </div>
          @else
            <a href="{{ $item['url'] }}" class="row" style="padding:7px 0;gap:10px;text-decoration:none;color:inherit">
              <span class="ms" style="font-size:20px;color:#C8BFCC">radio_button_unchecked</span>
              <span style="flex:1;font-size:13.5px;font-weight:600">{{ $item['label'] }}</span>
              <span class="ms" style="font-size:18px;color:#9A929E">chevron_right</span>
            </a>
          @endif
        @endforeach
      </div>
    </div>
  </div>

  <div class="dash-stats">
    <div class="card pad">
      <div class="row" style="gap:10px"><div class="ic"><span class="ms">event_available</span></div><span class="lbl">Bookings this month</span></div>
      <div style="font-size:26px;font-weight:800;margin-top:10px">{{ $bookingsThisMonth }}</div>
      <div class="faint" style="font-size:12px">{{ $bookingsMeta }}</div>
    </div>
    <div class="card pad">
      <div class="row" style="gap:10px"><div class="ic"><span class="ms">payments</span></div><span class="lbl">Studio earnings</span></div>
      <div style="font-size:26px;font-weight:800;margin-top:10px">{{ $earnedThisMonthLabel }}</div>
      <div class="faint" style="font-size:12px">{{ $monthDeltaLabel }}</div>
    </div>
    <div class="card pad">
      <div class="row" style="gap:10px"><div class="ic"><span class="ms">groups</span></div><span class="lbl">Active artists</span></div>
      <div style="font-size:26px;font-weight:800;margin-top:10px">{{ $activeArtistCount }}</div>
      <div class="faint" style="font-size:12px">{{ $artistsMeta }}</div>
    </div>
    <div class="card pad">
      <div class="row" style="gap:10px"><div class="ic"><span class="ms">account_balance</span></div><span class="lbl">Next payout</span></div>
      <div style="font-size:26px;font-weight:800;margin-top:10px">{{ $nextPayoutLabel }}</div>
      <div class="faint" style="font-size:12px">{{ $nextPayoutMeta }}</div>
    </div>
  </div>

  <div class="card dash-up">
    <div class="ch">
      <h3>Upcoming at your studio</h3>
      <a class="l" href="{{ route('studio.bookings', ['f' => 'upcoming']) }}" style="font-weight:600;font-size:13px;text-decoration:none;color:var(--pri)">View all <span class="ms" style="font-size:16px;vertical-align:-3px">arrow_forward</span></a>
    </div>
    @if ($upcomingBookings->isEmpty())
      <div class="faint" style="padding:28px 22px;font-size:13.5px">No upcoming bookings yet.</div>
    @else
      <table>
        <thead>
          <tr>
            <th>CLIENT</th>
            <th>ARTIST</th>
            <th>SERVICE</th>
            <th>DATE</th>
            <th>BOOKED THROUGH</th>
            <th>STUDIO SHARE</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($upcomingBookings as $row)
            <tr>
              <td data-label="Client"><b style="font-weight:600">{{ $row['client'] }}</b></td>
              <td data-label="Artist">{{ $row['artist'] }}</td>
              <td data-label="Service" class="muted">{{ $row['service'] }}</td>
              <td data-label="Date" style="white-space:nowrap">{{ $row['when'] }}</td>
              <td data-label="Booked through"><span class="pill nd k">{{ $row['through'] }}</span></td>
              <td data-label="Studio share"><b>{{ $row['share'] }}</b></td>
              <td data-label="">
                <button type="button" class="ms faint js-view-booking" style="border:0;background:none;cursor:pointer;color:var(--faint)"
                  data-id="{{ $row['id'] }}"
                  aria-label="View booking">visibility</button>
                <script type="application/json" id="bk-data-{{ $row['id'] }}">@json($row['modal'])</script>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

  @include('studio.artists.partials.invite-modal')
  @include('studio.bookings.partials.booking-detail-modal')
  @include('studio.bookings.partials.payment-link-modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@include('studio.bookings.partials.payment-link-modal-script')
@include('studio.bookings.partials.booking-detail-modal-script')
@include('studio.artists.partials.invite-modal-script')
@include('partials.page-quick-links-script')
<script>
(function () {
  var banner = document.getElementById('studioGsb');
  if (!banner) return;

  var key = 'bp-studio-gsb-dismissed';
  try { localStorage.removeItem(key); } catch (e) {}

  function hide() {
    banner.setAttribute('hidden', '');
    banner.style.display = 'none';
  }

  function show() {
    banner.removeAttribute('hidden');
    banner.style.display = '';
  }

  try {
    if (sessionStorage.getItem(key) === '1') hide();
    else show();
  } catch (e) {
    show();
  }

  document.getElementById('studioGsbDismiss')?.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    hide();
    try { sessionStorage.setItem(key, '1'); } catch (x) {}
  });
})();
</script>
<script>
(function () {
  function $$(sel, root) { return [].slice.call((root || document).querySelectorAll(sel)); }
  function closeAll() { $$('.smodal').forEach(function (m) { m.hidden = true; }); }

  document.addEventListener('click', function (e) {
    var t;
    if (e.target.classList.contains('smodal') || (t = e.target.closest('[data-close]'))) {
      if (e.target.classList.contains('smodal') || t) closeAll();
      return;
    }
    if ((t = e.target.closest('[data-open]'))) {
      e.preventDefault();
      var id = t.getAttribute('data-open');
      var m = document.getElementById(id);
      if (m) m.hidden = false;
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAll();
  });

  var pct = document.getElementById('inv-artist-pct');
  var them = document.getElementById('invsp');
  if (pct) {
    pct.value = @json((int) ($defaultArtistPercent ?? 60));
    if (them) them.textContent = (100 - parseInt(pct.value, 10)) + '%';
    pct.addEventListener('input', function () {
      var v = parseInt(pct.value, 10);
      if (them) them.textContent = (v >= 0 && v <= 100) ? (100 - v) + '%' : '–';
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

  document.querySelectorAll('#m-pl [data-pldone]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (document.getElementById('pl-done') && !document.getElementById('pl-done').hidden) {
        window.location.href = @json(route('studio.bookings.payment-links'));
      }
    });
  });
})();
</script>
@php
  $wlNeed = collect($checklistNeed ?? [])->map(fn ($i) => [
    't' => $i['label'],
    's' => $i['hint'],
    'h' => $i['url'],
    'done' => $i['done'],
  ])->values();
  $wlMore = collect($checklistMore ?? [])->map(fn ($i) => [
    't' => $i['label'],
    's' => $i['hint'],
    'h' => $i['url'],
    'done' => $i['done'],
  ])->values();
@endphp
<script>
(function () {
  var showWelcome = @json((bool) ($showWelcomePopup ?? false));
  var forceWelcome = location.hash === '#welcome';
  if (!showWelcome && !forceWelcome) return;

  var studioName = @json($studioName);
  var nopay = @json((bool) (($needsStripe ?? true) && ! ($stripeConnected ?? false)));
  var ownart = @json((bool) ($ownerIsArtist ?? false));
  var pageUrl = @json($publicPageUrl ?? '');
  var stripeUrl = @json($stripeConnectUrl ?? route('studio.money.payouts'));
  var markUrl = @json(route('studio.welcome-seen'));
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var NEED = @json($wlNeed);
  var MORE = @json($wlMore);

  var all = NEED.concat(MORE);
  var done = all.filter(function (x) { return x.done; }).length;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function row(x) {
    if (x.done) {
      return '<div class="wl-r dn"><span class="ms ok">check_circle</span><span><b>' + esc(x.t) + '</b></span></div>';
    }
    return '<a class="wl-r" href="' + esc(x.h) + '"><span class="ms no">radio_button_unchecked</span><span><b>' + esc(x.t) + '</b>'
      + (x.s ? '<small>' + esc(x.s) + '</small>' : '') + '</span><span class="ms go">chevron_right</span></a>';
  }

  function markSeen() {
    fetch(markUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
      },
    }).catch(function () {});
  }

  function open() {
    if (document.querySelector('.wl-bg')) return;
    var title = 'Welcome to Bookpay' + (studioName ? ', ' + studioName : '');
    var bg = document.createElement('div');
    bg.className = 'wl-bg';
    bg.innerHTML =
      '<div class="wl" role="dialog" aria-modal="true" aria-labelledby="wl-t">'
      + '<button type="button" class="wl-x" aria-label="Close"><span class="ms">close</span></button>'
      + '<div class="wl-h"><div class="wl-ic"><span class="ms">storefront</span></div>'
      + '<h2 id="wl-t">' + esc(title) + '</h2>'
      + '<p>Your studio is set up. Here\'s what\'s left so clients can find your studio and book your artists.</p></div>'
      + '<div class="wl-pr"><div class="wl-bar"><i style="width:' + Math.round(done / Math.max(1, all.length) * 100) + '%"></i></div>'
      + done + ' of ' + all.length + ' done</div>'
      + (nopay
        ? '<div class="wl-pay"><span class="ms">account_balance</span><div><b>First, connect payouts</b>'
          + '<span>Connect Stripe so your share of each revenue split can be paid to you. Until then your artists on a split are paid in full, and the split starts once you connect.</span>'
          + '<a class="btn" href="' + esc(stripeUrl) + '">Connect Stripe <span class="ms" style="font-size:17px">arrow_forward</span></a></div></div>'
        : '')
      + '<div class="wl-b">'
      + (NEED.length ? '<div class="wl-cap" style="color:#B3261E">NEEDED TO RECEIVE SPLITS</div>' + NEED.map(row).join('') : '')
      + '<div class="wl-cap" style="color:#6F6874">MAKE YOUR STUDIO STAND OUT</div>' + MORE.map(row).join('')
      + (ownart
        ? '<div class="wl-cap" style="color:#6F6874">YOUR OWN ARTIST ACCOUNT</div>'
          + '<div class="wl-r"><span class="ms no" style="color:#3E007C">mark_email_unread</span><span><b>Set up your artist account</b>'
          + '<small>You said you also tattoo here. Check your email for an invitation to set up your own artist account. It\'s a separate login from this studio account.</small></span></div>'
        : '')
      + '</div>'
      + '<div class="wl-help"><span class="ms">support_agent</span><span>Need help? Email us at <a href="mailto:support@inkjin.com">support@inkjin.com</a> or click <b>Get Help</b> in the menu to chat with us.</span></div>'
      + '<div class="wl-f">'
      + (pageUrl ? '<a class="btn ghost" href="' + esc(pageUrl) + '" target="_blank" rel="noopener" style="text-decoration:none">View studio page</a>' : '')
      + '<button type="button" class="btn" data-wl-close>Got it</button></div></div>';

    document.body.appendChild(bg);
    document.body.style.overflow = 'hidden';

    function close() {
      bg.remove();
      document.body.style.overflow = '';
      document.removeEventListener('keydown', escKey);
      markSeen();
      if (location.hash === '#welcome') history.replaceState(null, '', location.pathname + location.search);
    }
    function escKey(e) { if (e.key === 'Escape') close(); }

    bg.addEventListener('click', function (e) {
      if (e.target === bg || e.target.closest('.wl-x,[data-wl-close]')) close();
      if (e.target.closest('a.wl-r') || e.target.closest('.wl-pay .btn')) markSeen();
    });
    document.addEventListener('keydown', escKey);
    (bg.querySelector('.wl-pay .btn') || bg.querySelector('[data-wl-close]'))?.focus({ preventScroll: true });
  }

  window.bpWelcome = open;
  if (showWelcome || forceWelcome) {
    setTimeout(open, 180);
  }
  window.addEventListener('hashchange', function () {
    if (location.hash === '#welcome') open();
  });
})();
</script>
@endpush
