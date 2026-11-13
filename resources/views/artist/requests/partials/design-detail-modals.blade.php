{{-- Design request detail + decline modals (Bookpay D02 flash request) --}}
@include('partials.bookpay-datetime-picker')
<style>
  /* Feed card hover (shared) */
    .request-card { transition: all 0.15s ease; }
    .request-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .filter-pill { transition: all 0.2s; }
    .filter-pill.active { background: #22c55e; color: #ffffff; }

  /* D02 overlay modal */
  #requestDetailModal.ov{
    display:none;position:fixed!important;inset:0!important;z-index:560;
    width:100vw!important;height:100vh!important;height:100dvh!important;
    margin:0!important;flex:none!important;
    background:rgba(20,10,30,.45);padding:16px;
    align-items:center;justify-content:center;
  }
  #requestDetailModal.ov.open{display:flex}
  #requestDetailModal .modal.xl{
    width:min(1040px,100%);max-width:100%;
    max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);
    background:#fff;border-radius:22px;box-shadow:0 20px 60px rgba(0,0,0,.25);
    display:flex;flex-direction:column;overflow:hidden;padding:0;
  }
  #requestDetailModal .mh{
    padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0;
  }
  #requestDetailModal .mh h2{font-size:18px;font-weight:800;margin:0;letter-spacing:-.2px}
  #requestDetailModal .mh .sub{font-size:13px;color:var(--muted);margin-top:4px}
  #requestDetailModal .mx{
    border:0;background:none;cursor:pointer;color:var(--muted);
    width:36px;height:36px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;
  }
  #requestDetailModal .mx:hover{background:#F7F3F9;color:var(--ink)}
  #requestDetailModal .mb{
    padding:18px 24px;flex:1 1 auto;min-height:0;
    overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;
  }
  #requestDetailModal .mf{
    display:flex;gap:10px;align-items:center;justify-content:flex-end;
    padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;flex-shrink:0;
  }
  #requestDetailModal .mf .btn{min-width:110px;justify-content:center}
  #requestDetailModal .mf .btn-decline{
    margin-right:auto;color:#C62828;border-color:#F3C9C9;background:#fff;
  }
  #requestDetailModal .mf .btn-decline:hover{background:#FDECEC}
  #requestDetailModal .d02-grid{
    display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;
  }
  #requestDetailModal .d02-qa{
    display:grid;grid-template-columns:1fr 1fr;column-gap:20px;margin-top:6px;
  }
  #requestDetailModal .d02-qa-item{
    padding:9px 0;border-bottom:1px solid #F3EEF4;
  }
  #requestDetailModal .d02-qa-item .lbl{font-size:10.5px}
  #requestDetailModal .d02-qa-item .val{font-size:14px;margin-top:3px;color:var(--ink);word-break:break-word}
  #requestDetailModal .d02-design-thumb{
    width:74px;height:74px;border-radius:12px;background:#EEE;
    flex:none;overflow:hidden;display:flex;align-items:center;justify-content:center;
  }
  #requestDetailModal .d02-design-thumb img{width:100%;height:100%;object-fit:cover;display:block}
  #requestDetailModal .d02-avail-card{
    display:flex;align-items:center;gap:10px;background:#fff;border-radius:12px;
    padding:10px 12px;margin-top:8px;flex-wrap:wrap;
  }
  #requestDetailModal .d02-avail-card:first-of-type{margin-top:10px}
  #requestDetailModal .d02-avail-num{
    width:26px;height:26px;border-radius:7px;background:#3E007C;color:#fff;
    font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
  }
  #requestDetailModal .d02-offer-readout .in{background:#fff;font-weight:600}
  #requestDetailModal .d02-payout-gate{
    background:#F6ECFB;border:1px solid #E6D2F2;border-radius:16px;padding:18px 20px;
  }
  #requestDetailModal .d02-locked-offer{
    border:1px solid var(--line);border-radius:16px;padding:16px 18px;margin-top:14px;
    opacity:.55;pointer-events:none;
  }
  #requestDetailModal .d02-state-card{
    background:var(--tint);border:1px solid var(--tintline);border-radius:16px;padding:18px;
  }
  #requestDetailModal .hidden{display:none!important}
  #requestDetailModal textarea.in{
    display:block;min-height:60px;resize:vertical;align-items:flex-start;
    font-size:13px;line-height:1.45;padding:10px 13px;
  }

  /* Slot pickers — Bookpay D02 / BP-DT (scoped; beats custom-detail shared slot CSS) */
  #requestDetailModal .artist-slots-panel,
  #requestDetailModal .artist-slots-panel--session,
  #requestDetailModal .artist-slots-panel--consult{
    margin-top:14px;margin-bottom:0;padding:0;border:0;border-radius:0;
    background:transparent!important;
  }
  #requestDetailModal .artist-slot-block{
    background:#fff!important;border:1px solid #EAE2EE!important;border-radius:14px;
    padding:14px;margin:10px 0 0;box-shadow:none;
  }
  #requestDetailModal .artist-slot-block:first-child{margin-top:10px}
  #requestDetailModal .artist-slot-block .bpx-btn.in{
    width:100%;background:#fff;border:1px solid var(--line);border-radius:10px;
    padding:10px 13px;min-height:40px;font-size:13.5px;font-weight:400;color:var(--ink);
  }
  #requestDetailModal .artist-slot-block .bpx-btn.in:focus-visible{
    border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF;outline:none;
  }
  #requestDetailModal .artist-slot-block .artist-slot-date.is-invalid + .bpx-btn,
  #requestDetailModal .artist-slot-block .artist-slot-time-from.is-invalid + .bpx-btn,
  #requestDetailModal .artist-slot-block .bpx-btn.border-error,
  #requestDetailModal .artist-slot-block .bpx-btn.err,
  #requestDetailModal .artist-slot-block .bpx-btn.bad{
    border-color:#C62828!important;box-shadow:none;
  }
  #requestDetailModal .artist-slot-block.is-duplicate-date,
  #requestDetailModal .artist-slot-block.is-incomplete-block{
    border-color:#C62828!important;background:#fffbfb!important;
    box-shadow:0 0 0 2px rgba(198,40,40,.12);
  }
  #requestDetailModal .artist-slot-block-error:not(.hidden){
    display:flex;align-items:flex-start;gap:.35rem;color:#C62828;font-size:12px;font-weight:600;margin-top:8px;
  }
  #requestDetailModal .artist-slot-block-error:not(.hidden)::before{
    content:'error';font-family:'Material Symbols Outlined';font-size:1rem;color:#C62828;line-height:1.2;
  }
  #requestDetailModal .artist-slot-dt-row{
    display:grid;grid-template-columns:minmax(140px,.9fr) minmax(180px,1.2fr);gap:14px;align-items:start;
  }
  #requestDetailModal .artist-slot-dt-date .fl,
  #requestDetailModal .artist-slot-dt-times .fl{margin-bottom:6px}
  #requestDetailModal .artist-slot-times{
    display:flex;flex-direction:column;gap:8px;align-items:stretch;margin-top:0;
  }
  @media (max-width:640px){
    #requestDetailModal .artist-slot-dt-row{grid-template-columns:1fr;gap:10px}
  }
  #requestDetailModal .artist-slot-time-row{
    display:flex;align-items:center;gap:6px;flex-wrap:nowrap;
    margin:0;padding:0;border-radius:0;background:transparent!important;outline:none;
  }
  #requestDetailModal .artist-slot-time-row.is-conflict .bpx-btn,
  #requestDetailModal .artist-slot-time-row.is-conflict .artist-slot-time-from{
    border-color:#C62828!important;
  }
  #requestDetailModal .artist-slot-time-row .artist-slot-time-from,
  #requestDetailModal .artist-slot-time-row .bpx-btn{flex:1;min-width:0}
  #requestDetailModal .artist-slot-time-field{flex:1;min-width:0}
  #requestDetailModal .artist-slot-time-field label{display:none}
  #requestDetailModal .artist-slot-time-end-hint{display:none}
  #requestDetailModal .artist-slot-rmx{
    flex:none;width:32px;height:32px;border:0;background:none;border-radius:8px;
    cursor:pointer;color:#8F8A95;display:flex;align-items:center;justify-content:center;padding:0;
  }
  #requestDetailModal .artist-slot-rmx:hover{background:#F7F3F9;color:#C62828}
  #requestDetailModal .artist-slot-starts-label{display:block}
  #requestDetailModal .artist-slot-starts-ends{font-weight:500;color:var(--faint)}
  #requestDetailModal .artist-slot-add-time{
    display:inline-block;margin-top:8px;border:0!important;background:none!important;padding:0!important;
    font:inherit;font-size:12.5px;font-weight:600;color:#3E007C;cursor:pointer;border-radius:0;
  }
  #requestDetailModal .artist-slot-add-time:hover{text-decoration:underline}
  #requestDetailModal .artist-slots-add-date{
    display:block;width:auto;margin-top:10px;padding:0!important;border:0!important;
    background:none!important;border-radius:0;font:inherit;font-size:12.5px;font-weight:600;
    color:#3E007C;cursor:pointer;text-align:left;
  }
  #requestDetailModal .artist-slots-add-date:hover{text-decoration:underline;background:none!important}
  #requestDetailModal .artist-offer-notes-readonly{
    font-size:13.5px;color:var(--ink);white-space:pre-line;line-height:1.5;
  }
  #requestDetailModal .artist-offer-slot-ranges{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}
  #requestDetailModal .artist-offer-slot-range{
    display:flex;align-items:center;gap:8px;font-size:13.5px;font-weight:500;
    color:var(--ink);background:rgba(255,255,255,.7);border-radius:8px;padding:8px 12px;
  }
  #requestDetailModal .artist-offer-slot-range .ms{font-size:18px;color:var(--muted)}

  /* Decline modal (Bookpay shell) */
  #designDeclineModal.ov{
    display:none;position:fixed;inset:0;z-index:420;
    background:rgba(20,10,30,.45);padding:16px;
    align-items:center;justify-content:center;
  }
  #designDeclineModal.ov.open{display:flex}
  #designDeclineModal .modal{
    width:min(480px,100%);background:#fff;border-radius:22px;
    box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;
    display:flex;flex-direction:column;max-height:calc(100vh - 32px);
  }
  #designDeclineModal .mh{
    padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;
    display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
  }
  #designDeclineModal .mh h2{font-size:18px;font-weight:800;margin:0}
  #designDeclineModal .mx{
    border:0;background:none;cursor:pointer;color:var(--muted);
    width:36px;height:36px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
  }
  #designDeclineModal .mb{padding:18px 24px;overflow-y:auto}
  #designDeclineModal .mf{
    display:flex;gap:10px;justify-content:flex-end;
    padding:16px 24px;background:#FCF6FE;border-top:1px solid #F0E4F5;
  }
  #designDeclineModal textarea.in{
    display:block;min-height:100px;resize:vertical;width:100%;
    font-size:13.5px;line-height:1.45;padding:10px 13px;
  }
  #designDeclineModal .hidden{display:none!important}

  @media (max-width:900px){
    #requestDetailModal .d02-grid{grid-template-columns:1fr;gap:18px}
    #requestDetailModal .d02-qa{grid-template-columns:1fr}
    #requestDetailModal .mf{flex-wrap:wrap}
    #requestDetailModal .mf .btn{flex:1 1 auto}
    #requestDetailModal .mf .btn-decline{margin-right:0;order:-1;width:100%}
    #requestDetailModal .modal.xl{max-height:calc(100vh - 16px);max-height:calc(100dvh - 16px);border-radius:16px}
  }
