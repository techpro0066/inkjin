@extends('layouts.new-artist-dashboard-layout')

@section('title', 'My Page')

@section('styles')
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: #F3E8FF;
      color: #3E007C;
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
      display: inline-flex;
      align-items: center;
      padding: 3px 9px;
      border-radius: 20px;
      background: var(--pril, #F3E8FF);
      color: #7A1FC4;
      font-size: 11.5px;
      font-weight: 600;
      letter-spacing: 0;
      text-transform: none;
      line-height: 1.2;
    }
    .pricing-accordion-badge:before { display: none; }
    .pricing-accordion-subtitle {
      margin-top: 0.15rem;
      font-size: 12.5px;
      color: var(--faint, #9A929E);
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
    .smart-pricing-empty { display: none !important; }
    .prcadd-btn {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      border: 0;
      background: none;
      font: inherit;
      font-weight: 700;
      font-size: 13px;
      color: #3E007C;
      cursor: pointer;
      padding: 6px 0;
    }
    .prcadd-btn .ms { font-size: 18px; }
    .prcadd-btn:hover { opacity: .85; }
    #btnSaveSmartPricing.btn .ms { font-size: 18px; }
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

    /* Image crop modal must sit above New design (z-index 500) */
    .crop-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 600;
      background: rgba(0, 0, 0, 0.78);
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .crop-modal-backdrop.is-open { display: flex; }
    .crop-modal-inner {
      width: 100%; max-width: 560px; max-height: min(92vh, 900px);
      display: flex; flex-direction: column;
      background: #fff; border-radius: 22px;
      box-shadow: 0 20px 60px rgba(0,0,0,.25); overflow: hidden;
    }
    #designCropModal .mh{
      padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;
      display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0
    }
    #designCropModal .mh h2{font-size:18px;font-weight:800;margin:0}
    #designCropModal .mx{
      border:0;background:none;cursor:pointer;color:var(--muted);
      width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center
    }
    #designCropModal .mx:hover{background:#F7F3F9;color:var(--ink)}
    #designCropModal .mf{
      display:flex;gap:10px;justify-content:flex-end;padding:16px 24px;
      background:#FCF6FE;border-top:1px solid #F0E4F5;flex-shrink:0
    }
    #designCropModal .mf .btn{min-width:120px;justify-content:center}
    .cropper-stage-wrap {
      flex: 1;
      min-height: 200px;
      max-height: min(58vh, 520px);
      background: #1a1a1a;
      border-radius: 12px;
      overflow: hidden;
      margin: 12px 16px;
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

    /* Design cards (07b dcard) */
    .mp-dg .chip { cursor: pointer; user-select: none; font: inherit; }
    .mp-dg .chip b { font-weight: 700; margin-left: 4px; opacity: .7; }
    .mp-dg .chip.on b { opacity: .85; }
    .mp-dg .dcard { cursor: grab; transition: opacity .15s, box-shadow .15s; overflow: hidden; }
    .mp-dg .dcard.sortable-ghost { opacity: .4; }
    .mp-dg .dcard.sortable-chosen { box-shadow: 0 0 0 2px var(--pri, #3E007C); }
    .mp-dg #designsGrid.sorted .dcard { cursor: default; }
    .mp-dg .dcard-img {
      position: relative;
      height: 210px;
      background: #EEE center/cover no-repeat;
    }
    .mp-dg .dcard-img .pill {
      position: absolute;
      top: 10px;
      left: 10px;
      background: #fff;
      z-index: 2;
    }
    .mp-dg .design-drag-handle {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 3;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 3px;
      border-radius: 8px;
      border: 0;
      background: #fff;
      color: var(--ink, #1A1A1A);
      cursor: grab;
      line-height: 1;
    }
    .mp-dg .design-drag-handle:active { cursor: grabbing; }
    .mp-dg .design-drag-handle .ms { font-size: 18px; }
    body.design-sort-locked .mp-dg .design-drag-handle {
      opacity: 0.45;
      cursor: not-allowed;
    }
    .mp-dg .dcard-body { padding: 14px 16px; }
    .mp-dg .dcard-meta {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 10px;
      margin-top: 10px;
      flex-wrap: wrap;
    }
    .mp-dg .dcard-meta > b { font-size: 13.5px; }
    .mp-dg .dcard-meta .muted { font-size: 12.5px; white-space: nowrap; }
    .mp-dg .dcard-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #F3EEF4;
    }
    .mp-dg .dcard-foot .pic {
      width: 34px;
      height: 34px;
      border-radius: 9px;
      border: 0;
      background: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--faint, #9A929E);
      padding: 0;
    }
    .mp-dg .dcard-foot .pic:hover { background: #F7F3F9; color: var(--ink, #1A1A1A); }
    .mp-dg .dcard-foot .pic .ms { font-size: 20px; }
    .mp-dg .dcard-foot .pic.pdel:hover { background: #FDECEC; color: #C62828; }
    .mp-dg .dg-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }
    .mp-dg .dg-tools { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .mp-dg .dg-search { width: 220px; max-width: 100%; position: relative; }
    .mp-dg .dg-search .ms {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: var(--faint, #9A929E);
      pointer-events: none;
      z-index: 1;
    }
    /* Override Tailwind forms plugin so .in matches Bookpay template */
    .mp-dg input.in:not([type="number"]),
    .mp-dg select.in,
    #searchDesigns.in,
    #sortDesigns.in {
      -webkit-appearance: none !important;
      appearance: none !important;
    }
    .mp-dg input.in,
    .mp-dg select.in,
    #smartColorPercent.in,
    #searchDesigns.in,
    #sortDesigns.in {
      background-color: #fff !important;
      border: 1px solid var(--line, #E8DFEA) !important;
      border-radius: 10px !important;
      padding: 10px 13px !important;
      font-family: inherit !important;
      font-size: 13.5px !important;
      font-weight: 400 !important;
      line-height: normal !important;
      min-height: 40px !important;
      height: 40px !important;
      color: var(--ink, #1A1A1A) !important;
      box-shadow: none !important;
      outline: none !important;
      margin: 0 !important;
    }
    .mp-dg input.in:focus,
    .mp-dg select.in:focus,
    #smartColorPercent.in:focus,
    #searchDesigns.in:focus,
    #sortDesigns.in:focus {
      border-color: #3E007C !important;
      box-shadow: 0 0 0 3px #F3E8FF !important;
    }
    #smartColorPercent.in {
      width: 80px !important;
      max-width: 80px !important;
      text-align: center !important;
      padding: 6px 8px !important;
    }
    #searchDesigns.in {
      width: 100% !important;
      padding-left: 40px !important;
    }
    #searchDesigns.in::placeholder {
      color: var(--faint, #9A929E) !important;
      opacity: 1 !important;
      font-weight: 400 !important;
    }
    .mp-dg .ordwrap { position: relative; width: 170px; }
    .mp-dg .ordwrap select.in {
      width: 100% !important;
      cursor: pointer;
      padding-right: 36px !important;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24'%3E%3Cpath fill='%239A929E' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") !important;
      background-repeat: no-repeat !important;
      background-position: right 10px center !important;
      background-color: #fff !important;
    }
    .mp-dg #designsGrid {
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    @media (max-width: 1100px) {
      .mp-dg #designsGrid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 800px) {
      .mp-dg #designsGrid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
      .mp-dg #designsGrid { grid-template-columns: minmax(0, 1fr); }
      .mp-dg .dg-search { width: 100%; }
      .mp-dg .ordwrap { width: 100%; }
    }

    /* Mobile overflow fixes */
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }

    .design-field-section.scroll-mt-6 { scroll-margin-top: 1.5rem; }

    /* New design upload: template ratio 4:5 */
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

    /* New design modal (C01 / C02) */
    #newDesignModal.nd-ov{z-index:500;background:rgba(20,10,30,.45);padding:16px;align-items:center}
    #newDesignModal .modal.xl{
      width:min(1040px,100%);max-width:100%;max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
      background:#fff;border-radius:22px;box-shadow:0 20px 60px rgba(0,0,0,.25);
      display:flex;flex-direction:column;overflow:hidden
    }
    #newDesignModal .mh{padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0}
    #newDesignModal .mh h2{font-size:18px;font-weight:800;margin:0}
    #newDesignModal .mx{border:0;background:none;cursor:pointer;color:var(--muted);width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center}
    #newDesignModal .mx:hover{background:#F7F3F9;color:var(--ink)}
    #newDesignModal .mb{padding:18px 24px;flex:1 1 auto;min-height:0;overflow-y:auto;overscroll-behavior:contain}
    #newDesignModal .mf{display:flex;gap:10px;justify-content:flex-end;padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-shrink:0}
    #newDesignModal .mf .btn{min-width:120px;justify-content:center}
    #newDesignModal .nd-grid{grid-template-columns:300px 1fr;gap:26px;align-items:start}
    #newDesignModal .nd-half{grid-template-columns:1fr 1fr;gap:12px}
    #newDesignModal .ai{display:inline-flex;align-items:center;gap:3px;background:#F3E8FF;color:#6A2BB8;border-radius:6px;font-size:10.5px;font-weight:700;padding:1px 6px;margin-left:6px;vertical-align:middle}
    #newDesignModal .ai .ms{font-size:12px}
    #newDesignModal .cnt2{margin-left:auto;background:#F0EDF2;border-radius:6px;font-size:10.5px;font-weight:700;padding:2px 7px;color:#6F6874}
    #newDesignModal .chip.style-chip{cursor:pointer;font:inherit}
    #newDesignModal .chip.style-chip.is-selected,#newDesignModal .chip.style-chip.sel{background:#F3E8FF;border-color:#3E007C;color:#3E007C}
    #newDesignModal .chip.style-chip:disabled{opacity:.42;cursor:not-allowed}
    #newDesignModal .chip.style-chip .style-chip-check{display:none}
    #newDesignModal .nd-banner{border:1px solid #F3C9C9;background:#FDECEC;color:#C62828;border-radius:12px;padding:10px 12px;font-size:12.5px;margin-bottom:14px}
    #newDesignModal .design-field-error{color:#C62828;font-size:12px;margin-top:6px}
    #newDesignModal .nd-img{border-radius:14px;overflow:hidden;position:relative;cursor:pointer}
    #newDesignModal .nd-img.design-image-upload--empty{border:1.5px dashed var(--line);background:#FAF7FB;aspect-ratio:4/5;display:flex;align-items:center;justify-content:center}
    #newDesignModal .nd-img.design-image-upload--has-image{border:1px solid var(--line);aspect-ratio:auto;width:100%;max-width:300px}
    #newDesignModal .nd-img-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:24px;text-align:center}
    #newDesignModal .nd-img-empty .ms{font-size:36px;color:var(--faint)}
    #newDesignModal .nd-img-empty b{font-size:14px}
    #newDesignModal .nd-img-frame{aspect-ratio:4/5;background:#EEE;position:relative;overflow:hidden;border-radius:14px}
    #newDesignModal .nd-img-frame img{width:100%;height:100%;object-fit:cover;display:block}
    #newDesignModal .nd-ai-pill{position:absolute;left:10px;bottom:10px;background:#fff;color:#6A2BB8;z-index:2}
    #newDesignModal .nd-ai-pill .ms{font-size:14px}
    #newDesignModal .nd-img-meta{justify-content:space-between;margin-top:10px}
    #newDesignModal .nd-replace{border:0;background:none;font:inherit;font-size:12.5px;font-weight:600;color:var(--ink);cursor:pointer;padding:0}
    #newDesignModal .nd-replace:hover{color:var(--pri)}
    #newDesignModal .sect{border:1px solid var(--line);border-radius:14px;padding:14px 16px;background:#fff}
    #newDesignModal .sect.smart{border-color:#D9C4F2;background:#FBF7FF}
    #newDesignModal .sect .lnk{font-size:12.5px;font-weight:600;color:var(--ink);cursor:pointer;border:0;background:none;padding:0;font:inherit}
    #newDesignModal .sect .lnk:hover{color:var(--pri)}
    #newDesignModal .smart-price-lock{background:#F5F2F6;font-weight:700}
    #newDesignModal .smart-price-lock .ms{margin-left:auto;font-size:18px;color:var(--faint)}
    #newDesignModal .hidden{display:none!important}
    #newDesignModal .tog.toggle-switch{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer}
    #newDesignModal .tog.toggle-switch::after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s;transform:none}
    #newDesignModal .tog.toggle-switch.active,#newDesignModal .tog.toggle-switch.on{background:var(--pri)}
    #newDesignModal .tog.toggle-switch.active::after,#newDesignModal .tog.toggle-switch.on::after{left:19px;transform:none}
    /* Match Bookpay .in chrome inside modal (outside .mp-dg; Tailwind forms flattens radius) */
    #newDesignModal .in,
    #newDesignModal input.in,
    #newDesignModal select.in,
    #newDesignModal textarea.in{
      background-color:#fff!important;
      border:1px solid var(--line,#E8DFEA)!important;
      border-radius:10px!important;
      box-shadow:none!important;
      outline:none!important
    }
    #newDesignModal input.in,
    #newDesignModal select.in{
      display:block;min-height:40px;height:40px;padding:10px 13px!important;
      font-family:inherit!important;font-size:13.5px!important;font-weight:400!important;
      line-height:normal!important;color:var(--ink,#1A1A1A)!important;margin:0!important;
      -webkit-appearance:none!important;appearance:none!important
    }
    #newDesignModal select.in{appearance:auto!important;-webkit-appearance:menulist!important}
    #newDesignModal textarea.in{display:block;min-height:58px;height:auto;line-height:1.45;padding:10px 13px!important}
    #newDesignModal input.in:focus,
    #newDesignModal select.in:focus,
    #newDesignModal textarea.in:focus,
    #newDesignModal .in:focus-within{
      border-color:#3E007C!important;
      box-shadow:0 0 0 3px #F3E8FF!important
    }
    /* Nested inputs inside .in wrappers (price range / size) — kill Tailwind forms chrome */
    #newDesignModal .in > input,
    #newDesignModal .in > input[type="number"],
    #newDesignModal .in > input[type="text"]{
      border:0!important;outline:none!important;box-shadow:none!important;
      background:transparent!important;padding:0!important;margin:0!important;
      min-height:0!important;height:auto!important;width:100%;min-width:0;
      font:inherit;font-size:inherit;font-weight:inherit;line-height:inherit;color:inherit;
      border-radius:0!important;-webkit-appearance:none!important;appearance:textfield!important
    }
    #newDesignModal .in > input:focus,
    #newDesignModal .in > input:focus-visible{
      border:0!important;outline:none!important;box-shadow:none!important
    }
    #newDesignModal .in > input[type="number"]::-webkit-inner-spin-button,
    #newDesignModal .in > input[type="number"]::-webkit-outer-spin-button{
      -webkit-appearance:none!important;margin:0
    }
    #newDesignModal .nd-price-row{align-items:center;gap:6px}
    #newDesignModal .nd-price-row > .faint{flex-shrink:0;line-height:1;padding:0 2px}
    #newDesignModal .select2-container{width:100%!important;z-index:1}
    .select2-container--open{z-index:10060!important}
    #newDesignModal .select2-container--default .select2-selection--single{
      min-height:40px;padding:4px 8px;border-radius:10px;border:1px solid var(--line)!important;background:#fff!important
    }
    #newDesignModal .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:30px;padding-left:4px;font-size:13.5px;color:var(--ink)}
    #newDesignModal .select2-container--default .select2-selection--single .select2-selection__arrow{height:38px}
    @media (max-width:900px){
      #newDesignModal .nd-grid{grid-template-columns:1fr!important}
      #newDesignModal .nd-img.design-image-upload--empty,#newDesignModal .nd-img.design-image-upload--has-image{max-width:200px;margin:0 auto}
      #newDesignModal .nd-half{grid-template-columns:1fr!important}
      #newDesignModal .mf{flex-wrap:wrap}
      #newDesignModal .mf .btn{flex:1}
    }

    /* My Page designs chrome */
    .mp-dg .tog{width:38px;height:22px;border-radius:12px;background:#D9D2DD;position:relative;flex-shrink:0;cursor:pointer;border:0}
    .mp-dg .tog:after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:left .15s}
    .mp-dg .tog.on{background:var(--pri)}.mp-dg .tog.on:after{left:19px}
    .mp-dg .tog:disabled{opacity:.55;cursor:not-allowed}
    .mp-dg .ql{position:relative}
    .mp-dg .ql-b{display:inline-flex;align-items:center;gap:8px;font:inherit;font-weight:600;font-size:13.5px;padding:9px 12px 9px 13px;border-radius:12px;border:1px solid var(--line);background:#fff;color:var(--ink);cursor:pointer;white-space:nowrap}
    .mp-dg .ql-b:hover{background:#F7F3F9}
    .mp-dg .ql-b .ms{font-size:18px}.mp-dg .ql-b .cv{font-size:20px}
    .mp-dg .ql-p{position:absolute;right:0;top:calc(100% + 8px);width:min(400px,calc(100vw - 32px));background:#fff;border:1px solid #EAE2EE;border-radius:16px;box-shadow:0 18px 44px rgba(20,0,40,.18);padding:6px;z-index:80}
    .mp-dg .ql-p[hidden]{display:none}
    .mp-dg .ql-r{display:flex;align-items:center;gap:6px;padding:4px 4px 4px 12px;border-radius:10px}
    .mp-dg .ql-r:hover{background:#F7F3F9}
    .mp-dg .ql-u{flex:1;min-width:0;font-size:13.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .mp-dg .ql-u b{font-weight:700}
    .mp-dg .ql-i{width:34px;height:34px;border-radius:9px;border:0;background:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ink);flex-shrink:0;text-decoration:none}
    .mp-dg .ql-i:hover{background:#EFE7F3}
    .mp-dg .ql-i .ms{font-size:18px}.mp-dg .ql-i.done .ms{color:#1F8A4C}
    .mp-dg .mp-toast{position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));transition:opacity .25s,transform .35s;pointer-events:none}
    .mp-dg .mp-toast.on{opacity:1;transform:none}
    .mp-dg .mp-toast .ms{color:#3DD68C;font-size:20px}
    .mp-dg .secsw .ic{width:38px;height:38px;border-radius:10px;background:var(--chip);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .mp-dg .secsw .ic .ms{font-size:20px}
    @media(max-width:600px){.mp-dg .mp-toast{left:16px;right:16px;top:74px}.mp-dg .ql-p{right:auto;left:0}}

</style>
@endsection

@section('content')
@php
  $userDetail = $userDetail ?? auth()->user()?->userDetail;
  $username = trim((string) ($userDetail?->user_name ?? ''));
  $publicPageUrl = $username !== '' ? url('/@'.$username) : null;
  $publicPageHost = 'inkjin.com/@'.($username !== '' ? $username : 'username');
  $quickLinkRows = $username !== '' ? [
    ['suffix' => '', 'label' => $publicPageHost],
    ['suffix' => '#designs', 'label' => $publicPageHost.'#designs'],
    ['suffix' => '#portfolio', 'label' => $publicPageHost.'#portfolio'],
    ['suffix' => '#guest-spots', 'label' => $publicPageHost.'#guest-spots'],
    ['suffix' => '#policies', 'label' => $publicPageHost.'#policies'],
    ['suffix' => '#faq', 'label' => $publicPageHost.'#faq'],
  ] : [];
  $displayDesigns = (bool) ($displayDesigns ?? ($userDetail?->display_designs ?? true));
      @endphp
<div class="mp-dg">
  <div class="head">
          <div>
      <h1>
        My Page
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201266-dashboard-my-page-designs" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Everything clients see on your booking page.</div>
          </div>
    <div class="btns">
      @if ($publicPageUrl)
        <div class="ql" id="quickLinks">
          <button type="button" class="ql-b" id="quickLinksBtn" aria-haspopup="true" aria-expanded="false" aria-controls="quickLinksMenu">
            <span class="ms">link</span>Quick links<span class="ms cv">expand_more</span>
          </button>
          <div class="ql-p" id="quickLinksMenu" hidden role="menu" aria-label="Quick links">
            @foreach ($quickLinkRows as $row)
              <div class="ql-r" role="none">
                <div class="ql-u" title="{{ $row['label'] }}">
                  {{ $publicPageHost }}@if($row['suffix'] !== '')<b>{{ $row['suffix'] }}</b>@endif
                </div>
                <button type="button" class="ql-i" data-ql-copy="{{ $publicPageUrl.$row['suffix'] }}" data-ql-label="{{ $row['label'] }}" role="menuitem" title="Copy link">
                  <span class="ms">content_copy</span>
                </button>
                <a class="ql-i" href="{{ $publicPageUrl.$row['suffix'] }}" target="_blank" rel="noopener" role="menuitem" title="Open">
                  <span class="ms">open_in_new</span>
                </a>
              </div>
            @endforeach
          </div>
        </div>
        <a class="btn" href="{{ $publicPageUrl }}#designs" target="_blank" rel="noopener">
          <span class="ms">open_in_new</span>Open my page
          </a>
          @endif
        </div>
      </div>

  @include('artist.partials.my-page-tabs', [
    'myPageTab' => 'designs',
    'designsCount' => $designsCount ?? $artistDesigns->count(),
    'portfolioCount' => $portfolioCount ?? 0,
    'faqCount' => $faqCount ?? 0,
  ])

  @if (session('success'))
    <div class="note" style="margin-bottom:14px">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="note" style="margin-bottom:14px;border-color:#F3C9C9;background:#FDECEC;color:#C62828">{{ session('error') }}</div>
  @endif

  <div class="card secsw" style="display:flex;align-items:center;gap:14px;padding:14px 18px;margin-bottom:16px">
    <div class="ic"><span class="ms">visibility</span></div>
    <div style="flex:1">
      <b style="font-size:14.5px">Show Available designs on my page</b>
      <div class="faint secsub" id="displayDesignsSub" style="font-size:12.5px;margin-top:2px">
        {{ $displayDesigns
          ? 'Clients see the Available designs tab on your booking page.'
          : 'Hidden. Clients don’t see the Available designs tab. Your designs stay saved here.' }}
      </div>
    </div>
    <button type="button" class="tog {{ $displayDesigns ? 'on' : '' }}" id="displayDesignsTog" data-own="1" role="switch" aria-checked="{{ $displayDesigns ? 'true' : 'false' }}" aria-label="Show Available designs on my page"></button>
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
        $smartColorPercentLabel = rtrim(rtrim(number_format((float) ($smartPricingColorPercent ?? 20), 2, '.', ''), '0'), '.');
        $pricingClosedSummary = 'Choose how price, sessions and duration are set for new designs.';
        $pricingClosedBadge = $currentPricingType === 'smart' ? 'Smart pricing' : 'Manual pricing';
      @endphp
      <div
        class="card pricing-accordion"
        id="pricingSettingsPanel"
        data-pricing-type="{{ $currentPricingType }}"
        data-open="false"
        style="margin-bottom:16px"
      >
        <button
          type="button"
          id="pricingAccordionTrigger"
          class="pricing-accordion-trigger"
          aria-expanded="false"
          aria-controls="pricingAccordionBody"
        >
          <span class="pricing-accordion-icon" aria-hidden="true">
            <span class="ms">bolt</span>
          </span>
          <span class="pricing-accordion-copy">
            <span class="pricing-accordion-title-row">
              <span class="pricing-accordion-title" style="font-size:15px">Pricing</span>
              <span id="pricingAccordionBadge" class="pricing-accordion-badge">{{ $pricingClosedBadge }}</span>
            </span>
            <span id="pricingAccordionSubtitle" class="pricing-accordion-subtitle">{{ $pricingClosedSummary }}</span>
          </span>
          <span class="ms pricing-accordion-chevron" aria-hidden="true">expand_more</span>
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
                  <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Set price, sessions and duration yourself per design.</p>
          </div>
                <span class="pricing-choice-radio" aria-hidden="true"></span>
          </div>
            </button>
        </div>

          <p class="mt-3 text-xs text-on-surface-variant flex items-start gap-1.5">
            <span class="material-symbols-outlined text-[16px] mt-px text-on-surface-variant/80">info</span>
            <span>Switch anytime. Designs you've already uploaded keep their existing price, sessions and duration.</span>
          </p>

          <div id="smartPricingPanel" class="mt-5 tint {{ $currentPricingType === 'smart' ? '' : 'hidden' }}" style="padding:18px">
            <b style="font-size:14.5px;display:block">Size ranges</b>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Longest dimension, in {{ $pricingUnit }}. Edit the boundaries or the values.</div>

            <div class="smart-pricing-table-wrap" style="margin-top:12px">
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
              <p id="smartPricingEmpty" class="smart-pricing-empty" hidden>No size ranges yet. Add a size range to get started.</p>
              <p id="smartPricingRangesError" class="hidden mt-2 text-xs font-semibold text-[#b3261e]"></p>
            </div>

            <div class="row" style="gap:16px;margin-top:10px">
              <button type="button" id="btnAddSmartSizeRange" class="prcadd-btn">
                <span class="ms">add</span>Size range
          </button>
              <button type="button" id="btnAddSmartMoreThanRange" class="prcadd-btn">
                <span class="ms">add</span>Size larger than
              </button>
            </div>
            <div class="help" style="margin-top:8px">
              Example: a 5 {{ $pricingUnit }} design belongs to &ldquo;5–10,&rdquo; not &ldquo;0–5&rdquo;; a 10 {{ $pricingUnit }} design belongs to &ldquo;10–20,&rdquo; not &ldquo;5–10&rdquo;.
            </div>

            <div style="border-top:1px solid #EFE6F3;margin:16px 0"></div>

            <b style="font-size:14.5px;display:block">Color adjustment</b>
            <div class="faint" style="font-size:12.5px;margin-top:2px">Added on top of the table price for color designs</div>
            <div id="smartColorPercentField" class="row" style="gap:8px;margin-top:10px;flex-wrap:wrap">
              <span style="font-size:13.5px">Color designs</span>
              <input type="text" inputmode="decimal" id="smartColorPercent" class="in" value="{{ $smartColorPercentLabel }}" aria-label="Color percent more" placeholder="10">
              <span class="faint">% more</span>
          </div>
            <p id="smartPricingColorExample" class="help" style="margin-top:8px">
              Example: If the price for a 20+ {{ $pricingUnit }} black &amp; grey design is {{ $pricingCurrencySymbol }}450–{{ $pricingCurrencySymbol }}750, a color design at the same size would be {{ $pricingCurrencySymbol }}540–{{ $pricingCurrencySymbol }}900 (20% more).
            </p>

            <div class="row" style="gap:12px;margin-top:16px;padding-top:16px;border-top:1px solid #EFE6F3">
              <button type="button" id="btnSaveSmartPricing" class="btn">
                <span class="ms">save</span>Save changes
            </button>
              <p id="smartPricingSaveStatus" class="hidden text-sm font-medium"></p>
            </div>
          </div>
        </div>
      </div>

      @php
        $designFilterAll = $artistDesigns->count();
        $designFilterSold = $artistDesigns->filter(fn ($d) => $d->isSoldOut())->count();
        $designFilterAvailable = $artistDesigns->filter(fn ($d) => $d->is_active && ! $d->isSoldOut())->count();
        $designFilterUnavailable = $artistDesigns->filter(fn ($d) => ! $d->is_active && ! $d->isSoldOut())->count();
      @endphp

      <div class="dg-toolbar">
        <div class="chips" id="designFilterPills" role="tablist" aria-label="Filter designs">
          <button type="button" class="chip on" data-filter="all">All <b id="filterCountAll">{{ $designFilterAll }}</b></button>
          <button type="button" class="chip" data-filter="available">Available <b id="filterCountAvailable">{{ $designFilterAvailable }}</b></button>
          <button type="button" class="chip" data-filter="sold">Sold <b id="filterCountSold">{{ $designFilterSold }}</b></button>
          <button type="button" class="chip" data-filter="unavailable">Unavailable <b id="filterCountUnavailable">{{ $designFilterUnavailable }}</b></button>
        </div>
        <div class="dg-tools">
          <div class="dg-search">
            <span class="ms">search</span>
            <input type="text" id="searchDesigns" name="searchDesigns" class="in" placeholder="Search designs" autocomplete="off">
          </div>
          <div class="ordwrap">
            <select id="sortDesigns" name="sortDesigns" class="in" aria-label="Sort designs">
              <option value="custom" selected>Your order</option>
            <option value="newest">Newest</option>
            <option value="price-high">Price High–Low</option>
          </select>
          </div>
          <button type="button" id="btnOpenNewDesign" class="btn"><span class="ms">add</span>New design</button>
        </div>
      </div>

      <div id="designsGrid" class="grid">
        @forelse ($artistDesigns as $design)
        @php
          $sessionsCount = max(1, (int) ($design->max_sessions ?? 1));
          $sessionsFoot = $sessionsCount === 1 ? '1 session' : $sessionsCount.' sessions';
          $colorLabel = match ($design->color) {
              'color' => 'Color',
              'black-grey' => 'Black & Grey',
              'both' => 'Both',
              default => (string) $design->color,
          };
          $styleLabel = ucwords(str_replace('-', ' ', (string) $design->primary_style));
          $sizeUnitLabel = in_array(($sizeUnit ?? 'cm'), ['cm', 'in'], true) ? ($sizeUnit ?? 'cm') : 'cm';
          $sizeNum = (int) ($design->min_size ?? 0);
          if ($sizeNum <= 0) {
              $sizeNum = (int) ($design->max_size ?? 0);
          }
          $sizeFoot = $sizeNum > 0 ? 'min '.$sizeNum.' '.$sizeUnitLabel : '—';
          $tagsForSearch = is_array($design->tags) ? implode(' ', $design->tags) : '';
          $searchBlob = strtolower(
              $design->title.' '.
              $design->description.' '.
              $tagsForSearch.' '.
              $styleLabel.' '.
              $colorLabel
          );
          $canDeleteDesign = ($design->booking_requests_count ?? 0) === 0 && ($design->bookings_count ?? 0) === 0;
          $isSoldOut = $design->isSoldOut();
          if ($isSoldOut) {
              $statusLabel = 'Sold';
              $statusPill = 'r';
          } elseif ($design->is_active) {
              $statusLabel = 'Available';
              $statusPill = 'g';
          } else {
              $statusLabel = 'Unavailable';
              $statusPill = 'k';
          }
        @endphp
        <div
          class="card dcard design-card-wrap"
          data-design-id="{{ $design->id }}"
          data-is-active="{{ $design->is_active ? '1' : '0' }}"
          data-is-sold-out="{{ $isSoldOut ? '1' : '0' }}"
          data-created="{{ $design->created_at->getTimestamp() }}"
          data-sort-order="{{ (int) ($design->sort_order ?? 0) }}"
          data-max-price="{{ (int) $design->max_price }}"
          data-search="{{ e($searchBlob) }}"
        >
          <div class="dcard-img" style="background-image:url('{{ asset($design->image) }}')">
            <span class="pill {{ $statusPill }} design-status-pill">{{ $statusLabel }}</span>
            <button type="button" class="design-drag-handle" title="Drag to reorder" aria-label="Drag to reorder">
              <span class="ms">drag_indicator</span>
            </button>
          </div>
          <div class="dcard-body">
            <b style="display:block;font-size:14.5px">{{ $design->title }}</b>
            <div class="faint" style="font-size:12px;margin-top:2px">{{ $styleLabel }} · {{ $colorLabel }}</div>
            <div class="dcard-meta">
              <b>{{ $pricingCurrencySymbol }}{{ (int) $design->min_price }} – {{ $pricingCurrencySymbol }}{{ (int) $design->max_price }}</b>
              <span class="muted">{{ $sizeFoot }} · {{ $sessionsFoot }}</span>
            </div>
            <div class="dcard-foot">
              <div class="row" style="gap:4px">
                <button type="button" class="pic btn-edit-design" title="Edit" aria-label="Edit" data-design-id="{{ $design->id }}">
                  <span class="ms">edit</span>
                </button>
                @if ($canDeleteDesign)
                  <button
                    type="button"
                    class="pic pdel btn-delete-design"
                    title="Delete"
                    aria-label="Delete"
                    data-delete-url="{{ route('artist-designs.destroy', $design) }}"
                    data-design-id="{{ $design->id }}"
                  ><span class="ms">delete</span></button>
            @endif
            </div>
              <div class="row" style="gap:8px">
                <span class="faint" style="font-size:12.5px;font-weight:600">Bookable</span>
                <div
                  class="tog btn-toggle-design-availability {{ $design->is_active ? 'on active' : '' }}"
                  role="switch"
                  aria-checked="{{ $design->is_active ? 'true' : 'false' }}"
                  title="Toggle bookable"
                  data-own="1"
                  data-toggle-url="{{ route('artist-designs.toggle-availability', $design) }}"
                  data-design-id="{{ $design->id }}"
                  style="cursor:pointer"
                ></div>
            </div>
          </div>
        </div>
        </div>
        @empty
        <div id="designsNoDesigns" class="card" style="grid-column:1/-1;padding:56px 24px;text-align:center">
          <span class="ms" style="font-size:40px;color:var(--faint)">palette</span>
          <p style="font-weight:700;margin-top:8px">No designs yet</p>
          <p class="faint" style="font-size:12.5px;margin-top:4px;max-width:320px;margin-left:auto;margin-right:auto">Create a design with <b>New design</b> — it will show up here after you save.</p>
        </div>
        @endforelse
        <div id="designsFilterEmpty" class="card hidden" style="grid-column:1/-1;padding:56px 24px;text-align:center">
          <span class="ms" style="font-size:40px;color:var(--faint)">search_off</span>
          <p style="font-weight:700;margin-top:8px">No designs match</p>
          <p class="faint" style="font-size:12.5px;margin-top:4px">Try a different search, filter, or sort option.</p>
        </div>
      </div>

      @if ($artistDesigns->isNotEmpty())
        <div class="faint" id="designsDragHint" style="font-size:12.5px;margin-top:12px">
          <span class="ms" style="font-size:16px;vertical-align:-3px">drag_indicator</span>
          Drag designs to change the order they appear on your page
    </div>
      @endif

  <div class="mp-toast" id="mpToast" role="status"><span class="ms">check_circle</span><span id="mpToastText">Saved</span></div>
</div>

  @php
    $modalPricingType = in_array(($pricingType ?? 'manual'), ['manual', 'smart'], true) ? ($pricingType ?? 'manual') : 'manual';
    $modalSizeUnit = in_array(($sizeUnit ?? 'cm'), ['cm', 'in'], true) ? ($sizeUnit ?? 'cm') : 'cm';
    $modalCurrency = $pricingCurrencySymbol ?? '€';
  @endphp

  <!-- New Design Modal (C01 / C02) -->
  <div class="modal-backdrop nd-ov" id="newDesignModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="designModalTitle">
    <div class="modal xl new-design-modal-inner">
      <div class="mh">
        <div>
          <h2 id="designModalTitle">New design</h2>
          <div class="muted" style="font-size:13.5px;margin-top:4px">Upload the image first. AI fills in what it can. You can edit everything.</div>
        </div>
        <button type="button" class="mx" id="btnCloseNewDesign" aria-label="Close"><span class="ms">close</span></button>
      </div>
      <div class="mb">
        <div id="designFormBanner" class="hidden nd-banner"></div>
        <div class="grid nd-grid">
          <div class="design-field-section" data-design-field="image">
            <div id="designImageUpload" class="nd-img design-image-upload-slot design-image-upload--empty" role="button" tabindex="0">
              <div id="designImageUploadEmpty" class="nd-img-empty">
                <span class="ms">cloud_upload</span>
                <b>Drop image here</b>
                <span class="faint">or click to browse · PNG, JPG up to 10MB</span>
              </div>
              <div id="designImageUploadPreview" class="hidden">
                <div class="nd-img-frame">
                  <img id="designImagePreviewImg" src="" alt="Design preview">
                  <span id="designAiFilledPill" class="pill nd nd-ai-pill" hidden>
                    <span class="ms">auto_awesome</span><span id="designAiFilledText">AI filled</span>
                  </span>
                </div>
              </div>
              <div class="design-ai-overlay" aria-live="polite">
                <span class="ms">auto_awesome</span>
                <p>Filling empty fields with AI…</p>
              </div>
            </div>
            <div class="row nd-img-meta">
              <span class="faint" style="font-size:12px">1080 × 1350 · 4:5</span>
              <button type="button" class="nd-replace" id="btnReplaceDesignImage">Upload image</button>
            </div>
            <input type="file" id="designImage" name="designImage" accept="image/*" class="hidden">
            <input type="hidden" id="designImageData" name="designImageData" value="">
            <p class="hidden design-field-error" data-error-for="image"></p>
          </div>

          <div>
            <div class="design-field-section" data-design-field="settings">
              <div class="row" style="gap:22px;flex-wrap:wrap">
                <div class="hidden">
                  <div id="toggleVisibility" class="tog toggle-switch active on"></div>
                </div>
                <div class="row" style="gap:8px">
                  <div id="toggleAvailable" class="tog toggle-switch active on" role="switch" aria-checked="true"></div>
                  <span style="font-size:13.5px;font-weight:600">Available</span>
                </div>
                <div class="row" style="gap:8px">
                  <div id="toggleRepeatable" class="tog toggle-switch" role="switch" aria-checked="false"></div>
                  <span style="font-size:13.5px;font-weight:600">Repeatable</span>
                </div>
                <div class="row" style="gap:8px">
                  <div id="toggleSensitive" class="tog toggle-switch" role="switch" aria-checked="false"></div>
                  <span style="font-size:13.5px;font-weight:600">Sensitive</span>
                </div>
              </div>
              <div id="repeatLimitField" class="design-field-section hidden" data-design-field="repeat_limit" style="margin-top:14px">
                <label class="fl" for="designRepeatLimit">How many times can this be booked? <span style="color:#C62828">*</span></label>
                <input class="in" type="number" id="designRepeatLimit" name="designRepeatLimit" min="1" max="999" step="1" placeholder="e.g. 3" style="max-width:160px">
                <div class="help">Total number of clients who can book this design before it shows as sold out.</div>
                <p class="hidden design-field-error" data-error-for="repeat_limit"></p>
              </div>
            </div>

            {{-- Manual pricing (C01) --}}
            <div id="designPricingManual" class="sect design-field-section {{ $modalPricingType === 'smart' ? 'hidden' : '' }}" data-design-field="min_price" style="margin-top:16px">
              <div class="row" style="justify-content:space-between">
                <div class="row" style="gap:8px"><span class="ms">edit</span><b>Manual pricing</b></div>
                <button type="button" class="lnk" id="btnChangePricingManual">Change in Pricing</button>
              </div>
              <div class="grid nd-half" style="margin-top:10px">
                <div>
                  <span class="fl">Price range</span>
                  <div class="row nd-price-row">
                    <div class="in" style="flex:1;min-width:0">
                      <span class="faint">{{ $modalCurrency }}</span>
                      <input type="number" id="designPriceMin" name="designPriceMin" placeholder="Min" min="0" inputmode="decimal">
                    </div>
                    <span class="faint" aria-hidden="true">–</span>
                    <div class="in" style="flex:1;min-width:0">
                      <span class="faint">{{ $modalCurrency }}</span>
                      <input type="number" id="designPriceMax" name="designPriceMax" placeholder="Max" min="0" inputmode="decimal">
                    </div>
                  </div>
                  <p class="hidden design-field-error" data-error-for="min_price"></p>
                  <p class="hidden design-field-error" data-error-for="max_price"></p>
                </div>
                <div id="designManualSizeSlot">
                  <div id="designSizeFieldWrap" class="design-field-section" data-design-field="min_size">
                    <span class="fl" id="designSizeLabel">Minimum size</span>
                    <div class="in">
                      <input type="number" id="size_min" name="size_min" placeholder="e.g. 10" min="1" inputmode="decimal">
                      <span class="faint" id="sizeUnitLabel" style="margin-left:auto">{{ $modalSizeUnit }}</span>
                    </div>
                    <div class="help hidden" id="designSizeHelp">Required: the price comes from the size</div>
                    <p class="hidden design-field-error" data-error-for="min_size"></p>
                  </div>
                </div>
              </div>
            </div>

            {{-- Smart pricing (C02) --}}
            <div id="designPricingSmart" class="sect smart design-field-section {{ $modalPricingType === 'smart' ? '' : 'hidden' }}" style="margin-top:16px">
              <div class="row" style="justify-content:space-between">
                <div class="row" style="gap:8px"><span class="ms" style="color:#3E007C">bolt</span><b>Smart pricing</b></div>
                <button type="button" class="lnk" id="btnChangePricingSmart">Change in Pricing</button>
              </div>
              <div class="grid nd-half" style="margin-top:10px">
                <div id="designSmartSizeSlot"></div>
                <div>
                  <span class="fl">Price</span>
                  <div class="in smart-price-lock" id="designSmartPriceDisplay" aria-live="polite">
                    <span id="designSmartPriceText">—</span>
                    <span class="ms">lock</span>
                  </div>
                  <div class="help">Calculated automatically</div>
                </div>
              </div>
            </div>

            <div class="grid nd-half">
              <div class="design-field-section" data-design-field="max_sessions">
                <div class="row" style="margin-top:16px;margin-bottom:6px">
                  <span class="fl" style="margin:0">Max sessions</span>
                </div>
                <input class="in" type="number" id="designSessionsMax" name="designSessionsMax" min="1" placeholder="1">
                <div class="help">Leave blank if it's done in one session</div>
                <p class="hidden design-field-error" data-error-for="max_sessions"></p>
              </div>
              <div class="design-field-section" data-design-field="session_duration">
                <div class="row" style="margin-top:16px;margin-bottom:6px">
                  <span class="fl" style="margin:0">Duration</span>
                </div>
                <select class="in" id="designSessionTime" name="designSessionTime">
                  <option value="">Select…</option>
                  <option value="30min">30 min</option>
                  <option value="1h">1 hour</option>
                  <option value="2h">2 hours</option>
                  <option value="3h">3 hours</option>
                  <option value="4h">4 hours</option>
                  <option value="5h">5 hours</option>
                  <option value="6h">6 hours</option>
                  <option value="7h">7 hours</option>
                  <option value="8h">8 hours</option>
                  <option value="half-day">Half day</option>
                  <option value="full-day">Full day</option>
                </select>
                <p class="hidden design-field-error" data-error-for="session_duration"></p>
              </div>
            </div>

            <div class="design-field-section" data-design-field="title" style="margin-top:16px">
              <div class="row" style="margin-bottom:6px">
                <label class="fl" for="designTitle" style="margin:0">Title<span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
              </div>
              <input class="in" type="text" id="designTitle" name="designTitle" placeholder="e.g. Baby dragon with rose" autocomplete="off">
              <p class="hidden design-field-error" data-error-for="title"></p>
            </div>

            <div class="design-field-section" data-design-field="description" style="margin-top:16px">
              <div class="row" style="margin-bottom:6px">
                <label class="fl" for="designDescription" style="margin:0">Description <span class="faint" style="font-weight:500">(optional)</span><span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
              </div>
              <textarea class="in" id="designDescription" name="designDescription" rows="3" placeholder="Describe this design…" style="min-height:58px;align-items:flex-start;resize:vertical"></textarea>
              <p class="hidden design-field-error" data-error-for="description"></p>
            </div>

            <div class="design-field-section" data-design-field="primary_style" style="margin-top:16px">
              <div class="row" style="margin-bottom:6px">
                <label class="fl" for="designPrimaryStyle" style="margin:0">Primary style<span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
              </div>
              <select id="designPrimaryStyle" name="designPrimaryStyle" class="in js-design-modal-select2">
                <option value="">Select style…</option>
                @foreach ($styles as $style)
                  <option value="{{ $style }}">{{ $style }}</option>
                @endforeach
              </select>
              <p class="hidden design-field-error" data-error-for="primary_style"></p>
            </div>

            <div class="design-field-section" data-design-field="other_styles" style="margin-top:16px">
              <div class="row" style="margin-bottom:6px">
                <span class="fl" style="margin:0">Other styles<span class="ai"><span class="ms">auto_awesome</span>AI</span></span>
                <span class="cnt2"><span id="designOtherStylesCount">0</span> / 2</span>
              </div>
              <div id="designOtherStylesChips" class="chips" role="group" aria-label="Other tattoo styles">
                @foreach ($styles as $style)
                  <button type="button" class="chip style-chip" data-value="{{ $style }}" aria-pressed="false"><span class="material-symbols-outlined style-chip-check">check</span>{{ $style }}</button>
                @endforeach
              </div>
              <select id="designOtherStyles" name="designOtherStyles" multiple class="hidden" tabindex="-1" aria-hidden="true">
                @foreach ($styles as $style)
                  <option value="{{ $style }}">{{ $style }}</option>
                @endforeach
              </select>
              <p class="hidden design-field-error" data-error-for="other_styles"></p>
            </div>

            <div class="design-field-section" data-design-field="suggested_placements" style="margin-top:16px">
              <div class="row" style="margin-bottom:6px">
                <span class="fl" style="margin:0">Placement</span>
                <span class="cnt2"><span id="designPlacementsCount">0</span> / 3</span>
              </div>
              <div id="designPlacementsChips" class="chips" role="group" aria-label="Suggested placements">
                @foreach (($placements ?? []) as $placement)
                  <button type="button" class="chip style-chip" data-value="{{ $placement }}" aria-pressed="false"><span class="material-symbols-outlined style-chip-check">check</span>{{ $placement }}</button>
                @endforeach
              </div>
              <select id="designSuggestedPlacements" name="designSuggestedPlacements" multiple class="hidden" tabindex="-1" aria-hidden="true">
                @foreach (($placements ?? []) as $placement)
                  <option value="{{ $placement }}">{{ $placement }}</option>
                @endforeach
              </select>
              <p class="hidden design-field-error" data-error-for="suggested_placements"></p>
            </div>

            <div class="grid nd-half">
              <div class="design-field-section" data-design-field="color">
                <div class="row" style="margin-top:16px;margin-bottom:6px">
                  <label class="fl" for="designColors" style="margin:0">Colors<span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
                </div>
                <select id="designColors" name="designColors" class="in js-design-modal-select2">
                  <option value="">Select…</option>
                  <option value="color">Color</option>
                  <option value="black-grey">Black & grey</option>
                  <option value="both">Both</option>
                </select>
                <p class="hidden design-field-error" data-error-for="color"></p>
              </div>
              <div class="design-field-section" data-design-field="tags">
                <div class="row" style="margin-top:16px;margin-bottom:6px">
                  <label class="fl" for="designTags" style="margin:0">Tags<span class="ai"><span class="ms">auto_awesome</span>AI</span></label>
                </div>
                <input class="in" type="text" id="designTags" name="designTags" placeholder="dragon, rose, traditional" style="font-size:13px">
                <p class="hidden design-field-error" data-error-for="tags"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="mf">
        <button type="button" id="btnCancelNewDesign" class="btn ghost">Cancel</button>
        <button type="button" id="btnSaveDesign" class="btn"><span class="ms">save</span>Save design</button>
      </div>
    </div>
  </div>

  <!-- Crop design image (4:5 → 1080×1350) -->
  <div id="designCropModal" class="crop-modal-backdrop" aria-hidden="true">
    <div class="crop-modal-inner">
      <div class="mh">
        <div>
          <h2>Crop image</h2>
          <div class="muted" style="font-size:13px;margin-top:4px">Output <b style="color:var(--ink);font-weight:700">1080 × 1350 px</b> · ratio 4:5</div>
        </div>
        <button type="button" class="mx" id="btnDesignCropClose" aria-label="Close cropper"><span class="ms">close</span></button>
      </div>
      <div class="cropper-stage-wrap">
        <img id="designCropperImg" src="" alt="" class="max-w-full">
      </div>
      <div class="mf">
        <button type="button" class="btn ghost" id="btnDesignCropCancel">Cancel</button>
        <button type="button" class="btn" id="btnDesignCropApply"><span class="ms">check</span>Apply crop</button>
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
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
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
    var PRICING_TYPE_UPDATE_URL = @json(route('artist-designs.pricing-type.update'));
    var SMART_PRICING_VALIDATE_URL = @json(route('artist-designs.smart-pricing.update'));
    var SMART_PRICING_INITIAL_RANGES = @json($smartPricingRanges ?? []);
    var SMART_PRICING_COLOR_PERCENT = @json((float) ($smartPricingColorPercent ?? 20));
    var ARTIST_DESIGNS_BY_ID = @json($designsForEdit);
    var ARTIST_DESIGN_STYLE_OPTIONS = @json($styles);
    var ARTIST_DESIGN_PLACEMENT_OPTIONS = @json($placements ?? []);
    var ARTIST_DESIGN_SIZE_UNIT = @json($sizeUnit ?? 'cm');
    $(function () {
      var DISPLAY_DESIGNS_URL = @json(route('personal-page.display-designs'));

      function mpToast(msg) {
        var $t = $('#mpToast');
        if (!$t.length) return;
        $('#mpToastText').text(msg);
        $t.addClass('on');
        clearTimeout($t.data('t'));
        $t.data('t', setTimeout(function () { $t.removeClass('on'); }, 2400));
      }

      var $ql = $('#quickLinks'), $qlBtn = $('#quickLinksBtn'), $qlMenu = $('#quickLinksMenu');
      if ($ql.length) {
        function openQl(show) {
          $qlMenu.prop('hidden', !show);
          $qlBtn.attr('aria-expanded', show ? 'true' : 'false');
          $qlBtn.find('.cv').text(show ? 'expand_less' : 'expand_more');
        }
        $qlBtn.on('click', function (e) { e.stopPropagation(); openQl($qlMenu.prop('hidden')); });
        $qlMenu.on('click', function (e) {
          e.stopPropagation();
          var $c = $(e.target).closest('[data-ql-copy]');
          if (!$c.length) return;
          var u = $c.attr('data-ql-copy') || '';
          try { navigator.clipboard.writeText(u); } catch (x) {}
          $c.addClass('done').find('.ms').text('check');
          setTimeout(function () { $c.removeClass('done').find('.ms').text('content_copy'); }, 1600);
          mpToast('Link copied');
        });
        $(document).on('click', function (e) {
          if (!$qlMenu.prop('hidden') && !$(e.target).closest('#quickLinks').length) openQl(false);
        });
      }

      $('#displayDesignsTog').on('click', function () {
        var $t = $(this);
        if ($t.prop('disabled')) return;
        var on = !$t.hasClass('on');
        $t.toggleClass('on', on).attr('aria-checked', on ? 'true' : 'false').prop('disabled', true);
        $('#displayDesignsSub').text(on
          ? 'Clients see the Available designs tab on your booking page.'
          : 'Hidden. Clients don’t see the Available designs tab. Your designs stay saved here.');
        $.ajax({
          url: DISPLAY_DESIGNS_URL,
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '', Accept: 'application/json' },
          data: { display_designs: on ? 1 : 0 },
          success: function (res) { mpToast(res.message || (on ? 'Designs shown on your page' : 'Designs hidden from your page')); },
          error: function () {
            $t.toggleClass('on', !on).attr('aria-checked', !on ? 'true' : 'false');
            $('#displayDesignsSub').text(!on
              ? 'Clients see the Available designs tab on your booking page.'
              : 'Hidden. Clients don’t see the Available designs tab. Your designs stay saved here.');
            mpToast('Could not update designs visibility');
          },
          complete: function () { $t.prop('disabled', false); }
        });
      });

      var MODAL_MS = 350;
      var $newDesignModal = $('#newDesignModal');
      var $designCropModal = $('#designCropModal');
      var $deleteDesignModal = $('#deleteDesignModal');
      var $designCropperImg = $('#designCropperImg');
      var designCropper = null;
      var pendingOriginalImageFile = null;
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
        var isSmartStored = String(storedPricingType || '') === 'smart';
        var $subtitle = $('#pricingAccordionSubtitle');
        var $badge = $('#pricingAccordionBadge');

        $panel.attr('data-pricing-type', isSmartStored ? 'smart' : 'manual');
        $badge.text(isSmartStored ? 'Smart pricing' : 'Manual pricing');
        $subtitle.text('Choose how price, sessions and duration are set for new designs.');
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
        if (typeof syncDesignModalPricingMode === 'function') {
          syncDesignModalPricingMode();
        }

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
          var typeLabel = String(storedPricingType) === 'smart' ? 'Smart pricing' : 'Manual pricing';
          mpToast((res && res.message) || ('Switched to ' + typeLabel));
        }).fail(function (xhr) {
          var previous = String($('#pricingSettingsPanel').attr('data-previous-pricing-type') || storedPricingType || 'manual');
          setPricingMode(previous, { persist: false });
          var data = (xhr && xhr.responseJSON) || {};
          var errMsg = (data.errors && data.errors.pricing_type && data.errors.pricing_type[0])
            || data.message
            || 'Could not update pricing type';
          mpToast(errMsg);
        }).always(function () {
          pricingTypeSaving = false;
          $('#pricingSettingsPanel .pricing-choice-card').prop('disabled', false);
        });
      }

      $('#pricingAccordionTrigger').on('click', function () {
        setPricingAccordionOpen(!$('#pricingSettingsPanel').hasClass('is-open'));
      });

      function syncSmartPricingEmptyState() {
        // Template always shows column headers; empty copy is unused.
        $('#smartPricingEmpty').addClass('hidden').prop('hidden', true);
        $('.smart-pricing-table thead').removeClass('hidden');
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
        var original = '<span class="ms">save</span>Save changes';
        $btn.prop('disabled', true).html('<span class="ms">progress_activity</span>Saving…');

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
          mpToast((res && res.message) || 'Switched to Smart pricing');
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
            mpToast((data && data.message) || 'Unable to save smart pricing.');
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
          { value: '5h', hours: 5 },
          { value: '6h', hours: 6 },
          { value: '7h', hours: 7 },
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
        $('#designSmartPriceText').text('—');
      }

      function syncDesignSmartPriceDisplay() {
        var minP = $.trim(String($('#designPriceMin').val() || ''));
        var maxP = $.trim(String($('#designPriceMax').val() || ''));
        if (minP === '' && maxP === '') {
          $('#designSmartPriceText').text('—');
          return;
        }
        $('#designSmartPriceText').text(SMART_PRICING_CURRENCY + minP + ' – ' + SMART_PRICING_CURRENCY + maxP);
      }

      function syncDesignModalPricingMode() {
        var smart = String(storedPricingType || '') === 'smart';
        $('#designPricingManual').toggleClass('hidden', smart);
        $('#designPricingSmart').toggleClass('hidden', !smart);
        var $wrap = $('#designSizeFieldWrap');
        if (smart) {
          $('#designSmartSizeSlot').append($wrap);
          $('#designSizeLabel').html('Size <span style="color:#C62828">*</span>');
          $('#designSizeHelp').removeClass('hidden');
          $('#size_min').closest('.in').css('border', '2px solid #3E007C');
        } else {
          $('#designManualSizeSlot').append($wrap);
          $('#designSizeLabel').text('Minimum size');
          $('#designSizeHelp').addClass('hidden');
          $('#size_min').closest('.in').css('border', '');
        }
        if (smart) {
          applySmartPricingToDesignForm();
        } else {
          setSmartPricingNoRangeError(false);
          syncDesignSmartPriceDisplay();
        }
      }

      function goToPricingSettingsFromDesignModal() {
        closeNewDesignModal();
        setPricingAccordionOpen(true);
        var el = document.getElementById('pricingSettingsPanel');
        if (el && el.scrollIntoView) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
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
        syncDesignSmartPriceDisplay();

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
      syncDesignModalPricingMode();
      $('#btnChangePricingManual, #btnChangePricingSmart').on('click', function (e) {
        e.preventDefault();
        goToPricingSettingsFromDesignModal();
      });
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
        if (typeof Cropper === 'undefined') {
          alert('Cropper failed to load. Refresh the page and try again.');
          return;
        }
        pendingOriginalImageFile = file;
        destroyDesignCropper();
        revokeDesignCropBlob();
        var url = URL.createObjectURL(file);
        $designCropperImg.data('blob-url', url);
        $designCropModal.addClass('is-open').attr('aria-hidden', 'false');

        function initCropperOnImg() {
          var img = $designCropperImg[0];
          if (!img) return;
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
        }

        var cropInitDone = false;
        function startCropperOnce() {
          if (cropInitDone) return;
          cropInitDone = true;
          $designCropperImg.off('load.designcrop error.designcrop');
          initCropperOnImg();
        }

        $designCropperImg.off('load.designcrop error.designcrop');
        $designCropperImg.on('load.designcrop', startCropperOnce);
        $designCropperImg.on('error.designcrop', function () {
          $designCropperImg.off('load.designcrop error.designcrop');
          closeDesignCropModal();
          alert('Could not open that image. Try another file.');
        });
        $designCropperImg.attr('src', url);
        var imgEl = $designCropperImg[0];
        if (imgEl && imgEl.complete && imgEl.naturalWidth > 0) {
          startCropperOnce();
        }
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
        $slot.removeClass('design-image-upload--empty').addClass('design-image-upload--has-image');
        $('#btnReplaceDesignImage').text('Replace image');
        $('#designAiFilledPill').prop('hidden', true);
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

        var filled = 0;
        if (emptyAtRequest.title && suggestions.title) filled++;
        if (emptyAtRequest.description && suggestions.description) filled++;
        if (emptyAtRequest.primary_style && suggestions.primary_style) filled++;
        if (emptyAtRequest.other_styles && Array.isArray(suggestions.other_styles) && suggestions.other_styles.length) filled++;
        if (emptyAtRequest.suggested_placements && Array.isArray(suggestions.suggested_placements) && suggestions.suggested_placements.length) filled++;
        if (emptyAtRequest.color && suggestions.color) filled++;
        if (emptyAtRequest.tags && Array.isArray(suggestions.tags) && suggestions.tags.length) filled++;
        if (filled > 0) {
          $('#designAiFilledText').text('AI filled ' + filled + ' field' + (filled === 1 ? '' : 's'));
          $('#designAiFilledPill').prop('hidden', false);
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
        pendingOriginalImageFile = null;
        $('#designImageData').val('');
        $('#designImagePreviewImg').attr('src', '');
        var slot = document.getElementById('designImageUpload');
        if (slot) {
          slot.classList.remove('has-preview', 'design-image-upload--has-image');
          slot.classList.add('design-image-upload--empty');
          slot.style.removeProperty('--design-preview-ar');
        }
        $('#designImageUploadEmpty').removeClass('hidden');
        $('#designImageUploadPreview').addClass('hidden');
        $('#btnReplaceDesignImage').text('Upload image');
        $('#designAiFilledPill').prop('hidden', true);
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
        setDesignToggleState($('#toggleVisibility'), !!d.is_visible);
        setDesignToggleState($('#toggleAvailable'), !!d.is_active);
        setDesignToggleState($('#toggleRepeatable'), !!d.is_repeatable);
        $('#designRepeatLimit').val(d.is_repeatable && d.repeat_limit != null ? d.repeat_limit : '');
        syncRepeatLimitFieldVisibility();
        setDesignToggleState($('#toggleSensitive'), !!d.is_sensitive);
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
        $slot.removeClass('design-image-upload--empty').addClass('design-image-upload--has-image');
        $('#btnReplaceDesignImage').text('Replace image');
        $('#designAiFilledPill').prop('hidden', true);
        $('#designImage').val('');
      }

      function openEditDesignModal(d) {
        if (!d || !d.id) {
          return;
        }
        clearDesignFormErrors();
        resetDesignImageState();
        $newDesignModal.data('editingDesignId', d.id);
        $('#designModalTitle').text('Edit design');
        $('#btnSaveDesign').html('<span class="ms">save</span>Save design');
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

      function setDesignToggleState($el, on) {
        $el.toggleClass('active', !!on).toggleClass('on', !!on).attr('aria-checked', on ? 'true' : 'false');
      }

      function resetNewDesignFormFields() {
        clearDesignFormErrors();
        $newDesignModal.data('editingDesignId', null);
        $('#designModalTitle').text('New design');
        $('#btnSaveDesign').html('<span class="ms">save</span>Save design');
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
        setDesignToggleState($('#toggleVisibility'), true);
        setDesignToggleState($('#toggleAvailable'), true);
        setDesignToggleState($('#toggleRepeatable'), false);
        setDesignToggleState($('#toggleSensitive'), false);
        $('#designRepeatLimit').val('');
        syncRepeatLimitFieldVisibility();
        $('#designOtherStyles option').prop('selected', false);
        syncDesignOtherStylesChipsFromSelect();
        $('#designSuggestedPlacements option').prop('selected', false);
        syncDesignPlacementsChipsFromSelect();
        suppressSmartPricingAutofill = false;
        syncDesignModalPricingMode();
        syncDesignSmartPriceDisplay();
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
        syncDesignModalPricingMode();
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
        if (pendingOriginalImageFile) {
          var originalName = pendingOriginalImageFile.name || 'design-original.jpg';
          fd.append('original_image', pendingOriginalImageFile, originalName);
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

      function designStatusFromCard($card) {
        var isSoldOut = String($card.attr('data-is-sold-out')) === '1';
        var isActive = String($card.attr('data-is-active')) === '1';
        if (isSoldOut) {
          return { label: 'Sold', pill: 'r' };
        }
        if (isActive) {
          return { label: 'Available', pill: 'g' };
        }
        return { label: 'Unavailable', pill: 'k' };
      }

      function updateDesignFilterCounts() {
        var all = 0, available = 0, sold = 0, unavailable = 0;
        $('#designsGrid .design-card-wrap').each(function () {
          var $c = $(this);
          var isSoldOut = String($c.attr('data-is-sold-out')) === '1';
          var isActive = String($c.attr('data-is-active')) === '1';
          all += 1;
          if (isSoldOut) {
            sold += 1;
          } else if (isActive) {
            available += 1;
          } else {
            unavailable += 1;
          }
        });
        $('#filterCountAll').text(all);
        $('#filterCountAvailable').text(available);
        $('#filterCountSold').text(sold);
        $('#filterCountUnavailable').text(unavailable);
      }

      function updateDesignCardAvailability($card, isActive) {
        $card.attr('data-is-active', isActive ? '1' : '0');
        $card.find('.btn-toggle-design-availability')
          .toggleClass('active', !!isActive)
          .toggleClass('on', !!isActive)
          .attr('aria-checked', isActive ? 'true' : 'false');
        var status = designStatusFromCard($card);
        var $pill = $card.find('.design-status-pill');
        $pill.removeClass('g r k').addClass(status.pill).text(status.label);
        updateDesignFilterCounts();
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
        var willBeActive = !($toggle.hasClass('active') || $toggle.hasClass('on'));
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
          var designId = $toggle.data('design-id');
          var $card = $toggle.closest('.design-card-wrap');
          updateDesignCardAvailability($card, isActive);
          if (designId && ARTIST_DESIGNS_BY_ID[designId]) {
            ARTIST_DESIGNS_BY_ID[designId].is_active = isActive;
          }
          applyDesignFilters();
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
        var on = !$(this).hasClass('active');
        setDesignToggleState($(this), on);
        if (this.id === 'toggleRepeatable') {
          syncRepeatLimitFieldVisibility();
        }
      });

      function triggerDesignImagePicker() {
        var input = document.getElementById('designImage');
        if (input) input.click();
      }

      $('#btnReplaceDesignImage').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        triggerDesignImagePicker();
      });

      $('#designImageUpload').on('click', function (e) {
        e.preventDefault();
        triggerDesignImagePicker();
      }).on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          triggerDesignImagePicker();
        }
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
        var $hint = $('#designsDragHint');
        if (!$wraps.length) {
          $filterEmpty.addClass('hidden');
          syncDesignDragLock();
          return;
        }
        var filter = $('#designFilterPills .chip.on').data('filter') || 'all';
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
          if (filter === 'sold' && !isSoldOut) {
            return;
          }
          if (filter === 'unavailable' && (isActive || isSoldOut)) {
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
        if ($hint.length) {
          if (sort === 'custom') {
            $hint.html('<span class="ms" style="font-size:16px;vertical-align:-3px">drag_indicator</span> Drag designs to change the order they appear on your page');
          } else {
            $hint.html('<span class="ms" style="font-size:16px;vertical-align:-3px">info</span> Sorted by ' + (sort === 'newest' ? 'newest' : 'price') + '. Switch to <b>Your order</b> to drag designs.');
          }
        }
      }

      function syncDesignDragLock() {
        var sort = $('#sortDesigns').val() || 'custom';
        var filter = $('#designFilterPills .chip.on').data('filter') || 'all';
        var q = ($('#searchDesigns').val() || '').trim();
        var canDrag = sort === 'custom' && filter === 'all' && !q;
        $('body').toggleClass('design-sort-locked', !canDrag);
        $('#designsGrid').toggleClass('sorted', !canDrag);
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

      $('#designFilterPills').on('click', '.chip', function () {
        var $btn = $(this);
        if ($btn.hasClass('on')) {
            return;
          }
        $('#designFilterPills .chip').removeClass('on');
        $btn.addClass('on');
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
    });
  </script>
@endsection