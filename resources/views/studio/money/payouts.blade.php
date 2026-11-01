@extends('layouts.studio-dashboard-layout')

@section('title', 'Money · Payouts')

@php
  $offersRevenueSplit = (bool) ($offersRevenueSplit ?? true);
  $offersWorkstationRent = (bool) ($offersWorkstationRent ?? true);
  $defaultRevenueArtistPercent = max(1, min(99, (int) ($defaultRevenueArtistPercent ?? 60)));
  $stripeConnected = (bool) ($stripeConnected ?? false);
  $stripeConnectConfigured = (bool) ($stripeConnectConfigured ?? false);
  $stripeLast4 = trim((string) ($stripeLast4 ?? ''));
  $businessType = in_array(($businessType ?? 'company'), ['company', 'individual'], true) ? $businessType : 'company';
  $currentCountry = strtoupper((string) ($currentCountry ?? 'GR'));
  $currentCurrency = strtoupper((string) ($currentCurrency ?? 'EUR'));
  $stripeCountries = $stripeCountries ?? [];
  $stripeMeta = collect([
      $stripeDisplayName ?? $studioName ?? null,
      $stripeCountryName ?? null,
      $stripeCurrency ?? null,
  ])->filter(fn ($v) => trim((string) $v) !== '')->implode(' · ');
