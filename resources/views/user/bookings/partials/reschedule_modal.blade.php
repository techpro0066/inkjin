@php
    $userBookingsBase = rtrim((string) url('/user/bookings'), '/');
    $apiBookingsBase = rtrim((string) url('/api/bookings'), '/');
    $bookingsRescheduleBase = rtrim((string) url('/bookings'), '/');
@endphp

<div id="userRescheduleModal" class="user-rsm-modal fixed inset-0 z-[220] opacity-0 pointer-events-none transition-opacity duration-300 ease-out" aria-hidden="true" role="dialog" aria-labelledby="userRescheduleModalTitle">
  <div class="rsm-cal-backdrop absolute inset-0 bg-primary/55 backdrop-blur-[2px]" data-close-rsm></div>
  <div class="relative flex min-h-full items-end sm:items-center justify-center p-4 sm:p-6 pointer-events-none">
    <div class="rsm-cal-panel w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-outline-variant/30 overflow-hidden max-h-[92vh] flex flex-col">
      <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-outline-variant/20 shrink-0 bg-surface-container-low/40">
        <h2 id="userRescheduleModalTitle" class="text-lg font-extrabold text-on-surface tracking-tight">Reschedule</h2>
        <button type="button" class="w-10 h-10 rounded-full bg-surface-container-low text-on-surface flex items-center justify-center hover:bg-surface-container-high transition-colors" data-close-rsm aria-label="Close">
          <span class="material-symbols-outlined text-[22px]">close</span>
        </button>
      </div>
      <div class="p-5 sm:p-6 overflow-y-auto flex-1">
        <p id="rsmSubtitle" class="text-sm text-on-surface-variant mb-4"></p>
        <p id="rsmLoading" class="text-sm text-on-surface-variant">Loading available times…</p>
        <p id="rsmError" class="hidden text-sm font-semibold text-error mb-3"></p>
        <a id="rsmFullPageLink" class="hidden text-sm font-semibold text-primary hover:underline mb-4 inline-block" href="#">Open full reschedule page</a>
        <div id="rsmBody" class="hidden space-y-6">
          <div id="rsmSeparateConsult" class="hidden">
            <h3 class="text-sm font-bold text-on-surface mb-2">Consultation</h3>
            <div class="flex items-center justify-between mb-2">
              <button type="button" class="rsm-nav-prev-cc text-on-surface-variant hover:text-primary p-1 rounded-lg hover:bg-surface-container-low" aria-label="Previous month"><span class="material-symbols-outlined">chevron_left</span></button>
              <span id="rsmCcMonthLabel" class="text-sm font-bold text-on-surface"></span>
              <button type="button" class="rsm-nav-next-cc text-on-surface-variant hover:text-primary p-1 rounded-lg hover:bg-surface-container-low" aria-label="Next month"><span class="material-symbols-outlined">chevron_right</span></button>
            </div>
            <div class="rsm-cal-card p-3 mb-3">
              <div id="rsmCcGrid" class="rsm-cal-grid"></div>
            </div>
            <p class="text-xs font-semibold text-on-surface-variant mb-2">Time</p>
            <div id="rsmCcSlots" class="grid grid-cols-2 sm:grid-cols-3 gap-2"></div>
          </div>
          <div id="rsmSeparateTattoo" class="hidden">
            <h3 class="text-sm font-bold text-on-surface mb-2">Tattoo session</h3>
            <div class="flex items-center justify-between mb-2">
              <button type="button" class="rsm-nav-prev-tat text-on-surface-variant hover:text-primary p-1 rounded-lg hover:bg-surface-container-low" aria-label="Previous month"><span class="material-symbols-outlined">chevron_left</span></button>
              <span id="rsmTatMonthLabel" class="text-sm font-bold text-on-surface"></span>
              <button type="button" class="rsm-nav-next-tat text-on-surface-variant hover:text-primary p-1 rounded-lg hover:bg-surface-container-low" aria-label="Next month"><span class="material-symbols-outlined">chevron_right</span></button>
            </div>
            <div class="rsm-cal-card p-3 mb-3">
              <div id="rsmTatGrid" class="rsm-cal-grid"></div>
            </div>
            <p class="text-xs font-semibold text-on-surface-variant mb-2">Time</p>
            <div id="rsmTatSlots" class="grid grid-cols-2 sm:grid-cols-3 gap-2"></div>
          </div>
          <div id="rsmSingleFlow" class="hidden">
            <div class="flex items-center justify-between mb-2">
              <button type="button" class="rsm-nav-prev-main text-on-surface-variant hover:text-primary p-1 rounded-lg hover:bg-surface-container-low" aria-label="Previous month"><span class="material-symbols-outlined">chevron_left</span></button>
              <span id="rsmMainMonthLabel" class="text-sm font-bold text-on-surface"></span>
              <button type="button" class="rsm-nav-next-main text-on-surface-variant hover:text-primary p-1 rounded-lg hover:bg-surface-container-low" aria-label="Next month"><span class="material-symbols-outlined">chevron_right</span></button>
            </div>
            <div class="rsm-cal-card p-3 mb-3">
              <div id="rsmMainGrid" class="rsm-cal-grid"></div>
            </div>
            <p class="text-xs font-semibold text-on-surface-variant mb-2">Time</p>
            <div id="rsmMainSlots" class="grid grid-cols-2 sm:grid-cols-3 gap-2"></div>
          </div>
          <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
            <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-outline-variant/40 text-on-surface hover:bg-surface-container-low" data-close-rsm>Close</button>
            <button type="button" id="rsmSubmit" disabled class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary text-on-primary disabled:opacity-50 disabled:cursor-not-allowed">Save new time</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var USER_BOOKINGS_BASE = @json($userBookingsBase);
  var API_BOOKINGS_BASE = @json($apiBookingsBase);
  var BOOKINGS_RESCHEDULE_BASE = @json($bookingsRescheduleBase);
  var modal = document.getElementById('userRescheduleModal');
  if (!modal) return;

  var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  var weekdayKeys = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
  var today = new Date();
  var todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0);

  var rsmBookingId = null;
  var artistTimezone = 'UTC';
  var artistAvailabilitySchedule = {};
  var artistBlockedPeriods = [];
  var artistBusyIntervalsByDate = {};
  var tattooDurationMinutes = 120;
  var artistConsultationSettings = {};
  var consultationRequired = false;
  var consultationTiming = 'combined';
  var consultDurationMinutes = 30;
  var consultGapValue = 0;
  var bookingHasLinkedConsult = false;

  var mainYear, mainMonth, mainSelDate, mainSelTime;
  var ccYear, ccMonth, ccSelDate, ccSelTime;
  var tatYear, tatMonth, tatSelDate, tatSelTime;

  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function formatYmdArtistLocal(dateObj) {
    if (!(dateObj instanceof Date)) return '';
    try {
      return new Intl.DateTimeFormat('en-CA', { timeZone: artistTimezone, year: 'numeric', month: '2-digit', day: '2-digit' }).format(dateObj);
    } catch (e) {
      var y = dateObj.getFullYear();
      var mo = String(dateObj.getMonth() + 1).padStart(2, '0');
      var d = String(dateObj.getDate()).padStart(2, '0');
      return y + '-' + mo + '-' + d;
    }
  }

  function isArtistDateBlocked(ymd) {
    if (!ymd || !Array.isArray(artistBlockedPeriods) || !artistBlockedPeriods.length) return false;
    for (var i = 0; i < artistBlockedPeriods.length; i++) {
      var p = artistBlockedPeriods[i];
      if (!p) continue;
      var s = String(p.start_date || '');
      var e = String(p.end_date || '');
      if (ymd >= s && ymd <= e) return true;
    }
    return false;
  }

  function slotOverlapsExistingBooking(ymd, slotStartMin, requiredMinutes) {
    var slotEndMin = slotStartMin + requiredMinutes;
    var list = artistBusyIntervalsByDate[ymd];
    if (!Array.isArray(list) || !list.length) return false;
    for (var i = 0; i < list.length; i++) {
      var b = list[i];
      var bs = parseInt(b.start, 10);
      var be = parseInt(b.end, 10);
      if (isNaN(bs) || isNaN(be)) continue;
      if (slotStartMin < be && slotEndMin > bs) return true;
    }
    return false;
  }

  function formatTo12Hour(hour, minute) {
    var suffix = hour >= 12 ? 'PM' : 'AM';
    var h = hour % 12;
    if (h === 0) h = 12;
    var mm = String(minute).padStart(2, '0');
    return h + ':' + mm + ' ' + suffix;
  }

  function buildSlotsFromRanges(ranges, requiredMinutes) {
    var slots = [];
    if (!Array.isArray(ranges)) return slots;
    var minRequired = Math.max(0, parseInt(requiredMinutes || 0, 10) || 0);
    ranges.forEach(function (range) {
      if (!range || !range.start || !range.end) return;
      var sp = String(range.start).split(':');
      var ep = String(range.end).split(':');
      var startMinutes = (parseInt(sp[0] || '0', 10) * 60) + parseInt(sp[1] || '0', 10);
      var endMinutes = (parseInt(ep[0] || '0', 10) * 60) + parseInt(ep[1] || '0', 10);
      if (isNaN(startMinutes) || isNaN(endMinutes) || endMinutes <= startMinutes) return;
      for (var m = startMinutes; m < endMinutes; m += 30) {
        if (m + minRequired > endMinutes) break;
        var hour = Math.floor(m / 60);
        var minute = m % 60;
        slots.push({ time: formatTo12Hour(hour, minute), booked: false });
      }
    });
    return slots;
  }

  function getSlotsForDate(dateObj, requiredMinutes) {
    if (!(dateObj instanceof Date)) return [];
    var dayStart = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
    if (dayStart < todayStart) return [];
    var ymdArtist = formatYmdArtistLocal(dateObj);
    if (ymdArtist && isArtistDateBlocked(ymdArtist)) return [];
    var weekdayKey = weekdayKeys[dateObj.getDay()];
    var dayRanges = artistAvailabilitySchedule[weekdayKey];
    if (!Array.isArray(dayRanges) || !dayRanges.length) return [];
    var slots = buildSlotsFromRanges(dayRanges, requiredMinutes);
    if (dayStart.getTime() === todayStart.getTime()) {
      var now = new Date();
      var nowMinutes = now.getHours() * 60 + now.getMinutes();
      slots = slots.filter(function (slot) {
        return parseTime12hToMinutes(slot.time) > nowMinutes;
      });
    }
    var minRequired = Math.max(0, parseInt(requiredMinutes || 0, 10) || 0);
    if (ymdArtist && minRequired > 0) {
      slots = slots.filter(function (slot) {
        var sm = parseTime12hToMinutes(slot.time);
        return !slotOverlapsExistingBooking(ymdArtist, sm, minRequired);
      });
    }
    return slots;
  }

  function getHypotheticalSlotsForDate(dateObj, requiredMinutes) {
    if (!(dateObj instanceof Date)) return [];
    var dayStart = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
    if (dayStart < todayStart) return [];
    var ymdArtist = formatYmdArtistLocal(dateObj);
    if (ymdArtist && isArtistDateBlocked(ymdArtist)) return [];
    var weekdayKey = weekdayKeys[dateObj.getDay()];
    var dayRanges = artistAvailabilitySchedule[weekdayKey];
    if (!Array.isArray(dayRanges) || !dayRanges.length) return [];
    var slots = buildSlotsFromRanges(dayRanges, requiredMinutes);
    if (dayStart.getTime() === todayStart.getTime()) {
      var now = new Date();
      var nowMinutes = now.getHours() * 60 + now.getMinutes();
      slots = slots.filter(function (slot) {
        return parseTime12hToMinutes(slot.time) > nowMinutes;
      });
    }
    return slots;
  }

  function isDateFullyBookedOut(dateObj, requiredMinutes) {
    if (!(dateObj instanceof Date)) return false;
    var dayStart = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
    if (dayStart < todayStart) return false;
    var ymd = formatYmdArtistLocal(dateObj);
    if (ymd && isArtistDateBlocked(ymd)) return false;
    var hypo = getHypotheticalSlotsForDate(dateObj, requiredMinutes);
    if (!hypo.length) return false;
    return getSlotsForDate(dateObj, requiredMinutes).length === 0;
  }

  function getMainRequiredMinutes() {
    if (!consultationRequired) return tattooDurationMinutes;
    if (consultationTiming === 'combined') return tattooDurationMinutes + consultDurationMinutes;
    return tattooDurationMinutes;
  }

  function getConsultSelectionRequiredMinutes() {
    if (consultationTiming === 'combined') return tattooDurationMinutes + consultDurationMinutes;
    return consultDurationMinutes;
  }

  function parseTime12hToMinutes(timeLabel) {
    var match = String(timeLabel || '').trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return 0;
    var h = parseInt(match[1], 10);
    var m = parseInt(match[2], 10);
    var meridiem = match[3].toUpperCase();
    if (meridiem === 'PM' && h !== 12) h += 12;
    if (meridiem === 'AM' && h === 12) h = 0;
    return h * 60 + m;
  }

  function buildDateTime(dateObj, timeLabel) {
    var mins = parseTime12hToMinutes(timeLabel);
    var dt = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
    dt.setMinutes(mins);
    return dt;
  }

  function getTattooMinDateTime() {
    if (!ccSelDate || !ccSelTime) return null;
    if (consultationTiming === 'separate') {
      var gapDays = Math.max(0, consultGapValue);
      var minDate = new Date(ccSelDate.getFullYear(), ccSelDate.getMonth(), ccSelDate.getDate(), 0, 0, 0, 0);
      minDate.setDate(minDate.getDate() + gapDays + 1);
      return minDate;
    }
    return null;
  }

  function canNavigateToMonth(year, month) {
    var firstOfTarget = new Date(year, month, 1, 0, 0, 0, 0);
    var firstOfCurrent = new Date(todayStart.getFullYear(), todayStart.getMonth(), 1, 0, 0, 0, 0);
    return firstOfTarget >= firstOfCurrent;
  }

  function renderCalendarInto(gridId, labelId, year, month, selectedDateObj, minDateObj, requiredMinutes, onPickDay) {
    var grid = document.getElementById(gridId);
    var label = document.getElementById(labelId);
    if (!grid || !label) return;
    grid.innerHTML = '';
    label.textContent = monthNames[month] + ' ' + year;
    var firstDay = new Date(year, month, 1).getDay();
    var startOffset = (firstDay + 6) % 7;
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    var minDay = null;
    if (minDateObj instanceof Date) {
      minDay = new Date(minDateObj.getFullYear(), minDateObj.getMonth(), minDateObj.getDate(), 0, 0, 0, 0);
    }
    for (var i = 0; i < startOffset; i++) {
      var empty = document.createElement('div');
      empty.className = 'rsm-cal-day empty';
      grid.appendChild(empty);
    }
    for (var d = 1; d <= daysInMonth; d++) {
      (function (day) {
        var dt = new Date(year, month, day);
        var div = document.createElement('div');
        div.textContent = day;
        var ymdCell = formatYmdArtistLocal(dt);
        var isBlockedDay = !!(ymdCell && isArtistDateBlocked(ymdCell));
        var isSel = selectedDateObj && dt.toDateString() === selectedDateObj.toDateString();
        var isToday = dt.toDateString() === today.toDateString();
        var isFuture = dt > today;
        var isBeforeMin = minDay && dt < minDay;
        var isAvail = getSlotsForDate(dt, requiredMinutes).length > 0;
        var isFullyBooked = !isBeforeMin && !isAvail && !isBlockedDay && (isFuture || isToday) && isDateFullyBookedOut(dt, requiredMinutes);
        var cls = 'rsm-cal-day';
        if (isSel) cls += ' selected';
        else if (isAvail && !isBeforeMin) cls += ' available';
        else if (!isBeforeMin && isBlockedDay && (isFuture || isToday)) cls += ' blocked-by-artist';
        else if (isFullyBooked) cls += ' fully-booked-day';
        else if (isFuture || isToday) cls += ' unavailable-future';
        else cls += ' unavailable';
        if (isToday && !isSel) cls += ' today';
        div.className = cls;
        if (isAvail && !isBeforeMin) {
          div.addEventListener('click', function () { onPickDay(dt); });
        }
        grid.appendChild(div);
      })(d);
    }
  }

  function fillSlots(containerId, dateObj, requiredMinutes, selectedTime, onPick, minDt) {
    var el = document.getElementById(containerId);
    if (!el) return;
    if (!dateObj) {
      el.innerHTML = '';
      return;
    }
    var slots = getSlotsForDate(dateObj, requiredMinutes);
    el.innerHTML = '';
    slots.forEach(function (slot) {
      if (minDt) {
        var slotDt = buildDateTime(dateObj, slot.time);
        if (slotDt < minDt) return;
      }
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'rsm-slot' + (selectedTime === slot.time ? ' selected' : '');
      btn.textContent = slot.time;
      btn.addEventListener('click', function () {
        onPick(slot.time);
      });
      el.appendChild(btn);
    });
  }

  function pickMainTime(t) {
    mainSelTime = t;
    fillSlots('rsmMainSlots', mainSelDate, getMainRequiredMinutes(), mainSelTime, pickMainTime, null);
    updateSubmitState();
  }

  function refreshMain() {
    renderCalendarInto('rsmMainGrid', 'rsmMainMonthLabel', mainYear, mainMonth, mainSelDate, null, getMainRequiredMinutes(), function (dt) {
      mainSelDate = dt;
      mainSelTime = null;
      refreshMain();
    });
    fillSlots('rsmMainSlots', mainSelDate, getMainRequiredMinutes(), mainSelTime, pickMainTime, null);
  }

  function pickCcTime(t) {
    ccSelTime = t;
    tatSelDate = null;
    tatSelTime = null;
    fillSlots('rsmCcSlots', ccSelDate, getConsultSelectionRequiredMinutes(), ccSelTime, pickCcTime, null);
    refreshTattoo();
    updateSubmitState();
  }

  function refreshCc() {
    renderCalendarInto('rsmCcGrid', 'rsmCcMonthLabel', ccYear, ccMonth, ccSelDate, null, getConsultSelectionRequiredMinutes(), function (dt) {
      ccSelDate = dt;
      ccSelTime = null;
      tatSelDate = null;
      tatSelTime = null;
      refreshCc();
    });
    fillSlots('rsmCcSlots', ccSelDate, getConsultSelectionRequiredMinutes(), ccSelTime, pickCcTime, null);
    refreshTattoo();
  }

  function pickTatTime(t) {
    tatSelTime = t;
    var minDt = consultationTiming === 'separate' ? getTattooMinDateTime() : null;
    fillSlots('rsmTatSlots', tatSelDate, tattooDurationMinutes, tatSelTime, pickTatTime, minDt);
    updateSubmitState();
  }

  function refreshTattoo() {
    var minDt = consultationTiming === 'separate' ? getTattooMinDateTime() : null;
    renderCalendarInto('rsmTatGrid', 'rsmTatMonthLabel', tatYear, tatMonth, tatSelDate, minDt, tattooDurationMinutes, function (dt) {
      tatSelDate = dt;
      tatSelTime = null;
      refreshTattoo();
    });
    fillSlots('rsmTatSlots', tatSelDate, tattooDurationMinutes, tatSelTime, pickTatTime, minDt);
  }

  function updateSubmitState() {
    var btn = document.getElementById('rsmSubmit');
    if (!btn) return;
    var ok = false;
    if (consultationRequired && consultationTiming === 'separate' && !bookingHasLinkedConsult) {
      ok = !!(ccSelDate && ccSelTime && tatSelDate && tatSelTime);
    } else {
      ok = !!(mainSelDate && mainSelTime);
    }
    btn.disabled = !ok;
  }

  function resetState() {
    rsmBookingId = null;
    mainSelDate = mainSelTime = null;
    ccSelDate = ccSelTime = tatSelDate = tatSelTime = null;
    mainYear = ccYear = tatYear = today.getFullYear();
    mainMonth = ccMonth = tatMonth = today.getMonth();
  }

  function openModal(bookingId) {
    resetState();
    rsmBookingId = bookingId;
    document.getElementById('rsmError').classList.add('hidden');
    var fl = document.getElementById('rsmFullPageLink');
    if (fl) fl.classList.add('hidden');
    document.getElementById('rsmBody').classList.add('hidden');
    document.getElementById('rsmLoading').classList.remove('hidden');
    document.getElementById('rsmSubtitle').textContent = '';

    modal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(function () {
      modal.classList.add('rsm-open', 'opacity-100');
      modal.classList.remove('opacity-0', 'pointer-events-none');
    });
    document.body.style.overflow = 'hidden';

    fetch(USER_BOOKINGS_BASE + '/' + encodeURIComponent(bookingId) + '/reschedule-calendar-data', {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
      credentials: 'same-origin',
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        document.getElementById('rsmLoading').classList.add('hidden');
        if (!res.ok || !res.body.success) {
          throw new Error((res.body && res.body.message) || 'Could not load calendar');
        }
        var d = res.body.data;
        artistTimezone = d.artistTimezone || 'UTC';
        artistAvailabilitySchedule = d.artistAvailabilitySchedule || {};
        artistBlockedPeriods = d.artistBlockedPeriods || [];
        artistBusyIntervalsByDate = d.artistBusyIntervalsByDate || {};
        tattooDurationMinutes = parseInt(d.tattooDurationMinutes, 10) || 120;
        artistConsultationSettings = d.artistConsultationSettings || {};
        consultDurationMinutes = parseInt(artistConsultationSettings.session_duration_minutes || 30, 10) || 30;
        consultGapValue = parseInt(artistConsultationSettings.gap_value || 0, 10) || 0;
        var b = d.booking || {};
        consultationRequired = !!b.has_consultation;
        consultationTiming = String(b.consultation_timing_type || 'combined').toLowerCase();
        if (consultationTiming !== 'separate') consultationTiming = 'combined';
        bookingHasLinkedConsult = !!b.consultation_booking_id;

        document.getElementById('rsmSubtitle').textContent = bookingHasLinkedConsult
          ? 'This booking uses a linked consultation appointment — use the full reschedule page if you need to move both.'
          : 'Choose a new date and time. Your artist’s availability and session length apply.';

        document.getElementById('rsmBody').classList.remove('hidden');

        if (bookingHasLinkedConsult) {
          document.getElementById('rsmSingleFlow').classList.add('hidden');
          document.getElementById('rsmSeparateConsult').classList.add('hidden');
          document.getElementById('rsmSeparateTattoo').classList.add('hidden');
          document.getElementById('rsmError').textContent = 'This booking has a separate consultation record. Use the full reschedule page to move both appointments together.';
          document.getElementById('rsmError').classList.remove('hidden');
          var fl = document.getElementById('rsmFullPageLink');
          if (fl) {
            fl.href = BOOKINGS_RESCHEDULE_BASE + '/' + encodeURIComponent(rsmBookingId) + '/reschedule';
            fl.classList.remove('hidden');
          }
          document.getElementById('rsmSubmit').disabled = true;
          return;
        }

        if (consultationRequired && consultationTiming === 'separate') {
          document.getElementById('rsmSingleFlow').classList.add('hidden');
          document.getElementById('rsmSeparateConsult').classList.remove('hidden');
          document.getElementById('rsmSeparateTattoo').classList.remove('hidden');
          refreshCc();
          refreshTattoo();
        } else {
          document.getElementById('rsmSeparateConsult').classList.add('hidden');
          document.getElementById('rsmSeparateTattoo').classList.add('hidden');
          document.getElementById('rsmSingleFlow').classList.remove('hidden');
          refreshMain();
        }
        updateSubmitState();
      })
      .catch(function (e) {
        document.getElementById('rsmLoading').classList.add('hidden');
        document.getElementById('rsmError').textContent = e.message || 'Error';
        document.getElementById('rsmError').classList.remove('hidden');
      });
  }

  function closeModal() {
    modal.classList.remove('rsm-open', 'opacity-100');
    modal.classList.add('opacity-0', 'pointer-events-none');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.js-user-reschedule-open').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = this.getAttribute('data-booking-id');
      if (id) openModal(id);
    });
  });

  modal.querySelectorAll('[data-close-rsm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  function bindNav(sel, handler) {
    var el = modal.querySelector(sel);
    if (el) el.addEventListener('click', handler);
  }
  bindNav('.rsm-nav-prev-main', function () {
    if (!canNavigateToMonth(mainYear, mainMonth - 1)) return;
    mainMonth--;
    if (mainMonth < 0) { mainMonth = 11; mainYear--; }
    refreshMain();
  });
  bindNav('.rsm-nav-next-main', function () {
    mainMonth++;
    if (mainMonth > 11) { mainMonth = 0; mainYear++; }
    refreshMain();
  });
  bindNav('.rsm-nav-prev-cc', function () {
    if (!canNavigateToMonth(ccYear, ccMonth - 1)) return;
    ccMonth--;
    if (ccMonth < 0) { ccMonth = 11; ccYear--; }
    refreshCc();
  });
  bindNav('.rsm-nav-next-cc', function () {
    ccMonth++;
    if (ccMonth > 11) { ccMonth = 0; ccYear++; }
    refreshCc();
  });
  bindNav('.rsm-nav-prev-tat', function () {
    if (!canNavigateToMonth(tatYear, tatMonth - 1)) return;
    tatMonth--;
    if (tatMonth < 0) { tatMonth = 11; tatYear--; }
    refreshTattoo();
  });
  bindNav('.rsm-nav-next-tat', function () {
    tatMonth++;
    if (tatMonth > 11) { tatMonth = 0; tatYear++; }
    refreshTattoo();
  });

  var submitBtn = document.getElementById('rsmSubmit');
  if (submitBtn) {
    submitBtn.addEventListener('click', function () {
      if (!rsmBookingId) return;
      var body = { reason: '' };
      if (consultationRequired && consultationTiming === 'separate' && !bookingHasLinkedConsult) {
        body.new_date = formatYmdArtistLocal(tatSelDate);
        body.new_start_local = tatSelTime;
        body.consultation_date = formatYmdArtistLocal(ccSelDate);
        body.consultation_start_local = ccSelTime;
      } else {
        body.new_date = formatYmdArtistLocal(mainSelDate);
        body.new_start_local = mainSelTime;
      }
      submitBtn.disabled = true;
      fetch(API_BOOKINGS_BASE + '/' + encodeURIComponent(rsmBookingId) + '/reschedule', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf(),
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
      })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
          if (!res.ok || !res.body.success) {
            var msg = (res.body && res.body.message) || 'Reschedule failed';
            if (res.body && res.body.errors) {
              var parts = [];
              Object.keys(res.body.errors).forEach(function (k) {
                var arr = res.body.errors[k];
                if (Array.isArray(arr)) parts = parts.concat(arr);
              });
              if (parts.length) msg = parts.join(' ');
            }
            throw new Error(msg);
          }
          closeModal();
          window.location.reload();
        })
        .catch(function (e) {
          document.getElementById('rsmError').textContent = e.message || 'Error';
          document.getElementById('rsmError').classList.remove('hidden');
        })
        .finally(function () {
          submitBtn.disabled = false;
          updateSubmitState();
        });
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('rsm-open')) closeModal();
  });
})();
</script>
