<div class="cal-card">
  <div class="flex flex-col md:flex-row">
    <div class="flex-1 p-6 border-b md:border-b-0 md:border-r border-outline-variant/20">
      <div class="flex items-center justify-between mb-5">
        <button type="button" id="calPrev" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors">
          <span class="material-symbols-outlined text-on-surface-variant">chevron_left</span>
        </button>
        <span class="font-bold text-base" id="calMonth"></span>
        <button type="button" id="calNext" class="p-1.5 rounded-lg hover:bg-surface-container transition-colors">
          <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
        </button>
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
      <div class="grid grid-cols-7 gap-1 justify-items-center" id="calGrid"></div>
    </div>
    <div class="md:w-[280px] p-6" id="timeSlotsPanel">
      <div id="timeSlotsEmpty" class="flex flex-col items-center justify-center h-full min-h-[200px] text-center">
        <span class="material-symbols-outlined text-outline-variant text-4xl mb-2">calendar_today</span>
        <p class="text-sm text-on-surface-variant">Select a date to see<br>available times</p>
      </div>
      <div id="timeSlotsContent" class="hidden">
        <h3 class="font-bold text-base mb-1" id="selectedDateLabel">—</h3>
        <p class="text-xs text-on-surface-variant mb-4">Choose a time slot</p>
        <div class="space-y-2 max-h-[320px] overflow-y-auto pr-1" id="timeSlots"></div>
        @if (!empty($artistTimezone))
          <p class="text-xs text-on-surface-variant mt-4 flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">public</span> {{ $artistTimezone }}
          </p>
        @endif
      </div>
    </div>
  </div>
</div>
