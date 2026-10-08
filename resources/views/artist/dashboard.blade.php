@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Home')

@section('styles')
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
  /* Payment link popup — matches 01-home template (#m-pl) */
  #m-pl{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:300;padding:24px}
  #m-pl[hidden],#m-pl [hidden]{display:none!important}
  #m-pl .pl-pb{background:#fff;border-radius:18px;width:560px;max-width:100%;max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);font-size:13.5px;color:#1A1A1A}
  #m-pl .pl-ph{display:flex;gap:12px;align-items:flex-start;padding:20px 24px 14px;border-bottom:1px solid #EFE8F1;flex-shrink:0}
  #m-pl .pl-ph h2{font-size:19px;font-weight:800;margin:0}
  #m-pl .pl-ph .pl-ic{width:40px;height:40px;border-radius:12px;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;flex:none}
  #m-pl .pl-ph .pl-ic .ms{font-size:22px}
  #m-pl .pl-ph .pl-sub{color:#6F6874;font-size:13.5px;margin-top:4px;line-height:1.4}
  #m-pl .pl-px{margin-left:auto;border:0;background:none;cursor:pointer;color:#8F8A95;padding:2px;display:flex;align-items:center;justify-content:center}
  #m-pl .pl-px:hover{color:#1A1A1A}
  #m-pl .pl-pbd{padding:18px 24px;overflow:auto;flex:1;min-height:0}
  #m-pl .pl-pf{display:flex;gap:10px;justify-content:flex-end;padding:14px 24px;border-top:1px solid #EFE8F1;flex-shrink:0}
  #m-pl .pl-pbtn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;border:0;cursor:pointer;white-space:nowrap;text-decoration:none}
  #m-pl .pl-pbtn.pl-gh{background:#fff;color:#1A1A1A;border:1px solid #E6DEE9}
  #m-pl .pl-pbtn:disabled{opacity:.45;cursor:not-allowed}
  #m-pl .pl-pbtn .ms{font-size:18px}
  #m-pl .pl-lb{display:block;font-size:13px;font-weight:700;margin:16px 0 6px}
  #m-pl #pl-form>.pl-lb:first-of-type{margin-top:0}
  #m-pl .pl-lb i{color:#C62828;font-style:normal}
  #m-pl .pl-fi{display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #E6DEE9;border-radius:10px;padding:0 13px;min-height:42px}
  #m-pl .pl-fi:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  #m-pl .pl-fi input{border:0;outline:0;font:inherit;font-size:14px;flex:1;min-width:0;background:none;padding:10px 0;color:#1A1A1A}
  #m-pl .pl-fi .pl-cur{color:#8F8A95;font-weight:600}
  #m-pl .pl-fi.pl-bad{border-color:#C62828}
  #m-pl .pl-fi.pl-dt{cursor:pointer;justify-content:space-between}
  #m-pl .pl-fi.pl-dt .pl-ph0{color:#8F8A95;flex:1}
  #m-pl .pl-fi .ms{color:#8F8A95;font-size:19px}
  #m-pl .pl-seg{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
  #m-pl .pl-seg button{font:inherit;text-align:center;padding:10px;border-radius:12px;font-weight:600;border:1px solid #E8DFEA;background:#fff;cursor:pointer;display:flex;gap:6px;align-items:center;justify-content:center;color:#1A1A1A}
  #m-pl .pl-seg button .ms{font-size:18px}
  #m-pl .pl-seg button.pl-on{background:#F3E8FF;border-color:#3E007C;color:#3E007C}
  #m-pl .pl-chips{display:flex;flex-wrap:wrap;gap:8px}
  #m-pl .pl-chips button{font:inherit;font-size:13px;font-weight:600;padding:7px 13px;border-radius:20px;border:1px solid #E8DFEA;background:#fff;cursor:pointer;color:#1A1A1A}
  #m-pl .pl-chips button.pl-on{background:#F3E8FF;border-color:#3E007C;color:#3E007C}
  #m-pl .pl-note{background:#F7F3F9;border-radius:10px;padding:9px 12px;font-size:12.5px;color:#4A4450;margin-top:8px;display:flex;gap:8px;align-items:flex-start;line-height:1.45}
  #m-pl .pl-note .ms{font-size:17px;color:#6A2BB8;flex:none}
  #m-pl .pl-err{color:#C62828;font-size:12.5px;margin-top:6px}
  #m-pl .pl-g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start}
  #m-pl .pl-sum{border:1px solid #EFE8F1;border-radius:12px;margin-top:16px;padding:10px 14px}
  #m-pl .pl-sum .pl-r{display:flex;justify-content:space-between;padding:4px 0}
  #m-pl .pl-sum .pl-r b{font-weight:700}
  #m-pl .pl-sum .pl-hint{color:#8F8A95;font-size:12.5px;text-align:center;margin-top:4px}
  #m-pl .pl-warn{background:#FBF3E2;border-radius:10px;padding:10px 12px;font-size:12.5px;color:#6B5320;line-height:1.45;margin-bottom:4px}
  #m-pl .pl-warn a{color:#3E007C;font-weight:700;text-decoration:none}
  #m-pl .pl-cal{border:1px solid #E6DEE9;border-radius:12px;margin-top:8px;padding:12px}
  #m-pl .pl-calh{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;color:#6F6874;margin-bottom:8px}
  #m-pl .pl-calh a{color:#3E007C;font-weight:600;cursor:pointer}
  #m-pl .pl-mh{display:flex;align-items:center;justify-content:space-between;margin:2px 0 6px}
  #m-pl .pl-mh b{font-size:14px}
  #m-pl .pl-mh button{width:34px;height:34px;border:0;background:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#1A1A1A}
  #m-pl .pl-mh button:hover{background:#F7F3F9}
  #m-pl .pl-mh button:disabled{opacity:.3;cursor:default;background:none}
  #m-pl .pl-mg{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
  #m-pl .pl-mg i{font-style:normal;font-size:11px;font-weight:700;color:#8F8A95;text-align:center;padding:3px 0}
  #m-pl .pl-mg button{height:36px;border:0;background:none;border-radius:9px;font:inherit;font-size:13.5px;font-weight:700;cursor:pointer;color:#1A1A1A}
  #m-pl .pl-mg button:hover{background:#F3E8FF}
  #m-pl .pl-mg button:disabled{font-weight:400;color:#CFC8D3;cursor:default;background:none}
  #m-pl .pl-mg button.pl-out{visibility:hidden}
  #m-pl .pl-mg button.pl-td{box-shadow:inset 0 0 0 1.5px #3E007C}
  #m-pl .pl-mg button.pl-on{background:#3E007C;color:#fff}
  #m-pl .pl-tlab{font-size:12.5px;font-weight:700;margin:12px 0 0}
  #m-pl .pl-times{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-top:10px}
  #m-pl .pl-times button{font:inherit;font-size:12.5px;font-weight:600;padding:8px 0;border-radius:8px;border:1px solid #E8DFEA;background:#fff;cursor:pointer;color:#1A1A1A}
  #m-pl .pl-times button:disabled{background:#F4F1F5;color:#B8B0BC;text-decoration:line-through;cursor:default;border-color:#F4F1F5}
  #m-pl .pl-times button.pl-on{background:#3E007C;color:#fff;border-color:#3E007C}
  #m-pl .pl-closed{font-size:12.5px;color:#8F8A95;padding:10px 0;text-align:center}
  #m-pl .pl-qr{display:flex;justify-content:center;padding:16px;background:#fff;border:1px solid #EFE8F1;border-radius:16px;width:max-content;margin:4px auto 12px}
  #m-pl .pl-qr img,#m-pl .pl-qr svg{width:168px;height:168px;display:block}
  #m-pl .pl-lnk{display:flex;gap:8px;align-items:center}
  #m-pl .pl-lnk .pl-fi{flex:1;min-width:0}
  #m-pl .pl-lnk .pl-fi span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13.5px;color:#1A1A1A}
  @media (max-width:560px){
    #m-pl{padding:0;align-items:flex-end}
    #m-pl .pl-pb{width:100%;max-height:92vh;border-radius:18px 18px 0 0}
    #m-pl .pl-g2{grid-template-columns:1fr}
    #m-pl .pl-pf .pl-pbtn{flex:1;justify-content:center}
    #m-pl .pl-times{grid-template-columns:repeat(4,1fr)}
  }
  #dashboardWaitlistNotifyMessage{margin-top:10px;font-size:12px;border-radius:10px;padding:8px 10px;display:none}
  #dashboardWaitlistNotifyMessage.on{display:block}
  #dashboardWaitlistNotifyMessage.ok{background:#E6F8EE;color:#1F6B4E}
  #dashboardWaitlistNotifyMessage.bad{background:#FDECEC;color:#C62828}
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
  #waitlistNotifyModal .bg-primary\/10,#waitlistNotifyModal [class*="bg-primary"]{background:#F3E8FF}
  #waitlistNotifyModal .text-primary{color:#3E007C}
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
  #waitlistNotifyModal #waitlistNotifyModalConfirm{padding:10px 16px;border-radius:12px;border:0;background:#1A1A1A;color:#fff;font-weight:600;font-size:14px}
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
        <p id="dashboardWaitlistNotifyMessage" role="status"></p>
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

  @php
    $currencySymbol = $currency ?? '€';
    $isAuto = ! empty($isAutoScheduling);
  @endphp

  <div id="m-pl" hidden role="dialog" aria-modal="true" aria-labelledby="pl-t">
    <div class="pl-pb">
      <div class="pl-ph">
        <div class="pl-ic"><span class="ms" id="pl-ic">link</span></div>
        <div>
          <h2 id="pl-t">New payment link</h2>
          <div class="pl-sub" id="pl-sub">Takes the payment and creates the booking once the client pays.</div>
        </div>
        <button type="button" class="pl-px" data-plx aria-label="Close"><span class="ms">close</span></button>
    </div>

      <div class="pl-pbd" id="pl-form">
        @if (! $canPayLinks)
          <div class="pl-warn">
            {{ $paymentLinksBlockedMessage ?? 'Complete payout setup in Payment settings before creating payment links.' }}
            <div style="margin-top:6px"><a href="{{ route('settings.payment') }}">Go to Payment settings</a></div>
          </div>
        @endif

        <span class="pl-lb">Amount <i>*</i></span>
        <div class="pl-fi" id="pl-amtf">
          <span class="pl-cur">{{ $currencySymbol }}</span>
          <input id="pl-amt" type="text" inputmode="decimal" placeholder="0" aria-label="Amount" @disabled(! $canPayLinks)>
          </div>
        <div class="pl-err" id="pl-e-amt" hidden></div>

        <div class="pl-seg" id="pl-type">
          <button type="button" data-v="dep" class="pl-on" @disabled(! $canPayLinks)>Deposit</button>
          <button type="button" data-v="full" @disabled(! $canPayLinks)>Full payment</button>
          </div>
        <div class="pl-err" id="pl-e-payment_type" hidden></div>

        <span class="pl-lb">Title <i>*</i></span>
        <div class="pl-fi" id="pl-titlef">
          <input id="pl-title" type="text" placeholder="e.g. Deposit — peony, forearm" aria-label="Title" @disabled(! $canPayLinks)>
          </div>
        <div class="pl-err" id="pl-e-title" hidden></div>

        @if ($isAuto)
          <div class="pl-note">
            <span class="ms">event_available</span>
            <span>Your client picks a time from your open slots.</span>
          </div>
            @else
          <div id="pl-datew">
            <span class="pl-lb">Date and time <i>*</i></span>
            <div class="pl-fi pl-dt" id="pl-date" tabindex="0" role="button" @if(! $canPayLinks) aria-disabled="true" @endif>
              <span class="pl-ph0" id="pl-datet">Select date and time</span>
              <span class="ms">calendar_month</span>
            </div>
            <div class="pl-err" id="pl-e-date" hidden></div>
            <div class="pl-cal" id="pl-cal" hidden></div>
          </div>
            @endif

        <span class="pl-lb">Session duration <i>*</i></span>
        <div class="pl-chips" id="pl-dur">
          <button type="button" data-v="2h" @disabled(! $canPayLinks)>2h</button>
          <button type="button" data-v="3h" class="pl-on" @disabled(! $canPayLinks)>3h</button>
          <button type="button" data-v="4h" @disabled(! $canPayLinks)>4h</button>
          <button type="button" data-v="half-day" @disabled(! $canPayLinks)>Half day</button>
          <button type="button" data-v="full-day" @disabled(! $canPayLinks)>Full day</button>
        </div>
        <div class="pl-err" id="pl-e-dur" hidden></div>

        <div class="pl-g2">
          <div id="pl-totw">
            <span class="pl-lb">Total price <i>*</i></span>
            <div class="pl-fi" id="pl-totf">
              <span class="pl-cur">{{ $currencySymbol }}</span>
              <input id="pl-tot" type="text" inputmode="decimal" placeholder="0" aria-label="Total price" @disabled(! $canPayLinks)>
            </div>
            <div class="pl-err" id="pl-e-tot" hidden></div>
          </div>
          <div>
            <span class="pl-lb">Link expires <i>*</i></span>
            <div class="pl-chips" id="pl-exp">
              <button type="button" data-v="2 days" @disabled(! $canPayLinks)>2 days</button>
              <button type="button" data-v="3 days" @disabled(! $canPayLinks)>3 days</button>
              <button type="button" data-v="7 days" class="pl-on" @disabled(! $canPayLinks)>7 days</button>
            </div>
            <div class="pl-err" id="pl-e-exp" hidden></div>
      </div>
    </div>

        <div class="pl-sum" id="pl-sum" hidden></div>
        <div class="pl-err" id="pl-e-form" hidden style="margin-top:12px"></div>
  </div>

      <div class="pl-pbd" id="pl-done" hidden>
        <div class="pl-qr"><img id="pl-qr" alt="QR code" width="168" height="168"></div>
        <div style="text-align:center;font-size:13.5px;margin-bottom:14px" id="pl-dtxt">Send the link to the client, or show the QR code.</div>
        <span class="pl-lb">Link</span>
        <div class="pl-lnk">
          <div class="pl-fi"><span id="pl-url"></span></div>
          <button type="button" class="pl-pbtn pl-gh" data-plcopy><span class="ms">content_copy</span>Copy</button>
        </div>
        <div class="pl-note" id="pl-dinfo" style="margin-top:14px">
          <span class="ms">info</span>
          <span>The booking is created automatically once the client pays. You’ll find this link under Bookings &gt; Payment links.</span>
  </div>
