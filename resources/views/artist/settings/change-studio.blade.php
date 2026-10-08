@extends('layouts.new-artist-dashboard-layout')

@section('title', ($mode ?? 'change') === 'add' ? 'Add studio' : (($mode ?? 'change') === 'update' ? 'Update studio' : 'Change studio'))

@section('styles')
<style>
/* Page-specific from studio-dashboard/09ea (~213–243) + mlbox/mlinline/mlcopy from 09eb */
.po{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px;cursor:pointer;margin-bottom:10px}
.po.sel{border:2px solid #3E007C;padding:15px 17px;background:#FDFAFF}
.po input{accent-color:#3E007C;width:18px;height:18px;margin-top:2px;flex-shrink:0}
.po b{font-size:14.5px}.po .d{font-size:13px;color:var(--muted);margin-top:3px;line-height:1.5}
.splitbox{background:#FBF7FF;border:1px solid #E4D6F5;border-radius:14px;padding:18px;margin:4px 0 10px}
.stepn{width:26px;height:26px;border-radius:50%;background:#1A1A1A;color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.pct{display:flex;align-items:center;gap:8px}.pct input{width:80px;text-align:center}
.lockbar{display:flex;gap:8px;align-items:center;background:#F0EDF2;color:var(--muted);border-radius:10px;padding:9px 12px;font-size:12.5px}
.tgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.tgrid .po{margin:0}
.picked{background:#fff;border:2px solid #3E007C;border-radius:14px;padding:16px 18px}
.logo2{width:48px;height:48px;border-radius:12px;background:#EDD9FF;color:#3E007C;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.kv{display:flex;justify-content:space-between;gap:16px;padding:6px 0;font-size:13.5px}.kv span:first-child{color:var(--muted)}
.results{background:#fff;border:1px solid var(--line);border-radius:12px;padding:6px;margin-top:8px}
.result{display:flex;gap:12px;align-items:center;padding:9px 10px;border-radius:9px;cursor:pointer;text-decoration:none;color:inherit;border:0;background:none;width:100%;text-align:left;font:inherit}
.result:hover,.result.hl{background:#F8F0FC}
@media (max-width:700px){.tgrid{grid-template-columns:1fr}}
.hsteps{display:flex;gap:6px;margin:0 0 18px;flex-wrap:wrap}.hsteps span{display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;color:var(--faint);padding:6px 12px;border-radius:20px;background:#F5F1F6}
.hsteps span i{font-style:normal;width:18px;height:18px;border-radius:50%;background:#DDD3E2;color:#fff;font-size:11px;display:flex;align-items:center;justify-content:center}
.hsteps span.on{background:#F3E8FF;color:#3E007C}.hsteps span.on i,.hsteps span.done i{background:#3E007C}.hsteps span.done{color:#3E007C}
.hsum{border:1px solid #EFE8F1;border-radius:14px;background:#FDFBFD;padding:6px 16px;margin-bottom:12px}.hsum .kv{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #F3EEF4;font-size:13.5px}.hsum .kv:last-child{border:0}.hsum .kv span:first-child{color:var(--muted)}.hsum .kv span:last-child{font-weight:600;text-align:right}
.hwarn{display:flex;gap:10px;background:#FFF7E6;border:1px solid #F3DDB0;border-radius:12px;padding:12px 14px;color:#7A5200;font-size:13px;line-height:1.5;margin-bottom:10px}.hwarn .ms{color:#B7791F}
.hwarn.red{background:#FDECEC;border-color:#F3C9C9;color:#8E1B1B}.hwarn.red .ms{color:#C62828}
.csbar{display:flex;gap:10px;justify-content:flex-end;align-items:center;margin-top:18px;padding-top:16px;border-top:1px solid var(--line);flex-wrap:wrap}.csbar .sp{margin-right:auto}a.btn{text-decoration:none}
.blist{padding-left:22px;margin:12px 0 0;line-height:1.55;font-size:13.5px}
.csback{display:inline-flex;align-items:center;gap:4px;color:#3E007C;font-weight:600;font-size:13px;text-decoration:none;margin-bottom:6px}
.mlbox{display:flex;align-items:center;gap:8px;border:1px solid var(--line,#E7E1EA);background:#FAF8FB;border-radius:10px;padding:8px 8px 8px 12px;min-width:0}
.mlbox>.ms{font-size:18px;color:#3E007C;flex:none}
.mlbox a.mlk{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#3E007C;font-weight:600;font-size:13px;text-decoration:none}
.mlbox a.mlk:hover{text-decoration:underline}
.mlcopy{flex:none;border:1px solid #D9D2DC;background:#fff;border-radius:8px;padding:5px 10px;font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px}
.mlcopy .ms{font-size:15px}
.mlinline{display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap}
.mlinline a{color:#3E007C;font-weight:600;text-decoration:none}
.cs-panel[hidden],.cs-block[hidden]{display:none!important}
.cs-err{color:#C62828;font-size:12.5px;margin-top:6px}
.cs-err[hidden]{display:none!important}
.cs-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
.cs-toast.on{opacity:1;transform:none}
.cs-toast .ms{color:#3DD68C;font-size:20px}
.btn:disabled{opacity:.55;cursor:not-allowed}
.pac-container{z-index:10000!important}
@media (max-width:600px){.cs-toast{left:16px;right:16px;top:74px}}
</style>
@endsection

@section('content')
@php
  $modeKey = $mode ?? 'change';
  $isAdd = $modeKey === 'add';
  $isUpdate = $modeKey === 'update';
  $title = $isAdd ? 'Add studio' : ($isUpdate ? 'Update studio' : 'Change studio');
  $sub = $isAdd
    ? 'Add another place where you work. Same steps as when you signed up. Your current studio stays as it is.'
    : ($isUpdate
      ? 'Update this workplace. Changes save to the same studio — nothing new is added.'
      : 'Where you work now, and how you get paid there. Same steps as when you signed up.');
  $findTitle = $isAdd ? 'Find the studio to add' : ($isUpdate ? 'Your studio' : 'Find your new studio');
  $whereLabel = $isAdd ? 'Where else do you tattoo?' : ($isUpdate ? 'Studio details' : 'Where do you tattoo now?');
  $newStudioTitle = $isAdd ? 'Studio to add' : ($isUpdate ? 'This studio' : 'Your new studio');
  $stripeBits = $stripeConnected
    ? ('Your Stripe account is connected ('.($stripeLast4 ?: '••••').').')
    : 'Connect Stripe in Money → Payouts if you haven’t yet.';
  $wsIcons = [
    'private' => ['icon' => 'home', 'label' => 'Private Studio', 'hint' => 'A personal workspace. Clients visit by appointment only'],
    'shop' => ['icon' => 'storefront', 'label' => 'Tattoo Shop', 'hint' => 'A shared shop with walk-ins and appointments'],
    'home' => ['icon' => 'cottage', 'label' => 'Home Studio', 'hint' => 'Working from home. Address shared only after booking'],
    'collective' => ['icon' => 'groups', 'label' => 'Collective', 'hint' => 'A shared space run together by a group of artists'],
  ];
@endphp

<div id="changeStudioWizard"
     data-mode="{{ $mode }}"
     data-search-url="{{ route('settings.studio.change.search') }}"
     data-store-url="{{ route('settings.studio.change.store') }}"
     data-stripe-connected="{{ $stripeConnected ? '1' : '0' }}"
     data-current-name="{{ $currentLabel }}"
     data-current-payout="{{ $currentPayoutLabel }}"
     data-upcoming="{{ (int) ($upcomingBookingsCount ?? 0) }}">
<script type="application/json" id="csPrefillJson">@json($prefill ?? null)</script>

  <a class="csback" href="{{ route('settings.studio') }}"><span class="ms" style="font-size:18px">arrow_back</span>Account &gt; Studio</a>

  <div style="margin-bottom:18px">
    <div class="head">
      <div>
        <h1>{{ $title }}<a class="help-q" href="https://help.inkjin.com" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
        <div class="sub">{{ $sub }}</div>
      </div>
    </div>
  </div>

  <div class="hsteps" id="csSteps">
    <span class="on" data-step-pill="studio"><i>1</i>Studio</span>
    <span data-step-pill="payment"><i>2</i>Payment</span>
    <span data-step-pill="confirm"><i>3</i>Confirm</span>
  </div>

  @unless($isAdd)
    <div class="sect cscur" style="margin:0 0 14px" id="csCur">
      <div class="lbl">Now</div>
      <div class="row" style="gap:8px;margin-top:6px;flex-wrap:wrap">
        <b>{{ $currentLabel }}</b>
        <span class="faint">{{ $currentPayoutLabel }}</span>
      </div>
    </div>
  @endunless

  {{-- ========== STEP: STUDIO ========== --}}
  <div class="cs-panel" data-panel="studio">

    {{-- 09ea / kept above path cards for not-found / own / none --}}
    <div class="card pad" id="csSearchCard">
      <h3 style="margin-bottom:4px" id="csFindTitle">{{ $findTitle }}</h3>
      <div class="faint" style="font-size:13px;margin-bottom:14px" id="csFindHint">If the studio is on Bookpay, pick it and its details fill in automatically.</div>
      <span class="fl">{{ $whereLabel }} <span style="color:#C62828">*</span></span>
      <div class="in"><span class="ms">search</span><input id="csSearch" type="search" placeholder="Search studios on Bookpay by name or city" autocomplete="off" style="border:0;outline:0;font:inherit;flex:1;background:none"></div>
      <div class="lbl" id="csSearchLbl" style="margin:14px 0 0" hidden></div>
      <div class="faint" style="font-size:13px;margin:10px 0 0" id="csSearchEmpty" hidden></div>
      <div class="results" id="csResults" hidden></div>
      <div style="font-weight:700;font-size:13px;color:var(--muted);margin:16px 0 8px">Can’t find it?</div>
      <label class="po" data-path-radio="not_on_bookpay"><input type="radio" name="nf" value="not_on_bookpay"><div style="flex:1"><b>My studio isn’t on Bookpay</b><div class="d">Enter the studio name and address yourself</div></div></label>
      <label class="po" data-path-radio="own_space"><input type="radio" name="nf" value="own_space"><div style="flex:1"><b>I work from my own space</b><div class="d">Private or home studio. The address stays yours</div></div></label>
      @unless($isAdd || $isUpdate)
        <label class="po" data-path-radio="no_studio"><input type="radio" name="nf" value="no_studio"><div style="flex:1"><b>No studio for now</b><div class="d">End your link with {{ $currentLabel }} without adding a new place. New bookings pause until you add one</div></div></label>
      @endunless
      <div class="cs-err" id="csPathError" hidden></div>
    </div>

    {{-- 09eb found: TWO cards --}}
    <div class="cs-block" id="csFoundBlock" hidden>
      <div class="card pad" style="margin-bottom:14px">
        <h3 style="margin-bottom:12px" id="csFoundTitle">{{ $newStudioTitle }}</h3>
        <div class="picked">
          <div class="row" style="gap:14px;padding-bottom:12px;border-bottom:1px solid #F0EAF2">
            <div class="logo2" id="csFoundIni">ST</div>
            <div style="flex:1">
              <div class="row" style="gap:8px"><b style="font-size:16px" id="csFoundName">Studio</b><span class="pill g">On Bookpay</span></div>
              <div class="faint" style="font-size:12.5px" id="csFoundType">Tattoo Shop</div>
            </div>
            <a href="#" id="csChangePick" style="font-weight:700;color:#3E007C;text-decoration:none;font-size:13px">Change</a>
          </div>
          <div class="kv" style="margin-top:8px"><span>Address</span><span id="csFoundAddr">—</span></div>
          <div class="kv"><span>Map</span><span><span class="mlinline" id="csFoundMapInline"><a class="mlk" id="csFoundMapLink" href="#" target="_blank" rel="noopener">Open in Google Maps</a><button type="button" class="mlcopy"><span class="ms">content_copy</span>Copy link</button></span></span></div>
          <div class="faint" style="font-size:11.5px;margin-top:6px" id="csFoundManaged">Managed by Studio</div>
        </div>
        <div class="note" id="csFoundNote" style="margin-top:12px;background:#EEF4FF;border-color:#C9DBFA;color:#1D4EA0;display:block"></div>
      </div>
      <div class="card pad">
        <h3 style="margin-bottom:4px">Your relationship with this studio <span style="color:#C62828">*</span></h3>
        <div class="faint" style="font-size:13px;margin-bottom:12px">How do you work with this studio?</div>
        <div class="tgrid" id="csRelFound">
          @foreach($relationshipOptions as $val => $meta)
            <label class="po"><input type="radio" name="rel_found" value="{{ $val }}"><div style="flex:1"><b>{{ $meta['label'] }}</b><div class="d">{{ $meta['hint'] }}</div></div></label>
          @endforeach
        </div>
        <div class="help" style="margin-top:10px">Only there for a while? Guest spots are set up separately, in Schedule &gt; Guest spots.</div>
        <div class="cs-err" id="csRelFoundError" hidden></div>
      </div>
    </div>

    {{-- 09ec not on Bookpay: THREE .ch cards (search stays above) --}}
    <div class="cs-block" id="csManualBlock" hidden>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3>Studio details</h3><div class="faint" style="font-size:12.5px;margin-top:2px">You manage these details until the studio joins Bookpay.</div></div></div>
        <div style="padding:18px 22px">
          <div class="grid" style="gap:14px">
            <div><span class="fl">Studio name <span style="color:#C62828">*</span></span><input class="in" id="csManName" type="text"></div>
            <div>
              <span class="fl">Find your address <span style="color:#C62828">*</span></span>
              <div class="in"><span class="ms">location_on</span><input id="csManAddress" type="text" placeholder="Start typing your address" autocomplete="off" style="border:0;outline:0;font:inherit;flex:1;background:none"></div>
              <div class="help">Start typing and pick from Google suggestions to fill the fields below.</div>
            </div>
            <div class="grid" style="grid-template-columns:1fr 2fr;gap:12px">
              <div><span class="fl">Street number <span style="color:#C62828">*</span></span><input class="in" id="csManStreetNo" type="text"></div>
              <div><span class="fl">Street name <span style="color:#C62828">*</span></span><input class="in" id="csManStreet" type="text"></div>
            </div>
            <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
              <div><span class="fl">City <span style="color:#C62828">*</span></span><input class="in" id="csManCity" type="text"></div>
              <div><span class="fl">State / Province <span style="color:#C62828">*</span></span><input class="in" id="csManState" type="text"></div>
              <div><span class="fl">Postal code <span style="color:#C62828">*</span></span><input class="in" id="csManPostal" type="text"></div>
              <div><span class="fl">Country <span style="color:#C62828">*</span></span><input class="in" id="csManCountry" type="text"></div>
            </div>
            <div>
              <span class="fl">Google Maps link</span>
              <div class="mlbox" data-auto data-prefix="man"><span class="ms">map</span><a class="mlk" id="csManMapsLink" href="#" target="_blank" rel="noopener">google.com/maps</a><button type="button" class="mlcopy"><span class="ms">content_copy</span>Copy</button></div>
              <div class="help">Made from the address, so it updates when you change the address. Copy it to share.</div>
            </div>
          </div>
        </div>
      </div>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3>Studio type <span style="color:#C62828">*</span></h3><div class="faint" style="font-size:12.5px;margin-top:2px">What best describes the studio?</div></div></div>
        <div style="padding:18px 22px">
          <div class="tgrid" id="csTypeManual">
            @foreach($workspaceOptionsFound as $val => $label)
              @php $meta = $wsIcons[$val] ?? ['icon' => 'store', 'label' => $label, 'hint' => '']; @endphp
              <label class="po"><input type="radio" name="stype_manual" value="{{ $val }}"><div style="flex:1"><b><span class="ms" style="font-size:18px;vertical-align:-3px">{{ $meta['icon'] }}</span> {{ $meta['label'] }}</b><div class="d">{{ $meta['hint'] }}</div></div></label>
            @endforeach
          </div>
        </div>
      </div>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3>Your relationship with this studio <span style="color:#C62828">*</span></h3><div class="faint" style="font-size:12.5px;margin-top:2px">How do you work with this studio?</div></div></div>
        <div style="padding:18px 22px">
          <div class="tgrid" id="csRelManual">
            @foreach($relationshipOptions as $val => $meta)
              <label class="po"><input type="radio" name="rel_manual" value="{{ $val }}"><div style="flex:1"><b>{{ $meta['label'] }}</b><div class="d">{{ $meta['hint'] }}</div></div></label>
            @endforeach
          </div>
          <div class="help" style="margin-top:10px">Only there for a while? Guest spots are set up separately, in Schedule &gt; Guest spots.</div>
          <div class="cs-err" id="csManualError" hidden></div>
        </div>
      </div>
    </div>

    {{-- 09ed own space --}}
    <div class="cs-block" id="csOwnBlock" hidden>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3>Your space</h3><div class="faint" style="font-size:12.5px;margin-top:2px">Only shown to clients the way you choose below.</div></div></div>
        <div style="padding:18px 22px">
          <div class="grid" style="gap:14px">
            <div><span class="fl">Space name <span style="color:#C62828">*</span></span><input class="in" id="csOwnName" type="text"></div>
            <div>
              <span class="fl">Find your address <span style="color:#C62828">*</span></span>
              <div class="in"><span class="ms">location_on</span><input id="csOwnAddress" type="text" placeholder="Start typing your address" autocomplete="off" style="border:0;outline:0;font:inherit;flex:1;background:none"></div>
              <div class="help">Start typing and pick from Google suggestions to fill the fields below.</div>
            </div>
            <div class="grid" style="grid-template-columns:1fr 2fr;gap:12px">
              <div><span class="fl">Street number <span style="color:#C62828">*</span></span><input class="in" id="csOwnStreetNo" type="text"></div>
              <div><span class="fl">Street name <span style="color:#C62828">*</span></span><input class="in" id="csOwnStreet" type="text"></div>
            </div>
            <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
              <div><span class="fl">City <span style="color:#C62828">*</span></span><input class="in" id="csOwnCity" type="text"></div>
              <div><span class="fl">State / Province <span style="color:#C62828">*</span></span><input class="in" id="csOwnState" type="text"></div>
              <div><span class="fl">Postal code <span style="color:#C62828">*</span></span><input class="in" id="csOwnPostal" type="text"></div>
              <div><span class="fl">Country <span style="color:#C62828">*</span></span><input class="in" id="csOwnCountry" type="text"></div>
            </div>
            <div>
              <span class="fl">Google Maps link</span>
              <div class="mlbox" data-auto data-prefix="own"><span class="ms">map</span><a class="mlk" id="csOwnMapsLink" href="#" target="_blank" rel="noopener">google.com/maps</a><button type="button" class="mlcopy"><span class="ms">content_copy</span>Copy</button></div>
              <div class="help">Made from the address, so it updates when you change the address. Copy it to share.</div>
            </div>
          </div>
        </div>
      </div>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3>Space type <span style="color:#C62828">*</span></h3><div class="faint" style="font-size:12.5px;margin-top:2px">What best describes your workspace?</div></div></div>
        <div style="padding:18px 22px">
          <div class="tgrid" id="csTypeOwn">
            @foreach($workspaceOptionsOwn as $val => $label)
              @php $meta = $wsIcons[$val] ?? ['icon' => 'home', 'label' => $label, 'hint' => '']; @endphp
              <label class="po"><input type="radio" name="stype_own" value="{{ $val }}"><div style="flex:1"><b><span class="ms" style="font-size:18px;vertical-align:-3px">{{ $meta['icon'] }}</span> {{ $meta['label'] }}</b><div class="d">{{ $meta['hint'] }}</div></div></label>
            @endforeach
          </div>
          <div class="cs-err" id="csOwnError" hidden></div>
        </div>
      </div>
    </div>

    {{-- 09ee no studio --}}
    <div class="cs-block" id="csNoneBlock" hidden>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3>No studio for now</h3><div class="faint" style="font-size:12.5px;margin-top:2px">You end your link with {{ $currentLabel }} and add a new place later.</div></div></div>
        <div style="padding:18px 22px">
          <div class="hwarn red"><span class="ms">pause_circle</span><div><b>New bookings pause until you add where you work</b><br>Clients need an address to book you. Add your own space or a studio in Account &gt; Studio when you're ready.</div></div>
          <ul class="blist">
            <li>From now on you're paid directly (100%@if($stripeConnected), Stripe {{ $stripeLast4 ?: '••••' }}@endif).</li>
            <li>Moving to another studio? Search for it above instead, so you don't miss bookings.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  {{-- ========== STEP: PAYMENT ========== --}}
  <div class="cs-panel" data-panel="payment" hidden>

    {{-- 09ef found --}}
    <div class="cs-block" id="csPayFound" hidden>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3 id="csPayFoundTitle">How you get paid</h3><div class="faint" style="font-size:12.5px;margin-top:2px" id="csPayFoundSub"></div></div></div>
        <div style="padding:18px 22px">
          <label class="po"><input type="radio" name="po_found" value="direct"><div style="flex:1"><b>Artist - Direct payment</b><div class="d" id="csPayFoundDirectD">The full payment goes to your own Stripe account. You settle rent or fees with the studio outside Bookpay. The studio still gets your join request, but there's no split to confirm.</div></div></label>
          <label class="po sel"><input type="radio" name="po_found" value="split" checked><div style="flex:1"><b>Studio - Revenue split</b><div class="d" id="csPayFoundSplitD">Each payment is split between you and the studio. Your share goes to your Stripe account and theirs to theirs.</div></div></label>
          <div class="splitbox" id="splitbox_found">
            <div class="row" style="gap:8px;flex-wrap:wrap"><span class="pill a" id="csPayFoundPill">Studio confirms when they accept you</span><span class="faint" style="font-size:12.5px">Studio default: You 50% · Studio 50%</span></div>
            <div class="row" style="gap:28px;margin-top:12px;flex-wrap:wrap">
              <div><span class="fl">You get</span><div class="pct"><input class="in" id="you_found" type="number" min="0" max="100" value="60">%</div></div>
              <div><span class="fl">Studio gets</span><div id="them_found" style="font-size:20px;font-weight:800;padding:8px 0">40%</div></div>
            </div>
            <div class="help">Enter the split you agreed with the studio. It's sent with your join request. Once confirmed, it's locked.</div>
          </div>
          <div class="lockbar" style="margin-top:12px;{{ $stripeConnected ? 'background:#EEFAF3;color:#1F6B4E' : '' }}"><span class="ms" style="font-size:17px">{{ $stripeConnected ? 'check_circle' : 'link' }}</span>{{ $stripeBits }}</div>
        </div>
      </div>
    </div>

    {{-- 09eg not found --}}
    <div class="cs-block" id="csPayManual" hidden>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3 id="csPayManTitle">How you get paid</h3><div class="faint" style="font-size:12.5px;margin-top:2px" id="csPayManSub"></div></div></div>
        <div style="padding:18px 22px">
          <label class="po sel"><input type="radio" name="po_manual" value="direct" checked><div style="flex:1"><b>Artist - Direct payment</b><div class="d">The full payment goes to your own Stripe account. Nothing is sent to the studio. Choose this option if you’re renting a workstation. You settle rent or fees with them outside Bookpay.</div></div></label>
          <label class="po"><input type="radio" name="po_manual" value="split"><div style="flex:1"><b>Invite the studio and set a revenue split</b><div class="d">Your studio will get an invite to join Bookpay. They will need to set up their Stripe account. Once they join and confirm, each payment is split between you.</div></div></label>
          <div class="splitbox" id="splitbox_manual" hidden>
            <div class="row" style="gap:12px;align-items:flex-start">
              <div class="stepn">1</div>
              <div style="flex:1">
                <b style="font-size:14px">Invite the studio</b>
                <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px;margin-top:8px">
                  <div><span class="fl">Studio name</span><input class="in" id="csInviteName" type="text"></div>
                  <div><span class="fl">Email</span><input class="in" id="csInviteEmail" type="email" placeholder="studio@example.com"></div>
                </div>
                <div class="help">They get an email to join Bookpay and set up their Stripe account. The invite is sent when you confirm.</div>
              </div>
            </div>
            <div class="row" style="gap:12px;align-items:flex-start;margin-top:16px">
              <div class="stepn">2</div>
              <div style="flex:1">
                <b style="font-size:14px">Enter the revenue split</b>
                <div class="row" style="gap:28px;margin-top:8px">
                  <div><span class="fl">You get</span><div class="pct"><input class="in" id="you_manual" type="number" min="0" max="100" value="50">%</div></div>
                  <div><span class="fl">Studio gets</span><div id="them_manual" style="font-size:20px;font-weight:800;padding:8px 0">50%</div></div>
                </div>
                <div class="help" id="csPayManSplitHelp">You're paid directly until the studio joins Bookpay and confirms this split. Then it starts, for new bookings.</div>
              </div>
            </div>
          </div>
          <div class="lockbar" style="margin-top:12px;{{ $stripeConnected ? 'background:#EEFAF3;color:#1F6B4E' : '' }}"><span class="ms" style="font-size:17px">{{ $stripeConnected ? 'check_circle' : 'link' }}</span>{{ $stripeBits }}</div>
          <div class="cs-err" id="csPayError" hidden></div>
        </div>
      </div>
    </div>

    {{-- 09eh own space --}}
    <div class="cs-block" id="csPayOwn" hidden>
      <div class="card" style="margin-bottom:14px">
        <div class="ch"><div><h3>How you get paid in your own space</h3><div class="faint" style="font-size:12.5px;margin-top:2px">You work from your own space, so payments go straight to you.</div></div></div>
        <div style="padding:18px 22px">
          <div class="po sel" style="cursor:default"><span class="ms" style="color:#3E007C;font-variation-settings:'FILL' 1">check_circle</span><div><b>Artist - Direct payment</b><div class="d">The full payment goes to your own Stripe account. There's no split.</div></div></div>
          <div class="lockbar" style="margin-top:12px;{{ $stripeConnected ? 'background:#EEFAF3;color:#1F6B4E' : '' }}"><span class="ms" style="font-size:17px">{{ $stripeConnected ? 'check_circle' : 'link' }}</span>{{ $stripeBits }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ========== STEP: CONFIRM ========== --}}
  <div class="cs-panel" data-panel="confirm" hidden>
    <div class="card" style="margin-bottom:14px">
      <div class="ch"><div><h3>Check and confirm</h3><div class="faint" style="font-size:12.5px;margin-top:2px">Nothing changes until you confirm.</div></div></div>
      <div style="padding:18px 22px">
        <div class="hsum" id="csSummary"></div>
        <div class="hwarn red" id="csPauseWarn" hidden>
          <span class="ms">pause_circle</span>
          <div><b>New bookings pause until you add where you work</b><br>Clients need an address to book you.</div>
        </div>
        <div class="hwarn" id="csUpcomingWarn" @if(($upcomingBookingsCount ?? 0) < 1) hidden @endif>
          <span class="ms">event</span>
          <div><b><span id="csUpcomingN">{{ $upcomingBookingsCount }}</span> upcoming bookings stay at {{ $currentLabel }}</b><br>They keep their address and how they're paid. Message those clients if a session needs to move.</div>
        </div>
        <ul class="blist" id="csBullets"></ul>
      </div>
    </div>
  </div>

  <div class="csbar">
    <a class="btn ghost sp cscancel" href="{{ route('settings.studio') }}">Cancel</a>
    <button type="button" class="btn ghost" id="csBack" hidden><span class="ms">arrow_back</span>Back</button>
    <button type="button" class="btn" id="csNext" hidden><span class="ms" id="csNextIcon">arrow_forward</span><span id="csNextLabel">Next</span></button>
  </div>
</div>

<div class="cs-toast" id="csToast" role="status"><span class="ms">check_circle</span><span id="csToastMsg">Saved</span></div>
@endsection

@section('scripts')
@if(config('services.google.place_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
@endif
<script>
(function () {
  var root = document.getElementById('changeStudioWizard');
  if (!root) return;

  var mode = root.dataset.mode || 'change';
  var currentName = root.dataset.currentName || 'your studio';
  var upcoming = parseInt(root.dataset.upcoming || '0', 10) || 0;
  var typeLabels = { private: 'Private Studio', shop: 'Tattoo Shop', home: 'Home Studio', collective: 'Collective' };
  var relLabels = {
    co_owner: 'Co-owner', resident: 'Resident', collective_member: 'Collective Member',
    apprentice: 'Apprentice', other: 'Other (Contract Artist, Freelancer)'
  };

  var state = {
    step: 'studio',
    path: null,
    from_link: null,
    pick: null,
    relationship: null,
    workspace_type: null,
    payout_mode: 'direct',
    revenue_split: 60,
    manual: {},
    own: {},
    invite: { name: '', email: '' },
  };

  var panels = {
    studio: root.querySelector('[data-panel="studio"]'),
    payment: root.querySelector('[data-panel="payment"]'),
    confirm: root.querySelector('[data-panel="confirm"]'),
  };
  var searchCard = document.getElementById('csSearchCard');
  var foundBlock = document.getElementById('csFoundBlock');
  var manualBlock = document.getElementById('csManualBlock');
  var ownBlock = document.getElementById('csOwnBlock');
  var noneBlock = document.getElementById('csNoneBlock');
  var searchInput = document.getElementById('csSearch');
  var resultsEl = document.getElementById('csResults');
  var searchLbl = document.getElementById('csSearchLbl');
  var searchEmpty = document.getElementById('csSearchEmpty');
  var backBtn = document.getElementById('csBack');
  var nextBtn = document.getElementById('csNext');
  var nextLabel = document.getElementById('csNextLabel');
  var nextIcon = document.getElementById('csNextIcon');
  var toast = document.getElementById('csToast');
  var toastMsg = document.getElementById('csToastMsg');
  var searchTimer = null;
  var findHint = document.getElementById('csFindHint');

  function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }
  function showToast(msg) {
    toastMsg.textContent = msg || 'Saved';
    toast.classList.add('on');
    setTimeout(function () { toast.classList.remove('on'); }, 2800);
  }
  function setErr(id, msg) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg || '';
    el.hidden = !msg;
  }
  function selectedRadio(name) {
    var el = root.querySelector('input[name="'+name+'"]:checked');
    return el ? el.value : null;
  }
  function val(id) { return (document.getElementById(id)?.value || '').trim(); }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
    });
  }

  function bindPo() {
    root.querySelectorAll('.po input[type=radio]').forEach(function (r) {
      if (r._csBound) return;
      r._csBound = true;
      r.addEventListener('change', function () {
        root.querySelectorAll('input[name="'+r.name+'"]').forEach(function (o) {
          o.closest('.po')?.classList.toggle('sel', o.checked);
        });
        if (r.name === 'po_found') {
          document.getElementById('splitbox_found').hidden = r.value !== 'split';
          state.payout_mode = r.value;
        }
        if (r.name === 'po_manual') {
          document.getElementById('splitbox_manual').hidden = r.value !== 'split';
          state.payout_mode = r.value;
        }
      });
    });
  }
  bindPo();

  function mapsUrl(parts) {
    var q = parts.filter(Boolean).join(', ').trim();
    if (!q) return '#';
    return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q);
  }
  function mapsLabel(parts) {
    var q = parts.filter(Boolean).join(', ').trim();
    return q ? ('google.com/maps · ' + q) : 'google.com/maps';
  }
  function syncMlbox(prefix) {
    var ids = prefix === 'man'
      ? ['csManStreetNo','csManStreet','csManCity','csManState','csManPostal','csManCountry']
      : ['csOwnStreetNo','csOwnStreet','csOwnCity','csOwnState','csOwnPostal','csOwnCountry'];
    var parts = ids.map(function (id) { return val(id); });
    // Prefer find-address line if structured fields empty
    var findId = prefix === 'man' ? 'csManAddress' : 'csOwnAddress';
    var find = val(findId);
    if (!parts.some(Boolean) && find) parts = [find];
    var url = mapsUrl(parts);
    var label = mapsLabel(parts.some(Boolean) ? parts : (find ? [find] : []));
    var a = document.getElementById(prefix === 'man' ? 'csManMapsLink' : 'csOwnMapsLink');
    if (a) { a.href = url; a.textContent = label; }
  }
  ['man','own'].forEach(function (prefix) {
    var ids = prefix === 'man'
      ? ['csManAddress','csManStreetNo','csManStreet','csManCity','csManState','csManPostal','csManCountry']
      : ['csOwnAddress','csOwnStreetNo','csOwnStreet','csOwnCity','csOwnState','csOwnPostal','csOwnCountry'];
    ids.forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('input', function () { syncMlbox(prefix); });
    });
    syncMlbox(prefix);
  });

  document.addEventListener('click', function (e) {
    var b = e.target.closest('.mlcopy');
    if (!b || !root.contains(b)) return;
    e.preventDefault();
    var a = b.parentNode.querySelector('a.mlk');
    var href = a ? a.href : '';
    if (!href || href === '#') return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(href).then(function () { showToast('Link copied'); });
    }
  });

  function stepsForPath(path) {
    if (path === 'no_studio') return ['studio', 'confirm'];
    return ['studio', 'payment', 'confirm'];
  }
  function renderSteps() {
    var steps = stepsForPath(state.path);
    root.querySelectorAll('[data-step-pill]').forEach(function (p) {
      var key = p.getAttribute('data-step-pill');
      var idx = steps.indexOf(key);
      if (idx < 0) { p.hidden = true; return; }
      p.hidden = false;
      p.querySelector('i').textContent = String(idx + 1);
      var cur = steps.indexOf(state.step);
      p.classList.toggle('on', idx === cur);
      p.classList.toggle('done', idx < cur);
    });
  }

  function showStudioSubpanel() {
    var path = state.path;
    // found: hide search; others keep search (09ec/09ed/09ee)
    searchCard.hidden = (path === 'found');
    foundBlock.hidden = path !== 'found';
    manualBlock.hidden = path !== 'not_on_bookpay';
    ownBlock.hidden = path !== 'own_space';
    noneBlock.hidden = path !== 'no_studio';
    if (findHint) findHint.hidden = !!(path && path !== 'found');
    if (path === 'not_on_bookpay' || path === 'own_space') {
      initAddressAutocomplete();
    }
  }

  function primaryLabel() {
    if (state.path === 'no_studio') return mode === 'add' ? 'Confirm' : ('Leave ' + currentName);
    if (mode === 'update') {
      if (state.path === 'not_on_bookpay' && state.payout_mode === 'split') return 'Update studio and send invitation';
      return 'Update studio';
    }
    if (state.path === 'found') return 'Send join request';
    if (mode === 'add') {
      if (state.path === 'not_on_bookpay' && state.payout_mode === 'split') return 'Add studio and send invitation';
      return 'Add studio';
    }
    return 'Change studio';
  }

  function renderPayment() {
    document.getElementById('csPayFound').hidden = state.path !== 'found';
    document.getElementById('csPayManual').hidden = state.path !== 'not_on_bookpay';
    document.getElementById('csPayOwn').hidden = state.path !== 'own_space';

    if (state.path === 'found' && state.pick) {
      var n = state.pick.name;
      document.getElementById('csPayFoundTitle').textContent = 'How you get paid at ' + n;
      document.getElementById('csPayFoundSub').textContent = n + ' is on Bookpay. Choose how bookings there are paid.';
      document.getElementById('csPayFoundDirectD').textContent = 'The full payment goes to your own Stripe account. You settle rent or fees with the studio outside Bookpay. ' + n + ' still gets your join request, but there\'s no split to confirm.';
      document.getElementById('csPayFoundSplitD').textContent = 'Each payment is split between you and ' + n + '. Your share goes to your Stripe account and theirs to theirs.';
      document.getElementById('csPayFoundPill').textContent = n + ' confirms when they accept you';
      state.payout_mode = selectedRadio('po_found') || 'split';
      document.getElementById('splitbox_found').hidden = state.payout_mode !== 'split';
      var yf = document.getElementById('you_found');
      yf.value = state.revenue_split;
      document.getElementById('them_found').textContent = (100 - state.revenue_split) + '%';
    }
    if (state.path === 'not_on_bookpay') {
      var sn = state.manual.studio_name || val('csManName') || 'the studio';
      document.getElementById('csPayManTitle').textContent = 'How you get paid at ' + sn;
      document.getElementById('csPayManSub').textContent = sn + " isn't on Bookpay yet. Choose how bookings there are paid.";
      document.getElementById('csPayManSplitHelp').textContent = "You're paid directly until " + sn + " joins Bookpay and confirms this split. Then it starts, for new bookings.";
      document.getElementById('csInviteName').value = state.invite.name || sn;
      state.payout_mode = selectedRadio('po_manual') || 'direct';
      document.getElementById('splitbox_manual').hidden = state.payout_mode !== 'split';
      var ym = document.getElementById('you_manual');
      if (state.payout_mode === 'split') {
        state.revenue_split = Math.max(0, Math.min(100, parseInt(ym.value || '50', 10)));
      }
      ym.value = state.payout_mode === 'split' ? state.revenue_split : (ym.value || 50);
      document.getElementById('them_manual').textContent = (100 - parseInt(ym.value || '50', 10)) + '%';
    }
    if (state.path === 'own_space') {
      state.payout_mode = 'direct';
    }
  }

  function payoutLabel() {
    if (state.payout_mode === 'split') {
      if (state.path === 'not_on_bookpay') {
        return 'Direct now · Revenue split You ' + state.revenue_split + '% · Studio ' + (100 - state.revenue_split) + '% once they join';
      }
      return 'Revenue split · You ' + state.revenue_split + '% · Studio ' + (100 - state.revenue_split) + '%';
    }
    if (state.path === 'found') return 'Direct payment · You 100% (no split)';
    return 'Direct payment · You 100%';
  }
  function whereLabel() {
    if (state.path === 'found' && state.pick) {
      var bits = [state.pick.name];
      if (state.pick.type) bits.push(state.pick.type);
      if (state.pick.address) bits.push(state.pick.address.split(',')[0]);
      return bits.join(' · ');
    }
    if (state.path === 'not_on_bookpay') {
      var t = typeLabels[state.workspace_type] || 'Studio';
      return (state.manual.studio_name || 'Studio') + ' · ' + t + ' (not on Bookpay)';
    }
    if (state.path === 'own_space') {
      var ot = typeLabels[state.workspace_type] || 'Private Studio';
      return (state.own.studio_name || 'Own space') + ' · ' + ot + ' (own space)';
    }
    return 'No studio';
  }

  function renderConfirm() {
    var whereKey = mode === 'add' ? 'Studio to add' : (mode === 'update' ? 'Studio' : 'Where you work');
    var html = '';
    html += '<div class="kv"><span>' + whereKey + '</span><span>' + escapeHtml(whereLabel()) + '</span></div>';
    if (state.path === 'found' || state.path === 'not_on_bookpay') {
      html += '<div class="kv"><span>Relationship</span><span>' + escapeHtml(relLabels[state.relationship] || '—') + '</span></div>';
    }
    html += '<div class="kv"><span>How you get paid</span><span>' + escapeHtml(payoutLabel()) + '</span></div>';
    document.getElementById('csSummary').innerHTML = html;

    document.getElementById('csPauseWarn').hidden = state.path !== 'no_studio';
    var up = document.getElementById('csUpcomingWarn');
    if (up) {
      if (mode === 'add' || upcoming < 1) up.hidden = true;
      else up.hidden = false;
    }

    var bullets = document.getElementById('csBullets');
    var items = [];
    if (mode === 'update') {
      items = [
        'We\'ll update this studio in place. No new workplace is added.',
        'Your other studios stay as they are.',
        'Your page and booking emails will use the updated details.'
      ];
    } else if (mode === 'add') {
      items = [currentName + ' stays one of your studios. Nothing changes there.'];
      if (state.path === 'found') {
        items.push('We\'ll send a join request. ' + currentName + ' stays one of your studios.');
      }
      items.push('Your page will show both places. Clients see where you work.');
    } else if (state.path === 'found') {
      var pn = state.pick ? state.pick.name : 'the studio';
      items = [
        'You stay at ' + currentName + ' until ' + pn + ' accepts you. Then the change starts.',
        'We\'ll let ' + currentName + ' know you left. They don\'t need to approve it.',
        'Your page and booking emails will show the new address.'
      ];
    } else if (state.path === 'no_studio') {
      items = [
        'The change starts now.',
        'We\'ll let ' + currentName + ' know you left. They don\'t need to approve it.'
      ];
    } else if (state.path === 'not_on_bookpay') {
      var sn = state.manual.studio_name || 'the studio';
      items = [
        state.payout_mode === 'split'
          ? 'The change starts now. We\'ll email ' + sn + ' an invite to join Bookpay. You\'re paid directly until they join and confirm the split.'
          : 'The change starts now, for new bookings. Nothing is sent to ' + sn + '.',
        'We\'ll let ' + currentName + ' know you left. They don\'t need to approve it.',
        'Your page and booking emails will show the new address.'
      ];
    } else {
      items = [
        'The change starts now, for new bookings.',
        'We\'ll let ' + currentName + ' know you left. They don\'t need to approve it.',
        'Your page and booking emails will show the new address.'
      ];
    }
    bullets.innerHTML = items.map(function (t) { return '<li>' + escapeHtml(t) + '</li>'; }).join('');
  }

  function render() {
    Object.keys(panels).forEach(function (k) {
      panels[k].hidden = k !== state.step;
    });
    if (state.step === 'studio') showStudioSubpanel();
    if (state.step === 'payment') renderPayment();
    if (state.step === 'confirm') renderConfirm();
    renderSteps();

    var bareSearch = state.step === 'studio' && !state.path;
    backBtn.hidden = bareSearch;
    nextBtn.hidden = bareSearch;

    if (!bareSearch) {
      var steps = stepsForPath(state.path);
      var last = steps[steps.length - 1];
      var isLast = state.step === last;
      nextLabel.textContent = isLast ? primaryLabel() : 'Next';
      nextIcon.textContent = isLast
        ? (state.path === 'no_studio' ? 'logout' : (state.path === 'found' ? 'send' : 'check'))
        : 'arrow_forward';
      nextBtn.style.background = (isLast && state.path === 'no_studio') ? '#C62828' : '';
    }
  }

  function validateStudioStep() {
    setErr('csPathError'); setErr('csRelFoundError'); setErr('csManualError'); setErr('csOwnError');
    if (!state.path) {
      setErr('csPathError', 'Pick a studio from search or choose an option below.');
      return false;
    }
    if (state.path === 'found') {
      state.relationship = selectedRadio('rel_found');
      if (!state.relationship) { setErr('csRelFoundError', 'Select your relationship.'); return false; }
      return true;
    }
    if (state.path === 'not_on_bookpay') {
      state.manual = {
        studio_name: val('csManName'),
        studio_address: val('csManAddress') || [val('csManStreetNo'), val('csManStreet'), val('csManCity'), val('csManState'), val('csManPostal'), val('csManCountry')].filter(Boolean).join(', '),
        street_number: val('csManStreetNo'), street_name: val('csManStreet'),
        city: val('csManCity'), postal_code: val('csManPostal'),
        state: val('csManState'), country: val('csManCountry'),
        google_maps_link: (document.getElementById('csManMapsLink') || {}).href || '',
      };
      if (state.manual.google_maps_link === '#' || state.manual.google_maps_link === window.location.href) {
        state.manual.google_maps_link = mapsUrl([
          state.manual.street_number, state.manual.street_name, state.manual.city,
          state.manual.state, state.manual.postal_code, state.manual.country
        ].filter(Boolean));
      }
      state.workspace_type = selectedRadio('stype_manual');
      state.relationship = selectedRadio('rel_manual');
      if (!state.manual.studio_name || !state.manual.studio_address || !state.workspace_type || !state.relationship) {
        setErr('csManualError', 'Fill studio details, type, and relationship.');
        return false;
      }
      return true;
    }
    if (state.path === 'own_space') {
      state.own = {
        studio_name: val('csOwnName'),
        studio_address: val('csOwnAddress') || [val('csOwnStreetNo'), val('csOwnStreet'), val('csOwnCity'), val('csOwnState'), val('csOwnPostal'), val('csOwnCountry')].filter(Boolean).join(', '),
        street_number: val('csOwnStreetNo'), street_name: val('csOwnStreet'),
        city: val('csOwnCity'), postal_code: val('csOwnPostal'),
        state: val('csOwnState'), country: val('csOwnCountry'),
        google_maps_link: (document.getElementById('csOwnMapsLink') || {}).href || '',
      };
      if (state.own.google_maps_link === '#' || state.own.google_maps_link === window.location.href) {
        state.own.google_maps_link = mapsUrl([
          state.own.street_number, state.own.street_name, state.own.city,
          state.own.state, state.own.postal_code, state.own.country
        ].filter(Boolean));
      }
      state.workspace_type = selectedRadio('stype_own');
      if (!state.own.studio_name || !state.own.studio_address || !state.workspace_type) {
        setErr('csOwnError', 'Fill your space name, address, and type.');
        return false;
      }
      return true;
    }
    return true;
  }

  function validatePaymentStep() {
    setErr('csPayError');
    if (state.path === 'own_space') {
      state.payout_mode = 'direct';
      return true;
    }
    if (state.path === 'found') {
      state.payout_mode = selectedRadio('po_found') || 'direct';
      if (state.payout_mode === 'split') {
        state.revenue_split = Math.max(0, Math.min(100, parseInt(document.getElementById('you_found').value || '60', 10)));
      }
      return true;
    }
    state.payout_mode = selectedRadio('po_manual') || 'direct';
    if (state.payout_mode === 'split') {
      state.revenue_split = Math.max(0, Math.min(100, parseInt(document.getElementById('you_manual').value || '50', 10)));
      state.invite.name = val('csInviteName');
      state.invite.email = val('csInviteEmail');
      if (!state.invite.email) {
        setErr('csPayError', 'Enter the studio email to invite them for a split.');
        return false;
      }
    }
    return true;
  }

  function goNext() {
    if (state.step === 'studio') {
      if (!validateStudioStep()) return;
      var steps = stepsForPath(state.path);
      state.step = steps[1];
      render();
      return;
    }
    if (state.step === 'payment') {
      if (!validatePaymentStep()) return;
      state.step = 'confirm';
      render();
      return;
    }
    submitWizard();
  }
  function goBack() {
    if (state.step === 'studio' && state.path) {
      state.path = null;
      state.pick = null;
      root.querySelectorAll('input[name=nf]').forEach(function (r) {
        r.checked = false; r.closest('.po')?.classList.remove('sel');
      });
      resultsEl.hidden = true;
      searchLbl.hidden = true;
      searchEmpty.hidden = true;
      render();
      searchInput.focus();
      return;
    }
    var steps = stepsForPath(state.path);
    var idx = steps.indexOf(state.step);
    if (idx > 0) {
      state.step = steps[idx - 1];
      render();
    }
  }

  function payload() {
    var data = {
      mode: mode,
      path: state.path,
      payout_mode: state.payout_mode,
      revenue_split: state.revenue_split,
      relationship: state.relationship,
      workspace_type: state.workspace_type,
    };
    if (state.from_link) data.from_link = state.from_link;
    if (state.path === 'found' && state.pick) {
      data.studio_id = state.pick.id;
      data.studio_name = state.pick.name;
    }
    if (state.path === 'not_on_bookpay') {
      Object.assign(data, state.manual);
      data.invite_studio_name = state.invite.name;
      data.invite_studio_email = state.invite.email;
    }
    if (state.path === 'own_space') Object.assign(data, state.own);
    return data;
  }

  function submitWizard() {
    nextBtn.disabled = true;
    var body = new FormData();
    var data = payload();
    Object.keys(data).forEach(function (k) {
      if (data[k] !== null && data[k] !== undefined && data[k] !== '') body.append(k, data[k]);
    });
    fetch(root.dataset.storeUrl, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
      body: body,
    })
      .then(function (r) {
        return r.json().then(function (d) { return { ok: r.ok, data: d }; }).catch(function () {
          return { ok: false, data: null };
        });
      })
      .then(function (res) {
        if (res.ok && res.data && res.data.success) {
          showToast(res.data.message || 'Saved');
          setTimeout(function () { location.href = res.data.redirect || @json(route('settings.studio')); }, 700);
          return;
        }
        var msg = (res.data && (res.data.message || res.data.error)) || 'Could not save.';
        if (res.data && res.data.errors) {
          var first = Object.values(res.data.errors)[0];
          if (Array.isArray(first) && first[0]) msg = first[0];
        }
        showToast(msg);
      })
      .catch(function () { showToast('Network error.'); })
      .finally(function () { nextBtn.disabled = false; });
  }

  function runSearch(q) {
    if (q.length < 2) {
      resultsEl.hidden = true; searchLbl.hidden = true; searchEmpty.hidden = true; resultsEl.innerHTML = '';
      return;
    }
    fetch(root.dataset.searchUrl + '?q=' + encodeURIComponent(q), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var list = data.results || [];
        if (!list.length) {
          searchEmpty.hidden = false;
          searchEmpty.textContent = 'No studios on Bookpay match “' + q + '”.';
          searchLbl.hidden = true;
          resultsEl.hidden = true;
          resultsEl.innerHTML = '';
          return;
        }
        searchEmpty.hidden = true;
        searchLbl.hidden = false;
        searchLbl.textContent = 'Results for “' + q + '” · ' + list.length + ' studio' + (list.length === 1 ? '' : 's');
        resultsEl.hidden = false;
        resultsEl.innerHTML = list.map(function (s) {
          return '<button type="button" class="result" data-id="'+s.id+'">'
            + '<div class="logo2" style="width:40px;height:40px">'+escapeHtml(s.ini)+'</div>'
            + '<div><b style="font-size:14px">'+escapeHtml(s.name)+'</b>'
            + '<div class="faint" style="font-size:12px">'+escapeHtml((s.type||'')+(s.address?' · '+s.address:''))+'</div></div>'
            + '<span class="pill g" style="margin-left:auto">On Bookpay</span></button>';
        }).join('');
        resultsEl.querySelectorAll('.result').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var id = +btn.getAttribute('data-id');
            var s = list.find(function (x) { return x.id === id; });
            if (!s) return;
            pickFound(s);
          });
        });
      });
  }

  function pickFound(s) {
    state.path = 'found';
    state.pick = s;
    // Keep from_link in update mode (same row). For add/change, only when re-picking that past Bookpay studio.
    if (mode !== 'update' && state.from_link && state._fromLinkStudioId && state._fromLinkStudioId !== s.id) {
      state.from_link = null;
    }
    state.revenue_split = 60;
    document.getElementById('csFoundIni').textContent = s.ini || 'ST';
    document.getElementById('csFoundName').textContent = s.name;
    document.getElementById('csFoundType').textContent = s.type || 'Studio';
    document.getElementById('csFoundAddr').textContent = s.address || '—';
    document.getElementById('csFoundManaged').textContent = 'Managed by ' + s.name;
    var mapUrl = s.maps || mapsUrl([s.address]);
    var mapLink = document.getElementById('csFoundMapLink');
    mapLink.href = mapUrl;
    mapLink.textContent = 'Open in Google Maps';
    var note = document.getElementById('csFoundNote');
    if (mode === 'update') {
      note.innerHTML = '<b>We\'ll update your link with ' + escapeHtml(s.name) + '.</b> Relationship and payout details save on this studio. No new workplace is added.';
    } else if (mode === 'add') {
      note.innerHTML = '<b>We\'ll send ' + escapeHtml(s.name) + ' a request to join.</b> They see the relationship and split you pick and confirm them when they accept. ' + escapeHtml(currentName) + ' stays one of your studios.';
    } else {
      note.innerHTML = '<b>We\'ll send ' + escapeHtml(s.name) + ' a request to join.</b> They see the relationship and split you pick and confirm them when they accept. Until then you stay at ' + escapeHtml(currentName) + '.';
    }
    root.querySelectorAll('input[name=nf]').forEach(function (r) {
      r.checked = false; r.closest('.po')?.classList.remove('sel');
    });
    render();
  }

  searchInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    var q = searchInput.value.trim();
    searchTimer = setTimeout(function () { runSearch(q); }, 220);
  });

  root.querySelectorAll('input[name=nf]').forEach(function (r) {
    r.addEventListener('change', function () {
      if (!r.checked) return;
      state.path = r.value;
      state.pick = null;
      // Update mode always edits the same workplace row.
      if (mode !== 'update') {
        state.from_link = null;
        state._fromLinkStudioId = null;
      }
      if (r.value === 'found') return;
      if (r.value === 'not_on_bookpay') state.revenue_split = 50;
      if (r.value === 'own_space') state.payout_mode = 'direct';
      render();
    });
  });

  document.getElementById('csChangePick')?.addEventListener('click', function (e) {
    e.preventDefault();
    state.path = null; state.pick = null; render();
    searchInput.focus();
  });

  document.getElementById('you_found')?.addEventListener('input', function () {
    var n = Math.max(0, Math.min(100, parseInt(this.value || '0', 10)));
    state.revenue_split = n;
    document.getElementById('them_found').textContent = (100 - n) + '%';
  });
  document.getElementById('you_manual')?.addEventListener('input', function () {
    var n = Math.max(0, Math.min(100, parseInt(this.value || '0', 10)));
    state.revenue_split = n;
    document.getElementById('them_manual').textContent = (100 - n) + '%';
  });

  nextBtn.addEventListener('click', goNext);
  backBtn.addEventListener('click', goBack);

  function fillAddressFromPlace(prefix, place) {
    if (!place || !place.address_components) return;
    var map = {};
    place.address_components.forEach(function (c) {
      (c.types || []).forEach(function (t) { map[t] = c.long_name; });
      if ((c.types || []).indexOf('administrative_area_level_1') !== -1) {
        map.administrative_area_level_1 = c.short_name || c.long_name;
      }
    });
    var ids = prefix === 'man'
      ? { sn: 'csManStreetNo', st: 'csManStreet', city: 'csManCity', state: 'csManState', zip: 'csManPostal', country: 'csManCountry', find: 'csManAddress' }
      : { sn: 'csOwnStreetNo', st: 'csOwnStreet', city: 'csOwnCity', state: 'csOwnState', zip: 'csOwnPostal', country: 'csOwnCountry', find: 'csOwnAddress' };
    setInput(ids.sn, map.street_number || '');
    setInput(ids.st, map.route || '');
    setInput(ids.city, map.locality || map.postal_town || map.administrative_area_level_2 || '');
    setInput(ids.state, map.administrative_area_level_1 || '');
    setInput(ids.zip, map.postal_code || '');
    setInput(ids.country, map.country || '');
    var formatted = place.formatted_address || '';
    var findEl = document.getElementById(ids.find);
    if (findEl) findEl.value = formatted;
    var link = place.place_id
      ? ('https://www.google.com/maps/place/?q=place_id:' + place.place_id)
      : mapsUrl([formatted]);
    var a = document.getElementById(prefix === 'man' ? 'csManMapsLink' : 'csOwnMapsLink');
    if (a) {
      a.href = link;
      a.textContent = 'google.com/maps · ' + (formatted || '');
    } else {
      syncMlbox(prefix);
    }
  }

  function initAddressAutocomplete() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) return;
    [
      { id: 'csManAddress', prefix: 'man' },
      { id: 'csOwnAddress', prefix: 'own' },
    ].forEach(function (cfg) {
      var input = document.getElementById(cfg.id);
      if (!input || input._csAc) return;
      input._csAc = true;
      var ac = new google.maps.places.Autocomplete(input, {
        types: ['address'],
        fields: ['address_components', 'formatted_address', 'place_id', 'geometry'],
      });
      ac.addListener('place_changed', function () {
        fillAddressFromPlace(cfg.prefix, ac.getPlace());
      });
    });
  }

  function setRadio(name, value) {
    if (!value) return;
    var el = root.querySelector('input[name="'+name+'"][value="'+value+'"]');
    if (!el) return;
    el.checked = true;
    root.querySelectorAll('input[name="'+name+'"]').forEach(function (o) {
      o.closest('.po')?.classList.toggle('sel', o.checked);
    });
  }
  function setInput(id, value) {
    var el = document.getElementById(id);
    if (!el) return;
    el.value = value == null ? '' : String(value);
  }
  function applyPrefill() {
    var rawEl = document.getElementById('csPrefillJson');
    if (!rawEl) return;
    var raw = (rawEl.textContent || '').trim();
    if (!raw || raw === 'null') return;
    var p;
    try { p = JSON.parse(raw); } catch (e) { return; }
    if (!p || !p.path) return;

    if (p.from_link) state.from_link = p.from_link;
    if (p.path === 'found' && p.pick && p.pick.id) state._fromLinkStudioId = p.pick.id;
    if (p.search_q) searchInput.value = p.search_q;

    if (p.path === 'found' && p.pick) {
      pickFound(p.pick);
      if (p.relationship) setRadio('rel_found', p.relationship);
      state.relationship = p.relationship || null;
      state.workspace_type = p.workspace_type || (p.pick.workspace_type || null);
      return;
    }

    if (p.path === 'not_on_bookpay') {
      state.path = 'not_on_bookpay';
      state.revenue_split = p.revenue_split != null ? p.revenue_split : 50;
      state.payout_mode = p.payout_mode || 'direct';
      state.relationship = p.relationship || null;
      state.workspace_type = p.workspace_type || 'shop';
      setRadio('nf', 'not_on_bookpay');
      setInput('csManName', p.studio_name);
      setInput('csManAddress', p.studio_address);
      setInput('csManStreetNo', p.street_number);
      setInput('csManStreet', p.street_name);
      setInput('csManCity', p.city);
      setInput('csManState', p.state);
      setInput('csManPostal', p.postal_code);
      setInput('csManCountry', p.country);
      setRadio('stype_manual', state.workspace_type);
      setRadio('rel_manual', state.relationship);
      setInput('csInviteName', p.studio_name);
      setInput('you_manual', String(state.revenue_split));
      var them = document.getElementById('them_manual');
      if (them) them.textContent = (100 - state.revenue_split) + '%';
      if (state.payout_mode === 'split') {
        setRadio('po_manual', 'split');
        var sb = document.getElementById('splitbox_manual');
        if (sb) sb.hidden = false;
      }
      syncMlbox('man');
      if (p.google_maps_link) {
        var a = document.getElementById('csManMapsLink');
        if (a) {
          a.href = p.google_maps_link;
          a.textContent = 'google.com/maps · ' + (p.studio_address || p.studio_name || '');
        }
      }
      render();
      return;
    }

    if (p.path === 'own_space') {
      state.path = 'own_space';
      state.payout_mode = 'direct';
      state.workspace_type = p.workspace_type || 'private';
      setRadio('nf', 'own_space');
      setInput('csOwnName', p.studio_name);
      setInput('csOwnAddress', p.studio_address);
      setInput('csOwnStreetNo', p.street_number);
      setInput('csOwnStreet', p.street_name);
      setInput('csOwnCity', p.city);
      setInput('csOwnState', p.state);
      setInput('csOwnPostal', p.postal_code);
      setInput('csOwnCountry', p.country);
      setRadio('stype_own', state.workspace_type);
      syncMlbox('own');
      if (p.google_maps_link) {
        var ao = document.getElementById('csOwnMapsLink');
        if (ao) {
          ao.href = p.google_maps_link;
          ao.textContent = 'google.com/maps · ' + (p.studio_address || p.studio_name || '');
        }
      }
      render();
    }
  }

  applyPrefill();
  if (!state.path) render();

  if (document.readyState === 'complete') initAddressAutocomplete();
  else window.addEventListener('load', initAddressAutocomplete);
})();
</script>
@endsection
