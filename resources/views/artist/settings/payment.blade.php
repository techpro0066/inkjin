@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Money · Payouts')

@php
  $activeNav = 'money';
  $ud = $userDetail;
  $activePayout = $ud->activePayout();
  $hasStudioPayoutLink = \App\Models\UserStudio::tableReady()
    && (
      \App\Models\UserStudio::payoutLinkForUser((int) $ud->user_id)
      || \App\Models\UserStudio::pendingJoinForUser((int) $ud->user_id)
    );
  $studioEmail = old('studio_email', $ud->studio->email ?? '');
  // Money mode: payout=1 + revenue_split on user_studios (pending join still shows studio UI).
  $pt = $hasStudioPayoutLink ? 'studio_account' : $ud->resolvedPayoutPaymentType();
  $payoutKey = match ($pt) {
    'studio_account' => 'studio',
    default => 'artist',
  };
  $studioLocked = ($pt === 'studio_account' && (bool) $ud->resolvedStudioId());
  $payoutStudioLink = $ud->payoutUserStudio();
  $payoutStudio = $payoutStudioLink
    ? ($payoutStudioLink->studio ?? \App\Models\Studio::query()->find($payoutStudioLink->studio_id))
    : null;
  // Confirmed only when studio accepted (on_bookpay) + has Stripe — not artist payout "approved".
  $studioPayoutLinked = $pt === 'studio_account'
    && $payoutStudioLink
    && ($payoutStudioLink->connection_type ?? '') === \App\Models\UserStudio::CONNECTION_ON_BOOKPAY
    && filled($payoutStudio?->resolveStripeAccountId());
  $artistStripeConnected = (bool) ($artistStripeConnected ?? false);
  $studioPayoutConnected = (bool) ($studioPayoutConnected ?? $studioPayoutLinked);
  $studioPayoutCommitted = (bool) ($studioPayoutCommitted ?? $studioLocked);
  $studioPayoutStatus = match (true) {
    $studioPayoutConnected => 'connected',
    $studioPayoutCommitted => 'not_connected',
    default => 'email_not_sent',
  };
  $payoutOptionLocked = (bool) ($payoutOptionLocked ?? $studioPayoutCommitted);
  $studioDraftWithStripe = $artistStripeConnected
    && $pt === 'studio_account'
    && ! $studioPayoutCommitted;
  $showStudioDisconnect = $studioPayoutCommitted;
  $showArtistStripeDisconnect = $artistStripeConnected;
  $stripeComplete = (bool) ($stripeStatus['complete'] ?? false);
  $artistStripeNeedsAction = (bool) ($artistStripeNeedsAction ?? false);
  $studioStripeNeedsAction = (bool) ($studioStripeNeedsAction ?? false);
  $stripeConnectLocale = $stripeConnectLocale ?? config('services.stripe.connect.locale', 'en-US');
  $payoutBankCountry = $payoutBankCountry ?? $activePayout?->payout_bank_country ?? $ud->payout_bank_country ?? null;
  $payoutWaitingListCountry = $payoutWaitingListCountry ?? $activePayout?->payout_waiting_list_country ?? $ud->payout_waiting_list_country ?? null;
  $payoutBankCountryName = $payoutBankCountryName ?? ($payoutBankCountry ? \App\Support\StripeConnectCountries::nameFor($payoutBankCountry) : null);
  $hasSignupPayoutCountry = $payoutBankCountry
    && \App\Support\StripeConnectCountries::isSupported($payoutBankCountry);
  $payoutModeRaw = $activePayout?->payout_mode ?? $ud->payout_mode ?? 'automatic';
  $payoutMode = in_array($payoutModeRaw, ['manual', 'automatic'], true) ? $payoutModeRaw : 'automatic';
  $studioRelationshipTypes = [
    'co_owner' => ['Co-owner', 'Owns or partners in the studio'],
    'resident' => ['Resident', 'Permanent spot with regular bookings'],
    'collective_member' => ['Collective Member', 'Part of a collective studio model'],
    'apprentice' => ['Apprentice', 'Learning and training under someone'],
    'other' => ['Other (Contract Artist, Freelancer)', 'Contract or freelance arrangement'],
  ];
  $studioRevenueArtistPercent = (int) old(
    'studio_revenue_artist_percent',
    $ud->resolvedStudioRevenueArtistPercent(50)
  );
  $studioRevenueArtistPercent = max(0, min(100, $studioRevenueArtistPercent));
  $studioRelationshipSelected = old(
    'studio_relationship_type',
    $ud->payoutUserStudio()?->relationship ?? $ud->studio_relationship_type ?? 'resident'
  );
  if (! array_key_exists($studioRelationshipSelected, $studioRelationshipTypes)) {
    $studioRelationshipSelected = 'resident';
  }

  $studioDisplayName = method_exists($ud, 'resolvedStudioName')
    ? trim((string) $ud->resolvedStudioName())
    : trim((string) ($ud->studio_name ?? ''));
  if ($studioDisplayName === '') {
    $studioDisplayName = trim((string) ($ud->studio_name ?? ''));
  }
  $studioCity = trim((string) ($ud->city ?? ''));
  $studioInitials = collect(preg_split('/\s+/', $studioDisplayName ?: 'Studio'))
    ->filter()
    ->take(2)
    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
    ->implode('');
  if ($studioInitials === '') {
    $studioInitials = 'ST';
  }

  $isStudioSplit = $pt === 'studio_account';
  $youPercent = $isStudioSplit ? $studioRevenueArtistPercent : 100;
  $studioPercent = $isStudioSplit ? (100 - $studioRevenueArtistPercent) : 0;
  $setupComplete = $artistStripeConnected || $studioPayoutCommitted;
  $changePanelOpen = ! $setupComplete;

  $activeStudioLinks = \App\Models\UserStudio::tableReady()
    ? \App\Models\UserStudio::activeLinksForUser((int) $ud->user_id)
    : collect();
  $payoutStudioCards = $activeStudioLinks->map(function (\App\Models\UserStudio $link) {
    $studio = $link->studio;
    $name = trim((string) ($studio?->name ?: $link->studio_name ?: 'Studio'));
    $ini = collect(preg_split('/\s+/', $name))
      ->filter()
      ->take(2)
      ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
      ->implode('');
    $isSplit = (bool) $link->payout && $link->revenue_split !== null;
    $isWaiting = $isSplit && ($link->connection_type ?? '') === \App\Models\UserStudio::CONNECTION_NOT_CONNECTED;
    $isConfirmed = $isSplit && ($link->connection_type ?? '') === \App\Models\UserStudio::CONNECTION_ON_BOOKPAY;
    $hasPendingSplitAsk = $isSplit && $link->hasOpenArtistSplitProposal();
    $proposedYou = $hasPendingSplitAsk
      ? max(0, min(100, (int) $link->proposed_revenue_split))
      : null;
    $you = $isSplit ? max(0, min(100, (int) $link->revenue_split)) : 100;
    $city = trim((string) ($studio?->city ?: $link->city ?: ''));
    $onBookpay = ! empty($studio?->user_id) || $isConfirmed;
    $rel = $link->relationship;
    $relLabel = match ($rel) {
      'co_owner' => 'Co-owner',
      'resident' => 'Resident',
      'collective_member' => 'Collective Member',
      'apprentice' => 'Apprentice',
      'other' => 'Other',
      default => null,
    };
    $metaParts = array_values(array_filter([
      $onBookpay ? 'On Bookpay' : 'Not on Bookpay',
      $relLabel,
      $city !== '' ? $city : null,
    ]));

    return [
      'id' => $link->id,
      'studio_id' => $link->studio_id,
      'name' => $name,
      'ini' => $ini !== '' ? $ini : 'ST',
      'meta' => implode(' · ', $metaParts),
      'is_split' => $isSplit,
      'is_waiting' => $isWaiting,
      'is_confirmed' => $isConfirmed,
      'has_pending_split_ask' => $hasPendingSplitAsk,
      'proposed_you_percent' => $proposedYou,
      'proposed_studio_percent' => $proposedYou !== null ? 100 - $proposedYou : null,
      'you_percent' => $you,
      'studio_percent' => 100 - $you,
      'email' => $studio?->email,
    ];
  })->values();
  $hasMultiplePayoutStudios = $payoutStudioCards->count() >= 2;
  $payoutUserStudioId = $payoutStudioLink?->id;
  $pendingSplitAsk = $payoutStudioLink && $isStudioSplit && $payoutStudioLink->hasOpenArtistSplitProposal();
  $pendingSplitYouPercent = $pendingSplitAsk
    ? max(0, min(100, (int) $payoutStudioLink->proposed_revenue_split))
    : null;
  $pendingSplitStudioPercent = $pendingSplitYouPercent !== null ? 100 - $pendingSplitYouPercent : null;

  if ($isStudioSplit) {
    $howTitle = 'Studio - Revenue split';
    $howPill = $studioPayoutConnected
      ? ('Confirmed by '.($studioDisplayName !== '' ? $studioDisplayName : 'studio'))
      : ($studioPayoutCommitted ? 'Waiting for studio' : 'Not set up');
    $howPillClass = $studioPayoutConnected ? 'g' : 'a';
    $howWhere = $studioDisplayName !== ''
      ? ('Studio: '.$studioDisplayName.($studioCity !== '' ? ' · '.$studioCity : ''))
      : 'Studio revenue split';
    $howSub = $artistStripeConnected
      ? ($studioDisplayName !== ''
        ? 'You work at '.$studioDisplayName.' with a revenue split'
        : 'Payments go to your studio with a revenue split')
      : 'Paused until you connect a bank account. Your setup is kept.';
    $lockIcon = 'lock';
    $lockText = 'This split is locked. Only the studio can change it. If they do, you\'ll be asked to accept, and the new split applies only to new bookings.';
  } else {
    $howTitle = 'Artist - Direct payment';
    $howPill = 'Active';
    $howPillClass = 'g';
    $lockIcon = null;
    $lockText = null;
    if ($studioDisplayName !== '') {
      $howWhere = 'Studio: '.$studioDisplayName.($studioCity !== '' ? ' · '.$studioCity : '');
      $howSub = $artistStripeConnected
        ? 'You work at '.$studioDisplayName.' and get paid directly'
        : 'Paused until you connect a bank account. Your setup is kept.';
    } else {
      $howWhere = 'Own space'.($studioCity !== '' ? ': '.$studioCity : '');
      $howSub = $artistStripeConnected
        ? 'You work from your own space, so payments go straight to you'
        : 'Paused until you connect a bank account. Your setup is kept.';
    }
  }

  if ($hasMultiplePayoutStudios) {
    $howSub = 'You work at '.$payoutStudioCards->count().' studios. Each studio has its own setup.';
  }
@endphp

