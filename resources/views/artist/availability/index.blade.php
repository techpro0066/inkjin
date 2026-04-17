@extends('layouts.artist_dashboard_layout')

@section('title', 'Availability')

@section('styles')
    <style>
      .radio-card { border: 1.5px solid #cac4d3; border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; position: relative; }
      .radio-card.selected { border-color: #310f7a; background: #fdf7ff; }
      .radio-card .radio-dot { width: 18px; height: 18px; border-radius: 50%; border: 2px solid #cac4d3; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
      .radio-card.selected .radio-dot { border-color: #310f7a; background: #310f7a; }
      .radio-card.selected .radio-dot::after { content: ''; width: 6px; height: 6px; background: white; border-radius: 50%; }

      .cal-day { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; cursor: default; transition: all 0.15s; user-select: none; }
      .cal-day.today { border: 2px solid #310f7a; font-weight: 700; }
      .cal-day.blocked { background: #ffdad6; color: #ba1a1a; text-decoration: line-through; font-weight: 600; }
      .cal-day.past { color: #cac4d3; cursor: default; }
      .cal-day.disabled { color: #cac4d3; cursor: default; }

      #monthYearPicker { box-shadow: 0 12px 40px rgba(28, 27, 33, 0.12); }

      .block-modal-backdrop { background: rgba(28, 27, 33, 0.45); }
      .block-modal-panel { animation: blockModalIn 0.2s ease-out; }
      @keyframes blockModalIn {
        from { opacity: 0; transform: scale(0.97) translateY(6px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
      }
      textarea.block-reason-field { min-height: 5.5rem; resize: vertical; line-height: 1.45; }

      .wh-time-input {
        min-width: 7rem;
        color-scheme: light;
      }

      #saveBookingStatusBtnIcon.is-loading,
      #saveWorkingHoursBtnIcon.is-loading {
        animation: hourglass-wait 1.1s ease-in-out infinite;
      }
      @keyframes hourglass-wait {
        0%, 100% { opacity: 1; transform: rotate(0deg); }
        50% { opacity: 0.65; transform: rotate(180deg); }
      }

      @media (max-width: 1023px) {
        .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
        body { overflow-x: hidden; }
      }

      input[type="time"]::-webkit-calendar-picker-indicator {
  display: none;
  -webkit-appearance: none;
}

/* Optional: remove inner spin buttons */
input[type="time"]::-webkit-inner-spin-button {
  display: none;
}

/* Firefox */
input[type="time"] {
  appearance: textfield;
  -moz-appearance: textfield;
}
    </style>
@endsection

@section('content')
<!-- Sidebar Backdrop -->
{{-- <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="document.getElementById('mobileSidebar').classList.add('hidden');document.getElementById('mobileSidebar').classList.remove('flex');document.getElementById('mobileMenuBtn').textContent='menu';this.classList.add('hidden')"></div> --}}

<!-- Main Content -->
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-4xl">

    <!-- Page Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Availability</h2>
      <p class="text-on-surface-variant mt-1">Manage your booking status and blocked dates.</p>
    </div>

    <div class="space-y-10">

      <!-- ══════════════════════════════════ -->
      <!-- Section 1: Booking Status          -->
      <!-- ══════════════════════════════════ -->
      <section>
        <h3 id="bookingStatusHeading" class="text-lg font-bold text-on-surface mb-1">Booking Status</h3>
        <div class="h-px bg-outline-variant/30 mb-5"></div>

        <form id="bookingStatusForm" action="{{ route('availability.booking-status') }}" method="post" class="space-y-4">
          @csrf
          <input type="hidden" name="availability_status" id="availability_status" value="{{ old('availability_status', $savedAvailabilityStatus ?? '') }}">

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="statusCards" role="radiogroup" aria-labelledby="bookingStatusHeading">

          <!-- Open (Available Designs + Custom) -->
          <div role="radio" tabindex="0" aria-checked="false" class="radio-card" data-status="design_custom" onclick="selectStatus(this, 'design_custom')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectStatus(this,'design_custom');}">
            <div class="flex items-start justify-between mb-2">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                  <span class="material-symbols-outlined text-primary">flash_on</span>
                </div>
                <div>
                  <span class="text-sm font-bold text-on-surface">Open (Available Designs + Custom)</span>
                  <div class="status-active flex items-center gap-1.5 mt-0.5" style="display:none;">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-xs text-green-600 font-medium">Active</span>
                  </div>
                </div>
              </div>
              <div class="radio-dot"></div>
            </div>
            <p class="text-xs text-on-surface-variant ml-[52px]">Accepting both available design bookings and custom requests</p>
          </div>

          <!-- Open (Available Designs Only) -->
          <div role="radio" tabindex="0" aria-checked="false" class="radio-card" data-status="design_only" onclick="selectStatus(this, 'design_only')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectStatus(this,'design_only');}">
            <div class="flex items-start justify-between mb-2">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                  <span class="material-symbols-outlined text-primary">palette</span>
                </div>
                <div>
                  <span class="text-sm font-bold text-on-surface">Open (Available Designs Only)</span>
                  <div class="status-active flex items-center gap-1.5 mt-0.5" style="display:none;">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-xs text-green-600 font-medium">Active</span>
                  </div>
                </div>
              </div>
              <div class="radio-dot"></div>
            </div>
            <p class="text-xs text-on-surface-variant ml-[52px]">Only accepting bookings for your available designs</p>
          </div>

          <!-- Open (Custom Only) -->
          <div role="radio" tabindex="0" aria-checked="false" class="radio-card" data-status="custom_only" onclick="selectStatus(this, 'custom_only')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectStatus(this,'custom_only');}">
            <div class="flex items-start justify-between mb-2">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                  <span class="material-symbols-outlined text-primary">edit_note</span>
                </div>
                <div>
                  <span class="text-sm font-bold text-on-surface">Open (Custom Only)</span>
                  <div class="status-active flex items-center gap-1.5 mt-0.5" style="display:none;">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-xs text-green-600 font-medium">Active</span>
                  </div>
                </div>
              </div>
              <div class="radio-dot"></div>
            </div>
            <p class="text-xs text-on-surface-variant ml-[52px]">Only accepting custom tattoo requests</p>
          </div>

          <!-- Books Closed -->
          <div role="radio" tabindex="0" aria-checked="false" class="radio-card" data-status="closed" onclick="selectStatus(this, 'closed')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectStatus(this,'closed');}">
            <div class="flex items-start justify-between mb-2">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                  <span class="material-symbols-outlined text-primary">block</span>
                </div>
                <div>
                  <span class="text-sm font-bold text-on-surface">Books Closed</span>
                  <div class="status-active flex items-center gap-1.5 mt-0.5" style="display:none;">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-xs text-green-600 font-medium">Active</span>
                  </div>
                </div>
              </div>
              <div class="radio-dot"></div>
            </div>
            <p class="text-xs text-on-surface-variant ml-[52px]">Not accepting any new bookings</p>
          </div>

          </div>

          <p id="bookingStatusError" class="hidden text-sm font-medium text-error" role="alert"></p>

          <div class="mt-6 flex justify-end">
            <button type="submit" id="saveBookingStatusBtn" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-60 disabled:pointer-events-none">
              <span id="saveBookingStatusBtnIcon" class="material-symbols-outlined text-lg transition-opacity" aria-hidden="true">save</span>
              <span id="saveBookingStatusBtnLabel">Save Status</span>
            </button>
          </div>
        </form>
      </section>

      <!-- ══════════════════════════════════ -->
      <!-- Section 2: Blocked Dates           -->
      <!-- ══════════════════════════════════ -->
      <section>
        <h3 class="text-lg font-bold text-on-surface mb-1">Blocked Dates</h3>
        <p class="text-on-surface-variant text-sm mb-1">Block off dates when you're not available</p>
        <p class="text-on-surface-variant text-xs mb-3 max-w-xl">To block a day or range, use <strong class="text-on-surface font-semibold">Block a date</strong> or <strong class="text-on-surface font-semibold">Block a range</strong> below. To unblock, remove the entry from <strong class="text-on-surface font-semibold">Currently Blocked</strong>. The calendar is for reference only.</p>
        <div class="h-px bg-outline-variant/30 mb-5"></div>

        <div class="bg-surface-container-low rounded-2xl p-6 mb-6">
          <!-- Mini Calendar (read-only; blocks managed via modals + list) -->
          <div class="max-w-sm mx-auto">
            <div class="flex items-center justify-between mb-5 gap-2">
              <button type="button" onclick="changeMonth(-1)" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors shrink-0" aria-label="Previous month"><span class="material-symbols-outlined text-on-surface-variant">chevron_left</span></button>
              <div class="flex-1 flex justify-center min-w-0 relative">
                <button type="button" id="calMonthBtn" onclick="toggleMonthYearPicker(event)" class="font-bold text-base text-on-surface max-w-full truncate inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg hover:bg-surface-container transition-colors text-center" aria-expanded="false" aria-haspopup="dialog" aria-controls="monthYearPicker">
                  <span id="calMonth"></span>
                  <span class="material-symbols-outlined text-on-surface-variant text-[18px] shrink-0" aria-hidden="true">expand_more</span>
                </button>
                <div id="monthYearPicker" class="hidden absolute top-full left-1/2 -translate-x-1/2 mt-2 z-30 w-[min(calc(100vw-2rem),17rem)] rounded-xl border border-outline-variant/30 bg-white p-4" role="dialog" aria-modal="true" aria-labelledby="monthYearPickerTitle" onclick="event.stopPropagation()">
                  <p id="monthYearPickerTitle" class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-3">Go to month</p>
                  <div class="flex gap-2 mb-3">
                    <label class="sr-only" for="calMonthSelect">Month</label>
                    <select id="calMonthSelect" class="flex-1 min-w-0 text-sm border border-outline-variant/40 rounded-lg px-2 py-2 bg-surface-container-lowest text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"></select>
                    <label class="sr-only" for="calYearSelect">Year</label>
                    <select id="calYearSelect" class="w-[5.25rem] shrink-0 text-sm border border-outline-variant/40 rounded-lg px-2 py-2 bg-surface-container-lowest text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"></select>
                  </div>
                  <button type="button" onclick="applyMonthYear()" class="w-full py-2.5 rounded-lg bg-primary text-on-primary text-sm font-bold hover:opacity-90 transition-opacity">Go</button>
                </div>
              </div>
              <button type="button" onclick="changeMonth(1)" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors shrink-0" aria-label="Next month"><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center mb-2">
              <div class="text-xs font-semibold text-on-surface-variant py-1">Mon</div>
              <div class="text-xs font-semibold text-on-surface-variant py-1">Tue</div>
              <div class="text-xs font-semibold text-on-surface-variant py-1">Wed</div>
              <div class="text-xs font-semibold text-on-surface-variant py-1">Thu</div>
              <div class="text-xs font-semibold text-on-surface-variant py-1">Fri</div>
              <div class="text-xs font-semibold text-on-surface-variant py-1">Sat</div>
              <div class="text-xs font-semibold text-on-surface-variant py-1">Sun</div>
            </div>
            <div class="grid grid-cols-7 gap-1 justify-items-center" id="calGrid" aria-label="Calendar preview. Add blocks with Block a date or Block a range; remove them from Currently Blocked."></div>
          </div>

          <!-- Block date / range actions -->
          <div class="mt-6 pt-4 border-t border-outline-variant/20 flex flex-col sm:flex-row flex-wrap gap-3">
            <button type="button" onclick="openBlockDateModal()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-primary/30 bg-white text-primary text-sm font-bold hover:bg-primary/5 transition-colors">
              <span class="material-symbols-outlined text-[20px]">event_busy</span> Block a date
            </button>
            <button type="button" onclick="openBlockRangeModal()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-primary/30 bg-white text-primary text-sm font-bold hover:bg-primary/5 transition-colors">
              <span class="material-symbols-outlined text-[20px]">date_range</span> Block a range
            </button>
          </div>
        </div>

        <!-- Blocked Dates List -->
        <div class="bg-white rounded-2xl border border-outline-variant/20 p-5">
          <h4 class="text-sm font-bold text-on-surface mb-4">Currently Blocked</h4>
          <p id="blockedDatesError" class="hidden mb-3 text-sm font-medium text-error" role="alert"></p>
          <div class="space-y-3" id="blockedList"></div>
          <div id="blockedListPagination" class="hidden flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 mt-4 border-t border-outline-variant/15"></div>
        </div>
      </section>

      <!-- ══════════════════════════════════ -->
      <!-- Section 3: Working Hours           -->
      <!-- ══════════════════════════════════ -->
      <section>
        <h3 class="text-lg font-bold text-on-surface mb-1">Working Hours</h3>
        <p class="text-on-surface-variant text-sm mb-1">Set your regular weekly availability</p>
        <div class="h-px bg-outline-variant/30 mb-5"></div>

        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6">
          <div id="workingHoursContainer"></div>
          <p class="text-xs text-on-surface-variant mt-5 pt-4 border-t border-outline-variant/10">These are your regular weekly hours. Use Blocked Dates above to mark specific days off.</p>
        </div>
        <p id="workingHoursError" class="hidden mt-4 text-sm font-medium text-error" role="alert"></p>
        <div class="mt-6 flex justify-end">
          <button type="button" id="saveWorkingHoursBtn" onclick="saveWorkingHours()" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98] disabled:opacity-60 disabled:pointer-events-none">
            <span id="saveWorkingHoursBtnIcon" class="material-symbols-outlined text-lg transition-opacity" aria-hidden="true">save</span>
            <span id="saveWorkingHoursBtnLabel">Save working hours</span>
          </button>
        </div>
      </section>

    </div>
  </div>
</main>

<!-- Block a date modal -->
<div id="blockDateModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 block-modal-backdrop" onclick="closeBlockDateModal()">
  <div class="block-modal-panel w-full max-w-md rounded-2xl bg-surface-container-lowest border border-outline-variant/25 shadow-2xl shadow-primary/10 overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="blockDateModalTitle" onclick="event.stopPropagation()">
    <div class="px-5 py-4 border-b border-outline-variant/20 flex items-center justify-between gap-3 bg-white/80">
      <h3 id="blockDateModalTitle" class="text-lg font-bold text-on-surface">Block a date</h3>
      <button type="button" onclick="closeBlockDateModal()" class="p-2 rounded-lg hover:bg-surface-container transition-colors text-on-surface-variant" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4 bg-surface-container-lowest">
      <div>
        <label for="blockDateInput" class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Date</label>
        <input type="date" id="blockDateInput" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
      </div>
      <div>
        <label for="blockDateReason" class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Reason <span class="font-normal text-on-surface-variant/70">(optional)</span></label>
        <textarea id="blockDateReason" rows="3" placeholder="e.g., Personal day, Appointment…" class="block-reason-field w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface placeholder:text-on-surface-variant/50 focus:outline-none focus:ring-2 focus:ring-primary/30"></textarea>
      </div>
      <p id="blockDateModalError" class="hidden text-sm font-medium text-error" role="alert"></p>
      <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end pt-1">
        <button type="button" onclick="closeBlockDateModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-outline-variant/40 text-on-surface font-semibold text-sm hover:bg-white transition-colors">Cancel</button>
        <button type="button" onclick="submitBlockDate()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-on-primary font-bold text-sm hover:opacity-90 transition-opacity">
          <span class="material-symbols-outlined text-[18px]">event_busy</span>
          <span id="blockDateSubmitLabel">Block date</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Block a range modal -->
<div id="blockRangeModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 block-modal-backdrop" onclick="closeBlockRangeModal()">
  <div class="block-modal-panel w-full max-w-md rounded-2xl bg-surface-container-lowest border border-outline-variant/25 shadow-2xl shadow-primary/10 overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="blockRangeModalTitle" onclick="event.stopPropagation()">
    <div class="px-5 py-4 border-b border-outline-variant/20 flex items-center justify-between gap-3 bg-white/80">
      <h3 id="blockRangeModalTitle" class="text-lg font-bold text-on-surface">Block a range</h3>
      <button type="button" onclick="closeBlockRangeModal()" class="p-2 rounded-lg hover:bg-surface-container transition-colors text-on-surface-variant" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4 bg-surface-container-lowest">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="blockRangeFrom" class="text-xs font-semibold text-on-surface-variant mb-1.5 block">From</label>
          <input type="date" id="blockRangeFrom" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
          <label for="blockRangeTo" class="text-xs font-semibold text-on-surface-variant mb-1.5 block">To</label>
          <input type="date" id="blockRangeTo" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-2.5 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
      </div>
      <div>
        <label for="blockRangeReason" class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Reason <span class="font-normal text-on-surface-variant/70">(optional)</span></label>
        <textarea id="blockRangeReason" rows="3" placeholder="e.g., Vacation, convention, travel…" class="block-reason-field w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface placeholder:text-on-surface-variant/50 focus:outline-none focus:ring-2 focus:ring-primary/30"></textarea>
      </div>
      <p id="blockRangeModalError" class="hidden text-sm font-medium text-error" role="alert"></p>
      <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end pt-1">
        <button type="button" onclick="closeBlockRangeModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-outline-variant/40 text-on-surface font-semibold text-sm hover:bg-white transition-colors">Cancel</button>
        <button type="button" onclick="submitBlockRange()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-on-primary font-bold text-sm hover:opacity-90 transition-opacity">
          <span class="material-symbols-outlined text-[18px]">date_range</span>
          <span id="blockRangeSubmitLabel">Block range</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Confirm remove blocked entry -->
<div id="removeBlockedConfirmModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 block-modal-backdrop" onclick="closeRemoveBlockedConfirm()" role="presentation">
  <div class="block-modal-panel w-full max-w-md rounded-2xl bg-surface-container-lowest border border-outline-variant/25 shadow-2xl shadow-primary/10 overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="removeBlockedConfirmTitle" onclick="event.stopPropagation()">
    <div class="px-5 py-4 border-b border-outline-variant/20 bg-white/80">
      <div class="flex items-start gap-3">
        <div class="w-11 h-11 shrink-0 rounded-full bg-error-container/60 flex items-center justify-center">
          <span class="material-symbols-outlined text-error text-[26px]" aria-hidden="true">event_busy</span>
        </div>
        <div class="min-w-0 pt-0.5">
          <h3 id="removeBlockedConfirmTitle" class="text-lg font-bold text-on-surface leading-snug">Remove blocked dates?</h3>
          <p class="text-sm text-on-surface-variant mt-1">This period will be available for booking again. You can block it later if needed.</p>
        </div>
      </div>
    </div>
    <div class="p-5 space-y-4 bg-surface-container-lowest">
      <div>
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-2">You are about to remove</p>
        <div id="removeBlockedConfirmDetail" class="rounded-xl border border-outline-variant/30 bg-white px-4 py-3 text-sm font-semibold text-on-surface"></div>
        <p id="removeBlockedConfirmReasonLine" class="hidden mt-2 text-xs text-on-surface-variant"></p>
      </div>
      <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end pt-1">
        <button type="button" onclick="closeRemoveBlockedConfirm()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-outline-variant/40 text-on-surface font-semibold text-sm hover:bg-white transition-colors">Cancel</button>
        <button type="button" onclick="confirmRemoveBlocked()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-error text-on-error font-bold text-sm hover:opacity-90 transition-opacity">
          <span class="material-symbols-outlined text-[18px]">delete</span>
          Remove block
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // ── Booking Status (saved value from DB; "Active" badge only after a successful save) ──
  var savedBookingStatus = @json($savedAvailabilityStatus ?? null);

  function updateBookingStatusActiveBadges() {
    document.querySelectorAll('#statusCards .radio-card').forEach(function(card) {
      var badge = card.querySelector('.status-active');
      var st = card.getAttribute('data-status');
      var show = savedBookingStatus && st === savedBookingStatus;
      if (badge) badge.style.display = show ? 'flex' : 'none';
    });
  }

  function syncBookingStatusAriaChecked() {
    document.querySelectorAll('#statusCards .radio-card').forEach(function(card) {
      card.setAttribute('aria-checked', card.classList.contains('selected') ? 'true' : 'false');
    });
  }

  function selectStatus(el, dbValue) {
    document.querySelectorAll('#statusCards .radio-card').forEach(function(c) {
      c.classList.remove('selected');
    });
    el.classList.add('selected');
    var hidden = document.getElementById('availability_status');
    if (hidden) hidden.value = dbValue;
    syncBookingStatusAriaChecked();
    updateBookingStatusActiveBadges();
    var err = document.getElementById('bookingStatusError');
    if (err) { err.classList.add('hidden'); err.textContent = ''; }
  }

  function applySavedBookingStatusSelection() {
    var hidden = document.getElementById('availability_status');
    var val = hidden ? hidden.value : '';
    document.querySelectorAll('#statusCards .radio-card').forEach(function(card) {
      card.classList.remove('selected');
    });
    if (!val) {
      syncBookingStatusAriaChecked();
      updateBookingStatusActiveBadges();
      return;
    }
    document.querySelectorAll('#statusCards .radio-card').forEach(function(card) {
      if (card.getAttribute('data-status') === val) {
        card.classList.add('selected');
      }
    });
    syncBookingStatusAriaChecked();
    updateBookingStatusActiveBadges();
  }

  (function initBookingStatusForm() {
    applySavedBookingStatusSelection();

    var form = document.getElementById('bookingStatusForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var errEl = document.getElementById('bookingStatusError');
      var btn = document.getElementById('saveBookingStatusBtn');
      var hidden = document.getElementById('availability_status');
      var val = hidden ? String(hidden.value || '').trim() : '';

      if (errEl) {
        errEl.classList.add('hidden');
        errEl.textContent = '';
      }

      if (!val) {
        if (errEl) {
          errEl.textContent = 'Please select a booking status before saving.';
          errEl.classList.remove('hidden');
        }
        return;
      }

      var fd = new FormData(form);
      var btnIcon = document.getElementById('saveBookingStatusBtnIcon');
      var btnLabel = document.getElementById('saveBookingStatusBtnLabel');
      if (btn) {
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        if (btnIcon) {
          btnIcon.textContent = 'hourglass_empty';
          btnIcon.classList.add('is-loading');
        }
        if (btnLabel) btnLabel.textContent = 'Saving...';
      }

      fetch(form.action, {
        method: 'POST',
        body: fd,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      })
        .then(function(res) {
          return res.json().then(function(data) {
            return { ok: res.ok, status: res.status, data: data };
          });
        })
        .then(function(result) {
          if (result.ok && result.data && result.data.success) {
            savedBookingStatus = result.data.availability_status || val;
            applySavedBookingStatusSelection();
            if (typeof showSaveToast === 'function') showSaveToast();
            return;
          }
          var msg = (result.data && result.data.message) ? result.data.message : 'Could not save booking status.';
          if (result.data && result.data.errors && result.data.errors.availability_status) {
            msg = result.data.errors.availability_status[0];
          }
          if (errEl) {
            errEl.textContent = msg;
            errEl.classList.remove('hidden');
          }
        })
        .catch(function() {
          if (errEl) {
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.remove('hidden');
          }
        })
        .finally(function() {
          if (btn) {
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
          }
          var iconEl = document.getElementById('saveBookingStatusBtnIcon');
          var labelEl = document.getElementById('saveBookingStatusBtnLabel');
          if (iconEl) {
            iconEl.textContent = 'save';
            iconEl.classList.remove('is-loading');
          }
          if (labelEl) labelEl.textContent = 'Save Status';
        });
    });
  })();

  // ── Blocked Dates (availability_overrides: start_date, end_date, reason) ──
  var blockedPeriods = @json($blockedPeriods ?? []);

  var BLOCK_CSRF = '{{ csrf_token() }}';
  var BLOCK_STORE_URL = '{{ route('availability.override.store') }}';
  function blockDestroyUrl(id) {
    return '{{ url('/availability/override') }}/' + encodeURIComponent(id);
  }

  var editingBlockId = null;

  function clearBlockDateModalError() {
    var el = document.getElementById('blockDateModalError');
    if (el) {
      el.textContent = '';
      el.classList.add('hidden');
    }
  }

  function clearBlockRangeModalError() {
    var el = document.getElementById('blockRangeModalError');
    if (el) {
      el.textContent = '';
      el.classList.add('hidden');
    }
  }

  function clearBlockedDatesSectionError() {
    var el = document.getElementById('blockedDatesError');
    if (el) {
      el.textContent = '';
      el.classList.add('hidden');
    }
  }

  function showBlockDateModalError(msg) {
    clearBlockDateModalError();
    var el = document.getElementById('blockDateModalError');
    if (el) {
      el.textContent = msg;
      el.classList.remove('hidden');
    }
  }

  function showBlockRangeModalError(msg) {
    clearBlockRangeModalError();
    var el = document.getElementById('blockRangeModalError');
    if (el) {
      el.textContent = msg;
      el.classList.remove('hidden');
    }
  }

  function showBlockedDatesSectionError(msg) {
    clearBlockedDatesSectionError();
    var el = document.getElementById('blockedDatesError');
    if (el) {
      el.textContent = msg;
      el.classList.remove('hidden');
      el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  /** API/network errors: show in the open block modal if any, else under Currently Blocked */
  function showBlockedDatesApiError(msg) {
    var dateM = document.getElementById('blockDateModal');
    var rangeM = document.getElementById('blockRangeModal');
    if (dateM && !dateM.classList.contains('hidden')) {
      showBlockDateModalError(msg);
      return;
    }
    if (rangeM && !rangeM.classList.contains('hidden')) {
      showBlockRangeModalError(msg);
      return;
    }
    showBlockedDatesSectionError(msg);
  }

  const CAL_MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  function toInputDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  }

  function fromInputDate(str) {
    const p = str.split('-');
    return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
  }

  function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  function formatBlockedRangeLabel(start, end) {
    var sm = CAL_MONTHS[start.getMonth()];
    var sy = start.getFullYear();
    var sd = start.getDate();
    if (toInputDate(start) === toInputDate(end)) {
      return sm + ' ' + sd + ', ' + sy;
    }
    var em = CAL_MONTHS[end.getMonth()];
    var ey = end.getFullYear();
    var ed = end.getDate();
    if (start.getMonth() === end.getMonth() && sy === ey) {
      return sm + ' ' + sd + ' – ' + ed + ', ' + sy;
    }
    return sm + ' ' + sd + ', ' + sy + ' – ' + em + ' ' + ed + ', ' + ey;
  }

  const BLOCKED_PAGE_SIZE = 5;
  let blockedListPage = 1;
  var pendingRemoveBlockedIndex = null;

  function isSingleDayPeriod(p) {
    return p.start_date === p.end_date;
  }

  function clampBlockedListPage() {
    const totalPages = Math.max(1, Math.ceil(blockedPeriods.length / BLOCKED_PAGE_SIZE));
    if (blockedListPage > totalPages) blockedListPage = totalPages;
    if (blockedListPage < 1) blockedListPage = 1;
  }

  function resetDateModalUi() {
    document.getElementById('blockDateModalTitle').textContent = 'Block a date';
    document.getElementById('blockDateSubmitLabel').textContent = 'Block date';
  }

  function resetRangeModalUi() {
    document.getElementById('blockRangeModalTitle').textContent = 'Block a range';
    document.getElementById('blockRangeSubmitLabel').textContent = 'Block range';
  }

  function clearBlockDateInputMins() {
    ['blockDateInput', 'blockRangeFrom', 'blockRangeTo'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.removeAttribute('min');
    });
  }

  function renderBlockedList() {
    clampBlockedListPage();
    const container = document.getElementById('blockedList');
    if (!container) return;
    container.innerHTML = '';
    const n = blockedPeriods.length;
    const startIdx = (blockedListPage - 1) * BLOCKED_PAGE_SIZE;
    const endIdx = Math.min(startIdx + BLOCKED_PAGE_SIZE, n);
    for (var index = startIdx; index < endIdx; index++) {
      var b = blockedPeriods[index];
      var row = document.createElement('div');
      row.className = 'flex items-center justify-between gap-2 p-3 bg-surface-container-low rounded-xl';
      row.setAttribute('data-block', String(index));
      var label = formatBlockedRangeLabel(fromInputDate(b.start_date), fromInputDate(b.end_date));
      var reasonText = (b.reason && String(b.reason).trim()) ? escapeHtml(String(b.reason).trim()) : '';
      row.innerHTML =
        '<div class="flex items-center gap-3 min-w-0 flex-1">' +
          '<div class="w-9 h-9 shrink-0 rounded-lg bg-red-50 flex items-center justify-center">' +
            '<span class="material-symbols-outlined text-red-500 text-lg">event_busy</span>' +
          '</div>' +
          '<div class="min-w-0">' +
            '<p class="text-sm font-semibold text-on-surface">' + escapeHtml(label) + '</p>' +
            (reasonText ? '<p class="text-xs text-on-surface-variant mt-0.5">' + reasonText + '</p>' : '') +
          '</div>' +
        '</div>' +
        '<div class="flex items-center gap-0.5 shrink-0">' +
          '<button type="button" onclick="editBlocked(' + index + ')" class="p-2 rounded-lg hover:bg-primary/10 transition-colors text-primary" title="Edit" aria-label="Edit">' +
            '<span class="material-symbols-outlined text-lg">edit</span>' +
          '</button>' +
          '<button type="button" onclick="openRemoveBlockedConfirm(' + index + ')" class="p-2 rounded-lg hover:bg-error-container transition-colors" title="Remove" aria-label="Remove">' +
            '<span class="material-symbols-outlined text-error text-lg">delete</span>' +
          '</button>' +
        '</div>';
      container.appendChild(row);
    }
    renderBlockedPagination();
  }

  function renderBlockedPagination() {
    var wrap = document.getElementById('blockedListPagination');
    if (!wrap) return;
    var n = blockedPeriods.length;
    var totalPages = Math.max(1, Math.ceil(n / BLOCKED_PAGE_SIZE));
    if (n <= BLOCKED_PAGE_SIZE) {
      wrap.classList.add('hidden');
      wrap.innerHTML = '';
      return;
    }
    wrap.classList.remove('hidden');
    var start = (blockedListPage - 1) * BLOCKED_PAGE_SIZE + 1;
    var end = Math.min(blockedListPage * BLOCKED_PAGE_SIZE, n);
    var prevDisabled = blockedListPage <= 1;
    var nextDisabled = blockedListPage >= totalPages;
    wrap.innerHTML =
      '<p class="text-xs text-on-surface-variant">Showing ' + start + '–' + end + ' of ' + n + '</p>' +
      '<div class="flex items-center justify-center gap-2">' +
        '<button type="button" onclick="goBlockedPage(-1)" ' + (prevDisabled ? 'disabled' : '') + ' class="p-2 rounded-lg border border-outline-variant/30 bg-white text-on-surface hover:bg-surface-container transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white" aria-label="Previous page">' +
          '<span class="material-symbols-outlined text-on-surface-variant text-[20px]">chevron_left</span>' +
        '</button>' +
        '<span class="text-sm font-medium text-on-surface px-2 min-w-[7rem] text-center">Page ' + blockedListPage + ' of ' + totalPages + '</span>' +
        '<button type="button" onclick="goBlockedPage(1)" ' + (nextDisabled ? 'disabled' : '') + ' class="p-2 rounded-lg border border-outline-variant/30 bg-white text-on-surface hover:bg-surface-container transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white" aria-label="Next page">' +
          '<span class="material-symbols-outlined text-on-surface-variant text-[20px]">chevron_right</span>' +
        '</button>' +
      '</div>';
  }

  function goBlockedPage(delta) {
    blockedListPage += delta;
    clampBlockedListPage();
    renderBlockedList();
  }

  function setDateInputMinToday() {
    var t = toInputDate(new Date());
    var dateIn = document.getElementById('blockDateInput');
    var fromIn = document.getElementById('blockRangeFrom');
    var toIn = document.getElementById('blockRangeTo');
    if (dateIn) dateIn.min = t;
    if (fromIn) fromIn.min = t;
    if (toIn) toIn.min = t;
  }

  function editBlocked(index) {
    var b = blockedPeriods[index];
    if (!b) return;
    editingBlockId = b.id;
    clearBlockDateModalError();
    clearBlockRangeModalError();
    clearBlockedDatesSectionError();
    closeMonthYearPicker();
    clearBlockDateInputMins();
    if (isSingleDayPeriod(b)) {
      resetDateModalUi();
      document.getElementById('blockDateModalTitle').textContent = 'Edit blocked date';
      document.getElementById('blockDateSubmitLabel').textContent = 'Save changes';
      document.getElementById('blockDateInput').value = b.start_date;
      document.getElementById('blockDateReason').value = b.reason ? String(b.reason) : '';
      document.getElementById('blockDateModal').classList.remove('hidden');
      syncBodyScrollForBlockModals();
      setTimeout(function() { document.getElementById('blockDateInput').focus(); }, 50);
    } else {
      resetRangeModalUi();
      document.getElementById('blockRangeModalTitle').textContent = 'Edit blocked range';
      document.getElementById('blockRangeSubmitLabel').textContent = 'Save changes';
      document.getElementById('blockRangeFrom').value = b.start_date;
      document.getElementById('blockRangeTo').value = b.end_date;
      document.getElementById('blockRangeReason').value = b.reason ? String(b.reason) : '';
      document.getElementById('blockRangeModal').classList.remove('hidden');
      syncBodyScrollForBlockModals();
      setTimeout(function() { document.getElementById('blockRangeFrom').focus(); }, 50);
    }
  }

  function openBlockDateModal() {
    editingBlockId = null;
    clearBlockDateModalError();
    clearBlockedDatesSectionError();
    resetDateModalUi();
    closeMonthYearPicker();
    document.getElementById('blockDateModal').classList.remove('hidden');
    syncBodyScrollForBlockModals();
    setDateInputMinToday();
    document.getElementById('blockDateInput').value = '';
    document.getElementById('blockDateReason').value = '';
    setTimeout(function() { document.getElementById('blockDateInput').focus(); }, 50);
  }

  function syncBodyScrollForBlockModals() {
    var dateOpen = !document.getElementById('blockDateModal').classList.contains('hidden');
    var rangeOpen = !document.getElementById('blockRangeModal').classList.contains('hidden');
    var removeOpen = !document.getElementById('removeBlockedConfirmModal').classList.contains('hidden');
    document.body.style.overflow = (dateOpen || rangeOpen || removeOpen) ? 'hidden' : '';
  }

  function closeBlockDateModal() {
    document.getElementById('blockDateModal').classList.add('hidden');
    editingBlockId = null;
    clearBlockDateModalError();
    resetDateModalUi();
    syncBodyScrollForBlockModals();
  }

  function openBlockRangeModal() {
    editingBlockId = null;
    clearBlockRangeModalError();
    clearBlockedDatesSectionError();
    resetRangeModalUi();
    closeMonthYearPicker();
    document.getElementById('blockRangeModal').classList.remove('hidden');
    syncBodyScrollForBlockModals();
    setDateInputMinToday();
    document.getElementById('blockRangeFrom').value = '';
    document.getElementById('blockRangeTo').value = '';
    document.getElementById('blockRangeReason').value = '';
    setTimeout(function() { document.getElementById('blockRangeFrom').focus(); }, 50);
  }

  function closeBlockRangeModal() {
    document.getElementById('blockRangeModal').classList.add('hidden');
    editingBlockId = null;
    clearBlockRangeModalError();
    resetRangeModalUi();
    syncBodyScrollForBlockModals();
  }

  function closeAllBlockModals() {
    document.getElementById('blockDateModal').classList.add('hidden');
    document.getElementById('blockRangeModal').classList.add('hidden');
    document.getElementById('removeBlockedConfirmModal').classList.add('hidden');
    editingBlockId = null;
    pendingRemoveBlockedIndex = null;
    resetDateModalUi();
    resetRangeModalUi();
    syncBodyScrollForBlockModals();
  }

  function openRemoveBlockedConfirm(index) {
    var b = blockedPeriods[index];
    if (!b) return;
    pendingRemoveBlockedIndex = index;
    clearBlockedDatesSectionError();
    closeMonthYearPicker();
    document.getElementById('removeBlockedConfirmDetail').textContent = formatBlockedRangeLabel(fromInputDate(b.start_date), fromInputDate(b.end_date));
    var reason = b.reason && String(b.reason).trim();
    var reasonEl = document.getElementById('removeBlockedConfirmReasonLine');
    if (reason) {
      reasonEl.textContent = 'Reason: ' + reason;
      reasonEl.classList.remove('hidden');
    } else {
      reasonEl.textContent = '';
      reasonEl.classList.add('hidden');
    }
    document.getElementById('removeBlockedConfirmModal').classList.remove('hidden');
    syncBodyScrollForBlockModals();
  }

  function closeRemoveBlockedConfirm() {
    document.getElementById('removeBlockedConfirmModal').classList.add('hidden');
    pendingRemoveBlockedIndex = null;
    syncBodyScrollForBlockModals();
  }

  function mergeSavedBlock(block) {
    if (!block || !block.id) return;
    var idx = blockedPeriods.findIndex(function(p) { return p.id === block.id; });
    var row = { id: block.id, start_date: block.start_date, end_date: block.end_date, reason: block.reason || '' };
    if (idx >= 0) {
      blockedPeriods[idx] = row;
    } else {
      blockedPeriods.push(row);
    }
    blockedPeriods.sort(function(a, b) {
      if (a.start_date !== b.start_date) return a.start_date < b.start_date ? 1 : -1;
      return b.id - a.id;
    });
    blockedListPage = Math.min(blockedListPage, Math.max(1, Math.ceil(blockedPeriods.length / BLOCKED_PAGE_SIZE)));
    clampBlockedListPage();
  }

  function saveBlockPeriod(startDateStr, endDateStr, reason) {
    var fd = new FormData();
    fd.append('_token', BLOCK_CSRF);
    fd.append('start_date', startDateStr);
    fd.append('end_date', endDateStr);
    fd.append('reason', reason || '');
    if (editingBlockId) {
      fd.append('block_id', String(editingBlockId));
    }

    return fetch(BLOCK_STORE_URL, {
      method: 'POST',
      body: fd,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
      .then(function(res) {
        return res.json().then(function(data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function(result) {
        if (result.ok && result.data && result.data.success && result.data.block) {
          clearBlockDateModalError();
          clearBlockRangeModalError();
          clearBlockedDatesSectionError();
          mergeSavedBlock(result.data.block);
          renderBlockedList();
          renderCalendar();
          if (typeof showSaveToast === 'function') showSaveToast();
          closeAllBlockModals();
          return;
        }
        var msg = (result.data && result.data.message) ? result.data.message : 'Could not save blocked dates.';
        if (result.data && result.data.errors) {
          var keys = Object.keys(result.data.errors);
          if (keys.length && result.data.errors[keys[0]][0]) {
            msg = result.data.errors[keys[0]][0];
          }
        }
        showBlockedDatesApiError(msg);
      })
      .catch(function() {
        showBlockedDatesApiError('Network error. Please try again.');
      });
  }

  function confirmRemoveBlocked() {
    if (pendingRemoveBlockedIndex === null) return;
    var index = pendingRemoveBlockedIndex;
    var b = blockedPeriods[index];
    if (!b) {
      closeRemoveBlockedConfirm();
      return;
    }
    closeRemoveBlockedConfirm();

    var delFd = new FormData();
    delFd.append('_token', BLOCK_CSRF);
    delFd.append('_method', 'DELETE');

    fetch(blockDestroyUrl(b.id), {
      method: 'POST',
      body: delFd,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
      .then(function(res) {
        return res.json().then(function(data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function(result) {
        if (result.ok && result.data && result.data.success) {
          clearBlockedDatesSectionError();
          blockedPeriods.splice(index, 1);
          clampBlockedListPage();
          renderBlockedList();
          renderCalendar();
          if (typeof showSaveToast === 'function') showSaveToast();
          return;
        }
        showBlockedDatesSectionError((result.data && result.data.message) ? result.data.message : 'Could not remove block.');
      })
      .catch(function() {
        showBlockedDatesSectionError('Network error. Please try again.');
      });
  }

  function submitBlockDate() {
    clearBlockDateModalError();
    var val = document.getElementById('blockDateInput').value;
    if (!val) {
      showBlockDateModalError('Please select a date.');
      return;
    }
    var reason = document.getElementById('blockDateReason').value.trim();
    saveBlockPeriod(val, val, reason);
  }

  function submitBlockRange() {
    clearBlockRangeModalError();
    var startStr = document.getElementById('blockRangeFrom').value;
    var endStr = document.getElementById('blockRangeTo').value;
    if (!startStr || !endStr) {
      showBlockRangeModalError('Please select both from and to dates.');
      return;
    }
    var start = fromInputDate(startStr);
    var end = fromInputDate(endStr);
    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);
    if (end < start) {
      showBlockRangeModalError('The end date must be on or after the start date.');
      return;
    }
    var reason = document.getElementById('blockRangeReason').value.trim();
    saveBlockPeriod(startStr, endStr, reason);
  }

  let currentMonth = new Date().getMonth();
  let currentYear = new Date().getFullYear();

  function changeMonth(delta) {
    closeMonthYearPicker();
    currentMonth += delta;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    renderCalendar();
  }

  function syncMonthYearPicker() {
    const monthSel = document.getElementById('calMonthSelect');
    const yearSel = document.getElementById('calYearSelect');
    if (monthSel.options.length === 0) {
      CAL_MONTHS.forEach(function(name, i) {
        const o = document.createElement('option');
        o.value = String(i);
        o.textContent = name;
        monthSel.appendChild(o);
      });
    }
    monthSel.value = String(currentMonth);
    const yNow = new Date().getFullYear();
    const yFrom = yNow - 25;
    const yTo = yNow + 15;
    yearSel.innerHTML = '';
    for (let y = yFrom; y <= yTo; y++) {
      const o = document.createElement('option');
      o.value = String(y);
      o.textContent = String(y);
      yearSel.appendChild(o);
    }
    if (currentYear < yFrom) {
      const o = document.createElement('option');
      o.value = String(currentYear);
      o.textContent = String(currentYear);
      yearSel.insertBefore(o, yearSel.firstChild);
    } else if (currentYear > yTo) {
      const o = document.createElement('option');
      o.value = String(currentYear);
      o.textContent = String(currentYear);
      yearSel.appendChild(o);
    }
    yearSel.value = String(currentYear);
  }

  function closeMonthYearPickerOnOutside(e) {
    const picker = document.getElementById('monthYearPicker');
    const btn = document.getElementById('calMonthBtn');
    if (!picker || picker.classList.contains('hidden')) return;
    if (picker.contains(e.target) || btn.contains(e.target)) return;
    closeMonthYearPicker();
  }

  function closeMonthYearPicker() {
    const picker = document.getElementById('monthYearPicker');
    const btn = document.getElementById('calMonthBtn');
    if (picker) picker.classList.add('hidden');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    document.removeEventListener('click', closeMonthYearPickerOnOutside, true);
  }

  function toggleMonthYearPicker(e) {
    e.stopPropagation();
    const picker = document.getElementById('monthYearPicker');
    const btn = document.getElementById('calMonthBtn');
    if (picker.classList.contains('hidden')) {
      syncMonthYearPicker();
      picker.classList.remove('hidden');
      btn.setAttribute('aria-expanded', 'true');
      setTimeout(function() {
        document.addEventListener('click', closeMonthYearPickerOnOutside, true);
      }, 0);
    } else {
      closeMonthYearPicker();
    }
  }

  function applyMonthYear() {
    const m = parseInt(document.getElementById('calMonthSelect').value, 10);
    const y = parseInt(document.getElementById('calYearSelect').value, 10);
    if (!isNaN(m) && m >= 0 && m <= 11 && !isNaN(y)) {
      currentMonth = m;
      currentYear = y;
      renderCalendar();
    }
    closeMonthYearPicker();
  }

  document.addEventListener('keydown', function monthYearPickerEsc(e) {
    if (e.key !== 'Escape') return;
    var removeM = document.getElementById('removeBlockedConfirmModal');
    if (removeM && !removeM.classList.contains('hidden')) {
      closeRemoveBlockedConfirm();
      return;
    }
    var dateM = document.getElementById('blockDateModal');
    var rangeM = document.getElementById('blockRangeModal');
    if (dateM && !dateM.classList.contains('hidden')) {
      closeBlockDateModal();
      return;
    }
    if (rangeM && !rangeM.classList.contains('hidden')) {
      closeBlockRangeModal();
      return;
    }
    var picker = document.getElementById('monthYearPicker');
    if (picker && !picker.classList.contains('hidden')) closeMonthYearPicker();
  });

  function isBlocked(date) {
    var key = toInputDate(date);
    return blockedPeriods.some(function(p) {
      return key >= p.start_date && key <= p.end_date;
    });
  }

  function renderCalendar() {
    document.getElementById('calMonth').textContent = CAL_MONTHS[currentMonth] + ' ' + currentYear;

    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    const firstDay = new Date(currentYear, currentMonth, 1);
    let startDay = firstDay.getDay() - 1;
    if (startDay < 0) startDay = 6;

    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let i = 0; i < startDay; i++) {
      const empty = document.createElement('div');
      empty.className = 'cal-day disabled';
      grid.appendChild(empty);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dayEl = document.createElement('div');
      dayEl.className = 'cal-day';
      dayEl.textContent = d;

      const date = new Date(currentYear, currentMonth, d);
      date.setHours(0, 0, 0, 0);

      if (date < today) {
        dayEl.classList.add('past');
      } else {
        if (date.getTime() === today.getTime()) {
          dayEl.classList.add('today');
        }
        if (isBlocked(date)) {
          dayEl.classList.add('blocked');
          dayEl.title = 'Blocked — remove it from Currently Blocked below to unblock';
        } else {
          dayEl.title = 'Use Block a date or Block a range below to block this day';
        }
      }

      grid.appendChild(dayEl);
    }
  }

  renderCalendar();
  renderBlockedList();

  // ── Working Hours Manager ──
  // Each day: { dayKey, day, letter, available, slots: [{ start, end }, ...] } — loaded from DB via AvailabilityController
  var workingHoursData = @json($workingHoursInitial ?? []);

  var WH_CSRF_TOKEN = '{{ csrf_token() }}';
  var WH_STORE_URL = '{{ route('availability.store') }}';

  var WH_INPUT_CLASS = 'wh-time-input text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30';

  function updateWhSlot(dayIdx, slotIdx, field, value) {
    if (!workingHoursData[dayIdx] || !workingHoursData[dayIdx].slots[slotIdx]) return;
    workingHoursData[dayIdx].slots[slotIdx][field] = value;
    clearWhDayError(dayIdx);
  }

  function removeWhSlot(dayIdx, slotIdx) {
    var d = workingHoursData[dayIdx];
    if (!d || !d.slots) return;
    d.slots.splice(slotIdx, 1);
    if (d.slots.length === 0) {
      d.available = false;
    }
    clearWhDayError(dayIdx);
    renderWorkingHours();
  }

  function addWhSlot(dayIdx) {
    var d = workingHoursData[dayIdx];
    if (!d) return;
    if (!d.slots) d.slots = [];
    d.slots.push({ start: '09:00', end: '17:00' });
    d.available = true;
    clearWhDayError(dayIdx);
    renderWorkingHours();
  }

  function renderWorkingHours() {
    var container = document.getElementById('workingHoursContainer');
    container.innerHTML = '';
    workingHoursData.forEach(function(item, idx) {
      if (item.available && (!item.slots || item.slots.length === 0)) {
        item.available = false;
      }
      var row = document.createElement('div');
      row.id = 'wh-row-' + idx;
      row.style.transition = 'all 0.3s ease';
      var borderClass = idx < workingHoursData.length - 1 ? ' border-b border-outline-variant/10' : '';

      if (item.available && item.slots && item.slots.length > 0) {
        row.className = 'flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4 py-4 px-2' + borderClass;
        var slotsParts = [];
        for (var s = 0; s < item.slots.length; s++) {
          var slot = item.slots[s];
          slotsParts.push(
            '<div class="flex items-center gap-2 flex-wrap w-full sm:w-auto sm:min-w-[260px] rounded-xl border border-outline-variant/20 bg-surface-container-low/60 px-3 py-2.5">' +
              '<label class="sr-only">From ' + item.day + ' slot ' + (s + 1) + '</label>' +
              '<input type="time" value="' + slot.start + '" onchange="updateWhSlot(' + idx + ',' + s + ',\'start\',this.value)" class="' + WH_INPUT_CLASS + '" step="300" title="Start time">' +
              '<span class="text-on-surface-variant text-sm shrink-0">–</span>' +
              '<label class="sr-only">To ' + item.day + ' slot ' + (s + 1) + '</label>' +
              '<input type="time" value="' + slot.end + '" onchange="updateWhSlot(' + idx + ',' + s + ',\'end\',this.value)" class="' + WH_INPUT_CLASS + '" step="300" title="End time">' +
              '<button type="button" onclick="removeWhSlot(' + idx + ',' + s + ')" class="p-2 rounded-lg hover:bg-error-container/80 transition-colors text-on-surface-variant hover:text-error shrink-0" title="Remove this time">' +
                '<span class="material-symbols-outlined text-lg">delete</span>' +
              '</button>' +
            '</div>'
          );
        }
        row.innerHTML =
          '<div class="w-9 h-9 min-w-[36px] rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm shrink-0">' + item.letter + '</div>' +
          '<div class="flex-1 min-w-0 flex flex-col gap-3">' +
            '<div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">' + item.day + '</div>' +
            '<div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-stretch sm:gap-x-4 sm:gap-y-3">' +
              slotsParts.join('') +
            '</div>' +
            '<div class="flex flex-col sm:flex-row sm:flex-wrap gap-3 pt-0.5">' +
              '<button type="button" onclick="addWhSlot(' + idx + ')" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto text-sm font-bold text-primary border-2 border-primary/30 bg-white rounded-xl px-4 py-2.5 hover:bg-primary/5 transition-colors">' +
                '<span class="material-symbols-outlined text-[20px] shrink-0">add</span> Add another time slot' +
              '</button>' +
              '<button type="button" onclick="toggleDay(' + idx + ', false)" class="inline-flex items-center justify-center w-full sm:w-auto text-sm font-semibold text-error border border-error/30 bg-white rounded-xl px-4 py-2.5 hover:bg-error-container/40 transition-colors">' +
                'Make this day unavailable' +
              '</button>' +
            '</div>' +
            '<p id="wh-day-error-' + idx + '" class="wh-day-error hidden text-sm font-medium text-error mt-1" role="alert"></p>' +
          '</div>';
      } else {
        row.className = 'flex items-center gap-3 sm:gap-4 py-4 px-2' + borderClass;
        row.innerHTML =
          '<div class="w-9 h-9 min-w-[36px] rounded-full border-2 border-outline-variant text-on-surface-variant flex items-center justify-center font-bold text-sm shrink-0">' + item.letter + '</div>' +
          '<div class="flex-1 min-w-0">' +
            '<div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-0.5">' + item.day + '</div>' +
            '<span class="text-sm text-on-surface-variant">Unavailable</span>' +
          '</div>' +
          '<button type="button" onclick="toggleDay(' + idx + ', true)" class="w-8 h-8 min-w-[32px] rounded-full bg-primary text-white flex items-center justify-center hover:opacity-90 transition-all shrink-0" title="Add hours">' +
            '<span class="material-symbols-outlined text-lg">add</span>' +
          '</button>';
      }

      container.appendChild(row);
    });
  }

  function toggleDay(idx, makeAvailable) {
    var row = document.getElementById('wh-row-' + idx);
    row.style.opacity = '0';
    setTimeout(function() {
      workingHoursData[idx].available = makeAvailable;
      if (makeAvailable) {
        workingHoursData[idx].slots = [{ start: '09:00', end: '17:00' }];
      } else {
        workingHoursData[idx].slots = [];
      }
      renderWorkingHours();
      var newRow = document.getElementById('wh-row-' + idx);
      newRow.style.opacity = '0';
      requestAnimationFrame(function() { newRow.style.opacity = '1'; });
    }, 200);
  }

  function whTimeToMinutes(t) {
    if (!t || typeof t !== 'string') return NaN;
    var p = t.split(':');
    if (p.length < 2) return NaN;
    return parseInt(p[0], 10) * 60 + parseInt(p[1], 10);
  }

  function clearWhDayError(idx) {
    var el = document.getElementById('wh-day-error-' + idx);
    if (el) {
      el.textContent = '';
      el.classList.add('hidden');
    }
  }

  function clearAllWhDayErrors() {
    for (var i = 0; i < workingHoursData.length; i++) {
      clearWhDayError(i);
    }
  }

  function showWhDayError(dayIndex, message) {
    clearAllWhDayErrors();
    var globalEl = document.getElementById('workingHoursError');
    if (globalEl) {
      globalEl.classList.add('hidden');
      globalEl.textContent = '';
    }
    var el = document.getElementById('wh-day-error-' + dayIndex);
    if (el) {
      el.textContent = message;
      el.classList.remove('hidden');
    }
    var row = document.getElementById('wh-row-' + dayIndex);
    if (row && typeof row.scrollIntoView === 'function') {
      row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  /** Match server messages like "Overlapping time slots on Monday." or "End time must be after start time on Monday." */
  function parseWhServerErrorDayIndex(message) {
    if (!message || typeof message !== 'string') return -1;
    var m = message.match(/\bon\s+([A-Za-z]+)\s*\./);
    if (!m) return -1;
    var name = m[1];
    for (var i = 0; i < workingHoursData.length; i++) {
      if (workingHoursData[i].day === name) return i;
    }
    return -1;
  }

  function validateWorkingHoursSlots() {
    var err = document.getElementById('workingHoursError');
    if (err) {
      err.classList.add('hidden');
      err.textContent = '';
    }
    clearAllWhDayErrors();

    for (var d = 0; d < workingHoursData.length; d++) {
      var item = workingHoursData[d];
      if (!item.available || !item.slots || item.slots.length === 0) continue;
      var dayLabel = item.day;
      var intervals = [];
      for (var s = 0; s < item.slots.length; s++) {
        var sl = item.slots[s];
        var from = whTimeToMinutes(sl.start);
        var to = whTimeToMinutes(sl.end);
        if (isNaN(from) || isNaN(to)) {
          return { dayIndex: d, message: 'Please set valid start and end times for every slot.' };
        }
        if (from >= to) {
          return { dayIndex: d, message: 'End time must be after start time.' };
        }
        intervals.push({ from: from, to: to });
      }
      intervals.sort(function(a, b) { return a.from - b.from; });
      for (var i = 0; i < intervals.length - 1; i++) {
        if (intervals[i].to > intervals[i + 1].from) {
          return { dayIndex: d, message: 'These time slots overlap. Adjust the times so they do not cross.' };
        }
      }
    }
    return null;
  }

  function buildWorkingHoursAvailabilityPayload() {
    var out = {};
    workingHoursData.forEach(function(item) {
      if (!item.dayKey || !item.available || !item.slots || !item.slots.length) return;
      out[item.dayKey] = item.slots.map(function(s) {
        return { from: s.start, to: s.end };
      });
    });
    return out;
  }

  function saveWorkingHours() {
    var errEl = document.getElementById('workingHoursError');
    var btn = document.getElementById('saveWorkingHoursBtn');
    var btnIcon = document.getElementById('saveWorkingHoursBtnIcon');
    var btnLabel = document.getElementById('saveWorkingHoursBtnLabel');

    var validation = validateWorkingHoursSlots();
    if (validation) {
      showWhDayError(validation.dayIndex, validation.message);
      return;
    }

    var payload = buildWorkingHoursAvailabilityPayload();
    var fd = new FormData();
    fd.append('_token', WH_CSRF_TOKEN);
    Object.keys(payload).forEach(function(day) {
      payload[day].forEach(function(slot, i) {
        fd.append('availability[' + day + '][' + i + '][from]', slot.from);
        fd.append('availability[' + day + '][' + i + '][to]', slot.to);
      });
    });

    if (btn) {
      btn.disabled = true;
      btn.setAttribute('aria-busy', 'true');
    }
    if (btnIcon) {
      btnIcon.textContent = 'hourglass_empty';
      btnIcon.classList.add('is-loading');
    }
    if (btnLabel) btnLabel.textContent = 'Saving...';

    fetch(WH_STORE_URL, {
      method: 'POST',
      body: fd,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
      .then(function(res) {
        return res.json().then(function(data) {
          return { ok: res.ok, status: res.status, data: data };
        });
      })
      .then(function(result) {
        if (result.ok && result.data && result.data.success) {
          clearAllWhDayErrors();
          if (errEl) {
            errEl.classList.add('hidden');
            errEl.textContent = '';
          }
          if (typeof showSaveToast === 'function') showSaveToast();
          return;
        }
        var errMsg = (result.data && result.data.message) ? result.data.message : 'Could not save working hours.';
        if (result.data && result.data.errors) {
          if (result.data.errors.availability) {
            errMsg = Array.isArray(result.data.errors.availability)
              ? result.data.errors.availability[0]
              : result.data.errors.availability;
          }
        }
        var dayIdx = parseWhServerErrorDayIndex(errMsg);
        if (dayIdx >= 0) {
          showWhDayError(dayIdx, errMsg);
        } else if (errEl) {
          clearAllWhDayErrors();
          errEl.textContent = errMsg;
          errEl.classList.remove('hidden');
        }
      })
      .catch(function() {
        clearAllWhDayErrors();
        if (errEl) {
          errEl.textContent = 'Network error. Please try again.';
          errEl.classList.remove('hidden');
        }
      })
      .finally(function() {
        if (btn) {
          btn.disabled = false;
          btn.removeAttribute('aria-busy');
        }
        var iconEl = document.getElementById('saveWorkingHoursBtnIcon');
        var labelEl = document.getElementById('saveWorkingHoursBtnLabel');
        if (iconEl) {
          iconEl.textContent = 'save';
          iconEl.classList.remove('is-loading');
        }
        if (labelEl) labelEl.textContent = 'Save working hours';
      });
  }

  renderWorkingHours();
</script>
@endsection