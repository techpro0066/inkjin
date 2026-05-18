@extends('layouts.artist_dashboard_layout')

@section('styles')
<style>
    /* Request card hover */
    .request-card { transition: all 0.15s ease; }
    .request-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }

    
/* Status pill colors */
    .status-new { background: #f3e8ff; color: #6b21a8; }
    .status-new .status-dot { background: #9333ea; }
    .status-confirmed { background: #f0fdf4; color: #15803d; }
    .status-confirmed .status-dot { background: #22c55e; }
    .status-declined { background: #fef2f2; color: #b91c1c; }
    .status-declined .status-dot { background: #ef4444; }

    
/* Filter pill active */
    .filter-pill { transition: all 0.2s; }
    .filter-pill.active { background: #22c55e; color: #ffffff; }

    
/* Modal */
    .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 200; }
    .modal-backdrop.open { display: flex; }

    
/* Tag style */
    .info-tag { background: #f0fdf4; color: #15803d; }

    
/* Reference image placeholder */
    .ref-image-placeholder { background: #f2ecf5; border: 2px dashed #cac4d3; }
  
    /* Mobile overflow fixes */
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }
    .filter-pills { flex-wrap: wrap; }
    .request-card { overflow: hidden; word-break: break-word; }

    /* Client availability (modal) */
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
    @media (max-width: 480px) {
      .avail-meta-grid { grid-template-columns: 1fr; }
    }
    .avail-meta-item {
      background: #fff;
      border: 1px solid rgba(202, 196, 211, 0.35);
      border-radius: 0.75rem;
      padding: 0.65rem 0.85rem;
    }
    .avail-meta-item .avail-block-label { margin-bottom: 0.25rem; }
    .avail-meta-value { font-size: 0.85rem; font-weight: 600; color: #1c1b21; }
    .avail-avoid-box {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      background: #fff7ed;
      border: 1px solid #fed7aa;
      border-radius: 0.75rem;
      padding: 0.65rem 0.85rem;
      font-size: 0.85rem;
      font-weight: 500;
      color: #9a3412;
    }
    .avail-empty {
      font-size: 0.85rem;
      color: #7a7583;
      font-style: italic;
    }

    /* Artist-offered slots (session / consultation) */
    .artist-slots-panel {
      border-radius: 1rem;
      padding: 1.25rem;
      margin-bottom: 1rem;
    }
    .artist-slots-panel--session {
      background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
      border: 1px solid rgba(34, 197, 94, 0.28);
    }
    .artist-slots-panel--consult {
      background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
      border: 1px solid rgba(139, 92, 246, 0.28);
    }
    .artist-slot-block {
      background: #fff;
      border: 1px solid rgba(202, 196, 211, 0.45);
      border-radius: 0.75rem;
      padding: 1rem;
      margin-bottom: 0.75rem;
    }
    .artist-slot-block:last-child { margin-bottom: 0; }
    .artist-slot-date,
    .artist-slot-time-from,
    .artist-slot-time-to {
      width: 100%;
      border: 1px solid rgba(202, 196, 211, 0.45);
      border-radius: 0.75rem;
      padding: 0.5rem 0.75rem;
      font-size: 0.875rem;
      color: #1c1b21;
    }
    .artist-slot-date.is-invalid,
    .artist-slot-time-from.is-invalid,
    .artist-slot-time-to.is-invalid {
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
    .artist-slot-range-to-label {
      font-size: 0.75rem;
      font-weight: 600;
      color: #7a7583;
      padding-bottom: 0.55rem;
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
    .artist-slots-save-note {
      font-size: 0.75rem;
      color: #7a7583;
      margin-top: 0.75rem;
      text-align: center;
    }
    .artist-offer-notes {
      background: #fff;
      border: 1px solid rgba(202, 196, 211, 0.45);
      border-radius: 1rem;
      padding: 1.25rem;
      margin-bottom: 1rem;
    }
    .artist-offer-notes textarea {
      width: 100%;
      min-height: 100px;
      border: 1px solid rgba(202, 196, 211, 0.45);
      border-radius: 0.75rem;
      padding: 0.75rem 1rem;
      font-size: 0.875rem;
      color: #1c1b21;
      resize: vertical;
    }
    .artist-offer-notes textarea:focus {
      outline: none;
      border-color: rgba(49, 15, 122, 0.55);
      box-shadow: 0 0 0 3px rgba(49, 15, 122, 0.12);
    }
    .artist-offer-notes-readonly {
      font-size: 0.875rem;
      color: #1c1b21;
      white-space: pre-line;
      line-height: 1.5;
    }
    .artist-submitted-offer { margin-bottom: 0.5rem; }
    .artist-slots-panel--readonly { margin-bottom: 1rem; }
    .artist-slots-panel--readonly:last-child { margin-bottom: 0; }
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
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

      <!-- Request Type Tabs -->
      <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
        <a href="{{ route('artist.requests.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary transition-all">Available Design Requests</a>
      </div>

      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
          <div>
            <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Available Design Requests</h2>
            <p class="text-on-surface-variant mt-1">Review booking requests for your available designs and confirm appointments.</p>
          </div>
        </div>
      </div>

      <!-- Filters Bar -->
      <div class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
          <!-- Sort By -->
          <div>
            <label for="sortBy" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Sort by</label>
            <select id="sortBy" name="sortBy" onchange="applyFilters()" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <option value="recent">Most Recent</option>
              <option value="oldest">Oldest First</option>
            </select>
          </div>
          <!-- Search -->
          <div>
            <label for="searchClient" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
              <input type="text" id="searchClient" name="searchClient" placeholder="Search client name..." oninput="applyFilters()" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
          </div>
        </div>
        <!-- Status Pills -->
        <div class="flex flex-wrap gap-2">
          <button onclick="filterByStatus('all')" class="filter-pill active text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="all">All</button>
          <button type="button" onclick="filterByStatus('New Request')" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="New Request">New Requests</button>
          <button type="button" onclick="filterByStatus('Confirmed')" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="Confirmed">Confirmed</button>
          <button type="button" onclick="filterByStatus('Declined')" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="Declined">Declined</button>
        </div>
      </div>

      <!-- Requests Cards -->
      <div class="space-y-4" id="requestsList">
        @forelse ($requests as $request)
          @include('artist.requests.partials.request-card', ['request' => $request])
        @empty
        @endforelse
      </div>

      <div id="requestsEmpty" class="hidden bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-12 text-center">
        <span class="material-symbols-outlined text-4xl text-outline mb-3 block">search_off</span>
        <p class="text-on-surface-variant font-medium">No requests found</p>
        <p class="text-outline text-sm mt-1">Try adjusting your filters or search.</p>
      </div>

    </div>
  </main>

  <!-- Request Detail Modal -->
  <div class="modal-backdrop" id="requestDetailModal" onclick="closeModalOnBackdrop(event)">
    <div class="w-full h-full overflow-y-auto bg-white lg:bg-transparent lg:p-8 lg:flex lg:items-start lg:justify-center" onclick="closeModalOnBackdrop(event)">
      <div class="bg-white lg:rounded-2xl w-full lg:max-w-5xl lg:shadow-2xl min-h-screen lg:min-h-0 lg:max-h-[90vh] lg:overflow-y-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15 sticky top-0 bg-white z-10 lg:rounded-t-2xl">
          <h3 class="text-lg font-bold text-on-surface">Request Details</h3>
          <button type="button" onclick="closeRequestDetail()" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-low transition-colors">
            <span class="material-symbols-outlined text-on-surface-variant">close</span>
          </button>
        </div>
        <div class="p-6">
          <div id="requestDetailPanel" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-0 lg:gap-6 w-full">
            <div class="space-y-6" id="requestDetailLeft"></div>
            <div class="space-y-6 lg:sticky lg:top-4 lg:max-h-[calc(90vh-5rem)] lg:overflow-y-auto lg:overscroll-contain pr-0.5" id="requestDetailActions"></div>
          </div>
          <div id="requestDetailMissing" class="hidden text-center py-12 text-on-surface-variant">
            <span class="material-symbols-outlined text-4xl text-outline mb-3 block">error</span>
            <p>Request not found.</p>
          </div>
        </div>
        <div class="px-6 py-4 border-t border-outline-variant/15">
          <button type="button" onclick="closeRequestDetail()" class="text-sm font-semibold text-primary hover:text-primary-container transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Back to Design Requests
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Decline Request Modal -->
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
          <p class="text-sm text-on-surface-variant">Share why you are declining this request. The client may be notified with your message.</p>
          <div>
            <label for="declineReason" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Reason for decline</label>
            <textarea id="declineReason" rows="4" maxlength="2000" placeholder="e.g. Design style does not match my portfolio, schedule fully booked for requested dates…" class="w-full rounded-xl border border-outline-variant/30 px-4 py-3 text-sm text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/30 resize-y min-h-[100px]"></textarea>
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

  <script>
    const bookingRequestsById = @json(collect($requestsPayload)->keyBy('id'));
    const declineRequestUrlTemplate = @json(route('artist.requests.decline', ['bookingRequest' => 0]));
    const offerSlotsUrlTemplate = @json(route('artist.requests.offer-slots', ['bookingRequest' => 0]));
    let currentStatusFilter = 'all';
    let declineRequestId = null;
    let activeSlotsRequestId = null;

    function escapeHtml(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function filterByStatus(status) {
      currentStatusFilter = status;
      document.querySelectorAll('.filter-pill').forEach(function(pill) {
        pill.classList.toggle('active', pill.dataset.status === status);
      });
      applyFilters();
    }

    function applyFilters() {
      const search = document.getElementById('searchClient').value.toLowerCase().trim();
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
      document.getElementById('requestsEmpty').classList.toggle('hidden', !hasCards || visible > 0);
      document.getElementById('requestsList').classList.toggle('hidden', hasCards && visible === 0);
      const sort = document.getElementById('sortBy').value;
      const list = document.getElementById('requestsList');
      const sorted = Array.from(cards).filter(function(c) { return !c.classList.contains('hidden'); });
      sorted.sort(function(a, b) {
        const da = a.dataset.date || '';
        const db = b.dataset.date || '';
        return sort === 'oldest' ? da.localeCompare(db) : db.localeCompare(da);
      });
      sorted.forEach(function(card) { list.appendChild(card); });
    }

    function buildAvailabilityHtml(details) {
      details = details || {};
      var preferredDates = details.preferredDates || [];
      var preferredDays = details.preferredDays || [];
      var flexibility = details.flexibility || '';
      var urgency = details.urgency || '';
      var avoidDates = details.avoidDates || '';
      var sessionGap = details.sessionGap || '';
      var hasAny = preferredDates.length || preferredDays.length || flexibility || urgency || avoidDates || sessionGap;

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
      if (sessionGap) metaItems.push({ label: 'Session gap', value: sessionGap });
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
      return html.replace(/<motion/g, '<div').replace(/<\/motion>/g, '</div>');
    }

    function renderRequestDetail(req) {
      const left = document.getElementById('requestDetailLeft');
      const actions = document.getElementById('requestDetailActions');
      const designImg = req.designImage
        ? '<img src="' + escapeHtml(req.designImage) + '" alt="" class="w-full h-full object-cover">'
        : '<span class="material-symbols-outlined text-outline text-3xl">palette</span>';
      var questionsHtml = '';
      (req.questionsAnswers || []).forEach(function(item) {
        if (!item || !item.question) return;
        var answer = item.answer;
        if (typeof answer === 'boolean') answer = answer ? 'Yes' : 'No';
        if (Array.isArray(answer)) answer = answer.join(', ');
        questionsHtml += '<div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">' + escapeHtml(item.question) + '</h4><p class="text-sm font-medium text-on-surface">' + escapeHtml(answer || '—') + '</p></div>';
      });
      var availabilityHtml = buildAvailabilityHtml(req.availabilityDetails || {});
      left.innerHTML =
        '<div class="flex items-center gap-4"><div class="w-14 h-14 rounded-full bg-primary flex items-center justify-center flex-shrink-0"><span class="text-white text-lg font-bold">' + escapeHtml(req.clientInitials) + '</span></div><div><p class="font-bold text-lg text-on-surface">' + escapeHtml(req.clientName) + '</p><p class="text-sm text-on-surface-variant">' + escapeHtml(req.clientEmail) + '</p></div></div>' +
        '<div class="flex items-center gap-3 flex-wrap"><span class="inline-flex items-center gap-1.5 ' + escapeHtml(req.statusBadgeClass) + ' text-xs font-semibold px-3 py-1 rounded-full"><span class="w-1.5 h-1.5 rounded-full status-dot"></span> ' + escapeHtml(req.filterStatus) + '</span><span class="text-xs text-outline">Submitted ' + escapeHtml(req.submittedAt) + '</span><span class="text-xs text-outline">' + escapeHtml(req.reference) + '</span></div>' +
        '<div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/20 flex gap-4"><div class="w-20 h-20 rounded-xl bg-white flex items-center justify-center flex-shrink-0 border border-outline-variant/20 overflow-hidden">' + designImg + '</div><div><h4 class="font-bold text-on-surface text-lg">' + escapeHtml(req.designTitle) + '</h4><p class="text-sm text-on-surface-variant mt-1">' + escapeHtml(req.designStyle) + ' · ' + escapeHtml(req.priceLabel) + '</p><p class="text-xs text-on-surface-variant mt-2">' + escapeHtml(req.schedulingLabel) + '</p></div></div>' +
        '<div class="grid grid-cols-2 gap-4"><div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Placement</h4><p class="text-sm font-medium text-on-surface">' + escapeHtml(req.placement) + '</p></div><div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Scheduling</h4><p class="text-sm font-medium text-on-surface">' + escapeHtml(req.schedulingLabel) + '</p></div><div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Consultation</h4><p class="text-sm font-medium text-on-surface">' + escapeHtml(req.consultationLabel) + '</p></div><div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Health / Allergies</h4><p class="text-sm font-medium text-on-surface">' + escapeHtml(req.health) + '</p></div>' + questionsHtml + '</div>' +
        availabilityHtml +
        '<div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Additional Notes</h4><p class="text-sm text-on-surface leading-relaxed whitespace-pre-line">' + escapeHtml(req.additionalNotes) + '</p></div>';
      left.innerHTML = left.innerHTML.replace(/<motion>/g, '<div>').replace(/<\/motion>/g, '</div>').replace(/<motion>/g, '<div>').replace(/<\/motion>/g, '</div>');
      activeSlotsRequestId = req.id;
      if (req.isPending) {
        actions.innerHTML = buildArtistOfferSlotsHtml(req) + buildDeclineSectionHtml(req.id);
      } else {
        actions.innerHTML = buildNonPendingActionsHtml(req);
      }
    }

    function todayYmd() {
      var d = new Date();
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function requestHasConsultation(req) {
      return req.consultationLabel && req.consultationLabel !== 'None';
    }

    function buildArtistOfferNotesHtml(req) {
      var notes = escapeHtml(req.artistNotesToClient || '');
      return '<div class="artist-offer-notes">' +
        '<h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">chat</span> Message for client</h4>' +
        '<p class="text-sm text-on-surface-variant mb-3">Optional note sent with your offered times (prep instructions, what to bring, etc.).</p>' +
        '<label for="artistOfferNotes" class="sr-only">Message for client</label>' +
        '<textarea id="artistOfferNotes" name="artist_notes_to_client" rows="4" maxlength="2000" placeholder="e.g. Please arrive 10 minutes early. Bring reference images if you have updates…" oninput="onArtistOfferNotesChange()">' + notes + '</textarea>' +
        '<p class="text-xs text-outline mt-1.5">Max 2,000 characters</p>' +
        '</div>';
    }

    function buildArtistOfferNotesReadOnlyHtml(req) {
      if (!req.artistNotesToClient || !String(req.artistNotesToClient).trim()) return '';
      return '<div class="artist-offer-notes">' +
        '<h4 class="font-bold text-on-surface mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">chat</span> Message for client</h4>' +
        '<p class="artist-offer-notes-readonly">' + escapeHtml(req.artistNotesToClient) + '</p>' +
        '</div>';
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

    function artistHasSubmittedOffer(req) {
      var session = req.artistSessionSlots || [];
      var consult = req.artistConsultationSlots || [];
      var notes = req.artistNotesToClient && String(req.artistNotesToClient).trim();
      return session.length > 0 || consult.length > 0 || !!notes;
    }

    function buildArtistSubmittedOfferHtml(req) {
      var sessionSlots = req.artistSessionSlots || [];
      var consultSlots = req.artistConsultationSlots || [];
      var html = '<div class="artist-submitted-offer">';
      html += '<h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">event_available</span> Your submitted offer</h4>';
      html += '<p class="text-sm text-on-surface-variant mb-4">Times and message you sent to the client.</p>';

      if (!artistHasSubmittedOffer(req)) {
        html += '<p class="text-sm text-on-surface-variant bg-surface-container-low rounded-xl p-4 border border-outline-variant/20">No offer details were saved for this request.</p>';
        html += '</div>';
        return html;
      }

      if (sessionSlots.length) {
        html += buildArtistSlotsReadOnlyPanel('Tattoo session', 'brush', sessionSlots, 'artist-slots-panel--session');
      }
      if (requestHasConsultation(req) && consultSlots.length) {
        html += buildArtistSlotsReadOnlyPanel('Consultation', 'groups', consultSlots, 'artist-slots-panel--consult');
      }
      html += buildArtistOfferNotesReadOnlyHtml(req);
      html += '</div>';
      return html;
    }

    function buildNonPendingActionsHtml(req) {
      if (req.status === 'cancelled') {
        var declinedNote = req.reasonDecline
          ? '<p class="text-sm text-on-surface mt-3"><span class="font-semibold text-on-surface-variant">Decline reason:</span><br><span class="whitespace-pre-line">' + escapeHtml(req.reasonDecline) + '</span></p>'
          : '<p class="text-sm text-on-surface-variant mt-2">No decline reason was provided.</p>';
        return '<div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/20">' +
          '<h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-error text-lg">block</span> Declined</h4>' +
          declinedNote + '</div>';
      }

      if (req.status === 'confirmed' || req.status === 'moved_to_booking') {
        return buildArtistSubmittedOfferHtml(req);
      }

      if (artistHasSubmittedOffer(req)) {
        return buildArtistSubmittedOfferHtml(req);
      }

      return '<div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/20 text-sm text-on-surface-variant">This request is <strong>' + escapeHtml(req.filterStatus) + '</strong>.</div>';
    }

    function syncArtistOfferNotesFromRequest(req) {
      var el = document.getElementById('artistOfferNotes');
      if (!el) return;
      el.value = req && req.artistNotesToClient ? req.artistNotesToClient : '';
    }

    function onArtistOfferNotesChange() {
      var id = activeSlotsRequestId;
      if (!id) return;
      var el = document.getElementById('artistOfferNotes');
      if (!el) return;
      var req = bookingRequestsById[id] || bookingRequestsById[String(id)];
      if (!req) return;
      req.artistNotesToClient = el.value;
      bookingRequestsById[id] = req;
      bookingRequestsById[String(id)] = req;
    }

    function buildDeclineSectionHtml(requestId) {
      return '<div class="bg-white rounded-2xl p-5 border border-outline-variant/20 mt-4"><h4 class="font-bold text-on-surface mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-error text-lg">block</span> Decline Request</h4><p class="text-sm text-on-surface-variant mb-4">Decline this request and share a reason with the client.</p><button type="button" onclick="openDeclineModal(' + requestId + ')" class="w-full inline-flex items-center justify-center gap-2 border border-error/30 text-error px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-error/5 transition-colors"><span class="material-symbols-outlined text-lg">block</span> Decline request</button></div>';
    }

    function buildArtistOfferSlotsHtml(req) {
      var html = buildSlotsPanelHtml('session', 'Tattoo session', 'When you can do this tattoo session', 'artist_session_slots', 'artist-slots-panel--session', 'brush');
      if (requestHasConsultation(req)) {
        html += buildSlotsPanelHtml('consult', 'Consultation', 'When you can meet for the consultation (' + escapeHtml(req.consultationLabel) + ')', 'artist_consultation_slots', 'artist-slots-panel--consult', 'groups');
      }
      html += buildArtistOfferNotesHtml(req);
      html += '<button type="button" id="offerSlotsSubmitBtn" onclick="submitOfferedSlots()" class="w-full inline-flex items-center justify-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm disabled:opacity-60 disabled:pointer-events-none"><span class="material-symbols-outlined text-lg">check</span> Submit offered times</button>';
      return html;
    }

    function buildSlotsPanelHtml(kind, title, subtitle, fieldKey, panelClass, icon) {
      return '<div class="artist-slots-panel ' + panelClass + '" data-slots-kind="' + kind + '" data-field-key="' + fieldKey + '">' +
        '<h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">' + icon + '</span> ' + escapeHtml(title) + '</h4>' +
        '<p class="text-sm text-on-surface-variant mb-4">' + escapeHtml(subtitle) + '. Not tied to the client\'s preferred dates.</p>' +
        '<div id="' + kind + 'SlotsBlocks" class="slots-blocks-container"></div>' +
        '<p id="' + kind + 'SlotsError" class="hidden text-sm text-error font-medium mt-2"></p>' +
        '<button type="button" onclick="addSlotDateBlock(\'' + kind + '\')" class="artist-slots-add-date flex items-center justify-center gap-1"><span class="material-symbols-outlined text-lg">calendar_add_on</span> Add another date</button>' +
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

    function rangesOverlap(a, b) {
      return a.start < b.end && b.start < a.end;
    }

    function buildTimeRangeRowHtml(range) {
      range = range || { from: '', to: '' };
      return '<div class="artist-slot-time-row">' +
        '<div class="artist-slot-time-field"><label>From</label><input type="time" class="artist-slot-time-from" value="' + escapeHtml(range.from || '') + '" onchange="onArtistSlotFieldChange(this)"></div>' +
        '<span class="artist-slot-range-to-label">to</span>' +
        '<div class="artist-slot-time-field"><label>To</label><input type="time" class="artist-slot-time-to" value="' + escapeHtml(range.to || '') + '" onchange="onArtistSlotFieldChange(this)"></div>' +
        '<button type="button" onclick="removeSlotTimeRow(this)" class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center text-outline hover:bg-surface-container-low mb-0.5" title="Remove window"><span class="material-symbols-outlined text-lg">close</span></button>' +
        '</div>';
    }

    function buildSlotBlockHtml(kind, index, minDate, slot) {
      slot = slot || { date: '', ranges: [{ from: '', to: '' }] };
      var ranges = (slot.ranges && slot.ranges.length) ? slot.ranges : [{ from: '', to: '' }];
      var timeRows = ranges.map(function(r) { return buildTimeRangeRowHtml(r); }).join('');
      return '<div class="artist-slot-block" data-slot-index="' + index + '">' +
        '<div class="flex items-center justify-between mb-2"><p class="text-xs font-bold text-primary uppercase tracking-wider slot-block-label">' + (kind === 'consult' ? 'Consult' : 'Session') + ' date ' + (index + 1) + '</p>' +
        '<button type="button" onclick="removeSlotDateBlock(\'' + kind + '\', this)" class="text-xs font-semibold text-outline hover:text-error flex items-center gap-0.5"><span class="material-symbols-outlined text-sm">delete</span> Remove</button></div>' +
        '<label class="text-xs font-semibold text-on-surface-variant mb-1 block">Date</label>' +
        '<input type="date" class="artist-slot-date" min="' + escapeHtml(minDate) + '" value="' + escapeHtml(slot.date || '') + '" onchange="onArtistSlotFieldChange(this)">' +
        '<p class="text-xs font-semibold text-on-surface-variant mt-3 mb-1">Available time windows (from — to)</p>' +
        '<p class="text-xs text-on-surface-variant mb-2">Complete every date and time window you add, or remove extras you do not need. Windows on the same date cannot overlap.</p>' +
        '<div class="artist-slot-times">' + timeRows + '</div>' +
        '<button type="button" onclick="addSlotTimeRow(this)" class="mt-2 text-xs font-semibold text-primary hover:text-primary-container flex items-center gap-1"><span class="material-symbols-outlined text-sm">add</span> Add another time window</button>' +
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

    function getScrollableAncestors(el) {
      var list = [];
      var node = el && el.parentElement;
      while (node && node !== document.body) {
        var style = window.getComputedStyle(node);
        var oy = style.overflowY;
        if ((oy === 'auto' || oy === 'scroll' || oy === 'overlay') && node.scrollHeight > node.clientHeight + 2) {
          list.push(node);
        }
        node = node.parentElement;
      }
      return list;
    }

    function scrollElementIntoContainer(container, element, padding) {
      padding = padding || 40;
      var elRect = element.getBoundingClientRect();
      var containerRect = container.getBoundingClientRect();
      var fullyVisible = elRect.top >= containerRect.top + 8 && elRect.bottom <= containerRect.bottom - 8;
      if (fullyVisible) return;
      var nextTop = elRect.top - containerRect.top + container.scrollTop - padding;
      container.scrollTo({ top: Math.max(0, nextTop), behavior: 'smooth' });
    }

    function scrollToSlotBlock(block) {
      if (!block) return;
      var runScroll = function() {
        try {
          block.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        } catch (e) {
          block.scrollIntoView(true);
        }
        var ancestors = getScrollableAncestors(block);
        ancestors.forEach(function(parent) {
          scrollElementIntoContainer(parent, block, 48);
        });
        var panelError = block.querySelector('.artist-slot-block-error');
        if (panelError && !panelError.classList.contains('hidden')) {
          scrollElementIntoContainer(ancestors[0] || document.getElementById('requestDetailActions') || block.parentElement, panelError, 24);
        }
      };
      setTimeout(runScroll, 50);
      setTimeout(runScroll, 200);
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

    function validateRangesInBlock(block) {
      var ranges = [];
      var rows = block.querySelectorAll('.artist-slot-time-row');
      var messages = [];

      if (!rows.length) {
        return { ok: false, message: 'Add at least one time window for this date.' };
      }

      rows.forEach(function(row, rowIndex) {
        row.classList.remove('is-conflict');
        var fromInput = row.querySelector('.artist-slot-time-from');
        var toInput = row.querySelector('.artist-slot-time-to');
        fromInput.classList.remove('is-invalid');
        toInput.classList.remove('is-invalid');
        var from = fromInput.value;
        var to = toInput.value;
        var rowLabel = rows.length > 1 ? ('Window ' + (rowIndex + 1) + ': ') : '';

        if (!from || !to) {
          if (!from) fromInput.classList.add('is-invalid');
          if (!to) toInput.classList.add('is-invalid');
          messages.push(rowLabel + 'enter from and to times, or remove this window.');
          return;
        }
        var start = timeToMinutes(from);
        var end = timeToMinutes(to);
        if (start === null || end === null || start >= end) {
          fromInput.classList.add('is-invalid');
          toInput.classList.add('is-invalid');
          messages.push(rowLabel + 'from time must be earlier than to time.');
          return;
        }
        ranges.push({ start: start, end: end, row: row });
      });

      if (messages.length) {
        return { ok: false, message: messages.join(' ') };
      }

      ranges.sort(function(a, b) { return a.start - b.start; });
      for (var i = 0; i < ranges.length; i++) {
        for (var j = i + 1; j < ranges.length; j++) {
          if (rangesOverlap(ranges[i], ranges[j])) {
            ranges[i].row.classList.add('is-conflict');
            ranges[j].row.classList.add('is-conflict');
            return { ok: false, message: 'Time windows on this date overlap. Adjust or remove one.' };
          }
        }
      }
      return { ok: true, ranges: ranges };
    }

    function validateBlockRequired(block) {
      var dateInput = block.querySelector('.artist-slot-date');
      var date = dateInput ? dateInput.value : '';

      if (!date) {
        dateInput.classList.add('is-invalid');
        block.classList.add('is-incomplete-block');
        block.querySelectorAll('.artist-slot-time-from, .artist-slot-time-to').forEach(function(inp) {
          if (!inp.value) inp.classList.add('is-invalid');
        });
        return {
          ok: false,
          message: 'Select a date for this entry, or remove it if you do not need it.',
          block: block,
          focusEl: dateInput
        };
      }

      var rangeCheck = validateRangesInBlock(block);
      if (!rangeCheck.ok) {
        block.classList.add('is-incomplete-block');
        return {
          ok: false,
          message: rangeCheck.message,
          block: block,
          focusEl: block.querySelector('.artist-slot-time-from.is-invalid') || block.querySelector('.artist-slot-time-to.is-invalid') || block.querySelector('.artist-slot-time-from')
        };
      }

      return { ok: true, block: block, ranges: rangeCheck.ranges };
    }

    function reportSlotsValidationFailure(kind, result, options) {
      var shouldScroll = !options || options.scroll !== false;
      setPanelSlotsError(kind, result.message || '');
      if (result.block && shouldScroll) {
        scrollToSlotBlock(result.block);
        if (result.focusEl) {
          setTimeout(function() {
            try { result.focusEl.focus({ preventScroll: true }); } catch (e) { /* ignore */ }
          }, 400);
        }
      }
    }

    function buildSlotsPanelSummaryMessage(failureCount, hasComplete) {
      if (!hasComplete && failureCount === 0) {
        return 'Add at least one date with complete time windows (from and to).';
      }
      if (failureCount > 1) {
        return 'Fix ' + failureCount + ' entries — complete every date and time window, or remove extras you do not need.';
      }
      if (failureCount === 1) {
        return 'Complete the date and all time windows, or remove this entry.';
      }
      return '';
    }

    function panelLabelForKind(kind) {
      return kind === 'consult' ? 'Consultation' : 'Tattoo session';
    }

    function getActiveOfferedSlotKinds() {
      var kinds = ['session'];
      if (document.getElementById('consultSlotsBlocks')) {
        kinds.push('consult');
      }
      return kinds;
    }

    function runSlotsPanelValidation(kind) {
      clearSlotValidationMarks(kind);
      var container = document.getElementById(kind + 'SlotsBlocks');
      if (!container) {
        return { ok: false, kind: kind, panelLabel: panelLabelForKind(kind), message: 'Nothing to validate.', failures: [], hasComplete: false };
      }

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
        var check = validateBlockRequired(block);
        if (!check.ok) {
          addFailure(check);
        } else {
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
          addFailure({
            ok: false,
            message: dupMsg,
            block: block,
            focusEl: dateInput
          });
        });
      }

      if (!hasComplete && failures.length === 0 && blocks.length) {
        var firstBlock = blocks[0];
        var emptyMsg = 'Add at least one date with complete time windows (from and to).';
        if (firstBlock) {
          var dateEl = firstBlock.querySelector('.artist-slot-date');
          if (dateEl) dateEl.classList.add('is-invalid');
          firstBlock.querySelectorAll('.artist-slot-time-from, .artist-slot-time-to').forEach(function(inp) {
            inp.classList.add('is-invalid');
          });
          addFailure({
            ok: false,
            message: emptyMsg,
            block: firstBlock,
            focusEl: dateEl
          });
        }
      }

      if (failures.length || !hasComplete) {
        var summary = buildSlotsPanelSummaryMessage(failures.length, hasComplete);
        var first = failures[0] || null;
        return {
          ok: false,
          kind: kind,
          panelLabel: panelLabelForKind(kind),
          message: summary,
          block: first ? first.block : (blocks[0] || null),
          focusEl: first ? first.focusEl : null,
          failures: failures,
          hasComplete: hasComplete
        };
      }

      return { ok: true, kind: kind, panelLabel: panelLabelForKind(kind), failures: [], hasComplete: true };
    }

    function buildGlobalOfferedSlotsSummary(allFailures, sessionComplete, consultRequired, consultComplete) {
      var total = allFailures.length;
      if (!sessionComplete || (consultRequired && !consultComplete)) {
        if (consultRequired) {
          return 'Complete tattoo session and consultation sections below (date + time windows for each), or remove entries you do not need.';
        }
        return 'Complete the tattoo session section below (date + time windows), or remove entries you do not need.';
      }
      if (total > 1) {
        return 'Fix ' + total + ' entries across session and consultation — complete every date and time window, or remove extras.';
      }
      if (total === 1) {
        var f = allFailures[0];
        return (f.panelLabel || 'Entry') + ': ' + (f.message || 'Complete this entry.');
      }
      return '';
    }

    function validateAllOfferedSlots(options) {
      options = options || {};
      var kinds = getActiveOfferedSlotKinds();
      var consultRequired = kinds.indexOf('consult') !== -1;
      var allFailures = [];
      var sessionComplete = false;
      var consultComplete = !consultRequired;
      var panelResults = {};

      kinds.forEach(function(kind) {
        var result = runSlotsPanelValidation(kind);
        panelResults[kind] = result;
        if (result.hasComplete && kind === 'session') sessionComplete = true;
        if (result.hasComplete && kind === 'consult') consultComplete = true;
        (result.failures || []).forEach(function(f) {
          allFailures.push({
            kind: kind,
            panelLabel: panelLabelForKind(kind),
            message: f.message,
            block: f.block,
            focusEl: f.focusEl
          });
        });
      });

      kinds.forEach(function(kind) {
        var result = panelResults[kind];
        var kindFailures = allFailures.filter(function(f) { return f.kind === kind; });
        var kindComplete = kind === 'session' ? sessionComplete : consultComplete;
        if (!result.ok) {
          var panelMsg = panelLabelForKind(kind) + ' — ' + (kindFailures[0] && kindFailures.length === 1
            ? kindFailures[0].message
            : buildSlotsPanelSummaryMessage(kindFailures.length, kindComplete));
          setPanelSlotsError(kind, panelMsg);
        } else {
          setPanelSlotsError(kind, '');
        }
      });

      var overallOk = sessionComplete && consultComplete && allFailures.length === 0;
      if (!overallOk) {
        var globalSummary = buildGlobalOfferedSlotsSummary(allFailures, sessionComplete, consultRequired, consultComplete);
        var first = allFailures[0] || null;
        if (!first) {
          if (!sessionComplete && panelResults.session && panelResults.session.block) {
            first = { block: panelResults.session.block, focusEl: panelResults.session.focusEl, kind: 'session' };
          } else if (consultRequired && !consultComplete && panelResults.consult && panelResults.consult.block) {
            first = { block: panelResults.consult.block, focusEl: panelResults.consult.focusEl, kind: 'consult' };
          }
        }
        var combined = {
          ok: false,
          message: globalSummary,
          block: first ? first.block : null,
          focusEl: first ? first.focusEl : null,
          failures: allFailures
        };
        if (options.scroll !== false && combined.block) {
          scrollToSlotBlock(combined.block);
          if (combined.focusEl) {
            setTimeout(function() {
              try { combined.focusEl.focus({ preventScroll: true }); } catch (e) { /* ignore */ }
            }, 400);
          }
        }
        return combined;
      }

      kinds.forEach(function(k) { setPanelSlotsError(k, ''); });
      return { ok: true, message: '' };
    }

    function validateSlotsPanel(kind, options) {
      options = options || {};
      var result = runSlotsPanelValidation(kind);
      if (!result.ok) {
        setPanelSlotsError(kind, result.panelLabel + ' — ' + result.message);
        if (!options || options.report !== false) {
          reportSlotsValidationFailure(kind, result, options);
        }
      } else {
        setPanelSlotsError(kind, '');
      }
      return result;
    }

    function onArtistSlotFieldChange(input) {
      if (!slotsPanelKindFromEl(input)) return;
      validateAllOfferedSlots({ scroll: false });
    }

    function hydrateSlotBlocks(kind, slots, minDate) {
      var container = document.getElementById(kind + 'SlotsBlocks');
      if (!container) return;
      minDate = minDate || todayYmd();
      if (slots && slots.length) {
        container.innerHTML = slots.map(function(slot, i) {
          return buildSlotBlockHtml(kind, i, minDate, slot);
        }).join('');
        return;
      }
      if (!container.children.length) {
        container.innerHTML = buildSlotBlockHtml(kind, 0, minDate);
      }
    }

    function hydrateOfferedSlotsFromRequest(req) {
      var minDate = todayYmd();
      hydrateSlotBlocks('session', req.artistSessionSlots || [], minDate);
      if (document.getElementById('consultSlotsBlocks')) {
        hydrateSlotBlocks('consult', req.artistConsultationSlots || [], minDate);
      }
    }

    function reindexSlotBlocks(kind) {
      var container = document.getElementById(kind + 'SlotsBlocks');
      if (!container) return;
      container.querySelectorAll('.artist-slot-block').forEach(function(block, i) {
        block.dataset.slotIndex = String(i);
        var label = block.querySelector('.slot-block-label');
        if (label) label.textContent = (kind === 'consult' ? 'Consult' : 'Session') + ' date ' + (i + 1);
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
              row.querySelector('.artist-slot-time-to').value = '';
            } else {
              row.remove();
            }
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
      var wrap = btn.closest('.artist-slot-block').querySelector('.artist-slot-times');
      if (!wrap) return;
      wrap.insertAdjacentHTML('beforeend', buildTimeRangeRowHtml({ from: '', to: '' }));
      onArtistSlotFieldChange(wrap);
    }

    function removeSlotTimeRow(btn) {
      var block = btn.closest('.artist-slot-block');
      var rows = block.querySelectorAll('.artist-slot-time-row');
      if (rows.length <= 1) {
        rows[0].querySelector('.artist-slot-time-from').value = '';
        rows[0].querySelector('.artist-slot-time-to').value = '';
        onArtistSlotFieldChange(rows[0]);
        return;
      }
      btn.closest('.artist-slot-time-row').remove();
      onArtistSlotFieldChange(block);
    }

    function collectSlotsFromPanel(kind) {
      var container = document.getElementById(kind + 'SlotsBlocks');
      if (!container) return [];
      var slots = [];
      container.querySelectorAll('.artist-slot-block').forEach(function(block) {
        var date = (block.querySelector('.artist-slot-date') || {}).value || '';
        var ranges = [];
        block.querySelectorAll('.artist-slot-time-row').forEach(function(row) {
          var from = (row.querySelector('.artist-slot-time-from') || {}).value || '';
          var to = (row.querySelector('.artist-slot-time-to') || {}).value || '';
          if (from && to) ranges.push({ from: from, to: to });
        });
        if (date && ranges.length) slots.push({ date: date, ranges: ranges });
      });
      return slots;
    }

    function getArtistOfferNotes() {
      var el = document.getElementById('artistOfferNotes');
      return el ? String(el.value || '').trim() : '';
    }

    function getOfferSlotsUrl(id) {
      return offerSlotsUrlTemplate.replace(/\/0\/offer-slots$/, '/' + id + '/offer-slots');
    }

    function firstValidationError(data) {
      if (!data) return null;
      if (data.errors && typeof data.errors === 'object') {
        var keys = Object.keys(data.errors);
        if (keys.length) {
          var first = data.errors[keys[0]];
          if (Array.isArray(first) && first.length) return first[0];
          if (typeof first === 'string') return first;
        }
      }
      return data.message || null;
    }

    function applyOfferSlotsResult(req) {
      bookingRequestsById[req.id] = req;
      bookingRequestsById[String(req.id)] = req;
      updateRequestCardFromRequest(req);
      applyFilters();
    }

    async function submitOfferedSlots() {
      onArtistOfferNotesChange();
      var validation = validateAllOfferedSlots({ scroll: true });
      if (!validation.ok) {
        showErrorToast(validation.message || 'Please complete all date and time fields.');
        return;
      }
      var id = activeSlotsRequestId;
      if (!id) return;
      var btn = document.getElementById('offerSlotsSubmitBtn');
      if (btn) btn.disabled = true;
      try {
        var payload = {
          artist_session_slots: collectSlotsFromPanel('session'),
          artist_notes_to_client: getArtistOfferNotes() || null,
        };
        if (document.getElementById('consultSlotsBlocks')) {
          payload.artist_consultation_slots = collectSlotsFromPanel('consult');
        }
        var res = await fetch(getOfferSlotsUrl(id), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify(payload),
        });
        var data = null;
        try {
          data = await res.json();
        } catch (parseErr) {
          data = null;
        }
        if (!res.ok || !data || data.success !== true) {
          throw new Error(firstValidationError(data) || 'Could not save offered times.');
        }
        applyOfferSlotsResult(data.request);
        closeRequestDetail();
        showSuccessToast('Changes saved successfully');
      } catch (err) {
        showErrorToast(err.message || 'Something went wrong. Please try again.');
      } finally {
        if (btn) btn.disabled = false;
      }
    }

    function getDeclineUrl(id) {
      return declineRequestUrlTemplate.replace(/\/0\/decline$/, '/' + id + '/decline');
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
    }

    function applyDeclineResult(req) {
      bookingRequestsById[req.id] = req;
      bookingRequestsById[String(req.id)] = req;
      updateRequestCardFromRequest(req);
      const detailModal = document.getElementById('requestDetailModal');
      if (detailModal.classList.contains('open')) {
        renderRequestDetail(req);
      }
      applyFilters();
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

    function openRequestDetail(id) {
      const req = bookingRequestsById[id] || bookingRequestsById[String(id)];
      const panel = document.getElementById('requestDetailPanel');
      const missing = document.getElementById('requestDetailMissing');
      if (!req) {
        panel.classList.add('hidden');
        missing.classList.remove('hidden');
      } else {
        missing.classList.add('hidden');
        panel.classList.remove('hidden');
        renderRequestDetail(req);
        if (req.isPending) hydrateOfferedSlotsFromRequest(req);
      }
      document.getElementById('requestDetailModal').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeRequestDetail() {
      document.getElementById('requestDetailModal').classList.remove('open');
      document.body.style.overflow = '';
    }

    function closeModalOnBackdrop(e) {
      if (e.target === e.currentTarget) closeRequestDetail();
    }

    document.addEventListener('keydown', function(e) {
      if (e.key !== 'Escape') return;
      if (document.getElementById('declineModal').classList.contains('open')) {
        closeDeclineModal();
        return;
      }
      closeRequestDetail();
    });

    document.addEventListener('DOMContentLoaded', function() {
      applyFilters();
      @if($requests->isEmpty())
      var empty = document.getElementById('requestsEmpty');
      empty.classList.remove('hidden');
      document.getElementById('requestsList').classList.add('hidden');
      empty.querySelector('p.text-on-surface-variant').textContent = 'No booking requests yet';
      empty.querySelector('p.text-outline').textContent = 'When clients submit availability through your managed booking flow, they will appear here.';
      @endif
    });
  </script>

<!-- Toast Notification -->
<div id="saveToast" class="fixed top-6 right-6 z-50 transform translate-x-full opacity-0 transition-all duration-300">
  <div class="flex items-center gap-3 bg-on-surface text-white px-5 py-3 rounded-xl shadow-lg">
    <span id="saveToastIcon" class="material-symbols-outlined text-green-400" style="font-size:20px;">check_circle</span>
    <span id="saveToastMessage" class="text-sm font-medium">Changes saved successfully</span>
  </div>
</div>
<script>
function showToast(message, variant) {
  const toast = document.getElementById('saveToast');
  const msgEl = document.getElementById('saveToastMessage');
  const iconEl = document.getElementById('saveToastIcon');
  if (!toast || !msgEl) return;

  const isError = variant === 'error';
  const text = message != null ? String(message).trim() : '';
  msgEl.textContent = text || (isError ? 'Something went wrong. Please try again.' : 'Changes saved successfully');

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

function showSuccessToast(message) {
  showToast(message, 'success');
}

function showErrorToast(message) {
  showToast(message, 'error');
}
</script>
@endsection