@section('styles')
<style>
  .pay-page .payout-mode-option{display:flex;align-items:flex-start;gap:12px;cursor:pointer}
  .pay-page .payout-mode-option + .payout-mode-option{margin-top:16px}
  .pay-page .payout-mode-radio{
    width:20px;height:20px;border-radius:50%;border:2px solid #cac4d3;
    flex-shrink:0;margin-top:2px;display:flex;align-items:center;justify-content:center
  }
  .pay-page .payout-mode-option.selected .payout-mode-radio{border-color:#3E007C;background:#3E007C}
  .pay-page .payout-mode-option.selected .payout-mode-radio::after{content:'';width:6px;height:6px;background:#fff;border-radius:50%}
  .pay-page .picked{background:#fff;border:2px solid #3E007C;border-radius:14px;padding:16px 18px}
  .pay-page .logo2{width:48px;height:48px;border-radius:12px;background:#EDD9FF;color:#3E007C;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .pay-page .lockbar{display:flex;gap:8px;align-items:center;background:#F0EDF2;color:var(--muted);border-radius:10px;padding:9px 12px;font-size:12.5px}
  .pay-page .p2box{border:1px solid #E8DFEA;border-radius:14px;padding:16px 18px;background:#fff}
  .pay-page .p2box+.p2box{margin-top:12px}
  .pay-page .p2top{display:flex;gap:14px;align-items:center;flex-wrap:wrap}
  .pay-page .p2nums{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
  .pay-page .p2nums .sect{margin:0;text-align:center}
  .pay-page .p2nums .big{font-size:24px;font-weight:800}
  .pay-page .p2wait{display:flex;gap:12px;align-items:center;background:#FFF7E6;border:1px solid #F3DDB0;border-radius:12px;padding:10px 14px;margin-top:12px;color:#8A5A00;font-size:13px;flex-wrap:wrap}
  .pay-page .p2wait .ms{color:#B7791F}
  .pay-page .p2now{background:#F7F3F8;border-radius:12px;padding:10px 14px;font-size:13px}
  .pay-page .p2pct{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px}
  .pay-page .p2pct label{font-size:12px;font-weight:700;color:#6F6874;text-transform:uppercase;letter-spacing:.4px}
  .pay-page .p2pct .fld{display:flex;align-items:center;border:1px solid #DCD2E0;border-radius:10px;padding:0 12px;height:44px;margin-top:6px;background:#fff}
  .pay-page .p2pct .fld input{border:0;outline:0;font:inherit;font-size:16px;font-weight:700;width:100%;background:transparent}
  .pay-page .p2pct .fld.ro{background:#F7F3F8;font-size:16px;font-weight:700}
  .pay-page .sect{border:1px solid #EFE8F1;border-radius:14px;padding:14px 16px;background:#FDFBFD}
  .pay-page .dwarn{display:flex;gap:12px;align-items:center;background:#FDECEC;border:1px solid #F3C9C9;border-radius:14px;padding:14px 18px;color:#8E1B1B;margin-bottom:14px;flex-wrap:wrap}
  .pay-page .dwarn>.ms{color:#C62828;font-size:26px;font-variation-settings:'FILL' 1}
  .pay-page .ch .chpo{flex-shrink:0;white-space:nowrap}
  .pay-page .form-input{width:100%;padding:.75rem 1rem;border-radius:.75rem;border:1px solid rgba(202,196,211,.5);background:#fff;color:#1c1b21}
  .pay-page .form-input:focus{outline:none;box-shadow:0 0 0 2px rgba(62,0,124,.25)}
  .pay-page .hidden,.hidden{display:none!important}
  .pay-ov{position:fixed;inset:0;z-index:520;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(20,10,30,.45)}
  .pay-ov:not(.hidden){display:flex!important}
  .pay-ov .pay-modal{background:#fff;border-radius:22px;width:520px;max-width:100%;max-height:calc(100vh - 48px);box-shadow:0 20px 60px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:hidden}
  .pay-ov .pay-mh{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:22px 22px 14px;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .pay-ov .pay-mh h2{font-size:20px;font-weight:800;margin:0}
  .pay-ov .pay-mh .pay-x{background:none;border:0;cursor:pointer;color:var(--faint);font-size:22px;line-height:1;padding:2px 4px}
  .pay-ov .pay-mb{padding:18px 22px;overflow-y:auto;min-height:0;flex:1 1 auto}
  .pay-ov .pay-mf{display:flex;gap:10px;justify-content:flex-end;padding:14px 22px 20px;border-top:1px solid #F0EAF2;flex-shrink:0}
  .pay-ov .po{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px;cursor:pointer;margin-bottom:10px}
  .pay-ov .po.sel{border:2px solid #3E007C;padding:15px 17px;background:#FDFAFF}
  .pay-ov .po input{accent-color:#3E007C;width:18px;height:18px;margin-top:2px;flex-shrink:0}
  .pay-ov .po b{font-size:14.5px;display:block}
  .pay-ov .po .d{font-size:13px;color:var(--muted);margin-top:3px;line-height:1.5}
  .pay-ov .splitbox{background:#FBF7FF;border:1px solid #E4D6F5;border-radius:14px;padding:18px;margin:4px 0 10px}
  .pay-ov .pct{display:flex;align-items:center;gap:8px}
  .pay-ov .pct .in,.pay-ov .pct input.in{width:80px;text-align:center;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font:inherit;font-size:16px;font-weight:700}
  .pay-ov .stepn{width:26px;height:26px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .pay-ov .fl{font-size:13px;font-weight:700;margin-bottom:6px;display:block}
  .pay-ov .btn{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;cursor:pointer;white-space:nowrap}
  .pay-ov .btn .ms{font-size:18px}
  .pay-ov .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
  .pay-ov .btn:disabled{opacity:.4;pointer-events:none}
  .pay-ov .in{background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 13px;font:inherit;font-size:13.5px;color:var(--ink);width:100%;outline:none}
  .pay-ov .in:focus{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .pay-ov .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px}
  @media (max-width:560px){.pay-ov .grid-2{grid-template-columns:1fr}}
  .pay-ov .blist{font-size:13px;line-height:1.7;padding-left:18px;margin:12px 0 0;color:var(--ink)}
  .pay-ov .sect{border:1px solid #EFE8F1;border-radius:14px;padding:14px 16px;background:#FDFBFD}
  .pay-ov .lockbar{display:flex;gap:8px;align-items:center;background:#F0EDF2;color:var(--muted);border-radius:10px;padding:9px 12px;font-size:12.5px}
  .pay-ov .dwarn{display:flex;gap:12px;align-items:center;background:#FDECEC;border:1px solid #F3C9C9;border-radius:14px;padding:14px 18px;color:#8E1B1B;flex-wrap:wrap}
  .pay-ov .dwarn>.ms{color:#C62828;font-size:26px;font-variation-settings:'FILL' 1}
  .pay-ov .help{font-size:12px;color:var(--faint);margin-top:6px}
  .pay-ov .row{display:flex;align-items:center;gap:12px}
  .pay-ov .pc-fl{display:block;font-weight:700;font-size:13px;margin:4px 0 6px}
  .pay-ov .pc-cs{position:relative}
  .pay-ov .pc-cs-b{
    display:flex;align-items:center;width:100%;text-align:left;cursor:pointer;
    font:inherit;font-size:14px;background:#fff;border:1px solid #E1D8E4;
    border-radius:10px;padding:10px 12px;color:var(--ink)
  }
  .pay-ov .pc-cs-b:focus-visible,
  .pay-ov .pc-cs-b[aria-expanded="true"]{border-color:#3E007C;box-shadow:0 0 0 3px #EEE3FA;outline:0}
  .pay-ov .pc-cs-ph{color:#9A929E}
  .pay-ov .pc-cs-p{
    position:absolute;left:0;right:0;top:100%;margin-top:4px;background:#fff;
    border:1px solid #E8DFEA;border-radius:12px;box-shadow:0 12px 30px rgba(30,15,45,.14);
    z-index:40;padding:8px
  }
  .pay-ov .pc-cs-p[hidden]{display:none}
  .pay-ov .pc-cs-q{
    width:100%;margin-bottom:6px;border:1px solid #DCD2E0;border-radius:8px;
    font:inherit;padding:9px 11px;outline:0;box-sizing:border-box
  }
  .pay-ov .pc-cs-q:focus{border-color:#3E007C}
  .pay-ov .pc-cs-l{max-height:240px;overflow:auto}
  .pay-ov .pc-cs-o{
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    padding:10px 12px;border-radius:8px;font-size:14px;cursor:pointer
  }
  .pay-ov .pc-cs-o:hover,.pay-ov .pc-cs-o.kb{background:#F3E8FF}
  .pay-ov .pc-cs-o.sel{background:#3E007C;color:#fff}
  .pay-ov .pc-cs-o small{color:#9A929E;font-weight:600}
  .pay-ov .pc-cs-o.sel small{color:rgba(255,255,255,.8)}
  .pay-ov .pc-cs-none{padding:10px 12px;font-size:13px;color:#9A929E}
  .pay-ov .pc-cs-none[hidden]{display:none}
  .pay-ov .pc-cur{margin-top:10px;padding:10px 12px;border-radius:10px;background:#F3E8FF;color:#3E007C;font-size:13.5px}
  .pay-ov .pc-cur.same{background:#F6F3F8;color:var(--ink)}
  .pay-ov .pc-ul{margin:12px 0 0;padding:0;list-style:none;font-size:13px;color:#3F3A44}
  .pay-ov .pc-ul li{display:flex;gap:8px;padding:5px 0;align-items:flex-start}
  .pay-ov .pc-ul .ms{font-size:17px;color:#6F6874;flex-shrink:0;margin-top:1px}
  .pay-ov .pc-wait{display:flex;flex-direction:column;align-items:center;gap:12px;padding:30px 22px 34px;text-align:center;color:#6F6874}
  .pay-ov .pc-spin{width:34px;height:34px;border-radius:50%;border:3px solid #E9DDF0;border-top-color:#3E007C;animation:pay-pcs .8s linear infinite}
  @keyframes pay-pcs{to{transform:rotate(360deg)}}
  .pay-ov.pay-modal-wide .pay-modal{width:min(520px,100%)}
  .pay-ov.pay-modal-stripe .pay-modal{width:min(720px,100%)}
  .pay-ov #changePayoutAccountStripeMount{min-height:420px;width:100%;background:#fff;border-radius:12px}
  .pay-page button.btn{border:0;cursor:pointer;font:inherit}
  .pay-page .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
  .pay-page .radio-card{border:1.5px solid #cac4d3;border-radius:12px;padding:14px 16px;cursor:pointer;transition:all .2s;position:relative;display:flex;align-items:flex-start;gap:12px}
  .pay-page .radio-card.selected{border-color:#3E007C;background:#fdf7ff}
  .pay-page .radio-card .radio-dot{width:18px;height:18px;border-radius:50%;border:2px solid #cac4d3;transition:all .2s;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
  .pay-page .radio-card.selected .radio-dot{border-color:#3E007C;background:#3E007C}
  .pay-page .radio-card.selected .radio-dot::after{content:'';width:6px;height:6px;background:#fff;border-radius:50%}
  .pay-page .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  .pay-page .studio-split-relationship-panel{display:flex;flex-direction:column;gap:16px}
  .pay-page .studio-revenue-split,.pay-page .studio-relationship-type{display:flex;flex-direction:column;gap:10px}
  .pay-page .studio-revenue-split .flex{display:flex;flex-wrap:wrap;align-items:flex-end;gap:16px}
  .pay-page .studio-relationship-type .grid{display:grid;grid-template-columns:1fr;gap:10px}
  .pay-page .relative{position:relative}
  .pay-page .w-24{width:6rem}
  .pay-page .pr-8{padding-right:2rem}
  .pay-page .pointer-events-none{pointer-events:none}
  .pay-page .absolute.inset-y-0.right-2\.5,.pay-page .absolute{position:absolute;top:0;bottom:0;right:10px;display:flex;align-items:center}
  .pay-page .text-sm{font-size:13.5px}.pay-page .text-xs{font-size:12px}
  .pay-page .font-semibold,.pay-page .font-bold{font-weight:700}
  .pay-page .text-on-surface{color:var(--ink)}.pay-page .text-on-surface-variant{color:var(--muted)}
  .pay-page .text-primary{color:var(--pri)}
  .pay-page .border-primary\/20{border:1px solid rgba(62,0,124,.2)}
  .pay-page .rounded-xl{border-radius:12px}
  .pay-page .py-3{padding-top:12px;padding-bottom:12px}.pay-page .px-8{padding-left:28px;padding-right:28px}
  .pay-page .inline-flex{display:inline-flex;align-items:center;gap:8px}
  .pay-page .min-w-0{min-width:0}
  .pay-page .mt-0\.5{margin-top:2px}.pay-page .mb-1\.5{margin-bottom:6px}
  .pay-page .leading-relaxed{line-height:1.5}
  .pay-page .tabular-nums{font-variant-numeric:tabular-nums}
  .pay-page .block{display:block}
  .pay-page .text-center{text-align:center}
  .pay-page .select-none{user-select:none}
  .pay-page .bg-surface-container-high\/60{background:#F0EDF2}
  .pay-page .flex.items-center.justify-center{display:flex;align-items:center;justify-content:center;gap:4px}
  .pay-page .space-y-3>*+*,.pay-page .space-y-4>*+*{margin-top:0}
  .pay-page .card:has(#paymentAccountMount),
  .pay-page #paymentAccountMount,
  .pay-page #payoutArtistBankSection,
  .pay-page #artistStripeSetupSection,
  .pay-page #payoutStripeStep{
    width:100%;
    max-width:100%;
    min-width:0;
    overflow:visible;
  }
  .pay-page .stripe-connect-shell{
    width:100%;
    max-width:100%;
    min-width:0;
    margin:0;
    padding:8px 4px 12px;
    overflow-x:auto;
    overflow-y:visible;
    -webkit-overflow-scrolling:touch;
  }
  .pay-page #stripeConnectMount{
    min-height:420px;
    width:100%;
    max-width:100%;
    min-width:0;
    background:#fff;
    border-radius:12px;
    overflow:visible;
    box-sizing:border-box;
    padding:8px 16px 16px;
  }
  .pay-page #stripeConnectMount > *{
    display:block;
    width:100%!important;
    max-width:100%!important;
    min-width:0!important;
    box-sizing:border-box;
  }
  .pay-page #stripeConnectMount iframe{
    width:100%!important;
    max-width:100%!important;
  }
  @media (max-width:900px){
    .pay-page .ch:has(.chpo){flex-wrap:wrap;gap:10px}
    .pay-page .how-split{grid-template-columns:1fr!important}
    .pay-page #stripeConnectMount{padding:8px 12px 14px}
  }
</style>
@endsection

@section('content')
<div class="pay-page">
<form id="paymentForm">
  @csrf
  <input type="hidden" name="payment_type" id="payment_type" value="{{ $pt }}" />

  <div class="head">
    <div>
      <h1>
        Money
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201212-dashboard-money-payouts" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">What you've earned, how clients pay you, and where your payouts go.</div>
    </div>
  </div>

  @include('artist.partials.money-tabs', ['moneyTab' => 'payouts'])

  <p id="payment_type_error" class="help hidden" style="color:var(--red)"></p>
  <div id="payAlert" class="hidden" style="border-radius:12px;padding:12px 14px;font-size:13.5px;margin-bottom:14px"></div>

  @if (session('success'))
    <div class="note" style="margin-bottom:14px;border-style:solid;border-color:#C8E6C9;background:#E8F5E9;color:#1B5E20">
      <span class="ms">check_circle</span>{{ session('success') }}
    </div>
  @endif
  @if (session('error'))
    <div class="dwarn" style="margin-bottom:14px">
      <span class="ms">error</span>
      <div style="flex:1">{{ session('error') }}</div>
    </div>
  @endif

  @if($pt === 'studio_account' && ($ud->payment_status ?? '') === 'rejected' && empty($ud->studio_id))
    <div class="dwarn">
      <span class="ms">error</span>
      <div style="flex:1">Your studio declined your payout request. You can connect your own bank account as an Artist or send an invite to a different studio.</div>
        </div>
  @endif

  @if (! $artistStripeConnected && $hasSignupPayoutCountry && ! $payoutWaitingListCountry)
    <div class="dwarn" id="payoutDisconnectedWarn">
      <span class="ms">error</span>
      <div style="flex:1">
        <b>Your bank account is disconnected</b>
        <div style="font-size:13px;margin-top:3px;line-height:1.5">Clients can't pay deposits and new bookings are paused until you connect again. Money already on its way still arrives.</div>
                    </div>
      <button type="button" class="btn dconn" id="connectBankAccountWarnBtn">
        <span class="ms">link</span>Connect with Stripe
      </button>
                    </div>
  @elseif ($artistStripeConnected && $payoutMode === 'automatic')
    <div class="note" style="margin-bottom:14px">
      <span class="ms">bolt</span>Payouts are automatic. Money is sent to your bank as soon as it becomes available.
                    </div>
  @endif

  {{-- How you get paid --}}
  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>How you get paid</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">{{ $howSub }}</div>
                  </div>
      @unless ($hasMultiplePayoutStudios)
      <div class="row chpo" style="gap:8px;flex-wrap:wrap">
        @if ($isStudioSplit)
          @unless ($pendingSplitAsk)
          <button type="button" class="btn sm ghost" id="askNewSplitBtn">
            <span class="ms" style="font-size:17px">percent</span>Ask for a new split
          </button>
          @endunless
          <button type="button" class="btn sm ghost" id="changeHowPaidBtn">
            <span class="ms" style="font-size:17px">swap_horiz</span>Switch to direct payment
          </button>
        @else
          <button type="button" class="btn sm ghost" id="changeHowPaidBtn">
            <span class="ms" style="font-size:17px">swap_horiz</span>Switch to studio revenue split
          </button>
        @endif
      </div>
      @endunless
                </div>
    @if ($hasMultiplePayoutStudios)
    <div style="padding:18px 22px" id="p2list">
      @foreach ($payoutStudioCards as $card)
        <div class="p2box" data-user-studio-id="{{ $card['id'] }}" data-studio-name="{{ $card['name'] }}" data-studio-email="{{ $card['email'] ?? '' }}">
          <div class="p2top">
            <div class="logo2">{{ $card['ini'] }}</div>
            <div style="flex:1;min-width:180px">
              <div class="row" style="gap:8px;flex-wrap:wrap">
                <b style="font-size:15px">{{ $card['name'] }}</b>
                <span class="pill {{ $card['is_split'] ? ($card['is_waiting'] ? 'a' : 'g') : 'k' }}">
                  {{ $card['is_split'] ? ($card['is_waiting'] ? 'Waiting for studio' : 'Studio - Revenue split') : 'Artist - Direct payment' }}
                        </span>
                      </div>
              <div class="faint" style="font-size:12.5px;margin-top:2px">{{ $card['meta'] }}</div>
            </div>
            @if ($card['is_split'])
              <div class="row" style="gap:8px;flex-wrap:wrap">
                @unless ($card['has_pending_split_ask'])
                <button
                  type="button"
                  class="btn sm ghost js-p2-ask-new-split"
                  data-user-studio-id="{{ $card['id'] }}"
                  data-studio-name="{{ $card['name'] }}"
                  data-you-percent="{{ $card['you_percent'] }}"
                  data-studio-percent="{{ $card['studio_percent'] }}"
                >
                  <span class="ms" style="font-size:17px">percent</span>Ask for a new split
                </button>
                @endunless
                <button type="button" class="btn sm ghost js-p2-switch-direct" @if (! $artistStripeConnected) disabled style="opacity:.4;pointer-events:none" @endif>
                  <span class="ms" style="font-size:17px">swap_horiz</span>Switch to direct payment
                </button>
              </div>
            @else
              <button type="button" class="btn sm ghost js-p2-setup-split">
                <span class="ms" style="font-size:17px">percent</span>Set up a revenue split
              </button>
            @endif
                  </div>
          <div class="p2nums">
            <div class="sect">
              <div class="lbl">You get</div>
              <div class="big">{{ $card['you_percent'] }}%</div>
                </div>
            <div class="sect">
              <div class="lbl">{{ $card['is_split'] ? 'Studio gets' : 'Revenue split' }}</div>
              <div class="big" @if (! $card['is_split']) style="color:var(--faint)" @endif>
                {{ $card['is_split'] ? $card['studio_percent'].'%' : 'None' }}
              </div>
                    </div>
                    </div>
          @if ($card['has_pending_split_ask'])
            <div class="p2wait" style="margin-top:10px">
              <span class="ms">hourglass_top</span>
              <div style="flex:1;min-width:200px">
                <b>Waiting for {{ $card['name'] }}</b>
                <div style="font-size:12.5px;margin-top:2px">
                  You asked for You {{ $card['proposed_you_percent'] }}% · Studio {{ $card['proposed_studio_percent'] }}%.
                  Current split stays until they accept or decline. A new split applies only to new bookings.
                </div>
              </div>
            </div>
          @elseif ($card['is_confirmed'])
            <div class="faint" style="font-size:12.5px;margin-top:10px">Confirmed by {{ $card['name'] }}. Locked: only the studio can change it, and you accept first.</div>
          @elseif ($card['is_waiting'])
            <div class="p2wait">
              <span class="ms">hourglass_top</span>
              <div style="flex:1;min-width:200px">
                <b>Waiting for {{ $card['name'] }}</b>
                <div style="font-size:12.5px;margin-top:2px">You keep getting paid directly until they accept. The split applies only to new bookings.</div>
                    </div>
              <button type="button" class="btn sm ghost js-p2-resend">Resend invitation</button>
                      </div>
          @elseif (! $card['is_split'])
            <div class="faint" style="font-size:12.5px;margin-top:10px">The full payment goes to you. You settle rent or fees with {{ $card['name'] }} outside Bookpay.</div>
                    @endif
                  </div>
      @endforeach
                </div>
    <div style="padding:0 22px 18px">
      <div class="lockbar">
        <span class="ms" style="font-size:17px">info</span>
        <span>Each booking uses the setup of the studio where the session is. You pick which days you work at each studio in <a href="{{ route('schedule.calendar-sync') }}" style="color:#3E007C;font-weight:600">Schedule</a>.</span>
              </div>
                    </div>
    @else
    <div style="padding:18px 22px">
      <div class="picked" id="howPaidSummary" style="{{ $artistStripeConnected ? '' : 'opacity:.5' }}">
        <div class="row" style="gap:14px">
          <div class="logo2">{{ $studioInitials }}</div>
          <div style="flex:1">
            <div class="row" style="gap:8px;flex-wrap:wrap">
              <b style="font-size:15px">{{ $howTitle }}</b>
              <span class="pill {{ $howPillClass }}">{{ $howPill }}</span>
                      </div>
            <div class="faint" style="font-size:12.5px;margin-top:2px">{{ $howWhere }}</div>
                      </div>
                      </div>
        <div class="grid how-split" style="grid-template-columns:1fr 1fr;gap:10px;margin-top:14px">
          <div class="sect" style="margin:0;text-align:center">
            <div class="lbl">You get</div>
            <div style="font-size:26px;font-weight:800">{{ $youPercent }}%</div>
                      </div>
          <div class="sect" style="margin:0;text-align:center">
            <div class="lbl">{{ $isStudioSplit ? 'Studio gets' : 'Revenue split' }}</div>
            @if ($isStudioSplit)
              <div style="font-size:26px;font-weight:800">{{ $studioPercent }}%</div>
                    @else
              <div style="font-size:26px;font-weight:800;color:var(--faint)">None</div>
                      @endif
                      </div>
                    </div>
        @if ($pendingSplitAsk)
          <div class="p2wait" style="margin-top:14px">
            <span class="ms">hourglass_top</span>
            <div style="flex:1;min-width:200px">
              <b>Waiting for {{ $studioDisplayName !== '' ? $studioDisplayName : 'studio' }}</b>
              <div style="font-size:12.5px;margin-top:2px">
                You asked for You {{ $pendingSplitYouPercent }}% · Studio {{ $pendingSplitStudioPercent }}%.
                Current split stays until they accept or decline. A new split applies only to new bookings.
              </div>
            </div>
          </div>
        @endif
        @if ($isStudioSplit && $studioPayoutCommitted && ! $studioPayoutConnected)
          <div class="row" style="gap:10px;flex-wrap:wrap;align-items:center;margin-top:14px;padding-top:14px;border-top:1px solid #E9DDF0">
            <button type="button" class="btn sm ghost" id="resendStudioInviteBtn">
              <span class="ms" style="font-size:16px">send</span>Resend invitation
            </button>
            <span id="resendStudioInviteMsg" class="faint" style="font-size:12.5px"></span>
                  </div>
        @endif
                </div>
      @if ($lockText)
        <div class="lockbar" style="margin-top:12px">
          <span class="ms" style="font-size:17px">{{ $lockIcon }}</span>
          <span>{{ $lockText }}</span>
        </div>
                    @endif
                    
      <div id="changePayoutPanel" class="{{ $changePanelOpen ? '' : 'hidden' }}" style="margin-top:18px">
        <div id="payout-artist" class="{{ $payoutKey !== 'artist' ? 'hidden' : '' }}">
          <div id="artistBankMount"></div>
          <div id="paySaveArtistWrap" class="{{ ($studioDraftWithStripe && $payoutKey === 'artist') ? '' : 'hidden' }}" style="margin-top:12px">
            <button type="button" id="paySaveArtist" class="btn pri">Save as Artist</button>
            <p class="help">Stripe is already connected. Save Artist as your payout option.</p>
              </div>
            </div>

        <div id="payout-studio" class="{{ $payoutKey !== 'studio' ? 'hidden' : '' }}">
          <div id="studioBankMount" class="hidden"></div>
          <div style="background:#FBF8FC;border:1px solid var(--line);border-radius:14px;padding:18px;space-y:14px">
            <div style="margin-bottom:14px">
              <h3 style="margin-bottom:4px">Studio payout</h3>
              <p class="faint" style="font-size:13px;margin:0">Invite your studio to connect their bank account. Both your account and the studio must be connected.</p>
            </div>

            <div id="studioPayoutEmailNotSent" class="{{ $studioPayoutStatus !== 'email_not_sent' ? 'hidden' : '' }}">
              <span class="pill k" style="margin-bottom:10px">Email not sent</span>
              <p class="faint" style="font-size:13px;margin:10px 0 14px">You need to send an email to your studio and ask them to connect their bank account. Payouts are on hold until they do.</p>
              <div style="margin-bottom:14px">
                <label for="studio_email" class="fl">Studio Email Address</label>
                <input type="email" id="studio_email" name="studio_email" value="{{ $studioEmail }}" placeholder="studio@example.com" class="form-input" autocomplete="email">
        </div>
              @include('onboarding.partials.studio-split-relationship', [
                'fieldSuffix' => '',
                'percentId' => 'studio_revenue_you_get',
                'showSaveButton' => false,
                'hintId' => 'studio_revenue_you_get_hint',
                'hintText' => 'Enter 0 if the studio collects the entire payment for your bookings.',
                'studioRevenueArtistPercent' => $studioRevenueArtistPercent,
                'studioRelationshipSelected' => $studioRelationshipSelected,
                'studioRelationshipTypes' => $studioRelationshipTypes,
              ])
              <button type="button" id="payStudioSend" class="btn pri" style="margin-top:12px">Save &amp; Send</button>
      </div>
                    
            <div id="studioPayoutNotConnected" class="{{ $studioPayoutStatus !== 'not_connected' ? 'hidden' : '' }}">
              <span class="pill a" style="margin-bottom:10px">Not connected</span>
              <p class="faint" style="font-size:13px;margin:10px 0 14px">Your studio hasn't connected a bank account yet. Payouts are on hold until they do.</p>
              <div style="margin-bottom:14px">
                <label for="studio_email_not_connected" class="fl">Studio Email Address</label>
                <div class="row" style="gap:8px;flex-wrap:wrap;align-items:stretch">
                  <input type="email" id="studio_email_not_connected" value="{{ $studioEmail }}" placeholder="studio@example.com" class="form-input" style="flex:1;min-width:180px" autocomplete="email" readonly>
                  <button type="button" id="editStudioEmailBtn" class="btn sm ghost">Edit</button>
                  <button type="button" id="cancelStudioEmailEditBtn" class="btn sm ghost hidden">Cancel</button>
    </div>
  </div>
              <button type="button" id="payStudioReminder" class="btn pri" style="margin-bottom:14px">Resend invitation</button>
              @include('onboarding.partials.studio-split-relationship', [
                'fieldSuffix' => '_nc',
                'percentId' => 'studio_revenue_you_get_nc',
                'saveBtnId' => 'saveStudioSplitReminder',
                'showSaveButton' => true,
                'hintId' => null,
                'hintText' => 'Enter 0 if the studio collects the entire payment for your bookings.',
                'studioRevenueArtistPercent' => $studioRevenueArtistPercent,
                'studioRelationshipSelected' => $studioRelationshipSelected,
                'studioRelationshipTypes' => $studioRelationshipTypes,
              ])
</div>

            <div id="studioPayoutConnected" class="{{ $studioPayoutStatus !== 'connected' ? 'hidden' : '' }}">
              <span class="pill g" style="margin-bottom:10px">Connected</span>
              @if ($studioStripeNeedsAction ?? false)
                <p class="faint" style="font-size:13px;margin:10px 0">Your studio's bank account is connected, but Stripe needs more information from your studio before you can receive payments.</p>
                      @else
                <p class="faint" style="font-size:13px;margin:10px 0">Your studio's bank account is connected{{ $artistStripeConnected ? ' and your Stripe account is linked' : '' }}. You're ready to receive payments.</p>
                      @endif
              @if ($studioEmail)
                <div style="margin-top:10px">
                  <div class="lbl">Studio email</div>
                  <div style="font-weight:600;margin-top:4px">{{ $studioEmail }}</div>
      </div>
              @endif
              @if ($ud->studio_revenue_artist_percent !== null)
                <div style="margin-top:10px">
                  <div class="lbl">Revenue split</div>
                  <div style="font-weight:600;margin-top:4px">You {{ (int) $ud->studio_revenue_artist_percent }}% · Studio {{ 100 - (int) $ud->studio_revenue_artist_percent }}%</div>
      </div>
                    @endif
              @if (! empty($ud->studio_relationship_type) && isset($studioRelationshipTypes[$ud->studio_relationship_type]))
                <div style="margin-top:10px">
                  <div class="lbl">Relationship type</div>
                  <div style="font-weight:600;margin-top:4px">{{ $studioRelationshipTypes[$ud->studio_relationship_type][0] }}</div>
                  </div>
              @endif
              @if ($studioStripeNeedsAction ?? false)
                <button type="button" id="studioRequirementsReminderBtn" class="btn pri" style="margin-top:12px">
                  <span class="ms">mail</span>Ask studio to complete required information
        </button>
                <p id="studioRequirementsReminderMsg" class="help hidden"></p>
              @endif
      </div>

            @if ($showStudioDisconnect)
              <div id="studioPayoutDisconnectWrap" style="margin-top:16px;padding-top:14px;border-top:1px solid var(--line)">
                <button type="button" id="disconnectStudioBtn" class="btn sm ghost" style="color:#C62828;border-color:#F3C9C9">Disconnect studio payout</button>
                <p class="help">Cancel this studio request. You stay on Studio and can invite again or switch to Artist anytime.</p>
    </div>
            @endif

            <p id="studio_email_error" class="help hidden" style="color:var(--red)"></p>
            <p id="studio_revenue_artist_percent_error" class="help hidden" style="color:var(--red)"></p>
            <p id="studio_relationship_type_error" class="help hidden" style="color:var(--red)"></p>
  </div>
                      </div>
                      </div>
                      </div>
    @endif
  </div>

  {{-- Payment account --}}
  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Payment account</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Every artist connects their own Stripe account</div>
                      </div>
      <button type="button" class="btn sm ghost" id="changePayoutAccountBtn">
        <span class="ms" style="font-size:17px">swap_horiz</span>Change payout account
      </button>
                    </div>
    <div style="padding:18px 22px" id="paymentAccountMount">
      <div id="payoutArtistBankSection">
        <p id="payoutArtistBankSubtitle" class="hidden"></p>

        @if ($payoutWaitingListCountry)
          <div class="note" style="border-style:solid;border-color:#C8E6C9;background:#E8F5E9;color:#1B5E20">
            <span class="ms">schedule</span>
            <div>
              <b>Your country isn't supported yet</b>
              <div class="help" style="color:inherit;margin-top:4px">We'll notify you at <strong>{{ auth()->user()->email }}</strong> when payouts become available.</div>
                  </div>
                </div>
        @elseif (! $hasSignupPayoutCountry)
          <div class="dwarn" style="margin:0">
            <span class="ms">error</span>
            <div style="flex:1">
              <b>Payout country missing</b>
              <div style="font-size:13px;margin-top:3px;line-height:1.5">We need the country you chose at signup to set up Stripe payouts. Please contact support if this looks wrong.</div>
              </div>
            </div>
        @else
          <div id="artistPayoutNotConnected" class="{{ $artistStripeConnected ? 'hidden' : '' }}">
            @php
              $activeDisconnectedAt = $activePayout?->disconnected_at;
              $activeDisconnectedLabel = $activeDisconnectedAt
                ? $activeDisconnectedAt->timezone(config('app.timezone'))->format('d/m/Y')
                : null;
@endphp
            <div class="row" style="gap:14px;flex-wrap:wrap">
              <div class="ic" style="background:#FDECEC;color:#C62828"><span class="ms">account_balance</span></div>
              <div style="flex:1;min-width:180px">
                <div class="row" style="gap:8px;flex-wrap:wrap">
                  <b>Stripe · bank account</b>
                  <span class="pill r">{{ $activeDisconnectedLabel ? 'Disconnected' : 'Not connected' }}</span>
    </div>
                <div class="faint" style="font-size:12.5px;margin-top:2px">
                  @if ($activeDisconnectedLabel)
                    Disconnected on {{ $activeDisconnectedLabel }}. Connect again to get paid.
                  @else
                    Connect again to get paid.
                  @endif
    </div>
              </div>
              <button type="button" class="btn sm dconn" id="connectBankAccountBtn">
                <span class="ms" style="font-size:16px">link</span>Connect
              </button>
            </div>
        </div>

          <div id="artistPayoutConnected" class="{{ $artistStripeConnected ? '' : 'hidden' }}">
            <div class="row" style="gap:14px;flex-wrap:wrap">
              <div class="ic"><span class="ms">account_balance</span></div>
              <div style="flex:1;min-width:180px">
                <div class="row" style="gap:8px;flex-wrap:wrap">
                  <b>Stripe · bank account</b>
                  <span class="pill g">Connected</span>
      </div>
                <div class="faint" style="font-size:12.5px;margin-top:2px">
                  @if ($payoutMode === 'automatic')
                    Payouts are sent automatically when funds become available
            @else
                    Request a payout anytime once your balance is available
    @endif
    </div>
                <p id="artistConnectedNeedsActionMessage" class="help {{ $artistStripeNeedsAction ? '' : 'hidden' }}" style="color:var(--amber)">Your Stripe account is connected, but Stripe needs more information before you can receive payments.</p>
                <p id="artistConnectedReadyMessage" class="help {{ $artistStripeNeedsAction ? 'hidden' : '' }}" style="display:none"></p>
                <span id="artistConnectedCountryWrap" class="{{ ($payoutBankCountry && ($payoutBankCountryName ?? null)) ? '' : 'hidden' }}">
                  <span class="help">Bank account country: <strong id="artistConnectedCountryName">{{ $payoutBankCountryName }}</strong></span>
                </span>
        </div>
              <button type="button" class="btn sm ghost" id="disconnectStripeBtn" style="color:#C62828;border-color:#F3C9C9">Disconnect</button>
            </div>
            <a
              id="artistStripeRequirementsBtn"
              href="{{ route('settings.payment.stripe.requirements') }}"
              class="btn pri {{ $artistStripeNeedsAction ? '' : 'hidden' }}"
              style="margin-top:14px"
            >
              <span class="ms">assignment</span>Complete required information
            </a>
            <p id="artistStripeSavingMessage" class="help hidden">Saving your payout connection…</p>
      </div>

          <div id="artistStripeSetupSection" class="hidden" style="margin-top:18px">
            <div id="payoutStripeStep" class="hidden">
              <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--line)">
                <p id="payoutStripeStepTitle" style="font-weight:700;margin:0">Complete your payout details</p>
                <p id="payoutStripeStepDescription" class="faint" style="font-size:13px;margin:6px 0 0">
                  Add your personal details, upload an identity document (passport or ID).
                </p>
        </div>
              @if (!($stripeConnectConfigured ?? false))
                <div class="dwarn" style="margin:0">
                  <span class="ms">error</span>
                  <div style="flex:1">Stripe is not configured. Please contact support.</div>
      </div>
              @else
                <div class="stripe-connect-shell">
                  <div id="stripeConnectMount"></div>
    </div>
                <p id="stripe_connect_error" class="help hidden" style="color:var(--red)"></p>
                <p id="stripeConnectHint" class="help">Complete all Stripe steps. Your connection status will update automatically when you are done.</p>
              @endif
    </div>
  </div>
        @endif

        @php
          $disconnectedPayouts = $disconnectedPayouts ?? collect();
@endphp
        @if ($disconnectedPayouts->isNotEmpty())
          <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
            <div class="faint" style="font-size:12.5px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;margin-bottom:12px">
              Other accounts
          </div>
            <div style="display:flex;flex-direction:column;gap:12px">
              @foreach ($disconnectedPayouts as $oldPayout)
                @php
                  $oldCountry = $oldPayout->payout_bank_country
                    ? \App\Support\StripeConnectCountries::nameFor($oldPayout->payout_bank_country)
                    : null;
                  $oldWhen = $oldPayout->disconnected_at ?? $oldPayout->updated_at;
                  $oldWhenLabel = $oldWhen
                    ? $oldWhen->timezone(config('app.timezone'))->format('d/m/Y')
                    : null;
                @endphp
                <div class="row" style="gap:14px;flex-wrap:wrap;opacity:.92">
                  <div class="ic" style="background:#F0EDF2;color:var(--muted)"><span class="ms">account_balance</span></div>
                  <div style="flex:1;min-width:180px">
                    <div class="row" style="gap:8px;flex-wrap:wrap">
                      <b>
                        Stripe · bank account
                        @if ($oldCountry)
                          · {{ $oldCountry }}
                        @endif
                      </b>
                      <span class="pill r">Disconnected</span>
    </div>
                    <div class="faint" style="font-size:12.5px;margin-top:2px">
                      @if ($oldWhenLabel)
                        Disconnected on {{ $oldWhenLabel }}. Bookings already paid to this account still pay out here until they finish.
        @else
                        Previous payout account. Bookings already paid to this account still pay out here until they finish.
                      @endif
                    </div>
                  </div>
            <button
              type="button"
                    class="btn sm ghost js-activate-payout-account"
                    data-payout-id="{{ $oldPayout->id }}"
                    data-country="{{ $oldCountry ?? '' }}"
            >
                    <span class="ms" style="font-size:16px">check_circle</span>Activate this account
            </button>
          </div>
              @endforeach
              </div>
              </div>
            @endif
    </div>
        </div>
          </div>

</form>
          </div>

<div id="disconnectStripeModal" class="pay-ov hidden" role="dialog" aria-modal="true">
  <div style="background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);padding:22px;position:relative">
    <h2 style="font-size:20px;font-weight:800;margin:0 0 8px">Disconnect your bank account?</h2>
    <p style="color:#3a3a3a;line-height:1.55;margin:0 0 18px">Clients can't pay deposits and you won't receive payouts until you connect a bank account again. Money already on its way still arrives.</p>
    <div class="row" style="gap:10px">
      <button type="button" id="cancelDisconnectStripe" class="btn ghost" style="flex:1;justify-content:center">Cancel</button>
      <button type="button" id="confirmDisconnectStripeBtn" class="btn" style="flex:1;justify-content:center;background:#C62828;color:#fff">Disconnect</button>
              </div>
              </div>
            </div>

<div id="disconnectStudioModal" class="pay-ov hidden" role="dialog" aria-modal="true">
  <div style="background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);padding:22px">
    <h2 style="font-size:20px;font-weight:800;margin:0 0 8px">Disconnect studio payout?</h2>
    <p style="color:#3a3a3a;line-height:1.55;margin:0 0 18px">This cancels the request to your studio. You stay on Studio payout and can invite again or switch to Artist anytime.</p>
    <div class="row" style="gap:10px">
      <button type="button" id="cancelDisconnectStudio" class="btn ghost" style="flex:1;justify-content:center">Cancel</button>
      <button type="button" id="confirmDisconnectStudioBtn" class="btn" style="flex:1;justify-content:center;background:#C62828;color:#fff">Disconnect</button>
    </div>
                </div>
              </div>

<div id="activatePayoutAccountModal" class="pay-ov hidden" role="dialog" aria-modal="true" aria-labelledby="activatePayoutAccountTitle">
  <div style="background:#fff;border-radius:22px;width:420px;max-width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);padding:22px">
    <h2 id="activatePayoutAccountTitle" style="font-size:20px;font-weight:800;margin:0 0 8px">Activate this account?</h2>
    <p id="activatePayoutAccountMessage" style="color:#3a3a3a;line-height:1.55;margin:0 0 18px">Your current payout account will be disconnected. New bookings will use this Stripe account.</p>
    <div class="row" style="gap:10px">
      <button type="button" id="cancelActivatePayoutAccount" class="btn ghost" style="flex:1;justify-content:center">Cancel</button>
      <button type="button" id="confirmActivatePayoutAccountBtn" class="btn" style="flex:1;justify-content:center">Activate</button>
                </div>
                </div>
              </div>

@php
  $changeStudioLabel = $studioDisplayName !== '' ? $studioDisplayName : '';
  $changeDefaultYou = 50;
  $changeDefaultStudio = 50;
@endphp

@if (! $isStudioSplit || $hasMultiplePayoutStudios)
<div id="changeHowPaidModal" class="pay-ov hidden" role="dialog" aria-modal="true" aria-labelledby="changeHowPaidTitle" data-change-mode="to-studio" data-user-studio-id="">
  <div class="pay-modal">
    <div class="pay-mh">
      <div>
        <div class="ic" style="background:#F3E8FF;color:#3E007C;margin-bottom:12px"><span class="ms">swap_horiz</span></div>
        <h2 id="changeHowPaidTitle">Change how I get paid</h2>
        <div class="muted" id="changeHowPaidSub" style="font-size:13.5px;margin-top:4px">
          @if ($changeStudioLabel !== '')
            You work at {{ $changeStudioLabel }}
              @else
            Switch to a studio revenue split
              @endif
            </div>
          </div>
      <button type="button" class="pay-x js-close-change-how" aria-label="Close">×</button>
      </div>
    <div class="pay-mb">
      <div class="sect" style="margin:0 0 14px">
        <div class="lbl">Now</div>
        <div class="row" style="gap:8px;margin-top:6px;flex-wrap:wrap">
          <b>Artist - Direct payment</b>
          <span class="faint">You get 100%</span>
          <span class="pill g">Active</span>
    </div>
      </div>
      <b style="display:block;font-size:15px;margin-bottom:10px">Change to</b>
      <label class="po sel">
        <input type="radio" name="change_po" value="split" checked>
        <div style="flex:1">
          <b>Studio - Revenue split</b>
          <div class="d">Invite your studio by email. Once they connect and confirm, each payment is split between you. Your Stripe account stays connected.</div>
        </div>
      </label>
      <div class="splitbox" id="changeInviteSplitbox">
        <div class="row" style="gap:12px;align-items:flex-start">
          <div class="stepn">1</div>
          <div style="flex:1;min-width:0">
            <b style="font-size:14px">Invite the studio</b>
            <div class="grid-2">
          <div>
                <span class="fl">Studio name</span>
                <input class="in" id="changeInviteStudioName" type="text" value="{{ $changeStudioLabel }}" placeholder="Studio name">
          </div>
              <div>
                <span class="fl">Email</span>
                <input class="in" id="changeInviteStudioEmail" type="email" value="{{ $studioEmail }}" placeholder="studio@example.com" autocomplete="email">
        </div>
            </div>
            <div class="help">They get an email to join Bookpay and set up their Stripe account. The invite is sent when you press Send invite.</div>
          </div>
        </div>
        <div class="row" style="gap:12px;align-items:flex-start;margin-top:16px">
          <div class="stepn">2</div>
          <div style="flex:1;min-width:0">
            <b style="font-size:14px">Enter the revenue split</b>
            <div class="row" style="gap:28px;margin-top:8px;flex-wrap:wrap;align-items:flex-end">
          <div>
                <span class="fl">You get</span>
                <div class="pct">
                  <input class="in" id="changeYouPct" type="number" min="0" max="100" value="{{ $changeDefaultYou }}" inputmode="numeric">%
            </div>
          </div>
              <div>
                <span class="fl">Studio gets</span>
                <div id="changeThemPct" style="font-size:20px;font-weight:800;padding:8px 0">{{ $changeDefaultStudio }}%</div>
        </div>
            </div>
            <div class="help" id="changeInviteSplitHelp">You're paid directly until the studio joins Bookpay and confirms this split. Then it starts, for new bookings.</div>
          </div>
        </div>
      </div>
      <p id="changeHowPaidError" class="help hidden" style="color:var(--red);margin-top:10px"></p>
    </div>
    <div class="pay-mf">
      <button type="button" class="btn ghost js-close-change-how">Cancel</button>
      <button type="button" class="btn" id="changeHowPaidSendBtn"><span class="ms">send</span>Send invite</button>
    </div>
  </div>
</div>
@endif
@if ($isStudioSplit && ! $hasMultiplePayoutStudios)
<div id="changeHowPaidModal" class="pay-ov hidden" role="dialog" aria-modal="true" aria-labelledby="changeHowPaidTitle" data-change-mode="to-artist">
  <div class="pay-modal">
    <div class="pay-mh">
            <div>
        <div class="ic" style="background:#F3E8FF;color:#3E007C;margin-bottom:12px"><span class="ms">swap_horiz</span></div>
        <h2 id="changeHowPaidTitle">Switch to direct payment?</h2>
        <div class="muted" style="font-size:13.5px;margin-top:4px">
          @if ($changeStudioLabel !== '')
            You work at {{ $changeStudioLabel }}
          @else
            You currently use a studio revenue split
          @endif
        </div>
      </div>
      <button type="button" class="pay-x js-close-change-how" aria-label="Close">×</button>
    </div>
    <div class="pay-mb">
      <div class="sect" style="margin:0 0 14px">
        <div class="lbl">Now</div>
        <div class="row" style="gap:8px;margin-top:6px;flex-wrap:wrap">
          <b>Studio - Revenue split{{ $changeStudioLabel !== '' ? ' with '.$changeStudioLabel : '' }}</b>
          <span class="faint">You {{ $youPercent }}% · Studio {{ $studioPercent }}%</span>
        </div>
      </div>
      <div class="sect" style="margin:0 0 14px;border-color:#D9C4F2;background:#FBF7FF">
        <div class="lbl">After you switch</div>
        <div class="row" style="gap:8px;margin-top:6px;flex-wrap:wrap">
          <b>Artist - Direct payment</b>
          <span class="faint">You get 100%</span>
        </div>
      </div>
      <ul class="blist">
        <li>Future bookings are paid fully to your own Stripe account.</li>
        <li>Bookings already made keep the {{ $youPercent }} / {{ $studioPercent }} split.</li>
        <li>@if ($changeStudioLabel !== ''){{ $changeStudioLabel }} will be notified. @endif You settle rent or fees with the studio outside Bookpay.</li>
        <li>To go back to a split later, use Change how I get paid.</li>
      </ul>
      @if ($artistStripeConnected)
        <div class="lockbar" style="margin-top:14px;background:#EEFAF3;color:#1F6B4E">
          <span class="ms" style="font-size:17px">check_circle</span>
          Your Stripe account is connected. Direct payments go there.
        </div>
      @else
        <div class="dwarn" style="margin-top:14px;margin-bottom:0">
          <span class="ms">error</span>
          <div style="flex:1">
            <b>Connect your Stripe account first</b>
            <div style="font-size:13px;margin-top:3px;line-height:1.5">Direct payments go to your own Stripe account. Connect it, then you can switch.</div>
          </div>
            </div>
          @endif
      <p id="changeHowPaidError" class="help hidden" style="color:var(--red);margin-top:10px"></p>
        </div>
    <div class="pay-mf">
      <button type="button" class="btn ghost js-close-change-how">Cancel</button>
      <button
        type="button"
        class="btn"
        id="changeHowPaidSendBtn"
        @if (! $artistStripeConnected) disabled style="opacity:.4;pointer-events:none" @endif
      >
        <span class="ms">swap_horiz</span>Switch to direct payment
            </button>
          </div>
      </div>
          </div>
        @endif

@if ($hasMultiplePayoutStudios)
<div id="p2Modal" class="pay-ov hidden" role="dialog" aria-modal="true" aria-labelledby="p2ModalTitle" data-act="" data-user-studio-id="" data-studio-name="" data-studio-email="" data-you-percent="100" data-studio-percent="0">
  <div class="pay-modal">
    <div class="pay-mh">
      <div>
        <h2 id="p2ModalTitle">Revenue split</h2>
        <div class="muted" id="p2ModalSub" style="font-size:13.5px;margin-top:4px"></div>
      </div>
      <button type="button" class="pay-x js-p2-close" aria-label="Close">×</button>
    </div>
    <div class="pay-mb" id="p2ModalBody"></div>
    <div class="pay-mf">
      <button type="button" class="btn ghost js-p2-close">Cancel</button>
      <button type="button" class="btn" id="p2ModalOk"></button>
  </div>
  </div>
</div>
@endif

<div
  id="askNewSplitModal"
  class="pay-ov hidden"
  role="dialog"
  aria-modal="true"
  aria-labelledby="askNewSplitTitle"
  data-user-studio-id="{{ $payoutUserStudioId ?? '' }}"
  data-studio-name="{{ $studioDisplayName !== '' ? $studioDisplayName : 'Studio' }}"
  data-you-percent="{{ $youPercent }}"
  data-studio-percent="{{ $studioPercent }}"
>
  <div class="pay-modal">
    <div class="pay-mh">
      <div>
        <h2 id="askNewSplitTitle">Ask for a new split</h2>
      </div>
      <button type="button" class="pay-x js-close-ask-new-split" aria-label="Close">×</button>
    </div>
    <div class="pay-mb">
      <div class="sect" style="margin:0 0 16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <span class="lbl" style="margin:0">Now</span>
        <b id="askNewSplitNowLabel" style="font-size:14px;font-weight:600">You {{ $youPercent }}% · Studio {{ $studioPercent }}%</b>
      </div>
      <div class="row" style="gap:14px;align-items:flex-end;flex-wrap:wrap;margin-bottom:14px">
        <div class="pct">
          <label class="fl" for="askNewSplitYouPct">You get</label>
          <div class="row" style="gap:8px;align-items:center">
            <input class="in" id="askNewSplitYouPct" type="number" min="1" max="99" value="{{ $youPercent }}" inputmode="numeric">
            <span style="font-weight:700">%</span>
          </div>
        </div>
        <div class="faint" style="font-size:14px;padding-bottom:12px" id="askNewSplitStudioGets">
          {{ ($studioDisplayName !== '' ? $studioDisplayName : 'Studio') }} gets {{ $studioPercent }}%
        </div>
      </div>
      <p class="help" id="askNewSplitHelp" style="margin:0">
        {{ ($studioDisplayName !== '' ? $studioDisplayName : 'The studio') }} accepts or declines. Until then, the current split stays. A new split applies only to bookings made after they accept.
      </p>
    </div>
    <div class="pay-mf">
      <button type="button" class="btn ghost js-close-ask-new-split">Cancel</button>
      <button type="button" class="btn" id="askNewSplitSendBtn" disabled>Send request</button>
    </div>
  </div>
</div>

@php
  $changePayoutCountries = \App\Support\StripeConnectCountries::supportedForSelect();
  $changePayoutCountryMeta = [];
  foreach (\App\Support\StripeConnectCountries::supported() as $code => $meta) {
    $changePayoutCountryMeta[$code] = [
      'name' => $meta['name'] ?? $code,
      'currency' => strtoupper((string) ($meta['currency'] ?? 'EUR')),
    ];
  }
  $currentPayoutCountry = strtoupper((string) ($payoutBankCountry ?? ''));
  if ($currentPayoutCountry === '' || ! isset($changePayoutCountryMeta[$currentPayoutCountry])) {
    $currentPayoutCountry = $changePayoutCountries[0]['code'] ?? 'GR';
  }
  $currentPayoutCurrency = $changePayoutCountryMeta[$currentPayoutCountry]['currency'] ?? 'EUR';
  $currentPayoutCurrencyLabel = $ud->currency
    ? strtoupper((string) $ud->currency)
    : $currentPayoutCurrency;
@endphp

<div id="changePayoutAccountModal" class="pay-ov pay-modal-wide hidden" role="dialog" aria-modal="true" aria-labelledby="changePayoutAccountTitle">
  <div class="pay-modal">
    <div class="pay-mh">
      <h2 id="changePayoutAccountTitle">Change payout account</h2>
      <button type="button" class="pay-x js-close-change-payout-account" aria-label="Close">×</button>
    </div>
    <div class="pay-mb" id="changePayoutAccountBody">
      <p style="margin:0 0 12px;color:var(--muted);font-size:13.5px;line-height:1.5">Connect a new Stripe account, for example after moving to another country. Your currency follows the new account.</p>
      <span class="pc-fl" id="changePayoutAccountCountryLabel">Country of your new bank account</span>
      <input type="hidden" id="changePayoutAccountCountry" value="{{ $currentPayoutCountry }}">
      <div class="pc-cs" id="changePayoutAccountCountryPicker">
        <button type="button" class="pc-cs-b" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="changePayoutAccountCountryLabel">
          <span class="pc-cs-v">{{ $changePayoutCountryMeta[$currentPayoutCountry]['name'] ?? 'Select country' }}</span>
          <span class="ms" style="margin-left:auto;color:#9A929E">expand_more</span>
        </button>
        <div class="pc-cs-p" hidden>
          <input class="pc-cs-q" type="text" placeholder="Search" aria-label="Search countries" autocomplete="off">
          <div class="pc-cs-l" role="listbox"></div>
  </div>
</div>
      <div class="pc-cur" id="changePayoutAccountCurrencyNote"></div>
      <ul class="pc-ul" id="changePayoutAccountBullets"></ul>
    </div>
    <div class="pay-mf" id="changePayoutAccountFooter">
      <button type="button" class="btn ghost js-close-change-payout-account">Cancel</button>
      <button type="button" class="btn" id="changePayoutAccountContinueBtn">Continue to Stripe</button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script type="module">
import { loadConnectAndInitialize } from 'https://esm.sh/@stripe/connect-js@3.3.34/pure';

const stripeConfigured = @json($stripeConnectConfigured ?? false);
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json(route('settings.payment.stripe.session'));
const statusUrl = @json(route('settings.payment.stripe.status'));
const savePaymentUrl = @json(route('settings.payment.update'));
const initialPayoutBankCountryName = @json($payoutBankCountryName ?? null);
const stripeConnectLocale = @json($stripeConnectLocale);
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));
const initialPayoutBankCountry = @json($payoutBankCountry);
const initialWaitingListCountry = @json($payoutWaitingListCountry);
window.stripeOnboardingComplete = @json($stripeComplete);
window.payoutBankCountrySelected = @json((bool) $hasSignupPayoutCountry);

let connectInstance = null;
let onboardingMounted = false;
let stripeSessionData = null;

window.setStripeOnboardingComplete = function (complete) {
  window.stripeOnboardingComplete = !!complete;
  const hint = document.getElementById('stripeConnectHint');
  if (complete && hint) hint.classList.add('hidden');
  else if (hint) hint.classList.remove('hidden');
};

function setArtistStripeNeedsActionUi(needsAction) {
  const needs = !!needsAction;
  document.getElementById('artistConnectedReadyMessage')?.classList.toggle('hidden', needs);
  document.getElementById('artistConnectedNeedsActionMessage')?.classList.toggle('hidden', !needs);
  document.getElementById('artistStripeRequirementsBtn')?.classList.toggle('hidden', !needs);
}

function showArtistStripeConnectedUi(countryName, needsAction = false) {
  const setup = document.getElementById('artistStripeSetupSection');
  const notConnected = document.getElementById('artistPayoutNotConnected');
  const connected = document.getElementById('artistPayoutConnected');
  const countryWrap = document.getElementById('artistConnectedCountryWrap');
  const countryNameEl = document.getElementById('artistConnectedCountryName');
  const disconnectWarn = document.getElementById('payoutDisconnectedWarn');
  const howSummary = document.getElementById('howPaidSummary');

  if (setup) setup.classList.add('hidden');
  if (notConnected) notConnected.classList.add('hidden');
  if (connected) connected.classList.remove('hidden');
  if (disconnectWarn) disconnectWarn.classList.add('hidden');
  if (howSummary) howSummary.style.opacity = '';

  const resolvedCountry = countryName || initialPayoutBankCountryName || document.getElementById('payoutBankCountryName')?.textContent?.trim();
  if (resolvedCountry && countryNameEl) {
    countryNameEl.textContent = resolvedCountry;
    if (countryWrap) countryWrap.classList.remove('hidden');
  }

  setArtistStripeNeedsActionUi(needsAction);

  window.artistStripeConnected = true;
  window.stripeOnboardingComplete = true;
  window.setStripeOnboardingComplete(true);
  // Do not lock Studio out — artist can still invite a studio with Stripe connected.
}

window.lockPayoutOptions = function (activeKey) {
  window.payoutOptionLocked = true;
  window.activePayoutKey = activeKey;
};

let autoSaveArtistStripeInProgress = false;
let artistStripeAutoSaved = false;
let stripeStatusPollTimer = null;

function isStripeDetailsSubmitted(status) {
  return !!(status?.success && (status.details_submitted || status.submitted || status.complete));
}

function stripeStatusNeedsAction(status) {
  if (!status?.success) return false;
  if (status.disabled_reason) return true;
  if (Array.isArray(status.currently_due) && status.currently_due.length > 0) return true;
  return false;
}

function stopStripeStatusPolling() {
  if (stripeStatusPollTimer) {
    clearInterval(stripeStatusPollTimer);
    stripeStatusPollTimer = null;
  }
}

async function maybeFinalizeArtistStripeConnect() {
  if (autoSaveArtistStripeInProgress || artistStripeAutoSaved) {
    return;
  }

  const status = await refreshStripeStatus();
  if (!isStripeDetailsSubmitted(status)) {
    return;
  }

  stopStripeStatusPolling();

  const changing = !!window.changingPayoutAccount && !!window.pendingChangePayoutId;
  if (!changing) {
    const countryName = document.getElementById('payoutBankCountryName')?.textContent?.trim() || initialPayoutBankCountryName;
    showArtistStripeConnectedUi(countryName, stripeStatusNeedsAction(status));
  }

  const paymentType = document.getElementById('payment_type')?.value;
  if (paymentType === 'studio_account' && !changing) {
    window.studioDraftWithStripe = true;
    window.artistStripeConnected = true;
    window.location.reload();
    return;
  }

  await tryAutoSaveArtistStripe();
}

function startStripeStatusPolling() {
  stopStripeStatusPolling();
  stripeStatusPollTimer = setInterval(() => {
    maybeFinalizeArtistStripeConnect().catch(() => {});
  }, 2000);
}

async function tryAutoSaveArtistStripe() {
  if (autoSaveArtistStripeInProgress || artistStripeAutoSaved) return;

  const accountId = stripeSessionData?.account_id;
  if (!accountId) return;

  autoSaveArtistStripeInProgress = true;
  const savingMsg = document.getElementById('artistStripeSavingMessage')
    || document.getElementById('changePayoutAccountSavingMessage');
  if (savingMsg) savingMsg.classList.remove('hidden');

  const finalizeUrl = @json(route('settings.payment.finalize-change-account'));
  const changing = !!window.changingPayoutAccount && !!window.pendingChangePayoutId;

  try {
    let data;
    if (changing) {
      const res = await fetch(finalizeUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
          _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          payout_id: window.pendingChangePayoutId,
          stripe_account_id: accountId,
        }),
      });
      data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Could not activate the new payout account.');
      }
    } else {
  const fd = new FormData();
  fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
      fd.append('payment_type', document.getElementById('payment_type')?.value || 'artist_account');
  fd.append('stripe_account_id', accountId);
    const res = await fetch(savePaymentUrl, {
      method: 'POST',
      body: fd,
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
    });
      data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || (data.errors?.stripe_connect?.[0]) || 'Could not save Stripe payout connection.');
      }
    }

    artistStripeAutoSaved = true;
    stopStripeStatusPolling();
    setArtistStripeNeedsActionUi(!!data.artist_stripe_needs_action);
    if (savingMsg) savingMsg.classList.add('hidden');
    autoSaveArtistStripeInProgress = false;

    if (changing) {
      window.changingPayoutAccount = false;
      window.pendingChangePayoutId = null;
    window.location.reload();
    }
  } catch (err) {
    autoSaveArtistStripeInProgress = false;
    if (savingMsg) savingMsg.classList.add('hidden');
    const errEl = document.getElementById('changePayoutAccountStripeError')
      || document.getElementById('stripe_connect_error');
    if (errEl) {
      errEl.textContent = err.message || 'Stripe is complete, but saving failed. Please refresh the page to try again.';
      errEl.classList.remove('hidden');
    }
  }
}

async function handleStripeOnboardingExit() {
  await new Promise((resolve) => setTimeout(resolve, 800));
  await maybeFinalizeArtistStripeConnect();
}

window.tryAutoSaveArtistStripe = tryAutoSaveArtistStripe;

async function createStripeSession(options = {}) {
  const paymentType = document.getElementById('payment_type')?.value || 'artist_account';
  const payload = { payment_type: paymentType };
  const payoutId = options.payoutId || window.pendingChangePayoutId;
  if (payoutId) payload.payout_id = payoutId;
  const res = await fetch(sessionUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  if (!res.ok || !data.client_secret) throw new Error(data.message || 'Could not start Stripe onboarding.');
  stripeSessionData = data;
  if (data.payout_id) window.pendingChangePayoutId = data.payout_id;
  return data;
}

function resetStripeMount() {
  stopStripeStatusPolling();
  onboardingMounted = false;
  connectInstance = null;
  stripeSessionData = null;
  const mount = document.getElementById('stripeConnectMount');
  if (mount) mount.innerHTML = '';
}

window.resetStripeMount = resetStripeMount;
window.stopStripeStatusPolling = stopStripeStatusPolling;
window.resetArtistStripeAutoSaved = function () {
  artistStripeAutoSaved = false;
};

async function refreshStripeStatus() {
  const params = new URLSearchParams();
  if (stripeSessionData?.account_id) params.set('account_id', stripeSessionData.account_id);
  const url = params.toString() ? `${statusUrl}?${params}` : statusUrl;
  const res = await fetch(url, { headers: { Accept: 'application/json' } });
  const data = await res.json();
  if (res.ok && data.success) {
    window.setStripeOnboardingComplete(!!(data.complete || data.details_submitted || data.submitted));
  }
  return data;
}

window.refreshStripeOnboardingStatus = refreshStripeStatus;

async function mountStripeOnboarding(options = {}) {
  const container = options.container || document.getElementById('stripeConnectMount');
  const stripeStep = document.getElementById('payoutStripeStep');
  const skipStepCheck = !!options.skipStepCheck;
  const errorElId = options.errorElId || 'stripe_connect_error';
  if (!stripeConfigured || !publishableKey || !container || !window.payoutBankCountrySelected) return false;
  if (!skipStepCheck && (!stripeStep || stripeStep.classList.contains('hidden'))) return false;
  if (onboardingMounted && !options.force) return true;

  resetStripeMount();
  container.innerHTML = '<p class="text-sm text-on-surface-variant p-6">Loading Stripe onboarding…</p>';

  try {
    await createStripeSession({ payoutId: options.payoutId || window.pendingChangePayoutId || null });
    connectInstance = loadConnectAndInitialize({
      publishableKey,
      fetchClientSecret: async () => stripeSessionData.client_secret,
      locale: stripeConnectLocale || 'en-US',
      appearance: stripeConnectAppearance,
    });
    connectInstance.update({ locale: stripeConnectLocale || 'en-US' });

    const accountOnboarding = connectInstance.create('account-onboarding');
    const collectionOptions = stripeSessionData.collection_options || {};
    accountOnboarding.setCollectionOptions({
      fields: collectionOptions.fields || 'eventually_due',
      futureRequirements: collectionOptions.futureRequirements || 'include',
      ...(collectionOptions.requirements ? { requirements: collectionOptions.requirements } : {}),
    });
    accountOnboarding.setOnExit(handleStripeOnboardingExit);
    accountOnboarding.setOnStepChange(() => {
      setTimeout(() => {
        maybeFinalizeArtistStripeConnect().catch(() => {});
      }, 800);
    });

    container.innerHTML = '';
    container.appendChild(accountOnboarding);
    onboardingMounted = true;
    startStripeStatusPolling();
    return true;
  } catch (err) {
    container.innerHTML = '';
    const errEl = document.getElementById(errorElId);
    if (errEl) {
      errEl.textContent = err.message || 'Could not load Stripe onboarding.';
      errEl.classList.remove('hidden');
    }
    throw err;
  }
}

window.mountStripeOnboarding = mountStripeOnboarding;

window.mountStripeOnboardingIfNeeded = async function () {
  const setupVisible = document.getElementById('artistStripeSetupSection') && !document.getElementById('artistStripeSetupSection').classList.contains('hidden');
  const stripeStepVisible = document.getElementById('payoutStripeStep') && !document.getElementById('payoutStripeStep').classList.contains('hidden');
  if (setupVisible && stripeStepVisible && stripeConfigured && window.payoutBankCountrySelected) {
    await mountStripeOnboarding();
  }
};

window.showPayoutStep = function (step) {
  const stripe = document.getElementById('payoutStripeStep');
  if (stripe) stripe.classList.toggle('hidden', step !== 'stripe');
};

document.getElementById('connectBankAccountBtn')?.addEventListener('click', async () => {
  if (!window.payoutBankCountrySelected) {
    const errEl = document.getElementById('stripe_connect_error');
  if (errEl) {
      errEl.textContent = 'Your signup country is required before connecting Stripe.';
      errEl.classList.remove('hidden');
  }
    return;
}

  const setup = document.getElementById('artistStripeSetupSection');
  if (setup) setup.classList.remove('hidden');
    window.showPayoutStep('stripe');
    await mountStripeOnboarding();
});

window.artistStripeConnected = @json($artistStripeConnected);
window.studioDraftWithStripe = @json($studioDraftWithStripe);
window.studioPayoutCommitted = @json($studioPayoutCommitted);

window.placePayoutBankSection = function (type) {
  const bank = document.getElementById('payoutArtistBankSection');
  const mount = document.getElementById('paymentAccountMount');
  const subtitle = document.getElementById('payoutArtistBankSubtitle');
  if (bank && mount && bank.parentElement !== mount) {
    mount.appendChild(bank);
  }
  if (subtitle) {
    subtitle.textContent = type === 'studio'
      ? 'Connect your Stripe account so you can receive payouts alongside your studio.'
      : 'Connect your Stripe account so you can receive payouts.';
  }
};

window.updateSaveArtistUi = function () {
  const wrap = document.getElementById('paySaveArtistWrap');
  if (!wrap) return;
  const paymentType = document.getElementById('payment_type')?.value;
  const show = paymentType === 'artist_account'
    && !!(window.artistStripeConnected || window.stripeOnboardingComplete)
    && !!window.studioDraftWithStripe;
  wrap.classList.toggle('hidden', !show);
};

window.placePayoutBankSection(@json($payoutKey));
window.updateSaveArtistUi();

window.getStripeSessionAccountId = function () { return stripeSessionData?.account_id || null; };
</script>
<script>
  window.payoutOptionLocked = @json($payoutOptionLocked);
  window.activePayoutKey = @json($payoutKey);
  const settingsPaymentSaveUrl = @json(route('settings.payment.update'));

  window.currentPayoutMode = @json($payoutMode);

  $(function () {
    function openStripeModal() { $('#disconnectStripeModal').removeClass('hidden'); }
    function closeStripeModal() { $('#disconnectStripeModal').addClass('hidden'); }
    $('#disconnectStripeBtn').on('click', openStripeModal);
    $('#cancelDisconnectStripe').on('click', closeStripeModal);
    $('#disconnectStripeModal').on('click', function (e) { if (e.target === this) closeStripeModal(); });
    $('#confirmDisconnectStripeBtn').on('click', function () {
      closeStripeModal();
      $.ajax({
        url: @json(route('settings.payment.update')),
        type: 'POST',
        data: { _token: @json(csrf_token()), disconnect_stripe: 1 },
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          window.location.reload();
          return;
        }
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not disconnect Stripe.').removeClass('hidden');
      }).fail(function (xhr) {
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not disconnect Stripe.').removeClass('hidden');
      });
    });

    function openStudioModal() { $('#disconnectStudioModal').removeClass('hidden'); }
    function closeStudioModal() { $('#disconnectStudioModal').addClass('hidden'); }
    $('#disconnectStudioBtn').on('click', openStudioModal);
    $('#cancelDisconnectStudio').on('click', closeStudioModal);
    $('#disconnectStudioModal').on('click', function (e) { if (e.target === this) closeStudioModal(); });
    $('#confirmDisconnectStudioBtn').on('click', function () {
      closeStudioModal();
      $.ajax({
        url: @json(route('settings.payment.update')),
        type: 'POST',
        data: { _token: @json(csrf_token()), disconnect_studio: 1 },
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          window.location.reload();
          return;
        }
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not disconnect studio payouts.').removeClass('hidden');
      }).fail(function (xhr) {
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not disconnect studio payouts.').removeClass('hidden');
      });
    });

    var pendingActivatePayoutId = null;
    function openActivateModal(payoutId, countryName) {
      pendingActivatePayoutId = payoutId;
      var msg = 'Your current payout account will be disconnected. New bookings will use this Stripe account.';
      if (countryName) {
        msg = 'Your current payout account will be disconnected. New bookings will use the Stripe account in ' + countryName + '.';
      }
      $('#activatePayoutAccountMessage').text(msg);
      $('#activatePayoutAccountModal').removeClass('hidden');
    }
    function closeActivateModal() {
      pendingActivatePayoutId = null;
      $('#activatePayoutAccountModal').addClass('hidden');
      $('#confirmActivatePayoutAccountBtn').prop('disabled', false).text('Activate');
    }
    $(document).on('click', '.js-activate-payout-account', function () {
      var id = parseInt($(this).attr('data-payout-id'), 10);
      if (!id) return;
      openActivateModal(id, ($(this).attr('data-country') || '').trim());
    });
    $('#cancelActivatePayoutAccount').on('click', closeActivateModal);
    $('#activatePayoutAccountModal').on('click', function (e) { if (e.target === this) closeActivateModal(); });
    $('#confirmActivatePayoutAccountBtn').on('click', function () {
      if (!pendingActivatePayoutId) return;
      var $btn = $(this);
      $btn.prop('disabled', true).text('Activating…');
      $.ajax({
        url: @json(route('settings.payment.activate-account')),
        type: 'POST',
        data: { _token: @json(csrf_token()), payout_id: pendingActivatePayoutId },
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          window.location.reload();
          return;
        }
        closeActivateModal();
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not activate that account.').removeClass('hidden');
      }).fail(function (xhr) {
        closeActivateModal();
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not activate that account.').removeClass('hidden');
      });
    });

    function setStudioActionLoading($btn, loading, loadingText) {
      if (!$btn.length) return;
      if (loading) {
        $btn.data('original-html', $btn.html());
        $btn.prop('disabled', true).text(loadingText);
      } else {
        $btn.prop('disabled', false).html($btn.data('original-html') || $btn.text());
      }
    }

    function syncStudioRevenueSplit($input) {
      var digits = String($input.val() || '').replace(/\D/g, '').slice(0, 3);
      if (digits !== String($input.val() || '')) {
        $input.val(digits);
      }
      if (digits === '') {
        $input.closest('.studio-revenue-split').find('.js-studio-revenue-studio-gets').text('100');
        return;
      }
      var you = parseInt(digits, 10);
      if (!Number.isFinite(you)) {
        you = 0;
      }
      if (you > 100) {
        you = 100;
        $input.val('100');
      }
      $input.closest('.studio-revenue-split').find('.js-studio-revenue-studio-gets').text(String(100 - you));
    }

    $(document).on('input', '.js-studio-revenue-you-get', function () {
      syncStudioRevenueSplit($(this));
    });
    $(document).on('blur', '.js-studio-revenue-you-get', function () {
      var $input = $(this);
      if (String($input.val() || '').trim() === '') {
        $input.val('0');
      }
      syncStudioRevenueSplit($input);
    });
    $('.js-studio-revenue-you-get').each(function () {
      syncStudioRevenueSplit($(this));
    });

    $(document).on('change', '.js-studio-relationship-input', function () {
      var $card = $(this).closest('.js-studio-relationship-card');
      $card.closest('.studio-relationship-type').find('.js-studio-relationship-card').removeClass('selected');
      $card.addClass('selected');
    });

    function syncStudioPayoutSectionInputs() {
      var $notSent = $('#studioPayoutEmailNotSent');
      var $notConnected = $('#studioPayoutNotConnected');
      if (!$notSent.length && !$notConnected.length) return;
      var notSentActive = $notSent.length && !$notSent.hasClass('hidden');
      $notSent.find('input, select, textarea').prop('disabled', !notSentActive);
      $notConnected.find('input, select, textarea').prop('disabled', notSentActive);
    }
    syncStudioPayoutSectionInputs();

    function collectStudioSplitPayload($panel) {
      var $splitInput = $panel.find('.js-studio-revenue-you-get').first();
      if ($splitInput.length && String($splitInput.val() || '').trim() === '') {
        $splitInput.val('0');
        syncStudioRevenueSplit($splitInput);
      }
      var $relInput = $panel.find('.js-studio-relationship-input:checked').first();
      return {
        _token: @json(csrf_token()),
        save_studio_split: 1,
        studio_revenue_artist_percent: ($splitInput.val() || '').trim(),
        studio_relationship_type: $relInput.length ? $relInput.val() : '',
      };
    }

    $(document).on('click', '.js-save-studio-split', function () {
      var $btn = $(this);
      var $panel = $btn.closest('.studio-split-relationship-panel');
      var $alertEl = $('#payAlert');
      $('#studio_email_error, #studio_revenue_artist_percent_error, #studio_relationship_type_error').addClass('hidden').text('');
      $alertEl.addClass('hidden').text('');
      var originalHtml = $btn.html();
      $btn.prop('disabled', true).text('Saving...');
      $.ajax({
        url: settingsPaymentSaveUrl,
        type: 'POST',
        data: collectStudioSplitPayload($panel),
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-green-50 text-green-800 border border-green-200').text(data.message || 'Saved.').removeClass('hidden');
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not save.').removeClass('hidden');
      }).fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          $.each(xhr.responseJSON.errors, function (k, msgs) {
            $('#' + k + '_error').text(msgs[0]).removeClass('hidden');
          });
          var firstMsg = Object.values(xhr.responseJSON.errors)[0];
          if (firstMsg && firstMsg[0]) {
            $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(firstMsg[0]).removeClass('hidden');
          }
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save.').removeClass('hidden');
      }).always(function () {
        $btn.prop('disabled', false).html(originalHtml);
      });
    });

    $('#payStudioSend').on('click', function () {
      var $btn = $(this);
      var $alertEl = $('#payAlert');
      $('#studio_email_error, #studio_revenue_artist_percent_error, #studio_relationship_type_error').addClass('hidden').text('');
      $alertEl.addClass('hidden').text('');
      $('#payment_type').val('studio_account');
      var email = ($('#studio_email').val() || '').trim();
      if (!email) {
        $('#studio_email_error').text('Studio email is required.').removeClass('hidden');
        return;
      }
      var $splitInput = $('#studio_revenue_you_get');
      if ($splitInput.length && String($splitInput.val() || '').trim() === '') {
        $splitInput.val('0');
        syncStudioRevenueSplit($splitInput);
      }
      setStudioActionLoading($btn, true, 'Saving...');
      syncStudioPayoutSectionInputs();
      var fd = new FormData();
      fd.append('_token', @json(csrf_token()));
      fd.append('payment_type', 'studio_account');
      fd.append('studio_email', email);
      var $sendPanel = $('#studioPayoutEmailNotSent').find('.studio-split-relationship-panel').first();
      if ($sendPanel.length) {
        var splitPayload = collectStudioSplitPayload($sendPanel);
        fd.append('studio_revenue_artist_percent', splitPayload.studio_revenue_artist_percent);
        fd.append('studio_relationship_type', splitPayload.studio_relationship_type);
      }
      $.ajax({
        url: settingsPaymentSaveUrl,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          if (typeof window.lockPayoutOptions === 'function') window.lockPayoutOptions('studio');
          window.location.reload();
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not send studio email.').removeClass('hidden');
      }).fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          $.each(xhr.responseJSON.errors, function (k, msgs) {
            $('#' + (k === 'stripe_connect' ? 'stripe_connect_error' : k + '_error')).text(msgs[0]).removeClass('hidden');
          });
          var firstMsg = Object.values(xhr.responseJSON.errors)[0];
          if (firstMsg && firstMsg[0]) {
            $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(firstMsg[0]).removeClass('hidden');
          }
        } else {
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not send studio email.').removeClass('hidden');
        }
      }).always(function () {
        setStudioActionLoading($btn, false);
      });
    });

    function sendStudioInviteReminder($btn, options) {
      options = options || {};
      var $alertEl = $('#payAlert');
      var $inlineMsg = $('#resendStudioInviteMsg');
      var $emailInput = $('#studio_email_not_connected');
      var email = ($emailInput.val() || @json($studioEmail) || '').trim();
      if ($emailInput.length && $emailInput.is(':visible') && !email) {
        $('#studio_email_error').text('Studio email is required.').removeClass('hidden');
        return;
      }
      $('#studio_email_error, #studio_revenue_artist_percent_error, #studio_relationship_type_error').addClass('hidden').text('');
      $alertEl.addClass('hidden').text('');
      if ($inlineMsg.length) $inlineMsg.text('');
      var originalHtml = $btn.html();
      $btn.prop('disabled', true).text(options.sendingLabel || 'Sending…');
      var payload = { _token: @json(csrf_token()), resend_studio_email: 1 };
      if (email) {
        payload.studio_email = email;
      }
      $.ajax({
        url: settingsPaymentSaveUrl,
        type: 'POST',
        data: payload,
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          if (data.studio_email && $emailInput.length) {
            $emailInput.val(data.studio_email);
            studioEmailOriginal = data.studio_email;
            setStudioEmailEditing(false);
          }
          var okMsg = data.message || 'Invitation resent to your studio.';
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-green-50 text-green-800 border border-green-200').text(okMsg).removeClass('hidden');
          if ($inlineMsg.length) $inlineMsg.text(okMsg);
          return;
        }
        var errMsg = data.message || 'Could not resend invitation.';
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(errMsg).removeClass('hidden');
        if ($inlineMsg.length) $inlineMsg.text(errMsg);
      }).fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          $.each(xhr.responseJSON.errors, function (k, msgs) {
            $('#' + k + '_error').text(msgs[0]).removeClass('hidden');
          });
        }
        var failMsg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not resend invitation.';
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(failMsg).removeClass('hidden');
        if ($inlineMsg.length) $inlineMsg.text(failMsg);
      }).always(function () {
        $btn.prop('disabled', false).html(originalHtml);
        if (typeof updateStudioReminderButtonLabel === 'function') {
          updateStudioReminderButtonLabel();
        }
      });
    }

    $('#payStudioReminder').on('click', function () {
      sendStudioInviteReminder($(this), { sendingLabel: 'Sending…' });
    });

    $('#resendStudioInviteBtn').on('click', function () {
      sendStudioInviteReminder($(this), { sendingLabel: 'Sending…' });
    });

    $('#paySaveArtist').on('click', function () {
      var $btn = $(this);
      var $alertEl = $('#payAlert');
      var originalHtml = $btn.html();
      $('#payment_type').val('artist_account');
      $alertEl.addClass('hidden').text('');
      $btn.prop('disabled', true).text('Saving...');
      var fd = new FormData();
      fd.append('_token', @json(csrf_token()));
      fd.append('payment_type', 'artist_account');
      var accountId = (typeof window.getStripeSessionAccountId === 'function' && window.getStripeSessionAccountId())
        || @json($activePayout?->stripe_account_id ?? null);
      if (accountId) {
        fd.append('stripe_account_id', accountId);
      }
      $.ajax({
        url: settingsPaymentSaveUrl,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          window.location.reload();
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not save Artist payout.').removeClass('hidden');
      }).fail(function (xhr) {
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save Artist payout.').removeClass('hidden');
      }).always(function () {
        $btn.prop('disabled', false).html(originalHtml);
      });
    });

    $('#studioRequirementsReminderBtn').on('click', function () {
      var $btn = $(this);
      var $msg = $('#studioRequirementsReminderMsg');
      var $alertEl = $('#payAlert');
      $alertEl.addClass('hidden').text('');
      if ($msg.length) $msg.addClass('hidden').text('');
      $btn.prop('disabled', true);
      $.ajax({
        url: settingsPaymentSaveUrl,
        type: 'POST',
        data: {
          _token: @json(csrf_token()),
          resend_studio_email: 1,
          requirements_reminder: 1,
        },
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-green-50 text-green-800 border border-green-200').text(data.message || 'Requirements reminder sent to your studio.').removeClass('hidden');
          if ($msg.length) {
            $msg.text(data.message || 'Requirements reminder sent to your studio.').removeClass('hidden');
          }
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not send reminder.').removeClass('hidden');
      }).fail(function (xhr) {
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not send reminder.').removeClass('hidden');
      }).always(function () {
        $btn.prop('disabled', false);
      });
    });

    var studioEmailOriginal = $('#studio_email_not_connected').val() || '';
    function isStudioEmailEditing() {
      var $input = $('#studio_email_not_connected');
      return $input.length && !$input.prop('readonly');
    }
    function updateStudioReminderButtonLabel() {
      var $btn = $('#payStudioReminder');
      if (!$btn.length) return;
      if (!$btn.data('default-html')) {
        $btn.data('default-html', $btn.html());
      }
      var current = ($('#studio_email_not_connected').val() || '').trim().toLowerCase();
      var original = (studioEmailOriginal || '').trim().toLowerCase();
      if (isStudioEmailEditing() && current && current !== original) {
        $btn.html('<span class="material-symbols-outlined text-[18px]">send</span> Update & send email');
      } else {
        $btn.html($btn.data('default-html'));
      }
    }
    function setStudioEmailEditing(editing) {
      var $input = $('#studio_email_not_connected');
      if (!$input.length) return;
      if (editing) {
        studioEmailOriginal = $input.val() || '';
        $input.prop('readonly', false).focus();
        $('#editStudioEmailBtn').addClass('hidden');
        $('#cancelStudioEmailEditBtn').removeClass('hidden');
      } else {
        $input.val(studioEmailOriginal).prop('readonly', true);
        $('#editStudioEmailBtn').removeClass('hidden');
        $('#cancelStudioEmailEditBtn').addClass('hidden');
        $('#studio_email_error').addClass('hidden').text('');
      }
      updateStudioReminderButtonLabel();
    }
    $('#editStudioEmailBtn').on('click', function () { setStudioEmailEditing(true); });
    $('#cancelStudioEmailEditBtn').on('click', function () { setStudioEmailEditing(false); });
    $('#studio_email_not_connected').on('input', updateStudioReminderButtonLabel);
  });
</script>

<script>
  (function () {
    var askModal = document.getElementById('askNewSplitModal');
    var askYou = document.getElementById('askNewSplitYouPct');
    var askStudioGets = document.getElementById('askNewSplitStudioGets');
    var askNowLabel = document.getElementById('askNewSplitNowLabel');
    var askHelp = document.getElementById('askNewSplitHelp');
    var askSendBtn = document.getElementById('askNewSplitSendBtn');

    function askStudioName() {
      return (askModal && askModal.getAttribute('data-studio-name')) || 'Studio';
    }

    function syncAskSendBtn() {
      if (!askSendBtn || !askModal) return;
      var current = parseInt(askModal.getAttribute('data-you-percent') || '', 10);
      var next = askYou ? parseInt(askYou.value, 10) : NaN;
      askSendBtn.disabled = !(next >= 1 && next <= 99) || next === current;
    }

    function updateAskStudioGets() {
      if (!askYou || !askStudioGets) return;
      var v = parseInt(askYou.value, 10);
      var name = askStudioName();
      if (v >= 0 && v <= 100) {
        askStudioGets.textContent = name + ' gets ' + (100 - v) + '%';
      } else {
        askStudioGets.textContent = name + ' gets –';
      }
      syncAskSendBtn();
    }

    function openAskNewSplitModal(opts) {
      if (!askModal) return;
      opts = opts || {};
      var name = opts.studioName != null && String(opts.studioName).trim() !== ''
        ? String(opts.studioName).trim()
        : (askModal.getAttribute('data-studio-name') || 'Studio');
      var you = opts.youPercent != null ? parseInt(opts.youPercent, 10) : parseInt(askModal.getAttribute('data-you-percent') || '60', 10);
      var studioPct = opts.studioPercent != null ? parseInt(opts.studioPercent, 10) : parseInt(askModal.getAttribute('data-studio-percent') || '40', 10);
      if (!(you >= 0 && you <= 100)) you = 60;
      if (!(studioPct >= 0 && studioPct <= 100)) studioPct = 100 - you;

      askModal.setAttribute('data-studio-name', name);
      if (opts.userStudioId != null && String(opts.userStudioId).trim() !== '') {
        askModal.setAttribute('data-user-studio-id', String(opts.userStudioId));
      }
      askModal.setAttribute('data-you-percent', String(you));
      askModal.setAttribute('data-studio-percent', String(studioPct));

      if (askNowLabel) askNowLabel.textContent = 'You ' + you + '% · Studio ' + studioPct + '%';
      if (askYou) askYou.value = String(you);
      if (askHelp) {
        askHelp.textContent = name + ' accepts or declines. Until then, the current split stays. A new split applies only to bookings made after they accept.';
      }
      updateAskStudioGets();
      askModal.classList.remove('hidden');
    }

    function closeAskNewSplitModal() {
      if (!askModal) return;
      askModal.classList.add('hidden');
    }

    window.openAskNewSplitModal = openAskNewSplitModal;

    document.getElementById('askNewSplitBtn')?.addEventListener('click', function (e) {
      e.preventDefault();
      openAskNewSplitModal();
    });

    if (askModal) {
      askModal.querySelectorAll('.js-close-ask-new-split').forEach(function (el) {
        el.addEventListener('click', closeAskNewSplitModal);
      });
      askModal.addEventListener('click', function (e) {
        if (e.target === askModal) closeAskNewSplitModal();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !askModal.classList.contains('hidden')) closeAskNewSplitModal();
      });
      askYou?.addEventListener('input', updateAskStudioGets);
      askYou?.addEventListener('change', updateAskStudioGets);
      askSendBtn?.addEventListener('click', function () {
        if (askSendBtn.disabled) return;
        var userStudioId = askModal.getAttribute('data-user-studio-id') || '';
        var next = askYou ? parseInt(askYou.value, 10) : NaN;
        if (!userStudioId || !(next >= 1 && next <= 99)) return;

        var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
        var original = askSendBtn.innerHTML;
        askSendBtn.disabled = true;
        askSendBtn.textContent = 'Sending…';

        fetch(@json(route('settings.payment.propose-split')), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            user_studio_id: parseInt(userStudioId, 10),
            artist_percent: next
          })
        }).then(function (r) {
          return r.json().then(function (d) { return { ok: r.ok, d: d }; });
        }).then(function (res) {
          if (!res.ok || !res.d.success) {
            if (window.bpToast) window.bpToast((res.d && res.d.message) || 'Could not send request', true);
            else alert((res.d && res.d.message) || 'Could not send request');
            syncAskSendBtn();
            askSendBtn.innerHTML = original;
            return;
          }
          if (window.bpToast) window.bpToast(res.d.message || 'Request sent');
          closeAskNewSplitModal();
          window.location.reload();
        }).catch(function () {
          if (window.bpToast) window.bpToast('Could not send request', true);
          else alert('Could not send request');
          syncAskSendBtn();
          askSendBtn.innerHTML = original;
        });
      });
    }

    document.querySelectorAll('.js-p2-ask-new-split').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        openAskNewSplitModal({
          userStudioId: el.getAttribute('data-user-studio-id') || '',
          studioName: el.getAttribute('data-studio-name') || '',
          youPercent: el.getAttribute('data-you-percent'),
          studioPercent: el.getAttribute('data-studio-percent'),
        });
      });
    });
  })();
</script>

<script>
  (function () {
    var btn = document.getElementById('changeHowPaidBtn');
    var modal = document.getElementById('changeHowPaidModal');
    var saveUrl = @json(route('settings.payment.update'));
    var csrf = @json(csrf_token());
    var sending = false;
    var defaultStudioName = @json($changeStudioLabel);
    var defaultStudioEmail = @json($studioEmail);
    var defaultSplitHelp = "You're paid directly until the studio joins Bookpay and confirms this split. Then it starts, for new bookings.";

    function openChangeModal(opts) {
      if (!modal) return;
      opts = opts || {};
      var err = document.getElementById('changeHowPaidError');
      if (err) { err.classList.add('hidden'); err.textContent = ''; }
      var mode = modal.getAttribute('data-change-mode') || 'to-studio';
      if (mode === 'to-studio') {
        var name = opts.studioName != null ? opts.studioName : defaultStudioName;
        var email = opts.studioEmail != null ? opts.studioEmail : defaultStudioEmail;
        var userStudioId = opts.userStudioId != null ? String(opts.userStudioId) : '';
        modal.setAttribute('data-user-studio-id', userStudioId);
        var sub = document.getElementById('changeHowPaidSub');
        if (sub) {
          sub.textContent = name
            ? ('You work at ' + name)
            : 'Switch to a studio revenue split';
        }
        var nameInput = document.getElementById('changeInviteStudioName');
        var emailInput = document.getElementById('changeInviteStudioEmail');
        if (nameInput) nameInput.value = name || '';
        if (emailInput) emailInput.value = email || '';
        var help = document.getElementById('changeInviteSplitHelp');
        if (help) {
          help.textContent = name
            ? ("You're paid directly until " + name + " joins Bookpay and confirms this split. Then it starts, for new bookings.")
            : defaultSplitHelp;
        }
        var you = document.getElementById('changeYouPct');
        var them = document.getElementById('changeThemPct');
        if (you) you.value = String(@json($changeDefaultYou));
        if (them) them.textContent = @json($changeDefaultStudio) + '%';
      }
      modal.classList.remove('hidden');
    }
    function closeChangeModal() {
      if (!modal) return;
      modal.classList.add('hidden');
      modal.setAttribute('data-user-studio-id', '');
    }
    function showChangeError(msg) {
      var err = document.getElementById('changeHowPaidError');
      if (!err) return;
      err.textContent = msg || 'Something went wrong.';
      err.classList.remove('hidden');
    }

    window.openChangeHowPaidModal = openChangeModal;

    if (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        openChangeModal();
      });
    }

    if (modal) {
      modal.querySelectorAll('.js-close-change-how').forEach(function (el) {
        el.addEventListener('click', closeChangeModal);
      });
      modal.addEventListener('click', function (e) {
        if (e.target === modal) closeChangeModal();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeChangeModal();
      });

      var you = document.getElementById('changeYouPct');
      var them = document.getElementById('changeThemPct');
      if (you && them) {
        you.addEventListener('input', function () {
          var v = parseInt(you.value, 10);
          them.textContent = (v >= 0 && v <= 100) ? (100 - v) + '%' : '–';
        });
      }

      document.getElementById('changeHowPaidSendBtn')?.addEventListener('click', function () {
        if (sending) return;
        var mode = modal.getAttribute('data-change-mode') || 'to-studio';
        var sendBtn = document.getElementById('changeHowPaidSendBtn');
        var err = document.getElementById('changeHowPaidError');
        if (err) { err.classList.add('hidden'); err.textContent = ''; }

        if (mode === 'to-studio') {
          var email = (document.getElementById('changeInviteStudioEmail')?.value || '').trim();
          var name = (document.getElementById('changeInviteStudioName')?.value || '').trim();
          var pct = parseInt(document.getElementById('changeYouPct')?.value, 10);
          if (!email) {
            showChangeError('Studio email is required.');
            return;
          }
          if (!(pct >= 0 && pct <= 100)) {
            showChangeError('Enter a revenue split from 0 to 100.');
            return;
          }

          sending = true;
          if (sendBtn) { sendBtn.disabled = true; sendBtn.innerHTML = '<span class="ms">hourglass_top</span>Sending…'; }

          var fd = new FormData();
          fd.append('_token', csrf);
          fd.append('payment_type', 'studio_account');
          fd.append('studio_email', email);
          fd.append('studio_name', name);
          fd.append('studio_revenue_artist_percent', String(pct));
          fd.append('studio_relationship_type', 'resident');
          var userStudioId = modal.getAttribute('data-user-studio-id') || '';
          if (userStudioId) fd.append('user_studio_id', userStudioId);

          fetch(saveUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            credentials: 'same-origin',
          })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, status: res.status, data: data }; }); })
            .then(function (result) {
              if (result.data && result.data.success) {
                window.location.reload();
                return;
              }
              var msg = (result.data && result.data.message) || 'Could not send studio invite.';
              if (result.data && result.data.errors) {
                var first = Object.values(result.data.errors)[0];
                if (first && first[0]) msg = first[0];
              }
              showChangeError(msg);
            })
            .catch(function () {
              showChangeError('Could not send studio invite.');
            })
            .finally(function () {
              sending = false;
              if (sendBtn) { sendBtn.disabled = false; sendBtn.innerHTML = '<span class="ms">send</span>Send invite'; }
            });
          return;
        }

        // Studio → direct: disconnect studio payout, keep artist Stripe.
        if (!@json($artistStripeConnected)) {
          showChangeError('Connect your Stripe account first.');
          return;
        }

        sending = true;
        if (sendBtn) { sendBtn.disabled = true; sendBtn.innerHTML = '<span class="ms">hourglass_top</span>Switching…'; }

        fetch(saveUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            Accept: 'application/json',
          },
          credentials: 'same-origin',
          body: JSON.stringify({ _token: csrf, switch_to_artist: 1 }),
        })
          .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
          .then(function (result) {
            if (result.data && result.data.success) {
              window.location.reload();
              return;
            }
            showChangeError((result.data && result.data.message) || 'Could not switch to direct payment.');
          })
          .catch(function () {
            showChangeError('Could not switch to direct payment.');
          })
          .finally(function () {
            sending = false;
            if (sendBtn) { sendBtn.disabled = false; sendBtn.innerHTML = '<span class="ms">swap_horiz</span>Switch to direct payment'; }
          });
      });
    }

    function triggerConnect() {
      var primary = document.getElementById('connectBankAccountBtn');
      if (primary) {
        primary.click();
        return;
      }
    }
    document.getElementById('connectBankAccountWarnBtn')?.addEventListener('click', triggerConnect);
  })();
</script>

@if ($hasMultiplePayoutStudios)
<script>
  (function () {
    var list = document.getElementById('p2list');
    var modal = document.getElementById('p2Modal');
    if (!list || !modal) return;

    var saveUrl = @json(route('settings.payment.update'));
    var csrf = @json(csrf_token());
    var artistConnected = @json((bool) $artistStripeConnected);
    var studios = @json($payoutStudioCards);
    var sending = false;

    function otherStudioName(id) {
      for (var i = 0; i < studios.length; i++) {
        if (String(studios[i].id) !== String(id)) return studios[i].name;
      }
      return 'your other studio';
    }

    function closeP2() {
      modal.classList.add('hidden');
      modal.setAttribute('data-act', '');
      modal.setAttribute('data-user-studio-id', '');
    }

    function showP2Error(msg) {
      var err = document.getElementById('p2ModalError');
      if (!err) return;
      err.textContent = msg || 'Something went wrong.';
      err.classList.remove('hidden');
    }

    function openP2Direct(box) {
      var id = box.getAttribute('data-user-studio-id') || '';
      var name = box.getAttribute('data-studio-name') || 'Studio';
      var card = null;
      for (var i = 0; i < studios.length; i++) {
        if (String(studios[i].id) === String(id)) { card = studios[i]; break; }
      }
      var you = card ? card.you_percent : 100;
      var studioPct = card ? card.studio_percent : 0;
      var other = otherStudioName(id);
      var title = document.getElementById('p2ModalTitle');
      var sub = document.getElementById('p2ModalSub');
      var body = document.getElementById('p2ModalBody');
      var ok = document.getElementById('p2ModalOk');

      modal.setAttribute('data-act', 'direct');
      modal.setAttribute('data-user-studio-id', id);
      modal.setAttribute('data-studio-name', name);

      title.textContent = 'Switch to direct payment at ' + name;
      sub.textContent = 'Only for ' + name + '. ' + other + ' stays as it is.';
      ok.innerHTML = '<span class="ms">swap_horiz</span>Switch to direct payment';
      body.innerHTML =
        '<div class="p2now">Now: <b>Studio - Revenue split</b> with ' + name + ' · You ' + you + '% · Studio ' + studioPct + '%</div>' +
        '<ul style="font-size:13px;line-height:1.7;padding-left:18px;margin:12px 0 0">' +
        '<li>New bookings at ' + name + ' are paid fully to you.</li>' +
        '<li>Bookings already made keep the ' + you + ' / ' + studioPct + ' split.</li>' +
        '<li>' + name + ' will be notified. You settle rent or fees with them outside Bookpay.</li>' +
        '</ul>' +
        (artistConnected
          ? '<div class="lockbar" style="margin-top:14px;background:#EEFAF3;color:#1F6B4E"><span class="ms" style="font-size:17px">check_circle</span>Your Stripe account is connected. Direct payments go there.</div>'
          : '<div class="dwarn" style="margin-top:14px;margin-bottom:0"><span class="ms">error</span><div style="flex:1"><b>Connect your Stripe account first</b><div style="font-size:13px;margin-top:3px;line-height:1.5">Direct payments go to your own Stripe account. Connect it, then you can switch.</div></div></div>') +
        '<p id="p2ModalError" class="help hidden" style="color:var(--red);margin-top:10px"></p>';
      if (!artistConnected) {
        ok.disabled = true;
        ok.style.opacity = '.4';
        ok.style.pointerEvents = 'none';
      } else {
        ok.disabled = false;
        ok.style.opacity = '';
        ok.style.pointerEvents = '';
      }

      modal.classList.remove('hidden');
    }

    modal.querySelectorAll('.js-p2-close').forEach(function (el) {
      el.addEventListener('click', closeP2);
    });
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeP2();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeP2();
    });

    list.addEventListener('click', function (e) {
      var box = e.target.closest('.p2box');
      if (!box) return;

      var switchBtn = e.target.closest('.js-p2-switch-direct');
      if (switchBtn) {
        e.preventDefault();
        openP2Direct(box);
        return;
      }

      var splitBtn = e.target.closest('.js-p2-setup-split');
      if (splitBtn) {
        e.preventDefault();
        if (typeof window.openChangeHowPaidModal === 'function') {
          window.openChangeHowPaidModal({
            studioName: box.getAttribute('data-studio-name') || '',
            studioEmail: box.getAttribute('data-studio-email') || '',
            userStudioId: box.getAttribute('data-user-studio-id') || '',
          });
        }
        return;
      }

      var resendBtn = e.target.closest('.js-p2-resend');
      if (resendBtn) {
        e.preventDefault();
        if (sending) return;
        var id = box.getAttribute('data-user-studio-id') || '';
        var email = (box.getAttribute('data-studio-email') || '').trim();
        var original = resendBtn.innerHTML;
        sending = true;
        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';
        var payload = { _token: csrf, resend_studio_email: 1, user_studio_id: id };
        if (email) payload.studio_email = email;
        fetch(saveUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify(payload),
        })
          .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
          .then(function (result) {
            var alertEl = document.getElementById('payAlert');
            var msg = (result.data && result.data.message) || (result.ok ? 'Invitation resent.' : 'Could not resend invitation.');
            if (alertEl) {
              alertEl.className = 'rounded-xl px-4 py-3 text-sm mb-6 ' + (result.data && result.data.success
                ? 'bg-green-50 text-green-800 border border-green-200'
                : 'bg-red-50 text-red-800 border border-red-200');
              alertEl.textContent = msg;
              alertEl.classList.remove('hidden');
            }
          })
          .catch(function () {
            var alertEl = document.getElementById('payAlert');
            if (alertEl) {
              alertEl.className = 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200';
              alertEl.textContent = 'Could not resend invitation.';
              alertEl.classList.remove('hidden');
            }
          })
          .finally(function () {
            sending = false;
            resendBtn.disabled = false;
            resendBtn.innerHTML = original;
          });
      }
    });

    document.getElementById('p2ModalOk')?.addEventListener('click', function () {
      if (sending) return;
      var act = modal.getAttribute('data-act') || '';
      var id = modal.getAttribute('data-user-studio-id') || '';
      var ok = document.getElementById('p2ModalOk');
      var err = document.getElementById('p2ModalError');
      if (err) { err.classList.add('hidden'); err.textContent = ''; }

      if (act !== 'direct') return;
      if (!artistConnected) {
        showP2Error('Connect your Stripe account first.');
        return;
      }
      sending = true;
      if (ok) { ok.disabled = true; ok.innerHTML = '<span class="ms">hourglass_top</span>Switching…'; }
      fetch(saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ _token: csrf, switch_to_artist: 1, user_studio_id: id }),
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (result.data && result.data.success) {
            window.location.reload();
            return;
          }
          showP2Error((result.data && result.data.message) || 'Could not switch to direct payment.');
        })
        .catch(function () { showP2Error('Could not switch to direct payment.'); })
        .finally(function () {
          sending = false;
          if (ok) { ok.disabled = false; ok.innerHTML = '<span class="ms">swap_horiz</span>Switch to direct payment'; }
        });
    });
  })();
