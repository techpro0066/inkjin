@extends('layouts.artist_dashboard_layout')

@section('title', 'Requests')

@section('styles')
<style>
    .request-card { transition: all 0.15s ease; }
    .request-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .status-new { background: #f3e8ff; color: #6b21a8; }
    .status-new .status-dot { background: #9333ea; }
    .status-confirmed { background: #f0fdf4; color: #15803d; }
    .status-confirmed .status-dot { background: #22c55e; }
    .status-declined { background: #fef2f2; color: #b91c1c; }
    .status-declined .status-dot { background: #ef4444; }
    .filter-pill { transition: all 0.2s; }
    .filter-pill.active { background: #22c55e; color: #ffffff; }
    .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 200; }
    .modal-backdrop.open { display: flex; }
    .custom-quote-panel {
      background: linear-gradient(135deg, #f8f1fb 0%, #f2ecf5 100%);
      border: 1px solid rgba(202, 196, 211, 0.45);
      border-radius: 1rem;
      padding: 1.25rem;
    }
    .custom-quote-panel input,
    .custom-quote-panel textarea,
    .custom-quote-panel select {
      width: 100%;
      border: 1px solid rgba(202, 196, 211, 0.45);
      border-radius: 0.75rem;
      padding: 0.625rem 0.875rem;
      font-size: 0.875rem;
      color: #1c1b21;
      background: #fff;
    }
    .custom-quote-panel input:focus,
    .custom-quote-panel textarea:focus,
    .custom-quote-panel select:focus {
      outline: none;
      border-color: rgba(49, 15, 122, 0.55);
      box-shadow: 0 0 0 3px rgba(49, 15, 122, 0.12);
    }
    .custom-quote-panel textarea { resize: vertical; min-height: 100px; }
    .custom-quote-panel input.quote-input-invalid,
    .custom-quote-panel textarea.quote-input-invalid {
      border-color: #ba1a1a;
      box-shadow: 0 0 0 2px rgba(186, 26, 26, 0.12);
    }
    .quote-field-error:not(.hidden) { display: block; }
    .custom-quote-readonly {
      background: #fff;
      border: 1px solid rgba(202, 196, 211, 0.35);
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      font-size: 0.875rem;
      color: #1c1b21;
    }
    .avail-section {
      background: linear-gradient(135deg, #f8f1fb 0%, #f2ecf5 100%);
      border: 1px solid rgba(202, 196, 211, 0.45);
      border-radius: 1rem;
      padding: 1.25rem;
      margin: 1.25rem 0;
    }
    .avail-section-title {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #310f7a;
      margin-bottom: 1rem;
    }
    .avail-block { margin-bottom: 1rem; }
    .avail-block:last-child { margin-bottom: 0; }
    .avail-block-label {
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #7a7583;
      margin-bottom: 0.5rem;
    }
    .avail-pref-list { display: flex; flex-direction: column; gap: 0.5rem; }
    .avail-pref-card {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      background: #fff;
      border: 1px solid rgba(202, 196, 211, 0.35);
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
    }
    .avail-pref-num {
      flex-shrink: 0;
      width: 1.75rem;
      height: 1.75rem;
      border-radius: 0.5rem;
      background: #310f7a;
      color: #fff;
      font-size: 0.75rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .avail-pref-body { flex: 1; min-width: 0; }
    .avail-pref-date { font-size: 0.9rem; font-weight: 600; color: #1c1b21; }
    .avail-pref-times { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.4rem; }
    .avail-time-pill {
      font-size: 0.7rem;
      font-weight: 600;
      padding: 0.2rem 0.55rem;
      border-radius: 9999px;
      background: #ede9fe;
      color: #5b21b6;
      border: 1px solid #ddd6fe;
    }
    .avail-day-row { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .avail-day-pill {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 0.35rem 0.65rem;
      border-radius: 0.5rem;
      background: #fff;
      color: #310f7a;
      border: 1px solid #ddd0ff;
    }
    .avail-meta-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.5rem;
    }
    .avail-meta-value { font-size: 0.875rem; font-weight: 600; color: #1c1b21; }
    .avail-avoid-box {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      background: #fff;
      border: 1px solid rgba(202, 196, 211, 0.35);
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      font-size: 0.875rem;
      color: #1c1b21;
    }
    .avail-empty { font-size: 0.875rem; color: #7a7583; font-style: italic; }
  .artist-slots-panel {
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
  }
  .artist-slots-panel--session {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 1px solid rgba(34, 197, 94, 0.28);
  }
  .artist-slot-block {
    background: #fff;
    border: 1px solid rgba(202, 196, 211, 0.45);
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
  }
  .artist-slot-block:last-child { margin-bottom: 0; }
  .artist-slot-date {
    width: 100%;
    border: 1px solid rgba(202, 196, 211, 0.45);
    border-radius: 0.75rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #1c1b21;
  }
  .artist-slot-time-from {
    width: 100%;
  }
  .artist-slot-date.is-invalid {
    border-color: #ba1a1a;
    box-shadow: 0 0 0 2px rgba(186, 26, 26, 0.12);
  }
  .artist-slot-block.is-duplicate-date,
  .artist-slot-block.is-incomplete-block {
    border-color: #ba1a1a;
    background: #fffbfb;
    box-shadow: 0 0 0 2px rgba(186, 26, 26, 0.12);
  }
  .artist-slot-block-error:not(.hidden) {
    display: flex;
    align-items: flex-start;
    gap: 0.35rem;
  }
  .artist-slot-block-error:not(.hidden)::before {
    content: 'error';
    font-family: 'Material Symbols Outlined';
    font-size: 1rem;
    color: #ba1a1a;
    line-height: 1.2;
  }
  .artist-slot-time-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.5rem;
    background: #f8f1fb;
  }
  .artist-slot-time-row.is-conflict {
    background: #fef2f2;
    outline: 1px solid rgba(186, 26, 26, 0.35);
  }
  .artist-slot-time-field { flex: 1; min-width: 6.5rem; }
  .artist-slot-time-field label {
    display: block;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #7a7583;
    margin-bottom: 0.25rem;
  }
  .artist-slot-time-end-hint {
    font-size: 0.8rem;
    font-weight: 600;
    color: #7a7583;
    padding-bottom: 0.55rem;
    white-space: nowrap;
  }
  .artist-slots-add-date {
    width: 100%;
    margin-top: 0.5rem;
    padding: 0.625rem;
    border-radius: 0.75rem;
    border: 1px dashed rgba(49, 15, 122, 0.35);
    font-size: 0.8125rem;
    font-weight: 600;
    color: #310f7a;
    background: transparent;
    transition: background 0.15s;
  }
  .artist-slots-add-date:hover { background: rgba(49, 15, 122, 0.05); }
  .artist-slots-panel--readonly { margin-bottom: 1rem; }
  .artist-offer-slot-readonly-block {
    background: #fff;
    border: 1px solid rgba(202, 196, 211, 0.45);
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
  }
  .artist-offer-slot-readonly-block:last-child { margin-bottom: 0; }
  .artist-offer-slot-ranges {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  .artist-offer-slot-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #1c1b21;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
  }
  .artist-offer-slot-range .material-symbols-outlined {
    font-size: 1.125rem;
    color: #7a7583;
  }
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

    @include('artist.requests.partials.page-header-and-tabs', [
      'activeTab' => $activeTab ?? 'custom',
      'customPendingCount' => $pendingCount,
    ])

    <div class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="sortBy" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Sort by</label>
          <select id="sortBy" onchange="applyFilters()" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="recent">Most Recent</option>
            <option value="oldest">Oldest First</option>
          </select>
        </div>
        <div>
          <label for="searchClient" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <input type="search" id="searchClient" oninput="applyFilters()" placeholder="Client name, email, reference…" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <button type="button" onclick="filterByStatus('all')" class="filter-pill active text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="all">All</button>
        <button type="button" onclick="filterByStatus('New Request')" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="New Request">New</button>
        <button type="button" onclick="filterByStatus('Quote sent')" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="Quote sent">Quote sent</button>
        <button type="button" onclick="filterByStatus('Declined')" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="Declined">Declined</button>
      </div>
    </div>

    <div class="space-y-4" id="requestsList">
      @forelse ($requests as $customRequest)
        @php
          $filterStatus = $customRequest->filterStatusLabel();
          $badgeClass = $customRequest->statusBadgeClass();
          $answerCount = count($customRequest->formattedQuestionAnswers());
        @endphp
        <div class="request-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-5 cursor-pointer"
             data-request-id="{{ $customRequest->id }}"
             data-status="{{ $filterStatus }}"
             data-client="{{ $customRequest->clientSearchKey() }}"
             data-date="{{ $customRequest->created_at?->format('Y-m-d') ?? '' }}"
             onclick="openCustomRequestDetail({{ $customRequest->id }})">
          <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-outline-variant/20">
              <span class="text-primary font-bold text-lg">{{ $customRequest->clientInitials() }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                <div class="flex items-center gap-3 flex-wrap">
                  <p class="font-semibold text-on-surface">{{ $customRequest->clientDisplayName() }}</p>
                  <span class="request-status-badge inline-flex items-center gap-1.5 {{ $badgeClass }} text-xs font-semibold px-3 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full status-dot"></span> <span class="request-status-label">{{ $filterStatus }}</span>
                  </span>
                  <span class="text-xs text-outline">{{ $customRequest->referenceLabel() }}</span>
                </div>
                <p class="text-xs text-outline flex-shrink-0">{{ $customRequest->created_at?->format('M j, Y') }}</p>
              </div>
              <p class="text-sm text-on-surface-variant mb-3">
                {{ ($scope ?? 'custom') === 'guest' ? 'Guest spot request' : 'Custom tattoo request' }}
                @if(($scope ?? 'custom') === 'guest' && $customRequest->guestSpot)
                  · {{ $customRequest->guestSpot->city }}, {{ $customRequest->guestSpot->country }}
                @endif
                @if($answerCount > 0)
                  · {{ $answerCount }} {{ $answerCount === 1 ? 'answer' : 'answers' }}
                @endif
                @if($customRequest->contactPhone())
                  · {{ $customRequest->contactPhone() }}
                @endif
              </p>
              <div class="flex flex-col items-end gap-1.5" data-card-actions>
                <div class="flex flex-wrap items-center gap-2 justify-end">
                  @if($customRequest->status === 'pending')
                  <button type="button" data-decline-btn onclick="event.stopPropagation(); openDeclineModal({{ $customRequest->id }})" class="text-xs font-semibold text-error border border-error/30 hover:bg-error/5 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">block</span> Decline
                  </button>
                  @endif
                  <button type="button" onclick="event.stopPropagation(); openCustomRequestDetail({{ $customRequest->id }})" class="text-xs font-semibold text-primary hover:text-primary-container transition-colors flex items-center gap-1">
                    View Details <span class="material-symbols-outlined text-sm">arrow_forward</span>
                  </button>
                </div>
                @if(
                  $customRequest->status === 'pending'
                  && $customRequest->isGuestRequest()
                  && $customRequest->guestSpot
                  && $customRequest->guestSpot->isFull()
                )
                  <p data-slots-full-msg class="text-xs font-semibold text-right" style="color:#FFBF00;">Unable to send. All slots are full.</p>
                @endif
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-12 text-center">
          <span class="material-symbols-outlined text-4xl text-outline mb-3 block">{{ ($scope ?? 'custom') === 'guest' ? 'luggage' : 'brush' }}</span>
          <p class="text-on-surface-variant font-medium">{{ ($scope ?? 'custom') === 'guest' ? 'No guest requests yet' : 'No custom requests yet' }}</p>
          <p class="text-outline text-sm mt-1">
            @if(($scope ?? 'custom') === 'guest')
              When clients reserve a guest spot, their requests will appear here.
            @else
              When clients submit a custom tattoo request, they will appear here.
            @endif
          </p>
        </div>
      @endforelse
    </div>

    <div id="requestsEmpty" class="hidden bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-12 text-center">
      <span class="material-symbols-outlined text-4xl text-outline mb-3 block">search_off</span>
      <p class="text-on-surface-variant font-medium">No requests match your filters</p>
    </div>

  </div>
</main>

<div class="modal-backdrop" id="customRequestModal" onclick="closeModalOnBackdrop(event)">
  <div class="w-full h-full overflow-y-auto bg-white lg:bg-transparent lg:p-8 lg:flex lg:items-start lg:justify-center" onclick="closeModalOnBackdrop(event)">
    <div class="bg-white lg:rounded-2xl w-full lg:max-w-5xl lg:shadow-2xl min-h-screen lg:min-h-0 lg:max-h-[90vh] lg:overflow-y-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15 sticky top-0 bg-white z-10 lg:rounded-t-2xl">
        <h3 class="text-lg font-bold text-on-surface">{{ ($scope ?? 'custom') === 'guest' ? 'Guest Request Details' : 'Custom Request Details' }}</h3>
        <button type="button" onclick="closeCustomRequestDetail()" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low transition-colors">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="p-6">
        <div id="customRequestDetailPanel" class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 w-full">
          <div class="space-y-6" id="customRequestDetailLeft"></div>
          <div class="space-y-6 lg:sticky lg:top-4 lg:max-h-[calc(90vh-5rem)] lg:overflow-y-auto lg:overscroll-contain pr-0.5" id="customRequestDetailQuote"></div>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15">
        <button type="button" onclick="closeCustomRequestDetail()" class="text-sm font-semibold text-primary hover:text-primary-container transition-colors flex items-center gap-1">
          <span class="material-symbols-outlined text-lg">arrow_back</span> Back to {{ ($scope ?? 'custom') === 'guest' ? 'Guest Requests' : 'Custom Requests' }}
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="declineModal" style="z-index: 210;" onclick="closeDeclineModalOnBackdrop(event)">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="closeDeclineModalOnBackdrop(event)">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
        <h3 class="text-lg font-bold text-on-surface flex items-center gap-2">
          <span class="material-symbols-outlined text-error">block</span> Decline Request
        </h3>
        <button type="button" onclick="closeDeclineModal()" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low transition-colors">
          <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
      </div>
      <div class="p-6 space-y-4">
        <p class="text-sm text-on-surface-variant">Share why you are declining this custom request. The client will be notified by email.</p>
        <div>
          <label for="declineReason" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Reason for decline</label>
          <textarea id="declineReason" rows="4" maxlength="2000" placeholder="e.g. Style does not match my portfolio, books are full for custom work…" class="w-full rounded-xl border border-outline-variant/30 px-4 py-3 text-sm text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30 resize-y min-h-[100px]"></textarea>
        </div>
        <p id="declineError" class="hidden text-sm text-error font-medium"></p>
      </div>
      <div class="px-6 py-4 border-t border-outline-variant/15 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        <button type="button" onclick="closeDeclineModal()" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-surface-container-low transition-colors">Cancel</button>
        <button type="button" id="declineSubmitBtn" onclick="submitDecline()" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-error hover:bg-error/90 transition-colors flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-lg">send</span> Submit decline
        </button>
      </div>
    </div>
  </div>
</div>

<div id="saveToast" class="fixed top-6 right-6 z-[220] transform translate-x-full opacity-0 transition-all duration-300">
  <div class="flex items-center gap-3 bg-on-surface text-white px-5 py-3 rounded-xl shadow-lg">
    <span id="saveToastIcon" class="material-symbols-outlined text-green-400" style="font-size:20px;">check_circle</span>
    <span id="saveToastMessage" class="text-sm font-medium">Done</span>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/question-answer-display.js') }}"></script>
<script>
  const requestScope = @json($scope ?? 'custom');
  const customRequestsById = @json(collect($requestsPayload)->keyBy('id'));
  const declineRequestUrlTemplate = @json(route('artist.custom-requests.decline', ['customRequest' => 0]));
  const sendQuoteUrlTemplate = @json(route('artist.custom-requests.send-quote', ['customRequest' => 0]));
  let currentStatusFilter = 'all';
  let declineRequestId = null;
  let activeCustomRequestId = null;

  function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function formatAnswer(answer, answerType) {
    if (window.QuestionAnswerDisplay) {
      var imageUrls = window.QuestionAnswerDisplay.imageUrlsFromAnswer(answer, answerType);
      if (imageUrls.length) {
        return imageUrls.map(function (url, idx) {
          return '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener" class="text-primary font-semibold hover:underline">Photo ' + (idx + 1) + '</a>';
        }).join(', ');
      }
      return escapeHtml(window.QuestionAnswerDisplay.formatAnswerForReview(answer, answerType));
    }
    if (typeof answer === 'boolean') return answer ? 'Yes' : 'No';
    if (Array.isArray(answer) && answer.length) {
      return answer.map(function (_, idx) { return 'Photo ' + (idx + 1); }).join(', ');
    }
    if (typeof answer === 'string' && /^https?:\/\//i.test(answer)) {
      return '<a href="' + escapeHtml(answer) + '" target="_blank" rel="noopener" class="text-primary font-semibold hover:underline">Photo 1</a>';
    }
    return escapeHtml(String(answer || '—'));
  }

  function filterByStatus(status) {
    currentStatusFilter = status;
    document.querySelectorAll('.filter-pill').forEach(function(pill) {
      pill.classList.toggle('active', pill.dataset.status === status);
    });
    applyFilters();
  }

  function applyFilters() {
    const search = (document.getElementById('searchClient')?.value || '').toLowerCase().trim();
    const cards = document.querySelectorAll('#requestsList .request-card');
    let visible = 0;
    cards.forEach(function(card) {
      const matchesStatus = currentStatusFilter === 'all' || card.dataset.status === currentStatusFilter;
      const matchesSearch = !search || (card.dataset.client || '').includes(search);
      const show = matchesStatus && matchesSearch;
      card.classList.toggle('hidden', !show);
      if (show) visible++;
    });
    const hasCards = cards.length > 0;
    document.getElementById('requestsEmpty')?.classList.toggle('hidden', !hasCards || visible > 0);
    document.getElementById('requestsList')?.classList.toggle('hidden', hasCards && visible === 0);
    const sort = document.getElementById('sortBy')?.value || 'recent';
    const list = document.getElementById('requestsList');
    if (!list) return;
    const sorted = Array.from(cards).filter(function(c) { return !c.classList.contains('hidden'); });
    sorted.sort(function(a, b) {
      const da = a.dataset.date || '';
      const db = b.dataset.date || '';
      return sort === 'oldest' ? da.localeCompare(db) : db.localeCompare(da);
    });
    sorted.forEach(function(card) { list.appendChild(card); });
  }

  function customRequestHasQuote(req) {
    if (!req) return false;
    return !!(
      (req.estimatedPrice && parseFloat(req.estimatedPrice) > 0) ||
      (req.estimatedTime && String(req.estimatedTime).trim()) ||
      (req.numberOfSessions && String(req.numberOfSessions).trim()) ||
      (req.messageForClient && String(req.messageForClient).trim())
    );
  }

  function buildAvailabilityHtml(details) {
    details = details || {};
    var preferredDates = details.preferredDates || [];
    var preferredDays = details.preferredDays || [];
    var flexibility = details.flexibility || '';
    var urgency = details.urgency || '';
    var avoidDates = details.avoidDates || '';
    var hasAny = preferredDates.length || preferredDays.length || flexibility || urgency || avoidDates;

    if (!hasAny) {
      return '<section class="avail-section"><div class="avail-section-title"><span class="material-symbols-outlined text-[20px]">event_available</span> Client Availability</div><p class="avail-empty">No availability details provided.</p></section>';
    }

    var html = '<section class="avail-section"><div class="avail-section-title"><span class="material-symbols-outlined text-[20px]">event_available</span> Client Availability</div>';

    if (preferredDates.length) {
      html += '<div class="avail-block"><p class="avail-block-label">Preferred dates & times</p><div class="avail-pref-list">';
      preferredDates.forEach(function(pref, idx) {
        var num = pref.preference || (idx + 1);
        var timesHtml = '';
        (pref.times || []).forEach(function(t) {
          timesHtml += '<span class="avail-time-pill">' + escapeHtml(t) + '</span>';
        });
        if (!timesHtml) timesHtml = '<span class="avail-time-pill">Any time</span>';
        html += '<div class="avail-pref-card"><span class="avail-pref-num">' + num + '</span><div class="avail-pref-body"><p class="avail-pref-date">' + escapeHtml(pref.dateLabel || pref.date) + '</p><div class="avail-pref-times">' + timesHtml + '</div></div></div>';
      });
      html += '</div></div>';
    }

    if (preferredDays.length) {
      html += '<div class="avail-block"><p class="avail-block-label">Preferred days of the week</p><div class="avail-day-row">';
      preferredDays.forEach(function(day) {
        html += '<span class="avail-day-pill">' + escapeHtml(day) + '</span>';
      });
      html += '</div></div>';
    }

    var metaItems = [];
    if (flexibility) metaItems.push({ label: 'Flexibility', value: flexibility });
    if (urgency) metaItems.push({ label: 'Urgency', value: urgency });
    if (metaItems.length) {
      html += '<div class="avail-block"><p class="avail-block-label">Scheduling preferences</p><div class="avail-meta-grid">';
      metaItems.forEach(function(item) {
        html += '<div class="avail-meta-item"><p class="avail-block-label">' + escapeHtml(item.label) + '</p><p class="avail-meta-value">' + escapeHtml(item.value) + '</p></div>';
      });
      html += '</div></div>';
    }

    if (avoidDates) {
      html += '<div class="avail-block"><p class="avail-block-label">Dates to avoid</p><div class="avail-avoid-box"><span class="material-symbols-outlined text-[18px] shrink-0">event_busy</span><span>' + escapeHtml(avoidDates) + '</span></div></div>';
    }

    html += '</section>';
    return html;
  }

  function buildCustomRequestLeftHtml(req) {
    var questionsHtml = '';
    (req.questionsAnswers || []).forEach(function(item) {
      if (!item || !item.question) return;
      questionsHtml += window.QuestionAnswerDisplay
        ? window.QuestionAnswerDisplay.renderAnswerHtml(item)
        : '<div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">' + escapeHtml(item.question) + '</h4><p class="text-sm font-medium text-on-surface">' + formatAnswer(item.answer, item.type) + '</p></div>';
    });

    var availabilityHtml = (req.type === 'managed' && !req.isGuest) ? buildAvailabilityHtml(req.availabilityDetails || {}) : '';
    var guestSpotHtml = req.isGuest ? buildGuestSpotHtml(req.guestSpot || null) : '';
    var requestTitle = req.isGuest ? 'Guest Spot Request' : 'Custom Tattoo Request';
    var requestSubtitle = req.isGuest ? 'Client reservation for your guest spot' : 'Client-submitted custom work inquiry';
    var requestIcon = req.isGuest ? 'luggage' : 'brush';

    return '' +
      '<div class="flex items-center gap-4">' +
        '<div class="w-14 h-14 rounded-full bg-primary flex items-center justify-center flex-shrink-0"><span class="text-white text-lg font-bold">' + escapeHtml(req.clientInitials) + '</span></div>' +
        '<div><p class="font-bold text-lg text-on-surface">' + escapeHtml(req.clientName) + '</p>' +
        '<p class="text-sm text-on-surface-variant">' + escapeHtml(req.clientEmail) + '</p>' +
        (req.clientPhone ? '<p class="text-sm text-on-surface-variant">' + escapeHtml(req.clientPhone) + '</p>' : '') +
        '</div></div>' +
      '<div class="flex items-center gap-3 flex-wrap">' +
        '<span class="inline-flex items-center gap-1.5 ' + escapeHtml(req.statusBadgeClass) + ' text-xs font-semibold px-3 py-1 rounded-full"><span class="w-1.5 h-1.5 rounded-full status-dot"></span> ' + escapeHtml(req.filterStatus) + '</span>' +
        '<span class="text-xs text-outline">Submitted ' + escapeHtml(req.submittedAt) + '</span>' +
        '<span class="text-xs text-outline">' + escapeHtml(req.reference) + '</span>' +
      '</div>' +
      '<div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/20 flex gap-4">' +
        '<div class="w-20 h-20 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-outline-variant/20">' +
          '<span class="material-symbols-outlined text-primary text-3xl">' + requestIcon + '</span>' +
        '</div>' +
        '<div><h4 class="font-bold text-on-surface text-lg">' + requestTitle + '</h4>' +
        '<p class="text-sm text-on-surface-variant mt-1">' + requestSubtitle + '</p>' +
        '<p class="text-xs text-on-surface-variant mt-2">' + escapeHtml(req.schedulingLabel || 'Auto scheduling') + '</p>' +
        (req.referralSource && req.referralSource !== '—' ? '<p class="text-xs text-on-surface-variant mt-2">Referral: ' + escapeHtml(req.referralSource) + '</p>' : '') +
        '</div></div>' +
      '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">' + (questionsHtml || '<p class="text-sm text-on-surface-variant italic sm:col-span-2">No question answers recorded.</p>') + '</div>' +
      guestSpotHtml +
      availabilityHtml +
      '<div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Additional Notes</h4>' +
        '<p class="text-sm text-on-surface leading-relaxed whitespace-pre-line">' + escapeHtml(req.additionalNotes) + '</p></div>' +
      (req.reasonDecline ? '<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-900"><p class="font-semibold mb-1">Decline reason</p><p class="whitespace-pre-line">' + escapeHtml(req.reasonDecline) + '</p></div>' : '');
  }

  function isManagedCustomRequest(req) {
    return req && req.type === 'managed';
  }

  function needsQuoteSessionSlots(req) {
    return isManagedCustomRequest(req) && !(req && req.isGuest);
  }

  function buildGuestSpotHtml(guestSpot) {
    if (!guestSpot) return '';
    var lines = [];
    if (guestSpot.city || guestSpot.country) {
      lines.push('<p class="font-semibold text-on-surface">' + escapeHtml([guestSpot.city, guestSpot.country].filter(Boolean).join(', ')) + '</p>');
    }
    if (guestSpot.fromDate && guestSpot.toDate) {
      lines.push('<p class="text-sm text-on-surface-variant mt-1">' + escapeHtml(guestSpot.fromDate + ' – ' + guestSpot.toDate) + '</p>');
    }
    if (guestSpot.availabilityTime) {
      lines.push('<p class="text-sm text-on-surface-variant mt-1">Daily hours: ' + escapeHtml(guestSpot.availabilityTime) + '</p>');
    }
    if (guestSpot.studio) {
      lines.push('<p class="text-sm text-on-surface mt-2"><span class="font-semibold">Studio:</span> ' + escapeHtml(guestSpot.studio) + '</p>');
    }
    if (guestSpot.location) {
      lines.push('<p class="text-sm text-on-surface-variant mt-1">' + escapeHtml(guestSpot.location) + '</p>');
    }
    if (guestSpot.tracksCapacity) {
      if (guestSpot.isFull) {
        lines.push('<p class="text-sm font-semibold mt-2" style="color:#FFBF00;">All slots are full</p>');
      } else if (guestSpot.remainingSpotsLabel) {
        lines.push('<p class="text-sm font-semibold text-green-700 mt-2">' + escapeHtml(guestSpot.remainingSpotsLabel) + '</p>');
      }
    }
    if (!lines.length) return '';
    return '<section class="avail-section"><div class="avail-section-title"><span class="material-symbols-outlined text-[20px]">luggage</span> Guest Spot Availability</div><div class="bg-white rounded-xl border border-outline-variant/20 p-4">' + lines.join('') + '</div></section>';
  }

  function todayYmd() {
    var d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }

  function formatOfferDate(dateStr) {
    if (!dateStr) return '—';
    var parts = String(dateStr).split('-');
    if (parts.length !== 3) return dateStr;
    var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
  }

  function formatOfferTime(timeStr) {
    if (!timeStr) return '';
    var parts = String(timeStr).split(':');
    var h = parseInt(parts[0], 10);
    var m = parts[1] || '00';
    if (isNaN(h)) return timeStr;
    var ampm = h >= 12 ? 'PM' : 'AM';
    var h12 = h % 12 || 12;
    return h12 + ':' + m + ' ' + ampm;
  }

  function buildArtistSlotsReadOnlyPanel(title, icon, slots, panelClass) {
    if (!slots || !slots.length) return '';
    var html = '<div class="artist-slots-panel artist-slots-panel--readonly ' + panelClass + '">' +
      '<h4 class="font-bold text-on-surface mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">' + icon + '</span> ' + escapeHtml(title) + '</h4>';
    slots.forEach(function(slot) {
      html += '<div class="artist-offer-slot-readonly-block">';
      html += '<p class="text-xs font-bold text-primary uppercase tracking-wider mb-2">' + escapeHtml(formatOfferDate(slot.date)) + '</p>';
      html += '<ul class="artist-offer-slot-ranges">';
      (slot.ranges || []).forEach(function(range) {
        html += '<li class="artist-offer-slot-range"><span class="material-symbols-outlined">schedule</span><span>' +
          escapeHtml(formatOfferTime(range.from)) + ' – ' + escapeHtml(formatOfferTime(range.to)) + '</span></li>';
      });
      html += '</ul></div>';
    });
    html += '</div>';
    return html;
  }

  function buildCustomQuoteSessionSlotsPanel() {
    return '<div class="artist-slots-panel artist-slots-panel--session" data-slots-kind="session" data-field-key="artist_session_slots">' +
      '<h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">brush</span> Tattoo session times</h4>' +
      '<p class="text-sm text-on-surface-variant mb-2">Offer dates and start times when you can do this tattoo. Not tied to the client\'s preferred dates.</p>' +
      '<p class="text-xs font-semibold text-primary/80 mb-3">End time uses your estimated duration above.</p>' +
      '<div id="sessionSlotsBlocks" class="slots-blocks-container"></div>' +
      '<p id="sessionSlotsError" class="hidden text-sm text-error font-medium mt-2"></p>' +
      '<button type="button" onclick="addSlotDateBlock(\'session\')" class="artist-slots-add-date flex items-center justify-center gap-1"><span class="material-symbols-outlined text-lg">calendar_add_on</span> Add another date</button>' +
      '</div>';
  }

  function timeToMinutes(value) {
    if (!value || value.indexOf(':') === -1) return null;
    var parts = value.split(':');
    var h = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10);
    if (isNaN(h) || isNaN(m)) return null;
    return h * 60 + m;
  }

  function minutesToTime(totalMinutes) {
    var mins = ((totalMinutes % (24 * 60)) + (24 * 60)) % (24 * 60);
    var h = Math.floor(mins / 60);
    var m = mins % 60;
    return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
  }

  function addMinutesToTime(timeStr, minutes) {
    var start = timeToMinutes(timeStr);
    if (start === null || !minutes || minutes <= 0) return null;
    var end = start + minutes;
    if (end >= 24 * 60) return null;
    return minutesToTime(end);
  }

  function parseQuoteDurationMinutes(text) {
    var s = String(text || '').trim();
    if (!s) return null;
    var match = s.match(/(\d+(?:\.\d+)?)/);
    if (!match) return null;
    return Math.max(60, Math.round(parseFloat(match[1]) * 60));
  }

  function getCustomQuoteDurationMinutes() {
    return parseQuoteDurationMinutes(document.getElementById('quoteEstimatedDuration')?.value);
  }

  function getCustomQuoteDurationLabel() {
    var text = String(document.getElementById('quoteEstimatedDuration')?.value || '').trim();
    return text || 'session';
  }

  function getDurationMinutesForSlotsKind(kind) {
    return getCustomQuoteDurationMinutes();
  }

  function syncTimeRowEndHint(row, kind) {
    if (!row) return;
    var hint = row.querySelector('.artist-slot-time-end-hint');
    var from = (row.querySelector('.artist-slot-time-from') || {}).value || '';
    var duration = getDurationMinutesForSlotsKind(kind);
    if (!hint) return;
    if (!from || !duration) {
      hint.textContent = '';
      return;
    }
    var end = addMinutesToTime(from, duration);
    hint.textContent = end ? ('→ ' + formatOfferTime(end)) : '→ past midnight';
  }

  function syncAllTimeRowEndHints(kind) {
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return;
    container.querySelectorAll('.artist-slot-time-row').forEach(function(row) {
      syncTimeRowEndHint(row, kind);
    });
  }

  function rangesOverlap(a, b) {
    return a.start < b.end && b.start < a.end;
  }

  function buildTimeRangeRowHtml(range, kind) {
    range = range || { from: '', to: '' };
    var from = range.from || '';
    var endHint = '';
    if (from) {
      var duration = getDurationMinutesForSlotsKind(kind);
      var end = duration ? addMinutesToTime(from, duration) : null;
      endHint = end ? ('→ ' + formatOfferTime(end)) : (duration ? '→ past midnight' : '');
    }
    return '<div class="artist-slot-time-row">' +
      '<div class="artist-slot-time-field"><label>Start</label><div class="inkjin-time-wrap"><input type="time" class="inkjin-time-input artist-slot-time-from px-3 py-2" value="' + escapeHtml(from) + '" step="300" onchange="onArtistSlotFieldChange(this)"></div></div>' +
      '<span class="artist-slot-time-end-hint">' + escapeHtml(endHint) + '</span>' +
      '<button type="button" onclick="removeSlotTimeRow(this)" class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center text-outline hover:bg-surface-container-low mb-0.5" title="Remove slot"><span class="material-symbols-outlined text-lg">close</span></button>' +
      '</div>';
  }

  function buildSlotBlockHtml(kind, index, minDate, slot) {
    slot = slot || { date: '', ranges: [{ from: '', to: '' }] };
    var ranges = (slot.ranges && slot.ranges.length) ? slot.ranges : [{ from: '', to: '' }];
    var timeRows = ranges.map(function(r) { return buildTimeRangeRowHtml(r, kind); }).join('');
    return '<div class="artist-slot-block" data-slot-index="' + index + '">' +
      '<div class="flex items-center justify-between mb-2"><p class="text-xs font-bold text-primary uppercase tracking-wider slot-block-label">Session date ' + (index + 1) + '</p>' +
      '<button type="button" onclick="removeSlotDateBlock(\'' + kind + '\', this)" class="text-xs font-semibold text-outline hover:text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">delete</span> Remove</button></div>' +
      '<label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label>' +
      '<input type="date" class="artist-slot-date" min="' + escapeHtml(minDate) + '" value="' + escapeHtml(slot.date || '') + '" onchange="onArtistSlotFieldChange(this)">' +
      '<p class="text-xs font-semibold text-on-surface-variant mt-3 mb-1">Available start times</p>' +
      '<p class="text-xs text-on-surface-variant mb-2">End time uses your estimated duration. Multiple start times on the same date cannot overlap.</p>' +
      '<div class="artist-slot-times">' + timeRows + '</div>' +
      '<button type="button" onclick="addSlotTimeRow(this)" class="mt-2 text-xs font-semibold text-primary hover:text-primary-container flex items-center gap-1"><span class="material-symbols-outlined text-sm">add</span> Add another start time</button>' +
      '<p class="artist-slot-block-error hidden text-xs text-error font-medium mt-2" role="alert"></p>' +
      '</div>';
  }

  function slotsPanelKindFromEl(el) {
    var panel = el && el.closest ? el.closest('[data-slots-kind]') : null;
    return panel ? panel.dataset.slotsKind : null;
  }

  function setPanelSlotsError(kind, message) {
    var el = document.getElementById(kind + 'SlotsError');
    if (!el) return;
    if (message) {
      el.textContent = message;
      el.classList.remove('hidden');
    } else {
      el.textContent = '';
      el.classList.add('hidden');
    }
  }

  function setBlockInlineError(block, message) {
    if (!block) return;
    var el = block.querySelector('.artist-slot-block-error');
    if (!el) return;
    if (message) {
      el.textContent = message;
      el.classList.remove('hidden');
    } else {
      el.textContent = '';
      el.classList.add('hidden');
    }
  }

  function scrollToSlotBlock(block) {
    if (!block) return;
    setTimeout(function() {
      try { block.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) { block.scrollIntoView(true); }
    }, 50);
  }

  function clearSlotValidationMarks(kind) {
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return;
    container.querySelectorAll('.artist-slot-date').forEach(function(d) { d.classList.remove('is-invalid'); });
    container.querySelectorAll('.artist-slot-block').forEach(function(b) {
      b.classList.remove('is-duplicate-date', 'is-incomplete-block');
      setBlockInlineError(b, '');
    });
    container.querySelectorAll('.artist-slot-time-row').forEach(function(r) {
      r.classList.remove('is-conflict');
      r.querySelectorAll('input').forEach(function(i) { i.classList.remove('is-invalid'); });
    });
  }

  function markDuplicateDates(kind) {
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return false;
    var byDate = {};
    container.querySelectorAll('.artist-slot-block').forEach(function(block) {
      var date = (block.querySelector('.artist-slot-date') || {}).value || '';
      block.classList.remove('is-duplicate-date');
      (block.querySelector('.artist-slot-date') || {}).classList.remove('is-invalid');
      if (!date) return;
      byDate[date] = byDate[date] || [];
      byDate[date].push(block);
    });
    var hasDup = false;
    Object.keys(byDate).forEach(function(date) {
      if (byDate[date].length > 1) {
        hasDup = true;
        byDate[date].forEach(function(block) {
          block.classList.add('is-duplicate-date');
          block.querySelector('.artist-slot-date').classList.add('is-invalid');
        });
      }
    });
    return hasDup;
  }

  function validateRangesInBlock(block, kind) {
    var ranges = [];
    var rows = block.querySelectorAll('.artist-slot-time-row');
    var messages = [];
    var durationMinutes = getDurationMinutesForSlotsKind(kind);
    var durationLabel = getCustomQuoteDurationLabel();

    if (!rows.length) return { ok: false, message: 'Add at least one start time for this date.' };
    if (!durationMinutes) return { ok: false, message: 'Enter estimated duration above first.' };

    rows.forEach(function(row, rowIndex) {
      row.classList.remove('is-conflict');
      var fromInput = row.querySelector('.artist-slot-time-from');
      fromInput.classList.remove('is-invalid');
      var from = fromInput.value;
      var rowLabel = rows.length > 1 ? ('Slot ' + (rowIndex + 1) + ': ') : '';
      if (!from) {
        fromInput.classList.add('is-invalid');
        messages.push(rowLabel + 'enter a start time, or remove this slot.');
        return;
      }
      var endTime = addMinutesToTime(from, durationMinutes);
      if (!endTime) {
        fromInput.classList.add('is-invalid');
        messages.push(rowLabel + 'start time is too late for a ' + durationLabel + ' session.');
        return;
      }
      ranges.push({ start: timeToMinutes(from), end: timeToMinutes(endTime), row: row });
      syncTimeRowEndHint(row, kind);
    });
    if (messages.length) return { ok: false, message: messages.join(' ') };
    ranges.sort(function(a, b) { return a.start - b.start; });
    for (var i = 0; i < ranges.length; i++) {
      for (var j = i + 1; j < ranges.length; j++) {
        if (rangesOverlap(ranges[i], ranges[j])) {
          ranges[i].row.classList.add('is-conflict');
          ranges[j].row.classList.add('is-conflict');
          return { ok: false, message: 'Start times on this date are too close — they would overlap given the ' + durationLabel + ' session length.' };
        }
      }
    }
    return { ok: true, ranges: ranges };
  }

  function validateBlockRequired(block, kind) {
    var dateInput = block.querySelector('.artist-slot-date');
    var date = dateInput ? dateInput.value : '';
    if (!date) {
      dateInput.classList.add('is-invalid');
      block.classList.add('is-incomplete-block');
      block.querySelectorAll('.artist-slot-time-from').forEach(function(inp) {
        if (!inp.value) inp.classList.add('is-invalid');
      });
      return { ok: false, message: 'Select a date for this entry, or remove it if you do not need it.', block: block, focusEl: dateInput };
    }
    var rangeCheck = validateRangesInBlock(block, kind);
    if (!rangeCheck.ok) {
      block.classList.add('is-incomplete-block');
      return { ok: false, message: rangeCheck.message, block: block, focusEl: block.querySelector('.artist-slot-time-from.is-invalid') || block.querySelector('.artist-slot-time-from') };
    }
    return { ok: true, block: block, ranges: rangeCheck.ranges };
  }

  function buildSlotsPanelSummaryMessage(failureCount, hasComplete) {
    if (!hasComplete && failureCount === 0) return 'Add at least one date with a start time.';
    if (failureCount > 1) return 'Fix ' + failureCount + ' entries — complete every date and start time, or remove extras you do not need.';
    if (failureCount === 1) return 'Complete the date and start time, or remove this entry.';
    return '';
  }

  function runSlotsPanelValidation(kind) {
    clearSlotValidationMarks(kind);
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return { ok: false, kind: kind, panelLabel: 'Tattoo session', message: 'Add session dates and times.', failures: [], hasComplete: false };
    var blocks = container.querySelectorAll('.artist-slot-block');
    var failures = [];
    var hasComplete = false;
    var seenFailureBlocks = new Set();
    function addFailure(check) {
      if (!check || !check.block) return;
      setBlockInlineError(check.block, check.message);
      check.block.classList.add('is-incomplete-block');
      if (!seenFailureBlocks.has(check.block)) {
        seenFailureBlocks.add(check.block);
        failures.push(check);
      }
    }
    for (var i = 0; i < blocks.length; i++) {
      var block = blocks[i];
      var check = validateBlockRequired(block, kind);
      if (!check.ok) addFailure(check);
      else {
        setBlockInlineError(block, '');
        block.classList.remove('is-incomplete-block');
        hasComplete = true;
      }
    }
    if (markDuplicateDates(kind)) {
      var dupMsg = 'This date is already used — each date can only appear once.';
      container.querySelectorAll('.artist-slot-block.is-duplicate-date').forEach(function(block) {
        var dateInput = block.querySelector('.artist-slot-date');
        if (dateInput) dateInput.classList.add('is-invalid');
        addFailure({ ok: false, message: dupMsg, block: block, focusEl: dateInput });
      });
    }
    if (!hasComplete && failures.length === 0 && blocks.length) {
      var firstBlock = blocks[0];
      if (firstBlock) {
        var dateEl = firstBlock.querySelector('.artist-slot-date');
        if (dateEl) dateEl.classList.add('is-invalid');
        firstBlock.querySelectorAll('.artist-slot-time-from').forEach(function(inp) { inp.classList.add('is-invalid'); });
        addFailure({ ok: false, message: 'Add at least one date with a start time.', block: firstBlock, focusEl: dateEl });
      }
    }
    if (failures.length || !hasComplete) {
      var summary = buildSlotsPanelSummaryMessage(failures.length, hasComplete);
      var first = failures[0] || null;
      return { ok: false, kind: kind, panelLabel: 'Tattoo session', message: summary, block: first ? first.block : (blocks[0] || null), focusEl: first ? first.focusEl : null, failures: failures, hasComplete: hasComplete };
    }
    return { ok: true, kind: kind, panelLabel: 'Tattoo session', failures: [], hasComplete: true };
  }

  function validateCustomQuoteSessionSlots(options) {
    options = options || {};
    var result = runSlotsPanelValidation('session');
    if (!result.ok) {
      setPanelSlotsError('session', 'Tattoo session — ' + result.message);
      if (options.scroll !== false && result.block) {
        scrollToSlotBlock(result.block);
        if (result.focusEl) setTimeout(function() { try { result.focusEl.focus({ preventScroll: true }); } catch (e) {} }, 400);
      }
      return { ok: false, message: result.message || 'Complete session dates and times.' };
    }
    setPanelSlotsError('session', '');
    return { ok: true, message: '' };
  }

  function onArtistSlotFieldChange(input) {
    var kind = slotsPanelKindFromEl(input);
    if (!kind) return;
    var row = input.closest('.artist-slot-time-row');
    if (row) syncTimeRowEndHint(row, kind);
    validateCustomQuoteSessionSlots({ scroll: false });
  }

  function hydrateSlotBlocks(kind, slots, minDate) {
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return;
    minDate = minDate || todayYmd();
    if (slots && slots.length) {
      container.innerHTML = slots.map(function(slot, i) { return buildSlotBlockHtml(kind, i, minDate, slot); }).join('');
      syncAllTimeRowEndHints(kind);
      return;
    }
    if (!container.children.length) container.innerHTML = buildSlotBlockHtml(kind, 0, minDate);
  }

  function reindexSlotBlocks(kind) {
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return;
    container.querySelectorAll('.artist-slot-block').forEach(function(block, i) {
      block.dataset.slotIndex = String(i);
      var label = block.querySelector('.slot-block-label');
      if (label) label.textContent = 'Session date ' + (i + 1);
    });
  }

  function addSlotDateBlock(kind) {
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return;
    var index = container.querySelectorAll('.artist-slot-block').length;
    container.insertAdjacentHTML('beforeend', buildSlotBlockHtml(kind, index, todayYmd()));
    onArtistSlotFieldChange(container);
  }

  function removeSlotDateBlock(kind, btn) {
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return;
    var blocks = container.querySelectorAll('.artist-slot-block');
    if (blocks.length <= 1) {
      var block = btn.closest('.artist-slot-block');
      if (block) {
        block.querySelector('.artist-slot-date').value = '';
        block.querySelectorAll('.artist-slot-time-row').forEach(function(row, i) {
          if (i === 0) {
            row.querySelector('.artist-slot-time-from').value = '';
            syncTimeRowEndHint(row, kind);
          } else row.remove();
        });
      }
      onArtistSlotFieldChange(block.querySelector('.artist-slot-date'));
      return;
    }
    btn.closest('.artist-slot-block').remove();
    reindexSlotBlocks(kind);
    onArtistSlotFieldChange(container);
  }

  function addSlotTimeRow(btn) {
    var block = btn.closest('.artist-slot-block');
    var wrap = block ? block.querySelector('.artist-slot-times') : null;
    var kind = slotsPanelKindFromEl(btn);
    if (!wrap || !kind) return;
    wrap.insertAdjacentHTML('beforeend', buildTimeRangeRowHtml({ from: '', to: '' }, kind));
    onArtistSlotFieldChange(wrap);
  }

  function removeSlotTimeRow(btn) {
    var block = btn.closest('.artist-slot-block');
    var kind = slotsPanelKindFromEl(btn);
    var rows = block.querySelectorAll('.artist-slot-time-row');
    if (rows.length <= 1) {
      rows[0].querySelector('.artist-slot-time-from').value = '';
      if (kind) syncTimeRowEndHint(rows[0], kind);
      onArtistSlotFieldChange(rows[0]);
      return;
    }
    btn.closest('.artist-slot-time-row').remove();
    onArtistSlotFieldChange(block);
  }

  function collectSlotsFromPanel(kind) {
    var container = document.getElementById(kind + 'SlotsBlocks');
    if (!container) return [];
    var durationMinutes = getDurationMinutesForSlotsKind(kind);
    var slots = [];
    container.querySelectorAll('.artist-slot-block').forEach(function(block) {
      var date = (block.querySelector('.artist-slot-date') || {}).value || '';
      var ranges = [];
      block.querySelectorAll('.artist-slot-time-row').forEach(function(row) {
        var from = (row.querySelector('.artist-slot-time-from') || {}).value || '';
        if (!from || !durationMinutes) return;
        var to = addMinutesToTime(from, durationMinutes);
        if (to) ranges.push({ from: from, to: to });
      });
      if (date && ranges.length) slots.push({ date: date, ranges: ranges });
    });
    return slots;
  }

  function firstServerValidationError(errors) {
    if (!errors || typeof errors !== 'object') return null;
    var keys = Object.keys(errors);
    for (var i = 0; i < keys.length; i++) {
      var messages = errors[keys[i]];
      if (Array.isArray(messages) && messages[0]) return messages[0];
      if (typeof messages === 'string' && messages) return messages;
    }
    return null;
  }

  var quoteFieldConfig = {
    estimated_price: { inputId: 'quoteEstimatedPrice', errorId: 'quoteErrorPrice' },
    estimated_time: { inputId: 'quoteEstimatedDuration', errorId: 'quoteErrorDuration' },
    number_of_sessions: { inputId: 'quoteNumberOfSessions', errorId: 'quoteErrorSessions' },
    message_for_client: { inputId: 'quoteMessageForClient', errorId: 'quoteErrorMessage' },
  };

  function quoteFieldBlock(fieldKey, label, inputHtml, hintHtml) {
    var cfg = quoteFieldConfig[fieldKey];
    return '<div class="mb-3 quote-field-group" data-quote-field="' + fieldKey + '">' +
      '<label for="' + cfg.inputId + '" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">' + label + '</label>' +
      inputHtml +
      (hintHtml || '') +
      '<p id="' + cfg.errorId + '" class="quote-field-error hidden text-sm text-error mt-1.5" role="alert"></p>' +
      '</div>';
  }

  function buildQuoteFormHtml(req) {
    var price = req.estimatedPrice != null && req.estimatedPrice !== '' ? String(req.estimatedPrice) : '';
    var duration = req.estimatedTime ? escapeHtml(req.estimatedTime) : '';
    var sessions = req.numberOfSessions ? escapeHtml(req.numberOfSessions) : '';
    var message = req.messageForClient ? escapeHtml(req.messageForClient) : '';
    var guestSpotFull = !!(req.isGuest && req.guestSpot && req.guestSpot.isFull);
    var guestFullBanner = guestSpotFull
      ? '<div class="rounded-xl border px-4 py-3 text-sm font-semibold" style="border-color: rgba(255,191,0,0.45); background: rgba(255,191,0,0.12); color: #B8860B;">All slots are full. You cannot send a quote for this guest spot.</div>'
      : '';

    return '<form class="custom-quote-panel space-y-4" id="customQuoteForm" onsubmit="return false;" novalidate>' +
      '<div><h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">send</span> Send Quote</h4>' +
      '<p class="text-sm text-on-surface-variant">' + (req.isGuest
        ? 'Send your price and message. The client will book within your guest spot availability.'
        : 'Fill in all fields below to send your quote to the client.') + '</p></div>' +
      guestFullBanner +
      quoteFieldBlock('estimated_price', 'Estimated Price (€)',
        '<input type="number" id="quoteEstimatedPrice" name="estimated_price" min="0" step="0.01" placeholder="e.g. 350" value="' + escapeHtml(price) + '"' + (guestSpotFull ? ' disabled' : '') + '>') +
      quoteFieldBlock('estimated_time', 'Estimated Duration',
        '<input type="text" id="quoteEstimatedDuration" name="estimated_time" placeholder="e.g. 3–4 hours" value="' + duration + '"' + (guestSpotFull ? ' disabled' : '') + '>') +
      quoteFieldBlock('number_of_sessions', 'Number of Sessions',
        '<input type="text" id="quoteNumberOfSessions" name="number_of_sessions" placeholder="e.g. 2 sessions" value="' + sessions + '"' + (guestSpotFull ? ' disabled' : '') + '>') +
      quoteFieldBlock('message_for_client', 'Message to Client',
        '<textarea id="quoteMessageForClient" name="message_for_client" rows="5" maxlength="2000" placeholder="Describe your quote, design approach, deposit requirements, or next steps…"' + (guestSpotFull ? ' disabled' : '') + '>' + message + '</textarea>',
        '<p class="text-xs text-outline mt-1.5">Max 2,000 characters</p>') +
      (needsQuoteSessionSlots(req) ? buildCustomQuoteSessionSlotsPanel() : '') +
      '<p id="quoteFormGeneralError" class="hidden text-sm text-error font-medium" role="alert"></p>' +
      '<button type="button" id="customQuoteSubmitBtn" onclick="submitCustomQuote()" ' + (guestSpotFull ? 'disabled ' : '') + 'class="w-full inline-flex items-center justify-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm disabled:opacity-60 disabled:pointer-events-none">' +
      '<span class="material-symbols-outlined text-lg">send</span> ' + (guestSpotFull ? 'All slots are full' : 'Send Quote') + '</button>' +
      '</form>';
  }

  function buildQuoteReadOnlyHtml(req) {
    var priceLabel = req.estimatedPrice != null && parseFloat(req.estimatedPrice) > 0
      ? '€' + parseFloat(req.estimatedPrice).toFixed(2)
      : '—';

    return '<div class="custom-quote-panel space-y-4">' +
      '<div><h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">request_quote</span> Quote sent</h4>' +
      '<p class="text-sm text-on-surface-variant">Details you shared with the client.</p></div>' +
      '<div class="grid grid-cols-1 gap-3">' +
        '<div><p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Estimated Price (€)</p><p class="custom-quote-readonly">' + escapeHtml(priceLabel) + '</p></div>' +
        '<div><p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Estimated Duration</p><p class="custom-quote-readonly">' + escapeHtml(req.estimatedTime || '—') + '</p></div>' +
        '<div><p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Number of Sessions</p><p class="custom-quote-readonly">' + escapeHtml(req.numberOfSessions || '—') + '</p></div>' +
        '<div><p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Message to Client</p>' +
        '<p class="custom-quote-readonly whitespace-pre-line">' + escapeHtml(req.messageForClient || '—') + '</p></div>' +
      '</div>' +
      (needsQuoteSessionSlots(req) && (req.artistSessionSlots || []).length
        ? buildArtistSlotsReadOnlyPanel('Tattoo session times', 'brush', req.artistSessionSlots, 'artist-slots-panel--session')
        : '') +
      '</div>';
  }

  function buildDeclineSectionHtml(requestId) {
    return '<div class="bg-white rounded-2xl p-5 border border-outline-variant/20">' +
      '<h4 class="font-bold text-on-surface mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-error text-lg">block</span> Decline Request</h4>' +
      '<p class="text-sm text-on-surface-variant mb-4">Decline this request and share a reason with the client.</p>' +
      '<button type="button" onclick="openDeclineModal(' + requestId + ')" class="w-full inline-flex items-center justify-center gap-2 border border-error/30 text-error px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-error/5 transition-colors">' +
      '<span class="material-symbols-outlined text-lg">block</span> Decline request</button></div>';
  }

  function buildQuotePanelHtml(req) {
    if (req.status === 'cancelled') {
      var declinedNote = req.reasonDecline
        ? '<p class="text-sm text-on-surface mt-3"><span class="font-semibold text-on-surface-variant">Decline reason:</span><br><span class="whitespace-pre-line">' + escapeHtml(req.reasonDecline) + '</span></p>'
        : '<p class="text-sm text-on-surface-variant mt-2">No decline reason was provided.</p>';
      return '<div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/20">' +
        '<h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-error text-lg">block</span> Declined</h4>' +
        declinedNote + '</div>';
    }
    if (req.status === 'pending') {
      return buildQuoteFormHtml(req) + buildDeclineSectionHtml(req.id);
    }
    if (req.status === 'confirmed' || req.status === 'moved_to_booking' || customRequestHasQuote(req)) {
      return buildQuoteReadOnlyHtml(req);
    }
    return '<div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/20 text-sm text-on-surface-variant">' +
      '<p class="font-semibold text-on-surface mb-1">No quote yet</p>' +
      '<p>This request is <strong>' + escapeHtml(req.filterStatus) + '</strong>. No quotation was recorded.</p></div>';
  }

  function clearQuoteFieldErrors() {
    Object.keys(quoteFieldConfig).forEach(function (key) {
      var cfg = quoteFieldConfig[key];
      var errEl = document.getElementById(cfg.errorId);
      var inputEl = document.getElementById(cfg.inputId);
      if (errEl) {
        errEl.textContent = '';
        errEl.classList.add('hidden');
      }
      if (inputEl) inputEl.classList.remove('quote-input-invalid');
    });
    hideQuoteFormGeneralError();
  }

  function setQuoteFieldErrors(errors) {
    clearQuoteFieldErrors();
    var hasFieldError = false;
    Object.keys(quoteFieldConfig).forEach(function (key) {
      var msg = errors[key];
      if (!msg) return;
      hasFieldError = true;
      var cfg = quoteFieldConfig[key];
      var errEl = document.getElementById(cfg.errorId);
      var inputEl = document.getElementById(cfg.inputId);
      if (errEl) {
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
      }
      if (inputEl) inputEl.classList.add('quote-input-invalid');
    });
    return hasFieldError;
  }

  function mapServerQuoteErrors(serverErrors) {
    var fieldErrors = {};
    if (!serverErrors || typeof serverErrors !== 'object') return fieldErrors;
    Object.keys(serverErrors).forEach(function (key) {
      if (!quoteFieldConfig[key]) return;
      var messages = serverErrors[key];
      if (Array.isArray(messages) && messages[0]) fieldErrors[key] = messages[0];
      else if (typeof messages === 'string' && messages) fieldErrors[key] = messages;
    });
    return fieldErrors;
  }

  function showQuoteFormGeneralError(message) {
    var el = document.getElementById('quoteFormGeneralError');
    if (!el) return;
    el.textContent = message || '';
    if (message) el.classList.remove('hidden');
    else el.classList.add('hidden');
  }

  function hideQuoteFormGeneralError() {
    showQuoteFormGeneralError('');
  }

  function bindQuoteFormValidationClear() {
    var form = document.getElementById('customQuoteForm');
    if (!form || form.dataset.quoteBound === '1') return;
    form.dataset.quoteBound = '1';
    form.addEventListener('input', function (e) {
      var group = e.target.closest('[data-quote-field]');
      if (!group) return;
      var key = group.getAttribute('data-quote-field');
      var cfg = quoteFieldConfig[key];
      if (!cfg) return;
      var errEl = document.getElementById(cfg.errorId);
      var inputEl = document.getElementById(cfg.inputId);
      if (errEl) {
        errEl.textContent = '';
        errEl.classList.add('hidden');
      }
      if (inputEl) inputEl.classList.remove('quote-input-invalid');
      hideQuoteFormGeneralError();
      if (key === 'estimated_time') {
        syncAllTimeRowEndHints('session');
        validateCustomQuoteSessionSlots({ scroll: false });
      }
    });
  }

  function renderCustomRequestDetail(req) {
    var left = document.getElementById('customRequestDetailLeft');
    var quote = document.getElementById('customRequestDetailQuote');
    if (!left || !quote) return;
    left.innerHTML = buildCustomRequestLeftHtml(req);
    quote.innerHTML = buildQuotePanelHtml(req);
    bindQuoteFormValidationClear();
    if (req.isPending && needsQuoteSessionSlots(req)) {
      hydrateSlotBlocks('session', req.artistSessionSlots || [], todayYmd());
    }
  }

  function getDeclineUrl(id) {
    return declineRequestUrlTemplate.replace(/\/0\/decline$/, '/' + id + '/decline');
  }

  function getSendQuoteUrl(id) {
    return sendQuoteUrlTemplate.replace(/\/0\/send-quote$/, '/' + id + '/send-quote');
  }

  function collectQuoteForm() {
    return {
      estimated_price: parseFloat(String(document.getElementById('quoteEstimatedPrice')?.value || '').trim()),
      estimated_time: String(document.getElementById('quoteEstimatedDuration')?.value || '').trim(),
      number_of_sessions: String(document.getElementById('quoteNumberOfSessions')?.value || '').trim(),
      message_for_client: String(document.getElementById('quoteMessageForClient')?.value || '').trim(),
    };
  }

  function getQuoteValidationErrors(payload) {
    var errors = {};
    if (!payload.estimated_price || isNaN(payload.estimated_price) || payload.estimated_price < 0.01) {
      errors.estimated_price = 'Please enter a valid estimated price (at least €0.01).';
    }
    if (!payload.estimated_time) {
      errors.estimated_time = 'Please enter the estimated duration.';
    }
    if (!payload.number_of_sessions) {
      errors.number_of_sessions = 'Please enter the number of sessions.';
    }
    if (!payload.message_for_client) {
      errors.message_for_client = 'Please add a message to the client.';
    } else if (payload.message_for_client.length < 5) {
      errors.message_for_client = 'Please add a message to the client (at least 5 characters).';
    } else if (payload.message_for_client.length > 2000) {
      errors.message_for_client = 'Message must be 2,000 characters or fewer.';
    }
    return errors;
  }

  function hasQuoteValidationErrors(errors) {
    return Object.keys(errors).some(function (key) { return !!errors[key]; });
  }

  function applyQuoteResult(req) {
    customRequestsById[req.id] = req;
    customRequestsById[String(req.id)] = req;
    updateRequestCardFromRequest(req);
    syncGuestSpotFullAcrossCards(req);
    const detailModal = document.getElementById('customRequestModal');
    if (detailModal && detailModal.classList.contains('open')) {
      renderCustomRequestDetail(req);
    }
    applyFilters();
    updatePendingBadge();
  }

  function syncGuestSpotFullAcrossCards(req) {
    if (!req || !req.isGuest || !req.guestSpot || !req.guestSpot.id) return;
    var guestId = req.guestSpot.id;
    var isFull = !!req.guestSpot.isFull;
    var seen = {};
    Object.values(customRequestsById).forEach(function (r) {
      if (!r || !r.id || seen[r.id]) return;
      if (!r.isGuest || !r.guestSpot || r.guestSpot.id !== guestId) return;
      seen[r.id] = true;
      r.guestSpot.isFull = isFull;
      if (r.guestSpot.tracksCapacity) {
        r.guestSpot.remainingSpotsLabel = isFull ? 'Available spots: Full' : r.guestSpot.remainingSpotsLabel;
      }
      customRequestsById[r.id] = r;
      customRequestsById[String(r.id)] = r;
      updateRequestCardFromRequest(r);
    });
  }

  async function submitCustomQuote() {
    clearQuoteFieldErrors();
    setPanelSlotsError('session', '');
    const id = activeCustomRequestId;
    if (!id) return;
    const req = customRequestsById[id] || customRequestsById[String(id)];
    const payload = collectQuoteForm();
    const validationErrors = getQuoteValidationErrors(payload);
    if (hasQuoteValidationErrors(validationErrors)) {
      setQuoteFieldErrors(validationErrors);
      var firstInvalid = document.querySelector('.custom-quote-panel .quote-input-invalid');
      if (firstInvalid) firstInvalid.focus();
      return;
    }
    if (needsQuoteSessionSlots(req)) {
      const slotsValidation = validateCustomQuoteSessionSlots({ scroll: true });
      if (!slotsValidation.ok) {
        showQuoteFormGeneralError(slotsValidation.message || 'Add session dates and start times.');
        return;
      }
      payload.artist_session_slots = collectSlotsFromPanel('session');
    }
    if (req && req.isGuest && req.guestSpot && req.guestSpot.isFull) {
      showQuoteFormGeneralError('All slots are full. You cannot send a quote for this guest spot.');
      showErrorToast('All slots are full. You cannot send a quote for this guest spot.');
      return;
    }
    const btn = document.getElementById('customQuoteSubmitBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }
    try {
      const res = await fetch(getSendQuoteUrl(id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(function () { return {}; });
      if (!res.ok || !data.success) {
        var fieldErrors = mapServerQuoteErrors(data.errors);
        if (hasQuoteValidationErrors(fieldErrors)) {
          setQuoteFieldErrors(fieldErrors);
          var firstServerInvalid = document.querySelector('.custom-quote-panel .quote-input-invalid');
          if (firstServerInvalid) firstServerInvalid.focus();
          return;
        }
        var slotsErr = firstServerValidationError(data.errors);
        if (slotsErr && data.errors && (data.errors.artist_session_slots || Object.keys(data.errors).some(function(k) { return k.indexOf('artist_session_slots') === 0; }))) {
          setPanelSlotsError('session', slotsErr);
          showQuoteFormGeneralError(slotsErr);
          var sessionPanel = document.getElementById('sessionSlotsBlocks');
          if (sessionPanel) scrollToSlotBlock(sessionPanel.closest('.artist-slot-block') || sessionPanel);
          return;
        }
        var generalMsg = data.message || slotsErr || 'Could not send this quote.';
        showQuoteFormGeneralError(generalMsg);
        showErrorToast(generalMsg);
        return;
      }
      applyQuoteResult(data.request);
      showSuccessToast(data.message || 'Quote sent successfully');
    } catch (err) {
      var fallback = err.message || 'Something went wrong. Please try again.';
      showQuoteFormGeneralError(fallback);
      showErrorToast(fallback);
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-lg">send</span> Send Quote';
      }
    }
  }

  function openDeclineModal(id) {
    declineRequestId = id;
    document.getElementById('declineReason').value = '';
    document.getElementById('declineError').classList.add('hidden');
    document.getElementById('declineModal').classList.add('open');
  }

  function closeDeclineModal() {
    declineRequestId = null;
    document.getElementById('declineModal').classList.remove('open');
  }

  function closeDeclineModalOnBackdrop(e) {
    if (e.target === e.currentTarget) closeDeclineModal();
  }

  function updateRequestCardFromRequest(req) {
    const card = document.querySelector('.request-card[data-request-id="' + req.id + '"]');
    if (!card) return;
    card.dataset.status = req.filterStatus;
    const badge = card.querySelector('.request-status-badge');
    if (badge) {
      badge.className = 'request-status-badge inline-flex items-center gap-1.5 ' + req.statusBadgeClass + ' text-xs font-semibold px-3 py-1 rounded-full';
      const label = badge.querySelector('.request-status-label');
      if (label) label.textContent = req.filterStatus;
    }
    if (!req.canDecline) {
      const declineBtn = card.querySelector('[data-decline-btn]');
      if (declineBtn) declineBtn.remove();
    }
    syncSlotsFullMessageOnCard(card, req);
  }

  function syncSlotsFullMessageOnCard(card, req) {
    var actions = card.querySelector('[data-card-actions]');
    if (!actions) return;
    var existing = actions.querySelector('[data-slots-full-msg]');
    var show = req.status === 'pending' && req.isGuest && req.guestSpot && req.guestSpot.isFull;
    if (show) {
      if (!existing) {
        existing = document.createElement('p');
        existing.setAttribute('data-slots-full-msg', '');
        existing.className = 'text-xs font-semibold text-right';
        existing.style.color = '#FFBF00';
        existing.textContent = 'Unable to send. All slots are full.';
        actions.appendChild(existing);
      }
    } else if (existing) {
      existing.remove();
    }
  }

  function applyDeclineResult(req) {
    customRequestsById[req.id] = req;
    customRequestsById[String(req.id)] = req;
    updateRequestCardFromRequest(req);
    const detailModal = document.getElementById('customRequestModal');
    if (detailModal && detailModal.classList.contains('open')) {
      renderCustomRequestDetail(req);
    }
    applyFilters();
    updatePendingBadge();
  }

  function updatePendingBadge() {
    var pending = 0;
    var seen = {};
    Object.values(customRequestsById).forEach(function(r) {
      if (!r || !r.id || seen[r.id]) return;
      seen[r.id] = true;
      if (r.status === 'pending') pending++;
    });
    var badge = document.getElementById('customPendingBadge');
    var text = document.getElementById('customPendingBadgeText');
    if (!badge || !text) return;
    if (pending > 0) {
      badge.classList.remove('hidden');
      text.textContent = pending + ' pending';
    } else {
      badge.classList.add('hidden');
    }
  }

  async function submitDecline() {
    const reason = document.getElementById('declineReason').value.trim();
    const errEl = document.getElementById('declineError');
    if (!declineRequestId) return;
    if (reason.length < 5) {
      errEl.textContent = 'Please provide a reason (at least 5 characters).';
      errEl.classList.remove('hidden');
      return;
    }
    errEl.classList.add('hidden');
    const btn = document.getElementById('declineSubmitBtn');
    btn.disabled = true;
    try {
      const res = await fetch(getDeclineUrl(declineRequestId), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ reason_decline: reason }),
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        const msg = data.message || (data.errors && data.errors.reason_decline && data.errors.reason_decline[0]) || 'Could not decline this request.';
        throw new Error(msg);
      }
      applyDeclineResult(data.request);
      closeDeclineModal();
      showSuccessToast(data.message || 'Request declined successfully');
    } catch (err) {
      errEl.textContent = err.message || 'Something went wrong. Please try again.';
      errEl.classList.remove('hidden');
    } finally {
      btn.disabled = false;
    }
  }

  function openCustomRequestDetail(id) {
    const req = customRequestsById[id] || customRequestsById[String(id)];
    const modal = document.getElementById('customRequestModal');
    if (!req || !modal) return;
    activeCustomRequestId = req.id;
    renderCustomRequestDetail(req);
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeCustomRequestDetail() {
    const modal = document.getElementById('customRequestModal');
    if (modal) modal.classList.remove('open');
    document.body.style.overflow = '';
    activeCustomRequestId = null;
  }

  function closeModalOnBackdrop(event) {
    if (event.target.id === 'customRequestModal' || event.target === event.currentTarget) {
      closeCustomRequestDetail();
    }
  }

  function showToast(message, variant) {
    const toast = document.getElementById('saveToast');
    const msgEl = document.getElementById('saveToastMessage');
    const iconEl = document.getElementById('saveToastIcon');
    if (!toast || !msgEl) return;
    const isError = variant === 'error';
    msgEl.textContent = message != null ? String(message).trim() : (isError ? 'Something went wrong.' : 'Done');
    if (iconEl) {
      iconEl.textContent = isError ? 'error' : 'check_circle';
      iconEl.classList.toggle('text-green-400', !isError);
      iconEl.classList.toggle('text-red-400', isError);
    }
    toast.classList.remove('translate-x-full', 'opacity-0');
    toast.classList.add('translate-x-0', 'opacity-100');
    setTimeout(function() {
      toast.classList.add('translate-x-full', 'opacity-0');
      toast.classList.remove('translate-x-0', 'opacity-100');
    }, 3000);
  }

  function showSuccessToast(message) { showToast(message, 'success'); }
  function showErrorToast(message) { showToast(message, 'error'); }

  document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    if (document.getElementById('declineModal').classList.contains('open')) {
      closeDeclineModal();
      return;
    }
    closeCustomRequestDetail();
  });

  (function() {
    const params = new URLSearchParams(window.location.search);
    const openId = parseInt(params.get('open') || '', 10);
    if (!isNaN(openId) && customRequestsById[openId]) {
      openCustomRequestDetail(openId);
      params.delete('open');
      const qs = params.toString();
      history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
    }
  })();
</script>
@endsection
