{{-- $kind: consult|session --}}
<div class="picker-card">
  <div class="picker-split">
    <div class="picker-dates-col">
      <p class="picker-step-label">Choose a date</p>
      <div class="offered-dates-list" data-offered-dates="{{ $kind }}"></div>
      <p class="text-sm text-on-surface-variant hidden" data-no-dates="{{ $kind }}">No dates offered yet.</p>
    </div>

    <div class="picker-times-col">
      <p class="picker-step-label">Choose a time</p>
      <div data-time-empty="{{ $kind }}" class="picker-times-empty">
        <span class="material-symbols-outlined text-outline-variant text-3xl mb-1">schedule</span>
        <p class="text-sm picker-times-empty-text">Select a date to see available times</p>
      </div>
      <div data-time-content="{{ $kind }}" class="hidden picker-times-filled">
        <p class="text-sm font-semibold text-on-surface mb-2 shrink-0" data-selected-date-label="{{ $kind }}">—</p>
        <div class="picker-times-scroll space-y-2" data-time-slots="{{ $kind }}"></div>
      </div>
    </div>
  </div>
</div>
<div class="mt-3 hidden" data-selection-chip="{{ $kind }}">
  <span class="confirm-chip"><span class="material-symbols-outlined text-sm">check_circle</span> <span data-chip-text="{{ $kind }}">—</span></span>
</div>
