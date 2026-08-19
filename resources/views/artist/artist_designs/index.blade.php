@extends('layouts.artist_dashboard_layout')

@section('title', 'Artist Designs')

@section('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    /* Modal (animated open / close) */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.6);
      z-index: 200;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .modal-backdrop.modal-visible { display: flex; }
    .modal-backdrop.modal-visible:not(.modal-open) { pointer-events: none; }
    .modal-backdrop.modal-open { opacity: 1; pointer-events: auto; }
    #deleteDesignModal.modal-backdrop { z-index: 400; }
    .design-delete-modal-inner {
      transform: scale(0.96) translateY(10px);
      opacity: 0;
      transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
    }
    #deleteDesignModal.modal-open .design-delete-modal-inner {
      transform: scale(1) translateY(0);
      opacity: 1;
    }
    .new-design-modal-inner {
      transform: scale(0.96) translateY(10px);
      opacity: 0;
      transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
    }
    .modal-backdrop.modal-open .new-design-modal-inner {
      transform: scale(1) translateY(0);
      opacity: 1;
    }

    /* Other styles chip picker */
    .style-chip {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 8px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      border: 1px solid rgba(122, 117, 131, 0.35);
      background: #fff;
      color: #494552;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s, color 0.2s, box-shadow 0.2s;
    }
    .style-chip:hover:not(:disabled) {
      border-color: rgba(26, 26, 26, 0.35);
      background: #f8f1fb;
    }
    .style-chip.is-selected {
      border-color: #310f7a;
      background: #e8ddff;
      color: #310f7a;
      box-shadow: 0 1px 2px rgba(49, 15, 122, 0.12);
    }
    .style-chip:disabled { opacity: 0.42; cursor: not-allowed; }
    .style-chip .style-chip-check {
      display: none;
      font-size: 15px;
      font-variation-settings: 'FILL' 0, 'wght' 600, 'GRAD' 0, 'opsz' 20;
    }
    .style-chip.is-selected .style-chip-check { display: inline-flex; }

    /* Pricing settings accordion + choice cards */
    .pricing-accordion {
      background: #fff;
      border: 1px solid rgba(122, 117, 131, 0.2);
      border-radius: 1rem;
      box-shadow: 0 1px 2px rgba(26, 26, 26, 0.04);
      overflow: hidden;
    }
    .pricing-accordion-trigger {
      width: 100%;
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      padding: 1rem 1.1rem;
      background: transparent;
      border: 0;
      cursor: pointer;
      text-align: left;
    }
    .pricing-accordion-trigger:hover {
      background: #fbf8ff;
    }
    .pricing-accordion-icon {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: #eee8f8;
      color: #310f7a;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .pricing-accordion-copy {
      min-width: 0;
      flex: 1;
      padding-top: 0.1rem;
    }
    .pricing-accordion-title-row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.5rem;
    }
    .pricing-accordion-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: #1a1a1a;
      line-height: 1.2;
    }
    .pricing-accordion-badge {
      display: none;
      align-items: center;
      padding: 0.2rem 0.55rem;
      border-radius: 999px;
      background: #e8ddff;
      color: #310f7a;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      line-height: 1.2;
    }
    .pricing-accordion:not(.is-open) .pricing-accordion-badge {
      display: inline-flex;
    }
    .pricing-accordion-subtitle {
      margin-top: 0.35rem;
      font-size: 0.875rem;
      color: #7a7583;
      line-height: 1.4;
    }
    .pricing-accordion-chevron {
      color: #9a94a3;
      flex-shrink: 0;
      margin-top: 0.35rem;
      transition: transform 0.2s ease;
    }
    .pricing-accordion.is-open .pricing-accordion-chevron {
      transform: rotate(180deg);
    }
    .pricing-accordion-body {
      display: none;
      padding: 0 1.1rem 1.15rem;
      border-top: 1px solid rgba(122, 117, 131, 0.12);
    }
    .pricing-accordion.is-open .pricing-accordion-body {
      display: block;
    }
    .pricing-choice-card {
      display: flex;
      flex-direction: column;
      gap: 0.55rem;
      padding: 1.1rem 1.15rem;
      border-radius: 1rem;
      border: 1.5px solid rgba(122, 117, 131, 0.22);
      background: #fff;
      text-align: left;
      cursor: pointer;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
      box-shadow: 0 1px 2px rgba(26, 26, 26, 0.04);
      width: 100%;
    }
    .pricing-choice-card:hover {
      border-color: rgba(49, 15, 122, 0.35);
      background: #fbf8ff;
    }
    .pricing-choice-card.is-selected {
      border-color: #310f7a;
      box-shadow: 0 0 0 1px #310f7a, 0 1px 2px rgba(49, 15, 122, 0.08);
      background: #fff;
    }
    .pricing-choice-radio {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 2px solid #cac4d3;
      flex-shrink: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: border-color 0.2s, background 0.2s;
    }
    .pricing-choice-card.is-selected .pricing-choice-radio {
      border-color: #310f7a;
      background: #310f7a;
    }
    .pricing-choice-radio::after {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #fff;
      opacity: 0;
      transform: scale(0.6);
      transition: opacity 0.15s, transform 0.15s;
    }
    .pricing-choice-card.is-selected .pricing-choice-radio::after {
      opacity: 1;
      transform: scale(1);
    }
    .pricing-choice-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: #eee8f8;
      color: #310f7a;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .pricing-choice-tag {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: #7a7583;
      margin-top: auto;
    }
    .pricing-choice-card.is-selected .pricing-choice-tag {
      color: #310f7a;
    }

    /* Smart pricing table (design only) */
    .smart-pricing-input {
      min-width: 0;
      border: 1px solid #e2dee8 !important;
      border-radius: 12px !important;
      background: #fff !important;
      padding: 0.65rem 0.85rem;
      font-size: 0.875rem;
      font-weight: 500;
      color: #1a1a1a;
      text-align: center;
      transition: none;
      box-shadow: none !important;
      outline: none !important;
      --tw-ring-shadow: 0 0 #0000 !important;
      --tw-ring-offset-shadow: 0 0 #0000 !important;
    }
    .smart-pricing-input::placeholder {
      color: #9a94a3;
      opacity: 1;
    }
    .smart-pricing-input:focus::placeholder,
    .smart-pricing-input:focus-visible::placeholder,
    .smart-pricing-input:active::placeholder {
      color: transparent !important;
      opacity: 0 !important;
    }
    .smart-pricing-input:hover,
    .smart-pricing-input:focus,
    .smart-pricing-input:focus-visible,
    .smart-pricing-input:active {
      border-color: #e2dee8 !important;
      outline: none !important;
      box-shadow: none !important;
      --tw-ring-color: transparent !important;
      --tw-ring-shadow: 0 0 #0000 !important;
      --tw-ring-offset-shadow: 0 0 #0000 !important;
    }
    .smart-pricing-input--sm { width: 4rem; max-width: 4rem; }
    .smart-pricing-input--md { width: 5.25rem; max-width: 5.25rem; }
    .smart-pricing-input--duration { width: 5.75rem; max-width: 5.75rem; text-align: left; padding-left: 0.95rem; }
    .smart-pricing-table-wrap {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .smart-pricing-table {
      width: 100%;
      min-width: 720px;
      border-collapse: collapse;
    }
    .smart-pricing-table thead th {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #9a94a3;
      text-align: left;
      padding: 0 0.35rem 0.85rem;
      white-space: nowrap;
      border: 0;
      background: transparent;
    }
    .smart-pricing-table tbody tr {
      background: transparent;
    }
    .smart-pricing-table td {
      padding: 0.85rem 0.35rem;
      vertical-align: top;
      border: 0;
      border-top: 1px solid #ebe6f0;
      background: transparent;
    }
    .smart-pricing-field-stack {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 0.3rem;
    }
    .smart-pricing-size-cell,
    .smart-pricing-currency-cell,
    .smart-pricing-sessions-cell,
    .smart-pricing-duration-cell {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      white-space: nowrap;
      background: transparent;
      border: 0;
      border-radius: 0;
      padding: 0;
      min-height: 0;
      box-shadow: none;
    }
    .smart-pricing-affix {
      font-size: 0.875rem;
      font-weight: 500;
      color: #7a7583;
      flex-shrink: 0;
    }
    .smart-pricing-open-max {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1.25rem;
      color: #1a1a1a;
      font-size: 0.875rem;
      font-weight: 600;
      background: transparent;
      border-radius: 0;
      height: auto;
    }
    .smart-pricing-color-input-wrap {
      display: inline-flex;
      align-items: center;
    }
    .smart-pricing-color-input-wrap .smart-pricing-input {
      width: 4.75rem;
      max-width: 4.75rem;
    }
    .smart-pricing-remove-btn {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #9a94a3;
      background: transparent;
      border: 0;
      cursor: pointer;
      transition: color 0.15s, background 0.15s;
    }
    .smart-pricing-remove-btn:hover {
      color: #b3261e;
      background: #fceeed;
    }
    .smart-pricing-empty {
      padding: 1.5rem 0.75rem;
      text-align: center;
      color: #7a7583;
      font-size: 0.875rem;
    }
    .smart-pricing-input.is-invalid,
    .smart-pricing-input.is-invalid:hover,
    .smart-pricing-input.is-invalid:focus,
    .smart-pricing-input.is-invalid:focus-visible {
      border-color: #b3261e !important;
    }
    .smart-pricing-field-error {
      display: block;
      margin-top: 0.35rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #b3261e;
    }

    /* Image crop modal */
    .crop-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 300;
      background: rgba(0, 0, 0, 0.78);
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .crop-modal-backdrop.is-open { display: flex; }
    .crop-modal-inner { width: 100%; max-width: 560px; max-height: min(92vh, 900px); display: flex; flex-direction: column; }
    .cropper-stage-wrap {
      flex: 1;
      min-height: 200px;
      max-height: min(58vh, 520px);
      background: #1a1a1a;
      border-radius: 12px;
      overflow: hidden;
    }
    .cropper-stage-wrap img { max-height: min(58vh, 520px); display: block; }
    .cropper-stage-wrap .cropper-container,
    .cropper-stage-wrap .cropper-wrap-box,
    .cropper-stage-wrap .cropper-canvas img { max-height: min(58vh, 520px); }

    /* Toggle switch */
    .toggle-switch { position: relative; width: 44px; height: 24px; background: #cac4d3; border-radius: 12px; cursor: pointer; transition: background 0.2s; flex-shrink: 0; }
    .toggle-switch.active { background: #310f7a; }
    .toggle-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: white; border-radius: 50%; transition: transform 0.2s; }
    .toggle-switch.active::after { transform: translateX(20px); }

    /* Toggle badge pills */
    .toggle-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 8px; font-size: 11px; font-weight: 600; transition: all 0.2s; cursor: pointer; user-select: none; }
    .toggle-badge.on { background: #e8ddff; color: #310f7a; }
    .toggle-badge.off { background: #f2ecf5; color: #7a7583; }
    .toggle-badge .material-symbols-outlined { font-size: 14px; }

    /* Info tag */
    .info-tag { background: #e8ddff; color: #310f7a; }

    /* Filter pill */
    .filter-pill { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1.5px solid transparent; }
    .filter-pill.active { background: #310f7a; color: white; }
    .filter-pill:not(.active) { background: white; color: #494552; border-color: #cac4d3; }
    .filter-pill:not(.active):hover { background: #f8f1fb; border-color: #310f7a; }

    /* Design card */
    .design-card { transition: all 0.15s ease; }
    .design-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .design-card-wrap .design-image-wrap { position: relative; overflow: hidden; }
    .design-drag-handle {
      position: absolute;
      top: 0.75rem;
      right: 0.75rem;
      z-index: 3;
      width: 2rem;
      height: 2rem;
      border-radius: 0.625rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #1c1b1f;
      background: rgba(255, 255, 255, 0.92);
      border: 0;
      box-shadow: 0 1px 4px rgba(0,0,0,0.18);
      cursor: grab;
    }
    .design-drag-handle:active { cursor: grabbing; }
    .design-drag-handle .material-symbols-outlined { font-size: 1.125rem; }
    .design-card-wrap.sortable-ghost { opacity: 0.45; }
    .design-card-wrap.sortable-chosen .design-card { cursor: grabbing; box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
    body.design-sort-locked .design-drag-handle {
      opacity: 0.45;
      cursor: not-allowed;
    }

    /* Mobile overflow fixes */
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }

    .design-field-section.scroll-mt-6 { scroll-margin-top: 1.5rem; }

    /* New design upload: placeholder ratio; after crop, frame matches output image */
    .design-image-upload-slot {
      aspect-ratio: 4 / 5;
      max-height: 20rem;
      width: 100%;
      position: relative;
    }
    .design-image-upload-slot.has-preview {
      aspect-ratio: var(--design-preview-ar, 4 / 5);
      max-height: min(20rem, min(70vw, 85vh));
    }
    .design-ai-overlay {
      display: none;
      position: absolute;
      inset: 0;
      z-index: 5;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 0.5rem;
      background: rgba(28, 27, 33, 0.55);
      color: #fff;
      text-align: center;
      padding: 1rem;
      pointer-events: none;
      border-radius: 1rem;
    }
    .design-image-upload-slot.ai-busy .design-ai-overlay { display: flex; }
    .design-ai-overlay .material-symbols-outlined {
      font-size: 28px;
      animation: design-ai-pulse 1.1s ease-in-out infinite;
    }
    @keyframes design-ai-pulse {
      0%, 100% { opacity: 0.55; transform: scale(0.96); }
      50% { opacity: 1; transform: scale(1); }
    }

    /* What's included editor */
    .included-item-row { display: flex; align-items: center; gap: 0.5rem; }
    .included-item-row input { flex: 1; min-width: 0; }
    .included-item-remove {
      width: 2rem;
      height: 2rem;
      border-radius: 0.5rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #7a7583;
      flex-shrink: 0;
      transition: background 0.15s, color 0.15s;
    }
    .included-item-remove:hover { background: #fce8e8; color: #ba1a1a; }
    .included-item-remove.is-hidden { visibility: hidden; pointer-events: none; }
    .included-preset-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.375rem 0.75rem;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 600;
      border: 1px dashed rgba(122, 117, 131, 0.45);
      background: #fff;
      color: #494552;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s, color 0.2s;
    }
    .included-preset-chip:hover:not(:disabled) {
      border-color: #310f7a;
      background: #f8f1fb;
      color: #310f7a;
    }
    .included-preset-chip:disabled { opacity: 0.45; cursor: not-allowed; }

    /* Select2 (New Design modal — dropdown on body so it is not clipped by overflow) */
    #newDesignModal .select2-container { width: 100% !important; z-index: 1; }
    .select2-container--open { z-index: 10060 !important; }
    #newDesignModal .select2-container--default .select2-selection--single {
      min-height: 46px;
      padding: 4px 10px;
      border-radius: 0.75rem;
      border: 1px solid rgba(202,196,211,0.5) !important;
      background: #fff !important;
    }
    #newDesignModal .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 2.15rem;
      padding-left: 2px;
      color: #1c1b21;
      font-size: 0.875rem;
    }
    #newDesignModal .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px; }
    #newDesignModal .select2-container--default.select2-container--focus .select2-selection--single,
    #newDesignModal .select2-container--default.select2-container--open .select2-selection--single {
      border-color: rgba(26, 26, 26, 0.35) !important;
      box-shadow: 0 0 0 2px rgba(26, 26, 26, 0.12);
    }
    .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #1a1a1a !important; }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border-radius: 0.5rem;
      border-color: rgba(202,196,211,0.5);
    }
</style>
@endsection

@section('content')
  <!-- Main Content -->
  <main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

      <!-- Page Header -->
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

      @include('artist.partials.booking-page-tabs', ['activeTab' => 'designs'])


      <!-- Section intro -->
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <p class="text-on-surface-variant">Upload and manage designs clients can book directly.</p>
          <button type="button" id="btnOpenNewDesign" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2 flex-shrink-0">
            <span class="material-symbols-outlined text-lg">add</span> New Design
          </button>
        </div>
      </div>

      <!-- Pricing Settings -->
      @php
        $currentPricingType = in_array(($pricingType ?? 'manual'), ['manual', 'smart'], true)
          ? ($pricingType ?? 'manual')
          : 'manual';
        $pricingUnit = in_array(($sizeUnit ?? 'cm'), ['cm', 'in'], true) ? ($sizeUnit ?? 'cm') : 'cm';
        $pricingCurrencyCode = strtoupper((string) ($currencyCode ?? 'EUR'));
        $pricingCurrencySymbol = match ($pricingCurrencyCode) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $pricingCurrencyCode.' ',
        };
        $smartRangesCount = is_array($smartPricingRanges ?? null) ? count($smartPricingRanges) : 0;
        $smartColorPercentLabel = rtrim(rtrim(number_format((float) ($smartPricingColorPercent ?? 20), 2, '.', ''), '0'), '.');
        $pricingClosedSummary = $currentPricingType === 'smart'
          ? ($smartRangesCount.' size range'.($smartRangesCount === 1 ? '' : 's').' · +'.$smartColorPercentLabel.'% for color designs')
          : 'Set the price for each design manually';
        $pricingClosedBadge = $currentPricingType === 'smart' ? 'Smart Pricing' : 'Manual Pricing';
      @endphp
      <div
        class="mb-8 pricing-accordion"
        id="pricingSettingsPanel"
        data-pricing-type="{{ $currentPricingType }}"
        data-open="false"
      >
        <button
          type="button"
          id="pricingAccordionTrigger"
          class="pricing-accordion-trigger"
          aria-expanded="false"
          aria-controls="pricingAccordionBody"
        >
          <span class="pricing-accordion-icon" aria-hidden="true">
            <span class="material-symbols-outlined text-[22px]">bolt</span>
          </span>
          <span class="pricing-accordion-copy">
            <span class="pricing-accordion-title-row">
              <span class="pricing-accordion-title">Pricing</span>
              <span id="pricingAccordionBadge" class="pricing-accordion-badge">{{ $pricingClosedBadge }}</span>
            </span>
            <span id="pricingAccordionSubtitle" class="pricing-accordion-subtitle">{{ $pricingClosedSummary }}</span>
          </span>
          <span class="material-symbols-outlined pricing-accordion-chevron text-[22px]" aria-hidden="true">expand_more</span>
        </button>

        <div id="pricingAccordionBody" class="pricing-accordion-body" hidden>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-4" role="radiogroup" aria-label="Pricing mode">
            <button
              type="button"
              class="pricing-choice-card {{ $currentPricingType === 'smart' ? 'is-selected' : '' }}"
              data-pricing-mode="smart"
              role="radio"
              aria-checked="{{ $currentPricingType === 'smart' ? 'true' : 'false' }}"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-base font-bold text-on-surface">Smart Pricing</p>
                  <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Set rates by size once, pricing fills in automatically.</p>
                </div>
                <span class="pricing-choice-radio" aria-hidden="true"></span>
              </div>
            </button>

            <button
              type="button"
              class="pricing-choice-card {{ $currentPricingType === 'manual' ? 'is-selected' : '' }}"
              data-pricing-mode="manual"
              role="radio"
              aria-checked="{{ $currentPricingType === 'manual' ? 'true' : 'false' }}"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-base font-bold text-on-surface">Manual Pricing</p>
                  <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Set price, sessions, and duration yourself per design.</p>
                </div>
                <span class="pricing-choice-radio" aria-hidden="true"></span>
              </div>
            </button>
          </div>

          <p class="mt-3 text-xs text-on-surface-variant flex items-start gap-1.5">
            <span class="material-symbols-outlined text-[16px] mt-px text-on-surface-variant/80">info</span>
            <span>Switch anytime. Designs you've already uploaded keep their existing price, sessions, and duration.</span>
          </p>

          <div id="smartPricingPanel" class="mt-5 bg-[#fbf8ff] rounded-2xl border border-outline-variant/20 p-5 md:p-6 {{ $currentPricingType === 'smart' ? '' : 'hidden' }}">
            <div class="mb-5">
              <h4 class="text-base font-bold text-on-surface">Size ranges</h4>
              <p class="text-sm text-on-surface-variant mt-1">Longest dimension, in {{ $pricingUnit }} · edit the boundaries or the values</p>
            </div>

            <div class="smart-pricing-table-wrap">
              <table class="smart-pricing-table">
                <thead>
                  <tr>
                    <th style="width:26%;">Size</th>
                    <th style="width:17%;">Min price</th>
                    <th style="width:17%;">Max price</th>
                    <th style="width:14%;">Sessions</th>
                    <th style="width:16%;">Duration</th>
                    <th style="width:10%;" class="!text-right"><span class="sr-only">Remove</span></th>
                  </tr>
                </thead>
                <tbody id="smartPricingRows"></tbody>
              </table>
              <p id="smartPricingEmpty" class="smart-pricing-empty">No size ranges yet. Add a size range to get started.</p>
              <p id="smartPricingRangesError" class="hidden mt-2 text-xs font-semibold text-[#b3261e]"></p>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:flex-wrap gap-2 sm:gap-3">
              <button type="button" id="btnAddSmartSizeRange" class="inline-flex items-center gap-1 text-sm font-semibold text-[#310f7a] hover:opacity-80 transition-opacity">
                <span class="material-symbols-outlined text-[18px]">add</span> Size range
              </button>
              <button type="button" id="btnAddSmartMoreThanRange" class="inline-flex items-center gap-1 text-sm font-semibold text-[#310f7a] hover:opacity-80 transition-opacity">
                <span class="material-symbols-outlined text-[18px]">add</span> Size larger than
              </button>
            </div>
            <p class="mt-3 text-xs text-on-surface-variant leading-relaxed">
              Example: a 5 {{ $pricingUnit }} design belongs to &ldquo;5–10,&rdquo; not &ldquo;0–5&rdquo;; a 10 {{ $pricingUnit }} design belongs to &ldquo;10–15,&rdquo; not &ldquo;5–10&rdquo;; a 15 {{ $pricingUnit }} design belongs to &ldquo;15–20,&rdquo; not &ldquo;10–15&rdquo;.
            </p>

            <div class="mt-6 pt-5 border-t border-outline-variant/20">
              <h4 class="text-base font-bold text-on-surface">Color adjustment</h4>
              <p class="text-sm text-on-surface-variant mt-1">Added on top of the table price for color designs</p>
              <div id="smartColorPercentField" class="mt-4">
                <div class="flex flex-wrap items-center gap-2 text-sm text-on-surface">
                  <span>Color designs</span>
                  <div class="smart-pricing-color-input-wrap">
                    <input type="text" inputmode="decimal" id="smartColorPercent" class="smart-pricing-input smart-pricing-input--sm" value="{{ $smartColorPercentLabel }}" aria-label="Color percent more" placeholder="20">
                  </div>
                  <span class="smart-pricing-affix">%</span>
                  <span class="text-on-surface-variant">more</span>
                </div>
              </div>
              <p class="mt-3 text-xs text-on-surface-variant leading-relaxed">
                Example: If the price for a 20+ {{ $pricingUnit }} black &amp; grey design is {{ $pricingCurrencySymbol }}450–{{ $pricingCurrencySymbol }}750, a color design at the same size would be {{ $pricingCurrencySymbol }}540–{{ $pricingCurrencySymbol }}900 (20% more).
              </p>
            </div>

            <div class="mt-6 pt-5 border-t border-outline-variant/20 flex flex-col sm:flex-row sm:items-center gap-3">
              <button type="button" id="btnSaveSmartPricing" class="inline-flex items-center justify-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm">
                <span class="material-symbols-outlined text-lg">save</span> Save
              </button>
              <p id="smartPricingSaveStatus" class="hidden text-sm font-medium"></p>
            </div>
          </div>
        </div>
      </div>

      <!-- What's Included -->
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 md:p-6 mb-8" id="whatsIncludedPanel">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-5">
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-on-surface">What's included in the session</h3>
            <p class="text-sm text-on-surface-variant mt-1 max-w-2xl">Let clients know what's part of your service — sizing, placement, touch-ups, aftercare.</p>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <span class="text-sm font-semibold text-on-surface-variant">Show on page</span>
            <div id="toggleWhatsIncluded" class="toggle-switch @if($whatsIncludedIsActive) active @endif" role="switch" aria-checked="{{ $whatsIncludedIsActive ? 'true' : 'false' }}" title="Show or hide What's Included on your public design pages"></div>
            <span id="toggleWhatsIncludedLabel" class="text-xs font-semibold {{ $whatsIncludedIsActive ? 'text-primary' : 'text-on-surface-variant' }} min-w-[1.75rem]">{{ $whatsIncludedIsActive ? 'Yes' : 'No' }}</span>
          </div>
        </div>

        <div id="whatsIncludedEditor" class="space-y-5 @if(!$whatsIncludedIsActive) hidden @endif">
          <div id="whatsIncludedList" class="space-y-2.5" aria-label="What's included items"></div>

          <button type="button" id="btnAddIncludedItem" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-container transition-colors">
            <span class="material-symbols-outlined text-[18px]">add</span> Add item
          </button>
          <p id="whatsIncludedLimitHint" class="hidden text-xs text-on-surface-variant">You can add up to 8 items. Remove one to add another preset.</p>

          <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2.5">Suggestions</p>
            <div id="whatsIncludedPresets" class="flex flex-wrap gap-2"></div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center gap-3 pt-2 border-t border-outline-variant/15">
            <button type="button" id="btnSaveWhatsIncluded" class="inline-flex items-center justify-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm">
              <span class="material-symbols-outlined text-lg">save</span> Save
            </button>
            <p id="whatsIncludedSaveStatus" class="hidden text-sm font-medium text-green-700"></p>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
        <div class="flex items-center gap-2 flex-wrap" id="designFilterPills" role="tablist" aria-label="Filter designs">
          <button type="button" class="filter-pill active" data-filter="all">All</button>
          <button type="button" class="filter-pill" data-filter="available">Available</button>
          <button type="button" class="filter-pill" data-filter="sold-out">Sold Out</button>
        </div>
        <div class="flex items-center gap-3 sm:ml-auto">
          <select id="sortDesigns" name="sortDesigns" class="text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="custom" selected>Your order</option>
            <option value="newest">Newest</option>
            <option value="price-high">Price High–Low</option>
          </select>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
            <input type="text" id="searchDesigns" name="searchDesigns" placeholder="Search designs…" class="text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 w-48">
          </div>
        </div>
      </div>

      <!-- Designs Grid -->
      @if ($artistDesigns->isNotEmpty())
        <p class="text-xs text-on-surface-variant mb-3 flex items-center gap-1.5" id="designsDragHint">
          <span class="material-symbols-outlined text-base text-outline">drag_indicator</span>
          Drag designs to change the order they appear on your booking page (when “Your order” is selected).
        </p>
      @endif
      <div id="designsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($artistDesigns as $design)
        @php
          $sessionLabel = match ($design->session_duration) {
              '30min' => '30 min',
              '1h' => '1 hour',
              '2h' => '2 hours',
              '3h' => '3 hours',
              '4h' => '4 hours',
              '6h' => '6 hours',
              '8h' => '8 hours',
              default => $design->session_duration,
          };
          $sessionsLabel = (int) $design->max_sessions > 1
              ? 'Up to '.$design->max_sessions.' sessions'
              : '1 session';
          $colorLabel = match ($design->color) {
              'color' => 'color',
              'black-grey' => 'black grey',
              'both' => 'both',
              default => (string) $design->color,
          };
          $tagsForSearch = is_array($design->tags) ? implode(' ', $design->tags) : '';
          $searchBlob = strtolower(
              $design->title.' '.
              $design->description.' '.
              $tagsForSearch.' '.
              str_replace('-', ' ', $design->primary_style).' '.
              $colorLabel
          );
          $canDeleteDesign = ($design->booking_requests_count ?? 0) === 0 && ($design->bookings_count ?? 0) === 0;
          $isSoldOut = $design->isSoldOut();
        @endphp
        <div
          class="design-card-wrap"
          data-design-id="{{ $design->id }}"
          data-is-active="{{ $design->is_active ? '1' : '0' }}"
          data-is-sold-out="{{ $isSoldOut ? '1' : '0' }}"
          data-created="{{ $design->created_at->getTimestamp() }}"
          data-sort-order="{{ (int) ($design->sort_order ?? 0) }}"
          data-max-price="{{ (int) $design->max_price }}"
          data-search="{{ e($searchBlob) }}"
        >
          <div class="design-card bg-white rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
          <div class="design-image-wrap aspect-[4/5] bg-surface-container-high rounded-t-2xl">
            <button type="button" class="design-drag-handle" title="Drag to reorder" aria-label="Drag to reorder">
              <span class="material-symbols-outlined">drag_indicator</span>
            </button>
            <img src="{{ asset($design->image) }}" alt="" class="w-full h-full object-cover pointer-events-none">
          </div>
          <div class="p-4">
            <div class="flex flex-wrap gap-1.5 mb-3">
              @if ($isSoldOut)
              <span class="toggle-badge off">Sold Out</span>
              @endif
              <span class="design-availability-badge toggle-badge {{ $design->is_active ? 'on' : 'off' }}">
                <span class="design-availability-label">{{ $design->is_active ? 'Available' : 'Unavailable' }}</span>
              </span>
              <span class="toggle-badge {{ $design->is_visible ? 'on' : 'off' }} hidden">Visibility</span>
              <span class="toggle-badge {{ $design->is_repeatable ? 'on' : 'off' }}">
                @if ($design->is_repeatable && $design->repeat_limit)
                  Repeatable ×{{ $design->repeat_limit }}
                @else
                  Repeatable
                @endif
              </span>
              <span class="toggle-badge {{ $design->is_sensitive ? 'on' : 'off' }}">Sensitive</span>
            </div>
            <h4 class="font-bold text-on-surface text-sm mb-1.5">{{ $design->title }}</h4>
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-primary/10 text-primary">{{ ucwords(str_replace('-', ' ', $design->primary_style)) }}</span>
              <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-surface-container-high text-on-surface-variant">
                @if ($design->color === 'color') Color @elseif ($design->color === 'black-grey') Black & Grey @elseif ($design->color === 'both') Both @else {{ $design->color }} @endif
              </span>
            </div>
            @if (!empty($design->tags))
            <div class="flex flex-wrap gap-1 mb-2">
              @foreach ($design->tags as $tag)
              <span class="inline-block px-2 py-0.5 rounded-md text-[11px] font-semibold bg-surface-container-high text-on-surface-variant">{{ $tag }}</span>
              @endforeach
            </div>
            @endif
            <div class="text-xs text-on-surface-variant space-y-0.5 mb-3">
              <p><span class="font-semibold text-on-surface">Price:</span> €{{ $design->min_price }} — €{{ $design->max_price }}</p>
              <p><span class="font-semibold text-on-surface">Size (min):</span> {{ $design->sizeLabel($sizeUnit ?? null) }}</p>
              <p><span class="font-semibold text-on-surface">Sessions:</span> {{ $sessionsLabel }}, {{ $sessionLabel }} each</p>
            </div>
            <div class="flex items-center gap-1 pt-2 mt-1 border-t border-outline-variant/10">
              <button type="button" class="btn-edit-design w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low transition-colors" title="Edit" data-design-id="{{ $design->id }}"><span class="material-symbols-outlined text-on-surface-variant text-lg">edit</span></button>
              @if ($canDeleteDesign)
              <button type="button" class="btn-delete-design w-8 h-8 rounded-lg flex items-center justify-center hover:bg-error-container transition-colors" title="Delete" data-delete-url="{{ route('artist-designs.destroy', $design) }}" data-design-id="{{ $design->id }}"><span class="material-symbols-outlined text-error text-lg">delete</span></button>
              @else
              <div class="ml-auto flex items-center gap-2 pl-2" title="Has bookings or requests — set unavailable instead of delete">
                <span class="text-[11px] font-semibold text-on-surface-variant">Available</span>
                <div
                  class="btn-toggle-design-availability toggle-switch {{ $design->is_active ? 'active' : '' }}"
                  role="switch"
                  aria-checked="{{ $design->is_active ? 'true' : 'false' }}"
                  title="Toggle availability"
                  data-toggle-url="{{ route('artist-designs.toggle-availability', $design) }}"
                  data-design-id="{{ $design->id }}"
                ></div>
              </div>
              @endif
            </div>
          </div>
        </div>
        </div>
        @empty
        <div id="designsNoDesigns" class="col-span-full rounded-2xl border border-dashed border-outline-variant/40 bg-white/60 px-6 py-14 text-center">
          <span class="material-symbols-outlined text-outline/40 text-4xl mb-2 inline-block">palette</span>
          <p class="text-sm font-semibold text-on-surface">No designs yet</p>
          <p class="text-xs text-on-surface-variant mt-1 max-w-sm mx-auto">Create a design with <strong class="text-on-surface">New Design</strong> — it will show up here after you save.</p>
        </div>
        @endforelse
        <div id="designsFilterEmpty" class="hidden col-span-full rounded-2xl border border-dashed border-outline-variant/40 bg-white/60 px-6 py-14 text-center">
          <span class="material-symbols-outlined text-outline/40 text-4xl mb-2 inline-block">search_off</span>
          <p class="text-sm font-semibold text-on-surface">No designs match</p>
          <p class="text-xs text-on-surface-variant mt-1 max-w-sm mx-auto">Try a different search, filter, or sort option.</p>
        </div>
      </div>
    </div>
  </main>

  <!-- New Design Modal -->
  <div class="modal-backdrop" id="newDesignModal" aria-hidden="true">
    <div class="new-design-modal-inner bg-white rounded-2xl w-full max-w-4xl mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15 sticky top-0 bg-white z-10 rounded-t-2xl">
        <h3 id="designModalTitle" class="text-lg font-bold text-on-surface">New Design</h3>
        <button type="button" id="btnCloseNewDesign" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low transition-colors">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="p-6">
        <div id="designFormBanner" class="hidden mb-5 rounded-xl border border-error/30 bg-error-container/40 px-3 py-2 text-xs text-on-error-container font-medium whitespace-pre-line"></div>
        <p class="text-on-surface-variant mb-6 leading-relaxed">Upload the image first. We'll use AI to analyze your uploaded image and automatically fill in some fields. You can always edit these. You'll still need to enter values for the remaining fields manually (pricing, minimum size, sessions etc).</p>
        <div class="flex flex-col lg:flex-row gap-6">
          <!-- Left: Image Upload -->
          <div class="lg:w-2/5 space-y-5">
            <div class="design-field-section scroll-mt-6" data-design-field="image">
              <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Image</label>
              <p class="text-[11px] text-on-surface-variant mb-2">Cropped to <strong class="text-on-surface">1080 × 1350 px</strong> · aspect <strong class="text-on-surface">4:5</strong></p>
              <div id="designImageUpload" class="design-image-upload-slot relative border-2 border-dashed border-outline-variant/40 rounded-2xl mx-auto cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-[aspect-ratio,max-height] duration-200 overflow-hidden">
                <div id="designImageUploadEmpty" class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-4 py-6">
                  <span class="material-symbols-outlined text-outline/40 text-5xl">cloud_upload</span>
                  <div class="text-center">
                    <p class="text-sm font-semibold text-on-surface">Drop image here</p>
                    <p class="text-xs text-on-surface-variant mt-1">or click to browse</p>
                    <p class="text-xs text-outline mt-2">PNG, JPG up to 10MB</p>
                  </div>
                </div>
                <div id="designImageUploadPreview" class="hidden absolute inset-0 bg-transparent">
                  <img id="designImagePreviewImg" src="" alt="Design preview" class="w-full h-full object-contain">
                  <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent pt-8 pb-2 px-3">
                    <p class="text-[11px] text-white/90 text-center font-medium">Tap to replace image</p>
                  </div>
                </div>
                <div class="design-ai-overlay" aria-live="polite">
                  <span class="material-symbols-outlined">auto_awesome</span>
                  <p class="text-xs font-semibold leading-snug">Filling empty fields with AI…</p>
                </div>
              </div>
              <input type="file" id="designImage" name="designImage" accept="image/*" class="hidden">
              <input type="hidden" id="designImageData" name="designImageData" value="">
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="image"></p>
            </div>
            <!-- Size (min) -->
            <div class="design-field-section scroll-mt-6" data-design-field="min_size">
              <label for="size_min" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Size (min)</label>
              <div class="flex items-center gap-3">
                <input type="number" id="size_min" name="size_min" placeholder="e.g. 10" min="1" class="w-full min-w-0 flex-1 text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 text-center">
                <span class="text-sm font-semibold text-on-surface shrink-0" id="sizeUnitLabel">{{ $sizeUnit ?? 'cm' }}</span>
              </div>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="min_size"></p>
            </div>
          </div>
          <!-- Right: Form Fields -->
          <div class="lg:w-3/5 space-y-5">
            <!-- Title -->
            <div class="design-field-section scroll-mt-6" data-design-field="title">
              <label for="designTitle" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Title</label>
              <input type="text" id="designTitle" name="designTitle" placeholder="e.g., Japanese Dragon Sleeve" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="title"></p>
            </div>
            <!-- Description -->
            <div class="design-field-section scroll-mt-6" data-design-field="description">
              <label for="designDescription" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Description <span class="text-outline font-normal">(optional)</span></label>
              <textarea id="designDescription" name="designDescription" rows="3" placeholder="Describe this design…" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="description"></p>
            </div>
            <!-- Toggle Switches Row -->
            <div class="design-field-section scroll-mt-6" data-design-field="settings">
              <label class="block text-xs font-semibold text-on-surface-variant mb-2">Settings</label>
              <div class="flex flex-wrap gap-4">
                <div class="hidden">
                  <div id="toggleVisibility" class="toggle-switch active"></div>
                  <span class="text-sm text-on-surface">Visibility</span>
                </div>
                <div class="flex items-center gap-2">
                  <div id="toggleAvailable" class="toggle-switch active"></div>
                  <span class="text-sm text-on-surface">Available</span>
                </div>
                <div class="flex items-center gap-2">
                  <div id="toggleRepeatable" class="toggle-switch"></div>
                  <span class="text-sm text-on-surface">Repeatable</span>
                </div>
                <div class="flex items-center gap-2">
                  <div id="toggleSensitive" class="toggle-switch"></div>
                  <span class="text-sm text-on-surface">Sensitive</span>
                </div>
              </div>
              <div id="repeatLimitField" class="design-field-section hidden mt-4 scroll-mt-6" data-design-field="repeat_limit">
                <label for="designRepeatLimit" class="block text-xs font-semibold text-on-surface-variant mb-1.5">How many times can this be booked? <span class="text-red-600">*</span></label>
                <input type="number" id="designRepeatLimit" name="designRepeatLimit" min="1" max="999" step="1" placeholder="e.g. 3" class="w-full max-w-[160px] text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p class="text-xs text-on-surface-variant mt-1.5">Total number of clients who can book this design before it shows as sold out.</p>
                <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="repeat_limit"></p>
              </div>
            </div>
            <!-- Primary Style -->
            <div class="design-field-section scroll-mt-6" data-design-field="primary_style">
              <label for="designPrimaryStyle" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Primary Style</label>
              <select id="designPrimaryStyle" name="designPrimaryStyle" class="js-design-modal-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Select style…</option>
                @foreach ($styles as $style)
                <option value="{{ $style }}">{{ $style }}</option>
                @endforeach
              </select>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="primary_style"></p>
            </div>
            <!-- Other styles (max 2) -->
            <div class="design-field-section scroll-mt-6" data-design-field="other_styles">
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="text-xs font-semibold text-on-surface-variant">Other styles</span>
                <span class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-surface-container-high px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Max 2</span>
              </div>
              <p class="text-[11px] text-on-surface-variant leading-relaxed mb-3">Add up to two secondary styles. They should differ from your primary style when possible.</p>
              <div class="flex items-center justify-between mb-2.5 rounded-xl bg-surface-container-low/80 px-3 py-2 border border-outline-variant/15">
                <span class="text-xs text-on-surface-variant">Selected</span>
                <span class="text-sm font-bold tabular-nums text-on-surface"><span id="designOtherStylesCount">0</span><span class="text-on-surface-variant font-semibold"> / 2</span></span>
              </div>
              <div id="designOtherStylesChips" class="flex flex-wrap gap-2" role="group" aria-label="Other tattoo styles">
                @foreach ($styles as $style)
                <button type="button" class="style-chip" data-value="{{ $style }}" aria-pressed="false"><span class="material-symbols-outlined style-chip-check">check</span>{{ $style }}</button>
                @endforeach
              </div>
              <select id="designOtherStyles" name="designOtherStyles" multiple class="hidden" tabindex="-1" aria-hidden="true">
                @foreach ($styles as $style)
                <option value="{{ $style }}">{{ $style }}</option>
                @endforeach
              </select>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="other_styles"></p>
            </div>
            <!-- Suggested placement (max 3) -->
            <div class="design-field-section scroll-mt-6" data-design-field="suggested_placements">
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="text-xs font-semibold text-on-surface-variant">Suggested placement</span>
                <span class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-surface-container-high px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Max 3</span>
              </div>
              <p class="text-[11px] text-on-surface-variant leading-relaxed mb-3">Choose up to three body areas. If you leave this empty, clients will see “Anywhere”.</p>
              <div class="flex items-center justify-between mb-2.5 rounded-xl bg-surface-container-low/80 px-3 py-2 border border-outline-variant/15">
                <span class="text-xs text-on-surface-variant">Selected</span>
                <span class="text-sm font-bold tabular-nums text-on-surface"><span id="designPlacementsCount">0</span><span class="text-on-surface-variant font-semibold"> / 3</span></span>
              </div>
              <div id="designPlacementsChips" class="flex flex-wrap gap-2" role="group" aria-label="Suggested placements">
                @foreach (($placements ?? []) as $placement)
                <button type="button" class="style-chip" data-value="{{ $placement }}" aria-pressed="false"><span class="material-symbols-outlined style-chip-check">check</span>{{ $placement }}</button>
                @endforeach
              </div>
              <select id="designSuggestedPlacements" name="designSuggestedPlacements" multiple class="hidden" tabindex="-1" aria-hidden="true">
                @foreach (($placements ?? []) as $placement)
                <option value="{{ $placement }}">{{ $placement }}</option>
                @endforeach
              </select>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="suggested_placements"></p>
            </div>
            <!-- Colors -->
            <div class="design-field-section scroll-mt-6" data-design-field="color">
              <label for="designColors" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Colors</label>
              <select id="designColors" name="designColors" class="js-design-modal-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Select…</option>
                <option value="color">Color</option>
                <option value="black-grey">Black & Grey</option>
                <option value="both">Both</option>
              </select>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="color"></p>
            </div>
            <!-- Tags -->
            <div class="design-field-section scroll-mt-6" data-design-field="tags">
              <label for="designTags" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Tags <span class="text-outline font-normal">(comma separated)</span></label>
              <input type="text" id="designTags" name="designTags" placeholder="e.g., dragon, sleeve, oriental" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="tags"></p>
            </div>
            <!-- Price Range -->
            <div class="design-field-section scroll-mt-6" data-design-field="min_price">
              <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Price Range</label>
              <div class="flex items-center gap-3">
                <div class="relative flex-1">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-sm">€</span>
                  <input type="number" id="designPriceMin" name="designPriceMin" placeholder="Min" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-7 pr-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
                <span class="text-on-surface-variant font-medium">—</span>
                <div class="relative flex-1">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-sm">€</span>
                  <input type="number" id="designPriceMax" name="designPriceMax" placeholder="Max" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-7 pr-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                </div>
              </div>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="min_price"></p>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="max_price"></p>
            </div>
            <!-- Max sessions -->
            <div class="design-field-section scroll-mt-6" data-design-field="max_sessions">
              <label for="designSessionsMax" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Max sessions</label>
              <input type="number" id="designSessionsMax" name="designSessionsMax" placeholder="" min="1" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p class="text-xs text-on-surface-variant mt-1.5 italic">Leave blank if this design will be completed in one session</p>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="max_sessions"></p>
            </div>
            <!-- Duration -->
            <div class="design-field-section scroll-mt-6" data-design-field="session_duration">
              <label for="designSessionTime" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Duration</label>
              <select id="designSessionTime" name="designSessionTime" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <option value="">Select…</option>
                <option value="30min">30 min</option>
                <option value="1h">1 hour</option>
                <option value="2h">2 hours</option>
                <option value="3h">3 hours</option>
                <option value="4h">4 hours</option>
                <option value="6h">6 hours</option>
                <option value="8h">8 hours</option>
              </select>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="session_duration"></p>
            </div>
          </div>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 sticky bottom-0 bg-white rounded-b-2xl">
        <button type="button" id="btnCancelNewDesign" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnSaveDesign" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg">save</span> Save Design
        </button>
      </div>
    </div>
  </div>

  <!-- Crop design image (4:5 → 1080×1350) -->
  <div id="designCropModal" class="crop-modal-backdrop" aria-hidden="true">
    <div class="crop-modal-inner bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">
      <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15 flex-shrink-0">
        <div>
          <h3 class="text-lg font-bold text-on-surface">Crop image</h3>
          <p class="text-xs text-on-surface-variant mt-0.5">Output <span class="font-semibold text-on-surface">1080 × 1350 px</span> · ratio 4:5</p>
        </div>
        <button type="button" id="btnDesignCropClose" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors" aria-label="Close cropper">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="cropper-stage-wrap mx-4 my-3 flex-shrink-0">
        <img id="designCropperImg" src="" alt="" class="max-w-full">
      </div>
      <div class="px-5 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 flex-shrink-0 bg-surface-container-low/30">
        <button type="button" id="btnDesignCropCancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2.5 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnDesignCropApply" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg">check</span> Apply crop
        </button>
      </div>
    </div>
  </div>

  <!-- Delete design confirmation -->
  <div class="modal-backdrop" id="deleteDesignModal" aria-hidden="true">
    <div class="design-delete-modal-inner bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden">
      <div class="p-6">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-error-container flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-error text-2xl">delete_forever</span>
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-on-surface tracking-tight">Delete this design?</h3>
            <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">This will permanently remove the design from your available designs. You cannot undo this.</p>
            <p id="deleteDesignError" class="hidden mt-3 text-xs text-error font-semibold leading-snug"></p>
          </div>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 bg-surface-container-low/30">
        <button type="button" id="btnDeleteDesignCancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2.5 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnDeleteDesignConfirm" class="bg-error text-on-error px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-95 transition-opacity shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg confirm-delete-design-icon">delete</span> <span class="confirm-delete-design-label">Delete</span>
        </button>
      </div>
    </div>
  </div>

@endsection

@section('scripts')
@php
    $designsForEdit = $artistDesigns->keyBy('id')->map(function ($d) {
        return [
            'id' => $d->id,
            'title' => $d->title,
            'description' => $d->description,
            'image_url' => asset($d->image),
            'is_active' => (bool) $d->is_active,
            'is_visible' => (bool) $d->is_visible,
            'is_repeatable' => (bool) $d->is_repeatable,
            'repeat_limit' => $d->repeat_limit !== null ? (int) $d->repeat_limit : null,
            'claimed_count' => $d->claimedBookingCount(),
            'is_sensitive' => (bool) $d->is_sensitive,
            'primary_style' => $d->primary_style,
            'other_styles' => array_values($d->other_styles ?? []),
            'suggested_placements' => array_values($d->suggested_placements ?? []),
            'color' => $d->color,
            'tags' => array_values($d->tags ?? []),
            'min_price' => (int) $d->min_price,
            'max_price' => (int) $d->max_price,
            'min_size' => $d->min_size !== null ? (int) $d->min_size : null,
            'min_sessions' => (int) $d->min_sessions,
            'max_sessions' => (int) $d->max_sessions,
            'session_duration' => $d->session_duration,
            'can_delete' => ($d->booking_requests_count ?? 0) === 0 && ($d->bookings_count ?? 0) === 0,
            'toggle_availability_url' => route('artist-designs.toggle-availability', $d),
        ];
    });
@endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    var ARTIST_DESIGNS_STORE_URL = @json(route('artist-designs.store'));
    var ARTIST_DESIGNS_INDEX_URL = @json(route('artist-designs.index'));
    var ARTIST_DESIGNS_REORDER_URL = @json(route('artist-designs.reorder'));
    var ARTIST_DESIGNS_AI_SUGGEST_URL = @json(route('artist-designs.ai-suggest'));
    var WHATS_INCLUDED_UPDATE_URL = @json(route('artist-designs.whats-included.update'));
    var PRICING_TYPE_UPDATE_URL = @json(route('artist-designs.pricing-type.update'));
    var SMART_PRICING_VALIDATE_URL = @json(route('artist-designs.smart-pricing.update'));
    var SMART_PRICING_INITIAL_RANGES = @json($smartPricingRanges ?? []);
    var SMART_PRICING_COLOR_PERCENT = @json((float) ($smartPricingColorPercent ?? 20));
    var WHATS_INCLUDED_INITIAL = @json(['is_active' => $whatsIncludedIsActive, 'items' => $whatsIncludedItems]);
    var ARTIST_DESIGNS_BY_ID = @json($designsForEdit);
    var ARTIST_DESIGN_STYLE_OPTIONS = @json($styles);
    var ARTIST_DESIGN_PLACEMENT_OPTIONS = @json($placements ?? []);
    var ARTIST_DESIGN_SIZE_UNIT = @json($sizeUnit ?? 'cm');
    $(function () {
      var MODAL_MS = 350;
      var $newDesignModal = $('#newDesignModal');
      var $designCropModal = $('#designCropModal');
      var $deleteDesignModal = $('#deleteDesignModal');
      var $designCropperImg = $('#designCropperImg');
      var designCropper = null;
      var CROP_OUT_W = 1080;
      var CROP_OUT_H = 1350;
      var CROP_RATIO = 4 / 5;
      var MAX_FILE_BYTES = 10 * 1024 * 1024;

      // Pricing type cards + dynamic size-range rows
      var SMART_PRICING_UNIT = @json($pricingUnit ?? 'cm');
      var SMART_PRICING_CURRENCY = @json($pricingCurrencySymbol ?? '€');
      var pricingTypeSaving = false;
      var storedPricingType = String($('#pricingSettingsPanel').data('pricing-type') || 'manual');
      var currentPricingType = storedPricingType;

      function formatSmartSummaryPercent(value) {
        var num = Number(value);
        if (!Number.isFinite(num)) {
          return '0';
        }
        return String(num.toFixed(2)).replace(/\.?0+$/, '');
      }

      function syncPricingAccordionHeader() {
        var $panel = $('#pricingSettingsPanel');
        var isOpen = $panel.hasClass('is-open');
        var isSmartStored = String(storedPricingType || '') === 'smart';
        var $subtitle = $('#pricingAccordionSubtitle');
        var $badge = $('#pricingAccordionBadge');

        $panel.attr('data-pricing-type', isSmartStored ? 'smart' : 'manual');
        $badge.text(isSmartStored ? 'Smart Pricing' : 'Manual Pricing');

        if (isOpen) {
          $subtitle.text('Choose how price, sessions, and duration are set for new designs.');
          return;
        }

        if (!isSmartStored) {
          $subtitle.text('Set the price for each design manually');
          return;
        }

        var rangeCount = $('#smartPricingRows .smart-pricing-row').length;
        if (!rangeCount && Array.isArray(SMART_PRICING_INITIAL_RANGES)) {
          rangeCount = SMART_PRICING_INITIAL_RANGES.length;
        }
        var percent = formatSmartSummaryPercent(
          $.trim($('#smartColorPercent').val() || '') !== ''
            ? $('#smartColorPercent').val()
            : SMART_PRICING_COLOR_PERCENT
        );
        var rangeLabel = rangeCount + ' size range' + (rangeCount === 1 ? '' : 's');
        $subtitle.text(rangeLabel + ' · +' + percent + '% for color designs');
      }

      function setPricingAccordionOpen(open) {
        var $panel = $('#pricingSettingsPanel');
        var isOpen = !!open;
        $panel.toggleClass('is-open', isOpen).attr('data-open', isOpen ? 'true' : 'false');
        $('#pricingAccordionTrigger').attr('aria-expanded', isOpen ? 'true' : 'false');
        $('#pricingAccordionBody').prop('hidden', !isOpen);
        syncPricingAccordionHeader();
      }

      function setPricingMode(mode, options) {
        options = options || {};
        var isSmart = mode === 'smart';
        $('#pricingSettingsPanel .pricing-choice-card').each(function () {
          var selected = $(this).data('pricing-mode') === mode;
          $(this).toggleClass('is-selected', selected).attr('aria-checked', selected ? 'true' : 'false');
        });
        $('#smartPricingPanel').toggleClass('hidden', !isSmart);
        currentPricingType = mode;
        syncPricingAccordionHeader();

        // Smart is only persisted after a successful size-chart Save with ≥1 range.
        if (options.persist === false || isSmart) {
          return;
        }

        if (pricingTypeSaving) {
          return;
        }

        pricingTypeSaving = true;
        $('#pricingSettingsPanel .pricing-choice-card').prop('disabled', true);

        $.ajax({
          url: PRICING_TYPE_UPDATE_URL,
          method: 'PUT',
          contentType: 'application/json',
          data: JSON.stringify({ pricing_type: mode }),
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).done(function (res) {
          if (res && res.pricing_type) {
            storedPricingType = res.pricing_type;
            currentPricingType = res.pricing_type;
            $('#pricingSettingsPanel').data('pricing-type', res.pricing_type);
          }
          syncPricingAccordionHeader();
        }).fail(function () {
          var previous = String($('#pricingSettingsPanel').attr('data-previous-pricing-type') || storedPricingType || 'manual');
          setPricingMode(previous, { persist: false });
        }).always(function () {
          pricingTypeSaving = false;
          $('#pricingSettingsPanel .pricing-choice-card').prop('disabled', false);
        });
      }

      $('#pricingAccordionTrigger').on('click', function () {
        setPricingAccordionOpen(!$('#pricingSettingsPanel').hasClass('is-open'));
      });

      function syncSmartPricingEmptyState() {
        var hasRows = $('#smartPricingRows .smart-pricing-row').length > 0;
        $('#smartPricingEmpty').toggleClass('hidden', hasRows);
        $('.smart-pricing-table thead').toggleClass('hidden', !hasRows);
        syncPricingAccordionHeader();
      }

      function formatSmartPricingValue(value) {
        if (value === null || value === undefined || value === '') {
          return '';
        }
        var num = Number(value);
        if (!Number.isFinite(num)) {
          return String(value);
        }
        var fixed = num.toFixed(2);
        return fixed.replace(/\.?0+$/, '');
      }

      function escapeSmartPricingAttr(value) {
        return String(value == null ? '' : value)
          .replace(/&/g, '&amp;')
          .replace(/"/g, '&quot;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;');
      }

      function buildSmartPricingSizeCellHtml(kind, row) {
        row = row || {};
        var sizeMin = escapeSmartPricingAttr(formatSmartPricingValue(row.size_min));
        var sizeMax = escapeSmartPricingAttr(formatSmartPricingValue(row.size_max));

        if (kind === 'less_than') {
          return `
            <div class="smart-pricing-size-cell">
              <span class="smart-pricing-open-max" aria-hidden="true">&lt;</span>
              <input type="text" inputmode="decimal" class="smart-pricing-input smart-pricing-input--sm js-smart-size-max" value="${sizeMax}" aria-label="Size max" placeholder="5">
              <span class="smart-pricing-affix">${SMART_PRICING_UNIT}</span>
            </div>`;
        }

        if (kind === 'more_than') {
          return `
            <div class="smart-pricing-size-cell">
              <input type="text" inputmode="decimal" class="smart-pricing-input smart-pricing-input--sm js-smart-size-min" value="${sizeMin}" aria-label="Size min" placeholder="20">
              <span class="smart-pricing-open-max" aria-label="Open ended min">+</span>
              <span class="smart-pricing-affix">${SMART_PRICING_UNIT}</span>
            </div>`;
        }

        return `
          <div class="smart-pricing-size-cell">
            <input type="text" inputmode="decimal" class="smart-pricing-input smart-pricing-input--sm js-smart-size-min" value="${sizeMin}" aria-label="Size min" placeholder="0">
            <span class="smart-pricing-affix">–</span>
            <input type="text" inputmode="decimal" class="smart-pricing-input smart-pricing-input--sm js-smart-size-max" value="${sizeMax}" aria-label="Size max" placeholder="5">
            <span class="smart-pricing-affix">${SMART_PRICING_UNIT}</span>
          </div>`;
      }

      function buildSmartPricingRowHtml(kind, row) {
        kind = kind || 'between';
        row = row || {};
        return `
          <tr class="smart-pricing-row" data-range-kind="${kind}">
            <td>
              <div class="smart-pricing-field-stack">
                ${buildSmartPricingSizeCellHtml(kind, row)}
              </div>
            </td>
            <td>
              <div class="smart-pricing-field-stack">
                <div class="smart-pricing-currency-cell">
                  <span class="smart-pricing-affix">${SMART_PRICING_CURRENCY}</span>
                  <input type="text" inputmode="decimal" class="smart-pricing-input smart-pricing-input--md js-smart-min-price" value="${escapeSmartPricingAttr(formatSmartPricingValue(row.min_price))}" aria-label="Min price" placeholder="0">
                </div>
              </div>
            </td>
            <td>
              <div class="smart-pricing-field-stack">
                <div class="smart-pricing-currency-cell">
                  <span class="smart-pricing-affix">${SMART_PRICING_CURRENCY}</span>
                  <input type="text" inputmode="decimal" class="smart-pricing-input smart-pricing-input--md js-smart-max-price" value="${escapeSmartPricingAttr(formatSmartPricingValue(row.max_price))}" aria-label="Max price" placeholder="0">
                </div>
              </div>
            </td>
            <td>
              <div class="smart-pricing-field-stack">
                <div class="smart-pricing-sessions-cell">
                  <input type="text" class="smart-pricing-input smart-pricing-input--md js-smart-sessions" value="${escapeSmartPricingAttr(row.sessions)}" aria-label="Sessions" placeholder="1">
                </div>
              </div>
            </td>
            <td>
              <div class="smart-pricing-field-stack">
                <div class="smart-pricing-duration-cell">
                  <input type="text" inputmode="decimal" class="smart-pricing-input smart-pricing-input--sm js-smart-duration" value="${escapeSmartPricingAttr(formatSmartPricingValue(row.duration))}" aria-label="Duration hours" placeholder="1">
                  <span class="smart-pricing-affix">hr</span>
                </div>
              </div>
            </td>
            <td class="text-right">
              <button type="button" class="smart-pricing-remove-btn js-remove-smart-row" title="Remove size range" aria-label="Remove size range">
                <span class="material-symbols-outlined text-[18px]">delete</span>
              </button>
            </td>
          </tr>`;
      }

      function addSmartPricingRow(kind, row) {
        $('#smartPricingRows').append(buildSmartPricingRowHtml(kind || (row && row.kind) || 'between', row || {}));
        $('#smartPricingRangesError').addClass('hidden').text('');
        syncSmartPricingEmptyState();
      }

      function loadSmartPricingInitialRows() {
        var ranges = Array.isArray(SMART_PRICING_INITIAL_RANGES) ? SMART_PRICING_INITIAL_RANGES : [];
        ranges.forEach(function (row) {
          addSmartPricingRow((row && row.kind) || 'between', row || {});
        });
        syncSmartPricingEmptyState();
      }

      $('#pricingSettingsPanel').on('click', '.pricing-choice-card', function () {
        if (pricingTypeSaving || $(this).prop('disabled')) {
          return;
        }
        var mode = String($(this).data('pricing-mode') || 'manual');
        if (mode === currentPricingType) {
          return;
        }
        $('#pricingSettingsPanel').attr('data-previous-pricing-type', currentPricingType);
        setPricingMode(mode);
      });

      $('#btnAddSmartSizeRange').on('click', function () {
        addSmartPricingRow('between');
      });

      $('#btnAddSmartMoreThanRange').on('click', function () {
        addSmartPricingRow('more_than');
      });

      $('#smartPricingRows').on('click', '.js-remove-smart-row', function () {
        $(this).closest('.smart-pricing-row').remove();
        syncSmartPricingEmptyState();
      });

      $('#smartPricingPanel').on('focusin', '.smart-pricing-input', function () {
        var $input = $(this);
        if ($input.data('placeholder-backup') == null) {
          $input.data('placeholder-backup', $input.attr('placeholder') || '');
        }
        $input.attr('placeholder', '');
      });

      $('#smartPricingPanel').on('focusout', '.smart-pricing-input', function () {
        var $input = $(this);
        var backup = $input.data('placeholder-backup');
        if (backup != null) {
          $input.attr('placeholder', backup);
        }
      });

      function clearSmartPricingValidation() {
        $('#smartPricingPanel .smart-pricing-input').removeClass('is-invalid');
        $('#smartPricingPanel .smart-pricing-field-error').remove();
        $('#smartPricingRangesError').addClass('hidden').text('');
        $('#smartPricingSaveStatus').addClass('hidden').removeClass('text-red-700').text('');
      }

      function showSmartPricingRangesError(message) {
        $('#smartPricingRangesError').removeClass('hidden').text(message);
      }

      function scrollToFirstSmartPricingError() {
        if (!$('#pricingSettingsPanel').hasClass('is-open')) {
          setPricingAccordionOpen(true);
        }
        if ($('#smartPricingPanel').hasClass('hidden')) {
          setPricingMode('smart', { persist: false });
        }

        var $target = $('#smartPricingPanel .smart-pricing-input.is-invalid').first();
        if (!$target.length) {
          $target = $('#smartPricingPanel .smart-pricing-field-error').filter(function () {
            return $.trim($(this).text()) !== '';
          }).first();
        }
        if (!$target.length && !$('#smartPricingRangesError').hasClass('hidden') && $.trim($('#smartPricingRangesError').text())) {
          $target = $('#smartPricingRangesError');
        }
        if (!$target.length && !$('#smartPricingSaveStatus').hasClass('hidden') && $.trim($('#smartPricingSaveStatus').text())) {
          $target = $('#smartPricingSaveStatus');
        }
        if (!$target.length) {
          $target = $('#smartPricingPanel');
        }

        requestAnimationFrame(function () {
          var el = $target[0];
          if (!el || !el.scrollIntoView) {
            return;
          }
          el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
      }

      function parseOptionalNumber(value) {
        var trimmed = $.trim(String(value == null ? '' : value));
        if (trimmed === '') {
          return null;
        }
        var num = Number(trimmed);
        return Number.isFinite(num) ? num : trimmed;
      }

      function collectSmartPricingPayload() {
        var colorRaw = $.trim($('#smartColorPercent').val() || '');
        var colorPercent = colorRaw === '' ? null : Number(colorRaw);
        var ranges = [];

        $('#smartPricingRows .smart-pricing-row').each(function () {
          var $row = $(this);
          var kind = String($row.data('range-kind') || 'between');
          ranges.push({
            kind: kind,
            size_min: parseOptionalNumber($row.find('.js-smart-size-min').val()),
            size_max: parseOptionalNumber($row.find('.js-smart-size-max').val()),
            min_price: parseOptionalNumber($row.find('.js-smart-min-price').val()),
            max_price: parseOptionalNumber($row.find('.js-smart-max-price').val()),
            sessions: $.trim($row.find('.js-smart-sessions').val() || ''),
            duration: parseOptionalNumber($row.find('.js-smart-duration').val()),
          });
        });

        return {
          color_percent: Number.isFinite(colorPercent) ? colorPercent : colorRaw,
          ranges: ranges,
        };
      }

      function mapSmartPricingField(key) {
        var map = {
          size_min: '.js-smart-size-min',
          size_max: '.js-smart-size-max',
          min_price: '.js-smart-min-price',
          max_price: '.js-smart-max-price',
          sessions: '.js-smart-sessions',
          duration: '.js-smart-duration',
        };
        return map[key] || null;
      }

      function showSmartPricingFieldError($input, message, fieldKey) {
        if (!$input.length) {
          return;
        }
        $input.addClass('is-invalid');
        var $stack = $input.closest('.smart-pricing-field-stack');
        if (!$stack.length) {
          $stack = $input.closest('td');
        }
        if (!$stack.length) {
          return;
        }
        var key = fieldKey || 'field';
        var $existing = $stack.find('.smart-pricing-field-error[data-field="' + key + '"]');
        if ($existing.length) {
          $existing.text(message);
          return;
        }
        $stack.append(
          '<span class="smart-pricing-field-error" data-field="' + key + '">' +
          $('<div>').text(message).html() +
          '</span>'
        );
      }

      function showSmartColorPercentError(message) {
        var $input = $('#smartColorPercent');
        var $wrap = $('#smartColorPercentField');
        $input.addClass('is-invalid');
        if ($wrap.find('.smart-pricing-field-error').length) {
          $wrap.find('.smart-pricing-field-error').text(message);
          return;
        }
        $wrap.append('<span class="smart-pricing-field-error">' + $('<div>').text(message).html() + '</span>');
      }

      function applySmartPricingErrors(errors) {
        Object.keys(errors || {}).forEach(function (key) {
          var messages = errors[key];
          var message = Array.isArray(messages) ? messages[0] : String(messages || 'Invalid value.');

          if (key === 'color_percent') {
            showSmartColorPercentError(message);
            return;
          }

          if (key === 'ranges') {
            showSmartPricingRangesError(message);
            return;
          }

          var match = key.match(/^ranges\.(\d+)\.([a-z_]+)$/);
          if (!match) {
            return;
          }
          var index = Number(match[1]);
          var field = match[2];
          var selector = mapSmartPricingField(field);
          if (!selector) {
            return;
          }
          var $row = $('#smartPricingRows .smart-pricing-row').eq(index);
          showSmartPricingFieldError($row.find(selector).first(), message, field);
        });
      }

      $('#btnSaveSmartPricing').on('click', function () {
        clearSmartPricingValidation();
        var payload = collectSmartPricingPayload();
        if (!payload.ranges || !payload.ranges.length) {
          showSmartPricingRangesError('Add at least one size range before saving.');
          scrollToFirstSmartPricingError();
          return;
        }

        var $btn = $(this);
        var original = $btn.html();
        $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-lg animate-spin">progress_activity</span> Saving…');

        $.ajax({
          url: SMART_PRICING_VALIDATE_URL,
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify(payload),
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).done(function (res) {
          if (res && Array.isArray(res.ranges)) {
            SMART_PRICING_INITIAL_RANGES = res.ranges;
          }
          if (res && res.color_percent != null && Number.isFinite(Number(res.color_percent))) {
            SMART_PRICING_COLOR_PERCENT = Number(res.color_percent);
          }
          if (res && res.pricing_type) {
            storedPricingType = res.pricing_type;
            currentPricingType = res.pricing_type;
            $('#pricingSettingsPanel').data('pricing-type', res.pricing_type);
          } else {
            storedPricingType = 'smart';
            currentPricingType = 'smart';
            $('#pricingSettingsPanel').data('pricing-type', 'smart');
          }
          setPricingMode('smart', { persist: false });
          syncPricingAccordionHeader();
          if (typeof showSaveToast === 'function') {
            showSaveToast();
          }
        }).fail(function (xhr) {
          var data = xhr.responseJSON || {};
          if (xhr.status === 422 && data.errors) {
            applySmartPricingErrors(data.errors);
            scrollToFirstSmartPricingError();
          } else {
            $('#smartPricingSaveStatus')
              .removeClass('hidden text-green-700')
              .addClass('text-red-700')
              .text((data && data.message) || 'Unable to save smart pricing.');
            scrollToFirstSmartPricingError();
          }
        }).always(function () {
          $btn.prop('disabled', false).html(original);
        });
      });

      var suppressSmartPricingAutofill = false;

      function getActiveSmartPricingColorPercent() {
        var live = Number($.trim($('#smartColorPercent').val() || ''));
        if (Number.isFinite(live)) {
          return live;
        }
        var stored = Number(SMART_PRICING_COLOR_PERCENT);
        return Number.isFinite(stored) ? stored : 0;
      }

      function smartPricingRangeInterval(row) {
        if (!row || !row.kind) {
          return null;
        }
        var min = row.size_min != null && row.size_min !== '' ? Number(row.size_min) : null;
        var max = row.size_max != null && row.size_max !== '' ? Number(row.size_max) : null;
        if (row.kind === 'between') {
          if (!Number.isFinite(min) || !Number.isFinite(max) || max < min) {
            return null;
          }
          return { low: min, high: max };
        }
        if (row.kind === 'less_than') {
          if (!Number.isFinite(max)) {
            return null;
          }
          return { low: 0, high: max };
        }
        if (row.kind === 'more_than') {
          if (!Number.isFinite(min)) {
            return null;
          }
          return { low: min, high: Number.POSITIVE_INFINITY };
        }
        return null;
      }

      function findSmartPricingRangeForSize(size) {
        var sizeNum = Number(size);
        if (!Number.isFinite(sizeNum)) {
          return null;
        }
        var ranges = Array.isArray(SMART_PRICING_INITIAL_RANGES) ? SMART_PRICING_INITIAL_RANGES : [];
        for (var i = 0; i < ranges.length; i++) {
          var interval = smartPricingRangeInterval(ranges[i]);
          if (!interval) {
            continue;
          }
          // Ranges are lower-inclusive / upper-exclusive (e.g. 5 belongs to 5–10, not 0–5).
          if (sizeNum >= interval.low && sizeNum < interval.high) {
            return ranges[i];
          }
        }
        return null;
      }

      function mapSmartDurationHoursToSelect(hours) {
        var h = Number(hours);
        if (!Number.isFinite(h)) {
          return '';
        }
        var map = [
          { value: '30min', hours: 0.5 },
          { value: '1h', hours: 1 },
          { value: '2h', hours: 2 },
          { value: '3h', hours: 3 },
          { value: '4h', hours: 4 },
          { value: '6h', hours: 6 },
          { value: '8h', hours: 8 }
        ];
        for (var i = 0; i < map.length; i++) {
          if (Math.abs(map[i].hours - h) < 0.001) {
            return map[i].value;
          }
        }
        return '';
      }

      function parseSmartSessionsToMaxSessions(sessions) {
        var raw = $.trim(String(sessions == null ? '' : sessions));
        if (raw === '') {
          return '';
        }
        if (/^\d+(\.\d+)?$/.test(raw)) {
          return String(Math.max(1, Math.round(Number(raw))));
        }
        var rangeMatch = raw.match(/(\d+)\s*[-–to]+\s*(\d+)/i);
        if (rangeMatch) {
          return String(Math.max(1, parseInt(rangeMatch[2], 10)));
        }
        var firstNum = raw.match(/(\d+)/);
        return firstNum ? String(Math.max(1, parseInt(firstNum[1], 10))) : '';
      }

      function clearSmartPricingDesignFields() {
        $('#designPriceMin').val('');
        $('#designPriceMax').val('');
        $('#designSessionsMax').val('');
        $('#designSessionTime').val('');
      }

      var SMART_PRICING_NO_RANGE_ERROR = 'We cannot set the price for this design because there is no size range for this size. Please add a range that covers this size, then come back to finish this upload';

      function setSmartPricingNoRangeError(show) {
        var $err = $('.design-field-error[data-error-for="min_size"]');
        if (show) {
          $err.removeClass('hidden').text(SMART_PRICING_NO_RANGE_ERROR);
        } else if ($err.text() === SMART_PRICING_NO_RANGE_ERROR) {
          $err.addClass('hidden').empty();
        }
      }

      function applySmartPricingToDesignForm() {
        if (suppressSmartPricingAutofill) {
          return;
        }
        if (String(storedPricingType || '') !== 'smart') {
          setSmartPricingNoRangeError(false);
          return;
        }

        var sizeRaw = $.trim(String($('#size_min').val() || ''));
        if (sizeRaw === '') {
          clearSmartPricingDesignFields();
          setSmartPricingNoRangeError(false);
          return;
        }

        var sizeNum = Number(sizeRaw);
        if (!Number.isFinite(sizeNum) || sizeNum < 1) {
          clearSmartPricingDesignFields();
          setSmartPricingNoRangeError(false);
          return;
        }

        var match = findSmartPricingRangeForSize(sizeRaw);
        if (!match) {
          clearSmartPricingDesignFields();
          setSmartPricingNoRangeError(true);
          return;
        }

        var minPrice = Number(match.min_price);
        var maxPrice = Number(match.max_price);
        if (!Number.isFinite(minPrice) || !Number.isFinite(maxPrice)) {
          clearSmartPricingDesignFields();
          setSmartPricingNoRangeError(true);
          return;
        }

        setSmartPricingNoRangeError(false);

        var colorValue = String($('#designColors').val() || '');
        if (colorValue === 'color') {
          var percent = getActiveSmartPricingColorPercent();
          if (Number.isFinite(percent) && percent !== 0) {
            var multiplier = 1 + (percent / 100);
            minPrice = Math.round(minPrice * multiplier);
            maxPrice = Math.round(maxPrice * multiplier);
          }
        }

        $('#designPriceMin').val(minPrice);
        $('#designPriceMax').val(maxPrice);

        var maxSessions = parseSmartSessionsToMaxSessions(match.sessions);
        if (maxSessions !== '' && parseInt(maxSessions, 10) > 1) {
          $('#designSessionsMax').val(maxSessions);
        } else {
          $('#designSessionsMax').val('');
        }

        var durationValue = mapSmartDurationHoursToSelect(match.duration);
        $('#designSessionTime').val(durationValue || '');
      }

      $('#size_min').on('input change blur', function () {
        applySmartPricingToDesignForm();
      });

      $('#designColors').on('change', function () {
        applySmartPricingToDesignForm();
      });

      setPricingMode(currentPricingType, { persist: false });
      loadSmartPricingInitialRows();
      syncSmartPricingEmptyState();
      setPricingAccordionOpen(false);
      $('#smartColorPercent').on('input change', function () {
        syncPricingAccordionHeader();
      });

      function destroyDesignCropper() {
        if (designCropper) {
          designCropper.destroy();
          designCropper = null;
        }
      }

      function revokeDesignCropBlob() {
        var u = $designCropperImg.data('blob-url');
        if (u) {
          URL.revokeObjectURL(u);
          $designCropperImg.removeData('blob-url');
        }
      }

      function closeDesignCropModal() {
        destroyDesignCropper();
        revokeDesignCropBlob();
        $designCropperImg.attr('src', '');
        $designCropModal.removeClass('is-open').attr('aria-hidden', 'true');
      }

      function openDesignCropModalWithFile(file) {
        if (!file || !/^image\//.test(file.type)) {
          alert('Please choose an image file (PNG or JPG).');
          return;
        }
        if (file.size > MAX_FILE_BYTES) {
          alert('Image must be 10MB or smaller.');
          return;
        }
        destroyDesignCropper();
        revokeDesignCropBlob();
        var url = URL.createObjectURL(file);
        $designCropperImg.data('blob-url', url);
        $designCropModal.addClass('is-open').attr('aria-hidden', 'false');
        $designCropperImg.off('load.designcrop').on('load.designcrop', function () {
          var img = this;
          $designCropperImg.off('load.designcrop');
          destroyDesignCropper();
          designCropper = new Cropper(img, {
            aspectRatio: CROP_RATIO,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            responsive: true,
            restore: false,
            guides: true,
            center: true,
            highlight: true,
            background: false
          });
        });
        $designCropperImg.attr('src', url);
      }

      function applyDesignCrop() {
        if (!designCropper) return;
        var canvas = designCropper.getCroppedCanvas({
          width: CROP_OUT_W,
          height: CROP_OUT_H,
          // Flatten transparent areas onto light grey (avoids black JPEG fill).
          fillColor: '#e8e8e8',
          imageSmoothingEnabled: true,
          imageSmoothingQuality: 'high'
        });
        if (!canvas) {
          alert('Could not read the crop. Try again.');
          return;
        }
        var dataUrl = canvas.toDataURL('image/jpeg', 0.92);
        $('#designImageData').val(dataUrl);
        var $slot = $('#designImageUpload');
        var w = canvas.width || CROP_OUT_W;
        var h = canvas.height || CROP_OUT_H;
        if (w > 0 && h > 0) {
          $slot.addClass('has-preview')[0].style.setProperty('--design-preview-ar', w + ' / ' + h);
        }
        $('#designImagePreviewImg').attr('src', dataUrl);
        $('#designImageUploadEmpty').addClass('hidden');
        $('#designImageUploadPreview').removeClass('hidden');
        closeDesignCropModal();
        $('#designImage').val('');
        requestDesignAiSuggestions(dataUrl);
      }

      var designAiSuggestXhr = null;

      function parseDesignTagsInput() {
        return ($('#designTags').val() || '').split(',').map(function (t) {
          return $.trim(t);
        }).filter(Boolean);
      }

      function collectEmptyAiFieldsSnapshot() {
        var empty = {
          title: !$.trim($('#designTitle').val() || ''),
          description: !$.trim($('#designDescription').val() || ''),
          primary_style: !($('#designPrimaryStyle').val() || ''),
          other_styles: $('#designOtherStyles option:selected').length === 0,
          suggested_placements: $('#designSuggestedPlacements option:selected').length === 0,
          color: !($('#designColors').val() || ''),
          tags: parseDesignTagsInput().length === 0
        };
        return empty;
      }

      function setDesignAiBusy(busy) {
        $('#designImageUpload').toggleClass('ai-busy', !!busy);
      }

      function applyDesignAiSuggestions(suggestions, emptyAtRequest) {
        if (!suggestions || typeof suggestions !== 'object') return;
        var emptyNow = collectEmptyAiFieldsSnapshot();

        if (emptyAtRequest.title && emptyNow.title && suggestions.title) {
          $('#designTitle').val(suggestions.title);
        }
        if (emptyAtRequest.description && emptyNow.description && suggestions.description) {
          $('#designDescription').val(suggestions.description);
        }
        if (emptyAtRequest.primary_style && emptyNow.primary_style && suggestions.primary_style) {
          ensureDesignStyleSelectValue($('#designPrimaryStyle'), suggestions.primary_style);
          $('#designPrimaryStyle').trigger('change');
        }
        if (emptyAtRequest.other_styles && emptyNow.other_styles && Array.isArray(suggestions.other_styles)) {
          var primary = $('#designPrimaryStyle').val() || '';
          var picked = [];
          suggestions.other_styles.forEach(function (styleValue) {
            if (!styleValue || styleValue === primary) return;
            if (picked.indexOf(styleValue) !== -1) return;
            if (!designOtherStyleOptionByValue(styleValue).length) return;
            picked.push(styleValue);
          });
          picked = picked.slice(0, 2);
          $('#designOtherStyles option').prop('selected', false);
          picked.forEach(function (styleValue) {
            designOtherStyleOptionByValue(styleValue).prop('selected', true);
          });
          syncDesignOtherStylesChipsFromSelect();
        }
        if (emptyAtRequest.suggested_placements && emptyNow.suggested_placements && Array.isArray(suggestions.suggested_placements)) {
          var pickedPlacements = [];
          suggestions.suggested_placements.forEach(function (placementValue) {
            if (!placementValue) return;
            if (pickedPlacements.indexOf(placementValue) !== -1) return;
            if (!designPlacementOptionByValue(placementValue).length) return;
            pickedPlacements.push(placementValue);
          });
          pickedPlacements = pickedPlacements.slice(0, 3);
          $('#designSuggestedPlacements option').prop('selected', false);
          pickedPlacements.forEach(function (placementValue) {
            designPlacementOptionByValue(placementValue).prop('selected', true);
          });
          syncDesignPlacementsChipsFromSelect();
        }
        if (emptyAtRequest.color && emptyNow.color && suggestions.color) {
          $('#designColors').val(suggestions.color).trigger('change');
        }
        if (emptyAtRequest.tags && emptyNow.tags && Array.isArray(suggestions.tags) && suggestions.tags.length) {
          $('#designTags').val(suggestions.tags.join(', '));
        }
      }

      function requestDesignAiSuggestions(dataUrl) {
        if (!ARTIST_DESIGNS_AI_SUGGEST_URL || !dataUrl) return;

        var emptyAtRequest = collectEmptyAiFieldsSnapshot();
        var anyEmpty = Object.keys(emptyAtRequest).some(function (k) { return emptyAtRequest[k]; });
        if (!anyEmpty) return;

        if (designAiSuggestXhr && designAiSuggestXhr.readyState !== 4) {
          try { designAiSuggestXhr.abort(); } catch (e) { /* ignore */ }
        }

        var fd = new FormData();
        fd.append('image_data', dataUrl);
        fd.append('title', $('#designTitle').val() || '');
        fd.append('description', $('#designDescription').val() || '');
        fd.append('primary_style', $('#designPrimaryStyle').val() || '');
        fd.append('color', $('#designColors').val() || '');
        $('#designOtherStyles option:selected').each(function () {
          fd.append('other_styles[]', $(this).val());
        });
        $('#designSuggestedPlacements option:selected').each(function () {
          fd.append('suggested_placements[]', $(this).val());
        });
        parseDesignTagsInput().forEach(function (tag) {
          fd.append('tags[]', tag);
        });

        setDesignAiBusy(true);
        designAiSuggestXhr = $.ajax({
          url: ARTIST_DESIGNS_AI_SUGGEST_URL,
          method: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
          },
          success: function (res) {
            if (!res || !res.success) return;
            applyDesignAiSuggestions(res.suggestions || {}, emptyAtRequest);
          },
          error: function (xhr) {
            if (xhr && xhr.statusText === 'abort') return;
            // Silent fail — artist can still fill fields manually.
          },
          complete: function () {
            setDesignAiBusy(false);
            designAiSuggestXhr = null;
          }
        });
      }

      function resetDesignImageState() {
        closeDesignCropModal();
        if (designAiSuggestXhr && designAiSuggestXhr.readyState !== 4) {
          try { designAiSuggestXhr.abort(); } catch (e) { /* ignore */ }
        }
        setDesignAiBusy(false);
        $('#designImageData').val('');
        $('#designImagePreviewImg').attr('src', '');
        var slot = document.getElementById('designImageUpload');
        if (slot) {
          slot.classList.remove('has-preview');
          slot.style.removeProperty('--design-preview-ar');
        }
        $('#designImageUploadEmpty').removeClass('hidden');
        $('#designImageUploadPreview').addClass('hidden');
        $('#designImage').val('');
      }

      var DESIGN_FORM_FIELD_ORDER = ['image', 'min_size', 'title', 'description', 'repeat_limit', 'primary_style', 'other_styles', 'suggested_placements', 'color', 'tags', 'min_price', 'max_price', 'max_sessions', 'session_duration'];

      function setDesignFormBanner(msg) {
        var el = document.getElementById('designFormBanner');
        if (!el) return;
        if (!msg) {
          el.textContent = '';
          el.classList.add('hidden');
          return;
        }
        el.textContent = msg;
        el.classList.remove('hidden');
      }

      function clearDesignFormErrors() {
        setDesignFormBanner('');
        $('.design-field-error').addClass('hidden').empty();
      }

      function applyDesignFormErrorMap(map) {
        var bannerParts = [];
        Object.keys(map).forEach(function (key) {
          var raw = map[key];
          var msg = typeof raw === 'string' ? raw : (Array.isArray(raw) ? raw.join(' ') : String(raw));
          var baseKey = key.indexOf('.') !== -1 ? key.split('.')[0] : key;
          var $el = $('.design-field-error[data-error-for="' + key + '"]');
          if (!$el.length) {
            $el = $('.design-field-error[data-error-for="' + baseKey + '"]');
          }
          if ($el.length) {
            $el.removeClass('hidden').text(msg);
          } else {
            bannerParts.push(msg);
          }
        });
        if (bannerParts.length) {
          setDesignFormBanner(bannerParts.join('\n'));
        }
      }

      function scrollDesignModalToElement(el) {
        if (!el) return;
        var inner = document.querySelector('#newDesignModal .new-design-modal-inner');
        if (inner && el.getBoundingClientRect) {
          var er = el.getBoundingClientRect();
          var ir = inner.getBoundingClientRect();
          var delta = er.top - ir.top + inner.scrollTop - 16;
          inner.scrollTo({ top: Math.max(0, delta), behavior: 'smooth' });
          return;
        }
        if (el.scrollIntoView) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }

      function scrollToFirstDesignFormError() {
        var found = false;
        for (var i = 0; i < DESIGN_FORM_FIELD_ORDER.length; i++) {
          var f = DESIGN_FORM_FIELD_ORDER[i];
          var $err = $('.design-field-error[data-error-for="' + f + '"]');
          if (!$err.length || $err.hasClass('hidden') || !$.trim($err.text())) {
            continue;
          }
          found = true;
          var $section = $err.closest('.design-field-section');
          var $target = $section.length ? $section : $err;
          scrollDesignModalToElement($target[0]);
          if (f === 'primary_style' || f === 'color') {
            var $sel = f === 'primary_style' ? $('#designPrimaryStyle') : $('#designColors');
            setTimeout(function ($node) {
              return function () {
                try {
                  var $box = $node.next('.select2-container').find('.select2-selection');
                  if ($box.length) {
                    $box.trigger('focus');
                  }
                } catch (e) { /* ignore */ }
              };
            }($sel), 320);
          } else if (f === 'image') {
            setTimeout(function () {
              var z = document.getElementById('designImageUpload');
              if (z) z.focus();
            }, 320);
          } else {
            var $focus = $section.find('input:visible, textarea:visible, select:visible, button.style-chip:visible').filter(function () {
              return $(this).attr('type') !== 'file';
            }).first();
            if ($focus.length) {
              setTimeout(function ($node) {
                return function () {
                  try {
                    $node.trigger('focus');
                  } catch (e) { /* ignore */ }
                };
              }($focus), 320);
            }
          }
          break;
        }
        if (!found) {
          var banner = document.getElementById('designFormBanner');
          if (banner && !banner.classList.contains('hidden') && $.trim($(banner).text())) {
            scrollDesignModalToElement(banner);
          }
        }
      }

      function artistDesignUpdateUrl(id) {
        return String(ARTIST_DESIGNS_INDEX_URL).replace(/\/+$/, '') + '/' + encodeURIComponent(id);
      }

      function syncRepeatLimitFieldVisibility() {
        var repeatable = $('#toggleRepeatable').hasClass('active');
        $('#repeatLimitField').toggleClass('hidden', !repeatable);
        if (!repeatable) {
          $('#designRepeatLimit').val('');
          $('.design-field-error[data-error-for="repeat_limit"]').addClass('hidden').empty();
        }
      }

      function designOtherStyleOptionByValue(val) {
        return $('#designOtherStyles option').filter(function () {
          return $(this).val() === val;
        });
      }

      function designStyleChipByValue(val) {
        return $('#designOtherStylesChips .style-chip').filter(function () {
          return $(this).attr('data-value') === val;
        });
      }

      function designPlacementOptionByValue(val) {
        return $('#designSuggestedPlacements option').filter(function () {
          return $(this).val() === val;
        });
      }

      function designPlacementChipByValue(val) {
        return $('#designPlacementsChips .style-chip').filter(function () {
          return $(this).attr('data-value') === val;
        });
      }

      function ensureDesignStyleSelectValue($select, value) {
        if (!value) {
          return;
        }
        var exists = false;
        $select.find('option').each(function () {
          if ($(this).val() === value) {
            exists = true;
            return false;
          }
        });
        if (!exists) {
          $select.append($('<option></option>').attr('value', value).text(value));
        }
        $select.val(value);
      }

      function populateDesignFormFromPayload(d) {
        suppressSmartPricingAutofill = true;
        $('#designTitle').val(d.title || '');
        $('#designDescription').val(d.description || '');
        ensureDesignStyleSelectValue($('#designPrimaryStyle'), d.primary_style || '');
        $('#designPrimaryStyle').trigger('change');
        $('#designColors').val(d.color || '').trigger('change');
        $('#designTags').val(Array.isArray(d.tags) ? d.tags.join(', ') : '');
        $('#designPriceMin').val(d.min_price != null ? d.min_price : '');
        $('#designPriceMax').val(d.max_price != null ? d.max_price : '');
        $('#size_min').val(d.min_size != null ? d.min_size : '');
        $('#designSessionsMax').val((d.max_sessions != null && parseInt(d.max_sessions, 10) > 1) ? d.max_sessions : '');
        $('#designSessionTime').val(d.session_duration || '');
        $('#toggleVisibility').toggleClass('active', !!d.is_visible);
        $('#toggleAvailable').toggleClass('active', !!d.is_active);
        $('#toggleRepeatable').toggleClass('active', !!d.is_repeatable);
        $('#designRepeatLimit').val(d.is_repeatable && d.repeat_limit != null ? d.repeat_limit : '');
        syncRepeatLimitFieldVisibility();
        $('#toggleSensitive').toggleClass('active', !!d.is_sensitive);
        $('#designOtherStyles option').prop('selected', false);
        (d.other_styles || []).forEach(function (styleValue) {
          designOtherStyleOptionByValue(styleValue).prop('selected', true);
        });
        syncDesignOtherStylesChipsFromSelect();
        $('#designSuggestedPlacements option').prop('selected', false);
        (d.suggested_placements || []).forEach(function (placementValue) {
          designPlacementOptionByValue(placementValue).prop('selected', true);
        });
        syncDesignPlacementsChipsFromSelect();
        suppressSmartPricingAutofill = false;
      }

      function applyExistingDesignImagePreview(url) {
        $('#designImageData').val('');
        var $slot = $('#designImageUpload');
        $slot.removeClass('has-preview');
        if ($slot[0]) {
          $slot[0].style.removeProperty('--design-preview-ar');
        }
        var $img = $('#designImagePreviewImg');
        $img.off('load.designpref').on('load.designpref', function () {
          $img.off('load.designpref');
          var w = this.naturalWidth;
          var h = this.naturalHeight;
          if (w > 0 && h > 0 && $slot[0]) {
            $slot.addClass('has-preview');
            $slot[0].style.setProperty('--design-preview-ar', w + ' / ' + h);
          }
        });
        $img.attr('src', url || '');
        $('#designImageUploadEmpty').addClass('hidden');
        $('#designImageUploadPreview').removeClass('hidden');
        $('#designImage').val('');
      }

      function openEditDesignModal(d) {
        if (!d || !d.id) {
          return;
        }
        clearDesignFormErrors();
        resetDesignImageState();
        $newDesignModal.data('editingDesignId', d.id);
        $('#designModalTitle').text('Edit Design');
        $('#btnSaveDesign').html('<span class="material-symbols-outlined text-lg">save</span> Update Design');
        populateDesignFormFromPayload(d);
        applyExistingDesignImagePreview(d.image_url);
        openNewDesignModal();
      }

      function collectDesignFormClientErrors() {
        var errors = {};
        var editingId = $newDesignModal.data('editingDesignId');
        var dataUrl = $('#designImageData').val();
        var blob = dataUrl ? dataUrlToBlob(dataUrl) : null;
        var previewSrc = ($('#designImagePreviewImg').attr('src') || '').trim();
        var hasExistingPreview = !!editingId && !!previewSrc && !$('#designImageUploadPreview').hasClass('hidden');
        if (!blob && !hasExistingPreview) {
          errors.image = 'Please add and crop an image.';
        }
        var title = $.trim($('#designTitle').val());
        if (!title) {
          errors.title = 'Please enter a title.';
        } else if (title.length > 255) {
          errors.title = 'Title must not exceed 255 characters.';
        }
        var desc = $.trim($('#designDescription').val());
        if (desc.length > 255) {
          errors.description = 'Description must not exceed 255 characters.';
        }
        if ($('#toggleRepeatable').hasClass('active')) {
          var repeatLimitRaw = String($('#designRepeatLimit').val() || '').trim();
          var repeatLimit = parseInt(repeatLimitRaw, 10);
          if (repeatLimitRaw === '' || isNaN(repeatLimit) || repeatLimit < 1) {
            errors.repeat_limit = 'Please enter how many times this design can be booked (at least 1).';
          } else if (repeatLimit > 999) {
            errors.repeat_limit = 'Repeat limit cannot exceed 999.';
          }
        }
        var primary = $('#designPrimaryStyle').val();
        var allowedStyles = Array.isArray(ARTIST_DESIGN_STYLE_OPTIONS) ? ARTIST_DESIGN_STYLE_OPTIONS : [];
        if (!primary) {
          errors.primary_style = 'Please select a primary style.';
        } else if (allowedStyles.indexOf(primary) === -1) {
          errors.primary_style = 'Please select a valid primary style.';
        }
        var otherSelected = [];
        $('#designOtherStyles option:selected').each(function () {
          otherSelected.push($(this).val());
        });
        if (otherSelected.length > 2) {
          errors.other_styles = 'You can select at most 2 other styles.';
        }
        if (primary && otherSelected.indexOf(primary) !== -1) {
          errors.other_styles = 'Other styles cannot include the same value as primary style.';
        }
        otherSelected.forEach(function (v) {
          if (allowedStyles.indexOf(v) === -1) {
            errors.other_styles = 'One or more other styles are not valid.';
          }
        });
        var placementSelected = [];
        $('#designSuggestedPlacements option:selected').each(function () {
          placementSelected.push($(this).val());
        });
        var allowedPlacements = Array.isArray(ARTIST_DESIGN_PLACEMENT_OPTIONS) ? ARTIST_DESIGN_PLACEMENT_OPTIONS : [];
        if (placementSelected.length > 3) {
          errors.suggested_placements = 'You can select at most 3 suggested placements.';
        }
        placementSelected.forEach(function (v) {
          if (allowedPlacements.indexOf(v) === -1) {
            errors.suggested_placements = 'One or more placements are not valid.';
          }
        });
        var color = $('#designColors').val();
        if (!color) {
          errors.color = 'Please select a color option.';
        } else if (['color', 'black-grey', 'both'].indexOf(color) === -1) {
          errors.color = 'Please select a valid color option.';
        }
        var rawTags = $('#designTags').val();
        if (rawTags && $.trim(rawTags)) {
          var tagList = [];
          rawTags.split(',').forEach(function (t) {
            t = $.trim(t);
            if (t) tagList.push(t);
          });
          if (tagList.length > 30) {
            errors.tags = 'You can add at most 30 tags.';
          }
          for (var ti = 0; ti < tagList.length; ti++) {
            if (tagList[ti].length > 64) {
              errors.tags = 'Each tag must be 64 characters or fewer.';
              break;
            }
          }
        }
        var minPv = $('#designPriceMin').val();
        var maxPv = $('#designPriceMax').val();
        var minP = parseInt(minPv, 10);
        var maxP = parseInt(maxPv, 10);
        if (minPv === '' || isNaN(minP) || minP < 0) {
          errors.min_price = 'Please enter a valid minimum price (0 or more).';
        }
        if (maxPv === '' || isNaN(maxP) || maxP < 0) {
          errors.max_price = 'Please enter a valid maximum price (0 or more).';
        }
        if (!errors.min_price && !errors.max_price && minP > maxP) {
          errors.max_price = 'Maximum price must be greater than or equal to minimum price.';
        }
        var sizeMinRaw = String($('#size_min').val() || '').trim();
        var sizeMin = parseInt(sizeMinRaw, 10);
        if (sizeMinRaw === '' || isNaN(sizeMin) || sizeMin < 1) {
          errors.min_size = 'You need to enter the minimum size';
        } else if (String(storedPricingType || '') === 'smart' && !findSmartPricingRangeForSize(sizeMinRaw)) {
          errors.min_size = SMART_PRICING_NO_RANGE_ERROR;
        }
        var maxSev = String($('#designSessionsMax').val() || '').trim();
        if (maxSev !== '') {
          var maxSe = parseInt(maxSev, 10);
          if (isNaN(maxSe) || maxSe < 1) {
            errors.max_sessions = 'Please enter at least 1 session, or leave blank for a single-session design.';
          }
        }
        var sessionDur = $('#designSessionTime').val();
        var allowedDur = ['30min', '1h', '2h', '3h', '4h', '6h', '8h'];
        if (!sessionDur) {
          errors.session_duration = 'Please select session time.';
        } else if (allowedDur.indexOf(sessionDur) === -1) {
          errors.session_duration = 'Please select a valid session time.';
        }
        return errors;
      }

      function showDesignServerErrors(payload) {
        clearDesignFormErrors();
        var errs = (payload && payload.errors) ? payload.errors : {};
        var map = {};
        Object.keys(errs).forEach(function (key) {
          var msgs = errs[key];
          var msg = Array.isArray(msgs) ? msgs.join(' ') : String(msgs);
          var baseKey = key.indexOf('.') !== -1 ? key.split('.')[0] : key;
          if (map[baseKey]) {
            map[baseKey] += ' ' + msg;
          } else {
            map[baseKey] = msg;
          }
        });
        applyDesignFormErrorMap(map);
        requestAnimationFrame(function () {
          scrollToFirstDesignFormError();
        });
      }

      function dataUrlToBlob(dataUrl) {
        if (!dataUrl || dataUrl.indexOf(',') === -1) return null;
        var parts = dataUrl.split(',');
        var mimeMatch = parts[0].match(/:(.*?);/);
        if (!mimeMatch) return null;
        var mime = mimeMatch[1];
        var binary = atob(parts[1]);
        var len = binary.length;
        var arr = new Uint8Array(len);
        for (var i = 0; i < len; i++) {
          arr[i] = binary.charCodeAt(i);
        }
        return new Blob([arr], { type: mime });
      }

      function resetNewDesignFormFields() {
        clearDesignFormErrors();
        $newDesignModal.data('editingDesignId', null);
        $('#designModalTitle').text('New Design');
        $('#btnSaveDesign').html('<span class="material-symbols-outlined text-lg">save</span> Save Design');
        resetDesignImageState();
        suppressSmartPricingAutofill = true;
        $('#designTitle').val('');
        $('#designDescription').val('');
        $('#designPrimaryStyle').val('').trigger('change');
        $('#designColors').val('').trigger('change');
        $('#designTags').val('');
        $('#designPriceMin').val('');
        $('#designPriceMax').val('');
        $('#size_min').val('');
        $('#designSessionsMax').val('');
        $('#designSessionTime').val('');
        $('#toggleVisibility, #toggleAvailable').addClass('active');
        $('#toggleRepeatable, #toggleSensitive').removeClass('active');
        $('#designRepeatLimit').val('');
        syncRepeatLimitFieldVisibility();
        $('#designOtherStyles option').prop('selected', false);
        syncDesignOtherStylesChipsFromSelect();
        $('#designSuggestedPlacements option').prop('selected', false);
        syncDesignPlacementsChipsFromSelect();
        suppressSmartPricingAutofill = false;
      }

      function updateDesignOtherStylesChipsUI() {
        var $chips = $('#designOtherStylesChips .style-chip');
        var n = $chips.filter('.is-selected').length;
        $('#designOtherStylesCount').text(n);
        var atMax = n >= 2;
        $chips.each(function () {
          var on = $(this).hasClass('is-selected');
          $(this).prop('disabled', atMax && !on).attr('aria-pressed', on ? 'true' : 'false');
        });
      }

      function syncDesignOtherStylesChipsFromSelect() {
        $('#designOtherStylesChips .style-chip').removeClass('is-selected').prop('disabled', false);
        $('#designOtherStyles option').each(function () {
          if (this.selected) {
            designStyleChipByValue(this.value).addClass('is-selected');
          }
        });
        updateDesignOtherStylesChipsUI();
      }

      function updateDesignPlacementsChipsUI() {
        var $chips = $('#designPlacementsChips .style-chip');
        var n = $chips.filter('.is-selected').length;
        $('#designPlacementsCount').text(n);
        var atMax = n >= 3;
        $chips.each(function () {
          var on = $(this).hasClass('is-selected');
          $(this).prop('disabled', atMax && !on).attr('aria-pressed', on ? 'true' : 'false');
        });
      }

      function syncDesignPlacementsChipsFromSelect() {
        $('#designPlacementsChips .style-chip').removeClass('is-selected').prop('disabled', false);
        $('#designSuggestedPlacements option').each(function () {
          if (this.selected) {
            designPlacementChipByValue(this.value).addClass('is-selected');
          }
        });
        updateDesignPlacementsChipsUI();
      }

      function initDesignModalSelect2() {
        if (!window.jQuery || !$.fn.select2) return;
        var $primary = $('#designPrimaryStyle');
        var $colors = $('#designColors');
        if ($primary.length && !$primary.hasClass('select2-hidden-accessible')) {
          $primary.select2({
            width: '100%',
            dropdownParent: $('body'),
            placeholder: 'Select style…',
            allowClear: true
          });
        }
        if ($colors.length && !$colors.hasClass('select2-hidden-accessible')) {
          $colors.select2({
            width: '100%',
            dropdownParent: $('body'),
            placeholder: 'Select…',
            allowClear: true,
            minimumResultsForSearch: Infinity
          });
        }
      }

      function closeDesignModalSelect2() {
        ['#designPrimaryStyle', '#designColors'].forEach(function (sel) {
          var $n = $(sel);
          if ($n.length && $n.hasClass('select2-hidden-accessible')) {
            try {
              $n.select2('close');
            } catch (e) { /* ignore */ }
          }
        });
      }

      function openNewDesignModal() {
        clearTimeout($newDesignModal.data('closeTimer'));
        $newDesignModal.addClass('modal-visible').attr('aria-hidden', 'false');
        $('body').css('overflow', 'hidden');
        syncDesignOtherStylesChipsFromSelect();
        syncDesignPlacementsChipsFromSelect();
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            $newDesignModal.addClass('modal-open');
            initDesignModalSelect2();
            $('#designPrimaryStyle, #designColors').each(function () {
              if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).trigger('change.select2');
              }
            });
          });
        });
      }

      function closeNewDesignModal() {
        closeDesignModalSelect2();
        resetNewDesignFormFields();
        $newDesignModal.removeClass('modal-open');
        clearTimeout($newDesignModal.data('closeTimer'));
        var t = setTimeout(function () {
          $newDesignModal.removeClass('modal-visible').attr('aria-hidden', 'true');
          $('body').css('overflow', '');
        }, MODAL_MS);
        $newDesignModal.data('closeTimer', t);
      }

      function saveDesign() {
        clearDesignFormErrors();
        var clientErrors = collectDesignFormClientErrors();
        if (Object.keys(clientErrors).length) {
          applyDesignFormErrorMap(clientErrors);
          requestAnimationFrame(function () {
            scrollToFirstDesignFormError();
          });
          return;
        }
        var editId = $newDesignModal.data('editingDesignId');
        var dataUrl = $('#designImageData').val();
        var blob = dataUrl ? dataUrlToBlob(dataUrl) : null;
        var fd = new FormData();
        if (blob) {
          fd.append('image', blob, 'design.jpg');
        }
        fd.append('title', $.trim($('#designTitle').val()));
        fd.append('description', $.trim($('#designDescription').val()));
        fd.append('is_visible', $('#toggleVisibility').hasClass('active') ? '1' : '0');
        fd.append('is_active', $('#toggleAvailable').hasClass('active') ? '1' : '0');
        fd.append('is_repeatable', $('#toggleRepeatable').hasClass('active') ? '1' : '0');
        if ($('#toggleRepeatable').hasClass('active')) {
          fd.append('repeat_limit', String($('#designRepeatLimit').val() || '').trim());
        }
        fd.append('is_sensitive', $('#toggleSensitive').hasClass('active') ? '1' : '0');
        fd.append('primary_style', $('#designPrimaryStyle').val());
        $('#designOtherStyles option:selected').each(function () {
          fd.append('other_styles[]', $(this).val());
        });
        $('#designSuggestedPlacements option:selected').each(function () {
          fd.append('suggested_placements[]', $(this).val());
        });
        fd.append('color', $('#designColors').val());
        var rawTags = $('#designTags').val();
        if (rawTags) {
          rawTags.split(',').forEach(function (t) {
            t = $.trim(t);
            if (t) fd.append('tags[]', t);
          });
        }
        fd.append('min_price', $('#designPriceMin').val());
        fd.append('max_price', $('#designPriceMax').val());
        fd.append('min_size', $('#size_min').val());
        fd.append('min_sessions', '1');
        var maxSessionsVal = String($('#designSessionsMax').val() || '').trim();
        fd.append('max_sessions', maxSessionsVal === '' ? '1' : maxSessionsVal);
        fd.append('session_duration', $('#designSessionTime').val());
        var $btn = $('#btnSaveDesign');
        var btnHtml = $btn.html();
        var savingLabel = editId ? 'Updating…' : 'Saving…';
        $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-lg animate-pulse">hourglass_empty</span> ' + savingLabel);
        var saveUrl = editId ? artistDesignUpdateUrl(editId) : ARTIST_DESIGNS_STORE_URL;
        var saveMethod = 'POST';
        if (editId) {
          fd.append('_method', 'PUT');
        }
        fetch(saveUrl, {
          method: saveMethod,
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: fd,
          credentials: 'same-origin'
        }).then(function (res) {
          var ct = res.headers.get('content-type') || '';
          if (ct.indexOf('application/json') !== -1) {
            return res.json().then(function (data) {
              return { ok: res.ok, status: res.status, data: data };
            });
          }
          return res.text().then(function () {
            return { ok: false, status: res.status, data: {} };
          });
        }).then(function (result) {
          if (result.ok && result.data && result.data.success) {
            if (typeof showSaveToast === 'function') {
              showSaveToast();
            }
            window.location.reload();
            return;
          }
          if (result.status === 422 && result.data && result.data.errors) {
            showDesignServerErrors(result.data);
            return;
          }
          setDesignFormBanner((result.data && result.data.message) ? result.data.message : 'Could not save. Try again.');
          requestAnimationFrame(function () {
            scrollDesignModalToElement(document.getElementById('designFormBanner'));
          });
        }).catch(function () {
          setDesignFormBanner('Network error. Try again.');
          requestAnimationFrame(function () {
            scrollDesignModalToElement(document.getElementById('designFormBanner'));
          });
        }).finally(function () {
          $btn.prop('disabled', false).html(btnHtml);
        });
      }

      $('#btnOpenNewDesign').on('click', function () {
        resetNewDesignFormFields();
        openNewDesignModal();
      });

      $(document).on('click', '.btn-edit-design', function () {
        var id = $(this).data('design-id');
        var d = ARTIST_DESIGNS_BY_ID[id] || ARTIST_DESIGNS_BY_ID[String(id)];
        if (!d) {
          return;
        }
        openEditDesignModal(d);
      });

      function openDeleteDesignModal(delUrl) {
        if (!delUrl) {
          return;
        }
        $deleteDesignModal.data('delete-url', delUrl);
        $('#deleteDesignError').addClass('hidden').text('');
        $('#btnDeleteDesignConfirm').prop('disabled', false);
        $('#btnDeleteDesignConfirm .confirm-delete-design-icon').text('delete').removeClass('animate-pulse');
        $('#btnDeleteDesignConfirm .confirm-delete-design-label').text('Delete');
        clearTimeout($deleteDesignModal.data('closeTimer'));
        $deleteDesignModal.addClass('modal-visible').attr('aria-hidden', 'false');
        $('body').css('overflow', 'hidden');
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            $deleteDesignModal.addClass('modal-open');
          });
        });
      }

      function closeDeleteDesignModal() {
        $deleteDesignModal.removeClass('modal-open');
        clearTimeout($deleteDesignModal.data('closeTimer'));
        var t = setTimeout(function () {
          $deleteDesignModal.removeClass('modal-visible').attr('aria-hidden', 'true');
          if (!$newDesignModal.hasClass('modal-open') && !$newDesignModal.hasClass('modal-visible') && !$designCropModal.hasClass('is-open')) {
            $('body').css('overflow', '');
          }
          $deleteDesignModal.removeData('delete-url');
        }, MODAL_MS);
        $deleteDesignModal.data('closeTimer', t);
      }

      function runDesignDeleteRequest(delUrl) {
        return fetch(delUrl, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        }).then(function (res) {
          var ct = res.headers.get('content-type') || '';
          if (ct.indexOf('application/json') !== -1) {
            return res.json().then(function (data) {
              return { ok: res.ok, status: res.status, data: data };
            });
          }
          return res.text().then(function () {
            return { ok: false, status: res.status, data: {} };
          });
        });
      }

      $(document).on('click', '.btn-delete-design', function () {
        var delUrl = $(this).data('delete-url');
        if (!delUrl) {
          return;
        }
        openDeleteDesignModal(delUrl);
      });

      function updateDesignCardAvailability($card, isActive) {
        var $badge = $card.find('.design-availability-badge');
        var $label = $card.find('.design-availability-label');
        $badge.toggleClass('on', !!isActive).toggleClass('off', !isActive);
        $label.text(isActive ? 'Available' : 'Unavailable');
        $card.find('.btn-toggle-design-availability')
          .toggleClass('active', !!isActive)
          .attr('aria-checked', isActive ? 'true' : 'false');
        $card.attr('data-is-active', isActive ? '1' : '0');
      }

      $(document).on('click', '.btn-toggle-design-availability', function () {
        var $toggle = $(this);
        if ($toggle.data('loading')) {
          return;
        }
        var toggleUrl = $toggle.data('toggle-url');
        if (!toggleUrl) {
          return;
        }
        var willBeActive = !$toggle.hasClass('active');
        $toggle.data('loading', true);
        fetch(toggleUrl, {
          method: 'PATCH',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin',
          body: JSON.stringify({ is_active: willBeActive })
        }).then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok, data: data };
          });
        }).then(function (result) {
          if (!result.ok || !result.data || !result.data.success) {
            throw new Error((result.data && result.data.message) || 'Could not update availability.');
          }
          var isActive = !!result.data.is_active;
          $toggle.toggleClass('active', isActive).attr('aria-checked', isActive ? 'true' : 'false');
          var designId = $toggle.data('design-id');
          var $card = $toggle.closest('.design-card-wrap');
          updateDesignCardAvailability($card, isActive);
          if (designId && ARTIST_DESIGNS_BY_ID[designId]) {
            ARTIST_DESIGNS_BY_ID[designId].is_active = isActive;
          }
        }).catch(function (e) {
          window.alert(e.message || 'Could not update availability.');
        }).finally(function () {
          $toggle.data('loading', false);
        });
      });

      $('#btnDeleteDesignCancel').on('click', closeDeleteDesignModal);
      $deleteDesignModal.on('click', function (e) {
        if (e.target === this) {
          closeDeleteDesignModal();
        }
      });
      $deleteDesignModal.find('.design-delete-modal-inner').on('click', function (e) {
        e.stopPropagation();
      });

      $('#btnDeleteDesignConfirm').on('click', function () {
        var delUrl = $deleteDesignModal.data('delete-url');
        if (!delUrl) {
          return;
        }
        var $btn = $('#btnDeleteDesignConfirm');
        $('#deleteDesignError').addClass('hidden').text('');
        $btn.prop('disabled', true);
        $btn.find('.confirm-delete-design-icon').text('hourglass_empty').addClass('animate-pulse');
        $btn.find('.confirm-delete-design-label').text('Deleting…');
        runDesignDeleteRequest(delUrl).then(function (result) {
          if (result.ok && result.data && result.data.success) {
            closeDeleteDesignModal();
            window.location.reload();
            return;
          }
          var msg = (result.data && result.data.message) ? result.data.message : 'Could not delete this design.';
          $('#deleteDesignError').removeClass('hidden').text(msg);
          $btn.prop('disabled', false);
          $btn.find('.confirm-delete-design-icon').text('delete').removeClass('animate-pulse');
          $btn.find('.confirm-delete-design-label').text('Delete');
        }).catch(function () {
          $('#deleteDesignError').removeClass('hidden').text('Network error. Try again.');
          $btn.prop('disabled', false);
          $btn.find('.confirm-delete-design-icon').text('delete').removeClass('animate-pulse');
          $btn.find('.confirm-delete-design-label').text('Delete');
        });
      });
      $('#btnCloseNewDesign, #btnCancelNewDesign').on('click', closeNewDesignModal);
      $newDesignModal.on('click', function (e) {
        if (e.target === this) {
          closeNewDesignModal();
        }
      });
      $newDesignModal.find('.new-design-modal-inner').on('click', function (e) {
        e.stopPropagation();
      });

      $('#newDesignModal .toggle-switch').on('click', function () {
        $(this).toggleClass('active');
        if (this.id === 'toggleRepeatable') {
          syncRepeatLimitFieldVisibility();
        }
      });

      $('#designImageUpload').on('click', function (e) {
        e.preventDefault();
        var input = document.getElementById('designImage');
        if (input) input.click();
      });

      $('#designImage').on('change', function () {
        var file = this.files && this.files[0];
        if (file) {
          openDesignCropModalWithFile(file);
        }
      });

      $('#designImageUpload')
        .on('dragover', function (e) {
          e.preventDefault();
          e.stopPropagation();
          $(this).addClass('border-primary/60 bg-primary/5');
        })
        .on('dragleave drop', function (e) {
          e.preventDefault();
          e.stopPropagation();
          $(this).removeClass('border-primary/60 bg-primary/5');
        })
        .on('drop', function (e) {
          var file = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files[0];
          if (file) {
            openDesignCropModalWithFile(file);
          }
        });

      $('#btnDesignCropApply').on('click', applyDesignCrop);
      $('#btnDesignCropCancel, #btnDesignCropClose').on('click', function () {
        closeDesignCropModal();
        $('#designImage').val('');
      });

      $designCropModal.on('click', function (e) {
        if (e.target === this) {
          closeDesignCropModal();
          $('#designImage').val('');
        }
      });

      $('#designOtherStylesChips').on('click', '.style-chip', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        var val = $btn.attr('data-value');
        var $opt = designOtherStyleOptionByValue(val);
        if ($btn.hasClass('is-selected')) {
          $btn.removeClass('is-selected');
          $opt.prop('selected', false);
        } else {
          if ($('#designOtherStylesChips .style-chip.is-selected').length >= 2) return;
          $btn.addClass('is-selected');
          $opt.prop('selected', true);
        }
        updateDesignOtherStylesChipsUI();
      });

      $('#designPlacementsChips').on('click', '.style-chip', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        var val = $btn.attr('data-value');
        var $opt = designPlacementOptionByValue(val);
        if ($btn.hasClass('is-selected')) {
          $btn.removeClass('is-selected');
          $opt.prop('selected', false);
        } else {
          if ($('#designPlacementsChips .style-chip.is-selected').length >= 3) return;
          $btn.addClass('is-selected');
          $opt.prop('selected', true);
        }
        updateDesignPlacementsChipsUI();
      });

      $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if ($designCropModal.hasClass('is-open')) {
          closeDesignCropModal();
          $('#designImage').val('');
          return;
        }
        if ($deleteDesignModal.hasClass('modal-open')) {
          closeDeleteDesignModal();
          return;
        }
        if ($newDesignModal.hasClass('modal-open')) {
          closeNewDesignModal();
        }
      });

      $('#btnSaveDesign').on('click', saveDesign);

      var designSearchDebounceTimer = null;

      function applyDesignFilters() {
        var $grid = $('#designsGrid');
        var $wraps = $grid.find('.design-card-wrap');
        var $filterEmpty = $('#designsFilterEmpty');
        var $noDesigns = $('#designsNoDesigns');
        if (!$wraps.length) {
          $filterEmpty.addClass('hidden');
          syncDesignDragLock();
          return;
        }
        var filter = $('#designFilterPills .filter-pill.active').data('filter') || 'all';
        var q = ($('#searchDesigns').val() || '').trim().toLowerCase();
        var sort = $('#sortDesigns').val() || 'custom';
        var matched = [];
        $wraps.each(function () {
          var $w = $(this);
          var isActive = String($w.attr('data-is-active')) === '1';
          var isSoldOut = String($w.attr('data-is-sold-out')) === '1';
          if (filter === 'available' && (!isActive || isSoldOut)) {
            return;
          }
          if (filter === 'sold-out' && !isSoldOut) {
            return;
          }
          var hay = String($w.attr('data-search') || '').toLowerCase();
          if (q && hay.indexOf(q) === -1) {
            return;
          }
          matched.push(this);
        });
        matched.sort(function (a, b) {
          var $a = $(a);
          var $b = $(b);
          if (sort === 'price-high') {
            return (parseInt($b.attr('data-max-price'), 10) || 0) - (parseInt($a.attr('data-max-price'), 10) || 0);
          }
          if (sort === 'newest') {
            return (parseInt($b.attr('data-created'), 10) || 0) - (parseInt($a.attr('data-created'), 10) || 0);
          }
          return (parseInt($a.attr('data-sort-order'), 10) || 0) - (parseInt($b.attr('data-sort-order'), 10) || 0);
        });
        $wraps.addClass('hidden');
        matched.forEach(function (el) {
          $(el).removeClass('hidden');
          $grid.append(el);
        });
        $grid.append($filterEmpty);
        if ($noDesigns.length) {
          $grid.append($noDesigns);
        }
        if (!matched.length) {
          $filterEmpty.removeClass('hidden');
        } else {
          $filterEmpty.addClass('hidden');
        }
        syncDesignDragLock();
      }

      function syncDesignDragLock() {
        var sort = $('#sortDesigns').val() || 'custom';
        var filter = $('#designFilterPills .filter-pill.active').data('filter') || 'all';
        var q = ($('#searchDesigns').val() || '').trim();
        var canDrag = sort === 'custom' && filter === 'all' && !q;
        $('body').toggleClass('design-sort-locked', !canDrag);
        if (window.designsSortable) {
          window.designsSortable.option('disabled', !canDrag);
        }
      }

      function persistDesignOrder() {
        var ids = [];
        $('#designsGrid .design-card-wrap:not(.hidden)').each(function (index) {
          var id = parseInt($(this).attr('data-design-id'), 10);
          if (!id) return;
          ids.push(id);
          $(this).attr('data-sort-order', index + 1);
          if (ARTIST_DESIGNS_BY_ID && ARTIST_DESIGNS_BY_ID[id]) {
            ARTIST_DESIGNS_BY_ID[id].sort_order = index + 1;
          }
        });
        if (!ids.length || !ARTIST_DESIGNS_REORDER_URL) return;

        $.ajax({
          url: ARTIST_DESIGNS_REORDER_URL,
          method: 'POST',
          contentType: 'application/json; charset=UTF-8',
          data: JSON.stringify({ ids: ids }),
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).done(function () {
          if (typeof showSaveToast === 'function') {
            showSaveToast();
          }
        }).fail(function (xhr) {
          var msg = (xhr.responseJSON && xhr.responseJSON.message)
            ? xhr.responseJSON.message
            : 'Could not save design order.';
          window.alert(msg);
        });
      }

      if (typeof Sortable !== 'undefined') {
        var designsGridEl = document.getElementById('designsGrid');
        if (designsGridEl) {
          window.designsSortable = Sortable.create(designsGridEl, {
            draggable: '.design-card-wrap',
            handle: '.design-drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            filter: '#designsNoDesigns, #designsFilterEmpty',
            onEnd: function () {
              if (($('#sortDesigns').val() || 'custom') !== 'custom') return;
              persistDesignOrder();
            }
          });
        }
      }

      $('#designFilterPills').on('click', '.filter-pill', function () {
        var $btn = $(this);
        if ($btn.hasClass('active')) {
          return;
        }
        $('#designFilterPills .filter-pill').removeClass('active');
        $btn.addClass('active');
        applyDesignFilters();
      });

      $('#sortDesigns').on('change', function () {
        applyDesignFilters();
      });

      $('#searchDesigns').on('input', function () {
        clearTimeout(designSearchDebounceTimer);
        designSearchDebounceTimer = setTimeout(function () {
          applyDesignFilters();
        }, 200);
      });

      applyDesignFilters();

      syncDesignOtherStylesChipsFromSelect();
      syncDesignPlacementsChipsFromSelect();

      (function initWhatsIncludedUi() {
        var MAX_ITEMS = 8;
        var DEFAULT_PLACEHOLDERS = [
          'e.g. Custom sizing consultation',
          'e.g. Design placement guidance',
          'e.g. Touch-up session within 3 months',
          'e.g. Aftercare instructions'
        ];
        var PRESETS = [
          'Sizing consultation',
          'Placement guidance',
          'Color matching consultation',
          'Custom adjustments',
          'Stencil preview',
          'Breaks as needed',
          'Reference photos provided',
          'Aftercare instructions',
          'Aftercare product recommendations',
          'Healing check-in (photo review)'
        ];
        var $list = $('#whatsIncludedList');
        var $editor = $('#whatsIncludedEditor');
        var $presets = $('#whatsIncludedPresets');
        var $addBtn = $('#btnAddIncludedItem');
        var $limitHint = $('#whatsIncludedLimitHint');
        var $toggle = $('#toggleWhatsIncluded');
        var $toggleLabel = $('#toggleWhatsIncludedLabel');
        var $saveStatus = $('#whatsIncludedSaveStatus');

        if (!$list.length) return;

        function syncToggleUi() {
          var on = $toggle.hasClass('active');
          $toggle.attr('aria-checked', on ? 'true' : 'false');
          $toggleLabel.text(on ? 'Yes' : 'No').toggleClass('text-primary', on).toggleClass('text-on-surface-variant', !on);
          $editor.toggleClass('hidden', !on);
        }

        function rowCount() {
          return $list.find('.included-item-row').length;
        }

        function rowValues() {
          var values = [];
          $list.find('.included-item-input').each(function () {
            var v = $.trim($(this).val());
            if (v) values.push(v.toLowerCase());
          });
          return values;
        }

        function syncRemoveButtons() {
          $list.find('.included-item-row').each(function () {
            var hasText = $.trim($(this).find('.included-item-input').val()) !== '';
            $(this).find('.included-item-remove').toggleClass('is-hidden', !hasText);
          });
        }

        function syncAddButton() {
          var atMax = rowCount() >= MAX_ITEMS;
          $addBtn.prop('disabled', atMax).toggleClass('opacity-40 cursor-not-allowed', atMax);
          $limitHint.toggleClass('hidden', !atMax);
        }

        function syncPresetButtons() {
          var existing = rowValues();
          $presets.find('.included-preset-chip').each(function () {
            var label = String($(this).data('preset') || '').trim().toLowerCase();
            var used = existing.indexOf(label) !== -1;
            $(this).prop('disabled', used || rowCount() >= MAX_ITEMS);
          });
        }

        function buildRow(placeholder, value) {
          var $row = $('<div class="included-item-row"></div>');
          var $input = $('<input type="text" class="included-item-input w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" maxlength="255">');
          $input.attr('placeholder', placeholder || 'e.g. Add a service detail');
          if (value) $input.val(value);
          var $remove = $('<button type="button" class="included-item-remove is-hidden" title="Remove item" aria-label="Remove item"><span class="material-symbols-outlined text-[18px]">close</span></button>');
          $row.append($input, $remove);
          return $row;
        }

        function appendRow(placeholder, value) {
          if (rowCount() >= MAX_ITEMS) return false;
          $list.append(buildRow(placeholder, value || ''));
          syncRemoveButtons();
          syncAddButton();
          syncPresetButtons();
          return true;
        }

        function firstEmptyInput() {
          var $empty = null;
          $list.find('.included-item-input').each(function () {
            if (!$empty && $.trim($(this).val()) === '') {
              $empty = $(this);
            }
          });
          return $empty;
        }

        function insertPreset(text) {
          var label = String(text || '').trim();
          if (!label || rowCount() >= MAX_ITEMS) return;
          var lower = label.toLowerCase();
          if (rowValues().indexOf(lower) !== -1) return;
          var $empty = firstEmptyInput();
          if ($empty && $empty.length) {
            $empty.val(label);
          } else {
            appendRow('e.g. Add a service detail', label);
          }
          syncRemoveButtons();
          syncPresetButtons();
        }

        function loadInitialRows() {
          $list.empty();
          var savedItems = (WHATS_INCLUDED_INITIAL && WHATS_INCLUDED_INITIAL.items) || [];
          if (savedItems.length) {
            savedItems.forEach(function (item, idx) {
              appendRow(DEFAULT_PLACEHOLDERS[idx] || 'e.g. Add a service detail', item);
            });
          } else {
            DEFAULT_PLACEHOLDERS.forEach(function (ph) {
              appendRow(ph, '');
            });
          }
        }

        loadInitialRows();

        PRESETS.forEach(function (label) {
          $presets.append(
            $('<button type="button" class="included-preset-chip"></button>')
              .attr('data-preset', label)
              .html('<span class="material-symbols-outlined text-[16px]">add</span> ' + label)
          );
        });

        $addBtn.on('click', function () {
          appendRow('e.g. Add a service detail', '');
          $list.find('.included-item-row:last .included-item-input').trigger('focus');
        });

        $list.on('input', '.included-item-input', function () {
          syncRemoveButtons();
          syncPresetButtons();
        });

        $list.on('click', '.included-item-remove', function () {
          var $row = $(this).closest('.included-item-row');
          if (rowCount() <= 1) {
            $row.find('.included-item-input').val('');
            syncRemoveButtons();
            syncPresetButtons();
            return;
          }
          $row.remove();
          syncRemoveButtons();
          syncAddButton();
          syncPresetButtons();
        });

        $presets.on('click', '.included-preset-chip', function () {
          if ($(this).prop('disabled')) return;
          insertPreset($(this).data('preset'));
        });

        function collectItems() {
          var items = [];
          $list.find('.included-item-input').each(function () {
            var v = $.trim($(this).val());
            if (v) items.push(v);
          });
          return items;
        }

        function showWhatsIncludedStatus(message, isError) {
          $saveStatus.removeClass('hidden text-green-700 text-error')
            .addClass(isError ? 'text-error' : 'text-green-700')
            .text(message || '');
          clearTimeout($saveStatus.data('timer'));
          if (message) {
            var t = setTimeout(function () { $saveStatus.addClass('hidden').text(''); }, 4000);
            $saveStatus.data('timer', t);
          }
        }

        function saveWhatsIncluded(payload) {
          return fetch(WHATS_INCLUDED_UPDATE_URL, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
          }).then(function (res) {
            var ct = res.headers.get('content-type') || '';
            if (ct.indexOf('application/json') !== -1) {
              return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
              });
            }
            return res.text().then(function () {
              return { ok: false, status: res.status, data: {} };
            });
          });
        }

        $toggle.on('click', function () {
          var $t = $(this);
          var wasActive = $t.hasClass('active');
          $t.toggleClass('active');
          syncToggleUi();
          var isActive = $t.hasClass('active');
          $t.addClass('opacity-60 pointer-events-none');

          saveWhatsIncluded({ is_active: isActive }).then(function (result) {
            if (result.ok && result.data && result.data.success) {
              if (typeof showSaveToast === 'function') {
                showSaveToast();
              }
            } else {
              if (wasActive) {
                $t.addClass('active');
              } else {
                $t.removeClass('active');
              }
              syncToggleUi();
              showWhatsIncludedStatus(
                (result.data && result.data.message) || 'Could not update visibility. Please try again.',
                true
              );
            }
          }).catch(function () {
            if (wasActive) {
              $t.addClass('active');
            } else {
              $t.removeClass('active');
            }
            syncToggleUi();
            showWhatsIncludedStatus('Could not update visibility. Please try again.', true);
          }).finally(function () {
            $t.removeClass('opacity-60 pointer-events-none');
          });
        });

        $('#btnSaveWhatsIncluded').on('click', function () {
          var items = collectItems();
          var isActive = $toggle.hasClass('active');
          var $btn = $(this);
          var btnHtml = $btn.html();
          $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-[16px] animate-pulse">hourglass_empty</span> Saving…');

          saveWhatsIncluded({ is_active: isActive, items: items }).then(function (result) {
            if (result.ok && result.data && result.data.success) {
              showWhatsIncludedStatus(result.data.message || 'Saved.', false);
            } else {
              showWhatsIncludedStatus(
                (result.data && result.data.message) || 'Could not save. Please try again.',
                true
              );
            }
          }).catch(function () {
            showWhatsIncludedStatus('Could not save. Please try again.', true);
          }).finally(function () {
            $btn.prop('disabled', false).html(btnHtml);
          });
        });

        syncAddButton();
        syncPresetButtons();
        syncToggleUi();
      })();
    });
  </script>
@endsection