@extends('layouts.dashboard_layout')

@section('title', 'Availability')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-calendar.css') }}" />
<style>
  .availability-day-card {
    transition: all 0.3s ease;
  }
  
  .availability-day-card:hover {
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
  }
  
  .availability-day-card.disabled {
    opacity: 0.6;
    background-color: #f5f5f5;
  }
  
  .time-slot-item {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    position: relative;
  }
  
  .time-slot-item:last-child {
    margin-bottom: 0;
  }
  
  .time-slot-item.has-overlap {
    border-color: #ff6b6b;
    background-color: #fff5f5;
  }
  
  .overlap-error {
    color: #ff6b6b;
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }
  
  .day-label {
    font-weight: 600;
    font-size: 1.1rem;
  }
  
  .empty-state {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
  }
  
  .empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Header -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h4 class="fw-bold py-3 mb-1">
            <span class="text-muted fw-light">Settings /</span> Availability
          </h4>
          <p class="text-muted mb-0">Manage your weekly availability schedule</p>
        </div>
        <div>
          <button type="button" class="btn btn-primary" id="saveAvailabilityBtn">
            <i class="ti ti-device-floppy me-2"></i>
            Save Availability
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- / Header -->

  <!-- Success/Error Alerts -->
  <div id="alertContainer"></div>

  <!-- Availability Cards -->
  <div class="row g-4">
    @php
      $days = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday'
      ];
      $availabilityByDay = $availabilityByDay ?? [];
      $userTimezone = $userTimezone ?? 'UTC';
    @endphp

    @foreach($days as $dayKey => $dayName)
    <div class="col-12">
      <div class="card availability-day-card" data-day="{{ $dayKey }}">
        <div class="card-body">
          <div class="row align-items-center">
            <!-- Day Toggle -->
            <div class="col-md-3 mb-3 mb-md-0">
              <div class="form-check form-switch form-check-inline">
                <input 
                  class="form-check-input day-toggle" 
                  type="checkbox" 
                  id="day_{{ $dayKey }}" 
                  data-day="{{ $dayKey }}"
                  {{ isset($availabilityByDay[$dayKey]) && count($availabilityByDay[$dayKey]) > 0 ? 'checked' : '' }}>
                <label class="form-check-label day-label" for="day_{{ $dayKey }}">
                  {{ $dayName }}
                </label>
              </div>
            </div>

            <!-- Time Slots Container -->
            <div class="col-md-9">
              <div class="time-slots-container" id="slots_{{ $dayKey }}" data-day="{{ $dayKey }}">
                <!-- Time slots will be dynamically added here -->
                <div class="empty-state" style="display: none;">
                  <i class="ti ti-clock-hour-4"></i>
                  <p class="mb-0">No time slots added</p>
                </div>
              </div>

              <!-- Add Time Slot Button -->
              <div class="mt-3">
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-primary add-time-slot-btn" 
                  data-day="{{ $dayKey }}"
                  disabled
                  style="display: none;">
                  <i class="ti ti-plus me-1"></i>
                  Add Time Slot
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  <!-- / Availability Cards -->

  <!-- Date Overrides Section -->
  <div class="row mt-5">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="ti ti-calendar-event me-2"></i>
            Date-Specific Overrides
          </h5>
          <p class="text-muted mb-0 mt-1">Override your recurring availability for specific dates</p>
        </div>
        <div class="card-body">
          <div class="row mb-4">
            <div class="col-12">
              <button type="button" class="btn btn-primary" id="addOverrideBtn">
                <i class="ti ti-plus me-2"></i>
                Add Date Override
              </button>
            </div>
          </div>

          <!-- Existing Overrides List -->
          <div id="overridesList" class="row g-3">
            @if(isset($overridesByDate) && !empty($overridesByDate))
              @foreach($overridesByDate as $date => $override)
                <div class="col-md-6 col-lg-4 override-item" data-date="{{ $date }}" data-override-id="{{ $override['id'] }}">
                  <div class="card border">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                          <h6 class="mb-1">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</h6>
                          <p class="mb-0">
                            @if($override['is_unavailable'])
                              <span class="badge bg-danger">Unavailable</span>
                            @elseif(isset($override['start_time']) && isset($override['end_time']))
                              <span class="badge bg-success">{{ $override['start_time'] }} - {{ $override['end_time'] }}</span>
                            @else
                              <span class="badge bg-secondary">No time set</span>
                            @endif
                          </p>
                          @if(!empty($override['notes']))
                            <small class="text-muted d-block mt-1">{{ $override['notes'] }}</small>
                          @endif
                        </div>
                        <button type="button" class="btn btn-sm btn-label-danger remove-override-btn" data-override-id="{{ $override['id'] }}">
                          <i class="ti ti-trash"></i>
                        </button>
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-primary edit-override-btn" data-date="{{ $date }}" data-override-id="{{ $override['id'] }}">
                        <i class="ti ti-edit me-1"></i>
                        Edit
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
            @else
              <div class="col-12">
                <div class="text-center py-4">
                  <i class="ti ti-calendar-off ti-3x text-muted mb-3"></i>
                  <p class="text-muted">No date overrides set. Click "Add Date Override" to create one.</p>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- / Date Overrides Section -->
