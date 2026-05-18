@extends('layouts.user_dashboard_layout')

@section('title', 'My Requests')

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
  .filter-pill.active { background: #310f7a; color: #ffffff; }
  .info-tag { background: #f0fdf4; color: #15803d; }
  .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 200; align-items: center; justify-content: center; padding: 1rem; }
  .modal-backdrop.open { display: flex; }
  .modal-panel { max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
  .modal-scroll { overflow-y: auto; flex: 1; }
  .avail-section {
    background: linear-gradient(135deg, #f8f1fb 0%, #f2ecf5 100%);
    border: 1px solid rgba(202, 196, 211, 0.45);
    border-radius: 1rem;
    padding: 1.25rem;
    margin: 1rem 0;
  }
  .avail-section-title {
    display: flex; align-items: center; gap: 0.5rem;
    font-size: 0.8rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.04em; color: #310f7a; margin-bottom: 1rem;
  }
  .avail-block { margin-bottom: 1rem; }
  .avail-block:last-child { margin-bottom: 0; }
  .avail-block-label {
    font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.05em; color: #7a7583; margin-bottom: 0.5rem;
  }
  .avail-pref-list { display: flex; flex-direction: column; gap: 0.5rem; }
  .avail-pref-card {
    display: flex; align-items: flex-start; gap: 0.75rem;
    background: #fff; border: 1px solid rgba(202, 196, 211, 0.35);
    border-radius: 0.75rem; padding: 0.75rem 1rem;
  }
  .avail-pref-num {
    flex-shrink: 0; width: 1.75rem; height: 1.75rem; border-radius: 0.5rem;
    background: #310f7a; color: #fff; font-size: 0.75rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
  }
  .avail-pref-date { font-size: 0.9rem; font-weight: 600; color: #1c1b21; }
  .avail-pref-times { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.4rem; }
  .avail-time-pill {
    font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.55rem;
    border-radius: 9999px; background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe;
  }
  .avail-day-row { display: flex; flex-wrap: wrap; gap: 0.35rem; }
  .avail-day-pill {
    font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.65rem;
    border-radius: 0.5rem; background: #fff; color: #310f7a; border: 1px solid #ddd0ff;
  }
  .avail-meta-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem; }
  @media (max-width: 480px) { .avail-meta-grid { grid-template-columns: 1fr; } }
  .avail-meta-item {
    background: #fff; border: 1px solid rgba(202, 196, 211, 0.35);
    border-radius: 0.75rem; padding: 0.65rem 0.85rem;
  }
  .avail-meta-value { font-size: 0.85rem; font-weight: 600; color: #1c1b21; }
  .avail-avoid-box {
    display: flex; align-items: flex-start; gap: 0.5rem;
    background: #fff7ed; border: 1px solid #fed7aa; border-radius: 0.75rem;
    padding: 0.65rem 0.85rem; font-size: 0.85rem; font-weight: 500; color: #9a3412;
  }
  .artist-slots-panel--session {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 1px solid rgba(34, 197, 94, 0.28); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1rem;
  }
  .artist-slots-panel--consult {
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border: 1px solid rgba(139, 92, 246, 0.28); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1rem;
  }
  .artist-offer-slot-readonly-block {
    background: #fff; border: 1px solid rgba(202, 196, 211, 0.45);
    border-radius: 0.75rem; padding: 1rem; margin-bottom: 0.75rem;
  }
  .artist-offer-slot-readonly-block:last-child { margin-bottom: 0; }
  .artist-offer-slot-ranges { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.5rem; }
  .artist-offer-slot-range {
    display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;
    font-weight: 500; color: #1c1b21; background: rgba(255,255,255,0.7);
    border-radius: 0.5rem; padding: 0.5rem 0.75rem;
  }
  .artist-offer-notes {
    background: #fff; border: 1px solid rgba(202, 196, 211, 0.45);
    border-radius: 1rem; padding: 1.25rem; margin-bottom: 1rem;
  }
  .artist-offer-notes-readonly { font-size: 0.875rem; white-space: pre-line; line-height: 1.5; }
  .waiting-panel {
    background: #f8f1fb; border: 1px dashed rgba(49, 15, 122, 0.25);
    border-radius: 1rem; padding: 1.5rem; text-align: center;
  }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">
    <div class="mb-6">
      <h2 class="text-2xl font-extrabold text-on-surface tracking-tight">My Requests</h2>
      <p class="text-sm text-on-surface-variant mt-1">Booking requests you submitted to artists</p>
    </div>

    <div class="flex flex-wrap gap-2 mb-6 filter-pills">
      <button type="button" class="filter-pill active text-sm font-semibold px-4 py-2 rounded-full border border-outline-variant/30" data-status="all" onclick="filterByStatus('all', this)">All</button>
      <button type="button" class="filter-pill text-sm font-semibold px-4 py-2 rounded-full border border-outline-variant/30 text-on-surface-variant" data-status="pending" onclick="filterByStatus('pending', this)">Pending</button>
      <button type="button" class="filter-pill text-sm font-semibold px-4 py-2 rounded-full border border-outline-variant/30 text-on-surface-variant" data-status="confirmed" onclick="filterByStatus('confirmed', this)">Confirmed</button>
      <button type="button" class="filter-pill text-sm font-semibold px-4 py-2 rounded-full border border-outline-variant/30 text-on-surface-variant" data-status="declined" onclick="filterByStatus('declined', this)">Declined</button>
    </div>

    <div class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="sortBy" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Sort by</label>
          <select id="sortBy" onchange="applyFilters()" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white">
            <option value="recent">Most recent</option>
            <option value="oldest">Oldest first</option>
          </select>
        </div>
        <div>
          <label for="searchArtist" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input type="text" id="searchArtist" placeholder="Search artist name…" oninput="applyFilters()" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white">
          </div>
        </div>
      </div>
    </div>

    <div id="requestsList" class="space-y-4 mb-8">
      @foreach ($requests as $request)
        @include('user.requests.partials.request-card', ['request' => $request])
      @endforeach
    </div>

    <div id="requestsEmpty" class="hidden bg-white rounded-2xl border border-outline-variant/20 p-12 text-center">
      <span class="material-symbols-outlined text-outline text-5xl mb-4">inbox</span>
      <p class="font-bold text-on-surface text-lg mb-2">No requests found</p>
      <p class="text-sm text-on-surface-variant max-w-md mx-auto">When you submit a managed booking request, it will appear here so you can track the artist's response.</p>
    </div>
  </div>
</main>

<div id="requestDetailModal" class="modal-backdrop" onclick="closeModalOnBackdrop(event)">
  <div class="modal-panel bg-white rounded-2xl shadow-xl w-full max-w-5xl" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/15 shrink-0">
      <h3 class="text-lg font-bold text-on-surface">Request details</h3>
      <button type="button" onclick="closeUserRequestDetail()" class="w-9 h-9 rounded-lg flex items-center justify-center hover:bg-surface-container-low text-outline">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div id="requestDetailMissing" class="hidden modal-scroll p-8 text-center text-on-surface-variant">Request not found.</div>
    <div id="requestDetailPanel" class="modal-scroll hidden">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 lg:divide-x divide-outline-variant/15">
        <div id="requestDetailLeft" class="p-6 space-y-5"></div>
        <div id="requestDetailRight" class="p-6 bg-surface-container-low/50 space-y-4"></div>
      </div>
    </div>
  </div>
</div>

<script>
  const userRequestsById = @json(collect($requestsPayload)->keyBy('id'));
  let currentStatusFilter = 'all';

  function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function filterByStatus(status, btn) {
    currentStatusFilter = status;
    document.querySelectorAll('.filter-pill').forEach(function(pill) {
      pill.classList.toggle('active', pill === btn);
      pill.classList.toggle('text-on-surface-variant', pill !== btn);
    });
    applyFilters();
  }

  function applyFilters() {
    const search = (document.getElementById('searchArtist').value || '').trim().toLowerCase();
    const sortBy = document.getElementById('sortBy').value;
    const cards = Array.from(document.querySelectorAll('#requestsList .request-card'));
    let visible = 0;

    cards.sort(function(a, b) {
      const da = a.dataset.date || '';
      const db = b.dataset.date || '';
      return sortBy === 'oldest' ? da.localeCompare(db) : db.localeCompare(da);
    });

    const list = document.getElementById('requestsList');
    cards.forEach(function(card) { list.appendChild(card); });

    cards.forEach(function(card) {
      const statusOk = currentStatusFilter === 'all' || card.dataset.status === currentStatusFilter;
      const searchOk = !search || (card.dataset.artist || '').includes(search);
      const show = statusOk && searchOk;
      card.classList.toggle('hidden', !show);
      if (show) visible++;
    });

    document.getElementById('requestsEmpty').classList.toggle('hidden', visible > 0 || cards.length === 0);
    document.getElementById('requestsList').classList.toggle('hidden', cards.length === 0);
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
    return (h % 12 || 12) + ':' + m + ' ' + ampm;
  }

  function buildAvailabilityHtml(details) {
    details = details || {};
    var preferredDates = details.preferredDates || [];
    var preferredDays = details.preferredDays || [];
    var flexibility = details.flexibility || '';
    var urgency = details.urgency || '';
    var avoidDates = details.avoidDates || '';
    var sessionGap = details.sessionGap || '';
    var hasContent = preferredDates.length || preferredDays.length || flexibility || urgency || avoidDates || sessionGap;
    if (!hasContent) return '';

    var html = '<section class="avail-section"><div class="avail-section-title"><span class="material-symbols-outlined text-lg">calendar_month</span> Your preferred availability</div>';
    if (preferredDates.length) {
      html += '<div class="avail-block"><p class="avail-block-label">Preferred dates</p><div class="avail-pref-list">';
      preferredDates.forEach(function(item) {
        var timesHtml = (item.times || []).map(function(t) {
          return '<span class="avail-time-pill">' + escapeHtml(t) + '</span>';
        }).join('');
        html += '<div class="avail-pref-card"><span class="avail-pref-num">' + escapeHtml(item.preference) + '</span><div class="avail-pref-body"><p class="avail-pref-date">' + escapeHtml(item.dateLabel || item.date) + '</p>' + (timesHtml ? '<div class="avail-pref-times">' + timesHtml + '</div>' : '') + '</div></div>';
      });
      html += '</div></div>';
    }
    if (preferredDays.length) {
      html += '<div class="avail-block"><p class="avail-block-label">Preferred days</p><div class="avail-day-row">';
      preferredDays.forEach(function(day) { html += '<span class="avail-day-pill">' + escapeHtml(day) + '</span>'; });
      html += '</div></div>';
    }
    var meta = [];
    if (flexibility) meta.push({ label: 'Flexibility', value: flexibility });
    if (urgency) meta.push({ label: 'Urgency', value: urgency });
    if (sessionGap) meta.push({ label: 'Session gap', value: sessionGap });
    if (meta.length) {
      html += '<div class="avail-block"><p class="avail-block-label">Scheduling</p><div class="avail-meta-grid">';
      meta.forEach(function(item) {
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

  function buildSlotsReadOnlyPanel(title, icon, slots, panelClass) {
    if (!slots || !slots.length) return '';
    var html = '<div class="artist-slots-panel ' + panelClass + '"><h4 class="font-bold text-on-surface mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">' + icon + '</span> ' + escapeHtml(title) + '</h4>';
    slots.forEach(function(slot) {
      html += '<div class="artist-offer-slot-readonly-block"><p class="text-xs font-bold text-primary uppercase tracking-wider mb-2">' + escapeHtml(formatOfferDate(slot.date)) + '</p><ul class="artist-offer-slot-ranges">';
      (slot.ranges || []).forEach(function(range) {
        html += '<li class="artist-offer-slot-range"><span class="material-symbols-outlined">schedule</span><span>' + escapeHtml(formatOfferTime(range.from)) + ' – ' + escapeHtml(formatOfferTime(range.to)) + '</span></li>';
      });
      html += '</ul></div>';
    });
    html += '</div>';
    return html;
  }

  function buildPickTimesButtonHtml(req) {
    if (req.canPay && req.paymentUrl) {
      return '<div class="mt-5 pt-4 border-t border-outline-variant/20">' +
        '<a href="' + escapeHtml(req.paymentUrl) + '" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary-container transition-colors shadow-md shadow-primary/15">' +
        '<span class="material-symbols-outlined text-lg">payments</span> Complete payment</a></div>';
    }
    if (!req.canSelectTimes || !req.confirmTimesUrl) return '';
    return '<div class="mt-5 pt-4 border-t border-outline-variant/20">' +
      '<a href="' + escapeHtml(req.confirmTimesUrl) + '" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary-container transition-colors shadow-md shadow-primary/15">' +
      '<span class="material-symbols-outlined text-lg">event</span> Set date &amp; time</a></div>';
  }

  function buildArtistResponseHtml(req) {
    if (req.isDeclined) {
      var reason = req.reasonDecline
        ? '<p class="text-sm text-on-surface whitespace-pre-line mt-2">' + escapeHtml(req.reasonDecline) + '</p>'
        : '<p class="text-sm text-on-surface-variant mt-2">No reason was provided.</p>';
      return '<div class="bg-white rounded-2xl p-5 border border-error/20"><h4 class="font-bold text-error flex items-center gap-2"><span class="material-symbols-outlined">block</span> Request declined</h4>' + reason + '</div>';
    }
    var html;
    if (!req.hasArtistOffer && req.isPending) {
      html = '<div class="waiting-panel"><span class="material-symbols-outlined text-primary text-4xl mb-2">hourglass_top</span><p class="font-semibold text-on-surface">Waiting for artist</p><p class="text-sm text-on-surface-variant mt-2">' + escapeHtml(req.artistName) + ' is reviewing your request and will share available times soon.</p></div>';
    } else if (!req.hasArtistOffer) {
      html = '<p class="text-sm text-on-surface-variant">No response from the artist yet.</p>';
    } else {
    html = '<div><h4 class="font-bold text-on-surface mb-1 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">event_available</span> Artist offered times</h4><p class="text-sm text-on-surface-variant mb-4">Times suggested by ' + escapeHtml(req.artistName) + '</p>';
    if ((req.artistSessionSlots || []).length) {
      html += buildSlotsReadOnlyPanel('Tattoo session', 'brush', req.artistSessionSlots, 'artist-slots-panel--session');
    }
    if (req.hasConsultation && (req.artistConsultationSlots || []).length) {
      html += buildSlotsReadOnlyPanel('Consultation', 'groups', req.artistConsultationSlots, 'artist-slots-panel--consult');
    }
    if (req.artistNotesToClient && String(req.artistNotesToClient).trim()) {
      html += '<div class="artist-offer-notes"><h4 class="font-bold text-on-surface mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">chat</span> Message from artist</h4><p class="artist-offer-notes-readonly">' + escapeHtml(req.artistNotesToClient) + '</p></div>';
    }
    html += '</div>';
    }
    if (req.canPay || req.canSelectTimes) {
      html += buildPickTimesButtonHtml(req);
    }
    return html;
  }

  function renderUserRequestDetail(req) {
    var left = document.getElementById('requestDetailLeft');
    var right = document.getElementById('requestDetailRight');
    var designImg = req.designImage
      ? '<img src="' + escapeHtml(req.designImage) + '" alt="" class="w-full h-full object-cover">'
      : '<span class="material-symbols-outlined text-outline text-3xl">palette</span>';
    var profileLink = req.artistProfileUrl
      ? '<a href="' + escapeHtml(req.artistProfileUrl) + '" target="_blank" rel="noopener" class="text-sm font-semibold text-primary hover:underline">View artist profile</a>'
      : '';

    var questionsHtml = '';
    (req.questionsAnswers || []).forEach(function(item) {
      if (!item || !item.question) return;
      var answer = item.answer;
      if (typeof answer === 'boolean') answer = answer ? 'Yes' : 'No';
      if (Array.isArray(answer)) answer = answer.join(', ');
      questionsHtml += '<div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">' + escapeHtml(item.question) + '</h4><p class="text-sm font-medium text-on-surface">' + escapeHtml(answer || '—') + '</p></div>';
    });

    left.innerHTML =
      '<div class="flex items-center gap-4"><div class="w-14 h-14 rounded-full bg-primary flex items-center justify-center flex-shrink-0"><span class="text-white text-lg font-bold">' + escapeHtml(req.artistInitials) + '</span></div><div><p class="font-bold text-lg text-on-surface">' + escapeHtml(req.artistName) + '</p>' + (profileLink ? '<p class="mt-1">' + profileLink + '</p>' : '') + '</div></div>' +
      '<div class="flex items-center gap-3 flex-wrap"><span class="inline-flex items-center gap-1.5 ' + escapeHtml(req.statusBadgeClass) + ' text-xs font-semibold px-3 py-1 rounded-full"><span class="w-1.5 h-1.5 rounded-full status-dot"></span> ' + escapeHtml(req.filterStatus) + '</span><span class="text-xs text-outline">Submitted ' + escapeHtml(req.submittedAt) + '</span><span class="text-xs text-outline">' + escapeHtml(req.reference) + '</span></div>' +
      '<div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/20 flex gap-4"><div class="w-20 h-20 rounded-xl bg-white flex items-center justify-center flex-shrink-0 border border-outline-variant/20 overflow-hidden">' + designImg + '</div><div><h4 class="font-bold text-on-surface text-lg">' + escapeHtml(req.designTitle) + '</h4><p class="text-sm text-on-surface-variant mt-1">' + escapeHtml(req.designStyle) + ' · ' + escapeHtml(req.priceLabel) + '</p><p class="text-xs text-on-surface-variant mt-2">' + escapeHtml(req.schedulingLabel) + '</p></div></div>' +
      '<div class="grid grid-cols-2 gap-4"><div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Placement</h4><p class="text-sm font-medium text-on-surface">' + escapeHtml(req.placement) + '</p></div><div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Consultation</h4><p class="text-sm font-medium text-on-surface">' + escapeHtml(req.consultationLabel) + '</p></div></div>' +
      buildAvailabilityHtml(req.availabilityDetails) +
      questionsHtml +
      '<div><h4 class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Your notes</h4><p class="text-sm text-on-surface leading-relaxed whitespace-pre-line">' + escapeHtml(req.additionalNotes) + '</p></div>';

    right.innerHTML = buildArtistResponseHtml(req);
  }

  function openUserRequestDetail(id) {
    var req = userRequestsById[id] || userRequestsById[String(id)];
    var panel = document.getElementById('requestDetailPanel');
    var missing = document.getElementById('requestDetailMissing');
    if (!req) {
      panel.classList.add('hidden');
      missing.classList.remove('hidden');
    } else {
      missing.classList.add('hidden');
      panel.classList.remove('hidden');
      renderUserRequestDetail(req);
    }
    document.getElementById('requestDetailModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeUserRequestDetail() {
    document.getElementById('requestDetailModal').classList.remove('open');
    document.body.style.overflow = '';
  }

  function closeModalOnBackdrop(e) {
    if (e.target === e.currentTarget) closeUserRequestDetail();
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeUserRequestDetail();
  });

  document.addEventListener('DOMContentLoaded', function() {
    applyFilters();
    @if($requests->isEmpty())
    document.getElementById('requestsEmpty').classList.remove('hidden');
    document.getElementById('requestsList').classList.add('hidden');
    document.querySelector('#requestsEmpty p.text-on-surface-variant').textContent = 'You have not submitted any booking requests yet. Browse artists and submit a managed booking request to get started.';
    @endif
  });
</script>
@endsection
