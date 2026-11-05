{{-- Custom/guest request detail + decline modals (Bookpay D01 custom request) --}}
@include('partials.bookpay-datetime-picker')
<style>
  /* Feed card hover (shared) */
    .request-card { transition: all 0.15s ease; }
    .request-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .filter-pill { transition: all 0.2s; }
    .filter-pill.active { background: #22c55e; color: #ffffff; }

  /* D01 overlay modal */
  #customRequestModal.ov{
    display:none;position:fixed;inset:0;z-index:400;
    background:rgba(20,10,30,.45);padding:16px;
    align-items:center;justify-content:center;
  }
  #customRequestModal.ov.open{display:flex}
  #customRequestModal .modal.xl{
    width:min(1040px,100%);max-width:100%;
    max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);
    background:#fff;border-radius:22px;box-shadow:0 20px 60px rgba(0,0,0,.25);
    display:flex;flex-direction:column;overflow:hidden;padding:0;
  }
  #customRequestModal .mh{
    padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0;
  }
  #customRequestModal .mh h2{font-size:18px;font-weight:800;margin:0;letter-spacing:-.2px}
  #customRequestModal .mh .sub{font-size:13px;color:var(--muted);margin-top:4px}
  #customRequestModal .mx{
    border:0;background:none;cursor:pointer;color:var(--muted);
    width:36px;height:36px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;
  }
  #customRequestModal .mx:hover{background:#F7F3F9;color:var(--ink)}
  #customRequestModal .mb{
    padding:18px 24px;flex:1 1 auto;min-height:0;
    overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;
  }
  #customRequestModal .mf{
    display:flex;gap:10px;align-items:center;justify-content:flex-end;
    padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-shrink:0;
  }
  #customRequestModal .mf .btn{min-width:110px;justify-content:center}
  #customRequestModal .mf .btn-decline,
  #customRequestModal .mf .btn-sendback{
    margin-right:auto;color:#C62828;border-color:#F3C9C9;background:#fff;
  }
  #customRequestModal .mf .btn-decline:hover,
  #customRequestModal .mf .btn-sendback:hover{background:#FDECEC}
  #customRequestModal .d01-via-studio{
    border:1px solid #E6D2F2;background:#F8F0FF;border-radius:12px;
    padding:10px 14px;font-size:13px;color:#5B2A94;font-weight:600;
    margin-bottom:12px;display:flex;gap:8px;align-items:center;
  }
  #customRequestModal .mf .btn[disabled]{opacity:.45;pointer-events:none;cursor:not-allowed}
  #customRequestModal .hidden{display:none!important}
  #customRequestModal .d01-grid{
    display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;
  }
  #customRequestModal .d01-qa{
    display:grid;grid-template-columns:1fr 1fr;column-gap:20px;margin-top:6px;
  }
  #customRequestModal .d01-qa-item{padding:9px 0;border-bottom:1px solid #F3EEF4;min-width:0}
  #customRequestModal .d01-qa-item .lbl{font-size:10.5px}
  #customRequestModal .d01-qa-item .val{font-size:14px;margin-top:3px;color:var(--ink);word-break:break-word}
  #customRequestModal .d01-avail-card{
    display:flex;align-items:center;gap:10px;background:#fff;border-radius:12px;
    padding:10px 12px;margin-top:8px;flex-wrap:wrap;
  }
  #customRequestModal .d01-avail-num{
    width:26px;height:26px;border-radius:7px;background:#3E007C;color:#fff;
    font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
  }
  #customRequestModal .d01-section-title{
    display:flex;align-items:center;gap:8px;color:#3E007C;font-weight:800;font-size:13px;letter-spacing:.5px;
    text-transform:uppercase;
  }
  #customRequestModal .d01-state-card{
    background:var(--tint);border:1px solid var(--tintline);border-radius:16px;padding:18px;
  }
  #customRequestModal .d01-payout-gate{
    background:#F6ECFB;border:1px solid #E6D2F2;border-radius:16px;padding:18px 20px;
  }
  #customRequestModal .d01-locked-offer{
    border:1px solid var(--line);border-radius:16px;padding:16px 18px;margin-top:14px;
    opacity:.55;pointer-events:none;
  }
  #customRequestModal .d01-readout{background:#fff;font-weight:600}
  #customRequestModal .d01-guest-banner{
    border:1px solid rgba(255,191,0,.45);background:rgba(255,191,0,.12);color:#B8860B;
    border-radius:12px;padding:10px 14px;font-size:13px;font-weight:600;margin-bottom:12px;
  }
  /* Beat Tailwind forms — Bookpay .in: white, gray border, 10px radius */
  #customRequestModal input.in,
  #customRequestModal textarea.in,
  #customRequestModal select.in,
  #customRequestModal .in{
    background:#fff!important;
    border:1px solid var(--line,#E8DFEA)!important;
    border-radius:10px!important;
    box-shadow:none!important;
    padding:10px 13px!important;
    min-height:40px;
    font-family:inherit;
    font-size:13.5px;
    font-weight:400;
    line-height:normal;
    color:var(--ink,#1A1A1A);
    width:100%;
    outline:none;
  }
  #customRequestModal input.in:focus,
  #customRequestModal textarea.in:focus,
  #customRequestModal select.in:focus,
  #customRequestModal .in:focus-within{
    border-color:#3E007C!important;
    box-shadow:0 0 0 3px #F3E8FF!important;
  }
  #customRequestModal input.in::placeholder,
  #customRequestModal textarea.in::placeholder{
    color:var(--faint,#9A929E);
  }
  #customRequestModal textarea.in{
    display:block;min-height:70px;resize:vertical;align-items:flex-start;
    font-size:13px;line-height:1.45;padding:10px 13px!important;
  }
  #customRequestModal .in.quote-input-invalid,
  #customRequestModal input.in.quote-input-invalid,
  #customRequestModal textarea.in.quote-input-invalid,
  #customRequestModal .in.quote-input-invalid:focus,
  #customRequestModal input.in.quote-input-invalid:focus,
  #customRequestModal textarea.in.quote-input-invalid:focus{
    border-color:#C62828!important;
    box-shadow:0 0 0 3px rgba(198,40,40,.12)!important;
  }
  #customRequestModal .quote-field-error{display:none;color:#C62828;font-size:12px;font-weight:600;margin:6px 0 0}
  #customRequestModal .quote-field-error:not(.hidden){display:block}
  #customRequestModal .d01-quote-grid{
    display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;
  }
  #customRequestModal .d01-quote-grid .fl{margin-bottom:6px}

  /* Session slot pickers — Bookpay D01 / BP-DT (white cards on green panel) */
  #customRequestModal .artist-slots-panel--session{
    background:#EEFAF3;border:1px solid #BFE8D0;border-radius:14px;
    padding:14px;margin-top:12px;margin-bottom:0;
  }
  #customRequestModal .artist-slot-block{
    background:#fff!important;border:1px solid #E1EDE6!important;border-radius:12px;
    padding:12px 14px;margin:10px 0 0;box-shadow:none;
  }
  #customRequestModal .artist-slot-block:first-child{margin-top:10px}
  #customRequestModal .artist-slot-block .bpx-btn.in{
    width:100%;background:#fff;border:1px solid var(--line);border-radius:10px;
    padding:10px 13px;min-height:40px;font-size:13.5px;font-weight:400;color:var(--ink);
  }
  #customRequestModal .artist-slot-block .bpx-btn.in:focus-visible{
    border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF;outline:none;
  }
  #customRequestModal .artist-slot-block .artist-slot-date.is-invalid + .bpx-btn,
  #customRequestModal .artist-slot-block .artist-slot-time-from.is-invalid + .bpx-btn,
  #customRequestModal .artist-slot-block .bpx-btn.border-error,
  #customRequestModal .artist-slot-block .bpx-btn.err,
  #customRequestModal .artist-slot-block .bpx-btn.bad{
    border-color:#C62828!important;box-shadow:none;
  }
  #customRequestModal .artist-slot-block.is-duplicate-date,
  #customRequestModal .artist-slot-block.is-incomplete-block{
    border-color:#C62828!important;background:#fffbfb!important;
    box-shadow:0 0 0 2px rgba(198,40,40,.12);
  }
  #customRequestModal .artist-slot-block-error:not(.hidden){
    display:flex;align-items:flex-start;gap:.35rem;color:#C62828;font-size:12px;font-weight:600;margin-top:8px;
  }
  #customRequestModal .artist-slot-block-error:not(.hidden)::before{
    content:'error';font-family:'Material Symbols Outlined';font-size:1rem;color:#C62828;line-height:1.2;
  }
  #customRequestModal .artist-slot-dt-row{
    display:grid;grid-template-columns:minmax(140px,.9fr) minmax(180px,1.2fr);gap:14px;align-items:start;
  }
  #customRequestModal .artist-slot-dt-date .fl,
  #customRequestModal .artist-slot-dt-times .fl{margin-bottom:6px}
  #customRequestModal .artist-slot-times{
    display:flex;flex-direction:column;gap:8px;align-items:stretch;margin-top:0;
  }
  #customRequestModal .artist-slot-time-row{
    display:flex;align-items:center;gap:6px;flex-wrap:nowrap;
    margin:0;padding:0;border-radius:0;background:transparent!important;outline:none;
  }
  #customRequestModal .artist-slot-time-row.is-conflict .bpx-btn,
  #customRequestModal .artist-slot-time-row.is-conflict .artist-slot-time-from{
    border-color:#C62828!important;
  }
  #customRequestModal .artist-slot-time-row .artist-slot-time-from,
  #customRequestModal .artist-slot-time-row .bpx-btn{flex:1;min-width:0}
  #customRequestModal .artist-slot-rmx{
    flex:none;width:32px;height:32px;border:0;background:none;border-radius:8px;
    cursor:pointer;color:#8F8A95;display:flex;align-items:center;justify-content:center;padding:0;
  }
  #customRequestModal .artist-slot-rmx:hover{background:#F7F3F9;color:#C62828}
  #customRequestModal .artist-slot-starts-label{display:block}
  #customRequestModal .artist-slot-starts-ends{font-weight:500;color:var(--faint)}
  #customRequestModal .artist-slot-add-time{
    display:inline-block;margin-top:8px;border:0!important;background:none!important;padding:0!important;
    font:inherit;font-size:12.5px;font-weight:600;color:#3E007C;cursor:pointer;border-radius:0;
  }
  #customRequestModal .artist-slot-add-time:hover{text-decoration:underline}
  #customRequestModal .artist-slots-add-date{
    display:block;width:auto;margin-top:10px;padding:0!important;border:0!important;
    background:none!important;border-radius:0;font:inherit;font-size:12.5px;font-weight:600;
    color:#3E007C;cursor:pointer;text-align:left;
  }
  #customRequestModal .artist-slots-add-date:hover{text-decoration:underline;background:none!important}
  #customRequestModal .artist-offer-slot-ranges{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}
  #customRequestModal .artist-offer-slot-range{
    display:flex;align-items:center;gap:8px;font-size:13.5px;font-weight:500;
    color:var(--ink);background:rgba(255,255,255,.7);border-radius:8px;padding:8px 12px;
  }
  #customRequestModal .artist-offer-slot-range .ms{font-size:18px;color:var(--muted)}

  /* Decline modal (Bookpay shell) */
  #customDeclineModal.ov{
    display:none;position:fixed;inset:0;z-index:420;
    background:rgba(20,10,30,.45);padding:16px;
    align-items:center;justify-content:center;
  }
  #customDeclineModal.ov.open{display:flex}
  #customDeclineModal .modal{
    width:min(480px,100%);background:#fff;border-radius:22px;
    box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    display:flex;flex-direction:column;max-height:calc(100vh - 32px);
  }
  #customDeclineModal .mh{
    padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
  }
  #customDeclineModal .mh h2{font-size:18px;font-weight:800;margin:0}
  #customDeclineModal .mx{
    border:0;background:none;cursor:pointer;color:var(--muted);
    width:36px;height:36px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
  }
  #customDeclineModal .mx:hover{background:#F7F3F9;color:var(--ink)}
  #customDeclineModal .mb{padding:18px 24px;overflow-y:auto}
  #customDeclineModal .mf{
    display:flex;gap:10px;justify-content:flex-end;
    padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;
  }
  #customDeclineModal textarea.in{
    display:block;min-height:100px;resize:vertical;width:100%;
    font-size:13.5px;line-height:1.45;padding:10px 13px;
  }
  #customDeclineModal .hidden{display:none!important}

  @media (max-width:900px){
    #customRequestModal .d01-grid{grid-template-columns:1fr;gap:18px}
    #customRequestModal .d01-qa{grid-template-columns:1fr}
    #customRequestModal .d01-quote-grid{grid-template-columns:1fr}
    #customRequestModal .mf{flex-wrap:wrap}
    #customRequestModal .mf .btn{flex:1 1 auto}
    #customRequestModal .mf .btn-decline,
    #customRequestModal .mf .btn-sendback{margin-right:0;order:-1;width:100%}
    #customRequestModal .modal.xl{max-height:calc(100vh - 16px);max-height:calc(100dvh - 16px);border-radius:16px}
  }
  @media (max-width:640px){
    #customRequestModal .artist-slot-dt-row{grid-template-columns:1fr;gap:10px}
  }
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    }
</style>