@endphp

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .money-page .ic{width:38px;height:38px;border-radius:10px;background:var(--chip);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .money-page .ic .ms{font-size:20px}
  .money-page .pill{gap:5px}
  .money-page .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .money-page .pill.nd:before{display:none}
  .money-page .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer;display:inline-block}
  .money-page .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
  .money-page .tog.on{background:var(--pri)}.money-page .tog.on:after{left:19px}
  .money-page .split{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
  .money-page .split .box{border:1px solid var(--line);border-radius:12px;padding:10px 14px;background:#fff}
  .money-page .split .box span{display:block;font-size:12px;color:var(--muted);font-weight:600}
  .money-page .split input{border:0;font:inherit;font-size:20px;font-weight:800;width:70px;outline:none;background:transparent}
  .money-page .split .calc{font-size:20px;font-weight:800;display:block;margin-top:2px}
  .money-page .note.info{border-style:solid;background:#EEF4FF;border-color:#C9DAF8;color:#1E4E9A}
  .money-page .note.info .ms{color:#1E5BD8}
  .money-page .note.err{background:#FDECEC;border-color:#F5C2C2;color:#C62828}
  .money-page .note.err .ms{color:#C62828}
  .money-page .note[hidden]{display:none!important}
  .money-page .ch>div h3{margin:0;font-size:16px;font-weight:700}
  .money-page .btn.disabled,.money-page .btn[aria-disabled="true"]{opacity:.4;pointer-events:none}
  .money-page .pgsave{opacity:.4;pointer-events:none}
  .money-page .pgsave.is-dirty{opacity:1;pointer-events:auto}
  .money-page .field-err{display:none;color:#C62828;font-size:12px;margin-top:6px}
  .money-page .field-err.on{display:block}
  .money-page .stripe-connect-row{flex-wrap:wrap}
  .money-page .stripe-connect-row .btn{margin-left:auto}
  .money-page #paymentAccountCard{overflow:visible}
  .money-page .stripe-connect-shell{
    width:100%;max-width:100%;min-width:0;margin:8px -12px 0;padding:8px 12px 12px;
    overflow-x:auto;overflow-y:visible;-webkit-overflow-scrolling:touch
  }
  .money-page #studioStripeConnectMount{
    min-height:420px;width:100%;max-width:100%;min-width:0;background:#fff;overflow:visible;box-sizing:border-box;padding:4px 2px 8px
  }
  .money-page #studioStripeConnectMount > *{display:block;width:100%!important;max-width:100%!important;min-width:0!important;box-sizing:border-box}
  .money-page select.js-select2{width:100%}
  .money-page .select2-container{width:100%!important;z-index:1}
  .money-page .select2-container--open{z-index:10060!important}
  .select2-container--default .select2-selection--single{
    min-height:40px;padding:4px 10px;border-radius:10px;border:1px solid var(--line)!important;background:#fff!important
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:2rem;padding-left:4px;color:var(--ink);font-size:13.5px
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow{height:40px}
  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--pri)!important;box-shadow:0 0 0 3px #F3E8FF
  }
  .select2-dropdown{border-radius:10px;border-color:var(--line);overflow:hidden;font-family:inherit;font-size:13.5px}
  .select2-container--default .select2-results__option--highlighted[aria-selected]{background-color:var(--pri)!important}
  .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:60;padding:24px}
  .smodal[hidden]{display:none}
  .smodal .smbox{background:#fff;border-radius:18px;max-width:440px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden}
  .smodal .smh{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:20px 22px 0}
  .smodal .smh h2{margin:0;font-size:20px;font-weight:800;letter-spacing:-.3px}
  .smodal .smh .x{width:34px;height:34px;border-radius:50%;border:0;background:none;cursor:pointer;color:var(--muted);display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .smodal .smh .x:hover{background:#F4F0F6}
  .smodal .smb{padding:14px 22px 0;font-size:14px;line-height:1.55}
  .smodal .smf{display:flex;gap:10px;padding:18px 22px 22px;flex-wrap:wrap}
  .smodal .smf .btn{flex:1;justify-content:center;min-width:120px}
  .money-page .ch{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .pay-ov{position:fixed;inset:0;z-index:520;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(20,10,30,.45)}
  .pay-ov:not(.hidden){display:flex!important}
  .pay-ov .pay-modal{background:#fff;border-radius:22px;width:520px;max-width:100%;max-height:calc(100vh - 48px);box-shadow:0 20px 60px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:visible}
  .pay-ov .pay-mh{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:22px 22px 14px;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .pay-ov .pay-mh h2{font-size:20px;font-weight:800;margin:0}
  .pay-ov .pay-mh .pay-x{background:none;border:0;cursor:pointer;color:var(--faint);font-size:22px;line-height:1;padding:2px 4px}
  .pay-ov .pay-mb{padding:18px 22px;overflow:visible;min-height:0;flex:1 1 auto}
  .pay-ov.pay-modal-stripe .pay-mb{overflow-y:auto}
  .pay-ov .pay-mf{display:flex;gap:10px;justify-content:flex-end;padding:14px 22px 20px;border-top:1px solid #F0EAF2;flex-shrink:0}
  .pay-ov .btn{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;cursor:pointer;white-space:nowrap}
  .pay-ov .btn.ghost{background:#fff;color:var(--ink);border:1px solid var(--line)}
  .pay-ov .btn:disabled{opacity:.4;pointer-events:none}
  .pay-ov .hidden,.hidden{display:none!important}
  .pay-ov .pc-fl{display:block;font-weight:700;font-size:13px;margin:4px 0 6px}
  .pay-ov .pc-field{margin-bottom:12px}
  .pay-ov .pc-select{display:block;width:100%;font:inherit;font-size:14px;background:#fff;border:1px solid #E1D8E4;border-radius:10px;padding:10px 12px;color:var(--ink);box-sizing:border-box}
  .pay-ov .pc-select:focus{border-color:#3E007C;box-shadow:0 0 0 3px #EEE3FA;outline:0}
  .pay-ov .pc-field .field-err{min-height:0;margin-top:4px;color:#C62828;font-size:12.5px}
  .pay-ov .pc-cs{position:relative}
  .pay-ov .pc-cs-b{display:flex;align-items:center;width:100%;text-align:left;cursor:pointer;font:inherit;font-size:14px;background:#fff;border:1px solid #E1D8E4;border-radius:10px;padding:10px 12px;color:var(--ink)}
  .pay-ov .pc-cs-b:focus-visible,.pay-ov .pc-cs-b[aria-expanded="true"]{border-color:#3E007C;box-shadow:0 0 0 3px #EEE3FA;outline:0}
  .pay-ov .pc-cs-p{position:absolute;left:0;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid #E8DFEA;border-radius:12px;box-shadow:0 12px 30px rgba(30,15,45,.14);z-index:40;padding:8px}
  .pay-ov .pc-cs-p[hidden]{display:none}
  .pay-ov .pc-cs-q{width:100%;margin-bottom:6px;border:1px solid #DCD2E0;border-radius:8px;font:inherit;padding:9px 11px;outline:0;box-sizing:border-box}
  .pay-ov .pc-cs-l{max-height:240px;overflow:auto}
  .pay-ov .pc-cs-o{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:8px;font-size:14px;cursor:pointer}
  .pay-ov .pc-cs-o.is-hide{display:none!important}
  .pay-ov .pc-cs-o:hover,.pay-ov .pc-cs-o.kb{background:#F3E8FF}
  .pay-ov .pc-cs-o.sel{background:#3E007C;color:#fff}
  .pay-ov .pc-cs-o small{color:#9A929E;font-weight:600}
  .pay-ov .pc-cs-o.sel small{color:rgba(255,255,255,.8)}
  .pay-ov .pc-cs-none{padding:10px 12px;font-size:13px;color:#9A929E}
  .pay-ov .pc-cs-none.is-hide{display:none!important}
  .pay-ov .pc-cur{margin-top:10px;padding:10px 12px;border-radius:10px;background:#F3E8FF;color:#3E007C;font-size:13.5px}
  .pay-ov .pc-cur.same{background:#F6F3F8;color:var(--ink)}
  .pay-ov .pc-ul{margin:12px 0 0;padding:0;list-style:none;font-size:13px;color:#3F3A44}
  .pay-ov .pc-ul li{display:flex;gap:8px;padding:5px 0;align-items:flex-start}
  .pay-ov .pc-ul .ms{font-size:17px;color:#6F6874;flex-shrink:0;margin-top:1px}
  .pay-ov .pc-wait{display:flex;flex-direction:column;align-items:center;gap:12px;padding:30px 22px 34px;text-align:center;color:#6F6874}
  .pay-ov .pc-spin{width:34px;height:34px;border-radius:50%;border:3px solid #E9DDF0;border-top-color:#3E007C;animation:money-pcs .8s linear infinite}
  @keyframes money-pcs{to{transform:rotate(360deg)}}
  .pay-ov.pay-modal-stripe .pay-modal{width:min(720px,100%)}
  .pay-ov #changePayoutAccountStripeMount{min-height:420px;width:100%;background:#fff;border-radius:12px}
  @media (max-width:700px){
    .money-page .split{grid-template-columns:1fr}
    .money-page .stripe-connect-row .btn{margin-left:0;width:100%;justify-content:center}
  }
</style>
@endpush

@section('content')
<div class="money-page">
  <div class="head">
    <div>
      <h1>
        Money
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338688-studio-dashboard-money-payouts" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
      </h1>
      <div class="sub">Your studio's share of splits and where it goes.</div>
    </div>
  </div>

  @include('studio.money.partials.tabs')

  <div class="note info" style="margin-bottom:16px">
    <span class="ms">bolt</span>
    Payouts are automatic. Your share is sent to your bank as soon as it becomes available.
  </div>

  <div class="card" style="margin-bottom:14px" id="paymentAccountCard">
    <div class="ch">
      <div>
        <h3>Payment account</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Your studio's Stripe account</div>
      </div>
      @if($stripeConnected && ($stripeConnectConfigured ?? false))
        <button type="button" class="btn sm ghost" id="changePayoutAccountBtn">
          <span class="ms" style="font-size:17px">swap_horiz</span>Change payout account
        </button>
      @endif
    </div>
    <div style="padding:18px 22px">
      @if($stripeConnected)
        <div class="row" style="gap:14px;flex-wrap:wrap" id="stripeConnectedRow">
          <div class="ic"><span class="ms">account_balance</span></div>
          <div style="flex:1 1 200px;min-width:0">
            <div class="row" style="gap:8px;flex-wrap:wrap">
              <b>Stripe · bank account{{ $stripeLast4 !== '' ? ' ••• '.$stripeLast4 : '' }}</b>
              <span class="pill g">Connected</span>
            </div>
            @if($stripeMeta !== '')
              <div class="faint" style="font-size:12.5px">{{ $stripeMeta }}</div>
            @endif
          </div>
          <button type="button" class="btn ghost sm" id="disconnectStripeBtn" style="color:#C62828;border-color:#F3C9C9">Disconnect</button>
        </div>
      @else
        <div class="stripe-off">
          <div class="formgrid" id="studioSetupFields">
            <div>
              <label class="fl" for="business_country">Business country</label>
              <select class="js-select2" id="business_country" name="country" data-placeholder="Search and select country" aria-label="Business country">
                @foreach ($stripeCountries as $country)
                  <option value="{{ $country['code'] }}" @selected($currentCountry === strtoupper((string) $country['code']))>{{ $country['name'] }}</option>
                @endforeach
              </select>
              <div id="country_error" class="field-err"></div>
              <div class="help" id="currencyHelp">Currency: {{ $currentCurrency }}. Your Stripe bank account must be in the same currency.</div>
            </div>
            <div>
              <label class="fl" for="business_type">Business type</label>
              <select class="js-select2" id="business_type" name="business_type" data-placeholder="Select business type" data-searchable="false" aria-label="Business type">
                <option value="company" @selected($businessType === 'company')>Company</option>
                <option value="individual" @selected($businessType === 'individual')>Sole trader / individual</option>
              </select>
              <div id="business_type_error" class="field-err"></div>
            </div>
          </div>
          <input type="hidden" id="connect_currency" value="{{ $currentCurrency }}">

          <div class="row stripe-connect-row" style="gap:14px;margin-top:16px" id="stripeConnectIntro">
            <div class="ic"><span class="ms">account_balance</span></div>
            <div style="flex:1;min-width:0">
              <b>Connect with Stripe</b>
              <div class="faint" style="font-size:12.5px">Business details, owner ID and bank account. It takes about 5 minutes and you stay in Bookpay.</div>
            </div>
            @if ($stripeConnectConfigured)
              <button type="button" class="btn" id="stripeConnectBtn">Connect</button>
            @else
              <button type="button" class="btn disabled" disabled>Connect</button>
            @endif
          </div>

          @unless ($stripeConnectConfigured)
            <div class="note err" style="margin-top:14px"><span class="ms">error</span>Stripe is not configured. Please contact support.</div>
          @endunless

          <div id="studioStripeStep" hidden style="margin-top:18px">
            <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--line)">
              <p style="font-size:15px;font-weight:700;margin:0">Complete your Stripe payout setup</p>
              <p class="muted" id="studioStripeStepDescription" style="font-size:13.5px;margin-top:6px">Add your details, verify your identity, and connect your bank account below.</p>
            </div>
            <div class="stripe-connect-shell">
              <div id="studioStripeConnectMount"></div>
            </div>
            <p id="studio_stripe_connect_error" class="field-err"></p>
            <p class="help" id="studioStripeConnectHint">Setup finishes automatically when all required steps are complete.</p>
            <button type="button" class="btn ghost" id="studioStripeStepBack" style="margin-top:10px"><span class="ms">arrow_back</span>Back</button>
          </div>
        </div>
      @endif

      @php
        $disconnectedPayouts = $disconnectedPayouts ?? collect();
      @endphp
      @if ($disconnectedPayouts->isNotEmpty())
        <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
          <div class="faint" style="font-size:12.5px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;margin-bottom:12px">Other accounts</div>
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
                    <span class="pill" style="background:#FDECEC;color:#C62828">Disconnected</span>
                  </div>
                  <div class="faint" style="font-size:12.5px;margin-top:2px">
                    @if ($oldWhenLabel)
                      Disconnected on {{ $oldWhenLabel }}. Splits already paid to this account still pay out here until they finish.
                    @else
                      Previous payout account. Splits already paid to this account still pay out here until they finish.
                    @endif
                  </div>
                </div>
                <button
                  type="button"
                  class="btn sm ghost js-activate-studio-payout"
                  data-payout-id="{{ $oldPayout->id }}"
                >Use this account</button>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>

  <form id="studioMoneyPayoutsForm" method="POST" action="{{ route('studio.money.payouts.update') }}" novalidate>
    @csrf
    <input type="hidden" name="offers_revenue_split" id="offers_revenue_split" value="{{ $offersRevenueSplit ? '1' : '0' }}">
    <input type="hidden" name="offers_workstation_rent" id="offers_workstation_rent" value="{{ $offersWorkstationRent ? '1' : '0' }}">

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>How you work with artists</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">What you offer artists at your studio. Both are on to start.</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="row" style="justify-content:space-between;gap:14px;align-items:flex-start">
          <div class="row" style="gap:12px;align-items:flex-start">
            <div class="ic"><span class="ms">call_split</span></div>
            <div>
              <b>Revenue split</b>
              <div class="faint" style="font-size:12.5px;margin-top:2px">Each payment is split automatically between the artist's and your Stripe account, after the cancellation window.</div>
            </div>
          </div>
          <div class="tog{{ $offersRevenueSplit ? ' on' : '' }}" id="bm-split" role="switch" aria-checked="{{ $offersRevenueSplit ? 'true' : 'false' }}" aria-label="Revenue split" tabindex="0" data-shows="#bm-split-b" data-field="offers_revenue_split"></div>
        </div>
        <div id="bm-split-b" style="padding-left:52px;margin-top:4px" @if(! $offersRevenueSplit) hidden @endif>
          <div class="split">
            <div class="box">
              <span>Artist gets (default)</span>
              <input type="number" name="default_revenue_artist_percent" min="1" max="99" value="{{ $defaultRevenueArtistPercent }}" id="bm-artist-pct" data-pair="#defs" aria-label="Artist gets (default)">%
            </div>
            <div class="box">
              <span>Studio gets</span>
              <b class="calc" id="defs">{{ 100 - $defaultRevenueArtistPercent }}%</b>
            </div>
          </div>
          <div class="help">Your default, shown to artists as a hint. Each artist enters the split you agreed with them, and you confirm it.</div>
        </div>

        <div class="row" style="justify-content:space-between;gap:14px;align-items:flex-start;margin-top:16px;padding-top:16px;border-top:1px solid var(--line)">
          <div class="row" style="gap:12px;align-items:flex-start">
            <div class="ic"><span class="ms">chair</span></div>
            <div>
              <b>Workstation rent</b>
              <div class="faint" style="font-size:12.5px;margin-top:2px">Artists get the full payment for their bookings and pay you rent outside Bookpay.</div>
            </div>
          </div>
          <div class="tog{{ $offersWorkstationRent ? ' on' : '' }}" id="bm-rent" role="switch" aria-checked="{{ $offersWorkstationRent ? 'true' : 'false' }}" aria-label="Workstation rent" tabindex="0" data-field="offers_workstation_rent"></div>
        </div>
        <div class="note err" id="bm-err" @if($offersRevenueSplit || $offersWorkstationRent) hidden @endif style="margin-top:12px"><span class="ms">error</span>Keep at least one on.</div>
        <div class="help" style="margin-top:12px">Changes apply to new artists and new bookings. Artists who already have a split keep it.</div>
      </div>
    </div>

    <div class="pgsaverow">
      <button type="submit" class="btn pgsave" id="payoutsSaveBtn"><span class="ms">save</span>Save changes</button>
    </div>
  </form>
</div>

@if($stripeConnected)
<div class="smodal" id="m-disc" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-disc-t">
    <div class="smh">
      <div><h2 id="m-disc-t">Disconnect your bank account?</h2></div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb">
      <p style="color:#3a3a3a;line-height:1.55;margin:0">Your share of new splits can't be paid until you connect again. Artists on a split are paid directly in the meantime. Money already on its way still arrives.</p>
    </div>
    <div class="smf">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="button" class="btn" id="confirmDisconnectStripeBtn" style="background:#C62828;color:#fff">Disconnect</button>
    </div>
  </div>
</div>

@php
  $changePayoutCountries = $changePayoutCountries ?? ($stripeCountries ?? []);
  $changePayoutCountryMeta = $changePayoutCountryMeta ?? [];
  $currentPayoutCountry = strtoupper((string) ($currentPayoutCountry ?? $currentCountry ?? 'GR'));
  $currentPayoutCurrency = strtoupper((string) ($currentPayoutCurrency ?? $stripeCurrency ?? $currentCurrency ?? 'EUR'));
  if ($currentPayoutCountry === '' || ! isset($changePayoutCountryMeta[$currentPayoutCountry])) {
    $currentPayoutCountry = (string) (($changePayoutCountries[0]['code'] ?? 'GR'));
  }
@endphp
<div id="changePayoutAccountModal" class="pay-ov pay-modal-wide hidden" role="dialog" aria-modal="true" aria-labelledby="changePayoutAccountTitle">
  <div class="pay-modal">
    <div class="pay-mh">
      <h2 id="changePayoutAccountTitle">Change payout account</h2>
      <button type="button" class="pay-x js-close-change-payout-account" aria-label="Close">×</button>
    </div>
    <div class="pay-mb" id="changePayoutAccountBody">
      <p style="margin:0 0 12px;color:var(--muted);font-size:13.5px;line-height:1.5">Connect a new Stripe account, for example after moving to another country. Your currency follows the new account.</p>
      <div class="pc-field">
        <label class="pc-fl" id="changePayoutAccountCountryLabel" for="changePayoutAccountCountry">Business country</label>
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
        <div id="changePayoutAccountCountryError" class="field-err"></div>
      </div>
      <div class="pc-field">
        <label class="pc-fl" for="changePayoutAccountBusinessType">Business type</label>
        <select class="pc-select" id="changePayoutAccountBusinessType" aria-label="Business type">
          <option value="company" @selected(($businessType ?? 'company') === 'company')>Company</option>
          <option value="individual" @selected(($businessType ?? 'company') === 'individual')>Sole trader / individual</option>
        </select>
        <div id="changePayoutAccountBusinessTypeError" class="field-err"></div>
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
@endif
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  var form = document.getElementById('studioMoneyPayoutsForm');
  var btn = document.getElementById('payoutsSaveBtn');
  var defaultBtnHtml = btn ? btn.innerHTML : '';
  var artistPct = document.getElementById('bm-artist-pct');
  var dirty = false;
  var disconnectUrl = @json(route('studio.money.payouts.stripe.disconnect'));
  var reactivateUrl = @json(route('studio.money.payouts.stripe.reactivate'));
  var currencyByCountry = @json($currencyByCountry ?? []);
  var csrf = document.querySelector('meta[name="csrf-token"]')?.content
    || (form && form.querySelector('input[name="_token"]')?.value)
    || '';
  var $jq = window.jQuery;

  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return [].slice.call((r || document).querySelectorAll(s)); }

  function setDirty(v) {
    dirty = !!v;
    syncSaveUi();
  }

  function syncSaveUi() {
    var ok = hasOfferOn();
    if (btn) btn.classList.toggle('is-dirty', dirty && ok);
  }

  function hasOfferOn() {
    var splitEl = document.getElementById('bm-split');
    var rentEl = document.getElementById('bm-rent');
    return !!(splitEl && splitEl.classList.contains('on'))
      || !!(rentEl && rentEl.classList.contains('on'));
  }

  function syncPair(i) {
    if (!i) return;
    var v = Math.max(1, Math.min(99, parseInt(i.value || 0, 10) || 0));
    if (String(i.value) !== String(v) && i.value !== '') i.value = v;
    var o = document.querySelector(i.dataset.pair);
    if (o) o.textContent = (100 - v) + '%';
  }

  if (artistPct) {
    artistPct.addEventListener('input', function () {
      syncPair(artistPct);
      setDirty(true);
    });
    syncPair(artistPct);
  }

  function setField(name, on) {
    var el = document.getElementById(name);
    if (el) el.value = on ? '1' : '0';
  }

  function toggleTog(t) {
    t.classList.toggle('on');
    var on = t.classList.contains('on');
    t.setAttribute('aria-checked', on ? 'true' : 'false');
    if (t.dataset.field) setField(t.dataset.field, on);
    var sh = t.dataset.shows;
    if (sh) $$(sh).forEach(function (x) { x.hidden = !on; });
    syncState();
    setDirty(true);
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('.tog');
    if (!t || !form || !form.contains(t)) return;
    toggleTog(t);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var t = e.target.closest('.tog');
    if (!t || !form || !form.contains(t)) return;
    e.preventDefault();
    toggleTog(t);
  });

  function syncState() {
    var ok = hasOfferOn();
    var err = document.getElementById('bm-err');
    if (err) {
      if (ok) err.setAttribute('hidden', '');
      else err.removeAttribute('hidden');
    }
    syncSaveUi();
    return ok;
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!syncState()) {
        if (window.bpToast) window.bpToast('Keep at least one on.', true);
        return;
      }

      setField('offers_revenue_split', $('#bm-split').classList.contains('on'));
      setField('offers_workstation_rent', $('#bm-rent').classList.contains('on'));
      syncPair(artistPct);

      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
      }

      fetch(form.action, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: new FormData(form)
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, status: response.status, data: data };
          }).catch(function () {
            return { ok: false, status: response.status, data: {} };
          });
        })
        .then(function (result) {
          if (result.ok && result.data && result.data.success) {
            setDirty(false);
            if (window.bpToast) window.bpToast(result.data.message || 'Changes saved');
            return;
          }

          var msg = (result.data && result.data.message) || 'Could not save.';
          var errors = (result.data && result.data.errors) || {};
          if (errors.offers_revenue_split || errors.offers_workstation_rent) {
            var err = $('#bm-err');
            if (err) err.hidden = false;
          }
          if (window.bpToast) window.bpToast(msg, true);
        })
        .catch(function () {
          if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
        })
        .finally(function () {
          if (btn) {
            btn.innerHTML = defaultBtnHtml;
            btn.disabled = false;
            btn.classList.toggle('is-dirty', dirty);
          }
        });
    });
  }

  /* Country / business type selects */
  function initSelect2(selector, options) {
    if (!$jq || !$jq.fn.select2) return;
    var opts = options || {};
    $jq(selector).each(function () {
      var $el = $jq(this);
      if ($el.hasClass('select2-hidden-accessible')) return;
      var searchable = opts.searchable != null ? opts.searchable : $el.data('searchable');
      var cfg = {
        width: '100%',
        placeholder: opts.placeholder || $el.data('placeholder') || 'Select',
        allowClear: false,
        dropdownParent: $el.closest('#paymentAccountCard, .money-page').length
          ? $el.closest('#paymentAccountCard, .money-page')
          : $jq(document.body)
      };
      if (searchable === false || searchable === 0 || searchable === 'false') {
        cfg.minimumResultsForSearch = Infinity;
      }
      $el.select2(cfg);
    });
  }

  function countryName(code) {
    var opt = document.querySelector('#business_country option[value="' + code + '"]');
    return opt ? opt.textContent.trim() : code;
  }

  function syncCurrencyFromCountry(code) {
    var next = currencyByCountry[String(code || '').toUpperCase()] || 'EUR';
    var currencyEl = document.getElementById('connect_currency');
    if (currencyEl) currencyEl.value = next;
    var help = document.getElementById('currencyHelp');
    if (help) {
      help.textContent = 'Currency: ' + next + ' (from ' + countryName(code) + '). Your Stripe bank account must be in the same currency.';
    }
  }

  if (document.getElementById('business_country')) {
    initSelect2('#business_country', { placeholder: 'Search and select country', searchable: true });
    initSelect2('#business_type', { placeholder: 'Select business type', searchable: false });
    if ($jq) {
      $jq('#business_country').on('change', function () {
        syncCurrencyFromCountry(this.value);
      });
    }
    syncCurrencyFromCountry(document.getElementById('business_country').value);
  }

  function clearSetupErrors() {
    ['country_error', 'business_type_error', 'studio_stripe_connect_error'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) { el.textContent = ''; el.classList.remove('on'); }
    });
  }

  function showSetupError(id, message) {
    var el = document.getElementById(id);
    if (el) { el.textContent = message || ''; el.classList.toggle('on', !!message); }
  }

  function readSetup() {
    return {
      currency: document.getElementById('connect_currency')?.value || '',
      country: document.getElementById('business_country')?.value || '',
      business_type: document.getElementById('business_type')?.value || '',
      industry: 'tattoo_studio'
    };
  }

  function validateSetup() {
    clearSetupErrors();
    var setup = readSetup();
    var ok = true;
    if (!setup.country) { showSetupError('country_error', 'Please select your business country.'); ok = false; }
    if (!setup.business_type) { showSetupError('business_type_error', 'Please select a business type.'); ok = false; }
    if (!setup.currency) {
      setup.currency = currencyByCountry[String(setup.country).toUpperCase()] || 'EUR';
    }
    return ok ? setup : null;
  }

  function updateStripeDescription(setup) {
    var desc = document.getElementById('studioStripeStepDescription');
    if (!desc || !setup) return;
    var typeLabel = setup.business_type === 'individual' ? 'individual' : 'company';
    desc.textContent = 'Complete Stripe onboarding for your ' + typeLabel + ' account in ' + countryName(setup.country) + '. You will verify your identity and connect your bank account.';
  }

  document.getElementById('studioStripeStepBack')?.addEventListener('click', function () {
    document.getElementById('studioStripeStep').hidden = true;
    document.getElementById('stripeConnectIntro').hidden = false;
    document.getElementById('studioSetupFields').hidden = false;
  });

  window.__studioMoneyPayoutSetup = {
    validateSetup: validateSetup,
    updateStripeDescription: updateStripeDescription,
    showSetupError: showSetupError,
    csrf: csrf,
    reloadConnected: function () {
      try { sessionStorage.setItem('bp-toast', 'Stripe connected'); } catch (e) {}
      window.location.reload();
    }
  };

  /* Disconnect Stripe */
  var discModal = document.getElementById('m-disc');
  var discOpen = document.getElementById('disconnectStripeBtn');
  var discConfirm = document.getElementById('confirmDisconnectStripeBtn');

  function openDisc() { if (discModal) discModal.hidden = false; }
  function closeDisc() { if (discModal) discModal.hidden = true; }

  if (discOpen) {
    discOpen.addEventListener('click', function (e) {
      e.preventDefault();
      openDisc();
    });
  }

  if (discModal) {
    discModal.addEventListener('click', function (e) {
      if (e.target === discModal || e.target.closest('[data-close]')) closeDisc();
    });
  }

  if (discConfirm) {
    discConfirm.addEventListener('click', function () {
      discConfirm.disabled = true;
      discConfirm.textContent = 'Disconnecting…';

      fetch(disconnectUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ _token: csrf })
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          }).catch(function () {
            return { ok: false, data: {} };
          });
        })
        .then(function (result) {
          if (result.ok && result.data && result.data.success) {
            try { sessionStorage.setItem('bp-toast', result.data.message || 'Bank account disconnected'); } catch (e) {}
            window.location.reload();
            return;
          }
          if (window.bpToast) window.bpToast((result.data && result.data.message) || 'Could not disconnect.', true);
          discConfirm.disabled = false;
          discConfirm.textContent = 'Disconnect';
        })
        .catch(function () {
          if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
          discConfirm.disabled = false;
          discConfirm.textContent = 'Disconnect';
        });
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-activate-studio-payout');
    if (!btn) return;
    e.preventDefault();
    var payoutId = btn.getAttribute('data-payout-id');
    if (!payoutId) return;
    btn.disabled = true;
    var original = btn.textContent;
    btn.textContent = 'Reconnecting…';

    fetch(reactivateUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ _token: csrf, payout_id: parseInt(payoutId, 10) })
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        }).catch(function () {
          return { ok: false, data: {} };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          try { sessionStorage.setItem('bp-toast', result.data.message || 'Bank account reconnected'); } catch (err) {}
          window.location.reload();
          return;
        }
        if (window.bpToast) window.bpToast((result.data && result.data.message) || 'Could not reconnect.', true);
        btn.disabled = false;
        btn.textContent = original || 'Use this account';
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
        btn.disabled = false;
        btn.textContent = original || 'Use this account';
      });
  });

  syncState();
  setDirty(false);
})();
</script>

@if ($stripeConnectConfigured && ! $stripeConnected)
<script type="module">
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json(route('studio.money.payouts.stripe.session'));
const completeUrl = @json(route('studio.money.payouts.stripe.complete'));
const stripeConnectLocale = @json($stripeConnectLocale ?? 'en-US');
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));

let connectInstance = null;
let stripeSessionData = null;
let onboardingMounted = false;
let completeTriggered = false;
let loadConnectAndInitialize = null;

function helpers() {
  return window.__studioMoneyPayoutSetup || {};
}

function csrfToken() {
  return helpers().csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function ensureStripeConnectLoader() {
  if (typeof loadConnectAndInitialize === 'function') return loadConnectAndInitialize;

  const urls = [
    'https://esm.sh/@stripe/connect-js@3.3.34/pure',
    'https://cdn.jsdelivr.net/npm/@stripe/connect-js@3.3.34/+esm',
  ];
  let lastError = null;
  for (const url of urls) {
    try {
      const mod = await import(url);
      if (typeof mod.loadConnectAndInitialize === 'function') {
        loadConnectAndInitialize = mod.loadConnectAndInitialize;
        return loadConnectAndInitialize;
      }
    } catch (err) {
      lastError = err;
    }
  }
  throw lastError || new Error('Could not load Stripe Connect.');
}

function showStripeError(message) {
  const el = document.getElementById('studio_stripe_connect_error');
  if (el) {
    el.textContent = message || '';
    el.classList.toggle('on', !!message);
  }
}

async function createSession(setup) {
  const res = await fetch(sessionUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      Accept: 'application/json'
    },
    body: JSON.stringify(setup)
  });
  const data = await res.json().catch(() => ({}));
  if (res.status === 422 && data.errors) {
    const api = helpers();
    if (data.errors.country?.[0] && typeof api.showSetupError === 'function') api.showSetupError('country_error', data.errors.country[0]);
    if (data.errors.business_type?.[0] && typeof api.showSetupError === 'function') api.showSetupError('business_type_error', data.errors.business_type[0]);
    throw new Error(data.message || 'Please check your answers and try again.');
  }
  if (!res.ok || !data.client_secret) throw new Error(data.message || 'Could not start Stripe onboarding.');
  stripeSessionData = data;
  return data;
}

async function finalize() {
  if (completeTriggered || !stripeSessionData?.account_id) return;
  completeTriggered = true;
  const res = await fetch(completeUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      Accept: 'application/json'
    },
    body: JSON.stringify({ account_id: stripeSessionData.account_id })
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    completeTriggered = false;
    throw new Error(data.message || 'Could not save Stripe payout setup.');
  }
  if (typeof helpers().reloadConnected === 'function') {
    helpers().reloadConnected();
  } else {
    window.location.reload();
  }
}

async function mountOnboarding() {
  const container = document.getElementById('studioStripeConnectMount');
  if (!publishableKey || !container || !stripeSessionData?.client_secret) {
    throw new Error('Stripe is not ready. Please try again.');
  }

  if (onboardingMounted) {
    container.innerHTML = '';
    onboardingMounted = false;
    connectInstance = null;
  }

  container.innerHTML = '<p class="help" style="padding:24px">Loading Stripe onboarding…</p>';
  const loadConnect = await ensureStripeConnectLoader();
  connectInstance = loadConnect({
    publishableKey,
    fetchClientSecret: async () => stripeSessionData.client_secret,
    locale: stripeConnectLocale || 'en-US',
    appearance: stripeConnectAppearance
  });
  connectInstance.update({ locale: stripeConnectLocale || 'en-US' });
  const accountOnboarding = connectInstance.create('account-onboarding');
  const collectionOptions = stripeSessionData.collection_options || {};
  accountOnboarding.setCollectionOptions({
    fields: collectionOptions.fields || 'eventually_due',
    futureRequirements: collectionOptions.futureRequirements || 'include',
    ...(collectionOptions.requirements ? { requirements: collectionOptions.requirements } : {})
  });
  accountOnboarding.setOnExit(async () => {
    document.getElementById('studioStripeConnectHint')?.classList.add('hidden');
    await new Promise((resolve) => setTimeout(resolve, 1500));
    try {
      await finalize();
    } catch (err) {
      completeTriggered = false;
      showStripeError(err.message || 'Could not complete payout setup.');
    }
  });
  container.innerHTML = '';
  container.appendChild(accountOnboarding);
  onboardingMounted = true;
}

function resetStripeStepUi() {
  const container = document.getElementById('studioStripeConnectMount');
  if (container) container.innerHTML = '';
  const step = document.getElementById('studioStripeStep');
  const intro = document.getElementById('stripeConnectIntro');
  const fields = document.getElementById('studioSetupFields');
  if (step) step.hidden = true;
  if (intro) intro.hidden = false;
  if (fields) fields.hidden = false;
}

document.getElementById('stripeConnectBtn')?.addEventListener('click', async () => {
  const api = helpers();
  const setup = typeof api.validateSetup === 'function' ? api.validateSetup() : null;
  if (!setup) return;
  if (typeof api.updateStripeDescription === 'function') api.updateStripeDescription(setup);

  const btn = document.getElementById('stripeConnectBtn');
  const original = btn?.innerHTML;
  if (btn) { btn.disabled = true; btn.innerHTML = 'Loading…'; }

  showStripeError('');
  completeTriggered = false;

  try {
    await createSession(setup);
    document.getElementById('stripeConnectIntro').hidden = true;
    document.getElementById('studioSetupFields').hidden = true;
    document.getElementById('studioStripeStep').hidden = false;
    await mountOnboarding();
  } catch (err) {
    resetStripeStepUi();
    showStripeError(err.message || 'Could not load Stripe onboarding.');
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = original || 'Connect'; }
  }
});
</script>
@endif

@if ($stripeConnected && ($stripeConnectConfigured ?? false))
<script type="module">
const publishableKey = @json($stripePublishableKey ?? '');
const changeAccountUrl = @json(route('studio.money.payouts.stripe.change-account'));
const sessionUrl = @json(route('studio.money.payouts.stripe.session'));
const finalizeChangeUrl = @json(route('studio.money.payouts.stripe.finalize-change-account'));
const stripeConnectLocale = @json($stripeConnectLocale ?? 'en-US');
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));
const countryMeta = @json($changePayoutCountryMeta ?? []);
const countryList = @json($changePayoutCountries ?? []);
const defaultCountry = @json($currentPayoutCountry ?? 'GR');
const currentCurrency = @json($currentPayoutCurrency ?? 'EUR');
const currentLast4 = @json(($stripeLast4 ?? '') !== '' ? $stripeLast4 : '••••');
const csrf = @json(csrf_token());

