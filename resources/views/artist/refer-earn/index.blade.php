@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Refer & Earn')

@php
  $activeNav = 'referrals';
  $user = Auth::user();
  $user->loadMissing('userDetail');
  $referralUsername = trim((string) ($user->userDetail?->user_name ?? ''));
  $referralLink = $referralUsername !== ''
    ? rtrim((string) config('app.url'), '/').'/register/'.$referralUsername
    : null;
  $referralLinkDisplay = $referralLink
    ? preg_replace('#^https?://#', '', $referralLink)
    : null;
  $shareMessage = $referralLinkDisplay
    ? "I'm using BookPay to manage bookings and payments and I think you'd love it too. Use my link to sign up and your first client's booking fee will be free: ".$referralLinkDisplay
    : null;
  $referrals = $referrals ?? collect();
  $earnings = $earnings ?? [];
  $counts = $counts ?? ['all' => 0, 'signed_up' => 0, 'earned' => 0];
  $earningsItems = collect($earnings['items'] ?? []);
  $recentReferrals = $referrals->take(3);
  $pendingArtists = (int) ($earnings['pending_artists'] ?? $counts['signed_up'] ?? 0);
  $paidArtists = (int) ($earnings['paid_artists'] ?? 0);
  $onItsWayTotal = (float) ($earnings['on_its_way_total'] ?? 0);
  $paidTotal = (float) ($earnings['paid_total'] ?? 0);
  $totalEarned = (float) ($earnings['total_earned'] ?? ($onItsWayTotal + $paidTotal));
  $totalArtists = (int) ($earnings['total_artists'] ?? 0);
@endphp

