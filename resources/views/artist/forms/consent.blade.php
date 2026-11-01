@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Client Flow')

@php
  $studioName = trim((string) ($studioName ?? Auth::user()->userDetail?->studio_name ?? ''));
  $s = $consentSettings ?? [];
  $market = strtoupper((string) ($s['studio_market'] ?? $defaultMarketCountry ?? 'GR'));
  $consentStudios = $consentStudios ?? [];
  $previewUrl = route('public.consent.preview', ['artist' => Auth::id()]);
  $otherLang = strtolower((string) ($s['other_language'] ?? ''));
@endphp

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  /* —— Bookpay consent page —— */
  .cs-page{max-width:800px}
  .cs-page .sec{margin-bottom:14px}
  .cs-page .sec.card{overflow:hidden}
  .cs-page .srow{
    display:flex;gap:16px;align-items:flex-start;justify-content:space-between;
    padding:16px 20px;border-bottom:1px solid #F3EEF4
  }
  .cs-page .srow:last-child{border-bottom:0}
  .cs-page .srow b{font-size:14px;display:block}
  .cs-page .srow .faint{font-size:12.5px;line-height:1.5;margin-top:2px}
  .cs-page .sech{
    display:flex;gap:12px;align-items:center;padding:16px 20px;border-bottom:1px solid #F0E9F2
  }
  .cs-page .sech .ic{width:36px;height:36px;border-radius:10px}
  .cs-page .sech h3{font-size:15px;font-weight:700;margin:0}
  .cs-page .sech .faint{font-size:12.5px}
  .cs-page .lbl2{
    font-size:12px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;margin-bottom:8px;color:var(--muted)
  }
  .cs-page .lock{display:flex;gap:6px;align-items:center;font-size:13px;white-space:nowrap;color:var(--pri);font-weight:600}
  .cs-page .gfield{
    border:1px dashed #E1D5E6;background:#fff;border-radius:10px;padding:9px 13px;font-size:13px;margin-top:8px;color:#3a3a3a
  }
  .cs-page .tint-box{background:var(--tint);border:1px solid var(--tintline);border-radius:12px;padding:16px}
  .cs-page .warn-box{
    display:flex;align-items:flex-start;gap:8px;border-radius:10px;padding:10px 12px;
    background:#FFF3DE;color:#7A5210;font-size:12.5px;line-height:1.5;margin-top:12px
  }
  .cs-page .warn-box .ms{color:#B86E00;font-size:18px;flex-shrink:0}
  .cs-page .info-box{
    display:flex;align-items:flex-start;gap:8px;border-radius:10px;padding:10px 12px;
    background:#FFF3DE;color:#7A5210;font-size:12.5px;line-height:1.5
  }
  .cs-page .ok-box{display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;color:var(--pri)}
  .cs-page .lang{
    flex:1;border:1px solid #E8DFEA;border-radius:12px;padding:12px 14px;background:#fff;cursor:pointer;text-align:left
  }
  .cs-page .lang.sel,.cs-page .lang.border-primary{border:1.5px solid #1A1A1A;background:#FDFAFF}
  .cs-page .bar{height:5px;border-radius:3px;background:#EDE6F0;flex:1;overflow:hidden}
  .cs-page .bar > span,.cs-page .consent-lang-bar > span{display:block;height:100%;background:#3E007C;width:0;border-radius:3px}
  .cs-page .consent-lang-bar{height:5px;border-radius:3px;background:#EDE6F0;overflow:hidden;flex:1}
  .cs-page .foot{display:flex;justify-content:space-between;gap:10px;margin-top:6px;flex-wrap:wrap;align-items:center}
  .cs-page select.in,.cs-page input.in,.cs-page textarea.in{
    width:100%;font:inherit;font-size:14px;color:var(--ink)
  }
  .cs-page select.in{appearance:auto;cursor:pointer}
  .cs-page textarea.in{min-height:72px;resize:vertical}

  /* toggles (JS uses .toggle-switch.active) — also outside .cs-page for modals */
  .toggle-switch,.cs-page .tog{
    width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;
    cursor:pointer;border:0;padding:0;display:inline-block
  }
  .toggle-switch:after,.cs-page .tog:after{
    content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s
  }
  .toggle-switch.active,.cs-page .tog.on,.cs-page .tog.active{background:var(--pri)}
  .toggle-switch.active:after,.cs-page .tog.on:after,.cs-page .tog.active:after{left:19px}
  .cf-icon-btn{
    width:28px;height:28px;border:0;background:none;border-radius:8px;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;color:var(--faint);padding:0
  }
  .cf-icon-btn .ms{font-size:18px}
  .cf-icon-btn:hover{background:#F7F3F9;color:var(--ink)}
  .cf-icon-btn.del:hover{background:#FDECEC;color:#C62828}
  .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .field-err.hidden,.hidden{display:none!important}

  /* question rows */
  .cs-page .q-row,.cs-page .js-artist-consent-row{
    display:flex;gap:12px;align-items:center;padding:12px 20px;border-bottom:1px solid #F3EEF4
  }
  .cs-page .q-row:hover{background:#FBF7FC}
  .cs-page .q-row.disabled,.cs-page .q-row.off{opacity:.55}
  .cs-page .q-drag-handle{cursor:grab;flex-shrink:0;color:var(--faint)}
  .cs-page .q-text{flex:1;min-width:0;font-size:14px}
  .cs-page .q-text p{margin:0;line-height:1.4;overflow-wrap:anywhere}
  .cs-page .q-meta{display:flex;align-items:center;gap:8px;flex-shrink:0}
  .cs-page .badge{
    font-size:11px;font-weight:700;border-radius:6px;padding:3px 8px;white-space:nowrap
  }
  .cs-page .badge-toggle{background:#F6E6FA;color:#8E24AA}
  .cs-page .badge-select{background:var(--bluel);color:var(--blue)}
  .cs-page .badge-textarea{background:var(--chip);color:var(--muted)}
  .cs-page .cf-icon-btn{
    width:28px;height:28px;border:0;background:none;border-radius:8px;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;color:var(--faint);padding:0
  }
  .cs-page .cf-icon-btn .ms{font-size:18px}
  .cs-page .cf-icon-btn:hover{background:#F7F3F9;color:var(--ink)}
  .cs-page .cf-icon-btn.del:hover{background:#FDECEC;color:#C62828}
  .cs-page .js-artist-consent-add{
    display:flex;justify-content:center;align-items:center;gap:6px;margin:14px 20px 18px;
    background:#1A1A1A;color:#fff;border:0;border-radius:10px;padding:9px;font-weight:600;font-size:13.5px;cursor:pointer;width:calc(100% - 40px)
  }
  .cs-page .js-artist-consent-reset{
    display:inline-flex;align-items:center;gap:4px;border:1px solid var(--line);background:#fff;
    border-radius:8px;padding:5px 10px;font-size:12px;font-weight:600;cursor:pointer;color:var(--muted)
  }
  .cs-page .q-row.consent-lang-highlight{
    outline:2px solid #3E007C;outline-offset:-2px;background:#FBF7FC!important
  }
  .cs-page .q-row.consent-lang-missing .q-text{color:#9A929E;font-style:italic}
  .cs-page .sortable-ghost{opacity:.45;background:#FBF7FC}
  .cs-page .sortable-chosen{cursor:grabbing}

  /* JS utility compatibility (no Tailwind on this layout) */
  .cs-page .hidden,.hidden{display:none!important}
  .cs-page .text-primary,.text-primary{color:#3E007C!important}
  .cs-page .text-on-surface,.text-on-surface{color:var(--ink)!important}
  .cs-page .text-on-surface-variant,.text-on-surface-variant{color:var(--muted)!important}
  .cs-page .text-error,.text-error{color:#C62828!important}
  .cs-page .italic{font-style:italic}
  .cs-page .opacity-50{opacity:.5}
  .cs-page .pointer-events-none{pointer-events:none}
  .cs-page .bg-primary\/5,.cs-page .lang.bg-primary\/5{background:#F3E8FF!important}
  .cs-page .border-primary,.cs-page .lang.border-primary{border-color:#3E007C!important}
  .cs-page .border-outline-variant\/30{border-color:#E8DFEA!important}
  .cs-page .bg-white{background:#fff!important}
  .cs-page .bg-stone-200{background:#E7E5E4!important}
  .cs-page .text-stone-400{color:#A8A29E!important}
  .cs-page .text-white{color:#fff!important}
  .cs-page .cursor-not-allowed{cursor:not-allowed!important}
  .cs-page .field-err{color:#C62828;font-size:12px;margin-top:6px}

  /* Select2 */
  .cs-page .select2-container{width:100%!important;font-family:inherit}
  .select2-container--open{z-index:10060!important}
  .cs-page .select2-container--default .select2-selection--single{
    height:40px;min-height:40px;border:1px solid var(--line)!important;border-radius:10px!important;
    background:#fff!important;padding:0 8px 0 4px
  }
  .cs-page .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:38px;padding-left:9px;color:var(--ink);font-size:13.5px
  }
  .cs-page .select2-container--default .select2-selection--single .select2-selection__arrow{height:38px;right:6px}
  .cs-page .select2-container--default.select2-container--focus .select2-selection--single,
  .cs-page .select2-container--default.select2-container--open .select2-selection--single{
    border-color:#3E007C!important;box-shadow:0 0 0 3px #F3E8FF!important
  }
  .select2-dropdown{border:1px solid var(--line);border-radius:12px;overflow:hidden;font-family:'Plus Jakarta Sans',system-ui,sans-serif}
  .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
    background:#3E007C;color:#fff
  }

  /* Modals */
  .cs-page .modal-backdrop,#artistConsentQuestionModal,#artistConsentDeleteModal,#consentSaveModal,#consentPreviewModal{
    position:fixed;inset:0;background:rgba(26,26,26,.45);z-index:300;
    align-items:center;justify-content:center;padding:24px
  }
  #consentSaveModal,#consentPreviewModal{display:none}
  #consentSaveModal:not(.hidden),#consentPreviewModal:not(.hidden){display:flex!important}
  .cs-page .pv .lt{display:inline-flex;border:1px solid #E8DFEA;border-radius:10px;padding:3px;gap:2px;margin-bottom:12px}
  .cs-page .pv .lt span{padding:5px 12px;border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer}
  .cs-page .pv .lt span.on{background:#1A1A1A;color:#fff}
  .cs-page .pv h4{font-size:14px;font-weight:700;margin:16px 0 8px}
  .cs-page .pv .ck{display:flex;gap:10px;align-items:flex-start;font-size:13.5px;line-height:1.45;margin:8px 0}
  .cs-page .pv .ck i{width:16px;height:16px;border:1.5px solid #CFC6D4;border-radius:4px;flex-shrink:0;margin-top:2px;display:block}
  .cs-page .pv .it{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:8px 0;border-bottom:1px solid #F3EEF4;font-size:13.5px}
  .cs-page .pv .yn{display:flex;gap:6px;flex-shrink:0}
  .cs-page .pv .yn span{border:1px solid #E8DFEA;border-radius:8px;padding:4px 10px;font-size:12px;font-weight:600;color:var(--muted)}
  .cs-page .studio-banner .pill.g{background:var(--greenl);color:var(--green)}
  .cs-page .cfm,.cs-modal-panel{
    background:#fff;border-radius:16px;border:1px solid var(--line);width:100%;max-width:480px;
    max-height:calc(100vh - 48px);overflow:auto;box-shadow:0 18px 50px rgba(0,0,0,.18)
  }
  .cs-page .mp-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s;pointer-events:none
  }
  .cs-page .mp-toast.on{opacity:1;transform:none}
  .cs-page .mp-toast .ms{color:#3DD68C;font-size:20px}

  @media (max-width:640px){
    .cs-page .q-row{flex-wrap:wrap;padding:12px 16px;row-gap:8px}
    .cs-page .srow{flex-wrap:wrap;gap:12px}
    .cs-page .mp-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
<div class="cs-page">
  <div class="head">
    <div>
      <h1>
        Client Flow
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201393-dashboard-client-flow-consent-form" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">What clients fill in and receive, from first request to healed tattoo.</div>
    </div>
  </div>

  @include('artist.partials.client-flow-tabs', ['clientFlowTab' => 'consent'])

  @if(count($consentStudios) > 0)
    <div class="card" style="margin-bottom:14px;background:#FBF6FF;border-color:#E3D2F5;overflow:hidden">
      <div style="padding:14px 18px 12px">
        <b style="font-size:14px">Consent forms at your studios</b>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Studios can use their own consent form for sessions there.</div>
      </div>
      @foreach($consentStudios as $studioRow)
        <div class="row" style="gap:12px;padding:12px 18px;border-top:1px solid #EFE3F5;align-items:flex-start">
          <span class="ms" style="color:#3E007C;margin-top:1px">storefront</span>
          <div style="flex:1;font-size:13px;line-height:1.5">
            @if(!empty($studioRow['uses_own_form']))
              <b>{{ $studioRow['name'] }} uses its own consent form</b>
              <div class="faint" style="font-size:12.5px">Clients booked with you there sign the studio's form instead of yours.</div>
            @else
              <b>{{ $studioRow['name'] }} uses your consent form</b>
              <div class="faint" style="font-size:12.5px">Clients booked with you there sign your form.</div>
            @endif
          </div>
          @if(!empty($studioRow['uses_own_form']))
            <a class="btn sm ghost" href="{{ $previewUrl }}" target="_blank" rel="noopener" style="text-decoration:none;flex:none"><span class="ms" style="font-size:16px">visibility</span>Preview</a>
          @else
            <span class="pill g" style="flex:none">Your form</span>
          @endif
        </div>
      @endforeach
      <div style="padding:10px 18px 14px;border-top:1px solid #EFE3F5;font-size:12.5px;color:#5E5763">Your form below is used everywhere else: your own space, other studios and guest spots where the studio has no form.</div>
    </div>
  @endif

  <div id="consent-settings-panel">
    {{-- Send automatically --}}
    <div class="card sec">
      <div class="srow">
        <div>
          <b>Send automatically</b>
          <div class="faint">When turned on, Bookpay sends the consent form to clients 48 hours before their appointment. If you prefer not to send it automatically, you can show a QR code at the session for the client to scan and sign.</div>
        </div>
        <div class="row" style="gap:8px;align-items:center;flex-shrink:0">
          <button type="button" id="consent-send-auto-toggle" class="toggle-switch js-consent-toggle" role="switch" aria-checked="false"></button>
          <span id="consent-send-auto-label" style="font-size:13px;width:28px">Off</span>
        </div>
      </div>
    </div>

    {{-- Studio market --}}
    <div class="sec">
      <div class="lbl2">Studio market</div>
      <select id="consent-studio-market" class="in js-consent-market" aria-label="Studio market">
        @foreach(($registrationCountries ?? []) as $country)
          <option value="{{ $country['code'] }}" @selected(strtoupper((string) $country['code']) === $market)>{{ $country['name'] ?? $country['label'] ?? $country['code'] }}</option>
        @endforeach
      </select>
      <div class="help" style="margin-top:6px">Sets the legal sections and the languages of your form</div>
    </div>

    {{-- Studio details --}}
    <div class="card sec">
      <div class="srow" style="align-items:center">
        <div>
          <b>Studio{{ $studioName !== '' ? ': '.$studioName : '' }}</b>
          <div class="faint">Printed on every signed consent record.</div>
        </div>
        <a href="{{ route('settings.studio') }}" class="btn sm ghost" style="text-decoration:none;flex-shrink:0">Change<span class="ms" style="font-size:16px">chevron_right</span></a>
      </div>
      <div style="padding:16px 20px">
        <div class="lbl2">Local registration / licence no. <span style="text-transform:none;font-weight:500;color:var(--faint)">(optional)</span></div>
        <input class="in" id="consent-registration-number" type="text" value="{{ $s['registration_number'] ?? '' }}" placeholder="Licence registration number, if applicable in your country">
        <div id="consent-governing-law-wrap" class="hidden" style="margin-top:14px">
          <div class="lbl2">Governing law</div>
          <select id="consent-governing-law" class="in">
            <option>England and Wales</option>
            <option>Scotland</option>
            <option>Northern Ireland</option>
          </select>
        </div>
      </div>
    </div>

    {{-- Age requirement --}}
    <div class="card sec">
      <div class="sech">
        <div class="ic"><span class="ms">verified_user</span></div>
        <div><h3>Age requirement</h3></div>
      </div>
      <div class="srow">
        <div>
          <b>18 years or older</b>
          <div class="faint">Clients confirm their date of birth online. You check it against valid photo ID in person before the session. This can't be turned off.</div>
        </div>
        <div class="lock"><span class="ms" style="font-size:16px">lock</span> Fixed</div>
      </div>
      <div class="srow" style="border-bottom:0;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
          <b>Allow younger clients with guardian consent</b>
          <div class="faint">You decide whether this applies to your business. Check your local law before turning it on. Bookpay hasn't verified this for your market.</div>
        </div>
        <button type="button" id="consent-allow-younger-toggle" class="toggle-switch js-consent-toggle" role="switch" aria-checked="false"></button>
      </div>
      <div id="consent-age-blocked-note" class="hidden" style="padding:0 20px 16px">
        <div class="warn-box"><span class="ms">warning</span><span>Tattooing anyone under 18 is a criminal offence in the UK, regardless of guardian consent.</span></div>
      </div>
      <div id="consent-younger-extra" class="hidden" style="padding:0 20px 18px">
        <div class="tint-box">
          <div class="lbl2">Minimum age for this exception</div>
          <input class="in" id="consent-min-age" type="number" min="1" max="17" value="{{ $s['age_allow'] ?? 16 }}" style="width:110px">
          <div class="lbl2" style="margin-top:14px">Added to your form for clients under 18</div>
          <div class="gfield">Guardian full name</div>
          <div class="gfield">Guardian relationship to client</div>
          <div class="gfield">Guardian ID reference</div>
          <div class="gfield">Guardian signature (typed name)</div>
          <p class="faint" style="margin-top:10px;font-size:12.5px;line-height:1.5">When a consent form is for a client under 18, the guardian fields appear on the booking, so the guardian can complete and sign in person at the studio.</p>
          <label class="row" style="gap:10px;align-items:flex-start;margin-top:12px;font-size:13px;line-height:1.5;cursor:pointer">
            <input type="checkbox" id="consent-minor-law-ack" style="margin-top:3px;width:16px;height:16px;accent-color:#3E007C">
            <span>I've confirmed my local law allows tattooing minors with guardian consent, and I know the minimum age and requirements that apply in <span id="consent-market-country-label">your market</span>.</span>
          </label>
          <p id="consent-minor-ack-error" class="field-err hidden" role="alert"></p>
        </div>
      </div>
    </div>

    {{-- Form language --}}
    <div class="card sec">
      <div class="sech">
        <div class="ic"><span class="ms">language</span></div>
        <div>
          <h3>Form Language: <span id="consent-form-lang-title-langs">English</span></h3>
          <div class="faint" id="consent-form-lang-desc">Your fixed boilerplate default is English for this market, so there's no second language to author here yet.</div>
        </div>
      </div>

      <div id="consent-languages-dual" class="hidden">
        <div id="consent-local-lang-wrap" class="srow hidden" style="align-items:center">
          <div>
            <b>Local language</b>
            <div class="faint" id="consent-local-lang-help">This market has more than one official language. Pick the one your clients read.</div>
          </div>
          <select class="in" id="consent-local-language" style="width:170px" aria-label="Local language" data-initial="{{ $otherLang }}"></select>
        </div>
        <div class="srow">
          <div>
            <b>Offer both languages to clients</b>
            <div class="faint">When turned on, clients can choose between the languages below. Make sure both translations are 100% complete before enabling.</div>
          </div>
          <button type="button" id="consent-both-languages-toggle" class="toggle-switch js-consent-toggle" role="switch" aria-checked="false"></button>
        </div>
        <div class="row" style="gap:10px;padding:16px 20px;border-bottom:1px solid #F3EEF4">
          <button type="button" id="consent-lang-chip-en" class="lang sel border-primary bg-primary/5">
            <b style="font-size:13.5px">English</b>
            <div class="row" style="gap:8px;margin-top:8px;align-items:center">
              <div class="consent-lang-bar"><span id="consent-lang-en-bar"></span></div>
              <span id="consent-lang-en-pct" style="font-size:11px;color:var(--muted);font-weight:600">0%</span>
            </div>
          </button>
          <button type="button" id="consent-lang-chip-second" class="lang border-outline-variant/30 bg-white">
            <b style="font-size:13.5px"><span id="consent-second-lang-chip-label">Studio language</span></b>
            <div class="row" style="gap:8px;margin-top:8px;align-items:center">
              <div class="consent-lang-bar"><span id="consent-lang-second-bar"></span></div>
              <span id="consent-lang-second-pct" style="font-size:11px;color:var(--muted);font-weight:600">0%</span>
            </div>
          </button>
        </div>
        <div style="padding:14px 20px">
          <div id="consent-clients-toggle-ready" class="ok-box hidden"><span class="ms" style="font-size:16px">check</span> Clients will see a language toggle on this form.</div>
          <div id="consent-clients-toggle-pending" class="info-box">
            <span class="ms">info</span>
            <span>Complete every question in <span id="consent-lang-hint-label">the studio language</span> before you unlock the language for clients.</span>
          </div>
        </div>
      </div>

      <div style="padding:0 20px 16px">
        <button type="button" id="consent-goto-translation-btn" class="btn hidden" style="width:100%;justify-content:center">
          Go to question needing translation (<span class="js-missing-count">0</span>)
        </button>
        <p id="consent-lang-complete-msg" class="hidden" style="font-size:12.5px;font-weight:600;color:var(--pri);margin-top:8px">All enabled questions have a translation.</p>
      </div>
    </div>
  </div>

  @include('artist.forms.partials.consent-questions')

  {{-- Data & photo --}}
  <div class="card sec" style="margin-top:14px">
    <div class="sech">
      <div>
        <h3>Data &amp; photo consent</h3>
        <div class="faint" id="consent-data-photo-law">Required under GDPR for this market.</div>
      </div>
    </div>
    <div class="srow">
      <div>
        <b>Separate health information consent</b>
        <div class="faint">Risk terms and health-data sharing are agreed as two distinct steps, not one bundled checkbox.</div>
      </div>
      <div class="lock"><span class="ms" style="font-size:16px">lock</span> Required</div>
    </div>
    <div class="srow">
      <div>
        <b>Data retention notice + acknowledgement</b>
        <div class="faint">Standard legal wording, plus an active checkbox confirming the client has seen it.</div>
      </div>
      <div class="lock"><span class="ms" style="font-size:16px">lock</span> Required</div>
    </div>
    <div class="srow">
      <div>
        <b>Ask to use tattoo photos in your portfolio</b>
        <div class="faint">Shown as its own opt-in checkbox, unchecked by default, in every market.</div>
      </div>
      <button type="button" id="consent-ask-photo-toggle" class="toggle-switch js-consent-toggle" role="switch" aria-checked="false"></button>
    </div>
  </div>

  <div class="foot">
    <button type="button" class="btn ghost" id="btnPreviewConsentInline"><span class="ms">visibility</span>Preview</button>
    <div class="row" style="gap:12px;align-items:center;margin-left:auto">
      <p id="consent-save-error" class="field-err hidden" role="alert" style="margin:0;text-align:right;max-width:280px"></p>
      <button type="button" class="btn" id="btnSaveConsentSettings"><span class="ms">save</span>Save changes</button>
    </div>
  </div>

  {{-- Inline preview modal (08b) --}}
  <div id="consentPreviewModal" class="hidden" role="dialog" aria-modal="true" aria-labelledby="consentPreviewTitle">
    <div class="cs-modal-panel" style="max-width:560px;padding:0;display:flex;flex-direction:column;max-height:calc(100vh - 48px)" onclick="event.stopPropagation()">
      <div class="row" style="justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #F0EAF2;flex-shrink:0">
        <h3 id="consentPreviewTitle" style="font-size:18px;font-weight:800;margin:0">Preview · Consent form</h3>
        <button type="button" class="cf-icon-btn" id="btnCloseConsentPreview" aria-label="Close"><span class="ms">close</span></button>
      </div>
      <div id="consentPreviewBody" style="padding:16px 22px;overflow-y:auto;min-height:0;flex:1"></div>
      <div class="row" style="justify-content:space-between;gap:10px;padding:14px 22px;border-top:1px solid #F0E9F2;flex-shrink:0;flex-wrap:wrap">
        <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer" class="btn ghost" style="text-decoration:none"><span class="ms">open_in_new</span>Open client form</a>
        <button type="button" class="btn" id="btnCloseConsentPreview2">Close</button>
      </div>
    </div>
  </div>

  {{-- Save ack modal --}}
  <div id="consentSaveModal" class="hidden" role="dialog" aria-modal="true">
    <div class="cs-modal-panel" style="padding:22px 24px;max-width:420px" onclick="event.stopPropagation()">
      <h3 style="font-size:16px;font-weight:800;margin:0 0 10px">Before you publish</h3>
      <label class="row" style="gap:10px;align-items:flex-start;cursor:pointer;margin-bottom:18px;font-size:13.5px;line-height:1.5">
        <input id="consentSaveAck" type="checkbox" style="margin-top:3px;width:16px;height:16px;accent-color:#3E007C">
        <span>The health conditions, risk text, and aftercare above are mine to edit. Bookpay hasn't reviewed this content for legal sufficiency in my country, and I'm responsible for making sure it meets local requirements.</span>
      </label>
      <div class="row" style="gap:10px;justify-content:flex-end">
        <button type="button" class="btn ghost" id="consentSaveModalCancel">Cancel</button>
        <button type="button" class="btn bg-stone-200 text-stone-400 cursor-not-allowed" id="consentSaveConfirmBtn" disabled>Save</button>
      </div>
    </div>
  </div>

  <div class="mp-toast" id="csToast" role="status"><span class="ms">check_circle</span><span id="csToastText">Saved</span></div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.CONSENT_MARKET_COUNTRIES = @json($registrationCountries ?? []);
  window.CONSENT_DEFAULT_MARKET_COUNTRY = @json($defaultMarketCountry ?? 'GR');
  window.CONSENT_SETTINGS = @json($consentSettings ?? null);
  window.CONSENT_SETTINGS_SAVE_URL = @json(route('artist.forms.consent.settings'));
  window.CONSENT_QUESTIONS_SNAPSHOT = @json(($consentQuestions ?? collect())->values());
  window.CONSENT_PREVIEW_URL = @json($previewUrl);
  window.CONSENT_STUDIO_NAME = @json($studioName);
</script>
<script src="{{ asset('js/artist-consent-questions.js') }}?v={{ @filemtime(public_path('js/artist-consent-questions.js')) }}"></script>
<script src="{{ asset('js/artist-consent-settings.js') }}?v={{ @filemtime(public_path('js/artist-consent-settings.js')) }}"></script>
<script>
window.showSaveToast = function (msg) {
  var t = document.getElementById('csToast');
  var tx = document.getElementById('csToastText');
  if (!t || !tx) return;
  tx.textContent = msg || 'Saved';
  t.classList.add('on');
  clearTimeout(t._t);
  t._t = setTimeout(function () { t.classList.remove('on'); }, 2600);
};
</script>
@endsection