const btn = document.getElementById('changePayoutAccountBtn');
const modal = document.getElementById('changePayoutAccountModal');
if (btn && modal) {
  let body = document.getElementById('changePayoutAccountBody');
  let footer = document.getElementById('changePayoutAccountFooter');
  const step1Html = body ? body.innerHTML : '';
  const footerHtml = footer ? footer.innerHTML : '';
  let starting = false;
  let loadConnectAndInitialize = null;
  let changeConnectInstance = null;
  let changeSessionData = null;
  let changeCompleteTriggered = false;

  function currencyLabel(code) { return code || 'EUR'; }

  function updateNotes() {
    const countryInput = document.getElementById('changePayoutAccountCountry');
    const curNote = document.getElementById('changePayoutAccountCurrencyNote');
    const bullets = document.getElementById('changePayoutAccountBullets');
    if (!countryInput || !curNote || !bullets) return;
    const code = countryInput.value;
    const meta = countryMeta[code] || {};
    const nextCur = meta.currency || currentCurrency;
    const changed = nextCur !== currentCurrency;
    curNote.className = 'pc-cur' + (changed ? '' : ' same');
    curNote.innerHTML = changed
      ? 'Your currency changes from <b>' + currencyLabel(currentCurrency) + '</b> to <b>' + currencyLabel(nextCur) + '</b>.'
      : 'Your currency stays <b>' + currencyLabel(currentCurrency) + '</b>.';
    const items = [
      '<li><span class="ms">history</span><span>Splits already paid stay in ' + currencyLabel(currentCurrency) + ' and pay out to your current account (' + currentLast4 + ') until they\'re finished.</span></li>',
      '<li><span class="ms">event_available</span><span>New bookings use ' + currencyLabel(changed ? nextCur : currentCurrency) + ' once your new account is connected.</span></li>',
      changed
        ? '<li><span class="ms">sell</span><span>Your earnings and reports will show in ' + currencyLabel(nextCur) + '.</span></li>'
        : '<li><span class="ms">sell</span><span>Your prices don\'t change.</span></li>'
    ];
    bullets.innerHTML = items.join('');
  }

  function bindCountryPicker() {
    const root = document.getElementById('changePayoutAccountCountryPicker');
    const countryInput = document.getElementById('changePayoutAccountCountry');
    if (!root || !countryInput) return;
    const b = root.querySelector('.pc-cs-b');
    const p = root.querySelector('.pc-cs-p');
    const q = root.querySelector('.pc-cs-q');
    const l = root.querySelector('.pc-cs-l');
    const v = root.querySelector('.pc-cs-v');
    if (!b || !p || !q || !l || !v) return;
    let kb = -1;
    const list = Array.isArray(countryList) ? countryList : Object.values(countryList || {});
    l.innerHTML = list.map(function (c) {
      const code = String(c.code || '').toUpperCase();
      const name = String(c.name || code);
      const cur = (countryMeta[code] && countryMeta[code].currency) || '';
      return '<div class="pc-cs-o" role="option" tabindex="-1" data-v="' + code + '" data-label="' + name.toLowerCase() + ' ' + code.toLowerCase() + '"><span>' + name + '</span><small>' + cur + '</small></div>';
    }).join('') + '<div class="pc-cs-none is-hide">No matches</div>';
    const os = [].slice.call(l.querySelectorAll('.pc-cs-o'));
    const none = l.querySelector('.pc-cs-none');
    function isShown(o) { return !o.classList.contains('is-hide'); }
    function vis() { return os.filter(isShown); }
    function hl(i) {
      const vs = vis();
      os.forEach(function (o) { o.classList.remove('kb'); });
      kb = Math.max(0, Math.min(i, vs.length - 1));
      if (vs[kb]) { vs[kb].classList.add('kb'); vs[kb].scrollIntoView({ block: 'nearest' }); }
    }
    function filt() {
      const t = String(q.value || '').trim().toLowerCase();
      let n = 0;
      os.forEach(function (o) {
        const label = o.getAttribute('data-label') || o.textContent.toLowerCase();
        const show = !t || label.indexOf(t) > -1;
        o.classList.toggle('is-hide', !show);
        if (show) n++;
      });
      if (none) none.classList.toggle('is-hide', n > 0);
      kb = -1;
    }
    function openDrop() {
      p.hidden = false;
      p.classList.remove('hidden');
      b.setAttribute('aria-expanded', 'true');
      q.value = '';
      filt();
      setTimeout(function () { q.focus(); }, 0);
    }
    function closeDrop() {
      p.hidden = true;
      p.classList.add('hidden');
      b.setAttribute('aria-expanded', 'false');
    }
    function set(code, silent) {
      code = String(code || '').toUpperCase();
      countryInput.value = code || '';
      os.forEach(function (x) {
        const on = x.dataset.v === code;
        x.classList.toggle('sel', on);
        x.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      const selected = os.find(function (x) { return x.dataset.v === code; });
      v.textContent = selected
        ? selected.querySelector('span').textContent
        : ((countryMeta[code] && countryMeta[code].name) || 'Select country');
      if (!silent) updateNotes();
    }
    b.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      (p.hidden || p.classList.contains('hidden')) ? openDrop() : closeDrop();
    });
    q.addEventListener('click', function (e) { e.stopPropagation(); });
    q.addEventListener('mousedown', function (e) { e.stopPropagation(); });
    q.addEventListener('input', function (e) {
      e.stopPropagation();
      filt();
    });
    q.addEventListener('keyup', function (e) {
      e.stopPropagation();
      filt();
    });
    q.addEventListener('keydown', function (e) {
      e.stopPropagation();
      const vs = vis();
      if (e.key === 'Escape') { e.preventDefault(); closeDrop(); b.focus(); }
      else if (e.key === 'ArrowDown') { e.preventDefault(); hl(kb + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); hl(kb - 1); }
      else if (e.key === 'Enter' && vs.length) { e.preventDefault(); set((vs[kb] || vs[0]).dataset.v); closeDrop(); b.focus(); }
    });
    os.forEach(function (o) {
      o.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        set(o.dataset.v);
        closeDrop();
        b.focus();
      });
    });
    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) closeDrop();
    });
    set(countryInput.value || defaultCountry, true);
    closeDrop();
  }

  function showStep1() {
    modal.classList.remove('pay-modal-stripe');
    body = document.getElementById('changePayoutAccountBody');
    footer = document.getElementById('changePayoutAccountFooter');
    if (body) body.innerHTML = step1Html;
    if (footer) {
      footer.innerHTML = footerHtml;
      footer.classList.remove('hidden');
    }
    bindCountryPicker();
    updateNotes();
    document.getElementById('changePayoutAccountContinueBtn')?.addEventListener('click', startChangeAccount);
  }

  function showStripeStep(countryName, businessType) {
    modal.classList.add('pay-modal-stripe');
    footer = document.getElementById('changePayoutAccountFooter');
    body = document.getElementById('changePayoutAccountBody');
    if (footer) footer.classList.add('hidden');
    const typeLabel = businessType === 'individual' ? 'individual' : 'company';
    if (body) {
      body.innerHTML =
        '<div style="margin-bottom:12px">' +
          '<b style="display:block;font-size:15px">Connect Stripe in ' + countryName + '</b>' +
          '<p class="faint" style="font-size:13px;margin:6px 0 0;line-height:1.5">Complete Stripe onboarding for your ' + typeLabel + ' account. Your current payout stays active until you finish.</p>' +
        '</div>' +
        '<div class="stripe-connect-shell"><div id="changePayoutAccountStripeMount"></div></div>' +
        '<p id="changePayoutAccountStripeError" class="help hidden" style="color:var(--red)"></p>' +
        '<p id="changePayoutAccountSavingMessage" class="help hidden">Saving your new payout connection…</p>';
    }
  }

  async function ensureStripeConnectLoader() {
    if (typeof loadConnectAndInitialize === 'function') return loadConnectAndInitialize;
    const urls = [
      'https://esm.sh/@stripe/connect-js@3.3.34/pure',
      'https://cdn.jsdelivr.net/npm/@stripe/connect-js@3.3.34/+esm',
    ];
    let lastError = null;
    for (const url of urls) {
      try {
        const mod = await import(url);
        if (typeof mod.loadConnectAndInitialize === 'function') {
          loadConnectAndInitialize = mod.loadConnectAndInitialize;
          return loadConnectAndInitialize;
        }
      } catch (err) {
        lastError = err;
      }
    }
    throw lastError || new Error('Could not load Stripe Connect.');
  }

  function showChangeStripeError(message) {
    const el = document.getElementById('changePayoutAccountStripeError');
    if (el) {
      el.textContent = message || '';
      el.classList.toggle('hidden', !message);
    }
  }

  async function finalizeChange() {
    if (changeCompleteTriggered || !changeSessionData?.account_id || !changeSessionData?.payout_id) return;
    changeCompleteTriggered = true;
    const saving = document.getElementById('changePayoutAccountSavingMessage');
    if (saving) saving.classList.remove('hidden');
    const res = await fetch(finalizeChangeUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        Accept: 'application/json'
      },
      body: JSON.stringify({
        payout_id: changeSessionData.payout_id,
        stripe_account_id: changeSessionData.account_id,
        business_type: changeSessionData.business_type || undefined
      })
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      changeCompleteTriggered = false;
      if (saving) saving.classList.add('hidden');
      throw new Error(data.message || 'Could not save your new payout account.');
    }
    try { sessionStorage.setItem('bp-toast', data.message || 'Payout account updated'); } catch (e) {}
    window.location.reload();
  }

  async function mountChangeOnboarding() {
    const container = document.getElementById('changePayoutAccountStripeMount');
    if (!publishableKey || !container || !changeSessionData?.client_secret) {
      throw new Error('Stripe is not ready. Please try again.');
    }
    container.innerHTML = '<p class="help" style="padding:24px">Loading Stripe onboarding…</p>';
    const loadConnect = await ensureStripeConnectLoader();
    changeConnectInstance = loadConnect({
      publishableKey,
      fetchClientSecret: async () => changeSessionData.client_secret,
      locale: stripeConnectLocale || 'en-US',
      appearance: stripeConnectAppearance
    });
    changeConnectInstance.update({ locale: stripeConnectLocale || 'en-US' });
    const accountOnboarding = changeConnectInstance.create('account-onboarding');
    const collectionOptions = changeSessionData.collection_options || {};
    accountOnboarding.setCollectionOptions({
      fields: collectionOptions.fields || 'eventually_due',
      futureRequirements: collectionOptions.futureRequirements || 'include',
      ...(collectionOptions.requirements ? { requirements: collectionOptions.requirements } : {})
    });
    accountOnboarding.setOnExit(async () => {
      await new Promise((resolve) => setTimeout(resolve, 1500));
      try {
        await finalizeChange();
      } catch (err) {
        changeCompleteTriggered = false;
        showChangeStripeError(err.message || 'Could not complete payout setup.');
      }
    });
    container.innerHTML = '';
    container.appendChild(accountOnboarding);
  }

  function clearChangeFieldErrors() {
    const countryErr = document.getElementById('changePayoutAccountCountryError');
    const typeErr = document.getElementById('changePayoutAccountBusinessTypeError');
    if (countryErr) countryErr.textContent = '';
    if (typeErr) typeErr.textContent = '';
  }

  function readChangeSetup() {
    const countryInput = document.getElementById('changePayoutAccountCountry');
    const typeInput = document.getElementById('changePayoutAccountBusinessType');
    return {
      country: countryInput ? String(countryInput.value || '').toUpperCase() : '',
      business_type: typeInput ? String(typeInput.value || '') : ''
    };
  }

  async function startChangeAccount() {
    if (starting) return;
    clearChangeFieldErrors();
    const setup = readChangeSetup();
    const name = (countryMeta[setup.country] && countryMeta[setup.country].name) || setup.country;
    let ok = true;
    if (!setup.country) {
      const countryErr = document.getElementById('changePayoutAccountCountryError');
      if (countryErr) countryErr.textContent = 'Please select your business country.';
      ok = false;
    }
    if (!setup.business_type || (setup.business_type !== 'company' && setup.business_type !== 'individual')) {
      const typeErr = document.getElementById('changePayoutAccountBusinessTypeError');
      if (typeErr) typeErr.textContent = 'Please select a business type.';
      ok = false;
    }
    if (!ok) return;

    starting = true;
    changeCompleteTriggered = false;
    changeSessionData = null;
    footer = document.getElementById('changePayoutAccountFooter');
    body = document.getElementById('changePayoutAccountBody');
    if (footer) footer.classList.add('hidden');
    if (body) {
      body.innerHTML = '<div class="pc-wait" role="status"><div class="pc-spin"></div><b style="color:var(--ink)">Opening Stripe…</b><span style="font-size:13px">Preparing a new payout account in ' + name + '.</span></div>';
    }

    try {
      const res = await fetch(changeAccountUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json'
        },
        body: JSON.stringify({
          _token: csrf,
          payout_bank_country: setup.country,
          business_type: setup.business_type
        })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.success) {
        const msg = data.message
          || (data.errors && data.errors.payout_bank_country && data.errors.payout_bank_country[0])
          || (data.errors && data.errors.business_type && data.errors.business_type[0])
          || 'Could not start payout account change.';
        throw new Error(msg);
      }

      const businessType = data.business_type || setup.business_type;
      const sessionRes = await fetch(sessionUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          Accept: 'application/json'
        },
        body: JSON.stringify({
          payout_id: data.payout_id,
          country: data.payout_bank_country || setup.country,
          business_type: businessType,
          industry: 'tattoo_studio'
        })
      });
      const sessionData = await sessionRes.json().catch(() => ({}));
      if (!sessionRes.ok || !sessionData.client_secret) {
        throw new Error(sessionData.message || 'Could not start Stripe onboarding.');
      }
      changeSessionData = {
        ...sessionData,
        payout_id: sessionData.payout_id || data.payout_id,
        business_type: businessType
      };

      showStripeStep(data.payout_bank_country_name || name, businessType);
      await mountChangeOnboarding();
    } catch (err) {
      showStep1();
      const errBox = document.createElement('p');
      errBox.className = 'help';
      errBox.style.color = 'var(--red)';
      errBox.style.marginTop = '10px';
      errBox.textContent = err.message || 'Could not start payout account change.';
      document.getElementById('changePayoutAccountBody')?.appendChild(errBox);
    } finally {
      starting = false;
    }
  }

  function openModal() {
    showStep1();
    modal.classList.remove('hidden');
  }
  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('pay-modal-stripe');
    changeSessionData = null;
    changeCompleteTriggered = false;
  }

  btn.addEventListener('click', openModal);
  modal.addEventListener('click', function (e) {
    if (e.target === modal || e.target.closest('.js-close-change-payout-account')) {
      if (!starting) closeModal();
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.classList.contains('hidden') && !starting) closeModal();
  });
}
</script>
@endif
@endpush