<!-- Custom / guest request detail modal (D01) -->
<div class="ov" id="customRequestModal" role="dialog" aria-modal="true" aria-labelledby="customRequestTitle" onclick="closeCustomModalOnBackdrop(event)">
  <div class="modal xl" onclick="event.stopPropagation()">
    <div class="mh">
      <div>
        <h2 id="customRequestTitle">Custom request details</h2>
        <div class="sub hidden" id="customRequestSubtitle"></div>
      </div>
      <button type="button" class="mx" onclick="closeCustomRequestDetail()" aria-label="Close">
        <span class="ms faint">close</span>
        </button>
      </div>
    <div class="mb">
      <div id="customRequestDetailPanel" class="d01-grid">
        <div id="customRequestDetailLeft"></div>
        <div id="customRequestDetailQuote"></div>
        </div>
      </div>
    <div class="mf" id="customRequestDetailFooter">
      <button type="button" id="customDeclineFooterBtn" class="btn ghost btn-decline hidden" onclick="CustomRequestUI.openCustomDeclineFromFooter()">
        <span class="ms">block</span>Decline
      </button>
      <button type="button" id="customSendBackFooterBtn" class="btn ghost btn-sendback hidden" onclick="CustomRequestUI.openCustomSendBackFromFooter()">
        <span class="ms">undo</span>Send back to studio
      </button>
      <button type="button" class="btn ghost" onclick="closeCustomRequestDetail()">Close</button>
      <button type="button" id="customQuoteSubmitBtn" class="btn hidden" onclick="CustomRequestUI.submitCustomQuote()">
        <span class="ms">send</span>Send quote
        </button>
    </div>
  </div>
</div>

<!-- Decline custom request modal -->
<div class="ov" id="customDeclineModal" role="dialog" aria-modal="true" aria-labelledby="customDeclineTitle" onclick="closeCustomDeclineModalOnBackdrop(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="mh">
        <div>
        <h2 id="customDeclineTitle">Decline request</h2>
        </div>
      <button type="button" class="mx" onclick="closeCustomDeclineModal()" aria-label="Close">
        <span class="ms faint">close</span>
        </button>
      </div>
    <div class="mb">
      <p class="muted" style="font-size:13.5px;margin-bottom:14px;line-height:1.5">Share why you are declining this custom request. The client will be notified by email.</p>
      <label class="fl" for="customDeclineReason">Reason for decline</label>
      <textarea id="customDeclineReason" class="in" rows="4" maxlength="2000" placeholder="e.g. Style does not match my portfolio, books are full for custom work…"></textarea>
      <p id="customDeclineError" class="hidden" style="color:#C62828;font-size:13px;font-weight:600;margin-top:8px"></p>
    </div>
    <div class="mf">
      <button type="button" class="btn ghost" onclick="closeCustomDeclineModal()">Cancel</button>
      <button type="button" id="customDeclineSubmitBtn" class="btn" style="background:#C62828" onclick="submitCustomDecline()">
        <span class="ms">send</span>Submit decline
      </button>
    </div>
  </div>