@section('styles')
<style>
  .refer-page .help-q{
    display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-left:8px;
    border-radius:50%;color:#9A929E;vertical-align:middle;text-decoration:none;position:relative;top:-2px
  }
  .refer-page .help-q .ms{font-size:22px}
  .refer-page .help-q:hover{color:#3E007C;background:#F3E8FF}
  .refer-page .tabs div{cursor:pointer}
  .refer-page button.btn{border:0;cursor:pointer;font:inherit}
  .refer-page .chip{cursor:pointer;font:inherit}
  .refer-page .chip.on{background:#1A1A1A;border-color:#1A1A1A;color:#fff}
  .refer-page .hero-card{
    padding:26px;background:linear-gradient(135deg,#2A0A55,#3E007C);color:#fff;border:0;border-radius:16px
  }
  .refer-page .hero-card .in{background:#fff;color:#1A1A1A}
  .refer-page .hero-ghost{background:rgba(255,255,255,.12);color:#fff}
  .refer-page .hero-ghost:hover{background:rgba(255,255,255,.18)}
  .refer-page .step-n{
    width:24px;height:24px;border-radius:50%;background:#F3E8FF;color:#3E007C;font-weight:800;font-size:12px;
    display:flex;align-items:center;justify-content:center;flex-shrink:0
  }
  .refer-page .pill.signed_up,.refer-page .pill.a{background:var(--amberl);color:var(--amber)}
  .refer-page .pill.waiting_for_payout,.refer-page .pill.on_its_way,.refer-page .pill.b{background:var(--bluel);color:var(--blue)}
  .refer-page .pill.earned,.refer-page .pill.paid,.refer-page .pill.g{background:var(--greenl);color:var(--green)}
  .refer-page .pill.rejected,.refer-page .pill.r{background:var(--redl);color:var(--red)}
  .refer-page .refer-copy-btn.is-copied{background:#E6F8EE!important;color:#00A650!important}
  .refer-page .bdm{
    position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;
    z-index:80;padding:16px
  }
  .refer-page .bdm.open{display:flex}
  .refer-page .bdm-box{
    background:#fff;border-radius:22px;width:400px;max-width:100%;max-height:min(86vh,640px);
    box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;display:flex;flex-direction:column
  }
  .refer-page .bdm-h{padding:22px 22px 14px;border-bottom:1px solid var(--line);display:flex;gap:12px;align-items:flex-start}
  .refer-page .bdm-h h2{font-size:18px;font-weight:800;letter-spacing:-.3px;flex:1}
  .refer-page .bdm-x{border:0;background:none;cursor:pointer;padding:4px;border-radius:8px;color:var(--muted)}
  .refer-page .bdm-x:hover{background:var(--chip);color:var(--ink)}
  .refer-page .bdm-b{padding:18px 22px 22px;overflow:auto}
  .refer-page #referQrCode{
    width:170px;height:170px;margin:0 auto;padding:0;background:#fff;border-radius:8px;
    border:8px solid #fff;box-shadow:0 0 0 1px #EFE8F1;overflow:hidden
  }
  .refer-page #referQrCode img,
  .refer-page #referQrCode canvas{
    display:block;width:170px!important;height:170px!important;max-width:none!important
  }
  .refer-page #referQrCode table{
    width:170px!important;height:170px!important;border-collapse:collapse;margin:0
  }
  .refer-page #referQrCode table td{
    display:table-cell!important;padding:0!important;border:0!important;width:auto!important;
    justify-content:unset!important;text-align:left!important
  }
  .refer-page #referQrCode table td::before{display:none!important;content:none!important}
  @media(max-width:900px){
    .refer-page .card:has(table){overflow:visible;background:none;border:0}
    .refer-page .card table,.refer-page .card tbody,.refer-page .card tr,.refer-page .card td{display:block;width:100%}
    .refer-page .card tr:has(th){display:none}
    .refer-page .card tbody tr{
      position:relative;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px
    }
    .refer-page .card td{
      border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;
      gap:12px;font-size:13.5px;text-align:right
    }
    .refer-page .card td::before{
      content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0
    }
    .refer-page .card td:first-child{
      padding:0 0 8px!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;
      justify-content:flex-start;text-align:left
    }
    .refer-page .card td:first-child::before{display:none}
    .refer-page .card td:first-child>*{text-align:left}
  }
</style>
@endsection

@section('content')
<div class="refer-page">
  <div class="head">
    <div>
      <h1>
        Refer &amp; Earn
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201469-dashboard-referrals-get-your-link" target="_blank" rel="noopener" data-refer-help title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Earn cash when you bring artists you know to Bookpay.</div>
    </div>
  </div>

  <div class="tabs">
    <div class="on" data-refer-tab="link">Get your link</div>
    <div data-refer-tab="referrals">Referrals</div>
    <div data-refer-tab="earnings">Earnings</div>
  </div>

  {{-- Get your link --}}
  <div data-refer-panel="link">
    <div class="grid" style="grid-template-columns:1.3fr 1fr;gap:16px;align-items:start">
      <div>
        <div class="hero-card">
          <div style="font-size:26px;font-weight:800;letter-spacing:-.4px">Get €20 for every artist you refer</div>
          <div style="color:#D9C8F2;margin-top:6px;line-height:1.5;max-width:520px">
            When an artist you refer gets their first booking, you get €20 and their first booking fee is waived. No limit on how many artists you refer.
          </div>
          @if ($referralLink)
            <div class="row" style="margin-top:18px;gap:10px;flex-wrap:wrap">
              <div class="in" style="flex:1;min-width:180px" title="{{ $referralLinkDisplay }}">{{ $referralLinkDisplay }}</div>
              <button type="button" class="btn refer-copy-btn" id="referCopyBtn" data-link="{{ $referralLink }}" style="background:#fff;color:#1A1A1A">
                <span class="ms">content_copy</span><span class="refer-copy-label">Copy</span>
              </button>
            </div>
            <div class="row" style="margin-top:10px;gap:10px;flex-wrap:wrap">
              <button type="button" class="btn hero-ghost" id="referShareMessageBtn" data-message="{{ $shareMessage }}">
                <span class="ms">chat</span>Share via message
              </button>
              <button type="button" class="btn hero-ghost" id="referShowQrBtn">
                <span class="ms">qr_code_2</span>Show QR code
              </button>
            </div>
          @else
            <div style="margin-top:18px;color:#D9C8F2;font-size:13.5px">Set your username first to generate your referral link.</div>
            <a href="{{ route('profile.edit') }}" class="btn" style="margin-top:12px;background:#fff;color:#1A1A1A;display:inline-flex">Set username</a>
          @endif
        </div>

        <div class="card pad" style="margin-top:14px">
          <div class="lbl" style="margin-bottom:6px">How it works</div>
          <div class="row" style="padding:7px 0;gap:12px"><span class="step-n">1</span><span style="font-size:13.5px">Share your unique referral link</span></div>
          <div class="row" style="padding:7px 0;gap:12px"><span class="step-n">2</span><span style="font-size:13.5px">An artist friend signs up. One of their clients completes a first booking.</span></div>
          <div class="row" style="padding:7px 0;gap:12px"><span class="step-n">3</span><span style="font-size:13.5px">You get €20, paid straight to your account</span></div>
          <div class="row" style="padding:7px 0;gap:12px"><span class="step-n">4</span><span style="font-size:13.5px">They get their first client's booking fee waived</span></div>
          <a href="https://inkjin.com/en/refer-and-earn-terms" target="_blank" rel="noopener noreferrer" style="font-size:12.5px;font-weight:600;text-decoration:underline;margin-top:8px;display:inline-block;color:inherit">
            Refer &amp; Earn terms and conditions
          </a>
        </div>
      </div>

      <div>
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
          <div class="card pad">
            <div class="lbl">Pending</div>
            <div style="font-size:26px;font-weight:800;margin-top:6px">€{{ number_format(0, 0) }}</div>
            <div class="faint" style="font-size:12px">{{ $pendingArtists }} {{ \Illuminate\Support\Str::plural('artist', $pendingArtists) }} signed up</div>
          </div>
          <div class="card pad">
            <div class="lbl">Paid out</div>
            <div style="font-size:26px;font-weight:800;margin-top:6px">€{{ number_format($paidTotal, 0) }}</div>
            <div class="faint" style="font-size:12px">{{ $paidArtists }} {{ \Illuminate\Support\Str::plural('artist', $paidArtists) }} rewarded</div>
          </div>
        </div>

        <div class="card" style="margin-top:14px">
          <div class="ch">
            <h3>Recent referrals</h3>
            <button type="button" class="l" data-refer-goto="referrals" style="cursor:pointer;background:none;border:0;font:inherit;color:inherit;padding:0">View all</button>
          </div>
          @forelse ($recentReferrals as $referral)
            <div class="row" style="padding:12px 22px;{{ ! $loop->last ? 'border-bottom:1px solid #F3EEF4;' : '' }}">
              <div style="flex:1;min-width:0">
                <b style="font-size:13.5px">{{ $referral['artist_name'] }}</b>
                <div class="faint" style="font-size:12px">
                  @if ($referral['first_booking'])
                    First booking {{ $referral['first_booking'] }}
                  @else
                    Signed up {{ $referral['date'] }} · no booking yet
                  @endif
                </div>
              </div>
              <span class="pill {{ $referral['status'] }}">{{ $referral['status_label'] }}</span>
            </div>
          @empty
            <div class="pad faint" style="font-size:13px">No referrals yet. Share your link to get started.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- Referrals list --}}
  <div data-refer-panel="referrals" hidden>
    <div class="row" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
      <div class="chips" id="referFilterChips">
        <button type="button" class="chip on" data-refer-filter="all">All {{ $counts['all'] }}</button>
        <button type="button" class="chip" data-refer-filter="signed_up">Signed up {{ $counts['signed_up'] }}</button>
        <button type="button" class="chip" data-refer-filter="earned">Earned {{ $counts['earned'] }}</button>
      </div>
      <div class="faint" style="font-size:12.5px">You earn €20 when a referred artist’s client completes a first booking.</div>
    </div>

    <div class="card" style="overflow:hidden">
      <table>
        <thead>
          <tr>
            <th>ARTIST</th>
            <th>SIGNED UP</th>
            <th>FIRST BOOKING</th>
            <th>STATUS</th>
            <th>YOUR REWARD</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($referrals as $referral)
            <tr data-refer-row data-status="{{ $referral['status'] }}">
              <td data-label="ARTIST">
                <b>{{ $referral['artist_name'] }}</b>
                @if ($referral['artist_username'] !== '')
                  <div class="faint" style="font-size:12px">{{ '@'.$referral['artist_username'] }}</div>
                @elseif ($referral['artist_email'] !== '')
                  <div class="faint" style="font-size:12px">{{ $referral['artist_email'] }}</div>
                @endif
              </td>
              <td data-label="SIGNED UP">{{ $referral['date'] }}</td>
              <td data-label="FIRST BOOKING">
                @if ($referral['first_booking'])
                  {{ $referral['first_booking'] }}
                @else
                  <span class="faint">No booking yet</span>
                @endif
              </td>
              <td data-label="STATUS"><span class="pill {{ $referral['status'] }}">{{ $referral['status_label'] }}</span></td>
              <td data-label="YOUR REWARD"><b>€{{ number_format((float) $referral['amount'], 0) }}</b></td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="pad faint" style="text-align:center">No referrals yet. Share your link to get started.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card pad" style="margin-top:14px;display:flex;gap:14px;align-items:center;flex-wrap:wrap">
      <span class="ms" style="color:#3E007C">person_add</span>
      <div style="flex:1;min-width:200px">
        <b>Invite more artists</b>
        <div class="faint" style="font-size:12.5px">
          @if ($referralLinkDisplay)
            Share your link: {{ $referralLinkDisplay }}
          @else
            Set your username to get a referral link.
          @endif
        </div>
      </div>
      <button type="button" class="btn" data-refer-goto="link"><span class="ms">link</span>Get your link</button>
    </div>
  </div>

  {{-- Earnings --}}
  <div data-refer-panel="earnings" hidden>
    <div class="grid" style="grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px">
      <div class="card pad">
        <div class="lbl">Total earned</div>
        <div style="font-size:28px;font-weight:800;margin-top:6px">€{{ number_format($totalEarned, 0) }}</div>
        <div class="faint" style="font-size:12px">From {{ $totalArtists }} {{ \Illuminate\Support\Str::plural('artist', $totalArtists) }}</div>
      </div>
      <div class="card pad">
        <div class="lbl">On its way</div>
        <div style="font-size:28px;font-weight:800;margin-top:6px">€{{ number_format($onItsWayTotal, 0) }}</div>
        <div class="faint" style="font-size:12px">Paid automatically</div>
      </div>
      <div class="card pad">
        <div class="lbl">Paid out</div>
        <div style="font-size:28px;font-weight:800;margin-top:6px">€{{ number_format($paidTotal, 0) }}</div>
        <div class="faint" style="font-size:12px">To your payout account</div>
      </div>
    </div>

    <div class="note" style="margin-bottom:14px">
      <span class="ms">bolt</span>
      Referral rewards are paid automatically to the same bank account as your payouts.
    </div>

    <div class="card" style="overflow:hidden">
      <div class="ch"><h3>Reward history</h3></div>
      <table>
        <thead>
          <tr>
            <th>DATE</th>
            <th>ARTIST</th>
            <th>AMOUNT</th>
            <th>STATUS</th>
            <th>DETAILS</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($earningsItems as $item)
            <tr>
              <td data-label="DATE">{{ $item['date'] }}</td>
              <td data-label="ARTIST"><b>{{ $item['artist_name'] }}</b></td>
              <td data-label="AMOUNT"><b>€{{ number_format((float) $item['amount'], 0) }}</b></td>
              <td data-label="STATUS"><span class="pill {{ $item['state'] }}">{{ $item['status_label'] }}</span></td>
              <td data-label="DETAILS" class="faint">{{ $item['details'] }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="pad faint" style="text-align:center">
                No earnings yet. When a referred artist completes their first booking, your reward will show here.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- QR modal --}}
  <div class="bdm" id="referQrModal" role="dialog" aria-modal="true" aria-labelledby="referQrTitle" aria-hidden="true">
    <div class="bdm-box" onclick="event.stopPropagation()">
      <div class="bdm-h">
        <h2 id="referQrTitle">Your referral QR code</h2>
        <button type="button" class="bdm-x" id="referQrCloseBtn" aria-label="Close"><span class="ms">close</span></button>
      </div>
      <div class="bdm-b" style="text-align:center">
        <div style="display:flex;justify-content:center;padding:8px 0 12px">
          <div id="referQrCode" data-link="{{ $referralLink }}"></div>
        </div>
        <p style="margin:0;font-size:13.5px;color:var(--muted);line-height:1.5">
          Artists scan it to sign up with your link.
          @if ($referralLinkDisplay)
            <br><b style="color:var(--ink)">{{ $referralLinkDisplay }}</b>
          @endif
        </p>
      </div>
    </div>
  </div>

  <div id="referToast" class="bpw-toast" role="status" aria-live="polite">
    <span class="ms">check_circle</span>
    <span id="referToastMsg">Done</span>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  (function () {
    var toastEl = document.getElementById('referToast');
    var toastMsg = document.getElementById('referToastMsg');
    var toastTimer = null;

    function showToast(message) {
      if (!toastEl) return;
      if (toastMsg) toastMsg.textContent = message || 'Done';
      toastEl.classList.add('on');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () {
        toastEl.classList.remove('on');
      }, 2200);
    }

    var helpLinks = {
      link: 'https://help.inkjin.com/en/articles/17201469-dashboard-referrals-get-your-link',
      referrals: 'https://help.inkjin.com/en/articles/17201477-dashboard-referrals-referrals',
      earnings: 'https://help.inkjin.com/en/articles/17201480-dashboard-referrals-earnings'
    };
    var helpEl = document.querySelector('[data-refer-help]');
    var buttons = document.querySelectorAll('[data-refer-tab]');
    var panels = document.querySelectorAll('[data-refer-panel]');

    function setTab(tab) {
      buttons.forEach(function (b) {
        b.classList.toggle('on', b.getAttribute('data-refer-tab') === tab);
      });
      panels.forEach(function (panel) {
        var match = panel.getAttribute('data-refer-panel') === tab;
        if (match) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', '');
        }
      });
      if (helpEl && helpLinks[tab]) {
        helpEl.setAttribute('href', helpLinks[tab]);
      }
      try {
        history.replaceState(null, '', '#' + tab);
      } catch (e) {}
    }

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        setTab(btn.getAttribute('data-refer-tab'));
      });
    });

    document.querySelectorAll('[data-refer-goto]').forEach(function (el) {
      el.addEventListener('click', function () {
        setTab(el.getAttribute('data-refer-goto'));
      });
    });

    var hash = (location.hash || '').replace('#', '');
    if (hash === 'referrals' || hash === 'earnings' || hash === 'link') {
      setTab(hash);
    }

    var chips = document.querySelectorAll('[data-refer-filter]');
    var rows = document.querySelectorAll('[data-refer-row]');
    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        var filter = chip.getAttribute('data-refer-filter');
        chips.forEach(function (c) { c.classList.remove('on'); });
        chip.classList.add('on');
        rows.forEach(function (row) {
          var status = row.getAttribute('data-status') || '';
          var show = filter === 'all'
            || (filter === 'signed_up' && status === 'signed_up')
            || (filter === 'earned' && (status === 'earned' || status === 'waiting_for_payout'));
          row.style.display = show ? '' : 'none';
        });
      });
    });

    function fallbackCopyText(text, onSuccess) {
      var input = document.createElement('textarea');
      input.value = text;
      input.setAttribute('readonly', '');
      input.style.position = 'absolute';
      input.style.left = '-9999px';
      document.body.appendChild(input);
      input.select();
      try {
        document.execCommand('copy');
        if (typeof onSuccess === 'function') onSuccess();
      } catch (e) {}
      document.body.removeChild(input);
    }

    function copyText(text, onSuccess) {
      if (!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(function () {
          fallbackCopyText(text, onSuccess);
        });
        return;
      }
      fallbackCopyText(text, onSuccess);
    }

    var copyBtn = document.getElementById('referCopyBtn');
    if (copyBtn) {
      var resetTimer = null;
      copyBtn.addEventListener('click', function () {
        copyText(copyBtn.getAttribute('data-link') || '', function () {
          showToast('Link copied');
          copyBtn.classList.add('is-copied');
          copyBtn.innerHTML = '<span class="ms">check_circle</span><span class="refer-copy-label">Copied</span>';
          if (resetTimer) clearTimeout(resetTimer);
          resetTimer = setTimeout(function () {
            copyBtn.classList.remove('is-copied');
            copyBtn.innerHTML = '<span class="ms">content_copy</span><span class="refer-copy-label">Copy</span>';
          }, 2000);
        });
      });
    }

    var shareMessageBtn = document.getElementById('referShareMessageBtn');
    if (shareMessageBtn) {
      var shareResetTimer = null;
      shareMessageBtn.addEventListener('click', function () {
        copyText(shareMessageBtn.getAttribute('data-message') || '', function () {
          showToast('Message copied. Paste it in any chat');
          shareMessageBtn.innerHTML = '<span class="ms">check_circle</span>Message copied';
          if (shareResetTimer) clearTimeout(shareResetTimer);
          shareResetTimer = setTimeout(function () {
            shareMessageBtn.innerHTML = '<span class="ms">chat</span>Share via message';
          }, 2000);
        });
      });
    }

    var qrModal = document.getElementById('referQrModal');
    var qrContainer = document.getElementById('referQrCode');
    var showQrBtn = document.getElementById('referShowQrBtn');
    var closeQrBtn = document.getElementById('referQrCloseBtn');
    var qrGenerated = false;

    function renderQrFallback(link) {
      if (!qrContainer || !link) return;
      qrContainer.innerHTML = '';
      var img = document.createElement('img');
      img.width = 170;
      img.height = 170;
      img.alt = 'Referral QR code';
      img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=170x170&margin=0&data=' + encodeURIComponent(link);
      qrContainer.appendChild(img);
      qrGenerated = true;
    }

    function renderQr() {
      if (!qrContainer || qrGenerated) return;
      var link = qrContainer.getAttribute('data-link') || '';
      if (!link) return;

      if (typeof QRCode === 'undefined') {
        renderQrFallback(link);
        return;
      }

      qrContainer.innerHTML = '';
      try {
        new QRCode(qrContainer, {
          text: link,
          width: 170,
          height: 170,
          colorDark: '#1A1A1A',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        });
        qrGenerated = true;
      } catch (e) {
        renderQrFallback(link);
      }
    }

    function openQrModal() {
      if (!qrModal) return;
      qrModal.classList.add('open');
      qrModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      // Generate after the overlay is visible so canvas/table can size correctly.
      requestAnimationFrame(function () {
        renderQr();
      });
    }

    function closeQrModal() {
      if (!qrModal) return;
      qrModal.classList.remove('open');
      qrModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    if (showQrBtn) showQrBtn.addEventListener('click', openQrModal);
    if (closeQrBtn) closeQrBtn.addEventListener('click', closeQrModal);
    if (qrModal) {
      qrModal.addEventListener('click', function (e) {
        if (e.target === qrModal) closeQrModal();
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeQrModal();
    });
  })();
</script>
@endsection