</style>

<!-- Request Detail Modal (D02) -->
<div class="ov" id="requestDetailModal" role="dialog" aria-modal="true" aria-labelledby="requestDetailTitle" onclick="closeDesignModalOnBackdrop(event)">
  <div class="modal xl" onclick="event.stopPropagation()">
    <div class="mh">
      <div>
        <h2 id="requestDetailTitle">Flash design request</h2>
        <div class="sub hidden" id="requestDetailSubtitle"></div>
      </div>
      <button type="button" class="mx" onclick="closeRequestDetail()" aria-label="Close">
        <span class="ms faint">close</span>
          </button>
        </div>
    <div class="mb">
      <div id="requestDetailPanel" class="hidden d02-grid">
        <div id="requestDetailLeft"></div>
        <div id="requestDetailActions"></div>
                </div>
      <div id="requestDetailMissing" class="hidden" style="text-align:center;padding:48px 16px;color:var(--muted)">
        <span class="ms" style="font-size:36px;display:block;margin-bottom:8px;color:var(--faint)">error</span>
            <p>Request not found.</p>
                </div>
              </div>
    <div class="mf" id="requestDetailFooter">
      <button type="button" id="designDeclineFooterBtn" class="btn ghost btn-decline hidden" onclick="openDesignDeclineFromFooter()">
        <span class="ms">block</span>Decline
      </button>
      <button type="button" class="btn ghost" onclick="closeRequestDetail()">Close</button>
      <button type="button" id="offerSlotsSubmitBtn" class="btn hidden" onclick="submitOfferedSlots()">
        <span class="ms">send</span>Send times
          </button>
                </div>
                </div>
              </div>

  <!-- Decline Request Modal -->