</div>

<script>
/* iife-wrapped custom */
(function () {

  const requestScope = 'unified';
  const customRequestsById = @json(collect($customRequestsPayload ?? [])->keyBy('id'));
  (function mergeStudioOffersIntoPanel() {
    var offers = @json(collect($studioOffersPayload ?? [])->keyBy('studioOfferId'));
    Object.keys(offers || {}).forEach(function (sid) {
      var req = offers[sid];
      if (!req) return;
      var key = req.id || ('studio:' + sid);
      customRequestsById[key] = req;
      customRequestsById['studio:' + sid] = req;
    });
  })();
  const canSendQuotes = @json($canSendQuotes ?? true);
  const customQuotesBlockedMessage = @json($quotesBlockedMessage ?? null);
  const customPaymentSettingsUrl = @json(route('settings.payment'));
  const customDeclineRequestUrlTemplate = @json(route('artist.custom-requests.decline', ['customRequest' => 0]));
  const sendQuoteUrlTemplate = @json(route('artist.custom-requests.send-quote', ['customRequest' => 0]));
  let customCurrentStatusFilter = 'all';
  let customDeclineRequestId = null;
  let activeCustomRequestId = null;

  function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function isImageUrl(value) {
    var text = String(value || '').trim();
    if (!text) return false;
    return /^https?:\/\//i.test(text) || text.indexOf('/uploads/') !== -1 || text.indexOf('uploads/') === 0;
  }

  function formatAnswer(answer, answerType) {
    if (window.QuestionAnswerDisplay) {
      var imageUrls = window.QuestionAnswerDisplay.imageUrlsFromAnswer(answer, answerType);
      if (imageUrls.length) {
        return window.QuestionAnswerDisplay.buildImageCountHtml(imageUrls);
      }
      return escapeHtml(window.QuestionAnswerDisplay.formatAnswerForReview(answer, answerType));
    }
    if (typeof answer === 'boolean') return answer ? 'Yes' : 'No';
    if (Array.isArray(answer) && answer.length && answer.every(isImageUrl)) {
      return answer.length === 1 ? '1 picture' : (answer.length + ' pictures');
    }
    if (typeof answer === 'string' && isImageUrl(answer)) {
      return '1 picture';
    }
    return escapeHtml(String(answer || '—'));
  }

  function customFilterByStatus(status) { /* list filters handled on page */ }

  function customApplyFilters() {
    if (typeof window.applyRequestsFeedFilters === 'function') {
      window.applyRequestsFeedFilters();
      return;
    }
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

  function customStatusPillClass(req) {
    if (!req) return 'p';
    if (req.status === 'confirmed') return 'b';
    if (req.status === 'moved_to_booking') return 'g';
    if (req.status === 'cancelled') return 'r';
    return 'p';
  }

  function qaCell(label, valueHtml) {
    return '<div class="d01-qa-item"><div class="lbl">' + escapeHtml(label) + '</div><div class="val">' + valueHtml + '</div></div>';
  }

  function qaPlain(label, value) {
    var v = (value == null || String(value).trim() === '') ? '—' : String(value);
    return qaCell(label, escapeHtml(v));
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

    var html = '<div class="tint" style="padding:14px 16px;margin-top:6px">' +
      '<div class="d01-section-title"><span class="ms">event_available</span>Client availability</div>';

    if (!hasAny) {
      return html + '<p class="faint" style="font-size:13px;margin-top:10px;font-style:italic">No availability details provided.</p></div>';
    }

      preferredDates.forEach(function(pref, idx) {
        var num = pref.preference || (idx + 1);
        var timesHtml = '';
        (pref.times || []).forEach(function(t) {
        timesHtml += '<span class="pill nd p">' + escapeHtml(t) + '</span>';
      });
      if (!timesHtml) timesHtml = '<span class="pill nd p">Any time</span>';
      html += '<div class="d01-avail-card" style="' + (idx === 0 ? 'margin-top:10px' : '') + '">' +
        '<span class="d01-avail-num">' + escapeHtml(String(num)) + '</span>' +
        '<b style="font-size:13.5px">' + escapeHtml(pref.dateLabel || pref.date) + '</b>' +
        timesHtml + '</div>';
    });

    if (preferredDays.length) {
      html += '<div style="margin-top:12px"><div class="lbl" style="font-size:10.5px;margin-bottom:6px">Preferred days</div><div class="row" style="gap:8px;flex-wrap:wrap">';
      preferredDays.forEach(function(day) {
        html += '<span class="pill nd p">' + escapeHtml(day) + '</span>';
      });
      html += '</div></div>';
    }

    var metaItems = [];
    if (flexibility) metaItems.push({ label: 'Flexibility', value: flexibility });
    if (urgency) metaItems.push({ label: 'Urgency', value: urgency });
    if (sessionGap) metaItems.push({ label: 'Session gap', value: sessionGap });
    if (metaItems.length) {
      html += '<div class="d01-qa" style="margin-top:10px">';
      metaItems.forEach(function(item) {
        html += qaPlain(item.label, item.value);
      });
      html += '</div>';
    }

    if (avoidDates) {
      html += '<div class="d01-avail-card" style="background:#FFF7ED;border:1px solid #FED7AA;margin-top:10px">' +
        '<span class="ms" style="color:#9A3412">event_busy</span>' +
        '<span style="font-size:13px;font-weight:500;color:#9A3412">' + escapeHtml(avoidDates) + '</span></div>';
    }

    html += '</div>';
    return html;
  }

  function buildGuestSpotHtml(guestSpot) {
    if (!guestSpot) return '';
    var lines = [];
    if (guestSpot.city || guestSpot.country) {
      lines.push('<div style="font-weight:700;font-size:14px">' + escapeHtml([guestSpot.city, guestSpot.country].filter(Boolean).join(', ')) + '</div>');
    }
    if (guestSpot.fromDate && guestSpot.toDate) {
      lines.push('<div class="muted" style="font-size:13px;margin-top:4px">' + escapeHtml(guestSpot.fromDate + ' – ' + guestSpot.toDate) + '</div>');
    }
    if (guestSpot.availabilityTime) {
      lines.push('<div class="muted" style="font-size:13px;margin-top:4px">Daily hours: ' + escapeHtml(guestSpot.availabilityTime) + '</div>');
    }
    if (guestSpot.studio) {
      lines.push('<div style="font-size:13px;margin-top:8px"><b>Studio:</b> ' + escapeHtml(guestSpot.studio) + '</div>');
    }
    if (guestSpot.location) {
      lines.push('<div class="muted" style="font-size:13px;margin-top:4px">' + escapeHtml(guestSpot.location) + '</div>');
    }
    if (guestSpot.tracksCapacity) {
      if (guestSpot.isFull) {
        lines.push('<div style="font-size:13px;font-weight:700;margin-top:8px;color:#B8860B">All slots are full</div>');
      } else if (guestSpot.remainingSpotsLabel) {
        lines.push('<div style="font-size:13px;font-weight:700;margin-top:8px;color:#15803d">' + escapeHtml(guestSpot.remainingSpotsLabel) + '</div>');
      }
    }
    if (!lines.length) return '';
    return '<div class="tint" style="padding:14px 16px;margin-top:6px">' +
      '<div class="d01-section-title"><span class="ms">luggage</span>Guest spot availability</div>' +
      '<div style="background:#fff;border-radius:12px;padding:12px 14px;margin-top:10px">' + lines.join('') + '</div></div>';
  }

  function answerHasImages(item) {
    if (!item) return false;
    var type = String(item.question_type || item.type || '');
    if (window.QuestionAnswerDisplay && typeof window.QuestionAnswerDisplay.imageUrlsFromAnswer === 'function') {
      return window.QuestionAnswerDisplay.imageUrlsFromAnswer(item.answer, type).length > 0;
    }
    if (Array.isArray(item.answer) && item.answer.length && item.answer.every(isImageUrl)) return true;
    return typeof item.answer === 'string' && isImageUrl(item.answer);
  }

  function buildCustomRequestLeftHtml(req) {
    var cells = [];
    (req.questionsAnswers || []).forEach(function(item) {
      if (!item || !item.question) return;
      // Images render as thumbnails under Reference images — skip "1 picture" text rows.
      if (answerHasImages(item)) return;
      cells.push(qaCell(item.question, formatAnswer(item.answer, item.type)));
    });
    var questionsHtml = cells.length
      ? '<div class="d01-qa">' + cells.join('') + '</div>'
      : '<p class="faint" style="font-size:13px;font-style:italic;margin:10px 0">No question answers recorded.</p>';

    var availabilityHtml = (req.type === 'managed' && !req.isGuest) ? buildAvailabilityHtml(req.availabilityDetails || {}) : '';
    var guestSpotHtml = req.isGuest ? buildGuestSpotHtml(req.guestSpot || null) : '';
    var requestTitle = req.isGuest ? 'Guest spot request' : 'Custom tattoo request';
    var requestIcon = req.isGuest ? 'luggage' : 'brush';
    var metaLine = [req.schedulingLabel || 'Auto scheduling']
      .concat((req.referralSource && req.referralSource !== '—') ? ['Referral: ' + req.referralSource] : [])
      .filter(function(x) { return x && String(x).trim(); })
      .join(' · ');

    var messageClientHtml = req.chatUrl
      ? '<a href="' + escapeHtml(req.chatUrl) + '" class="btn sm ghost" style="margin-left:auto;text-decoration:none">' +
        '<span class="ms" style="font-size:16px">chat_bubble</span>Message client</a>'
      : '';
    var contactNote = req.chatUrl && !req.isBooked
      ? '<div class="faint" style="font-size:12px;margin:6px 0 12px 66px">Full contact details unlock after payment.</div>'
      : '<div style="height:8px"></div>';

    var notes = String(req.additionalNotes || '').trim();
    var notesHtml = (!notes || notes === '—')
      ? '<div class="faint" style="font-size:13.5px">None</div>'
      : '<div style="font-size:13.5px;line-height:1.5;white-space:pre-line">' + escapeHtml(notes) + '</div>';

    var viaStudio = String(req.viaStudio || '').trim();
    var studioNote = String(req.studioNote || '').trim();
    var viaBanner = viaStudio
      ? '<div class="d01-via-studio"><span class="ms" style="font-size:18px">storefront</span>' +
        '<span>Via ' + escapeHtml(viaStudio) +
        (studioNote ? ' · “' + escapeHtml(studioNote) + '”' : '') +
        (req.isStudioOffer ? ' · Sending a quote takes this request' : '') +
        '</span></div>'
      : '';
    var viaPill = viaStudio
      ? '<span class="pill nd" style="background:#F3E8FF;color:#3E007C">Via ' + escapeHtml(viaStudio) + '</span>'
      : '';
    var imagesHtml = '';
    if (Array.isArray(req.images) && req.images.length) {
      imagesHtml = '<div style="padding-top:12px"><div class="lbl" style="font-size:10.5px;margin-bottom:8px">Reference images</div>' +
        '<div class="row" style="gap:8px;flex-wrap:wrap">' +
        req.images.map(function (src) {
          return '<a href="' + escapeHtml(src) + '" target="_blank" rel="noopener" style="width:72px;height:72px;border-radius:10px;overflow:hidden;border:1px solid var(--line);display:block;background:#F7F3F9">' +
            '<img src="' + escapeHtml(src) + '" alt="" style="width:100%;height:100%;object-fit:cover;display:block"></a>';
        }).join('') +
        '</div></div>';
    }

    return '' +
      viaBanner +
      '<div class="row" style="gap:14px">' +
        '<div class="ini" style="width:52px;height:52px;font-size:18px">' + escapeHtml(req.clientInitials) + '</div>' +
        '<div style="flex:1;min-width:0">' +
          '<b style="font-size:18px">' + escapeHtml(req.clientName) + '</b>' +
          '<div class="row" style="gap:8px;margin-top:4px;flex-wrap:wrap">' +
            '<span class="pill ' + customStatusPillClass(req) + '">' + escapeHtml(req.filterStatus) + '</span>' +
            viaPill +
            '<span class="faint" style="font-size:12.5px">' + escapeHtml(req.submittedAt) + ' · ' + escapeHtml(req.reference) + '</span>' +
      '</div>' +
        '</div>' +
        messageClientHtml +
      '</div>' +
      contactNote +
      '<div class="tint row" style="padding:12px 14px;gap:12px">' +
        '<div class="ic"><span class="ms">' + requestIcon + '</span></div>' +
        '<div style="flex:1;min-width:0"><b>' + escapeHtml(requestTitle) + '</b>' +
        '<div class="faint" style="font-size:12.5px">' + escapeHtml(metaLine) + '</div></div>' +
      '</div>' +
      questionsHtml +
      guestSpotHtml +
      availabilityHtml +
      imagesHtml +
      '<div style="padding-top:10px"><div class="lbl" style="font-size:10.5px">Additional notes</div>' + notesHtml + '</div>';
  }

  function isManagedCustomRequest(req) {
    return req && req.type === 'managed';
  }

  function needsQuoteSessionSlots(req) {
    return isManagedCustomRequest(req) && !(req && req.isGuest);
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

  function buildArtistSlotsReadOnlyPanel(title, icon, slots) {
    if (!slots || !slots.length) return '';
    var html = '<div class="artist-slots-panel artist-slots-panel--session artist-slots-panel--readonly">' +
      '<div class="row" style="gap:8px;margin-bottom:6px"><span class="ms">' + icon + '</span><b style="font-size:15px">' + escapeHtml(title) + '</b></div>';
    slots.forEach(function(slot) {
      html += '<div class="artist-slot-block">';
      html += '<b style="font-size:12px;letter-spacing:.6px">' + escapeHtml(formatOfferDate(slot.date)) + '</b>';
      html += '<ul class="artist-offer-slot-ranges" style="margin-top:8px">';
      (slot.ranges || []).forEach(function(range) {
        html += '<li class="artist-offer-slot-range"><span class="ms">schedule</span><span>' +
          escapeHtml(formatOfferTime(range.from)) + ' – ' + escapeHtml(formatOfferTime(range.to)) + '</span></li>';
      });
      html += '</ul></div>';
    });
    html += '</div>';
    return html;
  }

  function buildCustomQuoteSessionSlotsPanel() {
    return '<div class="artist-slots-panel artist-slots-panel--session" data-slots-kind="session" data-field-key="artist_session_slots">' +
      '<div class="row" style="gap:8px"><span class="ms">brush</span><b>Tattoo session times</b></div>' +
      '<div class="faint" style="font-size:12.5px;margin:2px 0 4px">Offer dates and start times when you can do this tattoo. Not tied to the client\'s preferred dates. End time uses your estimated duration.</div>' +
      '<div id="sessionSlotsBlocks" class="slots-blocks-container"></div>' +
      '<p id="sessionSlotsError" class="hidden" style="color:#C62828;font-size:13px;font-weight:600;margin-top:8px"></p>' +
      '<button type="button" onclick="CustomRequestUI.addSlotDateBlock(\'session\')" class="artist-slots-add-date">+ Add another date</button>' +
      '</div>';
  }

  function slotsContainer(kind) {
    return document.querySelector('#customRequestModal #' + kind + 'SlotsBlocks');
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

  function syncBlockEndsLabel(block, kind) {
    if (!block) return;
    var endsEl = block.querySelector('.artist-slot-starts-ends');
    if (!endsEl) return;
    var duration = getDurationMinutesForSlotsKind(kind);
    var ends = [];
    block.querySelectorAll('.artist-slot-time-from').forEach(function(inp) {
      var from = (inp.value || '').slice(0, 5);
      if (!from || !duration) return;
    var end = addMinutesToTime(from, duration);
      if (end) ends.push(end.slice(0, 5));
    });
    endsEl.textContent = ends.length ? '(ends ' + ends.join(', ') + ')' : '';
  }

  function syncAllTimeRowEndHints(kind) {
    var container = slotsContainer(kind);
    if (!container) return;
    container.querySelectorAll('.artist-slot-block').forEach(function(block) {
      syncBlockEndsLabel(block, kind);
    });
  }

  function markSlotInputInvalid(inp, on) {
    if (!inp) return;
    inp.classList.toggle('is-invalid', !!on);
    inp.classList.toggle('border-error', !!on);
  }

  function focusSlotInput(el) {
    if (!el) return null;
    return el.__bpx || el;
  }

  function buildTimeRangeRowHtml(range, kind) {
    range = range || { from: '', to: '' };
    var from = range.from || '';
    return '<div class="artist-slot-time-row">' +
      '<input type="time" class="in artist-slot-time-from" value="' + escapeHtml(from) + '" step="1800" placeholder="Select time" data-ph="Select time" aria-label="Start time" onchange="CustomRequestUI.onArtistSlotFieldChange(this)">' +
      '<button type="button" onclick="CustomRequestUI.removeSlotTimeRow(this)" class="artist-slot-rmx" title="Remove start time" aria-label="Remove start time">' +
        '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>' +
      '</button>' +
      '</div>';
  }

  function buildSlotBlockHtml(kind, index, minDate, slot) {
    slot = slot || { date: '', ranges: [{ from: '', to: '' }] };
    var ranges = (slot.ranges && slot.ranges.length) ? slot.ranges : [{ from: '', to: '' }];
    var timeRows = ranges.map(function(r) { return buildTimeRangeRowHtml(r, kind); }).join('');
    var ends = [];
    var duration = getDurationMinutesForSlotsKind(kind);
    ranges.forEach(function(r) {
      if (!r.from || !duration) return;
      var end = addMinutesToTime(r.from, duration);
      if (end) ends.push(end.slice(0, 5));
    });
    var endsText = ends.length ? '(ends ' + ends.join(', ') + ')' : '';
    return '<div class="artist-slot-block" data-slot-index="' + index + '">' +
      '<div class="row" style="justify-content:space-between;margin-bottom:10px">' +
        '<b class="slot-block-label" style="font-size:12px;letter-spacing:.6px">SESSION DATE ' + (index + 1) + '</b>' +
        '<button type="button" onclick="CustomRequestUI.removeSlotDateBlock(\'' + kind + '\', this)" class="btn sm ghost artist-slot-remove-date" style="padding:4px 8px;font-size:12px;visibility:hidden"><span class="ms" style="font-size:16px">delete</span>Remove</button>' +
      '</div>' +
      '<div class="artist-slot-dt-row">' +
        '<div class="artist-slot-dt-date">' +
          '<span class="fl">Date</span>' +
          '<input type="date" class="in artist-slot-date" min="' + escapeHtml(minDate) + '" value="' + escapeHtml(slot.date || '') + '" placeholder="Select date" data-ph="Select date" aria-label="Date" onchange="CustomRequestUI.onArtistSlotFieldChange(this)">' +
        '</div>' +
        '<div class="artist-slot-dt-times">' +
          '<span class="fl artist-slot-starts-label">Start times <span class="artist-slot-starts-ends faint">' + escapeHtml(endsText) + '</span></span>' +
      '<div class="artist-slot-times">' + timeRows + '</div>' +
          '<button type="button" onclick="CustomRequestUI.addSlotTimeRow(this)" class="artist-slot-add-time">+ Add another start time</button>' +
        '</div>' +
      '</div>' +
      '<p class="artist-slot-block-error hidden" role="alert"></p>' +
      '</div>';
  }

  function slotsPanelKindFromEl(el) {
    var panel = el && el.closest ? el.closest('[data-slots-kind]') : null;
    return panel ? panel.dataset.slotsKind : null;
  }

  function setPanelSlotsError(kind, message) {
    var el = document.querySelector('#customRequestModal #' + kind + 'SlotsError');
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
    var container = slotsContainer(kind);
    if (!container) return;
    container.querySelectorAll('.artist-slot-date, .artist-slot-time-from').forEach(function(d) {
      markSlotInputInvalid(d, false);
    });
    container.querySelectorAll('.artist-slot-block').forEach(function(b) {
      b.classList.remove('is-duplicate-date', 'is-incomplete-block');
      setBlockInlineError(b, '');
    });
    container.querySelectorAll('.artist-slot-time-row').forEach(function(r) {
      r.classList.remove('is-conflict');
    });
  }

  function markDuplicateDates(kind) {
    var container = slotsContainer(kind);
    if (!container) return false;
    var byDate = {};
    container.querySelectorAll('.artist-slot-block').forEach(function(block) {
      var dateInput = block.querySelector('.artist-slot-date');
      var date = (dateInput || {}).value || '';
      block.classList.remove('is-duplicate-date');
      markSlotInputInvalid(dateInput, false);
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
          markSlotInputInvalid(block.querySelector('.artist-slot-date'), true);
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
      markSlotInputInvalid(fromInput, false);
      var from = fromInput.value;
      var rowLabel = rows.length > 1 ? ('Slot ' + (rowIndex + 1) + ': ') : '';
      if (!from) {
        markSlotInputInvalid(fromInput, true);
        messages.push(rowLabel + 'enter a start time, or remove this slot.');
        return;
      }
      var endTime = addMinutesToTime(from, durationMinutes);
      if (!endTime) {
        markSlotInputInvalid(fromInput, true);
        messages.push(rowLabel + 'start time is too late for a ' + durationLabel + ' session.');
        return;
      }
      ranges.push({ start: timeToMinutes(from), end: timeToMinutes(endTime), row: row });
    });
    syncBlockEndsLabel(block, kind);
    if (messages.length) return { ok: false, message: messages.join(' ') };
    // Start times are alternative options for the client — overlapping windows are OK.
    // Only reject the same start time twice on one date.
    ranges.sort(function(a, b) { return a.start - b.start; });
    for (var i = 1; i < ranges.length; i++) {
      if (ranges[i].start === ranges[i - 1].start) {
        ranges[i - 1].row.classList.add('is-conflict');
          ranges[i].row.classList.add('is-conflict');
        return { ok: false, message: 'You already offered that start time on this date — pick a different time, or remove the duplicate.' };
      }
    }
    return { ok: true, ranges: ranges };
  }

  function validateBlockRequired(block, kind) {
    var dateInput = block.querySelector('.artist-slot-date');
    var date = dateInput ? dateInput.value : '';
    if (!date) {
      markSlotInputInvalid(dateInput, true);
      block.classList.add('is-incomplete-block');
      block.querySelectorAll('.artist-slot-time-from').forEach(function(inp) {
        if (!inp.value) markSlotInputInvalid(inp, true);
      });
      return { ok: false, message: 'Select a date for this entry, or remove it if you do not need it.', block: block, focusEl: focusSlotInput(dateInput) };
    }
    var rangeCheck = validateRangesInBlock(block, kind);
    if (!rangeCheck.ok) {
      block.classList.add('is-incomplete-block');
      var badTime = block.querySelector('.artist-slot-time-from.is-invalid') || block.querySelector('.artist-slot-time-from');
      return { ok: false, message: rangeCheck.message, block: block, focusEl: focusSlotInput(badTime) };
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
    var container = slotsContainer(kind);
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
        markSlotInputInvalid(dateInput, true);
        addFailure({ ok: false, message: dupMsg, block: block, focusEl: focusSlotInput(dateInput) });
      });
    }
    if (!hasComplete && failures.length === 0 && blocks.length) {
      var firstBlock = blocks[0];
      if (firstBlock) {
        var dateEl = firstBlock.querySelector('.artist-slot-date');
        markSlotInputInvalid(dateEl, true);
        firstBlock.querySelectorAll('.artist-slot-time-from').forEach(function(inp) { markSlotInputInvalid(inp, true); });
        addFailure({ ok: false, message: 'Add at least one date with a start time.', block: firstBlock, focusEl: focusSlotInput(dateEl) });
      }
    }
    if (failures.length || !hasComplete) {
      var summary = buildSlotsPanelSummaryMessage(failures.length, hasComplete);
      var first = failures[0] || null;
      return { ok: false, kind: kind, panelLabel: 'Tattoo session', message: summary, block: first ? first.block : (blocks[0] || null), focusEl: first ? first.focusEl : null, failures: failures, hasComplete: hasComplete };
    }
    return { ok: true, kind: kind, panelLabel: 'Tattoo session', failures: [], hasComplete: true };
  }

  // Runs only on Send quote — field changes just clear marks (see onArtistSlotFieldChange).
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
    var block = input && input.closest ? input.closest('.artist-slot-block') : null;
    if (block) syncBlockEndsLabel(block, kind);
    else syncAllTimeRowEndHints(kind);
    // Validation errors only on Send quote — clear stale marks as the artist edits.
    clearSlotValidationMarks(kind);
    setPanelSlotsError(kind, '');
    hideQuoteFormGeneralError();
  }

  function syncSlotBlockRemoveVisibility(kind) {
    var container = slotsContainer(kind);
    if (!container) return;
    var blocks = container.querySelectorAll('.artist-slot-block');
    blocks.forEach(function(block) {
      var rm = block.querySelector('.artist-slot-remove-date');
      if (rm) rm.style.visibility = blocks.length > 1 ? '' : 'hidden';
    });
  }

  function hydrateSlotBlocks(kind, slots, minDate) {
    var container = slotsContainer(kind);
    if (!container) return;
    minDate = minDate || todayYmd();
    if (slots && slots.length) {
      container.innerHTML = slots.map(function(slot, i) { return buildSlotBlockHtml(kind, i, minDate, slot); }).join('');
      syncAllTimeRowEndHints(kind);
      syncSlotBlockRemoveVisibility(kind);
      return;
    }
    if (!container.children.length) {
      container.innerHTML = buildSlotBlockHtml(kind, 0, minDate);
      syncSlotBlockRemoveVisibility(kind);
    }
  }

  function reindexSlotBlocks(kind) {
    var container = slotsContainer(kind);
    if (!container) return;
    container.querySelectorAll('.artist-slot-block').forEach(function(block, i) {
      block.dataset.slotIndex = String(i);
      var label = block.querySelector('.slot-block-label');
      if (label) label.textContent = 'SESSION DATE ' + (i + 1);
    });
    syncSlotBlockRemoveVisibility(kind);
  }

  function addSlotDateBlock(kind) {
    var container = slotsContainer(kind);
    if (!container) return;
    var index = container.querySelectorAll('.artist-slot-block').length;
    container.insertAdjacentHTML('beforeend', buildSlotBlockHtml(kind, index, todayYmd()));
    reindexSlotBlocks(kind);
    onArtistSlotFieldChange(container);
  }

  function removeSlotDateBlock(kind, btn) {
    var container = slotsContainer(kind);
    if (!container) return;
    var blocks = container.querySelectorAll('.artist-slot-block');
    if (blocks.length <= 1) return;
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
      if (kind) syncBlockEndsLabel(block, kind);
      onArtistSlotFieldChange(rows[0]);
      return;
    }
    btn.closest('.artist-slot-time-row').remove();
    if (kind) syncBlockEndsLabel(block, kind);
    onArtistSlotFieldChange(block);
  }

  function collectSlotsFromPanel(kind) {
    var container = slotsContainer(kind);
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
    return '<div class="quote-field-group" data-quote-field="' + fieldKey + '">' +
      '<label for="' + cfg.inputId + '" class="fl">' + label + '</label>' +
      inputHtml +
      (hintHtml || '') +
      '<p id="' + cfg.errorId + '" class="quote-field-error hidden" role="alert"></p>' +
      '</div>';
  }

  function buildSetPayoutPanelHtml() {
    var detail = customQuotesBlockedMessage
      ? escapeHtml(customQuotesBlockedMessage)
      : "Clients pay a deposit to book your custom work, so you can't send quotes until payouts are set up. Connect your bank account through Stripe first. It takes about 5 minutes, and this request stays here waiting for you.";
    return '<div class="d01-payout-gate">' +
      '<div class="row" style="gap:10px"><span class="ms" style="color:#3E007C">account_balance</span><b style="font-size:16px">Finish payouts to reply</b></div>' +
      '<div class="muted" style="font-size:13.5px;margin:6px 0 14px;line-height:1.5">' + detail + '</div>' +
      '<a href="' + escapeHtml(customPaymentSettingsUrl) + '" class="btn" style="width:100%;justify-content:center;text-decoration:none">' +
      '<span class="ms">link</span>Finish payouts</a>' +
      '<div class="faint" style="font-size:12px;margin-top:10px;text-align:center">You can still read the request and message the client.</div>' +
      '</div>' +
      '<div class="d01-locked-offer" aria-disabled="true">' +
      '<div class="row" style="gap:8px"><span class="ms">lock</span><b style="font-size:15px">Send quote</b></div>' +
      '<div class="faint" style="font-size:12.5px;margin-top:4px">Unlocks when payouts are set up.</div>' +
      '</div>';
  }

  function buildQuoteFormHtml(req) {
    if (!canSendQuotes) {
      return buildSetPayoutPanelHtml();
    }

    var price = req.estimatedPrice != null && req.estimatedPrice !== '' ? String(req.estimatedPrice) : '';
    var duration = req.estimatedTime ? escapeHtml(req.estimatedTime) : '';
    var sessions = req.numberOfSessions ? escapeHtml(req.numberOfSessions) : '';
    var message = req.messageForClient ? escapeHtml(req.messageForClient) : '';
    var guestSpotFull = !!(req.isGuest && req.guestSpot && req.guestSpot.isFull);
    var dis = guestSpotFull ? ' disabled' : '';
    var guestFullBanner = guestSpotFull
      ? '<div class="d01-guest-banner">All slots are full. You cannot send a quote for this guest spot.</div>'
      : '';

    return '<form class="tint" style="padding:18px" id="customQuoteForm" onsubmit="return false;" novalidate>' +
      '<div class="row" style="gap:8px"><span class="ms">send</span><b style="font-size:16px">Send quote</b></div>' +
      '<div class="faint" style="font-size:12.5px;margin:2px 0 12px">' + (req.isGuest
        ? 'Send your price and message. The client will book within your guest spot availability.'
        : 'Fill in all fields to send your quote to the client.') + '</div>' +
      guestFullBanner +
      '<div class="d01-quote-grid">' +
        quoteFieldBlock('estimated_price', 'Estimated price (€)',
          '<input type="text" class="in" id="quoteEstimatedPrice" name="estimated_price" inputmode="decimal" autocomplete="off" placeholder="e.g. 350" value="' + escapeHtml(price) + '"' + dis + '>') +
        quoteFieldBlock('estimated_time', 'Duration',
          '<input type="text" class="in" id="quoteEstimatedDuration" name="estimated_time" placeholder="e.g. 3–4 hours" value="' + duration + '"' + dis + '>') +
        quoteFieldBlock('number_of_sessions', 'Sessions',
          '<input type="text" class="in" id="quoteNumberOfSessions" name="number_of_sessions" placeholder="e.g. 1" value="' + sessions + '"' + dis + '>') +
      '</div>' +
      '<div style="margin-top:12px">' +
        quoteFieldBlock('message_for_client', 'Message to client',
          '<textarea class="in" id="quoteMessageForClient" name="message_for_client" rows="4" maxlength="2000" placeholder="Describe your quote, design approach, deposit requirements, or next steps…"' + dis + '>' + message + '</textarea>',
          '<div class="help">Max 2,000 characters</div>') +
      '</div>' +
      (needsQuoteSessionSlots(req) ? buildCustomQuoteSessionSlotsPanel() : '') +
      '<p id="quoteFormGeneralError" class="hidden" style="color:#C62828;font-size:13px;font-weight:600;margin-top:10px" role="alert"></p>' +
      '</form>';
  }

  function buildReadoutCell(label, value) {
    return '<div><span class="fl">' + escapeHtml(label) + '</span><div class="in d01-readout">' + escapeHtml(value) + '</div></div>';
  }

  function buildQuoteReadOnlyHtml(req) {
    var priceLabel = req.estimatedPrice != null && parseFloat(req.estimatedPrice) > 0
      ? '€' + parseFloat(req.estimatedPrice).toFixed(2)
      : '—';

    return '<div class="tint" style="padding:18px">' +
      '<div class="row" style="gap:8px"><span class="ms">request_quote</span><b style="font-size:16px">Quote sent</b></div>' +
      '<div class="faint" style="font-size:12.5px;margin:2px 0 12px">Details you shared with the client.</div>' +
      '<div class="d01-quote-grid">' +
        buildReadoutCell('Estimated price', priceLabel) +
        buildReadoutCell('Duration', req.estimatedTime || '—') +
        buildReadoutCell('Sessions', req.numberOfSessions || '—') +
      '</div>' +
      '<div style="margin-top:12px"><span class="fl">Message to client</span>' +
        '<div class="in d01-readout" style="min-height:auto;align-items:flex-start;font-size:13px;font-weight:500;white-space:pre-line;line-height:1.45">' + escapeHtml(req.messageForClient || '—') + '</div></div>' +
      (needsQuoteSessionSlots(req) && (req.artistSessionSlots || []).length
        ? buildArtistSlotsReadOnlyPanel('Tattoo session times', 'brush', req.artistSessionSlots)
        : '') +
      '</div>';
  }

  function buildQuotePanelHtml(req) {
    if (req.status === 'cancelled') {
      var declinedNote = req.reasonDecline
        ? '<p style="font-size:13.5px;margin-top:10px;line-height:1.5"><span class="muted" style="font-weight:700">Decline reason</span><br><span style="white-space:pre-line">' + escapeHtml(req.reasonDecline) + '</span></p>'
        : '<p class="faint" style="font-size:13px;margin-top:8px">No decline reason was provided.</p>';
      return '<div class="d01-state-card">' +
        '<div class="row" style="gap:8px"><span class="ms" style="color:#C62828">block</span><b style="font-size:16px">Declined</b></div>' +
        declinedNote + '</div>';
    }
    if (req.status === 'pending') {
      return buildQuoteFormHtml(req);
    }
    if (req.status === 'confirmed' || req.status === 'moved_to_booking' || customRequestHasQuote(req)) {
      return buildQuoteReadOnlyHtml(req);
    }
    return '<div class="d01-state-card muted" style="font-size:13.5px">' +
      '<b style="color:var(--ink)">No quote yet</b>' +
      '<div style="margin-top:4px">This request is <strong>' + escapeHtml(req.filterStatus) + '</strong>. No quotation was recorded.</div></div>';
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
        // Duration drives end times — refresh labels and drop stale slot errors (validated on Send quote).
        syncAllTimeRowEndHints('session');
        clearSlotValidationMarks('session');
        setPanelSlotsError('session', '');
      }
    });
  }

  function updateCustomDetailFooter(req) {
    var declineBtn = document.getElementById('customDeclineFooterBtn');
    var sendBackBtn = document.getElementById('customSendBackFooterBtn');
    var sendBtn = document.getElementById('customQuoteSubmitBtn');
    var subtitle = document.getElementById('customRequestSubtitle');
    var pending = !!(req && req.status === 'pending');
    var guestSpotFull = !!(req && req.isGuest && req.guestSpot && req.guestSpot.isFull);
    var canSendBack = !!(req && req.canSendBack && req.sendBackUrl);

    if (declineBtn) declineBtn.classList.toggle('hidden', !pending || canSendBack);
    if (sendBackBtn) {
      sendBackBtn.classList.toggle('hidden', !canSendBack);
      if (canSendBack) {
        sendBackBtn.setAttribute('data-url', req.sendBackUrl || '');
        sendBackBtn.setAttribute('data-studio', req.viaStudio || '');
      }
    }

    if (sendBtn) {
      if (pending && canSendQuotes) {
        sendBtn.classList.remove('hidden');
        sendBtn.disabled = guestSpotFull;
        sendBtn.innerHTML = guestSpotFull
          ? '<span class="ms">block</span>All slots are full'
          : '<span class="ms">send</span>Send quote';
      } else {
        sendBtn.classList.add('hidden');
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<span class="ms">send</span>Send quote';
      }
    }

    if (subtitle) {
      if (pending && req.viaStudio) {
        subtitle.textContent = 'Via ' + req.viaStudio;
        subtitle.classList.remove('hidden');
      } else if (pending && !canSendQuotes) {
        subtitle.textContent = 'Payouts not set up';
        subtitle.classList.remove('hidden');
      } else {
        subtitle.textContent = '';
        subtitle.classList.add('hidden');
      }
    }
  }

  function openCustomSendBackFromFooter() {
    var btn = document.getElementById('customSendBackFooterBtn');
    if (!btn || btn.classList.contains('hidden')) return;
    if (window.sendBackStudioRequest) window.sendBackStudioRequest(btn);
    else if (window.openStudioSendBackForUrl) {
      window.openStudioSendBackForUrl(btn.getAttribute('data-url'), btn.getAttribute('data-studio'));
    }
  }

  function renderCustomRequestDetail(req) {
    var left = document.getElementById('customRequestDetailLeft');
    var quote = document.getElementById('customRequestDetailQuote');
    var title = document.getElementById('customRequestTitle');
    if (!left || !quote) return;
    if (title) title.textContent = req.isGuest ? 'Guest spot request' : 'Custom request details';
    left.innerHTML = buildCustomRequestLeftHtml(req);
    quote.innerHTML = buildQuotePanelHtml(req);
    bindQuoteFormValidationClear();
    if (canSendQuotes && req.isPending && needsQuoteSessionSlots(req)) {
      hydrateSlotBlocks('session', req.artistSessionSlots || [], todayYmd());
    }
    updateCustomDetailFooter(req);
  }

  function getCustomDeclineUrl(id) {
    return customDeclineRequestUrlTemplate.replace(/\/0\/decline$/, '/' + id + '/decline');
  }

  function getCustomSendQuoteUrl(id) {
    return sendQuoteUrlTemplate.replace(/\/0\/send-quote$/, '/' + id + '/send-quote');
  }

  function resolveSendQuoteUrl(req, id) {
    if (req && req.isStudioOffer && req.sendQuoteUrl) return req.sendQuoteUrl;
    var numericId = String(id || '').replace(/^studio:/, '');
    if (req && req.isStudioOffer && req.studioOfferId) {
      return null; // should have sendQuoteUrl
    }
    return getCustomSendQuoteUrl(numericId);
  }

  function parseQuotePrice(raw) {
    var cleaned = String(raw || '').trim().replace(/€/g, '').replace(/\s+/g, '').replace(',', '.');
    if (!cleaned || !/^\d+(\.\d{1,2})?$/.test(cleaned)) return NaN;
    return parseFloat(cleaned);
  }

  function collectQuoteForm() {
    return {
      estimated_price: parseQuotePrice(document.getElementById('quoteEstimatedPrice')?.value),
      estimated_time: String(document.getElementById('quoteEstimatedDuration')?.value || '').trim(),
      number_of_sessions: String(document.getElementById('quoteNumberOfSessions')?.value || '').trim(),
      message_for_client: String(document.getElementById('quoteMessageForClient')?.value || '').trim(),
    };
  }

  function getQuoteValidationErrors(payload) {
    var errors = {};
    if (!payload.estimated_price || isNaN(payload.estimated_price) || payload.estimated_price < 0.01) {
      errors.estimated_price = 'Please enter a valid number for the price (e.g. 350 or 350.00).';
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
    updateCustomRequestCardFromRequest(req);
    syncGuestSpotFullAcrossCards(req);
    const detailModal = document.getElementById('customRequestModal');
    if (detailModal && detailModal.classList.contains('open')) {
      renderCustomRequestDetail(req);
    }
    customApplyFilters();
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
      updateCustomRequestCardFromRequest(r);
    });
  }

  function focusFirstInvalidQuoteInput() {
    var el = document.querySelector('#customRequestModal .quote-input-invalid');
    if (el) el.focus();
  }

  async function submitCustomQuote() {
    clearQuoteFieldErrors();
    setPanelSlotsError('session', '');
    clearSlotValidationMarks('session');
    const id = activeCustomRequestId;
    if (!id) return;
    const req = customRequestsById[id] || customRequestsById[String(id)];
    const payload = collectQuoteForm();
    const validationErrors = getQuoteValidationErrors(payload);
    if (hasQuoteValidationErrors(validationErrors)) {
      setQuoteFieldErrors(validationErrors);
      focusFirstInvalidQuoteInput();
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
    const quoteUrl = resolveSendQuoteUrl(req, id);
    if (!quoteUrl) {
      showQuoteFormGeneralError('Could not send this quote.');
      return;
    }
    const btn = document.getElementById('customQuoteSubmitBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="ms">hourglass_empty</span>Sending…'; }
    try {
      const res = await fetch(quoteUrl, {
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
          focusFirstInvalidQuoteInput();
          return;
        }
        var slotsErr = firstServerValidationError(data.errors);
        if (slotsErr && data.errors && (data.errors.artist_session_slots || Object.keys(data.errors).some(function(k) { return k.indexOf('artist_session_slots') === 0; }))) {
          setPanelSlotsError('session', slotsErr);
          showQuoteFormGeneralError(slotsErr);
          var sessionPanel = slotsContainer('session');
          if (sessionPanel) scrollToSlotBlock(sessionPanel.closest('.artist-slot-block') || sessionPanel);
          return;
        }
        var generalMsg = data.message || slotsErr || 'Could not send this quote.';
        showQuoteFormGeneralError(generalMsg);
        showErrorToast(generalMsg);
        return;
      }
      if (req && req.isStudioOffer || data.reload) {
        showSuccessToast(data.message || 'Quote sent successfully');
        setTimeout(function () { location.reload(); }, 400);
        return;
      }
      applyQuoteResult(data.request);
      showSuccessToast(data.message || 'Quote sent successfully');
    } catch (err) {
      var fallback = err.message || 'Something went wrong. Please try again.';
      showQuoteFormGeneralError(fallback);
      showErrorToast(fallback);
    } finally {
      updateCustomDetailFooter(customRequestsById[id] || customRequestsById[String(id)] || null);
    }
  }

  function openCustomDeclineModal(id) {
    customDeclineRequestId = id;
    document.getElementById('customDeclineReason').value = '';
    document.getElementById('customDeclineError').classList.add('hidden');
    document.getElementById('customDeclineModal').classList.add('open');
  }

  function openCustomDeclineFromFooter() {
    if (!activeCustomRequestId) return;
    openCustomDeclineModal(activeCustomRequestId);
  }

  function closeCustomDeclineModal() {
    customDeclineRequestId = null;
    document.getElementById('customDeclineModal').classList.remove('open');
  }

  function closeCustomDeclineModalOnBackdrop(e) {
    if (e.target === e.currentTarget) closeCustomDeclineModal();
  }

    function updateCustomRequestCardFromRequest(req) {
    const kind = req.isGuest ? 'guest' : 'custom';
    const card = document.querySelector('.card[data-req][data-kind="' + kind + '"][data-id="' + req.id + '"]');
    if (!card) return;
    var statusKey = 'new';
    var statusLabel = 'New request';
    if (req.status === 'confirmed') { statusKey = 'quote_sent'; statusLabel = 'Quote sent'; }
    else if (req.status === 'moved_to_booking') { statusKey = 'booked'; statusLabel = 'Booked'; }
    else if (req.status === 'cancelled') { statusKey = 'declined'; statusLabel = 'Declined'; }
    card.dataset.statusKey = statusKey;
    card.dataset.needsReply = req.status === 'pending' ? '1' : '0';
    var canSendBack = !!(req.canSendBack && req.sendBackUrl && req.status === 'pending');
    card.dataset.canSendBack = canSendBack ? '1' : '0';
    if (canSendBack) {
      card.dataset.sendBackUrl = req.sendBackUrl || '';
      if (req.viaStudio) card.dataset.viaStudio = req.viaStudio;
    } else {
      card.dataset.sendBackUrl = '';
    }
    var pill = card.querySelector('[data-status-pill]');
    if (pill) {
      var cls = statusKey === 'new' ? 'p' : (statusKey === 'quote_sent' ? 'b' : (statusKey === 'booked' ? 'g' : 'r'));
      pill.className = 'pill ' + cls;
      pill.setAttribute('data-status-pill', '');
      pill.textContent = statusLabel;
    }
    if (req.chatUrl) card.dataset.chatUrl = req.chatUrl;
    if (typeof window.syncFeedCardAfterUpdate === 'function') window.syncFeedCardAfterUpdate(card);
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

  function applyCustomDeclineResult(req) {
    customRequestsById[req.id] = req;
    customRequestsById[String(req.id)] = req;
    updateCustomRequestCardFromRequest(req);
    const detailModal = document.getElementById('customRequestModal');
    if (detailModal && detailModal.classList.contains('open')) {
      renderCustomRequestDetail(req);
    }
    customApplyFilters();
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

  async function submitCustomDecline() {
    const reason = document.getElementById('customDeclineReason').value.trim();
    const errEl = document.getElementById('customDeclineError');
    if (!customDeclineRequestId) return;
    if (reason.length < 5) {
      errEl.textContent = 'Please provide a reason (at least 5 characters).';
      errEl.classList.remove('hidden');
      return;
    }
    errEl.classList.add('hidden');
    const btn = document.getElementById('customDeclineSubmitBtn');
    btn.disabled = true;
    try {
      const res = await fetch(getCustomDeclineUrl(customDeclineRequestId), {
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
      applyCustomDeclineResult(data.request);
      closeCustomDeclineModal();
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

  function openStudioOfferDetail(studioRequestId) {
    openCustomRequestDetail('studio:' + studioRequestId);
  }

  function closeCustomRequestDetail() {
    const modal = document.getElementById('customRequestModal');
    if (modal) modal.classList.remove('open');
    document.body.style.overflow = '';
    activeCustomRequestId = null;
    // Drop rendered markup so slot ids (sessionSlotsBlocks, etc.) never linger beside the flash modal.
    var left = document.getElementById('customRequestDetailLeft');
    var quote = document.getElementById('customRequestDetailQuote');
    if (left) left.innerHTML = '';
    if (quote) quote.innerHTML = '';
    updateCustomDetailFooter(null);
  }

  function closeCustomModalOnBackdrop(event) {
    if (event.target === event.currentTarget) {
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
    if (document.getElementById('customDeclineModal').classList.contains('open')) {
      closeCustomDeclineModal();
      return;
    }
    if (document.getElementById('customRequestModal').classList.contains('open')) {
    closeCustomRequestDetail();
    }
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

  window.CustomRequestUI = {
    addSlotDateBlock: addSlotDateBlock,
    addSlotTimeRow: addSlotTimeRow,
    closeCustomDeclineModal: closeCustomDeclineModal,
    closeCustomDeclineModalOnBackdrop: closeCustomDeclineModalOnBackdrop,
    closeCustomModalOnBackdrop: closeCustomModalOnBackdrop,
    closeCustomRequestDetail: closeCustomRequestDetail,
    onArtistSlotFieldChange: onArtistSlotFieldChange,
    openCustomDeclineFromFooter: openCustomDeclineFromFooter,
    openCustomDeclineModal: openCustomDeclineModal,
    openCustomSendBackFromFooter: openCustomSendBackFromFooter,
    openCustomRequestDetail: openCustomRequestDetail,
    openStudioOfferDetail: openStudioOfferDetail,
    removeSlotDateBlock: removeSlotDateBlock,
    removeSlotTimeRow: removeSlotTimeRow,
    submitCustomDecline: submitCustomDecline,
    submitCustomQuote: submitCustomQuote,
  };
  window.openCustomRequestDetail = openCustomRequestDetail;
  window.openStudioOfferDetail = openStudioOfferDetail;
  window.closeCustomRequestDetail = closeCustomRequestDetail;
  window.openCustomDeclineModal = openCustomDeclineModal;
  window.openCustomDeclineFromFooter = openCustomDeclineFromFooter;
  window.closeCustomDeclineModal = closeCustomDeclineModal;
  window.closeCustomDeclineModalOnBackdrop = closeCustomDeclineModalOnBackdrop;
  window.submitCustomDecline = submitCustomDecline;
  window.closeCustomModalOnBackdrop = closeCustomModalOnBackdrop;
})();
</script>
