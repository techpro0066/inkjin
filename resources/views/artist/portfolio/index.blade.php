@extends('layouts.artist_dashboard_layout')

@section('title', 'Portfolio')

@section('styles')
@php
  $showInstagramUi = in_array(strtolower((string) (Auth::user()->email ?? '')), [
    'ilias@inkjin.com',
    'touseef132ahmad@gmail.com',
  ], true);
@endphp
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    /* Modal */
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
    #deletePortfolioModal.modal-backdrop { z-index: 400; }
    #disconnectInstagramModal.modal-backdrop { z-index: 400; }
    .add-work-modal-inner {
      transform: scale(0.96) translateY(10px);
      opacity: 0;
      transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
    }
    .modal-backdrop.modal-open .add-work-modal-inner {
      transform: scale(1) translateY(0);
      opacity: 1;
    }

    /* Toggle switch */
    .toggle-switch { position: relative; width: 44px; height: 24px; background: #cac4d3; border-radius: 12px; cursor: pointer; transition: background 0.2s; flex-shrink: 0; }
    .toggle-switch.active { background: #310f7a; }
    .toggle-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: white; border-radius: 50%; transition: transform 0.2s; }
    .toggle-switch.active::after { transform: translateX(20px); }

    .btn-connect-instagram {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.625rem 1.25rem;
      border-radius: 0.75rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: #fff;
      border: 0;
      box-shadow: 0 1px 2px rgba(193, 53, 132, 0.25);
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
      transition: transform 0.15s ease, filter 0.15s ease, box-shadow 0.15s ease;
      text-decoration: none;
    }
    .btn-connect-instagram:hover {
      filter: brightness(1.05);
      box-shadow: 0 4px 14px rgba(188, 24, 136, 0.35);
      color: #fff;
    }
    .btn-connect-instagram:active {
      transform: scale(0.98);
    }
    .btn-connect-instagram svg {
      width: 1.125rem;
      height: 1.125rem;
      flex-shrink: 0;
    }

    .instagram-status-card {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.25rem;
      background: #fff;
      border: 1px solid rgba(121, 116, 126, 0.2);
      border-radius: 1rem;
    }
    .instagram-status-icon {
      width: 2.75rem;
      height: 2.75rem;
      border-radius: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
      flex-shrink: 0;
    }
    .instagram-status-icon svg {
      width: 1.25rem;
      height: 1.25rem;
    }
    .instagram-status-actions {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.5rem;
      margin-left: auto;
    }
    .btn-ig-refresh {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      padding: 0.5rem 1rem;
      border-radius: 0.75rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: #1c1b1f;
      background: #fff;
      border: 1px solid rgba(121, 116, 126, 0.3);
      transition: background 0.15s ease;
    }
    .btn-ig-refresh:hover { background: #f7f2fa; }
    .btn-ig-disconnect {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.5rem 1rem;
      border-radius: 0.75rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: #b3261e;
      background: #fff;
      border: 1px solid rgba(121, 116, 126, 0.3);
      transition: background 0.15s ease;
    }
    .btn-ig-disconnect:hover { background: #fceeee; }

    body.ig-import-lock #mobileSidebar,
    body.ig-import-lock #mobileMenuBtn,
    body.ig-import-lock .booking-page-tabs,
    body.ig-import-lock .booking-page-tabs a,
    body.ig-import-lock #btnAddWork,
    body.ig-import-lock #btnConnectInstagram,
    body.ig-import-lock #btnInstagramRefresh,
    body.ig-import-lock #btnInstagramDisconnect,
    body.ig-import-lock .btn-toggle-portfolio-visibility,
    body.ig-import-lock .portfolio-drag-handle {
      pointer-events: none !important;
      opacity: 0.55;
    }
    body.ig-import-lock #mobileSidebar a,
    body.ig-import-lock #mobileSidebar button {
      pointer-events: none !important;
    }
    body.ig-import-lock #instagramImportModal {
      pointer-events: auto;
      opacity: 1;
    }

    #instagramImportModal .ig-progress-track {
      height: 8px;
      border-radius: 999px;
      background: #efe7f4;
      overflow: hidden;
    }
    #instagramImportModal .ig-progress-bar {
      height: 100%;
      width: 0%;
      border-radius: 999px;
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
      transition: width 0.25s ease;
    }
    #igImportSpinner.animate-spin {
      animation: ig-spin 1s linear infinite;
    }
    @keyframes ig-spin {
      to { transform: rotate(360deg); }
    }

    /* Toggle badge pills */
    .toggle-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 8px; font-size: 11px; font-weight: 600; transition: all 0.2s; cursor: pointer; user-select: none; }
    .toggle-badge.on { background: #e8ddff; color: #310f7a; }
    .toggle-badge.off { background: #f2ecf5; color: #7a7583; }
    .toggle-badge .material-symbols-outlined { font-size: 14px; }

    /* Info tag */
    .info-tag { background: #e8ddff; color: #310f7a; }

    /* Portfolio card */
    .portfolio-card { transition: all 0.15s ease; }
    .portfolio-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .portfolio-ig-badge {
      position: absolute;
      top: 0.75rem;
      left: 0.75rem;
      width: 2rem;
      height: 2rem;
      border-radius: 0.625rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
      box-shadow: 0 2px 8px rgba(0,0,0,0.25);
      z-index: 2;
    }
    .portfolio-ig-badge svg {
      width: 1rem;
      height: 1rem;
    }
    .portfolio-drag-handle {
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
    .portfolio-drag-handle:active { cursor: grabbing; }
    .portfolio-drag-handle .material-symbols-outlined { font-size: 1.125rem; }
    .portfolio-card.sortable-ghost { opacity: 0.45; }
    .portfolio-card.sortable-chosen { cursor: grabbing; box-shadow: 0 8px 24px rgba(0,0,0,0.12); }

    /* Tag pill */
    .tag-pill { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #f2ecf5; color: #494552; }

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
      transition: border-color 0.2s, background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
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
    .style-chip:disabled {
      opacity: 0.42;
      cursor: not-allowed;
    }
    .style-chip .style-chip-check {
      display: none;
      font-size: 15px;
      font-variation-settings: 'FILL' 0, 'wght' 600, 'GRAD' 0, 'opsz' 20;
    }
    .style-chip.is-selected .style-chip-check { display: inline-flex; }

    /* Mobile overflow fixes */
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }

    /* Image crop modal (above Add Work z-index 200) */
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

    /* Select2 (Add Work modal — dropdown on body so it is not clipped by overflow) */
    #addWorkModal .select2-container { width: 100% !important; z-index: 1; }
    .select2-container--open { z-index: 10060 !important; }
    #addWorkModal .select2-container--default .select2-selection--single {
      min-height: 46px;
      padding: 4px 10px;
      border-radius: 0.75rem;
      border: 1px solid rgba(202,196,211,0.5) !important;
      background: #fff !important;
    }
    #addWorkModal .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 2.15rem;
      padding-left: 2px;
      color: #1c1b21;
      font-size: 0.875rem;
    }
    #addWorkModal .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px; }
    #addWorkModal .select2-container--default.select2-container--focus .select2-selection--single,
    #addWorkModal .select2-container--default.select2-container--open .select2-selection--single {
      border-color: rgba(26, 26, 26, 0.35) !important;
      box-shadow: 0 0 0 2px rgba(26, 26, 26, 0.12);
    }
    .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #1a1a1a !important; }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border-radius: 0.5rem;
      border-color: rgba(202,196,211,0.5);
    }

    /* Portfolio modal — image frame sizes to preview (intrinsic image bounds) */
    .work-image-upload {
      margin-left: auto;
      margin-right: auto;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .work-image-upload--empty {
      width: 100%;
      max-width: 20rem;
      min-height: 220px;
    }
    .work-image-upload--has-image {
      width: fit-content;
      max-width: 100%;
      border-style: solid;
      border-color: rgba(202, 196, 211, 0.55);
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
    }
    .work-image-upload--has-image:hover {
      border-color: rgba(26, 26, 26, 0.28);
    }
    .work-image-upload-preview-wrap {
      position: relative;
      line-height: 0;
      background: transparent;
      overflow: hidden;
    }
    #workImagePreviewImg.work-image-preview-img {
      display: block;
      width: auto;
      height: auto;
      max-width: min(100%, 22rem);
      max-height: min(65vh, 420px);
    }
    .work-ai-overlay {
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
    .work-image-upload.ai-busy .work-ai-overlay { display: flex; }
    .work-ai-overlay .material-symbols-outlined {
      font-size: 28px;
      animation: work-ai-pulse 1.1s ease-in-out infinite;
    }
    @keyframes work-ai-pulse {
      0%, 100% { opacity: 0.55; transform: scale(0.96); }
      50% { opacity: 1; transform: scale(1); }
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
        $bookingPageUrl = $bookingPageUsername ? 'https://inkjin.com/@'.$bookingPageUsername.'#portfolio' : null;
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

      @include('artist.partials.booking-page-tabs', ['activeTab' => 'portfolio'])

      @if (session('success'))
        <div class="mb-4 rounded-xl px-4 py-3 text-sm bg-emerald-50 text-emerald-800 border border-emerald-200">{{ session('success') }}</div>
      @endif
      @if (session('error'))
        <div class="mb-4 rounded-xl px-4 py-3 text-sm bg-red-50 text-red-800 border border-red-200">{{ session('error') }}</div>
      @endif

      <!-- Section intro -->
      @php
        $igIconPath = 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z';
        $instagramImportedCount = $instagramImportedCount ?? 0;
      @endphp
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 {{ ($showInstagramUi && !empty($instagramConnected)) ? 'mb-4' : '' }}">
          <div class="min-w-0">
            <p class="text-on-surface-variant">Showcase your best work to attract new clients.</p>
            @if ($showInstagramUi && !empty($instagramConnected))
              <p class="text-xs text-on-surface-variant/70 mt-1">Add Work to upload images yourself · Refresh to pull in the latest work from IG</p>
            @elseif ($showInstagramUi)
              <p class="text-xs text-on-surface-variant/70 mt-1">Add Work to upload an image yourself. Connect Instagram to import posts (requires an Instagram Business or Creator account).</p>
            @else
              <p class="text-xs text-on-surface-variant/70 mt-1">Add Work to upload an image yourself.</p>
            @endif
          </div>
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0">
            @if ($showInstagramUi && empty($instagramConnected))
              <a href="{{ route('instagram.connect') }}" id="btnConnectInstagram" class="btn-connect-instagram">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $igIconPath }}"/></svg>
                Connect Instagram
              </a>
            @endif
            <button type="button" id="btnAddWork" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-lg">add</span> Add Work
            </button>
          </div>
        </div>

        @if ($showInstagramUi && !empty($instagramConnected))
        <div class="instagram-status-card">
          <div class="instagram-status-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="{{ $igIconPath }}"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-semibold text-on-surface text-sm">Instagram</span>
              <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                Connected
              </span>
            </div>
            <p class="text-xs text-on-surface-variant mt-0.5" id="instagramImportedMeta">
              <span id="instagramImportedCountLabel">{{ $instagramImportedCount }} {{ $instagramImportedCount === 1 ? 'post' : 'posts' }} imported</span>
              @if (!empty($instagramUsername))
                · Connected account: {{ '@'.ltrim((string) $instagramUsername, '@') }}
              @endif
            </p>
          </div>
          <div class="instagram-status-actions">
            <button type="button" id="btnInstagramRefresh" class="btn-ig-refresh"
              data-start-url="{{ route('instagram.import-portfolio.start') }}"
              data-next-url="{{ route('instagram.import-portfolio.next') }}">
              <span class="material-symbols-outlined text-base">refresh</span>
              Refresh
            </button>
            <form method="POST" action="{{ route('instagram.disconnect') }}" id="formInstagramDisconnect">
              @csrf
              <button type="button" id="btnInstagramDisconnect" class="btn-ig-disconnect">Disconnect</button>
            </form>
          </div>
        </div>
        @endif
      </div>

      <!-- Portfolio Grid -->
      @if ($portfolios->isNotEmpty())
        <p class="text-xs text-on-surface-variant mb-3 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-base text-outline">drag_indicator</span>
          Drag pieces to change the order they appear on your booking page.
        </p>
      @endif
      <div id="portfolioGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($portfolios as $portfolio)
        @include('artist.portfolio._card', ['portfolio' => $portfolio])
        @empty
        <div class="col-span-full rounded-2xl border border-dashed border-outline-variant/40 bg-white/60 px-6 py-14 text-center" id="portfolioEmptyState">
          <span class="material-symbols-outlined text-outline/40 text-4xl mb-2 inline-block">photo_library</span>
          <p class="text-sm font-semibold text-on-surface">No portfolio pieces yet</p>
          <p class="text-xs text-on-surface-variant mt-1 max-w-sm mx-auto">Add your first work with the button above. It will appear here after you save.</p>
        </div>
        @endforelse
      </div>
    </div>
  </main>

  <!-- Instagram Import Progress Modal -->
  <div class="modal-backdrop" id="instagramImportModal" aria-hidden="true">
    <div class="add-work-modal-inner bg-white rounded-2xl w-full max-w-sm mx-4 shadow-2xl overflow-hidden">
      <div class="px-6 py-5 border-b border-outline-variant/15">
        <h3 class="text-lg font-bold text-on-surface">Importing from Instagram</h3>
      </div>
      <div class="p-6 space-y-4">
        <div class="flex items-center gap-3">
          <span class="material-symbols-outlined text-primary animate-spin text-2xl" id="igImportSpinner">progress_activity</span>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-on-surface" id="igImportStatus">Preparing…</p>
            <p class="text-xs text-on-surface-variant mt-0.5" id="igImportDetail">Please wait while we pull your latest images.</p>
          </div>
        </div>
        <div class="ig-progress-track" aria-hidden="true">
          <div class="ig-progress-bar" id="igImportProgressBar"></div>
        </div>
        <p class="text-center text-sm font-bold text-on-surface tabular-nums" id="igImportCount">0 / 0</p>
      </div>
    </div>
  </div>

  <!-- Add Work Modal -->
  <div class="modal-backdrop" id="addWorkModal" aria-hidden="true">
    <div class="add-work-modal-inner bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
        <h3 id="addWorkModalTitle" class="text-lg font-bold text-on-surface">Add Work</h3>
        <button type="button" id="btnCloseAddWork" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low transition-colors">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="p-6 space-y-5">
        <input type="hidden" id="workPortfolioId" value="">
        <input type="hidden" id="workPortfolioUpdateUrl" value="">
        <div id="workFormBanner" class="hidden rounded-xl border border-error/30 bg-error-container/40 px-3 py-2 text-xs text-on-error-container space-y-1"></div>
        <p class="text-on-surface-variant leading-relaxed">Upload the image first. We'll use AI to analyze your uploaded image and automatically fill in some fields. You can always edit these.</p>
        <!-- Image Upload -->
        <div class="work-field-section scroll-mt-6" data-work-field="image">
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5">Image</label>
          <p class="text-[11px] text-on-surface-variant mb-2">Cropped to <strong class="text-on-surface">1080 × 1350 px</strong> · aspect <strong class="text-on-surface">4:5</strong></p>
          <div id="workImageUpload" class="work-image-upload work-image-upload--empty relative border-2 border-dashed border-outline-variant/40 rounded-2xl cursor-pointer hover:border-primary/50 hover:bg-primary/5 overflow-hidden">
            <div id="workImageUploadEmpty" class="flex flex-col items-center justify-center gap-2 px-4 py-10 min-h-[220px]">
              <span class="material-symbols-outlined text-outline/40 text-4xl">cloud_upload</span>
              <div class="text-center">
                <p class="text-sm font-semibold text-on-surface">Drop image here</p>
                <p class="text-xs text-on-surface-variant mt-1">or click to browse</p>
                <p class="text-xs text-outline mt-2">PNG, JPG up to 10MB</p>
              </div>
            </div>
            <div id="workImageUploadPreview" class="hidden work-image-upload-preview-wrap rounded-2xl">
              <img id="workImagePreviewImg" src="" alt="Selected work preview" class="work-image-preview-img">
              <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent pt-8 pb-2 px-3 pointer-events-none">
                <p class="text-[11px] text-white/90 text-center font-medium">Tap to replace image</p>
              </div>
            </div>
            <div class="work-ai-overlay" aria-live="polite">
              <span class="material-symbols-outlined">auto_awesome</span>
              <p class="text-xs font-semibold leading-snug">Filling empty fields with AI…</p>
            </div>
          </div>
          <input type="file" id="workImage" name="workImage" accept="image/*" class="hidden">
          <input type="hidden" id="workImageData" name="workImageData" value="">
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="image"></p>
        </div>
        <!-- Title -->
        <div class="work-field-section scroll-mt-6" data-work-field="title">
          <label for="workTitle" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Title</label>
          <input type="text" id="workTitle" name="workTitle" placeholder="e.g., Koi Fish Half Sleeve" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="title"></p>
        </div>
        <!-- Description -->
        <div class="work-field-section scroll-mt-6" data-work-field="description">
          <label for="workDescription" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Description <span class="font-normal text-on-surface-variant/70">(optional)</span></label>
          <textarea id="workDescription" name="workDescription" rows="3" placeholder="Describe this piece…" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="description"></p>
        </div>
        <!-- Toggle Switches -->
        <div class="work-field-section scroll-mt-6" data-work-field="is_active">
          <label class="block text-xs font-semibold text-on-surface-variant mb-2">Settings</label>
          <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
              <div id="workToggleVisibility" class="toggle-switch active"></div>
              <span class="text-sm text-on-surface">Visibility</span>
            </div>
            <!-- <div class="flex items-center gap-2">
              <div id="workToggleSensitive" class="toggle-switch"></div>
              <span class="text-sm text-on-surface">Sensitive</span>
            </div> -->
          </div>
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="is_active"></p>
        </div>
        <!-- Primary Style -->
        <div class="work-field-section scroll-mt-6" data-work-field="primary_style">
          <label for="workPrimaryStyle" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Primary Style</label>
          <select id="workPrimaryStyle" name="workPrimaryStyle" class="js-portfolio-modal-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="">Select style…</option>
            @foreach ($styles as $style)
            <option value="{{ $style }}">{{ $style }}</option>
            @endforeach
          </select>
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="primary_style"></p>
        </div>
        <!-- Other Styles (max 2) -->
        <div class="work-field-section scroll-mt-6" data-work-field="other_styles">
          <div class="flex items-center justify-between gap-2 mb-1.5">
            <span class="text-xs font-semibold text-on-surface-variant">Other styles</span>
            <span class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-surface-container-high px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Max 2</span>
          </div>
          <p class="text-[11px] text-on-surface-variant leading-relaxed mb-3">Add up to two secondary styles. They should differ from your primary style when possible.</p>
          <div class="flex items-center justify-between mb-2.5 rounded-xl bg-surface-container-low/80 px-3 py-2 border border-outline-variant/15">
            <span class="text-xs text-on-surface-variant">Selected</span>
            <span class="text-sm font-bold tabular-nums text-on-surface"><span id="workOtherStylesCount">0</span><span class="text-on-surface-variant font-semibold"> / 2</span></span>
          </div>
          <div id="workOtherStylesChips" class="flex flex-wrap gap-2" role="group" aria-label="Other tattoo styles">
            @foreach ($styles as $style)
            <button type="button" class="style-chip" data-value="{{ $style }}" aria-pressed="false"><span class="material-symbols-outlined style-chip-check">check</span>{{ $style }}</button>
            @endforeach
          </div>
          <select id="workOtherStyles" name="workOtherStyles" multiple class="hidden" tabindex="-1" aria-hidden="true">
            @foreach ($styles as $style)
            <option value="{{ $style }}">{{ $style }}</option>
            @endforeach
          </select>
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="other_styles"></p>
        </div>
        <!-- Placement (max 1) -->
        <div class="work-field-section scroll-mt-6" data-work-field="placement">
          <div class="flex items-center justify-between gap-2 mb-1.5">
            <span class="text-xs font-semibold text-on-surface-variant">Placement</span>
            <span class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-surface-container-high px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Max 1</span>
          </div>
          <p class="text-[11px] text-on-surface-variant leading-relaxed mb-3">Where on the body is this piece placed.</p>
          <div class="flex items-center justify-between mb-2.5 rounded-xl bg-surface-container-low/80 px-3 py-2 border border-outline-variant/15">
            <span class="text-xs text-on-surface-variant">Selected</span>
            <span class="text-sm font-bold tabular-nums text-on-surface"><span id="workPlacementsCount">0</span><span class="text-on-surface-variant font-semibold"> / 1</span></span>
          </div>
          <div id="workPlacementsChips" class="flex flex-wrap gap-2" role="group" aria-label="Placement">
            @foreach (($placements ?? []) as $placement)
            <button type="button" class="style-chip" data-value="{{ $placement }}" aria-pressed="false"><span class="material-symbols-outlined style-chip-check">check</span>{{ $placement }}</button>
            @endforeach
          </div>
          <select id="workPlacement" name="workPlacement" class="hidden" tabindex="-1" aria-hidden="true">
            <option value=""></option>
            @foreach (($placements ?? []) as $placement)
            <option value="{{ $placement }}">{{ $placement }}</option>
            @endforeach
          </select>
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="placement"></p>
        </div>
        <!-- Colors -->
        <div class="work-field-section scroll-mt-6" data-work-field="color">
          <label for="workColors" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Colors</label>
          <select id="workColors" name="workColors" class="js-portfolio-modal-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="">Select…</option>
            <option value="color">Color</option>
            <option value="black-grey">Black & Grey</option>
            <option value="both">Both</option>
          </select>
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="color"></p>
        </div>
        <!-- Tags -->
        <div class="work-field-section scroll-mt-6" data-work-field="tags">
          <label for="workTags" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Tags <span class="text-outline font-normal">(comma separated)</span></label>
          <input type="text" id="workTags" name="workTags" placeholder="e.g., koi, sleeve, water" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          <p class="hidden work-field-error mt-1.5 text-xs text-error" data-error-for="tags"></p>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3">
        <button type="button" id="btnCancelAddWork" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnSaveWork" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg">save</span> <span class="save-btn-label">Save</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Crop image (4:5 → 1080×1350) -->
  <div id="workCropModal" class="crop-modal-backdrop" aria-hidden="true">
    <div class="crop-modal-inner bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">
      <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant/15 flex-shrink-0">
        <div>
          <h3 class="text-lg font-bold text-on-surface">Crop image</h3>
          <p class="text-xs text-on-surface-variant mt-0.5">Output <span class="font-semibold text-on-surface">1080 × 1350 px</span> · ratio 4:5</p>
        </div>
        <button type="button" id="btnCropClose" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors" aria-label="Close cropper">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="cropper-stage-wrap mx-4 my-3 flex-shrink-0">
        <img id="workCropperImg" src="" alt="" class="max-w-full">
      </div>
      <div class="px-5 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 flex-shrink-0 bg-surface-container-low/30">
        <button type="button" id="btnCropCancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2.5 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnCropApply" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg">check</span> Apply crop
        </button>
      </div>
    </div>
  </div>

  <!-- Delete portfolio confirmation -->
  <div class="modal-backdrop" id="deletePortfolioModal" aria-hidden="true">
    <div class="add-work-modal-inner bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden">
      <div class="p-6">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-error-container flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-error text-2xl">delete_forever</span>
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-on-surface tracking-tight">Delete this piece?</h3>
            <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">This will permanently remove the work from your portfolio. You cannot undo this.</p>
            <p id="deletePortfolioError" class="hidden mt-3 text-xs text-error font-semibold leading-snug"></p>
          </div>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 bg-surface-container-low/30">
        <button type="button" id="btnDeletePortfolioCancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2.5 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnDeletePortfolioConfirm" class="bg-error text-on-error px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-95 transition-opacity shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg confirm-delete-icon">delete</span> <span class="confirm-delete-label">Delete</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Disconnect Instagram confirmation -->
  <div class="modal-backdrop" id="disconnectInstagramModal" aria-hidden="true">
    <div class="add-work-modal-inner bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden">
      <div class="p-6">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-error-container flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-error text-2xl">link_off</span>
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-on-surface tracking-tight">Disconnect Instagram?</h3>
            <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">Your account will be disconnected. Already imported portfolio items will stay in your portfolio.</p>
          </div>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end gap-3 bg-surface-container-low/30">
        <button type="button" id="btnDisconnectInstagramCancel" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface px-4 py-2.5 rounded-xl transition-colors">Cancel</button>
        <button type="button" id="btnDisconnectInstagramConfirm" class="bg-error text-on-error px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-95 transition-opacity shadow-sm flex items-center gap-2">
          <span class="material-symbols-outlined text-lg">link_off</span> Disconnect
        </button>
      </div>
    </div>
  </div>

