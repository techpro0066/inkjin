{{-- Weekly hours not saved: blocks client bookings until at least one slot exists. --}}
@php
  $ctx = $context ?? 'dashboard';
@endphp
@if(!empty($needsWeeklyAvailabilitySetup))
<div
  id="availabilitySetupRequiredModal"
  class="fixed inset-0 z-[220] flex items-center justify-center p-4 sm:p-6 bg-black/55 backdrop-blur-[2px]"
  role="dialog"
  aria-modal="true"
  aria-labelledby="availabilitySetupModalTitle"
  aria-describedby="availabilitySetupModalDesc"
>
  <div class="relative w-full max-w-md rounded-2xl bg-white border border-outline-variant/20 shadow-2xl shadow-primary/10 overflow-hidden">
    <button
      type="button"
      id="availabilitySetupModalCloseBtn"
      class="absolute top-3 right-3 z-10 p-2 rounded-xl text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors"
      aria-label="Close"
    >
      <span class="material-symbols-outlined text-[22px]" aria-hidden="true">close</span>
    </button>
    <div class="p-6 sm:p-8">
      <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-200/60 flex items-center justify-center mb-5">
        <span class="material-symbols-outlined text-amber-800 text-3xl" aria-hidden="true">event_available</span>
      </div>
      <h2 id="availabilitySetupModalTitle" class="text-xl font-bold text-on-surface tracking-tight">Set your weekly availability</h2>
      <p id="availabilitySetupModalDesc" class="text-sm text-on-surface-variant mt-3 leading-relaxed">
        You have not added any working hours yet. Until you save at least one weekly time range, clients cannot complete bookings with you — your booking calendar will stay empty.
      </p>
      @if($ctx === 'dashboard')
        <a
          href="{{ route('availability.index') }}"
          class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-primary/20 hover:opacity-95 transition-opacity text-sm"
        >
          <span class="material-symbols-outlined text-lg" aria-hidden="true">calendar_clock</span>
          Set my availability
        </a>
      @else
        <button
          type="button"
          id="availabilitySetupScrollToHoursBtn"
          class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-primary/20 hover:opacity-95 transition-opacity text-sm"
        >
          <span class="material-symbols-outlined text-lg" aria-hidden="true">edit_calendar</span>
          Add weekly hours
        </button>
      @endif
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    function hideAvailabilitySetupModal() {
      var m = document.getElementById('availabilitySetupRequiredModal');
      if (m) m.classList.add('hidden');
    }
    var modal = document.getElementById('availabilitySetupRequiredModal');
    if (modal) {
      modal.addEventListener('click', function (e) {
        if (e.target === modal) hideAvailabilitySetupModal();
      });
    }
    var closeBtn = document.getElementById('availabilitySetupModalCloseBtn');
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        hideAvailabilitySetupModal();
      });
    }
    var scrollBtn = document.getElementById('availabilitySetupScrollToHoursBtn');
    if (scrollBtn) {
      scrollBtn.addEventListener('click', function () {
        hideAvailabilitySetupModal();
        var el = document.getElementById('weeklyHoursSection');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
  });
</script>
@endif
