<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - {{ $tattoo['title'] ?? 'Tattoo' }} | InkJin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <!-- Dropify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/css/dropify.min.css">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .header-section {
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #e9ecef;
        }
        
        .logo-img {
            height: 40px;
            width: auto;
        }
        
        .booking-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .tattoo-preview {
            width: 100%;
            max-width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .calendar-container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .calendar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        
        .calendar-nav-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .calendar-nav-btn:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .calendar-grid {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .calendar-weekday {
            text-align: center;
            font-weight: 600;
            font-size: 0.75rem;
            color: #6c757d;
            padding: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            font-weight: 600;
            font-size: 0.9rem;
            position: relative;
        }
        
        .calendar-day.empty {
            cursor: default;
            background: transparent;
            border: none;
        }
        
        .calendar-day.available {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #28a745;
            color: #155724;
        }
        
        .calendar-day.available:hover:not([disabled]) {
            background: linear-gradient(135deg, #c3e6cb 0%, #b1dfbb 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .calendar-day.unavailable {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-color: #dc3545;
            color: #721c24;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .calendar-day.past {
            background: #f4f4f4;
            color: #adb5bd;
            cursor: not-allowed;
            opacity: 0.5;
            border-color: #e0e0e0;
        }
        
        .calendar-day:disabled,
        .calendar-day[disabled] {
            pointer-events: none;
        }
        
        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #495057;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            border: 2px solid;
        }
        
        .legend-color.available {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #28a745;
        }
        
        .legend-color.unavailable {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-color: #dc3545;
        }
        
        .slot-card {
            transition: all 0.2s ease;
            border: 2px solid #e9ecef;
        }
        
        .slot-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-color: #0d6efd;
        }
        
        .slot-card.border-primary {
            border-color: #0d6efd !important;
            background-color: #e7f1ff !important;
        }
        
        .field-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        .form-control.is-invalid,
        .form-select.is-invalid,
        .form-check-input.is-invalid {
            border-color: #dc3545;
        }
        
        .selected-slot-info {
            background-color: #e7f1ff;
            border: 1px solid #0d6efd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .calendar-day {
                min-height: 40px;
                font-size: 0.8rem;
            }
            
            .calendar-header h2 {
                font-size: 1.25rem;
            }
            
            .booking-card {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .calendar-day {
                min-height: 35px;
                font-size: 0.75rem;
            }
            
            .calendar-weekday {
                font-size: 0.7rem;
                padding: 0.375rem 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between py-3">
                        <a href="/" class="d-flex align-items-center gap-3 text-decoration-none">
                            @if(file_exists(public_path('assets/img/branding/main-logo.png')))
                                <img src="{{ asset('assets/img/branding/main-logo.png') }}" alt="InkJin" class="logo-img">
                            @elseif(file_exists(public_path('assets/img/branding/logo.png')))
                                <img src="{{ asset('assets/img/branding/logo.png') }}" alt="InkJin" class="logo-img">
                            @else
                                <span class="fs-3 fw-bold text-dark">InkJin</span>
                            @endif
                        </a>
                        <a href="{{ route('public.tattoo.db', [
                            'artist_display_name' => slugify($artist['display_name'] ?? $artist['username'] ?? ''),
                            'tattoo_title' => slugify($tattoo['title'] ?? ''),
                            'tattoo_id' => $tattoo['tattoo_id'] ?? ''
                        ]) }}" class="btn btn-outline-secondary btn-sm">
                            ← Back to Tattoo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container py-5">
        <!-- Booking Info Card -->
        <div class="booking-card">
            <div class="row align-items-center">
                <div class="col-12 col-md-auto text-center text-md-start mb-3 mb-md-0">
                    @if(!empty($tattoo['field_tattoo_image_preview']))
                        <img src="{{ $tattoo['field_tattoo_image_preview'] }}" alt="{{ $tattoo['title'] }}" class="tattoo-preview">
                    @endif
                </div>
                <div class="col-12 col-md">
                    <h1 class="h3 mb-2">{{ $tattoo['title'] ?? 'Tattoo' }}</h1>
                    <p class="text-muted mb-0">by <strong>{{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}</strong></p>
                </div>
            </div>
        </div>
        
        <!-- Calendar Section -->
        <div class="booking-card">
            <div class="calendar-container">
                <div id="availability-calendar"></div>
            </div>
        </div>
    </div>
    
    <!-- Time Slots Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="slotsOffcanvas" aria-labelledby="slotsOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="slotsOffcanvasLabel">Available Time Slots</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="slotsContainer">
                <!-- Time slots will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Questions Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="questionsOffcanvas" aria-labelledby="questionsOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="questionsOffcanvasLabel">Booking Questions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div id="questionsContainer">
                <!-- Questions form will be loaded here -->
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Dropify JS -->
    <script src="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/js/dropify.min.js"></script>
    
    <script>
        // Availability data from server
        @php
            $weeklyAvailability = $availabilityData['weeklyAvailability'] ?? [];
            $artistTimezone = $availabilityData['userTimezone'] ?? 'UTC';
        @endphp
        const availabilityData = {
            availableDates: @json($availabilityData['availableDates'] ?? []),
            unavailableDates: @json($availabilityData['unavailableDates'] ?? []),
            weeklyAvailability: @json($weeklyAvailability),
            overrides: @json($availabilityData['overrides'] ?? []),
            artistTimezone: @json($artistTimezone),
        };
        
        // Helper function to get day of week in a specific timezone
        function getDayOfWeekInTimezone(date, timezone) {
            // Create a date string in the format needed for timezone conversion
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const dateString = `${year}-${month}-${day}`;
            
            // Use Intl.DateTimeFormat to get day of week in the artist's timezone
            // This ensures we check what day it is in the artist's timezone, not the browser's timezone
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: timezone,
                weekday: 'long'
            });
            const dayName = formatter.format(new Date(dateString + 'T12:00:00'));
            const dayMap = {
                'Sunday': 0,
                'Monday': 1,
                'Tuesday': 2,
                'Wednesday': 3,
                'Thursday': 4,
                'Friday': 5,
                'Saturday': 6
            };
            return dayMap[dayName];
        }
        
        // Helper function to check if a date is available based on weekly schedule
        function checkDateAvailability(date) {
            // Create date key directly from date components to avoid timezone conversion
            // toISOString() converts to UTC which can shift the date
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const dateKey = `${year}-${month}-${day}`;
            
            // Check overrides FIRST - they always take precedence
            const override = availabilityData.overrides.find(o => o.override_date === dateKey);
            if (override) {
                // If artist marked this date as unavailable, show as red
                if (override.is_unavailable) {
                    return 'unavailable';
                }
                // If artist set custom availability for this date, show as available
                return 'available';
            }
            
            // Check if in pre-calculated lists (for dates within 2 years)
            if (availabilityData.availableDates.includes(dateKey)) {
                return 'available';
            }
            if (availabilityData.unavailableDates.includes(dateKey)) {
                return 'unavailable';
            }
            
            // Check weekly availability pattern (for dates beyond pre-calculated range)
            // Get day of week in artist's timezone, not browser's local timezone
            const dayOfWeek = getDayOfWeekInTimezone(date, availabilityData.artistTimezone);
            const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            const dayName = dayNames[dayOfWeek];
           
            if (availabilityData.weeklyAvailability[dayName] && 
                availabilityData.weeklyAvailability[dayName].length > 0) {
                return 'available';
            }
            
            // Default to unavailable
            return 'unavailable';
        }
        
        // Calendar implementation
        function renderCalendar() {
            const calendarEl = document.getElementById('availability-calendar');
            if (!calendarEl) return;
            
            const today = new Date();
            let currentMonth = today.getMonth();
            let currentYear = today.getFullYear();
            
            function renderMonth(month, year) {
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDayOfWeek = firstDay.getDay();
                
                let calendarHTML = `
                    <div class="calendar-header">
                        <button class="calendar-nav-btn" onclick="changeMonth(-1)" type="button">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <h2>${firstDay.toLocaleString('default', { month: 'long', year: 'numeric' })}</h2>
                        <button class="calendar-nav-btn" onclick="changeMonth(1)" type="button">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
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
                for (let i = 0; i < startingDayOfWeek; i++) {
                    calendarHTML += '<div class="calendar-day empty"></div>';
                }
                
                // Days of the month
                for (let day = 1; day <= daysInMonth; day++) {
                    // Create date string directly from year, month, day to avoid timezone conversion issues
                    // toISOString() converts to UTC which can shift the date by timezone offset
                    const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    
                    // Create date object for comparison (using local timezone)
                    const date = new Date(year, month, day);
                    const isPast = date < new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    
                    let dayClass = 'calendar-day';
                    let disabled = '';
                    
                    if (isPast) {
                        dayClass += ' past';
                        disabled = 'disabled';
                    } else {
                        // Check availability (works for all future dates)
                        const availability = checkDateAvailability(date);
                        if (availability === 'available') {
                            dayClass += ' available';
                        } else {
                            dayClass += ' unavailable';
                            disabled = 'disabled';
                        }
                    }
                    
                    calendarHTML += `
                        <div class="${dayClass}" ${disabled} data-date="${dateKey}">
                            ${day}
                        </div>
                    `;
                }
                
                calendarHTML += `
                        </div>
                    </div>
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <span class="legend-color available"></span>
                            <span>Available</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color unavailable"></span>
                            <span>Unavailable</span>
                        </div>
                    </div>
                `;
                
                calendarEl.innerHTML = calendarHTML;
            }
            
            window.changeMonth = function(direction) {
                currentMonth += direction;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                } else if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderMonth(currentMonth, currentYear);
            };
            
            // Initial render
            renderMonth(currentMonth, currentYear);
        }
        
        // Initialize calendar when page loads
        document.addEventListener('DOMContentLoaded', renderCalendar);

        $(document).on('click', '.calendar-day.available:not([disabled])', function() {
            const date = $(this).data('date');
            if (!date) return;
            
            // Show loading state
            const offcanvas = new bootstrap.Offcanvas(document.getElementById('slotsOffcanvas'));
            document.getElementById('slotsOffcanvasLabel').textContent = 'Available Time Slots';
            document.getElementById('slotsContainer').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            offcanvas.show();
            
            $.ajax({
                url: '{{ route('api.availability.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                method: 'GET',
                data: {
                    date: date
                },
                success: function(response) {
                    if (response.success) {
                        displayTimeSlots(response, date);
                    } else {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-triangle me-2"></i>
                                ${response.message || 'No availability data found'}
                            </div>
                        `;
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Failed to load available slots. Please try again.';
                    document.getElementById('slotsContainer').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ti ti-x-circle me-2"></i>
                            ${errorMessage}
                        </div>
                    `;
                }
            });
        });
        
        function displayTimeSlots(data, date) {
            const container = document.getElementById('slotsContainer');
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('slotsOffcanvasLabel').textContent = `Available Time Slots - ${formattedDate}`;
            
            if (data.is_unavailable) {
                container.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="ti ti-calendar-x me-2"></i>
                        Artist is unavailable on this date.
                    </div>
                `;
                return;
            }
            
            if (!data.time_slots || data.time_slots.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        No available time slots for this date.
                    </div>
                `;
                return;
            }
            
            let slotsHTML = `
                <div class="mb-3">
                    <p class="text-muted mb-2">
                        <strong>Session Duration:</strong> ${data.tattoo.session_time_h} hour(s)<br>
                        <strong>Timezone:</strong> ${data.timezone}
                    </p>
                </div>
                <div class="row g-2">
            `;
            
            data.time_slots.forEach((slot, index) => {
                slotsHTML += `
                    <div class="col-12 col-md-6">
                        <div class="card slot-card h-100" style="cursor: pointer;" data-slot-index="${index}">
                            <div class="card-body text-center">
                                <h5 class="card-title mb-1">${slot.start_time_display}</h5>
                                <p class="text-muted mb-0 small">to ${slot.end_time_display}</p>
                                <p class="text-muted mb-0 small mt-1">Duration: ${slot.duration_hours} hour(s)</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            slotsHTML += `
                </div>
                <div class="mt-3">
                    <p class="text-muted small">
                        <i class="ti ti-info-circle me-1"></i>
                        Select a time slot to proceed with booking.
                    </p>
                </div>
                <div class="mt-3 d-none" id="nextButtonContainer">
                    <button type="button" class="btn btn-primary w-100" id="nextToQuestionsBtn">
                        Next <i class="ti ti-arrow-right ms-1"></i>
                    </button>
                </div>
            `;
            
            container.innerHTML = slotsHTML;
            
            // Store questions data globally
            window.bookingData = {
                questions: data.questions || [],
                date: date,
                tattoo: data.tattoo,
                artist: data.artist,
            };
            
            // Add click handlers to slot cards
            $(document).off('click', '.slot-card').on('click', '.slot-card', function() {
                const slotIndex = $(this).data('slot-index');
                const selectedSlot = data.time_slots[slotIndex];
                
                // Highlight selected slot
                $('.slot-card').removeClass('border-primary bg-light');
                $(this).addClass('border-primary bg-light');
                
                // Store selected slot data
                window.selectedSlot = {
                    date: date,
                    slot: selectedSlot,
                    tattoo: data.tattoo,
                    artist: data.artist
                };
                
                // Show Next button
                $('#nextButtonContainer').removeClass('d-none');
            });
            
            // Handle Next button click
            $(document).off('click', '#nextToQuestionsBtn').on('click', '#nextToQuestionsBtn', function() {
                if (!window.selectedSlot) {
                    alert('Please select a time slot first.');
                    return;
                }
                
                // Close slots offcanvas and open questions offcanvas
                const slotsOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('slotsOffcanvas'));
                if (slotsOffcanvas) {
                    slotsOffcanvas.hide();
                }
                
                // Show questions offcanvas
                setTimeout(() => {
                    showQuestionsForm();
                }, 300);
            });
        }
        
        function showQuestionsForm() {
            const questions = window.bookingData?.questions || [];
            const container = document.getElementById('questionsContainer');
            
            if (!questions || questions.length === 0) {
                // No questions, proceed to booking confirmation
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        No questions to answer. Proceeding to booking confirmation...
                    </div>
                `;
                // TODO: Proceed to booking confirmation
                return;
            }
            
            // Show selected time slot info
            const selectedSlot = window.selectedSlot;
            const formattedDate = new Date(selectedSlot.date + 'T00:00:00').toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            let questionsHTML = `
                <form id="questionsForm">
                    <div class="selected-slot-info">
                        <h6 class="mb-2"><i class="ti ti-calendar-event me-2"></i>Selected Time Slot</h6>
                        <p class="mb-1"><strong>Date:</strong> ${formattedDate}</p>
                        <p class="mb-1"><strong>Time:</strong> ${selectedSlot.slot.start_time_display} - ${selectedSlot.slot.end_time_display}</p>
                        <p class="mb-0"><strong>Duration:</strong> ${selectedSlot.slot.duration_hours} hour(s)</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted">Please answer the following questions:</h6>
                    </div>
            `;
            
            questions.forEach((question, index) => {
                const questionId = `question_${question.id}`;
                
                questionsHTML += `
                    <div class="mb-4">
                        <label for="${questionId}" class="form-label fw-semibold">
                            ${question.question}
                            <span class="text-danger">*</span>
                        </label>
                `;
                
                if (question.type === 'free') {
                    questionsHTML += `
                        <textarea 
                            class="form-control" 
                            id="${questionId}" 
                            name="questions[${question.id}]" 
                            rows="3" 
                            placeholder="Enter your answer..."></textarea>
                        <span class="field-error" id="${questionId}_error"></span>
                    `;
                } else if (question.type === 'select') {
                    questionsHTML += `
                        <select 
                            class="form-select" 
                            id="${questionId}" 
                            name="questions[${question.id}]">
                            <option value="">Select an option...</option>
                    `;
                    if (question.options && Array.isArray(question.options)) {
                        question.options.forEach(option => {
                            questionsHTML += `<option value="${option}">${option}</option>`;
                        });
                    }
                    questionsHTML += `
                        </select>
                        <span class="field-error" id="${questionId}_error"></span>
                    `;
                } else if (question.type === 'radio') {
                    questionsHTML += `<div class="mt-2">`;
                    if (question.options && Array.isArray(question.options)) {
                        question.options.forEach((option, optIndex) => {
                            const radioId = `${questionId}_${optIndex}`;
                            questionsHTML += `
                                <div class="form-check mb-2">
                                    <input 
                                        class="form-check-input" 
                                        type="radio" 
                                        name="questions[${question.id}]" 
                                        id="${radioId}" 
                                        value="${option}">
                                    <label class="form-check-label" for="${radioId}">
                                        ${option}
                                    </label>
                                </div>
                            `;
                        });
                    }
                    questionsHTML += `
                        </div>
                        <span class="field-error" id="${questionId}_error"></span>
                    `;
                } else if (question.type === 'image') {
                    questionsHTML += `
                        <input 
                            type="file" 
                            class="dropify" 
                            id="${questionId}" 
                            name="questions[${question.id}]" 
                            accept="image/*"
                            data-height="200">
                        <span class="field-error" id="${questionId}_error"></span>
                    `;
                }
                
                questionsHTML += `</div>`;
            });
            
            questionsHTML += `
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="backToSlotsBtn">
                            <i class="ti ti-arrow-left me-1"></i> Back
                        </button>
                        <button type="submit" class="btn btn-primary flex-grow-1" id="submitBookingBtn">
                            Submit Booking <i class="ti ti-check ms-1"></i>
                        </button>
                    </div>
                </form>
            `;
            
            container.innerHTML = questionsHTML;
            
            // Initialize Dropify for image inputs
            $('.dropify').dropify({
                messages: {
                    'default': 'Drag and drop an image here or click',
                    'replace': 'Drag and drop or click to replace',
                    'remove': 'Remove',
                    'error': 'Ooops, something wrong happened.'
                }
            });
            
            // Show questions offcanvas
            const questionsOffcanvas = new bootstrap.Offcanvas(document.getElementById('questionsOffcanvas'));
            questionsOffcanvas.show();
            
            // Handle back button
            $(document).off('click', '#backToSlotsBtn').on('click', '#backToSlotsBtn', function() {
                const questionsOffcanvasInstance = bootstrap.Offcanvas.getInstance(document.getElementById('questionsOffcanvas'));
                if (questionsOffcanvasInstance) {
                    questionsOffcanvasInstance.hide();
                }
                setTimeout(() => {
                    const slotsOffcanvas = new bootstrap.Offcanvas(document.getElementById('slotsOffcanvas'));
                    slotsOffcanvas.show();
                }, 300);
            });
            
            // Handle form submission
            $(document).off('submit', '#questionsForm').on('submit', '#questionsForm', function(e) {
                e.preventDefault();
                
                // Clear previous errors
                $('.field-error').text('').hide();
                $('.form-control, .form-select, .form-check-input').removeClass('is-invalid');
                
                const formData = new FormData(this);
                
                // Add slot data
                formData.append('slot[date]', window.selectedSlot.date);
                formData.append('slot[start_time_utc]', window.selectedSlot.slot.start_time_utc);
                formData.append('slot[end_time_utc]', window.selectedSlot.slot.end_time_utc);
                
                // Disable submit button
                const submitBtn = $('#submitBookingBtn');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Submitting...');
                
                // Submit via AJAX
                $.ajax({
                    url: '{{ route('api.booking.submit', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Booking submitted successfully!');
                            // TODO: Redirect to confirmation page
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            // Display validation errors
                            const errors = xhr.responseJSON.errors;
                            $.each(errors, function(field, messages) {
                                if (field.startsWith('questions.')) {
                                    const questionId = field.replace('questions.', '');
                                    const errorElement = $(`#question_${questionId}_error`);
                                    
                                    // Find input element(s) - could be textarea, select, file input, or radio buttons
                                    const inputElement = $(`#question_${questionId}`);
                                    const radioElements = $(`input[name="questions[${questionId}]"]`);
                                    
                                    if (errorElement.length) {
                                        errorElement.text(messages[0]).show();
                                    }
                                    
                                    // Mark input as invalid
                                    if (inputElement.length) {
                                        inputElement.addClass('is-invalid');
                                    } else if (radioElements.length) {
                                        // For radio buttons, mark all as invalid
                                        radioElements.addClass('is-invalid');
                                    }
                                }
                            });
                        } else {
                            alert('An error occurred. Please try again.');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('Submit Booking <i class="ti ti-check ms-1"></i>');
                    }
                });
            });
        }
    </script>
</body>
</html>