</script>
@endif

<script>
  (function () {
    var btn = document.getElementById('changePayoutAccountBtn');
    var modal = document.getElementById('changePayoutAccountModal');
    if (!btn || !modal) return;

    var countryMeta = @json($changePayoutCountryMeta);
    var countryList = @json($changePayoutCountries);
    var defaultCountry = @json($currentPayoutCountry);
    var currentCurrency = @json($currentPayoutCurrencyLabel);
    var currentLast4 = '••••';
    var changeAccountUrl = @json(route('settings.payment.change-account'));
    var csrf = @json(csrf_token());
    var body = document.getElementById('changePayoutAccountBody');
    var footer = document.getElementById('changePayoutAccountFooter');
    var countryInput = document.getElementById('changePayoutAccountCountry');
    var curNote = document.getElementById('changePayoutAccountCurrencyNote');
    var bullets = document.getElementById('changePayoutAccountBullets');
    var continueBtn = document.getElementById('changePayoutAccountContinueBtn');
    var step1Html = body ? body.innerHTML : '';
    var footerHtml = footer ? footer.innerHTML : '';
    var starting = false;

    function currencyLabel(code) {
      return code || 'EUR';
    }

    function updateNotes() {
      countryInput = document.getElementById('changePayoutAccountCountry');
      curNote = document.getElementById('changePayoutAccountCurrencyNote');
      bullets = document.getElementById('changePayoutAccountBullets');
      if (!countryInput || !curNote || !bullets) return;
      var code = countryInput.value;
      var meta = countryMeta[code] || {};
      var nextCur = meta.currency || currentCurrency;
      var changed = nextCur !== currentCurrency;
      curNote.className = 'pc-cur' + (changed ? '' : ' same');
      curNote.innerHTML = changed
        ? 'Your currency changes from <b>' + currencyLabel(currentCurrency) + '</b> to <b>' + currencyLabel(nextCur) + '</b>.'
        : 'Your currency stays <b>' + currencyLabel(currentCurrency) + '</b>.';
      var items = [
        '<li><span class="ms">history</span><span>Bookings already paid stay in ' + currencyLabel(currentCurrency) + ' and pay out to your current account (' + currentLast4 + ') until they\'re finished.</span></li>',
        '<li><span class="ms">event_available</span><span>New bookings use ' + currencyLabel(changed ? nextCur : currentCurrency) + ' once your new account is connected.</span></li>'
      ];
      if (changed) {
        items.push('<li><span class="ms">sell</span><span>Your rates, deposit and design prices are converted at today\'s rate. You check them before they change.</span></li>');
      } else {
        items.push('<li><span class="ms">sell</span><span>Your prices don\'t change.</span></li>');
      }
      bullets.innerHTML = items.join('');
    }

    function bindCountryPicker() {
      var root = document.getElementById('changePayoutAccountCountryPicker');
      countryInput = document.getElementById('changePayoutAccountCountry');
      if (!root || !countryInput) return;

      var b = root.querySelector('.pc-cs-b');
      var p = root.querySelector('.pc-cs-p');
      var q = root.querySelector('.pc-cs-q');
      var l = root.querySelector('.pc-cs-l');
      var v = root.querySelector('.pc-cs-v');
      var kb = -1;

      l.innerHTML = countryList.map(function (c) {
        var cur = (countryMeta[c.code] && countryMeta[c.code].currency) || '';
        return '<div class="pc-cs-o" role="option" tabindex="-1" data-v="' + c.code + '"><span>' + c.name + '</span><small>' + cur + '</small></div>';
      }).join('') + '<div class="pc-cs-none" hidden>No matches</div>';

      var os = [].slice.call(l.querySelectorAll('.pc-cs-o'));
      var none = l.querySelector('.pc-cs-none');

      function vis() { return os.filter(function (o) { return !o.hidden; }); }
      function hl(i) {
        var vs = vis();
        os.forEach(function (o) { o.classList.remove('kb'); });
        kb = Math.max(0, Math.min(i, vs.length - 1));
        if (vs[kb]) {
          vs[kb].classList.add('kb');
          vs[kb].scrollIntoView({ block: 'nearest' });
        }
      }
      function filt() {
        var t = q.value.trim().toLowerCase();
        var n = 0;
        os.forEach(function (o) {
          var label = o.textContent.toLowerCase();
          var show = !t || label.indexOf(t) > -1;
          o.hidden = !show;
          if (show) n++;
        });
        none.hidden = n > 0;
        kb = -1;
      }
      function openDrop() {
        p.hidden = false;
        b.setAttribute('aria-expanded', 'true');
        q.value = '';
        filt();
        var s = l.querySelector('.pc-cs-o.sel');
        if (s) s.scrollIntoView({ block: 'nearest' });
        setTimeout(function () { q.focus(); }, 0);
      }
      function closeDrop() {
        p.hidden = true;
        b.setAttribute('aria-expanded', 'false');
      }
      function set(code, silent) {
        countryInput.value = code || '';
        os.forEach(function (x) {
          var on = x.dataset.v === code;
          x.classList.toggle('sel', on);
          x.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        var selected = os.find(function (x) { return x.dataset.v === code; });
        var name = selected
          ? selected.querySelector('span').textContent
          : ((countryMeta[code] && countryMeta[code].name) || 'Select country');
        v.textContent = name;
        v.classList.toggle('pc-cs-ph', !code);
        if (!silent) updateNotes();
      }

      b.addEventListener('click', function (e) {
        e.stopPropagation();
        p.hidden ? openDrop() : closeDrop();
      });
      b.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openDrop();
        }
      });
      q.addEventListener('input', filt);
      q.addEventListener('keydown', function (e) {
        var vs = vis();
        if (e.key === 'Escape') {
          e.stopPropagation();
          closeDrop();
          b.focus();
        } else if (e.key === 'ArrowDown') {
          e.preventDefault();
          hl(kb + 1);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          hl(kb - 1);
        } else if (e.key === 'Enter' && vs.length) {
          e.preventDefault();
          set((vs[kb] || vs[0]).dataset.v);
          closeDrop();
          b.focus();
        }
      });
      os.forEach(function (o) {
        o.addEventListener('click', function () {
          set(o.dataset.v);
          closeDrop();
          b.focus();
        });
      });
      document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) closeDrop();
      });

      set(countryInput.value || defaultCountry, true);
    }

    function showStripeStep(countryName) {
      modal.classList.add('pay-modal-stripe');
      if (footer) footer.classList.add('hidden');
      if (body) {
        body.innerHTML =
          '<div style="margin-bottom:12px">' +
            '<b style="display:block;font-size:15px">Connect Stripe in ' + countryName + '</b>' +
            '<p class="faint" style="font-size:13px;margin:6px 0 0;line-height:1.5">Complete Stripe onboarding for your new bank account. Your current payout stays active until you finish.</p>' +
          '</div>' +
          '<div class="stripe-connect-shell"><div id="changePayoutAccountStripeMount"></div></div>' +
          '<p id="changePayoutAccountStripeError" class="help hidden" style="color:var(--red)"></p>' +
          '<p id="changePayoutAccountSavingMessage" class="help hidden">Saving your new payout connection…</p>';
      }
    }

    async function startChangeAccount() {
      if (starting) return;
      countryInput = document.getElementById('changePayoutAccountCountry');
      var pick = countryInput ? countryInput.value : '';
      var name = (countryMeta[pick] && countryMeta[pick].name) || pick;
      if (!pick) return;

      starting = true;
      window.changingPayoutAccount = true;
      window.pendingChangePayoutId = null;
      if (typeof window.resetArtistStripeAutoSaved === 'function') {
        window.resetArtistStripeAutoSaved();
      }
      if (footer) footer.classList.add('hidden');
      if (body) {
        body.innerHTML = '<div class="pc-wait" role="status"><div class="pc-spin"></div><b style="color:var(--ink)">Opening Stripe…</b><span style="font-size:13px">Preparing a new payout account in ' + name + '.</span></div>';
      }

      try {
        var res = await fetch(changeAccountUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            Accept: 'application/json',
          },
          credentials: 'same-origin',
          body: JSON.stringify({ _token: csrf, payout_bank_country: pick }),
        });
        var data = await res.json();
        if (!res.ok || !data.success) {
          throw new Error(data.message || (data.errors && data.errors.payout_bank_country && data.errors.payout_bank_country[0]) || 'Could not start payout account change.');
        }

        window.pendingChangePayoutId = data.payout_id || null;
        window.payoutBankCountrySelected = true;

        showStripeStep(data.payout_bank_country_name || name);
        var mount = document.getElementById('changePayoutAccountStripeMount');
        if (typeof window.mountStripeOnboarding !== 'function') {
          throw new Error('Stripe onboarding is not available on this page.');
        }
        var mounted = await window.mountStripeOnboarding({
          container: mount,
          skipStepCheck: true,
          force: true,
          errorElId: 'changePayoutAccountStripeError',
          payoutId: window.pendingChangePayoutId,
        });
        if (!mounted) {
          throw new Error('Could not load Stripe onboarding. Please try again.');
        }
      } catch (err) {
        window.changingPayoutAccount = false;
        window.pendingChangePayoutId = null;
        showStep1();
        var errBox = document.createElement('p');
        errBox.className = 'help';
        errBox.style.color = 'var(--red)';
        errBox.style.marginTop = '10px';
        errBox.textContent = err.message || 'Could not start payout account change.';
        if (body) body.appendChild(errBox);
      } finally {
        starting = false;
      }
    }

    function showStep1() {
      modal.classList.remove('pay-modal-stripe');
      if (body) body.innerHTML = step1Html;
      if (footer) {
        footer.classList.remove('hidden');
        footer.innerHTML = footerHtml;
      }
      countryInput = document.getElementById('changePayoutAccountCountry');
      curNote = document.getElementById('changePayoutAccountCurrencyNote');
      bullets = document.getElementById('changePayoutAccountBullets');
      continueBtn = document.getElementById('changePayoutAccountContinueBtn');
      bindCountryPicker();
      if (continueBtn) {
        continueBtn.addEventListener('click', function () {
          startChangeAccount();
        });
      }
      modal.querySelectorAll('.js-close-change-payout-account').forEach(function (el) {
        el.addEventListener('click', close);
      });
      updateNotes();
    }

    function open() {
      window.changingPayoutAccount = false;
      window.pendingChangePayoutId = null;
      showStep1();
      modal.classList.remove('hidden');
      var trigger = modal.querySelector('.pc-cs-b');
      if (trigger) trigger.focus();
    }
    function close() {
      // Closing mid-flow keeps the current payout active; pending draft stays inactive until finished.
      if (window.changingPayoutAccount) {
        window.changingPayoutAccount = false;
        window.pendingChangePayoutId = null;
        if (typeof window.resetStripeMount === 'function') {
          window.resetStripeMount();
        } else if (typeof window.stopStripeStatusPolling === 'function') {
          window.stopStripeStatusPolling();
        }
      }
      modal.classList.add('hidden');
      showStep1();
    }

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      open();
    });
    modal.addEventListener('click', function (e) {
      if (e.target === modal) close();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape' || modal.classList.contains('hidden')) return;
      var drop = modal.querySelector('.pc-cs-p');
      if (drop && !drop.hidden) {
        drop.hidden = true;
        var trigger = modal.querySelector('.pc-cs-b');
        if (trigger) {
          trigger.setAttribute('aria-expanded', 'false');
          trigger.focus();
        }
        e.stopPropagation();
        return;
      }
      close();
    });
  })();
</script>

@endsection