</div>

      <div class="pl-pf" id="pl-ff">
        <button type="button" class="pl-pbtn pl-gh" data-plx>Cancel</button>
        <button type="button" class="pl-pbtn" id="pl-go" @disabled(! $canPayLinks)>
          <span class="ms">link</span><span id="pl-got">Generate link</span>
        </button>
      </div>
      <div class="pl-pf" id="pl-df" hidden>
        <button type="button" class="pl-pbtn pl-gh" data-plcopy><span class="ms">content_copy</span>Copy link</button>
        <button type="button" class="pl-pbtn" data-pldone>Done</button>
      </div>
    </div>
  </div>
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
<script src="{{ asset('js/waitlist-notify.js') }}?v=2"></script>
<script>
  window.InkjinWaitlistNotify?.init({
    triggers: [
      document.getElementById('dashboardWaitlistNotifyBtn'),
      document.getElementById('dashboardQuickWaitlistNotifyBtn'),
    ].filter(Boolean),
    notifyUrl: @json(route('artist.clients.waitlist.notify')),
    messageEl: document.getElementById('dashboardWaitlistNotifyMessage'),
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
      const msg = document.getElementById('dashboardWaitlistNotifyMessage');

      if (countEl) countEl.textContent = String(pending);
      if (subtitleEl) {
        subtitleEl.textContent = pending === 1 ? 'Client waiting for a slot' : 'Clients waiting for a slot';
      }
      if (btn) btn.dataset.pendingCount = String(pending);
      if (quickBtn) quickBtn.dataset.pendingCount = String(pending);
      if (msg) {
        msg.classList.add('on', 'ok');
        msg.classList.remove('bad');
      }

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

(function () {
  const modal = document.getElementById('m-pl');
  if (!modal) return;

  const canPayLinks = @json((bool) ($canCreatePaymentLinks ?? false));
  const isAuto = @json((bool) ($isAutoScheduling ?? false));
  const validateUrl = @json(route('artist.payment-link.validate'));
  const storeUrl = @json(route('artist.payment-link.store'));
  const cur = @json(($dashboardStats['currency_symbol'] ?? '€'));

  const totalWrap = document.getElementById('pl-totw');
  const sumEl = document.getElementById('pl-sum');
  const totField = document.getElementById('pl-totf');
  const amtField = document.getElementById('pl-amtf');
  const titleField = document.getElementById('pl-titlef');
  const calEl = document.getElementById('pl-cal');
  const dateBtn = document.getElementById('pl-date');
  const dateText = document.getElementById('pl-datet');
  const dateErr = document.getElementById('pl-e-date');
  const amtInput = document.getElementById('pl-amt');
  const totInput = document.getElementById('pl-tot');
  const titleInput = document.getElementById('pl-title');
  const goBtn = document.getElementById('pl-go');
  const formView = document.getElementById('pl-form');
  const doneView = document.getElementById('pl-done');
  const formFooter = document.getElementById('pl-ff');
  const doneFooter = document.getElementById('pl-df');
  const headTitle = document.getElementById('pl-t');
  const headSub = document.getElementById('pl-sub');
  const headIcon = document.getElementById('pl-ic');
  const urlEl = document.getElementById('pl-url');
  const qrEl = document.getElementById('pl-qr');
  const dinfoEl = document.getElementById('pl-dinfo');

  const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  const MN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const HRS = [];
  for (var _h = 600; _h <= 1140; _h += 30) HRS.push(_h);

  var now = new Date();
  var TODAY = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  var S = {};
  var bad = false;
  var busy = false;
  var lastLinkUrl = '';

  const errMap = {
    amount: { el: 'pl-e-amt', field: amtField },
    payment_type: { el: 'pl-e-payment_type' },
    title: { el: 'pl-e-title', field: titleField },
    date_time: { el: 'pl-e-date', field: dateBtn },
    session_duration: { el: 'pl-e-dur' },
    total_price: { el: 'pl-e-tot', field: totField },
    expires: { el: 'pl-e-exp' },
  };

  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function segVal(id) {
    var b = document.querySelector('#' + id + ' button.pl-on');
    return b ? (b.getAttribute('data-v') || '') : '';
  }

  function setSeg(id, v) {
    var w = document.getElementById(id);
    if (!w) return;
    w.querySelectorAll('button').forEach(function (b) {
      b.classList.toggle('pl-on', (b.getAttribute('data-v') || '') === v);
    });
  }

  function selectInGroup(group, btn) {
    if (!group || !btn || btn.disabled) return;
    group.querySelectorAll('button').forEach(function (b) {
      b.classList.toggle('pl-on', b === btn);
    });
  }

  function isFull() {
    return segVal('pl-type') === 'full';
  }

  function num(v) {
    var s = String(v || '').replace(/[^0-9.,]/g, '').replace(/,(?=\d{3}\b)/g, '').replace(',', '.');
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  }

  function money(n) {
    n = Number(n) || 0;
    return cur + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function dd(d) {
    return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2);
  }

  function hm(m) {
    return ('0' + Math.floor(m / 60)).slice(-2) + ':' + ('0' + (m % 60)).slice(-2);
  }

  function range() {
    var mx = new Date(TODAY);
    mx.setMonth(mx.getMonth() + 12);
    return [new Date(TODAY), mx];
  }

  function dayOk(d) {
    var r = range();
    return d >= r[0] && d <= r[1];
  }

  function dateTimeValue() {
    if (!S.day || S.hour == null) return '';
    var d = S.day;
    var y = d.getFullYear();
    var m = ('0' + (d.getMonth() + 1)).slice(-2);
    var day = ('0' + d.getDate()).slice(-2);
    return y + '-' + m + '-' + day + ' ' + hm(S.hour);
  }

  function setDate(d, h) {
    S.day = d;
    S.hour = h;
    if (!dateText) return;
    if (d) {
      dateText.textContent = DOW[d.getDay()] + ', ' + dd(d) + '/' + d.getFullYear() + ' · ' + hm(h);
      dateText.className = '';
    } else {
      dateText.textContent = 'Select date and time';
      dateText.className = 'pl-ph0';
    }
  }

  function resetDate() {
    var kept = { mode: S.mode };
    S = kept;
    setDate(null);
  }

  function drawCal() {
    if (!calEl) return;
    var r = range();
    if (!S.pday) S.pday = new Date(TODAY);
    if (!S.pmon) {
      var bb = S.pday || S.day || r[0];
      S.pmon = new Date(bb.getFullYear(), bb.getMonth(), 1);
    }

    var y = S.pmon.getFullYear();
    var mo = S.pmon.getMonth();
    var first = new Date(y, mo, 1);
    var off = (first.getDay() + 6) % 7;
    var start = new Date(y, mo, 1 - off);
    var prevOk = new Date(y, mo, 0) >= r[0];
    var nextOk = new Date(y, mo + 1, 1) <= r[1];

    var h = '<div class="pl-calh"><span>Pick a date and time</span><a data-nodate>No date yet</a></div>'
      + '<div class="pl-mh"><button type="button" data-mnav="-1" aria-label="Previous month"' + (prevOk ? '' : ' disabled') + '><span class="ms">chevron_left</span></button><b>' + MN[mo] + ' ' + y + '</b><button type="button" data-mnav="1" aria-label="Next month"' + (nextOk ? '' : ' disabled') + '><span class="ms">chevron_right</span></button></div>'
      + '<div class="pl-mg">' + ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'].map(function (x) { return '<i>' + x + '</i>'; }).join('');

    for (var i = 0; i < 42; i++) {
      var d = new Date(start);
      d.setDate(start.getDate() + i);
      var inm = d.getMonth() === mo;
      var ok = inm && dayOk(d);
      h += '<button type="button" data-iso="' + d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate() + '"'
        + (ok ? '' : ' disabled')
        + ' class="' + (inm ? '' : 'pl-out ') + (+d === +TODAY ? 'pl-td ' : '') + (S.pday && +d === +S.pday ? 'pl-on' : '') + '">'
        + d.getDate() + '</button>';
    }
    h += '</div>';

    if (S.pday) {
      var sel = S.pday;
      h += '<div class="pl-tlab">' + DOW[sel.getDay()] + ' ' + sel.getDate() + ' ' + MN[sel.getMonth()].slice(0, 3) + '</div>'
        + '<div class="pl-times">' + HRS.map(function (x) {
          var on = S.day && +S.day === +sel && S.hour === x;
          return '<button type="button" data-h="' + x + '"' + (on ? ' class="pl-on"' : '') + '>' + hm(x) + '</button>';
        }).join('') + '</div>';
    } else {
      h += '<div class="pl-tlab" style="color:#8F8A95;font-weight:500">Pick a day to see times.</div>';
    }

    calEl.innerHTML = h;
  }

  function syncSum() {
    if (!sumEl) return;
    var full = isFull();
    var amt = num(amtInput?.value);
    var tot = num(totInput?.value);
    bad = false;

    if (full) {
      if (amt) {
        sumEl.hidden = false;
        sumEl.innerHTML =
          '<div class="pl-r"><span>Client pays now</span><b>' + money(amt) + '</b></div>' +
          '<div class="pl-r"><span>Balance due at the session</span><b>' + money(0) + '</b></div>';
      } else {
        sumEl.hidden = false;
        sumEl.innerHTML = '<div class="pl-hint">The client pays the full amount now.</div>';
      }
    } else if (amt && tot) {
      sumEl.hidden = false;
      if (amt >= tot) {
        sumEl.innerHTML = '<div class="pl-r" style="color:#C62828"><span>Total price must be greater than the amount.</span></div>';
        bad = true;
      } else {
        sumEl.innerHTML =
          '<div class="pl-r"><span>Client pays now</span><b>' + money(amt) + '</b></div>' +
          '<div class="pl-r"><span>Balance due at the session</span><b>' + money(tot - amt) + '</b></div>';
      }
    } else {
      sumEl.hidden = false;
      sumEl.innerHTML = '<div class="pl-hint">The balance is worked out once you enter both amounts.</div>';
    }

    totField?.classList.toggle('pl-bad', !!bad);
  }

  function clearErrors() {
    Object.keys(errMap).forEach(function (key) {
      var cfg = errMap[key];
      var el = document.getElementById(cfg.el);
      if (el) { el.hidden = true; el.textContent = ''; }
      if (cfg.field) cfg.field.classList.remove('pl-bad');
    });
    var formErr = document.getElementById('pl-e-form');
    if (formErr) { formErr.hidden = true; formErr.textContent = ''; }
  }

  function showErrors(errors) {
    clearErrors();
    if (!errors) return;
    var first = null;
    Object.keys(errMap).forEach(function (key) {
      if (!errors[key]) return;
      var msg = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
      var cfg = errMap[key];
      var el = document.getElementById(cfg.el);
      if (el) {
        el.textContent = msg || '';
        el.hidden = !msg;
        if (!first) first = el;
      }
      if (cfg.field) cfg.field.classList.add('pl-bad');
    });
    if (first && typeof first.scrollIntoView === 'function') {
      first.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
  }

  function buildFormData() {
    var fd = new FormData();
    fd.append('amount', (amtInput?.value || '').trim());
    fd.append('payment_type', isFull() ? 'full' : 'deposit');
    fd.append('title', (titleInput?.value || '').trim());
    fd.append('session_duration', segVal('pl-dur') || '3h');
    fd.append('expires', segVal('pl-exp') || '7 days');
    if (!isFull()) fd.append('total_price', (totInput?.value || '').trim());
    if (!isAuto) {
      var dt = dateTimeValue();
      if (dt) fd.append('date_time', dt);
    }
    return fd;
  }

  function setHead(title, sub, icon) {
    if (headTitle) headTitle.textContent = title;
    if (headSub) {
      headSub.textContent = sub || '';
      headSub.hidden = !sub;
    }
    if (headIcon) headIcon.textContent = icon || 'link';
  }

  function showStep(step) {
    if (formView) formView.hidden = step !== 'form';
    if (doneView) doneView.hidden = step !== 'done';
    if (formFooter) formFooter.hidden = step !== 'form';
    if (doneFooter) doneFooter.hidden = step !== 'done';
    var body = modal.querySelector('.pl-pbd:not([hidden])');
    if (body) body.scrollTop = 0;
  }

  function resetForm() {
    if (amtInput) amtInput.value = '';
    if (totInput) totInput.value = '';
    if (titleInput) titleInput.value = '';
    setSeg('pl-type', 'dep');
    setSeg('pl-dur', '3h');
    setSeg('pl-exp', '7 days');
    if (totalWrap) totalWrap.hidden = false;
    resetDate();
    if (calEl) calEl.hidden = true;
    clearErrors();
    lastLinkUrl = '';
    syncSum();
  }

  function openModal() {
    resetForm();
    setHead('New payment link', 'Takes the payment and creates the booking once the client pays.', 'link');
    showStep('form');
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    setTimeout(function () {
      try { amtInput?.focus(); } catch (e) {}
    }, 30);
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
    if (calEl) calEl.hidden = true;
  }

  function setLoading(loading) {
    if (!goBtn) return;
    goBtn.disabled = loading || !canPayLinks;
    var label = document.getElementById('pl-got');
    if (label) label.textContent = loading ? 'Generating…' : 'Generate link';
  }

  function showDone(link) {
    lastLinkUrl = link.url || '';
    if (urlEl) urlEl.textContent = link.display_url || link.url || '';
    if (qrEl && link.url) {
      qrEl.src = 'https://api.qrserver.com/v1/create-qr-code/?size=168x168&data=' + encodeURIComponent(link.url);
      qrEl.alt = 'QR code';
    }
    var dtxt = document.getElementById('pl-dtxt');
    if (dtxt) dtxt.textContent = 'Send the link to the client, or show the QR code.';
    if (dinfoEl) {
      dinfoEl.hidden = false;
      var infoText = dinfoEl.querySelector('span:last-child');
      if (infoText) {
        infoText.textContent = 'The booking is created automatically once the client pays. You’ll find this link under Bookings > Payment links.';
      }
    }
    var kind = isFull() ? 'full payment' : 'deposit';
    setHead('Payment link ready', (link.amount_formatted || '') + ' ' + kind, 'check_circle');
    showStep('done');
  }

  function copyLink(btn) {
    if (!lastLinkUrl) return;
    var done = function () {
      if (!btn) return;
      if (!btn.getAttribute('data-label')) {
        btn.setAttribute('data-label', btn.textContent.replace(/content_copy|check/g, '').trim() || 'Copy');
      }
      var ms = btn.querySelector('.ms');
      if (ms) ms.textContent = 'check';
      Array.prototype.slice.call(btn.childNodes).forEach(function (n) {
        if (n.nodeType === 3) n.textContent = ' Copied';
      });
      setTimeout(function () {
        if (ms) ms.textContent = 'content_copy';
        Array.prototype.slice.call(btn.childNodes).forEach(function (n) {
          if (n.nodeType === 3) n.textContent = ' ' + (btn.getAttribute('data-label') || 'Copy');
        });
      }, 1500);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(lastLinkUrl).then(done).catch(function () {});
      return;
    }
    var temp = document.createElement('textarea');
    temp.value = lastLinkUrl;
    document.body.appendChild(temp);
    temp.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(temp);
    done();
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf(),
      },
      body: body,
    }).then(function (response) {
      return response.json().then(function (data) {
        return { status: response.status, data: data };
      }).catch(function () {
        return { status: response.status, data: {} };
      });
    });
  }

  document.querySelectorAll('[data-open-payment-link]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      openModal();
    });
  });

  modal.querySelectorAll('[data-plx]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  modal.querySelectorAll('[data-pldone]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  modal.querySelectorAll('[data-plcopy]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      copyLink(el);
    });
  });

  modal.addEventListener('click', function (e) {
    if (e.target === modal) {
      closeModal();
      return;
    }

    if (e.target.closest('#pl-date')) {
      if (!canPayLinks || !calEl) return;
      clearErrors();
      calEl.hidden = !calEl.hidden;
      if (!calEl.hidden) {
        if (S.day) {
          S.pday = S.day;
          S.pmon = new Date(S.day.getFullYear(), S.day.getMonth(), 1);
        }
        drawCal();
      }
      return;
    }

    var mn = e.target.closest('[data-mnav]');
    if (mn && !mn.disabled) {
      S.pmon = new Date(S.pmon.getFullYear(), S.pmon.getMonth() + +mn.getAttribute('data-mnav'), 1);
      drawCal();
      return;
    }

    var dayBtnEl = e.target.closest('.pl-mg button');
    if (dayBtnEl && !dayBtnEl.disabled) {
      var q = dayBtnEl.getAttribute('data-iso').split('-');
      S.pday = new Date(+q[0], +q[1] - 1, +q[2]);
      drawCal();
      return;
    }

    var timeBtn = e.target.closest('.pl-times button');
    if (timeBtn && !timeBtn.disabled) {
      setDate(S.pday, +timeBtn.getAttribute('data-h'));
      if (calEl) calEl.hidden = true;
      if (dateErr) { dateErr.hidden = true; dateErr.textContent = ''; }
      dateBtn?.classList.remove('pl-bad');
      return;
    }

    if (e.target.closest('[data-nodate]')) {
      setDate(null);
      if (calEl) calEl.hidden = true;
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
    if (e.key === 'Enter' && e.target && e.target.id === 'pl-date') {
      e.preventDefault();
      dateBtn?.click();
    }
  });

  document.getElementById('pl-type')?.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-v]');
    if (!btn) return;
    selectInGroup(this, btn);
    if (totalWrap) totalWrap.hidden = btn.getAttribute('data-v') !== 'dep';
    syncSum();
  });

  document.getElementById('pl-dur')?.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-v]');
    if (!btn) return;
    selectInGroup(this, btn);
    var err = document.getElementById('pl-e-dur');
    if (err) { err.hidden = true; err.textContent = ''; }
  });

  document.getElementById('pl-exp')?.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-v]');
    if (!btn) return;
    selectInGroup(this, btn);
  });

  amtInput?.addEventListener('input', function () {
    amtField?.classList.remove('pl-bad');
    var err = document.getElementById('pl-e-amt');
    if (err) { err.hidden = true; err.textContent = ''; }
    syncSum();
  });
  totInput?.addEventListener('input', function () {
    totField?.classList.remove('pl-bad');
    var err = document.getElementById('pl-e-tot');
    if (err) { err.hidden = true; err.textContent = ''; }
    syncSum();
  });
  titleInput?.addEventListener('input', function () {
    titleField?.classList.remove('pl-bad');
    var err = document.getElementById('pl-e-title');
    if (err) { err.hidden = true; err.textContent = ''; }
  });

  goBtn?.addEventListener('click', function (e) {
    e.preventDefault();
    if (!canPayLinks || busy) return;
    clearErrors();
    syncSum();
    if (bad) {
      showErrors({ total_price: ['Total price must be greater than the amount.'] });
      return;
    }

    var fd = buildFormData();
    busy = true;
    setLoading(true);

    postJson(validateUrl, fd)
      .then(function (result) {
        if (result.status === 422 && result.data && result.data.errors) {
          showErrors(result.data.errors);
          return null;
        }
        if (!(result.status === 200 && result.data && result.data.success)) {
          var formErr = document.getElementById('pl-e-form');
          if (formErr) {
            formErr.textContent = (result.data && result.data.message) || 'Could not validate the payment link.';
            formErr.hidden = false;
          }
          return null;
        }
        // Same controller store endpoint after validation — then show template done screen
        return postJson(storeUrl, buildFormData());
      })
      .then(function (result) {
        if (!result) return;
        if (result.status === 422 && result.data && result.data.errors) {
          showErrors(result.data.errors);
          return;
        }
        if (result.status === 200 && result.data && result.data.success && result.data.payment_link) {
          showDone(result.data.payment_link);
          return;
        }
        var formErr = document.getElementById('pl-e-form');
        if (formErr) {
          formErr.textContent = (result.data && result.data.message) || 'Could not generate the link.';
          formErr.hidden = false;
        }
      })
      .catch(function () {
        var formErr = document.getElementById('pl-e-form');
        if (formErr) {
          formErr.textContent = 'Could not generate the link. Please try again.';
          formErr.hidden = false;
        }
      })
      .finally(function () {
        busy = false;
        setLoading(false);
      });
    });
  })();
</script>
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
