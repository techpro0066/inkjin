@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Home')

@section('styles')
@include('artist.partials.payment-link-modal-styles')
<style>
  .dash-todo-row{padding:13px 0;border-bottom:1px solid #F3EEF4}
  .dash-todo-row:last-child{border-bottom:0}
  .dash-req-row{padding:12px 22px;border-bottom:1px solid #F3EEF4}
  .dash-req-row:last-child{border-bottom:0}
  .dash-req-row:hover,.dash-booking-row:hover{background:#FBF8FC}
  .dash-qa{padding:14px 16px;gap:10px;text-decoration:none;color:inherit;transition:border-color .15s,background .15s}
  .dash-qa:hover{border-color:#D9C4F2;background:#FBF7FE}
  .dash-qa.disabled{opacity:.45;pointer-events:none}
  .stat-link{text-decoration:none;color:inherit;display:block}
  .stat-link:hover{border-color:#D9C4F2}
  /* Welcome checklist popup (01-home template) */
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
  /* Waitlist modal (no Tailwind on new layout) */
  #waitlistNotifyModal{position:fixed;inset:0;z-index:120;display:none;align-items:center;justify-content:center;padding:16px}
  #waitlistNotifyModal.is-open,#waitlistNotifyModal.flex{display:flex}
  #waitlistNotifyModal.hidden{display:none!important}
  #waitlistNotifyModalBackdrop{position:absolute;inset:0;background:rgba(0,0,0,.45);border:0;cursor:default}
  #waitlistNotifyModal .waitlist-notify-dialog{position:relative;background:#fff;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.2);width:100%;max-width:28rem;overflow:hidden}
  #waitlistNotifyModal .p-6{padding:24px}
  #waitlistNotifyModal .pb-5{padding-bottom:20px}
  #waitlistNotifyModal .px-6{padding-left:24px;padding-right:24px}
  #waitlistNotifyModal .py-4{padding-top:16px;padding-bottom:16px}
  #waitlistNotifyModal .mb-4{margin-bottom:16px}
  #waitlistNotifyModal .mb-2{margin-bottom:8px}
  #waitlistNotifyModal .mt-1{margin-top:4px}
  #waitlistNotifyModal .mt-4{margin-top:16px}
  #waitlistNotifyModal .w-12{width:48px;height:48px}
  #waitlistNotifyModal .rounded-2xl{border-radius:16px}
  #waitlistNotifyModal .rounded-xl{border-radius:12px}
  #waitlistNotifyModal .bg-primary\/10,#waitlistNotifyModal [class*="bg-primary"]{background:#EDEDED}
  #waitlistNotifyModal .text-primary{color:#1A1A1A}
  #waitlistNotifyModal #waitlistNotifyModalIcon{background:#EDEDED}
  #waitlistNotifyModal .text-on-surface{color:#1A1A1A}
  #waitlistNotifyModal .text-on-surface-variant{color:#6F6874}
  #waitlistNotifyModal .text-xl{font-size:20px;font-weight:800}
  #waitlistNotifyModal .text-sm{font-size:14px}
  #waitlistNotifyModal .text-xs{font-size:12px}
  #waitlistNotifyModal .font-bold{font-weight:800}
  #waitlistNotifyModal .font-semibold{font-weight:600}
  #waitlistNotifyModal .leading-relaxed{line-height:1.5}
  #waitlistNotifyModal .bg-green-50{background:#E6F8EE;border:1px solid #B7E2C6;border-radius:12px;padding:12px 16px}
  #waitlistNotifyModal .text-green-900{color:#1F6B4E;font-weight:700;font-size:14px}
  #waitlistNotifyModal .text-green-800{color:#2F6B4E;font-size:12px}
  #waitlistNotifyModal .bg-surface-container-low,#waitlistNotifyModal [class*="bg-surface-container"]{background:#F8F1FB}
  #waitlistNotifyModal [class*="border-outline"]{border:1px solid #E8DFEA}
  #waitlistNotifyModal .border-t{border-top:1px solid #E8DFEA}
  #waitlistNotifyModal .flex{display:flex}
  #waitlistNotifyModal .flex-col-reverse{flex-direction:column-reverse}
  #waitlistNotifyModal .gap-3{gap:12px}
  #waitlistNotifyModal .flex-1{flex:1}
  #waitlistNotifyModal .items-center{align-items:center}
  #waitlistNotifyModal .justify-center{justify-content:center}
  #waitlistNotifyModal .inline-flex{display:inline-flex}
  #waitlistNotifyModal button{font:inherit;cursor:pointer}
  #waitlistNotifyModal #waitlistNotifyModalCancel{padding:10px 16px;border-radius:12px;border:1px solid #E8DFEA;background:#fff;color:#1A1A1A;font-weight:600;font-size:14px}
  #waitlistNotifyModal #waitlistNotifyModalConfirm{padding:10px 16px;border-radius:12px;border:0;background:#1A1A1A!important;color:#fff;font-weight:600;font-size:14px}
  #waitlistNotifyModal .hidden{display:none!important}
  @media (min-width:640px){
    #waitlistNotifyModal .sm\:flex-row{flex-direction:row}
  }
  @media (max-width:1180px){
    .dash-top{grid-template-columns:1fr!important}
    .dash-stats{grid-template-columns:repeat(2,minmax(0,1fr))!important}
    .dash-lists{grid-template-columns:1fr!important}
    .dash-actions{grid-template-columns:repeat(2,minmax(0,1fr))!important}
  }
  @media (max-width:560px){
    .dash-stats,.dash-actions{grid-template-columns:1fr!important}
  }
</style>
@endsection

@section('content')
    @php
  $user = Auth::user();
  $ud = $user?->userDetail;
      $stats = $dashboardStats ?? [];
      $currency = $stats['currency_symbol'] ?? '€';
      $todayTotal = (int) ($stats['today_bookings_total'] ?? 0);
      $todayConfirmed = (int) ($stats['today_bookings_confirmed'] ?? 0);
      $todayPending = (int) ($stats['today_bookings_pending'] ?? 0);
      $monthRevenue = (float) ($stats['month_revenue'] ?? 0);
      $revenueChange = $stats['revenue_change_percent'] ?? null;
      $waitlistCount = (int) ($stats['waitlist_count'] ?? 0);
      $showWaitlistNotifyButton = (bool) ($stats['show_waitlist_notify_button'] ?? false);
      $booksClosed = $ud && ($ud->availability_status ?? '') === 'closed';
  $artistUsername = trim((string) ($ud->user_name ?? ''));
      $bookingPageUrl = $artistUsername !== ''
          ? route('public.artist', ['username' => $artistUsername])
          : '';
  $firstName = trim((string) ($user->first_name ?? ''));
  $canPayLinks = ! empty($canCreatePaymentLinks);
  $pendingRequests = (int) ($pendingCustomRequestsCount ?? 0);
  $todayLabel = now()->format('l M j');

      $todaySubtitle = $todayTotal === 0
          ? 'No bookings scheduled today'
          : collect([
              $todayConfirmed > 0 ? $todayConfirmed.' confirmed' : null,
              $todayPending > 0 ? $todayPending.' pending' : null,
      ])->filter()->implode(' · ');

      if ($revenueChange === null) {
          $revenueSubtitle = $monthRevenue > 0 ? 'First revenue this month' : 'No paid bookings this month';
      $revenueSubtitleHtml = e($revenueSubtitle);
      } elseif ($revenueChange > 0) {
      $revenueSubtitleHtml = '<span style="color:#00A650;font-weight:600">+'.e($revenueChange).'% from last month</span>';
      } elseif ($revenueChange < 0) {
      $revenueSubtitleHtml = '<span style="color:#C62828;font-weight:600">'.e($revenueChange).'% from last month</span>';
      } else {
      $revenueSubtitleHtml = 'Same as last month';
  }

  $todos = [];
  if ($pendingRequests > 0) {
      $todos[] = [
          'icon' => 'edit_note',
          'tone' => 'p',
          'title' => $pendingRequests.' new '.($pendingRequests === 1 ? 'request' : 'requests').' waiting for a reply',
          'sub' => 'Review and send a quote or decline',
          'cta' => 'Reply',
          'url' => route('artist.custom-requests.index'),
      ];
  }
  if (! empty($needsWeeklyAvailabilitySetup)) {
      $todos[] = [
          'icon' => 'event_available',
          'tone' => 'a',
          'title' => 'Set your weekly availability',
          'sub' => 'Until you do, clients can\'t book you',
          'cta' => 'Set hours',
          'url' => route('availability.index'),
      ];
  }
  if ($booksClosed) {
      $todos[] = [
          'icon' => 'event_busy',
          'tone' => 'a',
          'title' => 'Your books are closed',
          'sub' => $waitlistCount > 0
              ? $waitlistCount.' '.($waitlistCount === 1 ? 'client' : 'clients').' on your waitlist'
              : 'Clients can only join your waitlist',
          'cta' => 'Open books',
          'url' => route('availability.index').'?tab=status',
      ];
  }
  if ($showWaitlistNotifyButton) {
      $todos[] = [
          'icon' => 'mail',
          'tone' => 'b',
          'title' => 'Notify your waitlist',
          'sub' => $waitlistCount.' '.($waitlistCount === 1 ? 'client is' : 'clients are').' waiting for a slot',
          'cta' => 'Send email',
          'url' => null,
          'waitlist' => true,
      ];
  }
  if (! empty($showCustomizePageNotice)) {
      $todos[] = [
          'icon' => 'palette',
          'tone' => 'p',
          'title' => 'Customize your booking page',
          'sub' => 'Colors, bio, flash, portfolio, and forms',
          'cta' => 'Customize',
          'url' => route('personal-page.index', ['from' => 'dashboard_notice']),
      ];
  }
  if (! $canPayLinks) {
      $todos[] = [
          'icon' => 'account_balance',
          'tone' => 'a',
          'title' => 'Connect Stripe to take payments',
          'sub' => 'Needed before you can create payment links',
          'cta' => 'Connect',
          'url' => route('settings.payment'),
      ];
  }

  $checklist = $profileChecklist ?? [];
  $profileStepsNeeded = [
      [
          'done' => (bool) ($checklist['stripe'] ?? false),
          'label' => 'Finish payouts: connect to Stripe',
          'url' => route('settings.payment'),
      ],
      [
          'done' => (bool) ($checklist['hours'] ?? false),
          'label' => 'Set your working hours',
          'url' => route('availability.index'),
          'hint' => empty($checklist['hours']) ? 'Clients can\'t book until hours are set.' : null,
      ],
      [
          'done' => (bool) ($checklist['books_open'] ?? false),
          'label' => 'Open your books',
          'url' => route('availability.index').'?tab=status',
          'hint' => empty($checklist['books_open']) ? 'Your books are closed. Clients can only join your waitlist.' : null,
      ],
  ];
  $profileStepsStandout = [
      [
          'done' => (bool) ($checklist['portfolio'] ?? false),
          'label' => 'Add portfolio pieces',
          'url' => route('portfolio.index'),
      ],
      [
          'done' => (bool) ($checklist['flash'] ?? false),
          'label' => 'Add a flash design',
          'url' => route('artist-designs.index'),
      ],
      [
          'done' => (bool) ($checklist['tagline_bio'] ?? false),
          'label' => 'Add a tagline and bio',
          'url' => route('personal-page.index'),
      ],
      [
          'done' => (bool) ($checklist['faq'] ?? false),
          'label' => 'Add your FAQs',
          'url' => route('artist.faq.index'),
      ],
      [
          'done' => (bool) ($checklist['banner'] ?? false),
          'label' => 'Upload a banner',
          'url' => route('personal-page.index'),
      ],
  ];
  $igBioDone = (bool) ($checklist['instagram_bio'] ?? false);
  $igUsername = trim((string) (Auth::user()?->userDetail?->user_name ?? ''));
  $igPageUrl = $igUsername !== '' ? route('public.artist', ['username' => $igUsername]) : '';
  $igPageShort = $igPageUrl !== '' ? preg_replace('#^https?://#', '', $igPageUrl) : '';
  $profileAll = array_merge($profileStepsNeeded, $profileStepsStandout);
  $includeIgBio = $igPageUrl !== '';
  $profileDone = collect($profileAll)->where('done', true)->count() + ($includeIgBio && $igBioDone ? 1 : 0);
  $profileTotal = count($profileAll) + ($includeIgBio ? 1 : 0);
  $profilePct = (int) round(($profileDone / max(1, $profileTotal)) * 100);

  $statusPill = function (?string $key, ?string $label = null): string {
      $key = strtolower((string) $key);
      $label = strtolower((string) $label);
      if (str_contains($key, 'confirm') || str_contains($key, 'book') || str_contains($key, 'paid') || str_contains($key, 'complete') || str_contains($label, 'book')) {
          return 'g';
      }
      if (str_contains($key, 'declin') || str_contains($key, 'cancel') || str_contains($key, 'reject')) {
          return 'r';
      }
      if (str_contains($key, 'quote') || str_contains($label, 'quote')) {
          return 'b';
      }
      if (str_contains($key, 'pend') || str_contains($key, 'new') || str_contains($label, 'new')) {
          return 'p';
      }
      return 'k';
  };
@endphp

  <div class="head">
    <div>
      <h1>
        Welcome back{{ $firstName !== '' ? ', '.$firstName : '' }}
        <a class="help-q" href="https://help.inkjin.com/en/articles/17200789-dashboard-home" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Here's what needs you today, {{ $todayLabel }}.</div>
    </div>
    <div class="btns">
      @if ($bookingPageUrl !== '')
        <a class="btn ghost" href="{{ $bookingPageUrl }}" target="_blank" rel="noopener">
          <span class="ms">open_in_new</span>Open my page
        </a>
      @else
        <a class="btn ghost" href="{{ route('personal-page.index') }}">
          <span class="ms">open_in_new</span>Open my page
        </a>
      @endif
      <button type="button" class="btn" data-open-payment-link>
        <span class="ms">add</span>New payment link
      </button>
    </div>
    </div>

  <div class="grid dash-top" style="grid-template-columns:1.65fr 1fr;margin-top:26px">
    <div class="card">
      <div class="ch">
        <div class="row" style="gap:10px">
          <h3>To do</h3>
          @if (count($todos) > 0)
            <span class="pill nd p">{{ count($todos) }}</span>
          @endif
      </div>
        <span class="faint" style="font-size:12.5px">Items clear when they're done</span>
    </div>
      <div style="padding:4px 22px 6px">
        @forelse ($todos as $todo)
          <div class="row dash-todo-row">
            <div class="ic {{ $todo['tone'] }}"><span class="ms">{{ $todo['icon'] }}</span></div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700;font-size:14px">{{ $todo['title'] }}</div>
              <div class="faint" style="font-size:12.5px;margin-top:2px">{{ $todo['sub'] }}</div>
          </div>
            @if (! empty($todo['waitlist']))
              <button type="button" class="btn sm ghost" id="dashboardWaitlistNotifyBtn" data-pending-count="{{ $waitlistCount }}">{{ $todo['cta'] }}</button>
            @else
              <a class="btn sm ghost" href="{{ $todo['url'] }}">{{ $todo['cta'] }}</a>
            @endif
        </div>
        @empty
          <div class="row" style="padding:22px 0;gap:12px">
            <div class="ic g"><span class="ms">check</span></div>
            <div>
              <div style="font-weight:700;font-size:14px">You're all caught up</div>
              <div class="faint" style="font-size:12.5px;margin-top:2px">No urgent items right now</div>
          </div>
        </div>
        @endforelse
          </div>
        </div>

    <div class="card">
      <div class="ch">
        <h3>Complete your profile</h3>
        <b style="font-size:13px" id="cpcount">{{ $profileDone }} of {{ $profileTotal }}</b>
          </div>
      <div style="padding:14px 22px 16px">
        <div style="height:6px;border-radius:4px;background:#F0EDF2">
          <div id="cpbar" style="width:{{ $profilePct }}%;height:6px;border-radius:4px;background:var(--pri)"></div>
        </div>
        <div class="faint" style="font-size:12px;margin:8px 0 4px">Clients book more when your profile is complete.</div>

        <div style="font-size:11px;font-weight:700;letter-spacing:.6px;color:#B3261E;margin:6px 0 2px">NEEDED TO TAKE BOOKINGS</div>
        @foreach ($profileStepsNeeded as $step)
          @if ($step['done'])
            <div class="row" style="padding:6px 0;gap:10px">
              <span class="ms" style="font-size:20px;color:#00A650;font-variation-settings:'FILL' 1">check_circle</span>
              <span style="font-size:13.5px;color:#9A929E;text-decoration:line-through">{{ $step['label'] }}</span>
            </div>
          @else
            <a href="{{ $step['url'] }}" class="row" style="padding:7px 0;gap:10px;text-decoration:none;color:inherit;cursor:pointer">
              <span class="ms" style="font-size:20px;color:#C8BFCC">radio_button_unchecked</span>
              <span style="flex:1">
                <span style="font-size:13.5px;font-weight:600">{{ $step['label'] }}</span>
                @if (! empty($step['hint']))
                  <span style="display:block;font-size:12px;color:#8A6D1E;margin-top:1px">{{ $step['hint'] }}</span>
                @endif
              </span>
              <span class="ms" style="font-size:18px;color:#9A929E">chevron_right</span>
            </a>
          @endif
        @endforeach

        <div style="font-size:11px;font-weight:700;letter-spacing:.6px;color:#6F6874;margin:12px 0 2px">MAKE YOUR PAGE STAND OUT</div>
        @foreach ($profileStepsStandout as $step)
          @if ($step['done'])
            <div class="row" style="padding:6px 0;gap:10px">
              <span class="ms" style="font-size:20px;color:#00A650;font-variation-settings:'FILL' 1">check_circle</span>
              <span style="font-size:13.5px;color:#9A929E;text-decoration:line-through">{{ $step['label'] }}</span>
          </div>
          @else
            <a href="{{ $step['url'] }}" class="row" style="padding:7px 0;gap:10px;text-decoration:none;color:inherit;cursor:pointer">
              <span class="ms" style="font-size:20px;color:#C8BFCC">radio_button_unchecked</span>
              <span style="flex:1"><span style="font-size:13.5px;font-weight:600">{{ $step['label'] }}</span></span>
              <span class="ms" style="font-size:18px;color:#9A929E">chevron_right</span>
            </a>
          @endif
        @endforeach

        @if ($igPageUrl !== '')
          <div class="row" id="igbio" style="padding:7px 0;gap:10px;align-items:flex-start">
            <span class="ms" id="igbioic" style="font-size:20px;color:{{ $igBioDone ? '#00A650' : '#C8BFCC' }};{{ $igBioDone ? "font-variation-settings:'FILL' 1" : '' }}">{{ $igBioDone ? 'check_circle' : 'radio_button_unchecked' }}</span>
            <span style="flex:1;min-width:0">
              <span id="igbiot" style="display:block;font-size:13.5px;font-weight:{{ $igBioDone ? '400' : '600' }};{{ $igBioDone ? 'color:#9A929E;text-decoration:line-through' : '' }}">Add your page link to your Instagram bio</span>
              @unless ($igBioDone)
                <span class="faint" style="display:block;font-size:12px;margin-top:2px">In Instagram: Edit profile &gt; Links &gt; Add external link. Paste {{ $igPageShort }}</span>
                <span class="row" style="gap:8px;margin-top:8px;flex-wrap:wrap">
                  <button type="button" class="btn sm ghost" id="igbiocopy" style="font:inherit;font-weight:600;cursor:pointer"><span class="ms" style="font-size:16px">link</span>Copy link</button>
                  <button type="button" class="btn sm ghost" id="igbiodone" style="font:inherit;font-weight:600;cursor:pointer"><span class="ms" style="font-size:16px">check</span>I added it</button>
                </span>
              @endunless
            </span>
          </div>
        @endif
        </div>
          </div>
        </div>

  <div class="grid dash-stats" style="grid-template-columns:repeat(4,1fr);margin-top:16px">
    <a class="card pad stat-link" href="{{ route('artist.bookings.index') }}">
      <div class="ic"><span class="ms">calendar_month</span></div>
      <div style="font-size:28px;font-weight:800;margin-top:14px;letter-spacing:-.5px">{{ $todayTotal }}</div>
      <div style="font-weight:700;font-size:13.5px;margin-top:2px">Today's bookings</div>
      <div class="faint" style="font-size:12px;margin-top:3px">{{ $todaySubtitle }}</div>
    </a>
    <a class="card pad stat-link" href="{{ route('artist.custom-requests.index') }}">
      <div class="ic"><span class="ms">inbox</span></div>
      <div style="font-size:28px;font-weight:800;margin-top:14px;letter-spacing:-.5px">{{ $pendingRequests }}</div>
      <div style="font-weight:700;font-size:13.5px;margin-top:2px">Open requests</div>
      <div class="faint" style="font-size:12px;margin-top:3px">Pending review</div>
    </a>
    <a class="card pad stat-link" href="{{ route('artist.payments.index') }}">
      <div class="ic"><span class="ms">payments</span></div>
      <div style="font-size:28px;font-weight:800;margin-top:14px;letter-spacing:-.5px">{{ $currency }}{{ number_format($monthRevenue, 0) }}</div>
      <div style="font-weight:700;font-size:13.5px;margin-top:2px">This month's revenue</div>
      <div class="faint" style="font-size:12px;margin-top:3px">{!! $revenueSubtitleHtml !!}</div>
    </a>
    <div class="card pad">
      <div class="ic"><span class="ms">notifications</span></div>
      <div id="dashboardWaitlistCount" style="font-size:28px;font-weight:800;margin-top:14px;letter-spacing:-.5px">{{ $waitlistCount }}</div>
      <div style="font-weight:700;font-size:13.5px;margin-top:2px">Waitlist</div>
      <div id="dashboardWaitlistSubtitle" class="faint" style="font-size:12px;margin-top:3px">
        {{ $waitlistCount === 1 ? 'Client waiting for a slot' : 'Clients waiting for a slot' }}
      </div>
      @if ($showWaitlistNotifyButton)
        <div id="dashboardWaitlistNotifyWrap" style="margin-top:12px">
          <button type="button" class="btn sm" id="dashboardQuickWaitlistNotifyBtn" data-pending-count="{{ $waitlistCount }}">
            <span class="ms">mail</span>Send email
          </button>
        </div>
        @endif
      </div>
    </div>

  <div class="grid dash-lists" style="grid-template-columns:1fr 1.7fr;margin-top:16px;align-items:start">
    <div class="card">
      <div class="ch">
        <h3>Recent requests</h3>
        <a class="l" href="{{ route('artist.custom-requests.index') }}">View all <span class="ms" style="font-size:16px">arrow_forward</span></a>
      </div>
      @forelse ($recentCustomRequests ?? [] as $customRequest)
        @php
          $name = $customRequest->clientArtistFacingName();
          $initial = strtoupper(substr(preg_replace('/\s+/', '', $name) ?: 'C', 0, 1));
          $statusLabel = $customRequest->filterStatusLabel();
          $pill = $statusPill((string) $customRequest->status, $statusLabel);
        @endphp
        <a class="row dash-req-row" href="{{ route('artist.custom-requests.index') }}?open={{ $customRequest->id }}" style="text-decoration:none;color:inherit">
          <div class="ini" style="background:#EDE6F0;color:#1A1A1A">{{ $initial }}</div>
          <div style="flex:1;min-width:0">
            <div class="row" style="gap:8px;flex-wrap:wrap">
              <b style="font-size:14px">{{ $name }}</b>
              <span class="pill nd k">Custom</span>
              <span class="pill nd {{ $pill }}">{{ $statusLabel }}</span>
            </div>
            <div class="faint" style="font-size:12px;margin-top:3px">{{ $customRequest->referenceLabel() }} · {{ $customRequest->created_at?->format('M j') }}</div>
          </div>
          <span style="font-weight:600;font-size:13px">View</span>
        </a>
      @empty
        <div style="padding:28px 22px;text-align:center">
          <span class="ms" style="font-size:28px;color:#C8BFCC">brush</span>
          <div class="faint" style="margin-top:8px">No custom requests yet.</div>
        </div>
      @endforelse
    </div>

    <div class="card" style="overflow:hidden">
      <div class="ch">
        <h3>Upcoming bookings</h3>
        <a class="l" href="{{ route('artist.bookings.index') }}">View all <span class="ms" style="font-size:16px">arrow_forward</span></a>
      </div>
      @if (count($recentBookings ?? []) > 0)
        <table>
          <tr>
            <th>CLIENT</th>
            <th>SERVICE</th>
            <th>DATE</th>
            <th>STATUS</th>
            </tr>
          @foreach ($recentBookings as $booking)
            @php
              $clientName = (string) ($booking['client_name'] ?? 'Client');
              $initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $clientName) ?: 'CL', 0, 2));
              $pill = $statusPill($booking['status_key'] ?? null, $booking['status'] ?? null);
            @endphp
            <tr class="dash-booking-row">
              <td>
                <div class="row" style="gap:10px">
                  <div class="ini" style="width:28px;height:28px;font-size:10.5px">{{ $initials }}</div>
                  <b style="font-weight:600;white-space:nowrap">{{ $clientName }}</b>
                </div>
              </td>
              <td class="muted" style="white-space:nowrap">{{ $booking['service'] ?? '—' }}</td>
              <td style="white-space:nowrap">
                {{ $booking['date'] ?? '—' }}
                <span class="faint"> · {{ $booking['time'] ?? '—' }}</span>
              </td>
              <td><span class="pill nd {{ $pill }}">{{ $booking['status'] ?? '—' }}</span></td>
            </tr>
            @endforeach
        </table>
      @else
        <div style="padding:28px 22px;text-align:center">
          <span class="ms" style="font-size:28px;color:#C8BFCC">calendar_month</span>
          <div class="faint" style="margin-top:8px">No bookings yet.</div>
        </div>
      @endif
          </div>
          </div>

  <h3 style="margin:28px 0 12px">Quick actions</h3>
  <div class="grid dash-actions" style="grid-template-columns:repeat(5,1fr)">
    <button type="button" class="card row dash-qa" data-open-payment-link style="width:100%;border:1px solid var(--line);background:#fff;font:inherit;cursor:pointer;text-align:left">
      <span class="ms" style="font-size:20px">link</span>
      <b style="font-size:13.5px;font-weight:600">New payment link</b>
        </button>
    <a class="card row dash-qa" href="{{ route('artist-designs.index') }}">
      <span class="ms" style="font-size:20px">add_photo_alternate</span>
      <b style="font-size:13.5px;font-weight:600">Add a design</b>
    </a>
    <a class="card row dash-qa" href="{{ route('guest-spots.index') }}">
      <span class="ms" style="font-size:20px">flight_takeoff</span>
      <b style="font-size:13.5px;font-weight:600">Add guest spot</b>
    </a>
    <a class="card row dash-qa" href="{{ route('availability.index') }}?tab=blocked">
      <span class="ms" style="font-size:20px">event_busy</span>
      <b style="font-size:13.5px;font-weight:600">Block dates</b>
    </a>
    <button type="button" class="card row dash-qa" id="dashboardCopyBookingLinkBtn" data-booking-url="{{ $bookingPageUrl }}" style="width:100%;border:1px solid var(--line);background:#fff;font:inherit;cursor:pointer;text-align:left">
      <span class="ms" style="font-size:20px">share</span>
      <b style="font-size:13.5px;font-weight:600">Share my page</b>
        </button>
    </div>

  <div id="dashboardCopyLinkToast" class="bpw-toast" role="status" aria-live="polite">
    <span class="ms">check_circle</span>
    <span id="dashboardCopyLinkToastMessage">Booking link copied.</span>
</div>

  @if ($showWaitlistNotifyButton ?? false)
  @include('components.waitlist-notify-modal')
@endif

@include('artist.partials.payment-link-modal')
@endsection

@section('scripts')
@include('partials.reddit-pixel', ['event' => 'Active'])
<script>
(function () {
  var showWelcome = @json((bool) ($showWelcomePopup ?? false));
  var forceWelcome = location.hash === '#welcome';
  if (!showWelcome && !forceWelcome) return;

  var firstName = @json(trim((string) (Auth::user()->first_name ?? '')));
  var nopay = @json(! (bool) ($canCreatePaymentLinks ?? false));
  @php
    $wlUsername = trim((string) (Auth::user()?->userDetail?->user_name ?? ''));
    $wlPageUrl = $wlUsername !== '' ? route('public.artist', ['username' => $wlUsername]) : '';
    $wlCheck = $profileChecklist ?? [];
  @endphp
  var pageUrl = @json($wlPageUrl);
  var markUrl = @json(route('artist.welcome-seen'));
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  var NEED = [
    {
      t: 'Finish payouts: connect to Stripe',
      s: 'So clients can pay deposits and you get paid.',
      h: @json(route('settings.payment')),
      done: @json((bool) ($wlCheck['stripe'] ?? false)),
    },
    {
      t: 'Set your working hours',
      s: 'The days and times clients can book you.',
      h: @json(route('availability.index')),
      done: @json((bool) ($wlCheck['hours'] ?? false)),
    },
    {
      t: 'Open your books',
      s: nopay
        ? 'Finish payouts first so clients can pay a deposit.'
        : 'Your books are closed. Clients can only join your waitlist.',
      h: @json(route('availability.index').'?tab=status'),
      done: @json((bool) ($wlCheck['books_open'] ?? false)),
    },
  ];
  var MORE = [
    { t: 'Add portfolio pieces', h: @json(route('portfolio.index')), done: @json((bool) ($wlCheck['portfolio'] ?? false)) },
    { t: 'Add a flash design', h: @json(route('artist-designs.index')), done: @json((bool) ($wlCheck['flash'] ?? false)) },
    { t: 'Add a tagline and bio', h: @json(route('personal-page.index')), done: @json((bool) ($wlCheck['tagline_bio'] ?? false)) },
    { t: 'Add your FAQs', h: @json(route('artist.faq.index')), done: @json((bool) ($wlCheck['faq'] ?? false)) },
    { t: 'Upload a banner', h: @json(route('personal-page.index')), done: @json((bool) ($wlCheck['banner'] ?? false)) },
  ];

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
    var title = 'Welcome to Bookpay' + (firstName ? ', ' + firstName : '');
    var bg = document.createElement('div');
    bg.className = 'wl-bg';
    bg.innerHTML =
      '<div class="wl" role="dialog" aria-modal="true" aria-labelledby="wl-t">'
      + '<button type="button" class="wl-x" aria-label="Close"><span class="ms">close</span></button>'
      + '<div class="wl-h"><div class="wl-ic"><span class="ms">rocket_launch</span></div>'
      + '<h2 id="wl-t">' + esc(title) + '</h2>'
      + '<p>Your page is almost ready. Here\'s what you need to do so clients can find you and book.</p></div>'
      + '<div class="wl-pr"><div class="wl-bar"><i style="width:' + Math.round(done / Math.max(1, all.length) * 100) + '%"></i></div>'
      + done + ' of ' + all.length + ' done</div>'
      + (nopay
        ? '<div class="wl-pay"><span class="ms">account_balance</span><div><b>First, finish payouts</b>'
          + '<span>Connect Stripe so clients can pay deposits and you get paid. Until then you can\'t take bookings or reply to requests with a quote or times.</span>'
          + '<a class="btn" href="' + esc(@json(route('settings.payment'))) + '">Connect Stripe <span class="ms" style="font-size:17px">arrow_forward</span></a></div></div>'
        : '')
      + '<div class="wl-b"><div class="wl-cap" style="color:#B3261E">NEEDED TO TAKE BOOKINGS</div>' + NEED.map(row).join('')
      + '<div class="wl-cap" style="color:#6F6874">MAKE YOUR PAGE STAND OUT</div>' + MORE.map(row).join('') + '</div>'
      + '<div class="wl-help"><span class="ms">support_agent</span><span>Need help? Email us at <a href="mailto:support@inkjin.com">support@inkjin.com</a> or click <b>Get Help</b> in the menu to chat with us.</span></div>'
      + '<div class="wl-f">'
      + (pageUrl ? '<a class="btn ghost" href="' + esc(pageUrl) + '" target="_blank" rel="noopener" style="text-decoration:none">View my page</a>' : '')
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
@if ($showWaitlistNotifyButton ?? false)
<script src="{{ asset('js/waitlist-notify.js') }}?v=6"></script>
<script>
  window.InkjinWaitlistNotify?.init({
    triggers: [
      document.getElementById('dashboardWaitlistNotifyBtn'),
      document.getElementById('dashboardQuickWaitlistNotifyBtn'),
    ].filter(Boolean),
    notifyUrl: @json(route('artist.clients.waitlist.notify')),
    getPendingCount(trigger) {
      return parseInt(trigger?.dataset.pendingCount || '0', 10) || 0;
    },
    onSuccess(data) {
      const pending = Array.isArray(data.waitlist)
        ? data.waitlist.filter((entry) => entry.status_key === 'pending').length
        : 0;

      const countEl = document.getElementById('dashboardWaitlistCount');
      const subtitleEl = document.getElementById('dashboardWaitlistSubtitle');
      const wrapEl = document.getElementById('dashboardWaitlistNotifyWrap');
      const btn = document.getElementById('dashboardWaitlistNotifyBtn');
      const quickBtn = document.getElementById('dashboardQuickWaitlistNotifyBtn');

      if (countEl) countEl.textContent = String(pending);
      if (subtitleEl) {
        subtitleEl.textContent = pending === 1 ? 'Client waiting for a slot' : 'Clients waiting for a slot';
      }
      if (btn) btn.dataset.pendingCount = String(pending);
      if (quickBtn) quickBtn.dataset.pendingCount = String(pending);

      if (data.show_waitlist_notify_button === false) {
        wrapEl?.classList.add('hidden');
        if (wrapEl) wrapEl.style.display = 'none';
        if (btn) btn.style.display = 'none';
        if (quickBtn) quickBtn.style.display = 'none';
      }
    },
  });
</script>
@endif
<script>
  (function () {
    const copyBtn = document.getElementById('dashboardCopyBookingLinkBtn');
    const toast = document.getElementById('dashboardCopyLinkToast');
    const toastMessage = document.getElementById('dashboardCopyLinkToastMessage');
    let toastTimer = null;

    function showCopyToast(message) {
      if (!toast || !toastMessage) return;
      toastMessage.textContent = message;
    toast.classList.add('on');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () {
      toast.classList.remove('on');
      }, 3000);
    }

    copyBtn?.addEventListener('click', function () {
      const url = copyBtn.dataset.bookingUrl || '';
      if (!url) {
        showCopyToast('Set up your username first to share your booking link.');
        return;
      }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url)
        .then(function () { showCopyToast('Booking link copied.'); })
        .catch(function () { showCopyToast('Could not copy link. Please copy it manually.'); });
      return;
    }
        showCopyToast('Could not copy link. Please copy it manually.');
  });
})();
</script>

@include('artist.partials.payment-link-modal-script')
<script>
(function () {
  var copyBtn = document.getElementById('igbiocopy');
  var doneBtn = document.getElementById('igbiodone');
  if (!copyBtn || !doneBtn) return;

  var pageUrl = @json($igPageUrl ?? '');
  var pageShort = @json($igPageShort ?? '');
  var markUrl = @json(route('artist.instagram-bio-added'));
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var profileDone = {{ (int) ($profileDone ?? 0) }};
  var profileTotal = {{ (int) ($profileTotal ?? 9) }};
  var alreadyDone = @json((bool) ($igBioDone ?? false));

  function toast(m) {
    var x = document.createElement('div');
    x.setAttribute('role', 'status');
    x.style.cssText = 'position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:99';
    x.textContent = m;
    document.body.appendChild(x);
    setTimeout(function () { x.remove(); }, 2400);
  }

  function markDoneUi() {
    var i = document.getElementById('igbioic');
    var tt = document.getElementById('igbiot');
    if (!i || !tt) return;
    i.textContent = 'check_circle';
    i.style.color = '#00A650';
    i.style.fontVariationSettings = "'FILL' 1";
    tt.style.color = '#9A929E';
    tt.style.textDecoration = 'line-through';
    tt.style.fontWeight = '400';
    var actions = copyBtn.parentNode;
    var hint = tt.nextElementSibling;
    if (actions) actions.style.display = 'none';
    if (hint && hint.classList.contains('faint')) hint.style.display = 'none';
    if (!alreadyDone) {
      profileDone += 1;
      alreadyDone = true;
    }
    var count = document.getElementById('cpcount');
    var bar = document.getElementById('cpbar');
    if (count) count.textContent = profileDone + ' of ' + profileTotal;
    if (bar) bar.style.width = Math.round(profileDone / Math.max(1, profileTotal) * 100) + '%';
  }

  copyBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    if (!pageUrl) return;
    try { navigator.clipboard.writeText(pageUrl).catch(function () {}); } catch (x) {}
    toast('Link copied: ' + pageShort);
  });

  doneBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    markDoneUi();
    toast('Nice. Clients can now find your page from Instagram');
    fetch(markUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
      },
    }).catch(function () {});
    });
  })();
</script>
<script>window.BP_LIVE_UI = true; document.body.setAttribute('data-page', 'artist-home');</script>
<script src="{{ asset('new-ui-design-assets/gsb.js') }}"></script>
<script src="{{ asset('new-ui-design-assets/feedback-card.js') }}"></script>
@endsection