<div class="ov" id="designDeclineModal" role="dialog" aria-modal="true" aria-labelledby="designDeclineTitle" onclick="closeDesignDeclineModalOnBackdrop(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="mh">
                <div>
        <h2 id="designDeclineTitle">Decline request</h2>
                </div>
      <button type="button" class="mx" onclick="closeDesignDeclineModal()" aria-label="Close">
        <span class="ms faint">close</span>
          </button>
        </div>
    <div class="mb">
      <p class="muted" style="font-size:13.5px;margin-bottom:14px;line-height:1.5">Share why you are declining this request. The client may be notified with your message.</p>
      <label class="fl" for="designDeclineReason">Reason for decline</label>
      <textarea id="designDeclineReason" class="in" rows="4" maxlength="2000" placeholder="e.g. Design style does not match my portfolio, schedule fully booked for requested dates…"></textarea>
      <p id="designDeclineError" class="hidden" style="color:#C62828;font-size:13px;font-weight:600;margin-top:8px"></p>
    </div>
    <div class="mf">
      <button type="button" class="btn ghost" onclick="closeDesignDeclineModal()">Cancel</button>
      <button type="button" id="designDeclineSubmitBtn" class="btn" style="background:#C62828" onclick="submitDesignDecline()">
        <span class="ms">send</span>Submit decline
      </button>
      </div>
    </div>
  </div>
  

