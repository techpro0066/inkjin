@extends('layouts.dashboard_layout')

@section('title', 'Reschedule Booking')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Bookings /</span> Reschedule Booking
            </h4>

            <!-- Current Booking Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-calendar-event me-2"></i>Current Booking Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Date:</strong> {{ $booking->booking_date->format('F d, Y') }}</p>
                            <p><strong>Time:</strong> {{ $booking->booking_time['start'] ?? 'N/A' }} - {{ $booking->booking_time['end'] ?? 'N/A' }}</p>
                            <p><strong>Tattoo:</strong> {{ $booking->tattoo->title ?? 'Custom Tattoo' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Artist:</strong> {{ $booking->artist->name }}</p>
                            <p><strong>Status:</strong> <span class="badge bg-label-success">{{ ucfirst($booking->status) }}</span></p>
                            @if($booking->reschedule_count > 0)
                                <p><strong>Rescheduled:</strong> {{ $booking->reschedule_count }} time(s)</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Artist Request Message -->
            @if($isArtistRequested)
                <div class="alert alert-info">
                    <h5><i class="ti ti-info-circle me-2"></i>Artist Requested Reschedule</h5>
                    <p>{{ $booking->artist->name }} has requested to reschedule this booking.</p>
                    @if($booking->reschedule_reason)
                        <p><strong>Reason:</strong> {{ $booking->reschedule_reason }}</p>
                    @endif
                    <p class="mb-0">Please select a new date and time below. This reschedule will not count against your reschedule limit.</p>
                </div>
            @endif

            <!-- Eligibility Info (Client-initiated) -->
            @if($eligibility && !$isArtistRequested)
                @if($eligibility['can_reschedule'] ?? false)
                    <div class="alert alert-success">
                        <h6><i class="ti ti-check-circle me-2"></i>Reschedule Available</h6>
                        <p class="mb-1">{{ $eligibility['message'] ?? '' }}</p>
                        @if(isset($eligibility['deadline']))
                            <p class="mb-0"><small>Deadline: {{ \Carbon\Carbon::parse($eligibility['deadline'])->format('F d, Y g:i A') }}</small></p>
                        @endif
                    </div>
                @else
                    <div class="alert alert-danger">
                        <h6><i class="ti ti-alert-circle me-2"></i>Cannot Reschedule</h6>
                        <p class="mb-2">{{ $eligibility['message'] ?? 'This booking cannot be rescheduled.' }}</p>
                        <a href="{{ route('api.bookings.cancellation-info', $booking->id) }}" class="btn btn-danger btn-sm">
                            <i class="ti ti-x me-1"></i>Cancel Booking Instead
                        </a>
                    </div>
                @endif
            @endif

            @if(($eligibility && ($eligibility['can_reschedule'] ?? false)) || $isArtistRequested)
                <!-- Reschedule Calendar -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-calendar me-2"></i>Select New Date & Time</h5>
                    </div>
                    <div class="card-body">
                        <!-- Calendar Container -->
                        <div id="rescheduleCalendar" class="mb-4"></div>
                        
                        <!-- Available Slots -->
                        <div id="availableSlots" class="d-none">
                            <h6 class="mb-3">Available Time Slots</h6>
                            <div id="slotsContainer" class="row g-2"></div>
                        </div>

                        <!-- Selected Slot Info -->
                        <div id="selectedSlotInfo" class="alert alert-info d-none mt-3">
                            <h6>Selected Time Slot</h6>
                            <p class="mb-1"><strong>Date:</strong> <span id="selectedDate"></span></p>
                            <p class="mb-0"><strong>Time:</strong> <span id="selectedTime"></span></p>
                        </div>

                        <!-- Reschedule Button -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-primary" id="confirmRescheduleBtn" disabled>
                                <i class="ti ti-calendar-check me-1"></i>Confirm Reschedule
                            </button>
                            <a href="{{ route('bookings.index') }}" class="btn btn-label-secondary">
                                <i class="ti ti-x me-1"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
const bookingId = {{ $booking->id }};
const tattooId = {{ $booking->tattoo_id }};
let selectedDate = null;
let selectedSlot = null;

// Initialize calendar
document.addEventListener('DOMContentLoaded', function() {
    renderCalendar();
});

// Render a simple calendar (all future dates clickable; availability is checked per-date via API)
function renderCalendar() {
    const calendarEl = document.getElementById('rescheduleCalendar');
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    renderMonth(currentMonth, currentYear);
}

function renderMonth(month, year) {
    const calendarEl = document.getElementById('rescheduleCalendar');
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    
    let calendarHTML = `
        <div class="calendar-header mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(-1)">
                    <i class="ti ti-chevron-left"></i>
                </button>
                <h5 class="mb-0">${new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' })}</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(1)">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="calendar-grid">
            <div class="calendar-weekdays">
                <div class="calendar-weekday">Sun</div>
                <div class="calendar-weekday">Mon</div>
                <div class="calendar-weekday">Tue</div>
                <div class="calendar-weekday">Wed</div>
                <div class="calendar-weekday">Thu</div>
                <div class="calendar-weekday">Fri</div>
                <div class="calendar-weekday">Sat</div>
            </div>
            <div class="calendar-days">
    `;
    
    // Empty cells for days before the first day of the month
    for (let i = 0; i < firstDay; i++) {
        calendarHTML += '<div class="calendar-day empty"></div>';
    }
    
    // Days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const date = new Date(year, month, day);
        const isPast = date < new Date(today.getFullYear(), today.getMonth(), today.getDate());
        
        let dayClass = 'calendar-day';
        let disabled = '';
        
        if (isPast) {
            dayClass += ' past';
            disabled = 'disabled';
        } else {
            // For reschedule, allow all future dates; actual slot availability will be checked via API
            dayClass += ' available';
        }
        
        calendarHTML += `
            <div class="${dayClass}" ${disabled} data-date="${dateKey}" onclick="selectDate('${dateKey}')">
                ${day}
            </div>
        `;
    }
    
    calendarHTML += `
            </div>
        </div>
    `;
    
    calendarEl.innerHTML = calendarHTML;
}

function selectDate(dateKey) {
    selectedDate = dateKey;
    document.querySelectorAll('.calendar-day').forEach(day => {
        day.classList.remove('selected');
    });
    event.target.classList.add('selected');
    
    // Load available slots for this date
    loadAvailableSlots(dateKey);
}

function loadAvailableSlots(date) {
    fetch(`/api/availability/${tattooId}?date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.slots && data.data.slots.length > 0) {
                displaySlots(data.data.slots);
            } else {
                document.getElementById('availableSlots').classList.add('d-none');
                document.getElementById('slotsContainer').innerHTML = 
                    '<div class="col-12"><p class="text-muted">No available slots for this date.</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading slots:', error);
        });
}

function displaySlots(slots) {
    const container = document.getElementById('slotsContainer');
    container.innerHTML = '';
    
    slots.forEach(slot => {
        const slotEl = document.createElement('div');
        slotEl.className = 'col-md-3 col-sm-6 mb-2';
        slotEl.innerHTML = `
            <button class="btn btn-outline-primary w-100 slot-btn" 
                    data-start="${slot.start_utc}" 
                    data-end="${slot.end_utc}"
                    onclick="selectSlot('${slot.start_utc}', '${slot.end_utc}', '${slot.start}', '${slot.end}')">
                ${slot.start} - ${slot.end}
            </button>
        `;
        container.appendChild(slotEl);
    });
    
    document.getElementById('availableSlots').classList.remove('d-none');
}

function selectSlot(startUtc, endUtc, start, end) {
    selectedSlot = {
        date: selectedDate,
        start_time_utc: startUtc,
        end_time_utc: endUtc,
        start: start,
        end: end
    };
    
    // Update selected slot info
    document.getElementById('selectedDate').textContent = new Date(selectedDate).toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    document.getElementById('selectedTime').textContent = `${start} - ${end}`;
    document.getElementById('selectedSlotInfo').classList.remove('d-none');
    document.getElementById('confirmRescheduleBtn').disabled = false;
    
    // Highlight selected slot
    document.querySelectorAll('.slot-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

document.getElementById('confirmRescheduleBtn').addEventListener('click', function() {
    if (!selectedSlot) {
        alert('Please select a date and time slot.');
        return;
    }
    
    if (!confirm('Are you sure you want to reschedule this booking?')) {
        return;
    }
    
    // Disable button
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
    
    // Submit reschedule request
    fetch(`/api/bookings/${bookingId}/reschedule`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            new_date: selectedSlot.date,
            new_start_time_utc: selectedSlot.start_time_utc,
            new_end_time_utc: selectedSlot.end_time_utc
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Booking rescheduled successfully!');
            window.location.href = '{{ route("bookings.index") }}';
        } else {
            alert('Failed to reschedule: ' + (data.message || 'Unknown error'));
            this.disabled = false;
            this.innerHTML = '<i class="ti ti-calendar-check me-1"></i>Confirm Reschedule';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        this.disabled = false;
        this.innerHTML = '<i class="ti ti-calendar-check me-1"></i>Confirm Reschedule';
    });
});

// Add CSRF token meta tag if not exists
if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = '{{ csrf_token() }}';
    document.head.appendChild(meta);
}
</script>

<style>
.calendar-grid {
    display: grid;
    gap: 1px;
    background: #e0e0e0;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f5f5f5;
}

.calendar-weekday {
    padding: 10px;
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: white;
}

.calendar-day {
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: background-color 0.2s;
    min-height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-day.empty {
    background: #f9f9f9;
    cursor: default;
}

.calendar-day.past {
    background: #f5f5f5;
    color: #999;
    cursor: not-allowed;
}

.calendar-day.available {
    background: #e8f5e9;
    color: #2e7d32;
}

.calendar-day.available:hover {
    background: #c8e6c9;
}

.calendar-day.unavailable {
    background: #ffebee;
    color: #c62828;
    cursor: not-allowed;
}

.calendar-day.selected {
    background: #1976d2;
    color: white;
    font-weight: bold;
}

.slot-btn.active {
    background: #1976d2;
    color: white;
    border-color: #1976d2;
}
</style>
@endsection
