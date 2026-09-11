@extends('layouts.artist_dashboard_layout')

@section('title', 'Content form')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .q-row.consent-lang-highlight {
    outline: 2px solid #310f7a;
    outline-offset: -2px;
    background: #f8f1fb !important;
    transition: background 0.2s ease, outline-color 0.2s ease;
  }
  /* Match Forms (Available Design / Custom Request) question list pattern */
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
  .q-row.disabled { opacity: 0.45; }
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
  #consent-form-root .select2-container { width: 100% !important; z-index: 1; }
  .select2-container--open { z-index: 10060 !important; }
  #consent-form-root .select2-container--default .select2-selection--single {
    min-height: 48px;
    padding: 6px 12px;
    border-radius: 0.75rem;
    border: 1px solid rgba(202,196,211,0.5) !important;
    background: #fff !important;
  }
  #consent-form-root .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 2.25rem;
    padding-left: 4px;
    color: #1c1b21;
  }
  #consent-form-root .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
  #consent-form-root .select2-container--default.select2-container--focus .select2-selection--single,
  #consent-form-root .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #310f7a !important;
    box-shadow: 0 0 0 2px rgba(49,15,122,0.25);
  }
  .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
  .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #310f7a !important; }
  .select2-container--default .select2-search--dropdown .select2-search__field {
    border-radius: 0.5rem;
    border-color: rgba(202,196,211,0.5);
  }
  #consent-form-root [data-consent-hide-questions="1"] { display: none !important; }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">
    @php
      $bookingPageUsername = Auth::user()->userDetail->user_name ?? null;
      $bookingPageUrl = $bookingPageUsername ? 'https://inkjin.com/@'.$bookingPageUsername : null;
    @endphp
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
      <a href="{{ route('artist.forms.consent') }}" class="form-tab active">Content form</a>
    </div>

    {{-- Market / studio / age first (React), then consent questions below — same order as before --}}
    <div id="consent-form-root" class="max-w-3xl"></div>

    @include('artist.forms.partials.consent-questions')
  </div>
</main>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.CONSENT_MARKET_COUNTRIES = @json($registrationCountries ?? []);
  window.CONSENT_DEFAULT_MARKET_COUNTRY = @json($defaultMarketCountry ?? 'GR');
  window.CONSENT_SETTINGS = @json($consentSettings ?? null);
  window.CONSENT_SETTINGS_SAVE_URL = @json(route('artist.forms.consent.settings'));
  window.CONSENT_STRINGS_URLS = {
    boilerplate: @json(asset('data/consent-boilerplate-strings.json')),
    defaultItems: @json(asset('data/consent-default-items-strings.json'))
  };
  window.CONSENT_MANAGE_QUESTIONS_IN_BLADE = true;
  window.ARTIST_CONSENT_URLS = {
    store: @json(route('artist.forms.consent-questions.store')),
    update: @json(route('artist.forms.consent-questions.update', ['id' => '__ID__'])),
    status: @json(route('artist.forms.consent-questions.status', ['id' => '__ID__'])),
    reorder: @json(route('artist.forms.consent-questions.reorder')),
    destroy: @json(route('artist.forms.consent-questions.destroy', ['id' => '__ID__']))
  };
</script>
<script src="{{ asset('js/artist-consent-questions.js') }}?v={{ @filemtime(public_path('js/artist-consent-questions.js')) }}"></script>
<script crossorigin src="https://unpkg.com/react@18.3.1/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js"></script>
<script src="{{ asset('js/consent-form-settings.js') }}?v={{ @filemtime(public_path('js/consent-form-settings.js')) }}"></script>
<script>
  // Hide React checklist sections once mounted (questions managed in Blade). Keep Languages.
  (function () {
    if (!window.CONSENT_MANAGE_QUESTIONS_IN_BLADE) return;
    function hideReactQuestionSections() {
      var root = document.getElementById("consent-form-root");
      if (!root) return;
      root.querySelectorAll("section").forEach(function (section) {
        var title = (section.querySelector("h2") || {}).textContent || "";
        if (/Health conditions|Risks|Aftercare instructions/i.test(title)) {
          section.style.display = "none";
        }
      });
    }
    hideReactQuestionSections();
    var observer = new MutationObserver(hideReactQuestionSections);
    var root = document.getElementById("consent-form-root");
    if (root) observer.observe(root, { childList: true, subtree: true });
  })();
</script>
@endsection
