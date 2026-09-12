@extends('layouts.artist_dashboard_layout')

@section('title', 'Consent form')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .q-row.consent-lang-highlight {
    outline: 2px solid #310f7a;
    outline-offset: -2px;
    background: #f8f1fb !important;
    transition: background 0.2s ease, outline-color 0.2s ease;
  }
  .q-drag-handle {
    cursor: grab;
    touch-action: none;
    flex-shrink: 0;
    user-select: none;
    -webkit-user-select: none;
  }
  .q-drag-handle:active { cursor: grabbing; }
  .q-row.sortable-chosen { opacity: 0.4; }
  .toggle-switch {
    position: relative;
    width: 40px;
    height: 22px;
    background: #cac4d3;
    border-radius: 11px;
    cursor: pointer;
    transition: background 0.2s;
    flex-shrink: 0;
    border: none;
  }
  .toggle-switch.active { background: #310f7a; }
  .toggle-switch::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 18px;
    height: 18px;
    background: white;
    border-radius: 50%;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  }
  .toggle-switch.active::after { transform: translateX(18px); }
  .q-row {
    transition: all 0.15s ease;
    border-bottom: 1px solid rgba(202, 196, 211, 0.15);
    align-items: flex-start;
  }
  .q-row:hover { background: #f8f1fb; }
  .q-row:last-child { border-bottom: none; }
  .q-row > .material-symbols-outlined:not(.q-drag-handle) {
    flex-shrink: 0;
    margin-top: 2px;
  }
  .q-text {
    flex: 0 1 auto;
    min-width: 0;
    max-width: 90ch;
  }
  .q-text p {
    white-space: pre-line;
    overflow-wrap: anywhere;
    word-break: break-word;
    line-height: 1.4;
  }
  .q-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
    white-space: nowrap;
    margin-top: 2px;
    margin-left: 0;
  }
  .q-row .badge,
  .q-row .toggle-switch,
  .q-row .js-artist-consent-edit,
  .q-row .js-artist-consent-delete {
    flex-shrink: 0;
  }
  .questions-list-scroll {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    touch-action: pan-x pan-y;
  }
  .questions-list { min-width: 0; }
  .q-row .q-drag-handle {
    position: sticky;
    left: 0;
    z-index: 1;
    background: #ffffff;
    padding-right: 2px;
    margin-top: 2px;
  }
  .q-row:hover .q-drag-handle { background: #f8f1fb; }
  @media (max-width: 639px) {
    .q-text {
      max-width: min(90ch, calc(100vw - 11rem));
      flex: 1 1 auto;
    }
    .q-meta { flex: 0 0 auto; }
    .questions-list, .q-row { min-width: max-content; }
  }
  @media (min-width: 640px) {
    .q-row { align-items: center; width: 100%; }
    .q-text { flex: 1 1 auto; max-width: none; min-width: 0; }
    .q-meta { margin-left: auto; }
    .q-row > .material-symbols-outlined:not(.q-drag-handle),
    .q-row .q-drag-handle,
    .q-meta { margin-top: 0; }
    .questions-list-scroll { overflow-x: visible; }
    .questions-list, .q-row { min-width: 0; }
  }
  .sortable-ghost { opacity: 0.45; background: #f8f1fb; }
  .sortable-chosen { cursor: grabbing; }
  .form-tab {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    color: #494552;
    transition: all 0.2s;
    background: none;
    border-top: none;
    border-left: none;
    border-right: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
  }
  .form-tab.active { color: #310f7a; border-bottom-color: #310f7a; }
  .form-tab:hover:not(.active) { color: #1c1b21; }
  .badge {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    white-space: nowrap;
  }
  .badge-select { background: #dbeafe; color: #1d4ed8; }
  .badge-text { background: #f3f4f6; color: #374151; }
  .badge-textarea { background: #f3f4f6; color: #374151; }
  .badge-toggle { background: #f3e8ff; color: #7c3aed; }
  .badge-system { background: #e5e7eb; color: #6b7280; }
  .badge-custom { background: #e8ddff; color: #310f7a; }
  #consent-settings-panel .select2-container { width: 100% !important; z-index: 1; }
  .select2-container--open { z-index: 10060 !important; }
  #consent-settings-panel .select2-container--default .select2-selection--single {
    min-height: 48px;
    padding: 6px 12px;
    border-radius: 0.75rem;
    border: 1px solid rgba(202,196,211,0.5) !important;
    background: #fff !important;
  }
  #consent-settings-panel .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 2.25rem;
    padding-left: 4px;
    color: #1c1b21;
  }
  #consent-settings-panel .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
  #consent-settings-panel .select2-container--default.select2-container--focus .select2-selection--single,
  #consent-settings-panel .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #310f7a !important;
    box-shadow: 0 0 0 2px rgba(49,15,122,0.25);
  }
  .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
  .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #310f7a !important; }
  .select2-container--default .select2-search--dropdown .select2-search__field {
    border-radius: 0.5rem;
    border-color: rgba(202,196,211,0.5);
  }
  .consent-lang-bar {
    height: 6px;
    border-radius: 9999px;
    background: #e7e5e4;
    overflow: hidden;
  }
  .consent-lang-bar > span {
    display: block;
    height: 100%;
    border-radius: 9999px;
    width: 0;
    background: #310f7a;
  }
</style>
@endsection

@section('content')
@php
  $bookingPageUsername = Auth::user()->userDetail->user_name ?? null;
  $bookingPageUrl = $bookingPageUsername ? 'https://inkjin.com/@'.$bookingPageUsername : null;
  $studioName = trim((string) (Auth::user()->userDetail?->studio_name ?? ''));
  $s = $consentSettings ?? [];
  $market = strtoupper((string) ($s['studio_market'] ?? $defaultMarketCountry ?? 'GR'));
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">
    <div class="mb-8">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Booking Page</h2>
          <p class="text-on-surface-variant mt-1">Manage your intake forms, available designs, portfolio and the style of your page</p>
        </div>
        @if ($bookingPageUrl)
          <a href="{{ $bookingPageUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline bg-primary/5 px-4 py-2 rounded-xl transition-colors shrink-0">
            <span class="material-symbols-outlined text-lg">open_in_new</span> Open your booking page
          </a>
        @endif
      </div>
    </div>

    @include('artist.partials.booking-page-tabs', ['activeTab' => 'forms'])

    <div class="mb-8">
      <p class="text-on-surface-variant">Customize the consent form clients complete before their session.</p>
    </div>

    <div class="flex border-b border-outline-variant/20 mb-6">
      <a href="{{ route('artist.forms.index', ['tab' => 'booking']) }}" class="form-tab">Available Design</a>
      <a href="{{ route('artist.forms.index', ['tab' => 'custom']) }}" class="form-tab">Custom Request</a>
      <a href="{{ route('artist.forms.consent') }}" class="form-tab active">Consent form</a>
    </div>

    <div id="consent-settings-panel" class="max-w-3xl">
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-on-surface tracking-tight" style="font-family:'Space Grotesk',sans-serif;">Consent form</h1>
        <p class="mt-1.5 text-sm text-on-surface-variant leading-relaxed">Sections start with sensible defaults - edit them to match your studio. A few structural pieces are set automatically based on your market.</p>
      </div>

      {{-- Send automatically --}}
      <div class="mb-4 rounded-xl border border-outline-variant/20 bg-white overflow-hidden">
        <div class="px-5 py-4 flex items-start justify-between gap-4">
          <div class="min-w-0">
            <p class="text-sm font-medium text-on-surface">Send automatically</p>
            <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">When on, Bookpay will automatically send the consent form to clients before their appointment.</p>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <button type="button" id="consent-send-auto-toggle" class="toggle-switch js-consent-toggle" role="switch" aria-checked="false"></button>
            <span id="consent-send-auto-label" class="text-xs font-semibold text-on-surface-variant min-w-[1.75rem]">Off</span>
          </div>
        </div>
      </div>

      {{-- Studio market --}}
      <div class="mb-6 relative">
        <label class="block text-xs font-medium text-on-surface-variant uppercase tracking-wide mb-1.5" for="consent-studio-market">Studio market</label>
        <select id="consent-studio-market" class="js-consent-market w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          @foreach(($registrationCountries ?? []) as $country)
            <option value="{{ $country['code'] }}" @selected(strtoupper((string) $country['code']) === $market)>{{ $country['name'] ?? $country['label'] ?? $country['code'] }}</option>
          @endforeach
        </select>
      </div>

      {{-- Studio details --}}
      <section class="mb-4 rounded-xl border border-outline-variant/20 bg-white overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/15">
          <h2 class="text-sm font-semibold text-on-surface">Studio{{ $studioName !== '' ? ': ' . $studioName : '' }}</h2>
          <p class="text-xs text-on-surface-variant mt-0.5">Printed on every signed consent record.</p>
        </div>
        <div class="px-5 py-4 space-y-3">
          <div>
            <label class="block text-xs font-medium text-on-surface-variant uppercase tracking-wide mb-1" for="consent-registration-number">Local registration / licence no.</label>
            <input id="consent-registration-number" type="text" value="{{ $s['registration_number'] ?? '' }}" placeholder="e.g. LON-TAT-004471" class="w-full rounded-lg border border-outline-variant/30 px-3 py-2 text-sm placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
          </div>
          <div id="consent-governing-law-wrap" class="hidden">
            <label class="block text-xs font-medium text-on-surface-variant uppercase tracking-wide mb-1" for="consent-governing-law">Governing law</label>
            <select id="consent-governing-law" class="w-full rounded-lg border border-outline-variant/30 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
              <option>England and Wales</option>
              <option>Scotland</option>
              <option>Northern Ireland</option>
            </select>
          </div>
        </div>
      </section>

      {{-- Age requirement --}}
      <section class="mb-4 rounded-xl border border-outline-variant/20 bg-white overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/15">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-[18px]">verified_user</span>
            <h2 class="text-sm font-semibold text-on-surface">Age requirement</h2>
          </div>
        </div>
        <div class="px-5 py-4 flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-medium text-on-surface">18 years or older</p>
            <p class="text-xs text-on-surface-variant mt-0.5">Client self-reports date of birth online. You confirm it against valid photo ID in person before the session - this cannot be turned off.</p>
          </div>
          <div class="flex items-center gap-1.5 text-xs font-medium text-primary shrink-0 pt-0.5">
            <span class="material-symbols-outlined text-[14px]">lock</span> Fixed
          </div>
        </div>
        <div class="h-px bg-outline-variant/15 mx-5"></div>
        <div class="px-5 py-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-on-surface">Allow younger clients with guardian consent</p>
              <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">You decide whether this applies to your business. Check your local law before turning it on - Bookpay hasn't verified this for your market.</p>
            </div>
            <button type="button" id="consent-allow-younger-toggle" class="toggle-switch js-consent-toggle shrink-0" role="switch" aria-checked="false"></button>
          </div>
          <div id="consent-age-blocked-note" class="hidden mt-3 flex items-start gap-2 rounded-lg bg-[#FDF6EE] px-3 py-2.5">
            <span class="material-symbols-outlined text-[#9A5B13] text-[16px] mt-0.5 shrink-0">warning</span>
            <p class="text-xs text-[#7A5210] leading-relaxed">Tattooing anyone under 18 is a criminal offence in the UK, regardless of guardian consent.</p>
          </div>
          <div id="consent-younger-extra" class="hidden mt-3 rounded-lg border border-outline-variant/20 bg-surface-container-low/50 px-4 py-3.5">
            <div class="mb-3">
              <label class="block text-xs font-medium text-on-surface-variant uppercase tracking-wide mb-1" for="consent-min-age">Minimum age for this exception</label>
              <input id="consent-min-age" type="number" min="1" max="17" value="{{ $s['age_allow'] ?? 16 }}" class="w-28 rounded-lg border border-outline-variant/30 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>
            <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant mb-2">Added to your form for clients under 18</p>
            <div class="space-y-2">
              <div class="flex items-center gap-2 rounded-md border border-dashed border-outline-variant/40 bg-white px-3 py-2">
                <span class="text-xs text-on-surface-variant">Guardian full name</span>
              </div>
              <div class="flex items-center gap-2 rounded-md border border-dashed border-outline-variant/40 bg-white px-3 py-2">
                <span class="text-xs text-on-surface-variant">Guardian relationship to client</span>
              </div>
              <div class="flex items-center gap-2 rounded-md border border-dashed border-outline-variant/40 bg-white px-3 py-2">
                <span class="text-xs text-on-surface-variant">Guardian ID reference</span>
              </div>
              <div class="flex items-center gap-2 rounded-md border border-dashed border-outline-variant/40 bg-white px-3 py-2">
                <span class="text-xs text-on-surface-variant">Guardian signature (typed name)</span>
              </div>
            </div>
            <p class="mt-2.5 text-xs text-on-surface-variant leading-relaxed">Guardian fields only appear when you turn on &quot;Allow younger clients with guardian consent.&quot;</p>
            <label class="mt-3 flex items-start gap-3 cursor-pointer">
              <input id="consent-minor-law-ack" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-outline-variant accent-primary">
              <span class="text-xs text-on-surface leading-relaxed">I've confirmed my local law allows tattooing minors with guardian consent, and I know the minimum age and requirements that apply in <span id="consent-market-country-label">your market</span>.</span>
            </label>
            <p id="consent-minor-ack-error" class="hidden mt-2 text-xs font-medium text-[#c2410c]" role="alert"></p>
          </div>
        </div>
      </section>

      {{-- Form Language --}}
      <section class="mb-4 rounded-xl border border-outline-variant/20 bg-white overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/15">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-[18px]">language</span>
            <h2 class="text-sm font-semibold text-on-surface">Form Language: <span id="consent-form-lang-title-langs">English</span></h2>
          </div>
          <p id="consent-form-lang-desc" class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">Your fixed boilerplate default is English for this market, so there's no second language to author here yet.</p>
        </div>

        <div id="consent-languages-dual" class="hidden">
          <div class="px-5 py-4 border-b border-outline-variant/15 flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-on-surface">Offer both languages to clients</p>
              <p class="text-xs text-on-surface-variant mt-0.5">Only turns on once both languages below are 100% complete.</p>
            </div>
            <button type="button" id="consent-both-languages-toggle" class="toggle-switch js-consent-toggle shrink-0" role="switch" aria-checked="false"></button>
          </div>
          <div class="px-5 py-4 flex items-center gap-2">
            <button type="button" id="consent-lang-chip-en" class="flex-1 rounded-lg border border-primary bg-primary/5 px-3 py-2.5 text-left">
              <p class="text-sm font-medium text-on-surface">English</p>
              <div class="mt-1.5 flex items-center gap-2">
                <div class="consent-lang-bar flex-1"><span id="consent-lang-en-bar"></span></div>
                <span id="consent-lang-en-pct" class="text-[10px] font-medium text-on-surface-variant">0%</span>
              </div>
            </button>
            <button type="button" id="consent-lang-chip-second" class="flex-1 rounded-lg border border-outline-variant/30 bg-white px-3 py-2.5 text-left">
              <p class="text-sm font-medium text-on-surface"><span id="consent-second-lang-chip-label">Studio language</span></p>
              <div class="mt-1.5 flex items-center gap-2">
                <div class="consent-lang-bar flex-1"><span id="consent-lang-second-bar"></span></div>
                <span id="consent-lang-second-pct" class="text-[10px] font-medium text-on-surface-variant">0%</span>
              </div>
            </button>
          </div>
          <div class="px-5 pb-4">
            <div id="consent-clients-toggle-ready" class="hidden flex items-center gap-1.5 text-xs font-medium text-primary">
              <span class="material-symbols-outlined text-[14px]">check</span>
              Clients will see a language toggle on this form.
            </div>
            <div id="consent-clients-toggle-pending" class="flex items-start gap-2 rounded-lg bg-[#FDF6EE] px-3 py-2.5">
              <span class="material-symbols-outlined text-[#9A5B13] text-[16px] mt-0.5 shrink-0">info</span>
              <p class="text-xs text-[#7A5210] leading-relaxed">Complete every question in <span id="consent-lang-hint-label">the studio language</span> before you unlock the language for clients.</p>
            </div>
          </div>
        </div>

        <div class="px-5 pb-4">
          <button type="button" id="consent-goto-translation-btn" class="hidden w-full inline-flex items-center justify-center gap-2 text-sm font-semibold text-white rounded-lg px-3 py-2.5 bg-primary hover:bg-primary-container">
            Go to question needing translation (<span class="js-missing-count">0</span>)
          </button>
          <p id="consent-lang-complete-msg" class="hidden text-xs font-medium text-primary">All enabled questions have a translation.</p>
        </div>
      </section>
    </div>

    @include('artist.forms.partials.consent-questions')

    <div class="max-w-3xl mt-6 space-y-4">
      {{-- Data & photo consent --}}
      <section class="rounded-xl border border-outline-variant/20 bg-white overflow-hidden">
        <div class="px-5 py-4 border-b border-outline-variant/15">
          <h2 class="text-sm font-semibold text-on-surface">Data &amp; photo consent</h2>
          <p class="text-xs text-on-surface-variant mt-0.5">Required under GDPR/UK GDPR for this market when applicable - otherwise recommended as good practice.</p>
        </div>
        <div class="px-5 py-4 space-y-3">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-on-surface">Separate health information consent</p>
              <p class="text-xs text-on-surface-variant mt-0.5">Risk terms and health-data sharing are agreed as two distinct steps, not one bundled checkbox.</p>
            </div>
            <div id="consent-health-split-required" class="hidden flex items-center gap-1.5 text-xs font-medium text-primary shrink-0 pt-0.5">
              <span class="material-symbols-outlined text-[14px]">lock</span> Required
            </div>
            <div id="consent-health-split-toggle-wrap" class="flex flex-col items-end gap-1 shrink-0">
              <button type="button" id="consent-health-split-toggle" class="toggle-switch js-consent-toggle active" role="switch" aria-checked="true"></button>
              <span class="text-[10px] text-on-surface-variant">Recommended</span>
            </div>
          </div>
          <div class="h-px bg-outline-variant/15"></div>
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-on-surface">Data retention notice + acknowledgment</p>
              <p class="text-xs text-on-surface-variant mt-0.5">Standard legal wording, plus an active checkbox confirming the client has seen it.</p>
            </div>
            <div id="consent-retention-required" class="hidden flex items-center gap-1.5 text-xs font-medium text-primary shrink-0 pt-0.5">
              <span class="material-symbols-outlined text-[14px]">lock</span> Required
            </div>
            <div id="consent-retention-toggle-wrap" class="flex flex-col items-end gap-1 shrink-0">
              <button type="button" id="consent-retention-toggle" class="toggle-switch js-consent-toggle active" role="switch" aria-checked="true"></button>
              <span class="text-[10px] text-on-surface-variant">Recommended</span>
            </div>
          </div>
          <div class="h-px bg-outline-variant/15"></div>
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-on-surface">Ask to use tattoo photos in your portfolio</p>
              <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">Shown as its own opt-in checkbox, unchecked by default, in every market.</p>
            </div>
            <button type="button" id="consent-ask-photo-toggle" class="toggle-switch js-consent-toggle shrink-0" role="switch" aria-checked="false"></button>
          </div>
        </div>
      </section>

      <div class="flex items-center justify-between gap-4">
        <p id="consent-save-error" class="hidden text-xs font-medium text-[#c2410c] leading-relaxed max-w-md" role="alert"></p>
        <button type="button" id="btnSaveConsentSettings" class="ml-auto inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary-container transition-colors">
          Save changes
        </button>
      </div>
    </div>
  </div>
</main>

<div id="consentSaveModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/40" role="dialog" aria-modal="true">
  <div class="w-full max-w-[380px] rounded-2xl bg-white p-6 shadow-xl" onclick="event.stopPropagation()">
    <p class="text-sm font-semibold text-on-surface mb-1.5">Before you publish</p>
    <label class="flex items-start gap-3 cursor-pointer mb-4">
      <input id="consentSaveAck" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-outline-variant accent-primary">
      <span class="text-sm leading-relaxed text-on-surface">The health conditions, risk text, and aftercare above are mine to edit. Bookpay hasn't reviewed this content for legal sufficiency in my country, and I'm responsible for making sure it meets local requirements.</span>
    </label>
    <div class="flex gap-2">
      <button type="button" id="consentSaveModalCancel" class="flex-1 rounded-lg border border-outline-variant/30 px-3 py-2.5 text-sm font-medium text-on-surface-variant">Cancel</button>
      <button type="button" id="consentSaveConfirmBtn" disabled class="flex-1 rounded-lg px-3 py-2.5 text-sm font-semibold bg-stone-200 text-stone-400 cursor-not-allowed">Save</button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.CONSENT_MARKET_COUNTRIES = @json($registrationCountries ?? []);
  window.CONSENT_DEFAULT_MARKET_COUNTRY = @json($defaultMarketCountry ?? 'GR');
  window.CONSENT_SETTINGS = @json($consentSettings ?? null);
  window.CONSENT_SETTINGS_SAVE_URL = @json(route('artist.forms.consent.settings'));
  window.CONSENT_QUESTIONS_SNAPSHOT = @json(($consentQuestions ?? collect())->values());
</script>
<script src="{{ asset('js/artist-consent-questions.js') }}?v={{ @filemtime(public_path('js/artist-consent-questions.js')) }}"></script>
<script src="{{ asset('js/artist-consent-settings.js') }}?v={{ @filemtime(public_path('js/artist-consent-settings.js')) }}"></script>
@endsection