@endsection

@section('scripts')

@php
    $portfolioEditorData = $portfolios->mapWithKeys(function ($p) {
        return [(string) $p->id => [
            'title' => $p->title,
            'description' => $p->description,
            'is_active' => (bool) $p->is_active,
            'primary_style' => $p->primary_style,
            'other_styles' => $p->other_styles ?? [],
            'placement' => $p->placement,
            'color' => $p->color,
            'tags' => $p->tags ?? [],
            'image_url' => asset($p->image),
        ]];
    })->all();
@endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
  <script>
    window.PORTFOLIO_EDITOR_DATA = @json($portfolioEditorData);
    var PORTFOLIO_STYLE_OPTIONS = @json($styles);
    var PORTFOLIO_PLACEMENT_OPTIONS = @json($placements ?? []);
    var PORTFOLIO_STORE_URL = @json(route('portfolio.store'));
    var PORTFOLIO_AI_SUGGEST_URL = @json(route('portfolio.ai-suggest'));
    var PORTFOLIO_REORDER_URL = @json(route('portfolio.reorder'));
    var INSTAGRAM_AUTO_IMPORT = @json(!empty($instagramAutoImport) && !empty($showInstagramUi));
    $(function () {
      var MODAL_MS = 350;
      var $deletePortfolioModal = $('#deletePortfolioModal');
      var $disconnectInstagramModal = $('#disconnectInstagramModal');
      var $addWorkModal = $('#addWorkModal');
      var $workCropModal = $('#workCropModal');
      var $workCropperImg = $('#workCropperImg');
      var workCropper = null;
      var CROP_OUT_W = 1080;
      var CROP_OUT_H = 1350;
      var CROP_RATIO = 4 / 5;
      var MAX_FILE_BYTES = 10 * 1024 * 1024;

      function destroyWorkCropper() {
        if (workCropper) {
          workCropper.destroy();
          workCropper = null;
        }
      }

      function revokeWorkCropBlob() {
        var u = $workCropperImg.data('blob-url');
        if (u) {
          URL.revokeObjectURL(u);
          $workCropperImg.removeData('blob-url');
        }
      }

      function closeWorkCropModal() {
        destroyWorkCropper();
        revokeWorkCropBlob();
        $workCropperImg.attr('src', '');
        $workCropModal.removeClass('is-open').attr('aria-hidden', 'true');
      }

      function openWorkCropModalWithFile(file) {
        if (!file || !/^image\//.test(file.type)) {
          alert('Please choose an image file (PNG or JPG).');
          return;
        }
        if (file.size > MAX_FILE_BYTES) {
          alert('Image must be 10MB or smaller.');
          return;
        }
        destroyWorkCropper();
        revokeWorkCropBlob();
        var url = URL.createObjectURL(file);
        $workCropperImg.data('blob-url', url);
        $workCropModal.addClass('is-open').attr('aria-hidden', 'false');
        $workCropperImg.off('load.workcrop').on('load.workcrop', function () {
          var img = this;
          $workCropperImg.off('load.workcrop');
          destroyWorkCropper();
          workCropper = new Cropper(img, {
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
        $workCropperImg.attr('src', url);
      }

      function setWorkImageUploadHasPreview(hasPreview) {
        var $u = $('#workImageUpload');
        if (hasPreview) {
          $u.removeClass('work-image-upload--empty').addClass('work-image-upload--has-image');
        } else {
          $u.removeClass('work-image-upload--has-image').addClass('work-image-upload--empty');
        }
      }

      function applyWorkCrop() {
        if (!workCropper) return;
        var canvas = workCropper.getCroppedCanvas({
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
        $('#workImageData').val(dataUrl);
        $('#workImagePreviewImg').attr('src', dataUrl);
        $('#workImageUploadEmpty').addClass('hidden');
        $('#workImageUploadPreview').removeClass('hidden');
        setWorkImageUploadHasPreview(true);
        closeWorkCropModal();
        $('#workImage').val('');
        requestPortfolioAiSuggestions(dataUrl);
      }

      var portfolioAiSuggestXhr = null;

      function parseWorkTagsInput() {
        return ($('#workTags').val() || '').split(',').map(function (t) {
          return $.trim(t);
        }).filter(Boolean);
      }

      function collectEmptyPortfolioAiFieldsSnapshot() {
        return {
          title: !$.trim($('#workTitle').val() || ''),
          description: !$.trim($('#workDescription').val() || ''),
          primary_style: !($('#workPrimaryStyle').val() || ''),
          other_styles: $('#workOtherStyles option:selected').length === 0,
          placement: !($('#workPlacement').val() || ''),
          color: !($('#workColors').val() || ''),
          tags: parseWorkTagsInput().length === 0
        };
      }

      function setPortfolioAiBusy(busy) {
        $('#workImageUpload').toggleClass('ai-busy', !!busy);
      }

      function applyPortfolioAiSuggestions(suggestions, emptyAtRequest) {
        if (!suggestions || typeof suggestions !== 'object') return;
        var emptyNow = collectEmptyPortfolioAiFieldsSnapshot();

        if (emptyAtRequest.title && emptyNow.title && suggestions.title) {
          $('#workTitle').val(suggestions.title);
        }
        if (emptyAtRequest.description && emptyNow.description && suggestions.description) {
          $('#workDescription').val(suggestions.description);
        }
        if (emptyAtRequest.primary_style && emptyNow.primary_style && suggestions.primary_style) {
          ensureWorkStyleSelectValue($('#workPrimaryStyle'), suggestions.primary_style);
          $('#workPrimaryStyle').trigger('change');
        }
        if (emptyAtRequest.other_styles && emptyNow.other_styles && Array.isArray(suggestions.other_styles)) {
          var primary = $('#workPrimaryStyle').val() || '';
          var picked = [];
          suggestions.other_styles.forEach(function (styleValue) {
            if (!styleValue || styleValue === primary) return;
            if (picked.indexOf(styleValue) !== -1) return;
            if (!workOtherStyleOptionByValue(styleValue).length) return;
            picked.push(styleValue);
          });
          picked = picked.slice(0, 2);
          $('#workOtherStyles option').prop('selected', false);
          picked.forEach(function (styleValue) {
            workOtherStyleOptionByValue(styleValue).prop('selected', true);
          });
          syncOtherStylesChipsFromSelect();
        }
        if (emptyAtRequest.placement && emptyNow.placement) {
          var aiPlacements = Array.isArray(suggestions.suggested_placements) ? suggestions.suggested_placements : [];
          var firstPlacement = '';
          aiPlacements.forEach(function (placementValue) {
            if (firstPlacement || !placementValue) return;
            if (!workPlacementOptionByValue(placementValue).length) return;
            firstPlacement = placementValue;
          });
          if (firstPlacement) {
            $('#workPlacement').val(firstPlacement);
            syncWorkPlacementsChipsFromSelect();
          }
        }
        if (emptyAtRequest.color && emptyNow.color && suggestions.color) {
          $('#workColors').val(suggestions.color).trigger('change');
        }
        if (emptyAtRequest.tags && emptyNow.tags && Array.isArray(suggestions.tags) && suggestions.tags.length) {
          $('#workTags').val(suggestions.tags.join(', '));
        }
      }

      function requestPortfolioAiSuggestions(dataUrl) {
        if (!PORTFOLIO_AI_SUGGEST_URL || !dataUrl) return;

        var emptyAtRequest = collectEmptyPortfolioAiFieldsSnapshot();
        var anyEmpty = Object.keys(emptyAtRequest).some(function (k) { return emptyAtRequest[k]; });
        if (!anyEmpty) return;

        if (portfolioAiSuggestXhr && portfolioAiSuggestXhr.readyState !== 4) {
          try { portfolioAiSuggestXhr.abort(); } catch (e) { /* ignore */ }
        }

        var fd = new FormData();
        fd.append('image_data', dataUrl);
        fd.append('title', $('#workTitle').val() || '');
        fd.append('description', $('#workDescription').val() || '');
        fd.append('primary_style', $('#workPrimaryStyle').val() || '');
        fd.append('color', $('#workColors').val() || '');
        $('#workOtherStyles option:selected').each(function () {
          fd.append('other_styles[]', $(this).val());
        });
        if ($('#workPlacement').val()) {
          fd.append('suggested_placements[]', $('#workPlacement').val());
        }
        parseWorkTagsInput().forEach(function (tag) {
          fd.append('tags[]', tag);
        });

        setPortfolioAiBusy(true);
        portfolioAiSuggestXhr = $.ajax({
          url: PORTFOLIO_AI_SUGGEST_URL,
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
            applyPortfolioAiSuggestions(res.suggestions || {}, emptyAtRequest);
          },
          error: function (xhr) {
            if (xhr && xhr.statusText === 'abort') return;
          },
          complete: function () {
            setPortfolioAiBusy(false);
            portfolioAiSuggestXhr = null;
          }
        });
      }

      function updateOtherStylesChipsUI() {
        var $chips = $('#workOtherStylesChips .style-chip');
        var n = $chips.filter('.is-selected').length;
        $('#workOtherStylesCount').text(n);
        var atMax = n >= 2;
        $chips.each(function () {
          var on = $(this).hasClass('is-selected');
          $(this).prop('disabled', atMax && !on).attr('aria-pressed', on ? 'true' : 'false');
        });
      }

      function workOtherStyleOptionByValue(val) {
        return $('#workOtherStyles option').filter(function () {
          return $(this).val() === val;
        });
      }

      function workStyleChipByValue(val) {
        return $('#workOtherStylesChips .style-chip').filter(function () {
          return $(this).attr('data-value') === val;
        });
      }

      function ensureWorkStyleSelectValue($select, value) {
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

      function syncOtherStylesChipsFromSelect() {
        $('#workOtherStylesChips .style-chip').removeClass('is-selected').prop('disabled', false);
        $('#workOtherStyles option').each(function () {
          if (this.selected) {
            workStyleChipByValue(this.value).addClass('is-selected');
          }
        });
        updateOtherStylesChipsUI();
      }

      function updateWorkPlacementsChipsUI() {
        var $chips = $('#workPlacementsChips .style-chip');
        var n = $chips.filter('.is-selected').length;
        $('#workPlacementsCount').text(n);
        var atMax = n >= 1;
        $chips.each(function () {
          var on = $(this).hasClass('is-selected');
          $(this).prop('disabled', atMax && !on).attr('aria-pressed', on ? 'true' : 'false');
        });
      }

      function workPlacementOptionByValue(val) {
        return $('#workPlacement option').filter(function () {
          return $(this).val() === val;
        });
      }

      function workPlacementChipByValue(val) {
        return $('#workPlacementsChips .style-chip').filter(function () {
          return $(this).attr('data-value') === val;
        });
      }

      function syncWorkPlacementsChipsFromSelect() {
        $('#workPlacementsChips .style-chip').removeClass('is-selected').prop('disabled', false);
        var val = $('#workPlacement').val() || '';
        if (val) {
          workPlacementChipByValue(val).addClass('is-selected');
        }
        updateWorkPlacementsChipsUI();
      }

      function initPortfolioModalSelect2() {
        if (!window.jQuery || !$.fn.select2) return;
        var $primary = $('#workPrimaryStyle');
        var $colors = $('#workColors');
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

      function closePortfolioModalSelect2() {
        ['#workPrimaryStyle', '#workColors'].forEach(function (sel) {
          var $n = $(sel);
          if ($n.length && $n.hasClass('select2-hidden-accessible')) {
            try {
              $n.select2('close');
            } catch (e) { /* ignore */ }
          }
        });
      }

      function openAddWorkModal() {
        clearTimeout($addWorkModal.data('closeTimer'));
        $addWorkModal.addClass('modal-visible').attr('aria-hidden', 'false');
        $('body').css('overflow', 'hidden');
        syncOtherStylesChipsFromSelect();
        syncWorkPlacementsChipsFromSelect();
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            $addWorkModal.addClass('modal-open');
            initPortfolioModalSelect2();
            $('#workPrimaryStyle, #workColors').each(function () {
              if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).trigger('change.select2');
              }
            });
          });
        });
      }

      function resetWorkImageState() {
        closeWorkCropModal();
        if (portfolioAiSuggestXhr && portfolioAiSuggestXhr.readyState !== 4) {
          try { portfolioAiSuggestXhr.abort(); } catch (e) { /* ignore */ }
        }
        setPortfolioAiBusy(false);
        $('#workImageData').val('');
        $('#workImagePreviewImg').attr('src', '').off('load.portfoliofit');
        $('#workImageUploadEmpty').removeClass('hidden');
        $('#workImageUploadPreview').addClass('hidden');
        $('#workImage').val('');
        setWorkImageUploadHasPreview(false);
      }

      function closeAddWorkModal() {
        closePortfolioModalSelect2();
        resetWorkImageState();
        $addWorkModal.removeClass('modal-open');
        clearTimeout($addWorkModal.data('closeTimer'));
        var t = setTimeout(function () {
          $addWorkModal.removeClass('modal-visible').attr('aria-hidden', 'true');
          $('body').css('overflow', '');
        }, MODAL_MS);
        $addWorkModal.data('closeTimer', t);
      }

      function showSaveToast() {
        var $toast = $('#saveToast');
        if (!$toast.length) return;
        $toast.removeClass('translate-x-full opacity-0').addClass('translate-x-0 opacity-100');
        clearTimeout($toast.data('hideTimer'));
        $toast.data('hideTimer', setTimeout(function () {
          $toast.addClass('translate-x-full opacity-0').removeClass('translate-x-0 opacity-100');
        }, 3000));
      }

      function showToastMessage(message) {
        var $toast = $('#saveToast');
        if (!$toast.length) {
          return;
        }
        $toast.find('span').last().text(message || 'Saved successfully');
        showSaveToast();
      }

      var $igImportModal = $('#instagramImportModal');
      var igImportBusy = false;

      function setIgImportLock(locked) {
        $('body').toggleClass('ig-import-lock', !!locked);
        $('#btnInstagramRefresh, #btnInstagramDisconnect, #btnAddWork, #btnConnectInstagram').prop('disabled', !!locked);
      }

      function openIgImportModal() {
        clearTimeout($igImportModal.data('closeTimer'));
        $igImportModal.addClass('modal-visible').attr('aria-hidden', 'false');
        requestAnimationFrame(function () {
          $igImportModal.addClass('modal-open');
        });
        $('body').css('overflow', 'hidden');
      }

      function closeIgImportModal() {
        $igImportModal.removeClass('modal-open');
        clearTimeout($igImportModal.data('closeTimer'));
        var t = setTimeout(function () {
          $igImportModal.removeClass('modal-visible').attr('aria-hidden', 'true');
          if (!$addWorkModal.hasClass('modal-open') && !$deletePortfolioModal.hasClass('modal-open') && !$disconnectInstagramModal.hasClass('modal-open')) {
            $('body').css('overflow', '');
          }
        }, MODAL_MS);
        $igImportModal.data('closeTimer', t);
      }

      function updateIgImportProgress(current, total, status, detail) {
        var safeTotal = Math.max(0, parseInt(total, 10) || 0);
        var safeCurrent = Math.max(0, Math.min(safeTotal, parseInt(current, 10) || 0));
        var pct = safeTotal > 0 ? Math.round((safeCurrent / safeTotal) * 100) : 0;
        $('#igImportCount').text(safeCurrent + ' / ' + safeTotal);
        $('#igImportProgressBar').css('width', pct + '%');
        if (status) $('#igImportStatus').text(status);
        if (detail) $('#igImportDetail').text(detail);
      }

      function updateInstagramImportedCount(count) {
        var n = parseInt(count, 10);
        if (isNaN(n)) return;
        var label = n + ' ' + (n === 1 ? 'post' : 'posts') + ' imported';
        $('#instagramImportedCountLabel').text(label);
      }

      function prependImportedCard(html, editor) {
        if (!html) return;
        var $grid = $('#portfolioGrid');
        $('#portfolioEmptyState').remove();
        // Append so oldest→newest import lands with oldest higher / newest lower when sort_order increases.
        $grid.append(html);
        if (editor && editor.id) {
          window.PORTFOLIO_EDITOR_DATA = window.PORTFOLIO_EDITOR_DATA || {};
          window.PORTFOLIO_EDITOR_DATA[String(editor.id)] = {
            title: editor.title,
            description: editor.description,
            is_active: !!editor.is_active,
            primary_style: editor.primary_style,
            other_styles: editor.other_styles || [],
            placement: editor.placement || null,
            color: editor.color,
            tags: editor.tags || [],
            image_url: editor.image_url
          };
        }
      }

      function igAjaxPost(url) {
        return $.ajax({
          url: url,
          method: 'POST',
          dataType: 'json',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'Accept': 'application/json'
          }
        });
      }

      function finishIgImport(message, isError) {
        igImportBusy = false;
        setIgImportLock(false);
        closeIgImportModal();
        if (message) {
          showToastMessage(message);
        }
      }

      function runIgImportNext(nextUrl, expectingCurrent, total) {
        updateIgImportProgress(
          expectingCurrent,
          total,
          'Importing images…',
          'Importing image ' + expectingCurrent + ' of ' + total
        );

        igAjaxPost(nextUrl)
          .done(function (data) {
            var current = data && data.current ? data.current : expectingCurrent;
            var totalNow = data && data.total ? data.total : total;
            updateIgImportProgress(
              current,
              totalNow,
              'Importing images…',
              'Imported ' + current + ' of ' + totalNow
            );

            if (data && data.card_html) {
              prependImportedCard(data.card_html, data.editor);
            }
            if (data && typeof data.imported_count !== 'undefined') {
              updateInstagramImportedCount(data.imported_count);
            }

            if (data && data.done) {
              finishIgImport((data && data.message) || 'Import complete.');
              return;
            }

            runIgImportNext(nextUrl, current + 1, totalNow);
          })
          .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
              ? xhr.responseJSON.message
              : 'Failed to import Instagram images. Please try again.';
            finishIgImport(msg, true);
          });
      }

      function startInstagramImport() {
        if (igImportBusy) return;

        var $btn = $('#btnInstagramRefresh');
        if (!$btn.length) return;

        var startUrl = $btn.data('start-url');
        var nextUrl = $btn.data('next-url');
        if (!startUrl || !nextUrl) return;

        igImportBusy = true;
        setIgImportLock(true);
        updateIgImportProgress(0, 0, 'Preparing…', 'Checking your latest Instagram posts.');
        openIgImportModal();

        igAjaxPost(startUrl)
          .done(function (data) {
            var total = data && data.total ? parseInt(data.total, 10) : 0;
            if (!data || !data.ok) {
              finishIgImport((data && data.message) || 'Could not start Instagram import.');
              return;
            }

            if (!total) {
              finishIgImport((data && data.message) || 'No new Instagram images to import.');
              return;
            }

            runIgImportNext(nextUrl, 1, total);
          })
          .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
              ? xhr.responseJSON.message
              : 'Failed to start Instagram import. Please try again.';
            finishIgImport(msg, true);
          });
      }

      $(document).on('click', '#btnInstagramRefresh', function (e) {
        e.preventDefault();
        startInstagramImport();
      });

      if (INSTAGRAM_AUTO_IMPORT) {
        setTimeout(function () {
          startInstagramImport();
        }, 300);
      }

      var WORK_FORM_FIELD_ORDER = ['image', 'title', 'description', 'is_active', 'primary_style', 'other_styles', 'placement', 'color', 'tags'];

      function clearWorkFormErrors() {
        $('#workFormBanner').addClass('hidden').empty();
        $('.work-field-error').addClass('hidden').empty();
      }

      function applyWorkFormErrorMap(map) {
        Object.keys(map).forEach(function (key) {
          var raw = map[key];
          var msg = typeof raw === 'string' ? raw : (Array.isArray(raw) ? raw.join(' ') : String(raw));
          var baseKey = key.indexOf('.') !== -1 ? key.split('.')[0] : key;
          var $el = $('.work-field-error[data-error-for="' + key + '"]');
          if (!$el.length) {
            $el = $('.work-field-error[data-error-for="' + baseKey + '"]');
          }
          if ($el.length) {
            $el.removeClass('hidden').text(msg);
          } else {
            $('#workFormBanner').removeClass('hidden').append($('<p></p>').text(msg));
          }
        });
      }

      function scrollWorkModalToElement(el) {
        if (!el) return;
        var inner = document.querySelector('#addWorkModal .add-work-modal-inner');
        if (inner && typeof inner.getBoundingClientRect === 'function') {
          var er = el.getBoundingClientRect();
          var ir = inner.getBoundingClientRect();
          var delta = er.top - ir.top + inner.scrollTop - 16;
          inner.scrollTo({ top: Math.max(0, delta), behavior: 'smooth' });
          return;
        }
        if (typeof el.scrollIntoView === 'function') {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }

      function scrollToFirstWorkFormError() {
        var found = false;
        for (var i = 0; i < WORK_FORM_FIELD_ORDER.length; i++) {
          var f = WORK_FORM_FIELD_ORDER[i];
          var $err = $('.work-field-error[data-error-for="' + f + '"]');
          if (!$err.length || $err.hasClass('hidden') || !$.trim($err.text())) {
            continue;
          }
          found = true;
          var $section = $('.work-field-section[data-work-field="' + f + '"]');
          var $target = $section.length ? $section : $err;
          var el = $target[0];
          scrollWorkModalToElement(el);
          if (f === 'primary_style' || f === 'color') {
            var $sel = f === 'primary_style' ? $('#workPrimaryStyle') : $('#workColors');
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
          } else if (f !== 'image') {
            var $focus = $section.find('select:visible, textarea:visible, input[type="text"]:visible').first();
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
          var banner = document.getElementById('workFormBanner');
          if (banner && !banner.classList.contains('hidden') && $.trim($(banner).text())) {
            scrollWorkModalToElement(banner);
          }
        }
      }

      function collectWorkFormClientErrors() {
        var errors = {};
        var editing = !!$.trim($('#workPortfolioId').val());
        var dataUrl = $('#workImageData').val();
        var blob = dataUrl ? dataUrlToBlob(dataUrl) : null;
        if (!blob) {
          if (!editing) {
            errors.image = 'Please add and crop an image.';
          } else {
            var previewSrc = ($('#workImagePreviewImg').attr('src') || '').trim();
            if (!previewSrc) {
              errors.image = 'Please add an image or keep the existing one.';
            }
          }
        }
        var title = $.trim($('#workTitle').val());
        if (!title) {
          errors.title = 'Please enter a title.';
        } else if (title.length > 255) {
          errors.title = 'Title must not exceed 255 characters.';
        }
        var desc = $.trim($('#workDescription').val());
        if (desc.length > 2000) {
          errors.description = 'Description must not exceed 2000 characters.';
        }
        var primary = $('#workPrimaryStyle').val();
        if (!primary) {
          errors.primary_style = 'Please select a primary style.';
        }
        var otherSelected = [];
        $('#workOtherStyles option:selected').each(function () {
          otherSelected.push($(this).val());
        });
        if (otherSelected.length > 2) {
          errors.other_styles = 'You can select at most 2 other styles.';
        }
        if (primary && otherSelected.indexOf(primary) !== -1) {
          errors.other_styles = 'Other styles cannot include the same value as primary style.';
        }
        var placement = $('#workPlacement').val() || '';
        var allowedPlacements = Array.isArray(PORTFOLIO_PLACEMENT_OPTIONS) ? PORTFOLIO_PLACEMENT_OPTIONS : [];
        if (placement && allowedPlacements.indexOf(placement) === -1) {
          errors.placement = 'Please select a valid placement.';
        }
        var color = $('#workColors').val();
        if (!color) {
          errors.color = 'Please select a color option.';
        } else if (['color', 'black-grey', 'both'].indexOf(color) === -1) {
          errors.color = 'Please select a valid color option.';
        }
        var allowedStyles = Array.isArray(PORTFOLIO_STYLE_OPTIONS) ? PORTFOLIO_STYLE_OPTIONS : [];
        if (primary && allowedStyles.indexOf(primary) === -1) {
          errors.primary_style = 'Please select a valid primary style.';
        }
        otherSelected.forEach(function (v) {
          if (allowedStyles.indexOf(v) === -1) {
            errors.other_styles = 'One or more other styles are not valid.';
          }
        });
        var rawTags = $('#workTags').val();
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
        return errors;
      }

      function showWorkFormErrorsFromPayload(payload) {
        clearWorkFormErrors();
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
        applyWorkFormErrorMap(map);
        requestAnimationFrame(function () {
          scrollToFirstWorkFormError();
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

      function resetAddWorkFormFields() {
        $('#workPortfolioId').val('');
        $('#workPortfolioUpdateUrl').val('');
        $('#addWorkModalTitle').text('Add Work');
        $('#btnSaveWork .save-btn-label').text('Save');
        $('#workTitle').val('');
        $('#workDescription').val('');
        $('#workPrimaryStyle').val('').trigger('change');
        $('#workColors').val('').trigger('change');
        $('#workTags').val('');
        $('#workOtherStyles option').prop('selected', false);
        $('#workOtherStylesChips .style-chip').removeClass('is-selected').prop('disabled', false);
        updateOtherStylesChipsUI();
        $('#workPlacement').val('');
        $('#workPlacementsChips .style-chip').removeClass('is-selected').prop('disabled', false);
        updateWorkPlacementsChipsUI();
        $('#workToggleVisibility').addClass('active');
        resetWorkImageState();
      }

      function populateEditPortfolioForm(id) {
        var p = window.PORTFOLIO_EDITOR_DATA && window.PORTFOLIO_EDITOR_DATA[String(id)];
        if (!p) return;
        $('#workTitle').val(p.title || '');
        $('#workDescription').val(p.description || '');
        ensureWorkStyleSelectValue($('#workPrimaryStyle'), p.primary_style || '');
        $('#workPrimaryStyle').trigger('change');
        $('#workColors').val(p.color || '').trigger('change');
        $('#workTags').val((p.tags && p.tags.length) ? p.tags.join(', ') : '');
        $('#workOtherStyles option').prop('selected', false);
        (p.other_styles || []).forEach(function (v) {
          workOtherStyleOptionByValue(v).prop('selected', true);
        });
        syncOtherStylesChipsFromSelect();
        $('#workPlacement').val(p.placement || '');
        syncWorkPlacementsChipsFromSelect();
        $('#workToggleVisibility').toggleClass('active', !!p.is_active);
        $('#workImageData').val('');
        var $prevImg = $('#workImagePreviewImg');
        $prevImg.off('load.portfoliofit').on('load.portfoliofit', function () {
          setWorkImageUploadHasPreview(true);
        });
        $prevImg.attr('src', p.image_url || '');
        var el = $prevImg[0];
        if (el && el.complete && el.naturalWidth) {
          setWorkImageUploadHasPreview(true);
        }
        $('#workImageUploadEmpty').addClass('hidden');
        $('#workImageUploadPreview').removeClass('hidden');
        $('#btnSaveWork .save-btn-label').text('Update');
      }

      function saveWork() {
        clearWorkFormErrors();
        var clientErrors = collectWorkFormClientErrors();
        if (Object.keys(clientErrors).length) {
          applyWorkFormErrorMap(clientErrors);
          requestAnimationFrame(function () {
            scrollToFirstWorkFormError();
          });
          return;
        }
        var editing = !!$.trim($('#workPortfolioId').val());
        var updateUrl = $.trim($('#workPortfolioUpdateUrl').val());
        var dataUrl = $('#workImageData').val();
        var blob = dataUrl ? dataUrlToBlob(dataUrl) : null;
        var fd = new FormData();
        if (blob) {
          fd.append('image', blob, 'work.jpg');
        }
        fd.append('title', $.trim($('#workTitle').val()));
        fd.append('description', $.trim($('#workDescription').val()));
        fd.append('is_active', $('#workToggleVisibility').hasClass('active') ? '1' : '0');
        fd.append('primary_style', $('#workPrimaryStyle').val());
        $('#workOtherStyles option:selected').each(function () {
          fd.append('other_styles[]', $(this).val());
        });
        fd.append('placement', $('#workPlacement').val() || '');
        fd.append('color', $('#workColors').val());
        var rawTags = $('#workTags').val();
        if (rawTags) {
          rawTags.split(',').forEach(function (t) {
            t = $.trim(t);
            if (t) fd.append('tags[]', t);
          });
        }
        var url = editing && updateUrl ? updateUrl : PORTFOLIO_STORE_URL;
        if (editing && updateUrl) {
          fd.append('_method', 'PUT');
        }
        var $btn = $('#btnSaveWork');
        var btnHtml = $btn.html();
        var busyLabel = editing ? 'Updating…' : 'Saving…';
        $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-lg animate-pulse">hourglass_empty</span> ' + busyLabel);
        fetch(url, {
          method: 'POST',
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
            return { ok: false, status: res.status, data: { message: 'Unexpected response from server.' } };
          });
        }).then(function (result) {
          if (result.ok && result.data && result.data.success) {
            showSaveToast();
            resetAddWorkFormFields();
            closeAddWorkModal();
            setTimeout(function () {
              window.location.reload();
            }, 500);
            return;
          }
          if (result.status === 422 && result.data && result.data.errors) {
            showWorkFormErrorsFromPayload(result.data);
            return;
          }
          var msg = (result.data && (result.data.message || result.data.error)) ? (result.data.message || result.data.error) : 'Something went wrong. Try again.';
          clearWorkFormErrors();
          $('#workFormBanner').removeClass('hidden').append($('<p></p>').text(msg));
          requestAnimationFrame(function () {
            scrollToFirstWorkFormError();
            var inner = document.querySelector('#addWorkModal .add-work-modal-inner');
            if (inner) inner.scrollTo({ top: 0, behavior: 'smooth' });
          });
        }).catch(function () {
          clearWorkFormErrors();
          $('#workFormBanner').removeClass('hidden').append($('<p></p>').text('Network error. Try again.'));
          requestAnimationFrame(function () {
            var inner = document.querySelector('#addWorkModal .add-work-modal-inner');
            if (inner) inner.scrollTo({ top: 0, behavior: 'smooth' });
          });
        }).finally(function () {
          $btn.prop('disabled', false).html(btnHtml);
        });
      }

      function updatePortfolioCardVisibility($card, isActive) {
        if (!$card || !$card.length) return;
        $card.attr('data-is-active', isActive ? '1' : '0');
        $card.find('.btn-toggle-portfolio-visibility')
          .toggleClass('active', !!isActive)
          .attr('aria-checked', isActive ? 'true' : 'false');
        $card.find('.portfolio-visibility-label')
          .text(isActive ? 'Visible' : 'Hidden')
          .toggleClass('text-primary', !!isActive)
          .toggleClass('text-on-surface-variant', !isActive);
      }

      function persistPortfolioOrder() {
        var ids = [];
        $('#portfolioGrid .portfolio-card').each(function (index) {
          var id = parseInt($(this).attr('data-portfolio-id'), 10);
          if (!id) return;
          ids.push(id);
          $(this).attr('data-sort-order', index + 1);
        });
        if (!ids.length || !PORTFOLIO_REORDER_URL) return;

        $.ajax({
          url: PORTFOLIO_REORDER_URL,
          method: 'POST',
          contentType: 'application/json; charset=UTF-8',
          data: JSON.stringify({ ids: ids }),
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).done(function (data) {
          if (data && data.message) {
            showToastMessage(data.message);
          }
        }).fail(function (xhr) {
          var msg = (xhr.responseJSON && xhr.responseJSON.message)
            ? xhr.responseJSON.message
            : 'Could not save portfolio order.';
          showToastMessage(msg);
        });
      }

      if (typeof Sortable !== 'undefined') {
        var portfolioGridEl = document.getElementById('portfolioGrid');
        if (portfolioGridEl) {
          Sortable.create(portfolioGridEl, {
            draggable: '.portfolio-card',
            handle: '.portfolio-drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            filter: '#portfolioEmptyState',
            onEnd: function () {
              if (igImportBusy) return;
              persistPortfolioOrder();
            }
          });
        }
      }

      $(document).on('click', '.btn-toggle-portfolio-visibility', function () {
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
            throw new Error((result.data && result.data.message) || 'Could not update visibility.');
          }
          var isActive = !!result.data.is_active;
          var portfolioId = String($toggle.data('portfolio-id') || '');
          var $card = $toggle.closest('.portfolio-card');
          updatePortfolioCardVisibility($card, isActive);
          if (portfolioId && window.PORTFOLIO_EDITOR_DATA && window.PORTFOLIO_EDITOR_DATA[portfolioId]) {
            window.PORTFOLIO_EDITOR_DATA[portfolioId].is_active = isActive;
          }
        }).catch(function (e) {
          window.alert(e.message || 'Could not update visibility.');
        }).finally(function () {
          $toggle.data('loading', false);
        });
      });

      $('#btnAddWork').on('click', function () {
        resetAddWorkFormFields();
        openAddWorkModal();
      });

      $(document).on('click', '.btn-edit-portfolio', function () {
        var id = $(this).data('portfolio-id');
        var updateUrl = $(this).data('update-url');
        if (!id) return;
        resetAddWorkFormFields();
        $('#workPortfolioId').val(String(id));
        $('#workPortfolioUpdateUrl').val(updateUrl || '');
        $('#addWorkModalTitle').text('Edit Work');
        populateEditPortfolioForm(id);
        openAddWorkModal();
      });

      function openDeletePortfolioModal(delUrl) {
        if (!delUrl) return;
        $deletePortfolioModal.data('delete-url', delUrl);
        $('#deletePortfolioError').addClass('hidden').text('');
        $('#btnDeletePortfolioConfirm').prop('disabled', false);
        $('#btnDeletePortfolioConfirm .confirm-delete-icon').text('delete');
        $('#btnDeletePortfolioConfirm .confirm-delete-label').text('Delete');
        clearTimeout($deletePortfolioModal.data('closeTimer'));
        $deletePortfolioModal.addClass('modal-visible').attr('aria-hidden', 'false');
        $('body').css('overflow', 'hidden');
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            $deletePortfolioModal.addClass('modal-open');
          });
        });
      }

      function closeDeletePortfolioModal() {
        $deletePortfolioModal.removeClass('modal-open');
        clearTimeout($deletePortfolioModal.data('closeTimer'));
        var t = setTimeout(function () {
          $deletePortfolioModal.removeClass('modal-visible').attr('aria-hidden', 'true');
          if (!$addWorkModal.hasClass('modal-open') && !$addWorkModal.hasClass('modal-visible') && !$disconnectInstagramModal.hasClass('modal-open')) {
            $('body').css('overflow', '');
          }
          $deletePortfolioModal.removeData('delete-url');
        }, MODAL_MS);
        $deletePortfolioModal.data('closeTimer', t);
      }

      function runPortfolioDeleteRequest(delUrl) {
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

      $(document).on('click', '.btn-delete-portfolio', function () {
        var delUrl = $(this).data('delete-url');
        if (!delUrl) return;
        openDeletePortfolioModal(delUrl);
      });

      $('#btnDeletePortfolioCancel').on('click', closeDeletePortfolioModal);
      $deletePortfolioModal.on('click', function (e) {
        if (e.target === this) {
          closeDeletePortfolioModal();
        }
      });
      $deletePortfolioModal.find('.add-work-modal-inner').on('click', function (e) {
        e.stopPropagation();
      });

      function openDisconnectInstagramModal() {
        if (igImportBusy) return;
        clearTimeout($disconnectInstagramModal.data('closeTimer'));
        $disconnectInstagramModal.addClass('modal-visible').attr('aria-hidden', 'false');
        $('body').css('overflow', 'hidden');
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            $disconnectInstagramModal.addClass('modal-open');
          });
        });
      }

      function closeDisconnectInstagramModal() {
        $disconnectInstagramModal.removeClass('modal-open');
        clearTimeout($disconnectInstagramModal.data('closeTimer'));
        var t = setTimeout(function () {
          $disconnectInstagramModal.removeClass('modal-visible').attr('aria-hidden', 'true');
          if (!$addWorkModal.hasClass('modal-open') && !$deletePortfolioModal.hasClass('modal-open') && !$igImportModal.hasClass('modal-open')) {
            $('body').css('overflow', '');
          }
        }, MODAL_MS);
        $disconnectInstagramModal.data('closeTimer', t);
      }

      $('#btnInstagramDisconnect').on('click', function (e) {
        e.preventDefault();
        openDisconnectInstagramModal();
      });
      $('#btnDisconnectInstagramCancel').on('click', closeDisconnectInstagramModal);
      $disconnectInstagramModal.on('click', function (e) {
        if (e.target === this) {
          closeDisconnectInstagramModal();
        }
      });
      $disconnectInstagramModal.find('.add-work-modal-inner').on('click', function (e) {
        e.stopPropagation();
      });
      $('#btnDisconnectInstagramConfirm').on('click', function () {
        var $form = $('#formInstagramDisconnect');
        if (!$form.length) return;
        $(this).prop('disabled', true);
        $form.trigger('submit');
      });

      $('#btnDeletePortfolioConfirm').on('click', function () {
        var delUrl = $deletePortfolioModal.data('delete-url');
        if (!delUrl) return;
        var $btn = $('#btnDeletePortfolioConfirm');
        $('#deletePortfolioError').addClass('hidden').text('');
        $btn.prop('disabled', true);
        $btn.find('.confirm-delete-icon').text('hourglass_empty').addClass('animate-pulse');
        $btn.find('.confirm-delete-label').text('Deleting…');
        runPortfolioDeleteRequest(delUrl).then(function (result) {
          if (result.ok && result.data && result.data.success) {
            closeDeletePortfolioModal();
            window.location.reload();
            return;
          }
          var msg = (result.data && result.data.message) ? result.data.message : 'Could not delete this piece.';
          $('#deletePortfolioError').removeClass('hidden').text(msg);
          $btn.prop('disabled', false);
          $btn.find('.confirm-delete-icon').text('delete').removeClass('animate-pulse');
          $btn.find('.confirm-delete-label').text('Delete');
        }).catch(function () {
          $('#deletePortfolioError').removeClass('hidden').text('Network error. Try again.');
          $btn.prop('disabled', false);
          $btn.find('.confirm-delete-icon').text('delete').removeClass('animate-pulse');
          $btn.find('.confirm-delete-label').text('Delete');
        });
      });
      $('#btnCloseAddWork, #btnCancelAddWork').on('click', closeAddWorkModal);
      $addWorkModal.on('click', function (e) {
        if (e.target === this) {
          closeAddWorkModal();
        }
      });
      $addWorkModal.find('.add-work-modal-inner').on('click', function (e) {
        e.stopPropagation();
      });

      $('#workImageUpload').on('click', function (e) {
        e.preventDefault();
        var input = document.getElementById('workImage');
        if (input) input.click();
      });

      $('#workImage').on('change', function () {
        var file = this.files && this.files[0];
        if (file) {
          openWorkCropModalWithFile(file);
        }
      });

      $('#workImageUpload')
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
            openWorkCropModalWithFile(file);
          }
        });

      $('#btnCropApply').on('click', applyWorkCrop);
      $('#btnCropCancel, #btnCropClose').on('click', function () {
        closeWorkCropModal();
        $('#workImage').val('');
      });

      $workCropModal.on('click', function (e) {
        if (e.target === this) {
          closeWorkCropModal();
          $('#workImage').val('');
        }
      });

      $('#workOtherStylesChips').on('click', '.style-chip', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        var val = $btn.attr('data-value');
        var $opt = workOtherStyleOptionByValue(val);
        if ($btn.hasClass('is-selected')) {
          $btn.removeClass('is-selected');
          $opt.prop('selected', false);
        } else {
          if ($('#workOtherStylesChips .style-chip.is-selected').length >= 2) return;
          $btn.addClass('is-selected');
          $opt.prop('selected', true);
        }
        updateOtherStylesChipsUI();
      });

      $('#workPlacementsChips').on('click', '.style-chip', function () {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;
        var val = $btn.attr('data-value');
        if ($btn.hasClass('is-selected')) {
          $('#workPlacement').val('');
        } else {
          $('#workPlacement').val(val);
        }
        syncWorkPlacementsChipsFromSelect();
      });

      $('#workToggleVisibility, #workToggleSensitive').on('click', function () {
        $(this).toggleClass('active');
      });

      $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if ($workCropModal.hasClass('is-open')) {
          closeWorkCropModal();
          $('#workImage').val('');
          return;
        }
        if ($deletePortfolioModal.hasClass('modal-open')) {
          closeDeletePortfolioModal();
          return;
        }
        if ($disconnectInstagramModal.hasClass('modal-open')) {
          closeDisconnectInstagramModal();
          return;
        }
        if ($addWorkModal.hasClass('modal-open')) {
          closeAddWorkModal();
        }
      });

      $('#btnSaveWork').on('click', saveWork);

      syncOtherStylesChipsFromSelect();
      syncWorkPlacementsChipsFromSelect();
    });
  </script>

@endsection