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
    }
    .design-image-upload-slot.has-preview {
      aspect-ratio: var(--design-preview-ar, 4 / 5);
      max-height: min(20rem, min(70vw, 85vh));
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

      <!-- Content Tabs -->
      <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
        <a href="{{ route('artist.forms.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Forms</a>
        <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Available Designs</a>
        <a href="{{ route('portfolio.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Portfolio</a>
        <a href="{{ route('personal-page.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Content & Style</a>
      </div>


      <!-- Section intro -->
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <p class="text-on-surface-variant">Upload and manage designs clients can book directly.</p>
          <button type="button" id="btnOpenNewDesign" class="bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2 flex-shrink-0">
            <span class="material-symbols-outlined text-lg">add</span> New Design
          </button>
        </div>
      </div>

      <!-- What's Included -->
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 md:p-6 mb-8" id="whatsIncludedPanel">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-5">
          <div class="min-w-0 flex-1">
            <h3 class="text-lg font-bold text-on-surface">What's Included</h3>
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
          data-max-price="{{ (int) $design->max_price }}"
          data-search="{{ e($searchBlob) }}"
        >
          <div class="design-card bg-white rounded-2xl border border-outline-variant/20 overflow-hidden shadow-sm">
          <div class="aspect-[4/5] bg-surface-container-high rounded-t-2xl">
            <img src="{{ asset($design->image) }}" alt="" class="w-full h-full object-cover">
          </div>
          <div class="p-4">
            <div class="flex flex-wrap gap-1.5 mb-3">
              @if ($isSoldOut)
              <span class="toggle-badge off">Sold Out</span>
              @endif
              <span class="design-availability-badge toggle-badge {{ $design->is_active ? 'on' : 'off' }}">
                <span class="design-availability-label">{{ $design->is_active ? 'Available' : 'Unavailable' }}</span>
              </span>
              <span class="toggle-badge {{ $design->is_visible ? 'on' : 'off' }}">Visibility</span>
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
              <p><span class="font-semibold text-on-surface">Size:</span> {{ $design->sizeLabel() }}</p>
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
        <div class="flex flex-col lg:flex-row gap-6">
          <!-- Left: Image Upload -->
          <div class="lg:w-2/5 design-field-section scroll-mt-6" data-design-field="image">
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
            </div>
            <input type="file" id="designImage" name="designImage" accept="image/*" class="hidden">
            <input type="hidden" id="designImageData" name="designImageData" value="">
            <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="image"></p>
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
                <div class="flex items-center gap-2">
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
            <!-- Size (width / height) -->
            <div class="design-field-section scroll-mt-6" data-design-field="min_size">
              <label class="block text-sm font-semibold text-on-surface mb-2">Size</label>
              <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                  <span class="text-xs text-on-surface-variant">Width</span>
                  <input type="number" id="size_min" name="size_min" placeholder="e.g. 10" min="1" class="w-24 text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 text-center">
                </div>
                <span class="text-on-surface-variant">×</span>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-on-surface-variant">Height</span>
                  <input type="number" id="size_max" name="size_max" placeholder="e.g. 20" min="1" class="w-24 text-sm border border-outline-variant/30 rounded-xl px-3 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 text-center">
                </div>
                <span class="text-sm font-medium text-on-surface-variant" id="sizeUnitLabel">cm</span>
              </div>
              <p class="text-xs text-on-surface-variant mt-1.5">Enter width, height, or both.</p>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="min_size"></p>
              <p class="hidden design-field-error mt-1.5 text-xs text-error" data-error-for="max_size"></p>
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
            'color' => $d->color,
            'tags' => array_values($d->tags ?? []),
            'min_price' => (int) $d->min_price,
            'max_price' => (int) $d->max_price,
            'min_size' => $d->min_size !== null ? (int) $d->min_size : null,
            'max_size' => $d->max_size !== null ? (int) $d->max_size : null,
            'min_sessions' => (int) $d->min_sessions,
            'max_sessions' => (int) $d->max_sessions,
            'session_duration' => $d->session_duration,
            'can_delete' => ($d->booking_requests_count ?? 0) === 0 && ($d->bookings_count ?? 0) === 0,
            'toggle_availability_url' => route('artist-designs.toggle-availability', $d),
        ];
    });
@endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    var ARTIST_DESIGNS_STORE_URL = @json(route('artist-designs.store'));
    var ARTIST_DESIGNS_INDEX_URL = @json(route('artist-designs.index'));
    var WHATS_INCLUDED_UPDATE_URL = @json(route('artist-designs.whats-included.update'));
    var WHATS_INCLUDED_INITIAL = @json(['is_active' => $whatsIncludedIsActive, 'items' => $whatsIncludedItems]);
    var ARTIST_DESIGNS_BY_ID = @json($designsForEdit);
    var ARTIST_DESIGN_STYLE_OPTIONS = @json($styles);
    $(function () {
      var MODAL_MS = 350;
      var $newDesignModal = $('#newDesignModal');
      var $designCropModal = $('#designCropModal');
      var $deleteDesignModal = $('#deleteDesignModal');
      var $designCropperImg = $('#designCropperImg');
      var $mobileSidebar = $('#mobileSidebar');
      var $sidebarBackdrop = $('#sidebarBackdrop');
      var designCropper = null;
      var CROP_OUT_W = 1080;
      var CROP_OUT_H = 1350;
      var CROP_RATIO = 4 / 5;
      var MAX_FILE_BYTES = 10 * 1024 * 1024;

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
      }

      function resetDesignImageState() {
        closeDesignCropModal();
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

      var DESIGN_FORM_FIELD_ORDER = ['image', 'title', 'description', 'repeat_limit', 'primary_style', 'other_styles', 'color', 'tags', 'min_price', 'max_price', 'min_size', 'max_size', 'max_sessions', 'session_duration'];

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
        $('#designTitle').val(d.title || '');
        $('#designDescription').val(d.description || '');
        ensureDesignStyleSelectValue($('#designPrimaryStyle'), d.primary_style || '');
        $('#designPrimaryStyle').trigger('change');
        $('#designColors').val(d.color || '').trigger('change');
        $('#designTags').val(Array.isArray(d.tags) ? d.tags.join(', ') : '');
        $('#designPriceMin').val(d.min_price != null ? d.min_price : '');
        $('#designPriceMax').val(d.max_price != null ? d.max_price : '');
        $('#size_min').val(d.min_size != null ? d.min_size : '');
        $('#size_max').val(d.max_size != null ? d.max_size : '');
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
        var widthV = $('#size_min').val();
        var heightV = $('#size_max').val();
        var width = parseInt(widthV, 10);
        var height = parseInt(heightV, 10);
        var hasWidth = widthV !== '' && !isNaN(width) && width >= 1;
        var hasHeight = heightV !== '' && !isNaN(height) && height >= 1;
        if (!hasWidth && !hasHeight) {
          errors.min_size = 'Enter the width or height. At least one is required. You can enter both if you\'d like.';
        } else {
          if (widthV !== '' && (isNaN(width) || width < 1)) {
            errors.min_size = 'Width must be at least 1 cm.';
          }
          if (heightV !== '' && (isNaN(height) || height < 1)) {
            errors.max_size = 'Height must be at least 1 cm.';
          }
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
        $('#designTitle').val('');
        $('#designDescription').val('');
        $('#designPrimaryStyle').val('').trigger('change');
        $('#designColors').val('').trigger('change');
        $('#designTags').val('');
        $('#designPriceMin').val('');
        $('#designPriceMax').val('');
        $('#size_min').val('');
        $('#size_max').val('');
        $('#designSessionsMax').val('');
        $('#designSessionTime').val('');
        $('#toggleVisibility, #toggleAvailable').addClass('active');
        $('#toggleRepeatable, #toggleSensitive').removeClass('active');
        $('#designRepeatLimit').val('');
        syncRepeatLimitFieldVisibility();
        $('#designOtherStyles option').prop('selected', false);
        syncDesignOtherStylesChipsFromSelect();
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
        fd.append('max_size', $('#size_max').val());
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

      $('#mobileMenuBtn').on('click', function () {
        $mobileSidebar.toggleClass('hidden flex');
        $sidebarBackdrop.toggleClass('hidden');
      });
      $sidebarBackdrop.on('click', function () {
        $mobileSidebar.addClass('hidden').removeClass('flex');
        $sidebarBackdrop.addClass('hidden');
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
          return;
        }
        var filter = $('#designFilterPills .filter-pill.active').data('filter') || 'all';
        var q = ($('#searchDesigns').val() || '').trim().toLowerCase();
        var sort = $('#sortDesigns').val() || 'newest';
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
          return (parseInt($b.attr('data-created'), 10) || 0) - (parseInt($a.attr('data-created'), 10) || 0);
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