<script src="{{ asset('js/question-answer-display.js') }}"></script>
<script>
    const bookingRequestsById = @json(collect($requestsPayload)->keyBy('id'));
    const canOfferSlots = @json($canOfferSlots ?? true);
    const designQuotesBlockedMessage = @json($quotesBlockedMessage ?? null);
    const designPaymentSettingsUrl = @json(route('settings.payment'));
    const designDeclineRequestUrlTemplate = @json(route('artist.requests.decline', ['bookingRequest' => 0]));
    const offerSlotsUrlTemplate = @json(route('artist.requests.offer-slots', ['bookingRequest' => 0]));
    let designCurrentStatusFilter = 'all';
    let designDeclineRequestId = null;
    let activeSlotsRequestId = null;

    function escapeHtml(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function designFilterByStatus(status) { /* list filters handled on page */ }

    function designApplyFilters() {
      if (typeof window.applyRequestsFeedFilters === 'function') {
        window.applyRequestsFeedFilters();
        return;
      }
    }


    function designStatusPillClass(req) {
      if (!req) return 'p';
      if (req.status === 'confirmed') return 'b';
      if (req.status === 'moved_to_booking') return 'g';
      if (req.status === 'cancelled') return 'r';
      return 'p';
    }

    function designQaCell(label, valueHtml) {
      return '<div class="d02-qa-item"><div class="lbl">' + escapeHtml(label) + '</div><div class="val">' + valueHtml + '</div></div>';
    }

    function designQaPlain(label, value) {
      var v = (value == null || String(value).trim() === '') ? '—' : String(value);
      return designQaCell(label, escapeHtml(v));
    }

    function buildDesignQuestionsGridHtml(req) {
      var cells = [];
      cells.push(designQaPlain('Placement', req.placement));
      cells.push(designQaPlain('Scheduling', req.schedulingLabel));
      cells.push(designQaPlain('Consultation', req.consultationLabel));
      cells.push(designQaPlain('Health / Allergies', req.health));

      (req.questionsAnswers || []).forEach(function(item) {
        if (!item || !item.question) return;
        var answerHtml;
        if (window.QuestionAnswerDisplay) {
          var imageUrls = window.QuestionAnswerDisplay.imageUrlsFromAnswer(item.answer, item.type);
          if (imageUrls.length) {
            answerHtml = window.QuestionAnswerDisplay.buildImageCountHtml(imageUrls);
          } else {
            answerHtml = escapeHtml(window.QuestionAnswerDisplay.formatAnswerForReview(item.answer, item.type));
          }
        } else {
          answerHtml = escapeHtml(String(item.answer || '—'));
        }
        cells.push(designQaCell(item.question, answerHtml));
      });

      if (req.additionalNotes && String(req.additionalNotes).trim()) {
        cells.push(designQaCell('Additional notes', '<span style="white-space:pre-line">' + escapeHtml(req.additionalNotes) + '</span>'));
      }

      if (!cells.length) return '';
      return '<div class="d02-qa">' + cells.join('') + '</div>';
    }

    function updateRequestDetailFooter(req) {
      var declineBtn = document.getElementById('designDeclineFooterBtn');
      var sendBtn = document.getElementById('offerSlotsSubmitBtn');
      var subtitle = document.getElementById('requestDetailSubtitle');
      if (!req) {
        if (declineBtn) declineBtn.classList.add('hidden');
        if (sendBtn) sendBtn.classList.add('hidden');
        if (subtitle) { subtitle.textContent = ''; subtitle.classList.add('hidden'); }
        return;
      }

      var pending = !!req.isPending;
      if (declineBtn) {
        if (pending) declineBtn.classList.remove('hidden');
        else declineBtn.classList.add('hidden');
      }

      if (sendBtn) {
        if (pending && canOfferSlots) {
          sendBtn.classList.remove('hidden');
          sendBtn.disabled = false;
          sendBtn.style.opacity = '';
          sendBtn.style.pointerEvents = '';
          sendBtn.removeAttribute('title');
          sendBtn.innerHTML = '<span class="ms">send</span>Send times';
        } else if (pending && !canOfferSlots) {
          sendBtn.classList.remove('hidden');
          sendBtn.disabled = true;
          sendBtn.style.opacity = '.4';
          sendBtn.style.pointerEvents = 'none';
          sendBtn.setAttribute('title', 'Finish payouts first');
          sendBtn.innerHTML = '<span class="ms">lock</span>Send times';
        } else {
          sendBtn.classList.add('hidden');
        }
      }

      if (subtitle) {
        if (pending && !canOfferSlots) {
          subtitle.textContent = 'Payouts not set up';
          subtitle.classList.remove('hidden');
        } else {
          subtitle.textContent = '';
          subtitle.classList.add('hidden');
        }
      }
    }

    function openDesignDeclineFromFooter() {
      var id = activeSlotsRequestId;
      if (!id) return;
      openDesignDeclineModal(id);
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
        '<div class="row" style="gap:8px;color:#3E007C;font-weight:800;font-size:13px;letter-spacing:.5px">' +
        '<span class="ms">event_available</span>CLIENT AVAILABILITY</div>';

      if (!hasAny) {
        html += '<p class="faint" style="font-size:13px;margin-top:10px;font-style:italic">No availability details provided.</p></div>';
        return html;
      }

      if (preferredDates.length) {
        preferredDates.forEach(function(pref, idx) {
          var num = pref.preference || (idx + 1);
          var timesHtml = '';
          (pref.times || []).forEach(function(t) {
            timesHtml += '<span class="pill nd p">' + escapeHtml(t) + '</span>';
          });
          if (!timesHtml) timesHtml = '<span class="pill nd p">Any time</span>';
          html += '<div class="d02-avail-card">' +
            '<span class="d02-avail-num">' + escapeHtml(String(num)) + '</span>' +
            '<b style="font-size:13.5px">' + escapeHtml(pref.dateLabel || pref.date) + '</b>' +
            timesHtml +
            '</div>';
        });
      }

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
        html += '<div class="d02-qa" style="margin-top:10px">';
        metaItems.forEach(function(item) {
          html += designQaPlain(item.label, item.value);
        });
        html += '</div>';
      }

      if (avoidDates) {
        html += '<div class="d02-avail-card" style="background:#FFF7ED;border:1px solid #FED7AA;margin-top:10px">' +
          '<span class="ms" style="color:#9A3412">event_busy</span>' +
          '<span style="font-size:13px;font-weight:500;color:#9A3412">' + escapeHtml(avoidDates) + '</span></div>';
      }

      html += '</div>';
      return html;
    }

    function renderRequestDetail(req) {
      const left = document.getElementById('requestDetailLeft');
      const actions = document.getElementById('requestDetailActions');
      const designImg = req.designImage
        ? '<img src="' + escapeHtml(req.designImage) + '" alt="">'
        : '<span class="ms" style="font-size:28px;color:var(--faint)">palette</span>';

      var messageClientHtml = req.chatUrl
        ? '<a href="' + escapeHtml(req.chatUrl) + '" class="btn sm ghost" style="margin-left:auto;text-decoration:none">' +
          '<span class="ms" style="font-size:16px">chat_bubble</span>Message client</a>'
        : '';

      var contactNote = req.chatUrl && !req.isBooked
        ? '<div class="faint" style="font-size:12px;margin:6px 0 12px 66px">Full contact details unlock after payment.</div>'
        : '<div style="height:8px"></div>';

      var metaLine = [req.designStyle, req.priceLabel].filter(function(x) {
        return x && String(x).trim() && String(x).trim() !== '—';
      }).join(' · ');

      var guestSpotHtml = '';
      if (req.isGuest && req.guestSpot) {
        var gs = req.guestSpot;
        var guestBits = [];
        if (gs.city || gs.country) {
          guestBits.push([gs.city, gs.country].filter(Boolean).join(', '));
        }
        if (gs.fromDate && gs.toDate) {
          guestBits.push(gs.fromDate + ' – ' + gs.toDate);
        }
        if (gs.studio) {
          guestBits.push(gs.studio);
        }
        guestSpotHtml =
          '<div class="tint" style="padding:12px 14px;margin-bottom:12px;border-radius:14px">' +
            '<div class="row" style="gap:8px;align-items:center">' +
              '<span class="ms" style="font-size:18px;color:#3E007C">luggage</span>' +
              '<b style="font-size:14px">Guest spot request</b>' +
            '</div>' +
            (guestBits.length
              ? '<div class="muted" style="font-size:13px;margin-top:6px">' + escapeHtml(guestBits.join(' · ')) + '</div>'
              : '') +
          '</div>';
      }

      left.innerHTML =
        '<div class="row" style="gap:14px">' +
          '<div class="ini" style="width:52px;height:52px;font-size:18px">' + escapeHtml(req.clientInitials) + '</div>' +
          '<div style="flex:1;min-width:0">' +
            '<b style="font-size:18px">' + escapeHtml(req.clientName) + '</b>' +
            '<div class="row" style="gap:8px;margin-top:4px;flex-wrap:wrap">' +
              '<span class="pill ' + designStatusPillClass(req) + '">' + escapeHtml(req.filterStatus) + '</span>' +
              (req.isGuest ? '<span class="pill nd k">Guest spot</span>' : '') +
              '<span class="faint" style="font-size:12.5px">' + escapeHtml(req.submittedAt) + ' · ' + escapeHtml(req.reference) + '</span>' +
            '</div>' +
          '</div>' +
          messageClientHtml +
        '</div>' +
        contactNote +
        guestSpotHtml +
        '<div class="tint row" style="padding:12px 14px;gap:14px">' +
          '<div class="d02-design-thumb">' + designImg + '</div>' +
          '<div style="flex:1;min-width:0">' +
            '<b>' + escapeHtml(req.designTitle) + '</b>' +
            (metaLine ? '<div class="faint" style="font-size:12.5px;margin-top:2px">' + escapeHtml(metaLine) + '</div>' : '') +
            (req.schedulingLabel && req.schedulingLabel !== '—'
              ? '<div class="muted" style="font-size:13px;margin-top:6px">' + escapeHtml(req.schedulingLabel) + '</div>'
              : '') +
          '</div>' +
        '</div>' +
        buildDesignQuestionsGridHtml(req) +
        buildAvailabilityHtml(req.availabilityDetails || {});

      activeSlotsRequestId = req.id;
      if (req.isPending) {
        actions.innerHTML = buildArtistOfferSlotsHtml(req);
      } else {
        actions.innerHTML = buildNonPendingActionsHtml(req);
      }
      updateRequestDetailFooter(req);
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
      return '<span class="fl" style="margin-top:12px">Message to client <span class="faint" style="font-weight:500">(optional)</span></span>' +
        '<textarea id="artistOfferNotes" class="in" name="artist_notes_to_client" rows="3" maxlength="2000" placeholder="Optional note for the client…" oninput="onArtistOfferNotesChange()">' + notes + '</textarea>';
    }

    function buildArtistOfferNotesReadOnlyHtml(req) {
      if (!req.artistNotesToClient || !String(req.artistNotesToClient).trim()) return '';
      return '<div style="margin-top:12px"><span class="fl">Message to client</span>' +
        '<div class="in artist-offer-notes-readonly" style="min-height:auto;align-items:flex-start">' + escapeHtml(req.artistNotesToClient) + '</div></div>';
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
      var html = '<div class="tint" style="padding:14px 16px;margin-top:12px">' +
        '<div class="row" style="gap:8px;margin-bottom:10px"><span class="ms">' + icon + '</span><b style="font-size:15px">' + escapeHtml(title) + '</b></div>';
      slots.forEach(function(slot) {
        html += '<div class="artist-slot-block" style="margin-top:8px">';
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

    function artistHasSubmittedOffer(req) {
      var session = req.artistSessionSlots || [];
      var consult = req.artistConsultationSlots || [];
      var notes = req.artistNotesToClient && String(req.artistNotesToClient).trim();
      return session.length > 0 || consult.length > 0 || !!notes;
    }

    function buildArtistSubmittedOfferHtml(req) {
      var sessionSlots = req.artistSessionSlots || [];
      var consultSlots = req.artistConsultationSlots || [];
      var html = '<div class="d02-state-card">';
      html += '<div class="row" style="gap:8px"><span class="ms">event_available</span><b style="font-size:16px">Your submitted offer</b></div>';
      html += '<div class="faint" style="font-size:12.5px;margin:4px 0 12px">Times and message you sent to the client.</div>';

      if (!artistHasSubmittedOffer(req)) {
        html += '<p class="muted" style="font-size:13.5px">No offer details were saved for this request.</p>';
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
          ? '<p style="font-size:13.5px;margin-top:10px;line-height:1.5"><span class="muted" style="font-weight:700">Decline reason</span><br><span style="white-space:pre-line">' + escapeHtml(req.reasonDecline) + '</span></p>'
          : '<p class="faint" style="font-size:13px;margin-top:8px">No decline reason was provided.</p>';
        return '<div class="d02-state-card">' +
          '<div class="row" style="gap:8px"><span class="ms" style="color:#C62828">block</span><b style="font-size:16px">Declined</b></div>' +
          declinedNote + '</div>';
      }

      if (req.status === 'confirmed' || req.status === 'moved_to_booking') {
        return buildArtistSubmittedOfferHtml(req);
      }

      if (artistHasSubmittedOffer(req)) {
        return buildArtistSubmittedOfferHtml(req);
      }

      return '<div class="d02-state-card muted" style="font-size:13.5px">This request is <strong>' + escapeHtml(req.filterStatus) + '</strong>.</div>';
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
      return '';
    }

    function buildSetPayoutPanelHtml() {
      var detail = designQuotesBlockedMessage
        ? escapeHtml(designQuotesBlockedMessage)
        : "Clients pay a deposit to book your flash design, so you can't offer times until payouts are set up. Connect your bank account through Stripe first. It takes about 5 minutes, and this request stays here waiting for you.";
      return '<div class="d02-payout-gate">' +
        '<div class="row" style="gap:10px"><span class="ms" style="color:#3E007C">account_balance</span><b style="font-size:16px">Finish payouts to reply</b></div>' +
        '<div class="muted" style="font-size:13.5px;margin:6px 0 14px;line-height:1.5">' + detail + '</div>' +
        '<a href="' + escapeHtml(designPaymentSettingsUrl) + '" class="btn" style="width:100%;justify-content:center;text-decoration:none">' +
        '<span class="ms">link</span>Finish payouts</a>' +
        '<div class="faint" style="font-size:12px;margin-top:10px;text-align:center">You can still read the request and message the client.</div>' +
        '</div>' +
        '<div class="d02-locked-offer" aria-disabled="true">' +
        '<div class="row" style="gap:8px"><span class="ms">lock</span><b style="font-size:15px">Offer session times</b></div>' +
        '<div class="faint" style="font-size:12.5px;margin-top:4px">Unlocks when payouts are set up.</div>' +
        '</div>';
    }

    function buildArtistOfferSlotsHtml(req) {
      if (!canOfferSlots) {
        return buildSetPayoutPanelHtml();
      }

      var price = (req.priceLabel && req.priceLabel !== '—') ? req.priceLabel : '—';
      var duration = (req.tattooDurationLabel && String(req.tattooDurationLabel).trim()) ? req.tattooDurationLabel : '—';

      var html = '<div class="tint d02-offer-readout" style="padding:18px">' +
        '<div class="row" style="gap:8px"><span class="ms">event</span><b style="font-size:16px">Offer session times</b></div>' +
        '<div class="faint" style="font-size:12.5px;margin:2px 0 12px">The price comes from your flash design. Pick when you can do it and the client pays the deposit to book.</div>' +
        '<div class="grid" style="grid-template-columns:1fr 1fr;gap:10px">' +
          '<div><span class="fl">Price</span><div class="in">' + escapeHtml(price) + '</div></div>' +
          '<div><span class="fl">Duration</span><div class="in">' + escapeHtml(duration) + '</div></div>' +
        '</div>' +
        buildSlotsPanelHtml('session', 'Tattoo session', 'When you can do this tattoo session', 'artist_session_slots', 'artist-slots-panel--session', 'brush', req.tattooDurationLabel);

      if (requestHasConsultation(req)) {
        html += buildSlotsPanelHtml('consult', 'Consultation', 'When you can meet for the consultation (' + escapeHtml(req.consultationLabel) + ')', 'artist_consultation_slots', 'artist-slots-panel--consult', 'groups', req.consultDurationLabel);
      }

      html += buildArtistOfferNotesHtml(req);
      html += '</div>';
      return html;
    }

    function buildSlotsPanelHtml(kind, title, subtitle, fieldKey, panelClass, icon, durationLabel) {
      var header = '';
      if (kind !== 'session') {
        header = '<div class="row" style="gap:8px;margin-bottom:4px"><span class="ms">' + icon + '</span><b style="font-size:14px">' + escapeHtml(title) + '</b></div>' +
          '<p class="faint" style="font-size:12.5px;margin:0 0 8px">' + escapeHtml(subtitle) + '. Not tied to the client\'s preferred dates.</p>';
      }
      return '<div class="artist-slots-panel ' + panelClass + '" data-slots-kind="' + kind + '" data-field-key="' + fieldKey + '" style="margin-top:14px">' +
        header +
        '<div id="' + kind + 'SlotsBlocks" class="slots-blocks-container"></div>' +
        '<p id="' + kind + 'SlotsError" class="hidden" style="color:#C62828;font-size:13px;font-weight:600;margin-top:8px"></p>' +
        '<button type="button" onclick="addSlotDateBlock(\'' + kind + '\')" class="artist-slots-add-date">+ Add another date</button>' +
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

    function getDurationMinutesForSlotsKind(kind) {
      var id = activeSlotsRequestId;
      if (!id) return null;
      var req = bookingRequestsById[id] || bookingRequestsById[String(id)];
      if (!req) return null;
      if (kind === 'consult') return req.consultDurationMinutes || 30;
      return req.tattooDurationMinutes || 120;
    }

    function getDurationLabelForSlotsKind(kind) {
      var id = activeSlotsRequestId;
      if (!id) return '';
      var req = bookingRequestsById[id] || bookingRequestsById[String(id)];
      if (!req) return '';
      if (kind === 'consult') return req.consultDurationLabel || '30m';
      return req.tattooDurationLabel || '2h';
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
      var container = document.getElementById(kind + 'SlotsBlocks');
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
        '<input type="time" class="in artist-slot-time-from" value="' + escapeHtml(from) + '" step="1800" placeholder="Select time" data-ph="Select time" aria-label="Start time" onchange="onArtistSlotFieldChange(this)">' +
        '<button type="button" onclick="removeSlotTimeRow(this)" class="artist-slot-rmx" title="Remove start time" aria-label="Remove start time">' +
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
          '<b class="slot-block-label" style="font-size:12px;letter-spacing:.6px">' + (kind === 'consult' ? 'CONSULT DATE ' : 'SESSION DATE ') + (index + 1) + '</b>' +
          '<button type="button" onclick="removeSlotDateBlock(\'' + kind + '\', this)" class="btn sm ghost artist-slot-remove-date" style="padding:4px 8px;font-size:12px;visibility:hidden"><span class="ms" style="font-size:16px">delete</span>Remove</button>' +
        '</div>' +
        '<div class="artist-slot-dt-row">' +
          '<div class="artist-slot-dt-date">' +
            '<span class="fl">Date</span>' +
            '<input type="date" class="in artist-slot-date" min="' + escapeHtml(minDate) + '" value="' + escapeHtml(slot.date || '') + '" placeholder="Select date" data-ph="Select date" aria-label="Date" onchange="onArtistSlotFieldChange(this)">' +
          '</div>' +
          '<div class="artist-slot-dt-times">' +
            '<span class="fl artist-slot-starts-label">Start times <span class="artist-slot-starts-ends faint">' + escapeHtml(endsText) + '</span></span>' +
        '<div class="artist-slot-times">' + timeRows + '</div>' +
            '<button type="button" onclick="addSlotTimeRow(this)" class="artist-slot-add-time">+ Add another start time</button>' +
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
      var container = document.getElementById(kind + 'SlotsBlocks');
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
      var durationLabel = getDurationLabelForSlotsKind(kind);

      if (!rows.length) {
        return { ok: false, message: 'Add at least one start time for this date.' };
      }

      if (!durationMinutes) {
        return { ok: false, message: 'Session duration is not available for this request.' };
      }

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

        var start = timeToMinutes(from);
        var end = timeToMinutes(endTime);
        ranges.push({ start: start, end: end, row: row });
      });
      syncBlockEndsLabel(block, kind);

      if (messages.length) {
        return { ok: false, message: messages.join(' ') };
      }

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
        return {
          ok: false,
          message: 'Select a date for this entry, or remove it if you do not need it.',
          block: block,
          focusEl: focusSlotInput(dateInput)
        };
      }

      var rangeCheck = validateRangesInBlock(block, kind);
      if (!rangeCheck.ok) {
        block.classList.add('is-incomplete-block');
        var badTime = block.querySelector('.artist-slot-time-from.is-invalid') || block.querySelector('.artist-slot-time-from');
        return {
          ok: false,
          message: rangeCheck.message,
          block: block,
          focusEl: focusSlotInput(badTime)
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
        return 'Add at least one date with a start time.';
      }
      if (failureCount > 1) {
        return 'Fix ' + failureCount + ' entries — complete every date and start time, or remove extras you do not need.';
      }
      if (failureCount === 1) {
        return 'Complete the date and start time, or remove this entry.';
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
        var check = validateBlockRequired(block, kind);
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
          markSlotInputInvalid(dateInput, true);
          addFailure({
            ok: false,
            message: dupMsg,
            block: block,
            focusEl: focusSlotInput(dateInput)
          });
        });
      }

      if (!hasComplete && failures.length === 0 && blocks.length) {
        var firstBlock = blocks[0];
        var emptyMsg = 'Add at least one date with a start time.';
        if (firstBlock) {
          var dateEl = firstBlock.querySelector('.artist-slot-date');
          markSlotInputInvalid(dateEl, true);
          firstBlock.querySelectorAll('.artist-slot-time-from').forEach(function(inp) {
            markSlotInputInvalid(inp, true);
          });
          addFailure({
            ok: false,
            message: emptyMsg,
            block: firstBlock,
            focusEl: focusSlotInput(dateEl)
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
          return 'Complete tattoo session and consultation sections below (date + start times for each), or remove entries you do not need.';
        }
        return 'Complete the tattoo session section below (date + start times), or remove entries you do not need.';
      }
      if (total > 1) {
        return 'Fix ' + total + ' entries across session and consultation — complete every date and start time, or remove extras.';
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
      var kind = slotsPanelKindFromEl(input);
      if (!kind) return;
      var block = input && input.closest ? input.closest('.artist-slot-block') : null;
      if (block) syncBlockEndsLabel(block, kind);
      else syncAllTimeRowEndHints(kind);
      // Validation errors only on Send times — keep ends label in sync here.
      clearSlotValidationMarks(kind);
      setPanelSlotsError(kind, '');
    }

    function hydrateSlotBlocks(kind, slots, minDate) {
      var container = document.getElementById(kind + 'SlotsBlocks');
      if (!container) return;
      minDate = minDate || todayYmd();
      if (slots && slots.length) {
        container.innerHTML = slots.map(function(slot, i) {
          return buildSlotBlockHtml(kind, i, minDate, slot);
        }).join('');
        syncAllTimeRowEndHints(kind);
        syncSlotBlockRemoveVisibility(kind);
        return;
      }
      if (!container.children.length) {
        container.innerHTML = buildSlotBlockHtml(kind, 0, minDate);
        syncSlotBlockRemoveVisibility(kind);
      }
    }

    function hydrateOfferedSlotsFromRequest(req) {
      var minDate = todayYmd();
      hydrateSlotBlocks('session', req.artistSessionSlots || [], minDate);
      if (document.getElementById('consultSlotsBlocks')) {
        hydrateSlotBlocks('consult', req.artistConsultationSlots || [], minDate);
      }
    }

    function syncSlotBlockRemoveVisibility(kind) {
      var container = document.getElementById(kind + 'SlotsBlocks');
      if (!container) return;
      var blocks = container.querySelectorAll('.artist-slot-block');
      blocks.forEach(function(block) {
        var rm = block.querySelector('.artist-slot-remove-date');
        if (rm) rm.style.visibility = blocks.length > 1 ? '' : 'hidden';
      });
    }

    function reindexSlotBlocks(kind) {
      var container = document.getElementById(kind + 'SlotsBlocks');
      if (!container) return;
      container.querySelectorAll('.artist-slot-block').forEach(function(block, i) {
        block.dataset.slotIndex = String(i);
        var label = block.querySelector('.slot-block-label');
        if (label) label.textContent = (kind === 'consult' ? 'CONSULT DATE ' : 'SESSION DATE ') + (i + 1);
      });
      syncSlotBlockRemoveVisibility(kind);
    }

    function addSlotDateBlock(kind) {
      var container = document.getElementById(kind + 'SlotsBlocks');
      if (!container) return;
      var index = container.querySelectorAll('.artist-slot-block').length;
      container.insertAdjacentHTML('beforeend', buildSlotBlockHtml(kind, index, todayYmd()));
      reindexSlotBlocks(kind);
      onArtistSlotFieldChange(container);
    }

    function removeSlotDateBlock(kind, btn) {
      var container = document.getElementById(kind + 'SlotsBlocks');
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

    function getArtistOfferNotes() {
      var el = document.getElementById('artistOfferNotes');
      return el ? String(el.value || '').trim() : '';
    }

    function getDesignOfferSlotsUrl(id) {
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
      updateDesignRequestCardFromRequest(req);
      designApplyFilters();
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
      var btnOriginalHtml = btn ? btn.innerHTML : '';
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="ms">hourglass_empty</span>Sending…';
      }
      try {
        var payload = {
          artist_session_slots: collectSlotsFromPanel('session'),
          artist_notes_to_client: getArtistOfferNotes() || null,
        };
        if (document.getElementById('consultSlotsBlocks')) {
          payload.artist_consultation_slots = collectSlotsFromPanel('consult');
        }
        var res = await fetch(getDesignOfferSlotsUrl(id), {
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
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = btnOriginalHtml || '<span class="ms">send</span>Send times';
        }
      }
    }

    function getDesignDeclineUrl(id) {
      return designDeclineRequestUrlTemplate.replace(/\/0\/decline$/, '/' + id + '/decline');
    }

    function openDesignDeclineModal(id) {
      designDeclineRequestId = id;
      document.getElementById('designDeclineReason').value = '';
      document.getElementById('designDeclineError').classList.add('hidden');
      document.getElementById('designDeclineModal').classList.add('open');
    }

    function closeDesignDeclineModal() {
      designDeclineRequestId = null;
      document.getElementById('designDeclineModal').classList.remove('open');
    }

    function closeDesignDeclineModalOnBackdrop(e) {
      if (e.target === e.currentTarget) closeDesignDeclineModal();
    }

        function updateDesignRequestCardFromRequest(req) {
      const card = document.querySelector('.card[data-req][data-kind="design"][data-id="' + req.id + '"]');
      if (!card) return;
      var statusKey = 'new';
      var statusLabel = 'New request';
      if (req.status === 'confirmed') { statusKey = 'quote_sent'; statusLabel = 'Quote sent'; }
      else if (req.status === 'moved_to_booking') { statusKey = 'booked'; statusLabel = 'Booked'; }
      else if (req.status === 'cancelled') { statusKey = 'declined'; statusLabel = 'Declined'; }
      card.dataset.statusKey = statusKey;
      card.dataset.needsReply = req.status === 'pending' ? '1' : '0';
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

    function applyDesignDeclineResult(req) {
      bookingRequestsById[req.id] = req;
      bookingRequestsById[String(req.id)] = req;
      updateDesignRequestCardFromRequest(req);
      const detailModal = document.getElementById('requestDetailModal');
      if (detailModal.classList.contains('open')) {
        renderRequestDetail(req);
      }
      designApplyFilters();
    }

    async function submitDesignDecline() {
      const reason = document.getElementById('designDeclineReason').value.trim();
      const errEl = document.getElementById('designDeclineError');
      if (!designDeclineRequestId) return;
      if (reason.length < 5) {
        errEl.textContent = 'Please provide a reason (at least 5 characters).';
        errEl.classList.remove('hidden');
        return;
      }
      errEl.classList.add('hidden');
      const btn = document.getElementById('designDeclineSubmitBtn');
      btn.disabled = true;
      try {
        const res = await fetch(getDesignDeclineUrl(designDeclineRequestId), {
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
        applyDesignDeclineResult(data.request);
        closeDesignDeclineModal();
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
      const title = document.getElementById('requestDetailTitle');
      if (title) {
        title.textContent = (req && req.isGuest) ? 'Guest spot design request' : 'Flash design request';
      }
      if (!req) {
        panel.classList.add('hidden');
        missing.classList.remove('hidden');
        activeSlotsRequestId = null;
        updateRequestDetailFooter(null);
      } else {
        missing.classList.add('hidden');
        panel.classList.remove('hidden');
        renderRequestDetail(req);
        if (canOfferSlots && req.isPending) hydrateOfferedSlotsFromRequest(req);
      }
      const detailModal = document.getElementById('requestDetailModal');
      if (detailModal.parentElement !== document.body) {
        document.body.appendChild(detailModal);
      }
      detailModal.classList.add('open');
      document.body.style.overflow = 'hidden';
      document.body.classList.add('inbox-detail-open');
    }

    function closeRequestDetail() {
      document.getElementById('requestDetailModal').classList.remove('open');
      document.body.style.overflow = '';
      document.body.classList.remove('inbox-detail-open');
    }

    function closeDesignModalOnBackdrop(e) {
      if (e.target === e.currentTarget) closeRequestDetail();
    }

    document.addEventListener('keydown', function(e) {
      if (e.key !== 'Escape') return;
      if (document.getElementById('designDeclineModal').classList.contains('open')) {
        closeDesignDeclineModal();
        return;
      }
      closeRequestDetail();
    });

    document.addEventListener('DOMContentLoaded', function() {
      designApplyFilters();
    });

    window.openRequestDetail = openRequestDetail;
    window.inkjinRegisterAndOpenDesignRequest = function (req) {
      if (!req || req.id == null) return;
      bookingRequestsById[req.id] = req;
      bookingRequestsById[String(req.id)] = req;
      openRequestDetail(req.id);
    };
    window.closeRequestDetail = closeRequestDetail;
    window.closeDesignModalOnBackdrop = closeDesignModalOnBackdrop;
    window.openDesignDeclineModal = openDesignDeclineModal;
    window.openDesignDeclineFromFooter = openDesignDeclineFromFooter;
    window.closeDesignDeclineModal = closeDesignDeclineModal;
    window.closeDesignDeclineModalOnBackdrop = closeDesignDeclineModalOnBackdrop;
    window.submitDesignDecline = submitDesignDecline;
    window.submitOfferedSlots = submitOfferedSlots;
    window.addSlotDateBlock = addSlotDateBlock;
    window.addSlotTimeRow = addSlotTimeRow;
    window.removeSlotDateBlock = removeSlotDateBlock;
    window.removeSlotTimeRow = removeSlotTimeRow;
    window.onArtistOfferNotesChange = onArtistOfferNotesChange;
    window.onArtistSlotFieldChange = onArtistSlotFieldChange;

</script>

<!-- Toast Notification -->
<div id="saveToast" class="fixed top-6 right-6 transform translate-x-full opacity-0 transition-all duration-300" style="z-index:600">
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
