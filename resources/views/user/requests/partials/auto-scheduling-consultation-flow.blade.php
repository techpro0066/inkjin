<div class="bg-gradient-to-r from-primary/5 to-secondary-container/30 rounded-2xl border border-primary/10 p-5 mb-6">
  <div class="flex items-start gap-3">
    <span class="material-symbols-outlined text-primary text-2xl mt-0.5">video_camera_front</span>
    <div>
      <h3 class="text-base font-bold text-on-surface mb-1">{{ $artistName }} includes a consultation before your tattoo session</h3>
      <p class="text-sm text-on-surface-variant">You will have a {{ $consultDurationMinutes }}-minute consultation to discuss your custom design, placement, and any questions.</p>
    </div>
  </div>
</div>

<input type="hidden" name="client_consultation_slots[0][date]" id="inputConsultDate" value="{{ $savedConsultSelection['date'] ?? '' }}">
<input type="hidden" name="client_consultation_slots[0][ranges][0][from]" id="inputConsultFrom" value="{{ $savedConsultSelection['from'] ?? '' }}">
<input type="hidden" name="client_consultation_slots[0][ranges][0][to]" id="inputConsultTo" value="{{ $savedConsultSelection['to'] ?? '' }}">

<div class="mb-6" id="ccTypeSection">
  <h3 class="text-lg font-bold text-on-surface mb-1">How would you like to have your consultation?</h3>
  <p class="text-sm text-on-surface-variant mb-4">Choose the format that works best for you.</p>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="ccConsultTypeCards">
    <button type="button" class="consult-type-card text-left" data-type="video">
      <div class="ct-icon mb-3"><span class="material-symbols-outlined">videocam</span></div>
      <h4 class="font-bold text-sm text-on-surface mb-0.5">Video call</h4>
      <p class="text-xs text-on-surface-variant">{{ $consultDurationMinutes }}-minute call on Inkjin</p>
    </button>
    <button type="button" class="consult-type-card text-left" data-type="studio">
      <div class="ct-icon mb-3"><span class="material-symbols-outlined">storefront</span></div>
      <h4 class="font-bold text-sm text-on-surface mb-0.5">In-studio visit</h4>
      <p class="text-xs text-on-surface-variant">Visit {{ $studioName ?: 'the studio' }} in person</p>
      @if ($studioAddress ?? false)
        <p class="text-xs text-primary font-medium mt-1">{{ $studioAddress }}</p>
      @endif
    </button>
  </div>
  <p id="ccConsultTypeError" class="hidden text-sm text-error mt-3">Please select a consultation type before continuing.</p>
</div>

<div id="ccConsultSection" class="mb-6 hidden">
  <div class="flex items-center gap-2 mb-4">
    <span class="material-symbols-outlined text-primary">groups</span>
    <h3 class="text-lg font-bold text-on-surface">Schedule your consultation</h3>
  </div>
  <div class="cal-card mb-4">
    <div class="flex flex-col md:flex-row">
      <div class="flex-1 p-6 border-b md:border-b-0 md:border-r border-outline-variant/20">
        <div class="flex items-center justify-between mb-5">
          <button type="button" id="ccCalPrev" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors"><span class="material-symbols-outlined text-on-surface-variant">chevron_left</span></button>
          <span class="font-bold text-base" id="ccCalMonth"></span>
          <button type="button" id="ccCalNext" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors"><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center mb-2">
          <div class="text-xs font-semibold text-on-surface-variant py-1">Mon</div><div class="text-xs font-semibold text-on-surface-variant py-1">Tue</div><div class="text-xs font-semibold text-on-surface-variant py-1">Wed</div><div class="text-xs font-semibold text-on-surface-variant py-1">Thu</div><div class="text-xs font-semibold text-on-surface-variant py-1">Fri</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sat</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sun</div>
        </div>
        <div class="grid grid-cols-7 gap-1 justify-items-center" id="ccCalGrid"></div>
      </div>
      <div class="md:w-[280px] p-6">
        <div id="ccTimeSlotsEmpty" class="flex flex-col items-center justify-center min-h-[200px] text-center">
          <span class="material-symbols-outlined text-outline-variant text-4xl mb-2">calendar_today</span>
          <p class="text-sm text-on-surface-variant">Select a date to see available times</p>
        </div>
        <div id="ccTimeSlotsContent" class="hidden">
          <h3 class="font-bold text-base mb-1" id="ccSelectedDateLabel">?</h3>
          <p class="text-xs text-on-surface-variant mb-4">Choose a time slot</p>
          <div class="space-y-2 max-h-[300px] overflow-y-auto" id="ccTimeSlots"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="ccConsultChip" class="hidden mb-2"><div class="confirm-chip" id="ccConsultChipText">Consultation: ?</div></div>
</div>

<div id="ccTattooSection" class="mb-6 hidden">
  <div class="flex items-center gap-2 mb-4">
    <span class="material-symbols-outlined text-primary">brush</span>
    <h3 class="text-lg font-bold text-on-surface">Schedule your tattoo session</h3>
  </div>
  <div class="cal-card mb-4">
    <div class="flex flex-col md:flex-row">
      <div class="flex-1 p-6 border-b md:border-b-0 md:border-r border-outline-variant/20">
        <div class="flex items-center justify-between mb-5">
          <button type="button" id="ccTatCalPrev" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors"><span class="material-symbols-outlined text-on-surface-variant">chevron_left</span></button>
          <span class="font-bold text-base" id="ccTatCalMonth"></span>
          <button type="button" id="ccTatCalNext" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors"><span class="material-symbols-outlined text-on-surface-variant">chevron_right</span></button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center mb-2">
          <div class="text-xs font-semibold text-on-surface-variant py-1">Mon</div><div class="text-xs font-semibold text-on-surface-variant py-1">Tue</div><div class="text-xs font-semibold text-on-surface-variant py-1">Wed</div><div class="text-xs font-semibold text-on-surface-variant py-1">Thu</div><div class="text-xs font-semibold text-on-surface-variant py-1">Fri</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sat</div><div class="text-xs font-semibold text-on-surface-variant py-1">Sun</div>
        </div>
        <div class="grid grid-cols-7 gap-1 justify-items-center" id="ccTatCalGrid"></div>
      </div>
      <div class="md:w-[280px] p-6">
        <div id="ccTatTimeSlotsEmpty" class="flex flex-col items-center justify-center min-h-[200px] text-center">
          <span class="material-symbols-outlined text-outline-variant text-4xl mb-2">calendar_today</span>
          <p class="text-sm text-on-surface-variant">Select a date to see available times</p>
        </div>
        <div id="ccTatTimeSlotsContent" class="hidden">
          <h3 class="font-bold text-base mb-1" id="ccTatSelectedDateLabel">?</h3>
          <p class="text-xs text-on-surface-variant mb-4">Choose a time slot</p>
          <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1" id="ccTatTimeSlots"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="ccTattooChip" class="hidden mb-2"><div class="confirm-chip" id="ccTattooChipText">Tattoo session: ?</div></div>
</div>

<div id="ccCombinedSummary" class="hidden mb-4 bg-white rounded-2xl border border-primary/20 p-4 space-y-2">
  <p class="text-sm font-semibold" id="ccSumConsult">Consultation: ?</p>
  <p class="text-sm font-semibold" id="ccSumTattoo">Tattoo session: ?</p>
</div>
