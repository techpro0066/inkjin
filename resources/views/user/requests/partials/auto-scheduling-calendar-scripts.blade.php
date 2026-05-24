<script>
(function() {
  var CAL = @json($calendarPayload);
  var SAVED = @json($savedSelection);
  var artistAvailabilitySchedule = CAL.artistAvailabilitySchedule || {};
  var artistTimezone = CAL.artistTimezone || 'UTC';
  var artistBlockedPeriods = CAL.artistBlockedPeriods || [];
  var artistBusyIntervalsByDate = CAL.artistBusyIntervalsByDate || {};
  var tattooDurationMinutes = parseInt(CAL.tattooDurationMinutes || @json($durationMinutes), 10) || 120;
  var weekdayKeys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
  var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  var today = new Date();
  var todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0);
  var calYear = today.getFullYear();
  var calMonth = today.getMonth();
  var selectedDate = null;
  var selectedTime = null;

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
      slots = slots.filter(function(slot) {
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

  function canNavigateToMonth(year, month) {
    var firstOfTarget = new Date(year, month, 1, 0, 0, 0, 0);
    var firstOfCurrent = new Date(todayStart.getFullYear(), todayStart.getMonth(), 1, 0, 0, 0, 0);
    return firstOfTarget >= firstOfCurrent;
  }

  function updateConfirmBar() {
    var bar = document.getElementById('confirmBar');
    var btn = document.getElementById('btnConfirmTimes');
    var ok = selectedDate && selectedTime;
    if (ok) {
      bar.classList.remove('hidden');
      var ymd = formatYmdArtistLocal(selectedDate);
      var startM = parseTime12hToMinutes(selectedTime);
      document.getElementById('confirmSummary').textContent = selectedDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }) + ' · ' + selectedTime;
      document.getElementById('inputSessionDate').value = ymd;
      document.getElementById('inputSessionFrom').value = minutesToHi(startM);
      document.getElementById('inputSessionTo').value = minutesToHi(startM + tattooDurationMinutes);
    } else {
      bar.classList.add('hidden');
    }
    btn.disabled = !ok;
    btn.classList.toggle('opacity-60', !ok);
    btn.classList.toggle('cursor-not-allowed', !ok);
  }

  function slotsIncludingSelected(dateObj, requiredMinutes, timeLabel) {
    var slots = getSlotsForDate(dateObj, requiredMinutes);
    if (!timeLabel) return slots;
    var selM = parseTime12hToMinutes(timeLabel);
    if (selM <= 0) return slots;
    if (!slots.some(function(s) { return parseTime12hToMinutes(s.time) === selM; })) {
      slots = [{ time: timeLabel, booked: false, restored: true }].concat(slots);
    }
    return slots;
  }

  function showMainTimeSlots() {
    if (!selectedDate) {
      document.getElementById('timeSlotsEmpty').classList.remove('hidden');
      document.getElementById('timeSlotsContent').classList.add('hidden');
      return;
    }
    document.getElementById('timeSlotsEmpty').classList.add('hidden');
    document.getElementById('timeSlotsContent').classList.remove('hidden');
    document.getElementById('selectedDateLabel').textContent = selectedDate.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });
    var slots = slotsIncludingSelected(selectedDate, tattooDurationMinutes, selectedTime);
    var html = '';
    slots.forEach(function(slot) {
      var isSelected = selectedTime && parseTime12hToMinutes(selectedTime) === parseTime12hToMinutes(slot.time);
      var selectedClass = isSelected ? ' selected' : '';
      html += '<button type="button" class="time-slot-card' + selectedClass + '" data-time="' + slot.time + '">' + slot.time + '</button>';
    });
    var container = document.getElementById('timeSlots');
    container.innerHTML = html;
    container.querySelectorAll('.time-slot-card').forEach(function(btn) {
      btn.addEventListener('click', function() {
        container.querySelectorAll('.time-slot-card').forEach(function(b) { b.classList.remove('selected'); });
        btn.classList.add('selected');
        selectedTime = btn.getAttribute('data-time');
        updateConfirmBar();
      });
    });
    updateConfirmBar();
  }

  function renderMainCal() {
    var grid = document.getElementById('calGrid');
    var label = document.getElementById('calMonth');
    if (!grid || !label) return;
    grid.innerHTML = '';
    label.textContent = monthNames[calMonth] + ' ' + calYear;
    var prevBtn = document.getElementById('calPrev');
    if (prevBtn) {
      var allowPrev = canNavigateToMonth(calYear, calMonth - 1);
      prevBtn.disabled = !allowPrev;
      prevBtn.classList.toggle('opacity-40', !allowPrev);
      prevBtn.classList.toggle('cursor-not-allowed', !allowPrev);
    }
    var firstDay = new Date(calYear, calMonth, 1).getDay();
    var startOffset = (firstDay + 6) % 7;
    var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    for (var i = 0; i < startOffset; i++) {
      var empty = document.createElement('div');
      empty.className = 'cal-day empty';
      grid.appendChild(empty);
    }
    for (var d = 1; d <= daysInMonth; d++) {
      (function(day) {
        var dt = new Date(calYear, calMonth, day);
        var div = document.createElement('div');
        div.textContent = day;
        var ymdCell = formatYmdArtistLocal(dt);
        var isBlockedDay = !!(ymdCell && isArtistDateBlocked(ymdCell));
        var isAvail = getSlotsForDate(dt, tattooDurationMinutes).length > 0;
        var isToday = dt.toDateString() === today.toDateString();
        var isFuture = dt > today;
        var isFullyBooked = !isAvail && !isBlockedDay && (isFuture || isToday) && isDateFullyBookedOut(dt, tattooDurationMinutes);
        var isSel = selectedDate && dt.toDateString() === selectedDate.toDateString();
        var cls = 'cal-day';
        if (isSel) cls += ' selected';
        else if (isAvail) cls += ' available';
        else if (isBlockedDay && (isFuture || isToday)) {
          cls += ' blocked-by-artist';
          div.title = 'Artist unavailable';
        } else if (isFullyBooked) {
          cls += ' fully-booked-day';
          div.title = 'Fully booked';
        } else if (isFuture || isToday) cls += ' unavailable-future';
        else cls += ' unavailable';
        if (isToday && !isSel) cls += ' today';
        div.className = cls;
        if (isAvail) {
          div.addEventListener('click', function() {
            selectedDate = dt;
            selectedTime = null;
            renderMainCal();
            showMainTimeSlots();
          });
        }
        grid.appendChild(div);
      })(d);
    }
  }

  function restoreSavedSelection() {
    if (!SAVED || !SAVED.date || !SAVED.from) return;
    var parts = SAVED.date.split('-');
    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10) - 1;
    var d = parseInt(parts[2], 10);
    calYear = y;
    calMonth = m;
    selectedDate = new Date(y, m, d);
    var savedFromM = parseHiToMinutes(SAVED.from);
    selectedTime = formatTo12Hour(Math.floor(savedFromM / 60), savedFromM % 60);
    renderMainCal();
    showMainTimeSlots();
    updateConfirmBar();
  }

  document.getElementById('calPrev').addEventListener('click', function() {
    if (!canNavigateToMonth(calYear, calMonth - 1)) return;
    calMonth--;
    if (calMonth < 0) { calMonth = 11; calYear--; }
    renderMainCal();
  });
  document.getElementById('calNext').addEventListener('click', function() {
    calMonth++;
    if (calMonth > 11) { calMonth = 0; calYear++; }
    renderMainCal();
  });

  renderMainCal();
  restoreSavedSelection();
  window.__inkjinPrepareAutoConfirmForm = updateConfirmBar;
})();
</script>