</div>

<!-- Date Override Modal -->
<div class="modal fade" id="overrideModal" tabindex="-1" aria-labelledby="overrideModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="overrideModalLabel">Add Date Override</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="overrideForm">
          @csrf
          <input type="hidden" id="override_id" name="override_id">
          
          <div class="mb-3">
            <label for="override_date" class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="override_date" name="override_date" required min="{{ date('Y-m-d') }}">
            <p class="text-danger mt-1 mb-0" id="override_date_error" style="display: none; font-size: 0.875rem;"></p>
          </div>

          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_unavailable" name="is_unavailable" value="1">
              <label class="form-check-label" for="is_unavailable">
                Mark as unavailable for this date
              </label>
            </div>
          </div>

          <div id="timeFields">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="override_start_time" class="form-label">Start Time</label>
                <input type="text" class="form-control flatpickr-time" id="override_start_time" name="start_time" placeholder="09:00" readonly>
                <p class="text-danger mt-1 mb-0" id="start_time_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              <div class="col-md-6">
                <label for="override_end_time" class="form-label">End Time</label>
                <input type="text" class="form-control flatpickr-time" id="override_end_time" name="end_time" placeholder="17:00" readonly>
                <p class="text-danger mt-1 mb-0" id="end_time_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label for="override_notes" class="form-label">Notes (Optional)</label>
            <textarea class="form-control" id="override_notes" name="notes" rows="2" placeholder="Add any notes about this override..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveOverrideBtn">Save Override</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Override Confirmation Modal -->
<div class="modal fade" id="deleteOverrideModal" tabindex="-1" aria-labelledby="deleteOverrideModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteOverrideModalLabel">
          <i class="ti ti-alert-triangle text-warning me-2"></i>
          Delete Date Override
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this date override? This action cannot be undone.</p>
        <div id="deleteOverrideInfo" class="mt-3 p-3 bg-light rounded">
          <strong>Date:</strong> <span id="deleteOverrideDate"></span><br>
          <strong>Status:</strong> <span id="deleteOverrideStatus"></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteOverrideBtn">
          <i class="ti ti-trash me-2"></i>
          Delete Override
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Time Slot Template (Hidden) -->
<template id="timeSlotTemplate">
  <div class="time-slot-item" data-slot-index="" data-availability-id="">
    <div class="row g-2 align-items-center">
      <div class="col-md-4">
        <label class="form-label small mb-1">From</label>
        <input 
          type="text" 
          class="form-control form-control-sm time-from flatpickr-time" 
          placeholder="09:00"
          readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label small mb-1">To</label>
        <input 
          type="text" 
          class="form-control form-control-sm time-to flatpickr-time" 
          placeholder="17:00"
          readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label small mb-1 d-block">&nbsp;</label>
        <button 
          type="button" 
          class="btn btn-sm btn-label-danger remove-time-slot-btn">
          <i class="ti ti-trash me-1"></i>
          Remove
        </button>
      </div>
    </div>
    <div class="overlap-error" style="display: none;"></div>
  </div>
</template>

<!-- Hidden input for user timezone -->
<input type="hidden" id="userTimezone" value="{{ $userTimezone }}">
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script>
  // Flatpickr configuration
  if (typeof flatpickr !== 'undefined') {
    // Global flatpickr config is ready
  }
