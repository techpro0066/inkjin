<script>
(function() {
  var CAL = @json($calendarPayload);
  var SAVED = @json($savedSelection);
  var SAVED_CONSULT = @json($savedConsultSelection);
  var artistConsultationSettings = CAL.artistConsultationSettings || {};
  var consultationTiming = String(artistConsultationSettings.timing || 'combined').trim().toLowerCase();
  if (consultationTiming !== 'separate') consultationTiming = 'combined';
  var consultationSessionType = String(artistConsultationSettings.session_type || 'both').trim().toLowerCase();
  var consultDurationMinutes = parseInt(artistConsultationSettings.session_duration_minutes || @json($consultDurationMinutes), 10) || 30;
  var consultGapValue = parseInt(artistConsultationSettings.gap_value || 0, 10) || 0;
  var artistAvailabilitySchedule = CAL.artistAvailabilitySchedule || {};
  var artistTimezone = CAL.artistTimezone || 'UTC';
  var artistBlockedPeriods = CAL.artistBlockedPeriods || [];
  var artistBusyIntervalsByDate = CAL.artistBusyIntervalsByDate || {};
  var tattooDurationMinutes = parseInt(CAL.tattooDurationMinutes || @json($durationMinutes), 10) || 120;
  var weekdayKeys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
  var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  var today = new Date();
  var todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0);
  var ccConsultDate = null;
  var ccConsultTime = null;
  var ccTattooDate = null;
  var ccTattooTime = null;
  var ccConsultType = null;
  var ccCalYear = today.getFullYear();
  var ccCalMonth = today.getMonth();
  var ccTatCalYear = today.getFullYear();
  var ccTatCalMonth = today.getMonth();

  function formatYmdArtistLocal(dateObj) {
    if (!(dateObj instanceof Date)) return '';
    try {
      return new Intl.DateTimeFormat('en-CA', { timeZone: artistTimezone, year: 'numeric', month: '2-digit', day: '2-digit' }).format(dateObj);
    } catch (e) {
      var y = dateObj.getFullYear();
      var m = String(dateObj.getMonth() + 1).padStart(2, '0');
      var d = String(dateObj.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + d;
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
    ranges.forEach(function(range) {
      if (!range || !range.start || !range.end) return;
      var startParts = String(range.start).split(':');
      var endParts = String(range.end).split(':');
      var startMinutes = (parseInt(startParts[0] || '0', 10) * 60) + parseInt(startParts[1] || '0', 10);
      var endMinutes = (parseInt(endParts[0] || '0', 10) * 60) + parseInt(endParts[1] || '0', 10);
      if (isNaN(startMinutes) || isNaN(endMinutes) || endMinutes <= startMinutes) return;
      for (var m = startMinutes; m < endMinutes; m += 30) {
        if (m + minRequired > endMinutes) break;
        slots.push({ time: formatTo12Hour(Math.floor(m / 60), m % 60), booked: false });
      }
    });
    return slots;
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

  function parseHiToMinutes(hi) {
    var parts = String(hi || '').trim().split(':');
    if (parts.length < 2) return 0;
    var h = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10);
    if (isNaN(h) || isNaN(m)) return 0;
    return h * 60 + m;
  }

  function minutesToHi(total) {
    var h = Math.floor(total / 60);
    var m = total % 60;
    return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
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
      slots = slots.filter(function(slot) {
        return parseTime12hToMinutes(slot.time) > nowMinutes;
      });
    }
    var minRequired = Math.max(0, parseInt(requiredMinutes || 0, 10) || 0);
    if (ymdArtist && minRequired > 0) {
      slots = slots.filter(function(slot) {
        var sm = parseTime12hToMinutes(slot.time);
        return !slotOverlapsExistingBooking(ymdArtist, sm, minRequired);
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
    var weekdayKey = weekdayKeys[dateObj.getDay()];
    var dayRanges = artistAvailabilitySchedule[weekdayKey];
    if (!Array.isArray(dayRanges) || !dayRanges.length) return false;
    var hypo = buildSlotsFromRanges(dayRanges, requiredMinutes);
    if (!hypo.length) return false;
    return getSlotsForDate(dateObj, requiredMinutes).length === 0;
  }

  function canNavigateToMonth(year, month) {
    var firstOfTarget = new Date(year, month, 1, 0, 0, 0, 0);
    var firstOfCurrent = new Date(todayStart.getFullYear(), todayStart.getMonth(), 1, 0, 0, 0, 0);
    return firstOfTarget >= firstOfCurrent;
  }

  function getConsultSelectionRequiredMinutes() {
    if (consultationTiming === 'combined') return tattooDurationMinutes + consultDurationMinutes;
    return consultDurationMinutes;
  }

  function buildDateTime(dateObj, timeLabel) {
    var mins = parseTime12hToMinutes(timeLabel);
    var dt = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 0, 0, 0, 0);
    dt.setMinutes(mins);
    return dt;
  }

  function setSlotHidden(prefix, dateObj, fromM, durationMin) {
    document.getElementById(prefix + 'Date').value = formatYmdArtistLocal(dateObj);
    document.getElementById(prefix + 'From').value = minutesToHi(fromM);
    document.getElementById(prefix + 'To').value = minutesToHi(fromM + durationMin);
  }

  function updateConfirmBar() {
    var bar = document.getElementById('confirmBar');
    var btn = document.getElementById('btnConfirmTimes');
    var ok = false;
    var summary = '';
    if (consultationTiming === 'separate') {
      ok = ccConsultDate && ccConsultTime && ccTattooDate && ccTattooTime;
      if (ok) {
        summary = 'Consultation: ' + ccConsultDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccConsultTime
          + ' · Tattoo: ' + ccTattooDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccTattooTime;
        setSlotHidden('inputConsult', ccConsultDate, parseTime12hToMinutes(ccConsultTime), consultDurationMinutes);
        setSlotHidden('inputSession', ccTattooDate, parseTime12hToMinutes(ccTattooTime), tattooDurationMinutes);
      }
    } else {
      ok = ccConsultDate && ccConsultTime && ccTattooDate && ccTattooTime;
      if (ok) {
        summary = ccConsultDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }) + ' · ' + ccConsultTime + ' (consultation + session)';
        var startM = parseTime12hToMinutes(ccConsultTime);
        setSlotHidden('inputConsult', ccConsultDate, startM, consultDurationMinutes);
        setSlotHidden('inputSession', ccTattooDate, startM + consultDurationMinutes, tattooDurationMinutes);
      }
    }
    if (ok) {
      bar.classList.remove('hidden');
      document.getElementById('confirmSummary').textContent = summary;
    } else {
      bar.classList.add('hidden');
    }
    btn.disabled = !ok;
    btn.classList.toggle('opacity-60', !ok);
    btn.classList.toggle('cursor-not-allowed', !ok);
  }

  function renderCalendarInto(gridId, labelId, prevId, year, month, selectedDateObj, minDateObj, requiredMinutes, onPickDay) {
    var grid = document.getElementById(gridId);
    var label = document.getElementById(labelId);
    if (!grid || !label) return;
    grid.innerHTML = '';
    label.textContent = monthNames[month] + ' ' + year;
    if (prevId) {
      var prevBtn = document.getElementById(prevId);
      if (prevBtn) {
        var allowPrev = canNavigateToMonth(year, month - 1);
        prevBtn.disabled = !allowPrev;
        prevBtn.classList.toggle('opacity-40', !allowPrev);
        prevBtn.classList.toggle('cursor-not-allowed', !allowPrev);
      }
    }
    var firstDay = new Date(year, month, 1).getDay();
    var startOffset = (firstDay + 6) % 7;
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    var minDay = null;
    if (minDateObj instanceof Date) {
      minDay = new Date(minDateObj.getFullYear(), minDateObj.getMonth(), minDateObj.getDate(), 0, 0, 0, 0);
    }
    for (var i = 0; i < startOffset; i++) {
      var empty = document.createElement('div');
      empty.className = 'cal-day empty';
      grid.appendChild(empty);
    }
    for (var d = 1; d <= daysInMonth; d++) {
      (function(day) {
        var dt = new Date(year, month, day);
        var div = document.createElement('div');
        div.textContent = day;
        var ymdCell = formatYmdArtistLocal(dt);
        var isBlockedDay = !!(ymdCell && isArtistDateBlocked(ymdCell));
        var isBeforeMin = minDay && dt < minDay;
        var isAvail = getSlotsForDate(dt, requiredMinutes).length > 0;
        var isToday = dt.toDateString() === today.toDateString();
        var isFuture = dt > today;
        var isFullyBooked = !isBeforeMin && !isAvail && !isBlockedDay && (isFuture || isToday) && isDateFullyBookedOut(dt, requiredMinutes);
        var isSel = selectedDateObj && dt.toDateString() === selectedDateObj.toDateString();
        var cls = 'cal-day';
        if (isSel) cls += ' selected';
        else if (isAvail && !isBeforeMin) cls += ' available';
        else if (!isBeforeMin && isBlockedDay && (isFuture || isToday)) cls += ' blocked-by-artist';
        else if (isFullyBooked) cls += ' fully-booked-day';
        else if (isFuture || isToday) cls += ' unavailable-future';
        else cls += ' unavailable';
        if (isToday && !isSel) cls += ' today';
        div.className = cls;
        if (isAvail && !isBeforeMin) {
          div.addEventListener('click', function() { onPickDay(dt); });
        }
        grid.appendChild(div);
      })(d);
    }
  }

  function slotsIncludingSelected(dateObj, requiredMinutes, selectedTime) {
    var slots = getSlotsForDate(dateObj, requiredMinutes);
    if (!selectedTime) return slots;
    var selM = parseTime12hToMinutes(selectedTime);
    if (selM <= 0) return slots;
    var found = slots.some(function(s) { return parseTime12hToMinutes(s.time) === selM; });
    if (!found) {
      slots = [{ time: selectedTime, booked: false, restored: true }].concat(slots);
    }
    return slots;
  }

  function fillSlots(containerId, emptyId, contentId, labelId, dateObj, requiredMinutes, selectedTime, onPick, minDt) {
    var empty = document.getElementById(emptyId);
    var content = document.getElementById(contentId);
    var container = document.getElementById(containerId);
    if (!dateObj) {
      if (empty) empty.classList.remove('hidden');
      if (content) content.classList.add('hidden');
      return;
    }
    if (empty) empty.classList.add('hidden');
    if (content) content.classList.remove('hidden');
    if (labelId) document.getElementById(labelId).textContent = dateObj.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });
    var slots = slotsIncludingSelected(dateObj, requiredMinutes, selectedTime);
    container.innerHTML = '';
    slots.forEach(function(slot) {
      if (minDt) {
        var slotDt = buildDateTime(dateObj, slot.time);
        if (slotDt < minDt) return;
      }
      var isSelected = selectedTime && parseTime12hToMinutes(selectedTime) === parseTime12hToMinutes(slot.time);
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'time-slot-card' + (isSelected ? ' selected' : '');
      btn.textContent = slot.time;
      btn.addEventListener('click', function() { onPick(slot.time); });
      container.appendChild(btn);
    });
  }

  function showConsultTimeSlots() {
    if (!ccConsultDate) {
      document.getElementById('ccTimeSlotsEmpty').classList.remove('hidden');
      document.getElementById('ccTimeSlotsContent').classList.add('hidden');
      return;
    }
    fillSlots('ccTimeSlots', 'ccTimeSlotsEmpty', 'ccTimeSlotsContent', 'ccSelectedDateLabel', ccConsultDate, getConsultSelectionRequiredMinutes(), ccConsultTime, onConsultSlotPick, null);
  }

  function showTattooTimeSlots() {
    if (!ccTattooDate) {
      document.getElementById('ccTatTimeSlotsEmpty').classList.remove('hidden');
      document.getElementById('ccTatTimeSlotsContent').classList.add('hidden');
      return;
    }
    fillSlots('ccTatTimeSlots', 'ccTatTimeSlotsEmpty', 'ccTatTimeSlotsContent', 'ccTatSelectedDateLabel', ccTattooDate, tattooDurationMinutes, ccTattooTime, onTattooSlotPick, getTattooMinDateTime());
  }

  function getTattooMinDateTime() {
    if (!ccConsultDate || !ccConsultTime) return null;
    if (consultationTiming === 'separate') {
      var gapDays = Math.max(0, consultGapValue);
      var minDate = new Date(ccConsultDate.getFullYear(), ccConsultDate.getMonth(), ccConsultDate.getDate(), 0, 0, 0, 0);
      minDate.setDate(minDate.getDate() + gapDays + 1);
      return minDate;
    }
    return null;
  }

  function onConsultDaySelected(dt) {
    ccConsultDate = dt;
    ccConsultTime = null;
    ccTattooDate = null;
    ccTattooTime = null;
    document.getElementById('ccTattooSection').classList.add('hidden');
    document.getElementById('ccCombinedSummary').classList.add('hidden');
    document.getElementById('ccConsultChip').classList.add('hidden');
    document.getElementById('ccTattooChip').classList.add('hidden');
    syncConsultCalendar();
    updateConfirmBar();
  }

  function syncConsultCalendar() {
    renderCalendarInto('ccCalGrid', 'ccCalMonth', 'ccCalPrev', ccCalYear, ccCalMonth, ccConsultDate, null, getConsultSelectionRequiredMinutes(), onConsultDaySelected);
    showConsultTimeSlots();
  }

  function onConsultSlotPick(time) {
    ccConsultTime = time;
    document.getElementById('ccConsultChip').classList.remove('hidden');
    document.getElementById('ccConsultChipText').textContent = 'Consultation: ' + ccConsultDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + time;
    fillSlots('ccTimeSlots', 'ccTimeSlotsEmpty', 'ccTimeSlotsContent', 'ccSelectedDateLabel', ccConsultDate, getConsultSelectionRequiredMinutes(), ccConsultTime, onConsultSlotPick, null);
    if (consultationTiming === 'separate') {
      document.getElementById('ccTattooSection').classList.remove('hidden');
      ccTatCalYear = ccConsultDate.getFullYear();
      ccTatCalMonth = ccConsultDate.getMonth();
      var minDt = getTattooMinDateTime();
      if (minDt) {
        ccTatCalYear = minDt.getFullYear();
        ccTatCalMonth = minDt.getMonth();
      }
      syncTattooCalendar();
    } else {
      var consultStartDt = buildDateTime(ccConsultDate, ccConsultTime);
      var tattooStartDt = new Date(consultStartDt.getTime());
      tattooStartDt.setMinutes(tattooStartDt.getMinutes() + consultDurationMinutes);
      ccTattooDate = new Date(tattooStartDt.getFullYear(), tattooStartDt.getMonth(), tattooStartDt.getDate());
      ccTattooTime = formatTo12Hour(tattooStartDt.getHours(), tattooStartDt.getMinutes());
      document.getElementById('ccSumConsult').textContent = document.getElementById('ccConsultChipText').textContent;
      document.getElementById('ccSumTattoo').textContent = 'Tattoo session: ' + ccTattooDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccTattooTime;
      document.getElementById('ccCombinedSummary').classList.remove('hidden');
      updateConfirmBar();
    }
  }

  function onTattooDaySelected(dt) {
    ccTattooDate = dt;
    ccTattooTime = null;
    document.getElementById('ccTattooChip').classList.add('hidden');
    syncTattooCalendar();
    updateConfirmBar();
  }

  function syncTattooCalendar() {
    var minDt = getTattooMinDateTime();
    renderCalendarInto('ccTatCalGrid', 'ccTatCalMonth', 'ccTatCalPrev', ccTatCalYear, ccTatCalMonth, ccTattooDate, minDt, tattooDurationMinutes, onTattooDaySelected);
    showTattooTimeSlots();
  }

  function onTattooSlotPick(time) {
    ccTattooTime = time;
    document.getElementById('ccTattooChip').classList.remove('hidden');
    document.getElementById('ccTattooChipText').textContent = 'Tattoo session: ' + ccTattooDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + time;
    fillSlots('ccTatTimeSlots', 'ccTatTimeSlotsEmpty', 'ccTatTimeSlotsContent', 'ccTatSelectedDateLabel', ccTattooDate, tattooDurationMinutes, ccTattooTime, onTattooSlotPick, getTattooMinDateTime());
    updateConfirmBar();
  }

  function selectConsultType(card, type) {
    ccConsultType = type;
    document.getElementById('ccConsultTypeError').classList.add('hidden');
    document.querySelectorAll('#ccConsultTypeCards .consult-type-card').forEach(function(c) { c.classList.remove('selected'); });
    card.classList.add('selected');
    document.getElementById('ccConsultSection').classList.remove('hidden');
    if (consultationTiming === 'separate') {
      document.getElementById('ccTattooSection').classList.add('hidden');
    }
    syncConsultCalendar();
  }

  document.querySelectorAll('#ccConsultTypeCards .consult-type-card').forEach(function(card) {
    card.addEventListener('click', function() { selectConsultType(card, card.getAttribute('data-type')); });
  });
  var phoneCard = document.querySelector('#ccConsultTypeCards .consult-type-card[data-type="phone"]');
  if (phoneCard) phoneCard.classList.add('hidden');
  if (consultationSessionType === 'online') {
    document.querySelectorAll('#ccConsultTypeCards .consult-type-card[data-type="studio"]').forEach(function(c) { c.classList.add('hidden'); });
  } else if (consultationSessionType === 'physical') {
    document.querySelectorAll('#ccConsultTypeCards .consult-type-card[data-type="video"]').forEach(function(c) { c.classList.add('hidden'); });
  }
  var visible = document.querySelectorAll('#ccConsultTypeCards .consult-type-card:not(.hidden)');
  var hasSavedSelection = (SAVED_CONSULT && SAVED_CONSULT.date) || (SAVED && SAVED.date);
  if (visible.length === 1 && !hasSavedSelection) {
    selectConsultType(visible[0], visible[0].getAttribute('data-type'));
  }

  document.getElementById('ccCalPrev').addEventListener('click', function() {
    if (!canNavigateToMonth(ccCalYear, ccCalMonth - 1)) return;
    ccCalMonth--;
    if (ccCalMonth < 0) { ccCalMonth = 11; ccCalYear--; }
    syncConsultCalendar();
  });
  document.getElementById('ccCalNext').addEventListener('click', function() {
    ccCalMonth++;
    if (ccCalMonth > 11) { ccCalMonth = 0; ccCalYear++; }
    syncConsultCalendar();
  });
  document.getElementById('ccTatCalPrev').addEventListener('click', function() {
    if (!canNavigateToMonth(ccTatCalYear, ccTatCalMonth - 1)) return;
    ccTatCalMonth--;
    if (ccTatCalMonth < 0) { ccTatCalMonth = 11; ccTatCalYear--; }
    syncTattooCalendar();
  });
  document.getElementById('ccTatCalNext').addEventListener('click', function() {
    ccTatCalMonth++;
    if (ccTatCalMonth > 11) { ccTatCalMonth = 0; ccTatCalYear++; }
    syncTattooCalendar();
  });

  function restoreSaved() {
    if (!(SAVED_CONSULT && SAVED_CONSULT.date) && !(SAVED && SAVED.date)) return;

    var visibleTypeCards = document.querySelectorAll('#ccConsultTypeCards .consult-type-card:not(.hidden)');
    if (visibleTypeCards.length >= 1) {
      selectConsultType(visibleTypeCards[0], visibleTypeCards[0].getAttribute('data-type'));
    }

    if (SAVED_CONSULT && SAVED_CONSULT.date) {
      var cp = SAVED_CONSULT.date.split('-');
      ccConsultDate = new Date(parseInt(cp[0], 10), parseInt(cp[1], 10) - 1, parseInt(cp[2], 10));
      ccCalYear = ccConsultDate.getFullYear();
      ccCalMonth = ccConsultDate.getMonth();
      if (SAVED_CONSULT.from) {
        var cm = parseHiToMinutes(SAVED_CONSULT.from);
        ccConsultTime = formatTo12Hour(Math.floor(cm / 60), cm % 60);
      }
    }
    if (SAVED && SAVED.date) {
      var sp = SAVED.date.split('-');
      ccTattooDate = new Date(parseInt(sp[0], 10), parseInt(sp[1], 10) - 1, parseInt(sp[2], 10));
      ccTatCalYear = ccTattooDate.getFullYear();
      ccTatCalMonth = ccTattooDate.getMonth();
      if (SAVED.from) {
        var sm = parseHiToMinutes(SAVED.from);
        ccTattooTime = formatTo12Hour(Math.floor(sm / 60), sm % 60);
      }
    }
    if (!ccConsultDate) return;

    document.getElementById('ccConsultSection').classList.remove('hidden');
    if (ccConsultTime) {
      document.getElementById('ccConsultChip').classList.remove('hidden');
      document.getElementById('ccConsultChipText').textContent = 'Consultation: ' + ccConsultDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccConsultTime;
    }
    if (consultationTiming === 'separate') {
      if (ccConsultTime) document.getElementById('ccTattooSection').classList.remove('hidden');
      syncConsultCalendar();
      if (ccTattooDate) {
        syncTattooCalendar();
        if (ccTattooTime) {
          document.getElementById('ccTattooChip').classList.remove('hidden');
          document.getElementById('ccTattooChipText').textContent = 'Tattoo session: ' + ccTattooDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccTattooTime;
        }
      }
    } else if (ccConsultTime && ccTattooDate && ccTattooTime) {
      document.getElementById('ccSumConsult').textContent = document.getElementById('ccConsultChipText').textContent;
      document.getElementById('ccSumTattoo').textContent = 'Tattoo session: ' + ccTattooDate.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + ' at ' + ccTattooTime;
      document.getElementById('ccCombinedSummary').classList.remove('hidden');
      syncConsultCalendar();
    } else {
      syncConsultCalendar();
    }
    updateConfirmBar();
  }

  restoreSaved();
  window.__inkjinPrepareAutoConfirmForm = updateConfirmBar;
})();
</script>
