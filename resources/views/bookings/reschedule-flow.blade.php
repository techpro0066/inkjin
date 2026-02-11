<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Booking - {{ $tattoo['title'] ?? 'Tattoo' }} | InkJin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .booking-card.d-none {
            display: none !important;
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

        .calendar-day.past {
            background: #f4f4f4;
            color: #adb5bd;
            cursor: not-allowed;
            opacity: 0.5;
            border-color: #e0e0e0;
        }

        .calendar-day.selected {
            border-color: #0d6efd;
            box-shadow: 0 0 0 1px #0d6efd;
        }

        .slot-card {
            transition: all 0.2s ease;
            border: 2px solid #e9ecef;
            cursor: pointer;
        }

        .slot-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-color: #0d6efd;
        }

        .slot-card.active {
            border-color: #0d6efd !important;
            background-color: #e7f1ff !important;
        }

        .selected-slot-info {
            background-color: #e7f1ff;
            border: 1px solid #0d6efd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
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
                        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm">
                            ← Back to My Bookings
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
                    <h1 class="h4 mb-2">Reschedule Booking #{{ $booking->id }}</h1>
                    <p class="mb-1">
                        <strong>{{ $tattoo['title'] ?? 'Tattoo' }}</strong><br>
                        <span class="text-muted">by {{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}</span>
                    </p>
                    <p class="mb-0 text-muted">
                        Current: {{ $booking->booking_date->format('F d, Y') }},
                        {{ $booking->booking_time['start'] ?? '' }} - {{ $booking->booking_time['end'] ?? '' }}
                    </p>
                </div>
            </div>
            
            @if($isArtistRequested && $booking->reschedule_status === 'pending')
            <div class="alert alert-warning mt-3 mb-0">
                <div class="d-flex align-items-start">
                    <i class="ti ti-alert-triangle me-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="alert-heading mb-2">
                            <i class="ti ti-info-circle me-1"></i>Artist Has Requested to Reschedule
                        </h6>
                        <p class="mb-2">
                            The artist has requested to reschedule this booking. Please select a new date and time that works for you.
                        </p>
                        @if($booking->reschedule_reason)
                        <p class="mb-0">
                            <strong>Artist's Reason:</strong> {{ $booking->reschedule_reason }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Step 1: Calendar -->
        <div class="booking-card" id="calendarSection">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0" id="calendarTitle">
                    @if(($consultationInfo['is_separate'] ?? false))
                        Step 1: Select Consultation Date
                    @else
                        Step 1: Select Date
                    @endif
                </h5>
            </div>
            <div class="calendar-container">
                <div id="availability-calendar">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading calendar...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading calendar...</p>
                    </div>
                </div>
                <div class="mt-3 d-none" id="calendarNextButton">
                    <button type="button" class="btn btn-primary w-100" id="nextToSlotsBtn">
                        Next: Select Time <i class="ti ti-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Time Slots -->
        <div class="booking-card d-none" id="slotsSection">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0" id="slotsTitle">Step 2: Select Time Slot</h5>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="backToCalendarBtn">
                    <i class="ti ti-arrow-left me-1"></i> Change Date
                </button>
            </div>
            <div id="slotsContainer"></div>
            <div class="mt-3 d-none" id="slotsNextButton">
                <button type="button" class="btn btn-primary w-100" id="nextToConfirmBtn">
                    Next: Confirm Reschedule <i class="ti ti-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Confirm -->
        <div class="booking-card d-none" id="confirmSection">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Step 3: Confirm Reschedule</h5>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="backToSlotsFromConfirmBtn">
                    <i class="ti ti-arrow-left me-1"></i> Change Time
                </button>
            </div>
            <div id="confirmSummary">
                <!-- Summary will be injected here -->
            </div>
            <div class="mt-3">
                <button type="button" class="btn btn-success w-100" id="confirmRescheduleBtn">
                    <i class="ti ti-calendar-check me-1"></i>Confirm Reschedule
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const csrfToken = '{{ csrf_token() }}';
        const bookingId = {{ $booking->id }};
        const tattooId = {{ $tattoo['tattoo_id'] }};

        // Consultation & availability data from server
        @php
            $defaultConsultationInfo = [
                'requires_consultation' => false,
                'consultation_timing' => null,
                'is_separate' => false,
                'is_combined' => false,
                'session_duration_minutes' => null,
                'gap_required' => false,
                'gap_value' => null,
                'gap_unit' => null,
            ];
            $finalConsultationInfo = $consultationInfo ?? $defaultConsultationInfo;
            $weeklyAvailability = $availabilityData['weeklyAvailability'] ?? [];
            $artistTimezone = $availabilityData['userTimezone'] ?? 'UTC';
        @endphp

        const consultationInfo = @json($finalConsultationInfo);
        const availabilityData = {
            availableDates: @json($availabilityData['availableDates'] ?? []),
            unavailableDates: @json($availabilityData['unavailableDates'] ?? []),
            weeklyAvailability: @json($weeklyAvailability),
            overrides: @json($availabilityData['overrides'] ?? []),
            artistTimezone: @json($artistTimezone),
        };

        // Determine booking type from the booking being rescheduled
        const bookingType = '{{ $booking->booking_type ?? "flash" }}';
        const consultationTimingType = '{{ $booking->consultation_timing_type ?? "" }}';
        const consultationBooking = @json($consultationBooking ?? null);
        
        // Set booking flow step based on booking type and consultation requirements
        let bookingFlowStep;
        if (bookingType === 'consultation') {
            // Rescheduling a consultation booking
            bookingFlowStep = 'consultation';
        } else if (bookingType === 'tattoo_session' && consultationInfo.is_separate) {
            // Rescheduling a tattoo session with separate consultation - start with consultation
            bookingFlowStep = 'consultation';
        } else {
            // Regular booking (flash, custom, combined consultation, etc.)
            bookingFlowStep = 'tattoo_session';
        }
        
        // Store consultation slot info - initialize with existing consultation booking if available
        // Note: We pre-populate this for display purposes, but user still needs to select a NEW consultation slot
        let selectedConsultationSlot = null;
        let hasExistingConsultation = false;
        @if(isset($consultationBooking) && $consultationBooking)
        if (consultationInfo.is_separate) {
            hasExistingConsultation = true;
            // Store existing consultation for reference, but user will need to select a new one
            // Don't set selectedConsultationSlot here - let user select it fresh
        }
        @endif
        
        // Store tattoo session slot
        let selectedTattooSessionSlot = null;
        
        // Calculate minimum tattoo session date based on gap requirements
        let minimumTattooSessionDate = null;
        
        function calculateMinimumTattooSessionDate() {
            if (!selectedConsultationSlot || !consultationInfo.gap_required) {
                minimumTattooSessionDate = null;
                return;
            }
            
            const consultationEnd = new Date(selectedConsultationSlot.date + 'T' + selectedConsultationSlot.end_time_utc);
            const gapValue = consultationInfo.gap_value || 0;
            const gapUnit = consultationInfo.gap_unit || 'days';
            
            let minimumDate = new Date(consultationEnd);
            if (gapUnit === 'days') {
                minimumDate.setDate(minimumDate.getDate() + gapValue);
            } else if (gapUnit === 'hours') {
                minimumDate.setHours(minimumDate.getHours() + gapValue);
            } else if (gapUnit === 'weeks') {
                minimumDate.setDate(minimumDate.getDate() + (gapValue * 7));
            }
            
            minimumTattooSessionDate = minimumDate.toISOString();
        }
        
        // Initialize minimum date if consultation slot is already selected
        // (This will be called after user selects a new consultation slot)
        
        let selectedDate = null;

        // Calendar rendering (reuse logic from booking page, simplified)
        function renderCalendar() {
            const calendarEl = document.getElementById('availability-calendar');
            if (!calendarEl) return;

            if (!availabilityData || (!availabilityData.availableDates && !availabilityData.weeklyAvailability)) {
                calendarEl.innerHTML = `
                    <div class="alert alert-warning">
                        <h6>No availability data available</h6>
                        <p class="mb-0">Unable to load calendar. Please refresh the page or contact the artist.</p>
                        <button class="btn btn-primary btn-sm mt-2" onclick="location.reload()">Refresh Page</button>
                    </div>
                `;
                return;
            }

            const today = new Date();
            let currentMonth = today.getMonth();
            let currentYear = today.getFullYear();

            if (availabilityData.availableDates && availabilityData.availableDates.length > 0) {
                const sortedDates = availabilityData.availableDates
                    .filter(dateKey => {
                        const date = new Date(dateKey + 'T00:00:00');
                        return date >= new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    })
                    .sort();

                if (sortedDates.length > 0) {
                    const firstAvailableDate = new Date(sortedDates[0] + 'T00:00:00');
                    currentMonth = firstAvailableDate.getMonth();
                    currentYear = firstAvailableDate.getFullYear();
                }
            }

            renderMonth(currentMonth, currentYear);
        }

        function renderMonth(month, year) {
            const calendarEl = document.getElementById('availability-calendar');
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();

            let html = `
                <div class="calendar-header mb-3 d-flex justify-content-between align-items-center">
                    <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(-1)">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <h5 class="mb-0">${new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' })}</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(1)">
                        <i class="ti ti-chevron-right"></i>
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

            for (let i = 0; i < firstDay; i++) {
                html += '<div class="calendar-day empty"></div>';
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dateObj = new Date(year, month, day);
                const isPast = dateObj < new Date(today.getFullYear(), today.getMonth(), today.getDate());

                let dayClass = 'calendar-day';
                let disabled = '';

                if (isPast) {
                    dayClass += ' past';
                    disabled = 'disabled';
                } else {
                    // If on tattoo session step and consultation is selected, disable dates appropriately
                    let shouldDisable = false;
                    if (bookingFlowStep === 'tattoo_session' && selectedConsultationSlot) {
                        const consultationDate = new Date(selectedConsultationSlot.date + 'T00:00:00');
                        const checkDate = new Date(dateKey + 'T00:00:00');
                        
                        // Always disable dates before consultation date
                        if (checkDate < consultationDate) {
                            shouldDisable = true;
                        }
                        
                        // If gap is required, also disable dates before or equal to minimum date
                        if (!shouldDisable && minimumTattooSessionDate) {
                            const minDate = new Date(minimumTattooSessionDate.split('T')[0] + 'T00:00:00');
                            if (checkDate <= minDate) {
                                shouldDisable = true;
                            }
                        } else if (!shouldDisable && !minimumTattooSessionDate) {
                            // No gap requirement, but still disable consultation date itself
                            if (checkDate.getTime() === consultationDate.getTime()) {
                                shouldDisable = true;
                            }
                        }
                    }
                    
                    if (shouldDisable) {
                        dayClass += ' unavailable';
                        disabled = 'disabled';
                    } else {
                        const isAvailable = availabilityData.availableDates.includes(dateKey);
                        if (isAvailable) {
                            dayClass += ' available';
                        } else {
                            dayClass += ' unavailable';
                            disabled = 'disabled';
                        }
                    }
                }

                html += `
                    <div class="${dayClass}" ${disabled} data-date="${dateKey}">
                        ${day}
                    </div>
                `;
            }

            html += `
                    </div>
                </div>
            `;

            calendarEl.innerHTML = html;
        }

        function changeMonth(offset) {
            const header = document.querySelector('.calendar-header h5');
            if (!header) return;
            const [monthName, year] = header.textContent.split(' ');
            const date = new Date(`${monthName} 1, ${year}`);
            date.setMonth(date.getMonth() + offset);
            renderMonth(date.getMonth(), date.getFullYear());
        }

        // Handle date click
        $(document).on('click', '.calendar-day.available:not([disabled])', function() {
            const date = $(this).data('date');
            if (!date) return;

            $('.calendar-day').removeClass('selected');
            $(this).addClass('selected');

            selectedDate = date;
            $('#calendarNextButton').removeClass('d-none');
        });

        // Next from calendar to slots - handle separate consultation flow
        $(document).on('click', '#nextToSlotsBtn', function() {
            if (!selectedDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Date Required',
                    text: 'Please select a date first.',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }
            
            console.log('Next button clicked:', {
                bookingFlowStep: bookingFlowStep,
                bookingType: bookingType,
                consultationInfo: consultationInfo,
                selectedConsultationSlot: selectedConsultationSlot
            });
            
            // Check if separate consultation flow
            if (consultationInfo.is_separate) {
                // Safety check: If no consultation slot is selected yet, we MUST be in consultation step
                if (!selectedConsultationSlot) {
                    // Force consultation step
                    bookingFlowStep = 'consultation';
                    document.getElementById('calendarTitle').textContent = 'Step 1: Select Consultation Date';
                    loadConsultationSlots(selectedDate);
                    return;
                }
                
                // Determine which step we're on based on bookingFlowStep
                if (bookingFlowStep === 'consultation') {
                    // We're in consultation step - load consultation slots
                    loadConsultationSlots(selectedDate);
                } else if (bookingFlowStep === 'tattoo_session') {
                    // We're in tattoo session step - consultation slot should already be selected (checked above)
                    // Check if date is before minimum date
                    if (minimumTattooSessionDate) {
                        const selectedDateObj = new Date(selectedDate + 'T00:00:00');
                        const minDate = new Date(minimumTattooSessionDate.split('T')[0] + 'T00:00:00');
                        if (selectedDateObj < minDate) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Date Too Early',
                                html: `Please select a date on or after <strong>${minDate.toLocaleDateString()}</strong>.<br>Minimum gap required: ${consultationInfo.gap_value} ${consultationInfo.gap_unit} after consultation.`,
                                confirmButtonColor: '#0d6efd'
                            });
                            return;
                        }
                    }
                    loadTattooSessionSlots(selectedDate);
                } else {
                    // Fallback: default to consultation step
                    console.warn('bookingFlowStep not recognized, defaulting to consultation');
                    bookingFlowStep = 'consultation';
                    document.getElementById('calendarTitle').textContent = 'Step 1: Select Consultation Date';
                    loadConsultationSlots(selectedDate);
                }
            } else {
                // Regular flow (combined or no consultation)
                loadRegularSlots(selectedDate);
            }
        });

        // Load consultation slots
        function loadConsultationSlots(date) {
            console.log('loadConsultationSlots called for date:', date);
            // Ensure we're in consultation step
            bookingFlowStep = 'consultation';
            document.getElementById('calendarTitle').textContent = 'Step 1: Select Consultation Date';
            
            document.getElementById('slotsContainer').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading consultation slots...</p>
                </div>
            `;
            document.getElementById('slotsSection').classList.remove('d-none');
            document.getElementById('calendarSection').classList.add('d-none');
            document.getElementById('slotsTitle').textContent = 'Select Consultation Time Slot';
            document.getElementById('slotsNextButton').classList.add('d-none');
            
            const apiUrl = '{{ route('api.consultation.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}';
            const queryString = new URLSearchParams({ date: date }).toString();
            
            fetch(`${apiUrl}?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    if (data.is_unavailable) {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-calendar-x me-2"></i>Artist is unavailable on this date.
                            </div>
                        `;
                        return;
                    }
                    
                    if (data.success && data.time_slots && data.time_slots.length > 0) {
                        displayConsultationSlots(data, date);
                    } else {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-circle me-2"></i>${data.message || 'No consultation slots available for this date.'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading consultation slots:', error);
                    document.getElementById('slotsContainer').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ti ti-alert-circle me-2"></i>Failed to load consultation slots. Please try again.
                        </div>
                    `;
                });
        }
        
        // Load tattoo session slots
        function loadTattooSessionSlots(date) {
            console.log('loadTattooSessionSlots called for date:', date, 'selectedConsultationSlot:', selectedConsultationSlot);
            // This check should have been done before calling this function, but double-check
            if (!selectedConsultationSlot) {
                console.error('loadTattooSessionSlots called without selectedConsultationSlot');
                Swal.fire({
                    icon: 'info',
                    title: 'Consultation Required',
                    text: 'Please select a consultation slot first. Redirecting to consultation step...',
                    confirmButtonColor: '#0d6efd',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    // Switch back to consultation step
                    bookingFlowStep = 'consultation';
                    document.getElementById('calendarSection').classList.remove('d-none');
                    document.getElementById('slotsSection').classList.add('d-none');
                    document.getElementById('calendarTitle').textContent = 'Step 1: Select Consultation Date';
                    selectedDate = null;
                    $('.calendar-day').removeClass('selected');
                    $('#calendarNextButton').addClass('d-none');
                    renderCalendar(); // Re-render calendar
                });
                return;
            }
            
            document.getElementById('slotsContainer').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading tattoo session slots...</p>
                </div>
            `;
            document.getElementById('slotsSection').classList.remove('d-none');
            document.getElementById('calendarSection').classList.add('d-none');
            document.getElementById('slotsTitle').textContent = 'Select Tattoo Session Time Slot';
            document.getElementById('slotsNextButton').classList.add('d-none');
            
            const apiUrl = '{{ route('api.tattoo-session.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}';
            const apiData = {
                date: date,
                consultation_date: selectedConsultationSlot.date,
                consultation_start_time_utc: selectedConsultationSlot.start_time_utc,
                consultation_end_time_utc: selectedConsultationSlot.end_time_utc
            };
            const queryString = new URLSearchParams(apiData).toString();
            
            fetch(`${apiUrl}?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    if (data.is_unavailable) {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-calendar-x me-2"></i>Artist is unavailable on this date.
                            </div>
                        `;
                        return;
                    }
                    
                    if (data.success && data.time_slots && data.time_slots.length > 0) {
                        displayTattooSessionSlots(data, date);
                    } else {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-circle me-2"></i>${data.message || 'No tattoo session slots available for this date.'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading tattoo session slots:', error);
                    document.getElementById('slotsContainer').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ti ti-alert-circle me-2"></i>Failed to load tattoo session slots. Please try again.
                        </div>
                    `;
                });
        }
        
        // Load regular slots (for non-separate consultation bookings)
        function loadRegularSlots(date) {
            document.getElementById('slotsContainer').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading available time slots...</p>
                </div>
            `;
            document.getElementById('slotsSection').classList.remove('d-none');
            document.getElementById('calendarSection').classList.add('d-none');

            // Regular booking flow (flash, custom, combined consultation)
            const apiUrl = '{{ route('api.availability.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}';
            const apiData = { date: date };

            // Build query string
            const queryString = new URLSearchParams(apiData).toString();
            
            fetch(`${apiUrl}?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    // Check if date is unavailable
                    if (data.is_unavailable) {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-calendar-x me-2"></i>Artist is unavailable on this date.
                            </div>
                        `;
                        document.getElementById('slotsNextButton').classList.add('d-none');
                        return;
                    }
                    
                    // Handle response format - all endpoints return time_slots array
                    if (data.success && data.time_slots && Array.isArray(data.time_slots) && data.time_slots.length > 0) {
                        displaySlots(data.time_slots, date);
                    } else {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-circle me-2"></i>${data.message || 'No available slots for this date.'}
                            </div>
                        `;
                        document.getElementById('slotsNextButton').classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error loading slots:', error);
                    document.getElementById('slotsContainer').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ti ti-alert-circle me-2"></i>Failed to load time slots. Please try again.
                        </div>
                    `;
                    document.getElementById('slotsNextButton').classList.add('d-none');
                });
        }

        // Display consultation slots
        function displayConsultationSlots(data, date) {
            const container = document.getElementById('slotsContainer');
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('slotsTitle').textContent = `Select Consultation Time Slot - ${formattedDate}`;
            
            const consultationDuration = consultationInfo.session_duration_minutes || 30;
            let slotsHTML = `
                <div class="mb-3">
                    <p class="text-muted mb-2">
                        <strong>Consultation Duration:</strong> ${consultationDuration} minutes<br>
                        <strong>Timezone:</strong> ${data.timezone}
                    </p>
                </div>
                <div class="row g-2">
            `;
            
            data.time_slots.forEach((slot, index) => {
                const isSelected = selectedConsultationSlot && 
                    selectedConsultationSlot.date === date &&
                    selectedConsultationSlot.start_time_utc === slot.start_time_utc;
                
                slotsHTML += `
                    <div class="col-12 col-md-6">
                        <div class="card slot-card h-100 ${isSelected ? 'active' : ''}" style="cursor: pointer;" data-slot-index="${index}">
                            <div class="card-body text-center">
                                <h5 class="card-title mb-1">${slot.start_time_display}</h5>
                                <p class="text-muted mb-0 small">to ${slot.end_time_display}</p>
                                <p class="text-muted mb-0 small mt-1">Duration: ${consultationDuration} minutes</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            slotsHTML += `
                </div>
                <div class="mt-3 d-none" id="nextToTattooSessionBtnContainer">
                    <button type="button" class="btn btn-primary w-100" id="nextToTattooSessionBtn">
                        Next: Select Tattoo Session <i class="ti ti-arrow-right ms-1"></i>
                    </button>
                </div>
            `;
            
            container.innerHTML = slotsHTML;
            
            // Attach click handler
            $(container).off('click', '.slot-card').on('click', '.slot-card', function() {
                const idx = $(this).data('slot-index');
                const slot = data.time_slots[idx];
                
                $('.slot-card').removeClass('active');
                $(this).addClass('active');
                
                selectedConsultationSlot = {
                    date: date,
                    start_time_utc: slot.start_time_utc,
                    end_time_utc: slot.end_time_utc,
                    start_time_display: slot.start_time_display,
                    end_time_display: slot.end_time_display
                };
                
                calculateMinimumTattooSessionDate();
                $('#nextToTattooSessionBtnContainer').removeClass('d-none');
            });
            
            // Handle next to tattoo session button
            $(document).off('click', '#nextToTattooSessionBtn').on('click', '#nextToTattooSessionBtn', function() {
                if (!selectedConsultationSlot) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Consultation Required',
                        text: 'Please select a consultation slot first.',
                        confirmButtonColor: '#0d6efd'
                    });
                    return;
                }
                
                // Update flow step and show calendar for tattoo session
                bookingFlowStep = 'tattoo_session';
                document.getElementById('slotsSection').classList.add('d-none');
                document.getElementById('calendarSection').classList.remove('d-none');
                document.getElementById('calendarTitle').textContent = 'Step 2: Select Tattoo Session Date';
                selectedDate = null;
                $('.calendar-day').removeClass('selected');
                $('#calendarNextButton').addClass('d-none');
                
                // Re-render calendar to show gap restrictions
                renderCalendar();
            });
            
            // Show next button if consultation slot was already selected
            if (selectedConsultationSlot && selectedConsultationSlot.date === date) {
                $('#nextToTattooSessionBtnContainer').removeClass('d-none');
            }
        }
        
        // Display tattoo session slots
        function displayTattooSessionSlots(data, date) {
            const container = document.getElementById('slotsContainer');
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('slotsTitle').textContent = `Select Tattoo Session Time Slot - ${formattedDate}`;
            
            const gapInfo = data.consultation_info || {};
            let slotsHTML = `
                <div class="mb-3">
                    ${selectedConsultationSlot ? `
                    <div class="alert alert-success mb-2">
                        <strong>Selected Consultation:</strong><br>
                        ${new Date(selectedConsultationSlot.date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}<br>
                        ${selectedConsultationSlot.start_time_display} - ${selectedConsultationSlot.end_time_display}
                    </div>
                    ` : ''}
                    <p class="text-muted mb-2">
                        <strong>Tattoo Session Duration:</strong> ${data.tattoo.session_time_h} hour(s)<br>
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
            `;
            
            container.innerHTML = slotsHTML;
            
            selectedTattooSessionSlot = null;
            document.getElementById('slotsNextButton').classList.add('d-none');
            
            // Attach click handler
            $(container).off('click', '.slot-card').on('click', '.slot-card', function() {
                const idx = $(this).data('slot-index');
                const slot = data.time_slots[idx];
                
                selectedTattooSessionSlot = {
                    date: date,
                    start_time_utc: slot.start_time_utc,
                    end_time_utc: slot.end_time_utc,
                    start_time_display: slot.start_time_display,
                    end_time_display: slot.end_time_display,
                };
                
                $('.slot-card').removeClass('active');
                $(this).addClass('active');
                
                document.getElementById('slotsNextButton').classList.remove('d-none');
            });
        }
        
        // Display regular slots (for non-separate consultation)
        function displaySlots(slots, date) {
            const container = document.getElementById('slotsContainer');
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('slotsTitle').textContent = `Step 2: Select Time Slot - ${formattedDate}`;
            
            let slotsHTML = `
                <div class="row g-2">
            `;
            
            slots.forEach((slot, index) => {
                const totalDuration = slot.total_duration_minutes ? (slot.total_duration_minutes / 60).toFixed(2) : (slot.duration_hours || 0);
                
                slotsHTML += `
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card slot-card h-100" style="cursor: pointer;" data-slot-index="${index}">
                            <div class="card-body text-center">
                                <h5 class="card-title mb-1">${slot.start_time_display || slot.start_time_local || 'N/A'}</h5>
                                <p class="text-muted mb-0 small">to ${slot.end_time_display || slot.end_time_local || 'N/A'}</p>
                                <p class="text-muted mb-0 small mt-1">Duration: ${totalDuration} hour(s)</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            slotsHTML += `
                </div>
            `;
            
            container.innerHTML = slotsHTML;
            
            selectedTattooSessionSlot = null;
            document.getElementById('slotsNextButton').classList.add('d-none');

            // Attach click handler
            $(container).off('click', '.slot-card').on('click', '.slot-card', function() {
                const idx = $(this).data('slot-index');
                const slot = slots[idx];
                selectedTattooSessionSlot = {
                    date,
                    start_time_utc: slot.start_time_utc,
                    end_time_utc: slot.end_time_utc,
                    start_time_display: slot.start_time_display || slot.start_time_local,
                    end_time_display: slot.end_time_display || slot.end_time_local,
                };

                $('.slot-card').removeClass('active');
                $(this).addClass('active');

                document.getElementById('slotsNextButton').classList.remove('d-none');
            });
        }

        // Back buttons
        $(document).on('click', '#backToCalendarBtn', function() {
            document.getElementById('slotsSection').classList.add('d-none');
            document.getElementById('calendarSection').classList.remove('d-none');
            
            // Restore selected date highlight if date was previously selected
            if (selectedDate) {
                $('.calendar-day').removeClass('selected');
                setTimeout(function() {
                    $(`.calendar-day[data-date="${selectedDate}"]`).addClass('selected');
                    $('#calendarNextButton').removeClass('d-none');
                }, 100);
            }
        });

        $(document).on('click', '#backToSlotsFromConfirmBtn', function() {
            document.getElementById('confirmSection').classList.add('d-none');
            document.getElementById('slotsSection').classList.remove('d-none');
        });

        // Next to confirm
        $(document).on('click', '#nextToConfirmBtn', function() {
            if (consultationInfo.is_separate && bookingFlowStep === 'tattoo_session') {
                if (!selectedTattooSessionSlot) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Time Slot Required',
                        text: 'Please select a tattoo session time slot first.',
                        confirmButtonColor: '#0d6efd'
                    });
                    return;
                }
            } else if (!selectedTattooSessionSlot) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Time Slot Required',
                    text: 'Please select a time slot first.',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }

            // Build summary
            const summaryEl = document.getElementById('confirmSummary');
            let summaryHTML = '<div class="selected-slot-info"><h6 class="mb-3"><i class="ti ti-calendar-event me-2"></i>New Booking Times</h6>';
            
            if (consultationInfo.is_separate && selectedConsultationSlot) {
                const consultationDate = new Date(selectedConsultationSlot.date + 'T00:00:00').toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                });
                summaryHTML += `
                    <div class="mb-3">
                        <strong>Consultation:</strong><br>
                        ${consultationDate}<br>
                        ${selectedConsultationSlot.start_time_display} - ${selectedConsultationSlot.end_time_display}
                    </div>
                `;
            }
            
            if (selectedTattooSessionSlot) {
                const tattooDate = new Date(selectedTattooSessionSlot.date + 'T00:00:00').toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                });
                summaryHTML += `
                    <div>
                        <strong>${consultationInfo.is_separate ? 'Tattoo Session' : 'Booking'}:</strong><br>
                        ${tattooDate}<br>
                        ${selectedTattooSessionSlot.start_time_display} - ${selectedTattooSessionSlot.end_time_display}
                    </div>
                `;
            }
            
            summaryHTML += `
                </div>
                <p class="text-muted mb-0">
                    When you confirm, your existing booking${consultationInfo.is_separate ? 's' : ''} will be moved to these new date${consultationInfo.is_separate ? 's' : ''} and time${consultationInfo.is_separate ? 's' : ''}.
                </p>
            `;
            
            summaryEl.innerHTML = summaryHTML;

            document.getElementById('slotsSection').classList.add('d-none');
            document.getElementById('confirmSection').classList.remove('d-none');
        });

        // Confirm reschedule
        $(document).on('click', '#confirmRescheduleBtn', function() {
            if (consultationInfo.is_separate) {
                if (!selectedConsultationSlot || !selectedTattooSessionSlot) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please select both consultation and tattoo session slots.',
                        confirmButtonColor: '#0d6efd'
                    });
                    return;
                }
            } else if (!selectedTattooSessionSlot) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selection Required',
                    text: 'No slot selected.',
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }

            // Show confirmation dialog
            Swal.fire({
                icon: 'question',
                title: 'Confirm Reschedule',
                text: 'Are you sure you want to reschedule this booking?',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reschedule it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                
                // Proceed with reschedule
                proceedWithReschedule();
            });
        });
        
        function proceedWithReschedule() {

            const btn = document.getElementById('confirmRescheduleBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            
            // Show loading alert
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we reschedule your booking.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Prepare request body
            const requestBody = {
                new_date: selectedTattooSessionSlot.date,
                new_start_time_utc: selectedTattooSessionSlot.start_time_utc,
                new_end_time_utc: selectedTattooSessionSlot.end_time_utc,
                reason: null,
            };
            
            // If separate consultation, include consultation reschedule data
            if (consultationInfo.is_separate && selectedConsultationSlot) {
                requestBody.consultation_date = selectedConsultationSlot.date;
                requestBody.consultation_start_time_utc = selectedConsultationSlot.start_time_utc;
                requestBody.consultation_end_time_utc = selectedConsultationSlot.end_time_utc;
            }

            fetch(`/api/bookings/${bookingId}/reschedule`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(requestBody),
            })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Booking Rescheduled!',
                            html: 'Your booking has been moved to the new date and time.',
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Back to My Bookings'
                        }).then(() => {
                            window.location.href = '{{ route('bookings.index') }}';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Reschedule Failed',
                            text: data.message || 'Failed to reschedule booking. Please try again.',
                            confirmButtonColor: '#dc3545'
                        });
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-calendar-check me-1"></i>Confirm Reschedule';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-calendar-check me-1"></i>Confirm Reschedule';
                });
        }

        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            try {
                renderCalendar();
            } catch (error) {
                console.error('Error rendering calendar:', error);
                const calendarEl = document.getElementById('availability-calendar');
                if (calendarEl) {
                    calendarEl.innerHTML = `
                        <div class="alert alert-danger">
                            <h6>Error loading calendar</h6>
                            <p class="mb-0">${error.message}</p>
                            <button class="btn btn-primary btn-sm mt-2" onclick="location.reload()">Refresh Page</button>
                        </div>
                    `;
                }
            }
        });
    </script>
</body>
</html>