</script>
<script>
  // Initialize availability data structure
  let availabilityData = {
    monday: [],
    tuesday: [],
    wednesday: [],
    thursday: [],
    friday: [],
    saturday: [],
    sunday: []
  };

  // Flatpickr instances for time inputs
  let timePickers = {};

  // Initialize page
  $(document).ready(function() {
    // Load existing availability data from database
    @if(isset($availabilityByDay))
      @foreach($availabilityByDay as $day => $slots)
        @foreach($slots as $slot)
          addTimeSlot('{{ $day }}', '{{ $slot['start_time'] }}', '{{ $slot['end_time'] }}', {{ $slot['id'] }});
        @endforeach
      @endforeach
    @endif
    
    initializeDayToggles();
    initializeTimeSlots();
    initializeFlatpickr();
    
    // Add initial time slot for enabled days that have no slots
    // Also update button states for enabled days
    $('.day-toggle:checked').each(function() {
      const day = $(this).data('day');
      const slotsContainer = $(`#slots_${day}`);
      const addBtn = $(`.add-time-slot-btn[data-day="${day}"]`);
      addBtn.show();
      
      // Always enable the button so user can add slots
      addBtn.prop('disabled', false);
      
      if (slotsContainer.find('.time-slot-item').length === 0) {
        addTimeSlot(day);
      }
    });
  });

  // Initialize day toggles
  function initializeDayToggles() {
    $('.day-toggle').on('change', function() {
      const day = $(this).data('day');
      const isEnabled = $(this).is(':checked');
      const card = $(this).closest('.availability-day-card');
      const slotsContainer = $(`#slots_${day}`);
      const addBtn = $(`.add-time-slot-btn[data-day="${day}"]`);
      const emptyState = slotsContainer.find('.empty-state');

      if (isEnabled) {
        card.removeClass('disabled');
        addBtn.show();
        
        // Enable button if no slots exist (so user can add first slot)
        const slotCount = slotsContainer.find('.time-slot-item').length;
        if (slotCount === 0) {
          addBtn.prop('disabled', false);
          // Add a time slot if none exist
          addTimeSlot(day);
        } else {
          // Enable button if slots exist
          addBtn.prop('disabled', false);
        }
      } else {
        card.addClass('disabled');
        addBtn.hide().prop('disabled', true);
        slotsContainer.find('.time-slot-item').remove();
        availabilityData[day] = [];
      }

      checkOverlaps(day);
    });
  }

  // Initialize time slots
  function initializeTimeSlots() {
    // Add time slot button
    $(document).on('click', '.add-time-slot-btn', function() {
      const day = $(this).data('day');
      addTimeSlot(day);
    });

    // Remove time slot button
    $(document).on('click', '.remove-time-slot-btn', function() {
      const slotItem = $(this).closest('.time-slot-item');
      const day = slotItem.closest('.time-slots-container').data('day');
      const slotIndex = slotItem.data('slot-index');
      const availabilityId = slotItem.data('availability-id');
      
      // If this slot exists in database, delete it via AJAX
      if (availabilityId) {
        $.ajax({
          url: `{{ url('availability') }}/${availabilityId}`,
          method: 'DELETE',
          data: {
            _token: '{{ csrf_token() }}'
          },
          success: function(response) {
            // Slot deleted from database
          },
          error: function(xhr) {
            console.error('Failed to delete availability:', xhr);
          }
        });
      }
      
      // Remove from data
      if (slotIndex !== undefined && availabilityData[day][slotIndex]) {
        availabilityData[day].splice(slotIndex, 1);
      }
      
      // Remove picker instance
      const fromInput = slotItem.find('.time-from');
      const toInput = slotItem.find('.time-to');
      if (timePickers[fromInput.attr('id')]) {
        timePickers[fromInput.attr('id')].destroy();
        delete timePickers[fromInput.attr('id')];
      }
      if (timePickers[toInput.attr('id')]) {
        timePickers[toInput.attr('id')].destroy();
        delete timePickers[toInput.attr('id')];
      }
      
      slotItem.remove();
      updateSlotIndices(day);
      checkOverlaps(day);
      updateEmptyState(day);
      
      // Update add button state - enable if no slots remain (so user can add new one)
      const slotsContainer = $(`#slots_${day}`);
      const addBtn = $(`.add-time-slot-btn[data-day="${day}"]`);
      const slotCount = slotsContainer.find('.time-slot-item').length;
      if (slotCount === 0) {
        addBtn.prop('disabled', false);
      } else {
        addBtn.prop('disabled', false);
      }
    });

    // Time change handlers
    $(document).on('change', '.time-from, .time-to', function() {
      const day = $(this).closest('.time-slots-container').data('day');
      updateAvailabilityData(day);
      checkOverlaps(day);
    });
  }

  // Add time slot
  function addTimeSlot(day, fromTime = null, toTime = null, availabilityId = null) {
    const template = document.getElementById('timeSlotTemplate');
    const slotClone = $(template.content.cloneNode(true));
    const slotsContainer = $(`#slots_${day}`);
    const slotIndex = slotsContainer.find('.time-slot-item').length;
    
    slotClone.find('.time-slot-item').attr('data-slot-index', slotIndex);
    if (availabilityId) {
      slotClone.find('.time-slot-item').attr('data-availability-id', availabilityId);
    }
    
    // Generate unique IDs for inputs
    const uniqueId = `time_${day}_${slotIndex}_${Date.now()}`;
    const fromInput = slotClone.find('.time-from');
    const toInput = slotClone.find('.time-to');
    
    fromInput.attr('id', `${uniqueId}_from`);
    toInput.attr('id', `${uniqueId}_to`);
    
    slotsContainer.append(slotClone);
    
    // Set times (from database or default) before initializing flatpickr
    if (fromTime && toTime) {
      fromInput.val(fromTime);
      toInput.val(toTime);
    } else if (slotIndex === 0) {
      // Set default time if it's the first slot
      fromInput.val('09:00');
      toInput.val('17:00');
    } else {
      // For additional slots, use the last slot's "to" time as the new "from" time
      const previousSlots = slotsContainer.find('.time-slot-item');
      if (previousSlots.length > 0) {
        const lastToTime = $(previousSlots[previousSlots.length - 1]).find('.time-to').val();
        if (lastToTime) {
          fromInput.val(lastToTime);
          toInput.val(lastToTime);
        } else {
          fromInput.val('09:00');
          toInput.val('17:00');
        }
      }
    }
    
    // Initialize flatpickr for new inputs after setting values
    setTimeout(() => {
      initializeTimePicker(fromInput.attr('id'));
      initializeTimePicker(toInput.attr('id'));
      updateAvailabilityData(day);
    }, 50);
    
    updateSlotIndices(day);
    updateEmptyState(day);
    checkOverlaps(day);
    
    // Show and enable add button if day is enabled
    const dayToggle = $(`#day_${day}`);
    if (dayToggle.is(':checked')) {
      const addBtn = $(`.add-time-slot-btn[data-day="${day}"]`);
      addBtn.show();
      // Always enable button so user can add slots (even when no slots exist)
      addBtn.prop('disabled', false);
    }
  }

  // Initialize Flatpickr
  function initializeFlatpickr() {
    $('.flatpickr-time').each(function() {
      if (!$(this).attr('id')) return;
      initializeTimePicker($(this).attr('id'));
    });
  }

  // Initialize time picker
  function initializeTimePicker(inputId) {
    const input = $(`#${inputId}`);
    if (input.length && !timePickers[inputId]) {
      const slotItem = input.closest('.time-slot-item');
      const isFromInput = input.hasClass('time-from');
      const otherInput = isFromInput ? slotItem.find('.time-to') : slotItem.find('.time-from');
      
      // Get current value or default
      let currentValue = input.val();
      let defaultTime = currentValue || '09:00';
      
      timePickers[inputId] = flatpickr(input[0], {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minuteIncrement: 15,
        defaultDate: defaultTime,
        onChange: function(selectedDates, dateStr, instance) {
          const day = input.closest('.time-slots-container').data('day');
          updateAvailabilityData(day);
          checkOverlaps(day);
        }
      });
      
      // When clicking on the field, if it's empty and other field has a value, use that value
      input.on('focus', function() {
        const otherValue = otherInput.val();
        if (!input.val() && otherValue) {
          // Set the flatpickr value to the other field's value
          timePickers[inputId].setDate(otherValue, false);
          input.val(otherValue);
          const day = input.closest('.time-slots-container').data('day');
          updateAvailabilityData(day);
          checkOverlaps(day);
        }
      });
    }
  }

  // Update availability data
  function updateAvailabilityData(day) {
    availabilityData[day] = [];
    $(`#slots_${day} .time-slot-item`).each(function() {
      const from = $(this).find('.time-from').val();
      const to = $(this).find('.time-to').val();
      
      if (from && to) {
        availabilityData[day].push({
          from: from,
          to: to
        });
      }
    });
  }

  // Update slot indices
  function updateSlotIndices(day) {
    $(`#slots_${day} .time-slot-item`).each(function(index) {
      $(this).attr('data-slot-index', index);
    });
  }

  // Update empty state
  function updateEmptyState(day) {
    const slotsContainer = $(`#slots_${day}`);
    const emptyState = slotsContainer.find('.empty-state');
    const hasSlots = slotsContainer.find('.time-slot-item').length > 0;
    
    if (hasSlots) {
      emptyState.hide();
    } else {
      emptyState.show();
    }
  }

  // Check for overlaps
  function checkOverlaps(day) {
    const slots = availabilityData[day];
    const slotsContainer = $(`#slots_${day}`);
    
    // Reset all slots
    slotsContainer.find('.time-slot-item').removeClass('has-overlap').find('.overlap-error').hide().text('');
    
    // Check each slot against others
    for (let i = 0; i < slots.length; i++) {
      for (let j = i + 1; j < slots.length; j++) {
        if (isOverlapping(slots[i], slots[j])) {
          const slotItems = slotsContainer.find('.time-slot-item');
          $(slotItems[i]).addClass('has-overlap');
          $(slotItems[j]).addClass('has-overlap');
          
          $(slotItems[i]).find('.overlap-error').text('Time slot overlaps with another').show();
          $(slotItems[j]).find('.overlap-error').text('Time slot overlaps with another').show();
        }
      }
    }
  }

  // Check if two time slots overlap
  function isOverlapping(slot1, slot2) {
    const time1From = timeToMinutes(slot1.from);
    const time1To = timeToMinutes(slot1.to);
    const time2From = timeToMinutes(slot2.from);
    const time2To = timeToMinutes(slot2.to);
    
    // Check if slot1 starts before slot2 ends and slot1 ends after slot2 starts
    return time1From < time2To && time1To > time2From;
  }

  // Convert time string (HH:mm) to minutes
  function timeToMinutes(timeStr) {
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
  }

  // Save availability
  $('#saveAvailabilityBtn').on('click', function() {
    const btn = $(this);
    const originalHtml = btn.html();
    
    // Validate (allow empty slots, but validate time ranges if slots exist)
    let hasErrors = false;
    let hasOverlaps = false;
    
    Object.keys(availabilityData).forEach(day => {
      const isEnabled = $(`#day_${day}`).is(':checked');
      if (isEnabled) {
        updateAvailabilityData(day);
        checkOverlaps(day);
        
        // Check for overlaps (only if slots exist)
        if ($(`#slots_${day} .time-slot-item.has-overlap`).length > 0) {
          hasOverlaps = true;
        }
        
        // Validate each slot (only if slots exist)
        availabilityData[day].forEach((slot, index) => {
          if (!slot.from || !slot.to) {
            hasErrors = true;
          }
          
          if (slot.from && slot.to && timeToMinutes(slot.from) >= timeToMinutes(slot.to)) {
            hasErrors = true;
            showAlert('warning', `${day.charAt(0).toUpperCase() + day.slice(1)}: Time slot ${index + 1} has invalid time range (From time must be before To time).`);
          }
        });
      }
    });
    
    if (hasOverlaps) {
      showAlert('danger', 'Please resolve all overlapping time slots before saving.');
      return;
    }
    
    if (hasErrors) {
      return;
    }
    
    // Prepare data
    const dataToSave = {};
    Object.keys(availabilityData).forEach(day => {
      const isEnabled = $(`#day_${day}`).is(':checked');
      if (isEnabled && availabilityData[day].length > 0) {
        dataToSave[day] = availabilityData[day];
      }
    });
    
    // Show loading
    btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...').prop('disabled', true);
    
    // AJAX call to save availability
    $.ajax({
      url: '{{ route("availability.store") }}',
      method: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        availability: dataToSave
      },
      success: function(response) {
        showAlert('success', response.message || 'Availability saved successfully!');
        btn.html(originalHtml).prop('disabled', false);
        
        // Reload page after 1 second to show updated data
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      },
      error: function(xhr) {
        const errorMessage = xhr.responseJSON?.message || 'Failed to save availability. Please try again.';
        showAlert('danger', errorMessage);
        btn.html(originalHtml).prop('disabled', false);
      }
    });
  });

  // Show alert
  function showAlert(type, message) {
    const alertHtml = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        <i class="ti ti-${type === 'success' ? 'check-circle' : type === 'danger' ? 'alert-circle' : 'alert-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    
    $('#alertContainer').html(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
      $('#alertContainer .alert').alert('close');
    }, 5000);
  }

  // ========== Date Override Functionality ==========
  let overrideTimePickers = {};

  // Initialize override modal
  function initializeOverrideModal() {
    // Initialize time pickers for override modal
    if (typeof flatpickr !== 'undefined') {
      overrideTimePickers.start = flatpickr('#override_start_time', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
      });

      overrideTimePickers.end = flatpickr('#override_end_time', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
      });
    }

    // Toggle time fields based on unavailable checkbox
    $('#is_unavailable').on('change', function() {
      const isUnavailable = $(this).is(':checked');
      $('#timeFields').toggle(!isUnavailable);
      if (isUnavailable) {
        $('#override_start_time').val('');
        $('#override_end_time').val('');
      }
    });
  }

  // Open override modal for adding
  $('#addOverrideBtn').on('click', function() {
    resetOverrideForm();
    $('#overrideModalLabel').text('Add Date Override');
    $('#overrideModal').modal('show');
  });

  // Open override modal for editing
  $(document).on('click', '.edit-override-btn', function() {
    const date = $(this).data('date');
    const overrideId = $(this).data('override-id');
    
    // Fetch override data
    $.ajax({
      url: '{{ route("availability.override.get") }}',
      method: 'GET',
      data: { date: date },
      success: function(response) {
        if (response.success && response.data) {
          const override = response.data;
          $('#override_id').val(override.id);
          $('#override_date').val(date);
          $('#is_unavailable').prop('checked', override.is_unavailable);
          $('#timeFields').toggle(!override.is_unavailable);
          
          if (!override.is_unavailable && override.start_time && override.end_time) {
            $('#override_start_time').val(override.start_time);
            $('#override_end_time').val(override.end_time);
          } else {
            $('#override_start_time').val('');
            $('#override_end_time').val('');
          }
          
          $('#override_notes').val(override.notes || '');
          
          $('#overrideModalLabel').text('Edit Date Override');
          $('#overrideModal').modal('show');
        }
      },
      error: function() {
        showAlert('danger', 'Failed to load override data');
      }
    });
  });

  // Save override
  $('#saveOverrideBtn').on('click', function() {
    const btn = $(this);
    const originalHtml = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

    // Clear previous errors
    $('[id$="_error"]').hide();
    $('.form-control, .form-select').removeClass('is-invalid');

    const formData = {
      override_id: $('#override_id').val() || null,
      override_date: $('#override_date').val(),
      is_unavailable: $('#is_unavailable').is(':checked') ? 1 : 0,
      start_time: $('#override_start_time').val(),
      end_time: $('#override_end_time').val(),
      notes: $('#override_notes').val(),
      _token: '{{ csrf_token() }}'
    };

    // Validate
    if (!formData.override_date) {
      $('#override_date_error').text('Date is required').show();
      $('#override_date').addClass('is-invalid');
      btn.html(originalHtml).prop('disabled', false);
      return;
    }

    if (!formData.is_unavailable && (!formData.start_time || !formData.end_time)) {
      if (!formData.start_time) {
        $('#start_time_error').text('Start time is required').show();
        $('#override_start_time').addClass('is-invalid');
      }
      if (!formData.end_time) {
        $('#end_time_error').text('End time is required').show();
        $('#override_end_time').addClass('is-invalid');
      }
      btn.html(originalHtml).prop('disabled', false);
      return;
    }

    $.ajax({
      url: '{{ route("availability.override.store") }}',
      method: 'POST',
      data: formData,
      success: function(response) {
        if (response.success) {
          showAlert('success', response.message);
          $('#overrideModal').modal('hide');
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          showAlert('danger', response.message || 'Failed to save override');
          btn.html(originalHtml).prop('disabled', false);
        }
      },
      error: function(xhr) {
        const errors = xhr.responseJSON?.errors || {};
        const errorMessage = xhr.responseJSON?.message || 'Failed to save override. Please try again.';
        
        // Display validation errors
        Object.keys(errors).forEach(function(key) {
          const errorId = key === 'override_date' ? 'override_date_error' : 
                         key === 'start_time' ? 'start_time_error' :
                         key === 'end_time' ? 'end_time_error' : null;
          if (errorId) {
            $('#' + errorId).text(errors[key][0]).show();
            $('#' + (key === 'override_date' ? 'override_date' : 
                     key === 'start_time' ? 'override_start_time' : 
                     key === 'end_time' ? 'override_end_time' : '')).addClass('is-invalid');
          }
        });
        
        showAlert('danger', errorMessage);
        btn.html(originalHtml).prop('disabled', false);
      }
    });
  });

  // Delete override - show modal
  let deleteOverrideId = null;
  let deleteOverrideItem = null;

  $(document).on('click', '.remove-override-btn', function() {
    deleteOverrideId = $(this).data('override-id');
    deleteOverrideItem = $(this).closest('.override-item');
    
    // Get override details for display
    const date = deleteOverrideItem.data('date');
    const dateFormatted = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
    
    // Get status
    let statusText = '';
    const badge = deleteOverrideItem.find('.badge');
    if (badge.hasClass('bg-danger')) {
      statusText = 'Unavailable';
    } else if (badge.hasClass('bg-success')) {
      statusText = badge.text().trim();
    } else {
      statusText = 'No time set';
    }
    
    // Populate modal
    $('#deleteOverrideDate').text(dateFormatted);
    $('#deleteOverrideStatus').text(statusText);
    
    // Show modal
    $('#deleteOverrideModal').modal('show');
  });

  // Confirm delete override
  $('#confirmDeleteOverrideBtn').on('click', function() {
    if (!deleteOverrideId || !deleteOverrideItem) {
      return;
    }

    const btn = $(this);
    const originalHtml = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');

    $.ajax({
      url: `/availability/override/${deleteOverrideId}`,
      method: 'DELETE',
      data: {
        _token: '{{ csrf_token() }}'
      },
      success: function(response) {
        if (response.success) {
          $('#deleteOverrideModal').modal('hide');
          showAlert('success', response.message);
          deleteOverrideItem.fadeOut(300, function() {
            $(this).remove();
            // Check if list is empty
            if ($('#overridesList .override-item').length === 0) {
              $('#overridesList').html(`
                <div class="col-12">
                  <div class="text-center py-4">
                    <i class="ti ti-calendar-off ti-3x text-muted mb-3"></i>
                    <p class="text-muted">No date overrides set. Click "Add Date Override" to create one.</p>
                  </div>
                </div>
              `);
            }
          });
          
          // Reset variables
          deleteOverrideId = null;
          deleteOverrideItem = null;
        }
        btn.html(originalHtml).prop('disabled', false);
      },
      error: function(xhr) {
        const errorMessage = xhr.responseJSON?.message || 'Failed to delete override. Please try again.';
        showAlert('danger', errorMessage);
        btn.html(originalHtml).prop('disabled', false);
      }
    });
  });

  // Reset delete variables when modal is closed
  $('#deleteOverrideModal').on('hidden.bs.modal', function() {
    deleteOverrideId = null;
    deleteOverrideItem = null;
  });

  // Reset override form
  function resetOverrideForm() {
    $('#overrideForm')[0].reset();
    $('#override_id').val('');
    $('#is_unavailable').prop('checked', false);
    $('#timeFields').show();
    $('[id$="_error"]').hide();
    $('.form-control').removeClass('is-invalid');
  }

  // Initialize override functionality on page load
  $(document).ready(function() {
    initializeOverrideModal();
  });
</script>
@endpush

