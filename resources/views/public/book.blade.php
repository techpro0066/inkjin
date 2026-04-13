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
        
        .image-uploads-container {
            margin-bottom: 1rem;
        }
        
        .image-upload-item {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .image-upload-item .remove-image-btn {
            font-size: 0.875rem;
        }
        
        .image-uploads-list {
            margin-top: 0.5rem;
        }
        
        .add-more-image-btn {
            margin-top: 0.5rem;
        }
        
        .image-upload-item.border-danger {
            border-width: 2px !important;
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
        
        /* Booking sections */
        .booking-card {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        .booking-card.d-none {
            display: none !important;
        }
        
        #slotsContainer, #questionsContainer, #paymentContainer {
            min-height: 200px;
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
                        <a href="{{ route('dashboard.tattoo.show', ['id' => $tattoo['tattoo_id']]) }}" class="btn btn-outline-secondary btn-sm">
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
        <div class="booking-card" id="calendarSection">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0" id="calendarTitle">
                    @if(($consultationInfo['is_separate'] ?? false))
                        Step 1: Select Consultation Date
                    @else
                        Select Date
                    @endif
                </h5>
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="backToConsultationBtn">
                    <i class="ti ti-arrow-left me-1"></i> Back to Consultation
                </button>
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

        <!-- Time Slots Section -->
        <div class="booking-card d-none" id="slotsSection">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0" id="slotsTitle">Select Time Slot</h5>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="backToCalendarBtn">
                    <i class="ti ti-arrow-left me-1"></i> Change Date
                </button>
            </div>
            <div id="slotsContainer">
                <!-- Time slots will be loaded here -->
                    </div>
            <div class="mt-3 d-none" id="slotsNextButton">
                <button type="button" class="btn btn-primary w-100" id="nextToQuestionsFromSlotsBtn">
                    Next: Answer Questions <i class="ti ti-arrow-right ms-1"></i>
                </button>
                </div>
            </div>
    
        <!-- Questions Section -->
        <div class="booking-card d-none" id="questionsSection">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Booking Questions</h5>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="backToSlotsBtn">
                    <i class="ti ti-arrow-left me-1"></i> Change Time
                </button>
            </div>
            <div id="questionsContainer">
                <!-- Questions form will be loaded here -->
        </div>
    </div>
    
        <!-- Payment Section -->
        <div class="booking-card d-none" id="paymentSection">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Complete Payment</h5>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="backToQuestionsBtn">
                    <i class="ti ti-arrow-left me-1"></i> Back to Questions
                </button>
            </div>
            <div id="paymentContainer">
                <!-- Payment form will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingDetailsModalLabel">
                        <i class="ti ti-info-circle me-2"></i>Booking Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bookingDetailsContent">
                    <!-- Details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Dropify JS -->
    <script src="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/js/dropify.min.js"></script>
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <script>
        // CSRF Token for AJAX requests
        const csrfToken = '{{ csrf_token() }}';
        
        // User data (if authenticated)
        @auth
        const currentUser = {
            id: {{ auth()->id() }},
            name: @json(auth()->user()->name),
            email: @json(auth()->user()->email),
            isAuthenticated: true
        };
        @else
        const currentUser = {
            isAuthenticated: false
        };
        @endauth
        
        // Consultation info from server
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
        @endphp
        const consultationInfo = @json($finalConsultationInfo);
        
        // Booking flow state
        let bookingFlowStep = consultationInfo.is_separate ? 'consultation' : 'tattoo_session'; // 'consultation', 'tattoo_session', 'questions', 'payment'
        let selectedConsultationSlot = null;
        let selectedTattooSessionSlot = null;
        let minimumTattooSessionDate = null;
        
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
        
        // Cache for slot availability checks (to avoid multiple API calls for same date)
        const dateSlotAvailabilityCache = {
            // Format: 'date-flow': { hasSlots: boolean, checking: boolean }
            // Example: '2025-12-17-consultation': { hasSlots: true, checking: false }
        };
        
        // Function to clear slot availability cache (useful when flow changes)
        function clearSlotAvailabilityCache() {
            Object.keys(dateSlotAvailabilityCache).forEach(key => delete dateSlotAvailabilityCache[key]);
        }
        
        // Function to check if a date has available slots
        function checkDateSlotAvailability(dateKey, callback) {
            // Determine which API endpoint to use based on booking flow
            let cacheKey, apiUrl, apiData;
            
            if (bookingFlowStep === 'consultation') {
                cacheKey = `${dateKey}-consultation`;
                apiUrl = '{{ route('api.consultation.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}';
                apiData = { date: dateKey };
            } else if (bookingFlowStep === 'tattoo_session' && selectedConsultationSlot) {
                cacheKey = `${dateKey}-tattoo_session-${selectedConsultationSlot.date}`;
                apiUrl = '{{ route('api.tattoo-session.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}';
                apiData = {
                    date: dateKey,
                    consultation_date: selectedConsultationSlot.date,
                    consultation_start_time_utc: selectedConsultationSlot.start_time_utc,
                    consultation_end_time_utc: selectedConsultationSlot.end_time_utc
                };
            } else {
                // Regular booking flow
                cacheKey = `${dateKey}-regular`;
                apiUrl = '{{ route('api.availability.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}';
                apiData = { date: dateKey };
            }
            
            // Check cache first
            if (dateSlotAvailabilityCache[cacheKey]) {
                if (dateSlotAvailabilityCache[cacheKey].checking) {
                    // Already checking, wait for it
                    setTimeout(() => checkDateSlotAvailability(dateKey, callback), 100);
                    return;
                }
                callback(dateSlotAvailabilityCache[cacheKey].hasSlots);
                return;
            }
            
            // Mark as checking
            dateSlotAvailabilityCache[cacheKey] = { hasSlots: false, checking: true };
            
            // Make API call
            $.ajax({
                url: apiUrl,
                method: 'GET',
                data: apiData,
                success: function(response) {
                    // Check if response is valid
                    if (!response || typeof response.success === 'undefined') {
                        // Invalid response format - don't update date status
                        console.warn('Invalid response format for date:', dateKey, response);
                        delete dateSlotAvailabilityCache[cacheKey];
                        return; // Don't call callback, leave date as is
                    }
                    
                    // Check if response has slots
                    const hasSlots = response.success && 
                                    response.time_slots && 
                                    Array.isArray(response.time_slots) && 
                                    response.time_slots.length > 0;
                    
                    // Update cache
                    dateSlotAvailabilityCache[cacheKey] = { hasSlots: hasSlots, checking: false };
                    
                    // Only call callback with false if we're certain there are no slots
                    // If response.success is false but it's not an error (e.g., date unavailable), 
                    // we should still mark as unavailable
                    if (response.success === false) {
                        // API explicitly says no slots available
                        callback(false);
                    } else if (hasSlots === false) {
                        // Response is successful but has no slots
                        callback(false);
                    } else {
                        // Has slots
                        callback(true);
                    }
                },
                error: function(xhr, status, error) {
                    // On error, don't mark as unavailable - keep current state
                    // This prevents dates from turning red due to API errors
                    console.warn('Failed to check slot availability for date:', dateKey, error);
                    
                    // Remove from cache so it can be retried later
                    delete dateSlotAvailabilityCache[cacheKey];
                    
                    // Don't call callback with false - this would mark date as unavailable
                    // Instead, just remove the checking flag
                    // The date will remain in its current state (available/unavailable based on weekly schedule)
                }
            });
        }
        
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
            
            // Blocked date ranges (full-day unavailability) take precedence
            const inBlockedRange = availabilityData.overrides.some(o =>
                dateKey >= o.start_date && dateKey <= o.end_date
            );
            if (inBlockedRange) {
                return 'unavailable';
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
            if (!calendarEl) {
                console.error('Calendar element not found!');
                return;
            }
            
            // Check if availability data exists
            if (!availabilityData || (!availabilityData.availableDates && !availabilityData.weeklyAvailability)) {
                console.error('Availability data not loaded:', availabilityData);
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
            
            // Find first available date to navigate to that month
            if (availabilityData.availableDates && availabilityData.availableDates.length > 0) {
                // Sort available dates to find the earliest one
                const sortedDates = availabilityData.availableDates
                    .filter(dateKey => {
                        // Only consider future dates
                        const date = new Date(dateKey + 'T00:00:00');
                        return date >= new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    })
                    .sort();
                
                if (sortedDates.length > 0) {
                    // Get the first available date
                    const firstAvailableDate = new Date(sortedDates[0] + 'T00:00:00');
                    currentMonth = firstAvailableDate.getMonth();
                    currentYear = firstAvailableDate.getFullYear();
                }
            } else {
                // Check weekly availability to find first available month
                // Look ahead up to 12 months for availability
                for (let monthsAhead = 0; monthsAhead < 12; monthsAhead++) {
                    const checkDate = new Date(today.getFullYear(), today.getMonth() + monthsAhead, 1);
                    const lastDayOfMonth = new Date(checkDate.getFullYear(), checkDate.getMonth() + 1, 0);
                    
                    // Check if any day in this month is available
                    for (let day = 1; day <= lastDayOfMonth.getDate(); day++) {
                        const testDate = new Date(checkDate.getFullYear(), checkDate.getMonth(), day);
                        // Skip past dates
                        if (testDate < new Date(today.getFullYear(), today.getMonth(), today.getDate())) {
                            continue;
                        }
                        
                        const availability = checkDateAvailability(testDate);
                        if (availability === 'available') {
                            currentMonth = checkDate.getMonth();
                            currentYear = checkDate.getFullYear();
                            monthsAhead = 12; // Break outer loop
                            break;
                        }
                    }
                    if (monthsAhead === 12) break; // Found availability, exit
                }
            }
            
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
                        // If on tattoo session step and consultation is selected, disable dates appropriately
                        let shouldDisable = false;
                        if (bookingFlowStep === 'tattoo_session' && selectedConsultationSlot) {
                            const consultationDate = new Date(selectedConsultationSlot.date + 'T00:00:00');
                            const checkDate = new Date(dateKey + 'T00:00:00');
                            
                            // Always disable dates before consultation date
                            if (checkDate < consultationDate) {
                                shouldDisable = true;
                            }
                            
                            // If gap is required, also disable dates before or equal to minimum date (which includes gap period)
                            if (!shouldDisable && minimumTattooSessionDate) {
                                const minDate = new Date(minimumTattooSessionDate.split(' ')[0] + 'T00:00:00');
                                // Disable dates that are before or equal to minimum date (includes consultation date and all gap days)
                                // For example: consultation Dec 17, gap 1 day -> minimum is Dec 18, so disable Dec 17 and Dec 18
                                if (checkDate <= minDate) {
                                    shouldDisable = true;
                                }
                            } else if (!shouldDisable && !minimumTattooSessionDate) {
                                // No gap requirement, but still disable consultation date itself
                                // (can't book tattoo session on same day as consultation if no gap)
                                if (checkDate.getTime() === consultationDate.getTime()) {
                                    shouldDisable = true;
                                }
                            }
                        }
                        
                        if (shouldDisable) {
                            dayClass += ' unavailable';
                        disabled = 'disabled';
                    } else {
                        // Check availability (works for all future dates)
                        const availability = checkDateAvailability(date);
                        if (availability === 'available') {
                            dayClass += ' available';
                                // Note: Slot availability will be checked asynchronously after calendar renders
                        } else {
                            dayClass += ' unavailable';
                            disabled = 'disabled';
                            }
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
                
                // After calendar is rendered, check slot availability for visible dates
                // This will disable dates that have no available slots
                // TEMPORARILY DISABLED - Uncomment below to enable slot availability checking
                // Note: This feature checks each date via API and may cause dates to turn red
                // if API calls fail or return empty slots. Enable only after verifying API endpoints work correctly.
                /*
                setTimeout(function() {
                    // Only check slot availability if we have the necessary data
                    // Skip if we're in a flow that requires consultation slot selection
                    if (bookingFlowStep !== 'tattoo_session' || selectedConsultationSlot) {
                        checkSlotAvailabilityForVisibleDates();
                    } else if (bookingFlowStep === 'consultation' || !consultationInfo.is_separate) {
                        // For consultation or regular booking, check availability
                        checkSlotAvailabilityForVisibleDates();
                    }
                }, 500);
                */
            }
            
            // Function to check slot availability for visible dates in the calendar
            function checkSlotAvailabilityForVisibleDates() {
                const calendarDays = document.querySelectorAll('.calendar-day:not(.empty):not(.past)');
                const datesToCheck = [];
                const today = new Date();
                const maxDaysToCheck = 60; // Only check dates within next 60 days
                
                calendarDays.forEach(function(dayEl) {
                    const dateKey = dayEl.getAttribute('data-date');
                    if (dateKey && !dayEl.hasAttribute('disabled')) {
                        // Check if date is within reasonable range (next 60 days)
                        const dateParts = dateKey.split('-');
                        const checkDate = new Date(parseInt(dateParts[0]), parseInt(dateParts[1]) - 1, parseInt(dateParts[2]));
                        const daysDiff = Math.ceil((checkDate - today) / (1000 * 60 * 60 * 24));
                        
                        // Only check dates that are marked as available and within range
                        if (dayEl.classList.contains('available') && daysDiff >= 0 && daysDiff <= maxDaysToCheck) {
                            datesToCheck.push({ element: dayEl, dateKey: dateKey });
                        }
                    }
                });
                
                // If no dates to check, return early
                if (datesToCheck.length === 0) {
                    return;
                }
                
                // Check slot availability for each date
                // Limit concurrent requests and add delays to avoid overwhelming the server
                const maxConcurrent = 3; // Check max 3 dates at a time
                const batchDelay = 800; // Wait 800ms between batches
                
                // Process dates in batches
                for (let i = 0; i < datesToCheck.length; i += maxConcurrent) {
                    const batch = datesToCheck.slice(i, i + maxConcurrent);
                    
                    batch.forEach(function(item, batchIndex) {
                        setTimeout(function() {
                            checkDateSlotAvailability(item.dateKey, function(hasSlots) {
                                // Only update if callback was called with explicit false (not on error)
                                // hasSlots will be undefined if error occurred, so we skip updating
                                if (hasSlots === false) {
                                    // Explicitly no slots available, disable this date
                                    item.element.classList.remove('available');
                                    item.element.classList.add('unavailable');
                                    item.element.setAttribute('disabled', 'disabled');
                                }
                                // If hasSlots is true or undefined (error), leave date as is
                            });
                        }, (i * batchDelay) + (batchIndex * 150)); // Stagger within batch too
                    });
                }
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
        
        // Set up global back button handlers
        function setupBackButtonHandlers() {
            // Back to calendar button (from slots section)
            $(document).off('click', '#backToCalendarBtn').on('click', '#backToCalendarBtn', function() {
                document.getElementById('slotsSection').classList.add('d-none');
                document.getElementById('calendarSection').classList.remove('d-none');
                
                // If going back to consultation calendar, clear gap restrictions and re-render
                if (bookingFlowStep === 'consultation') {
                    minimumTattooSessionDate = null;
                    // Ensure booking flow step is set correctly
                    bookingFlowStep = 'consultation';
                    // Re-render calendar to remove gap-based date disabling
                    renderCalendar();
                }
                
                // Determine which date to highlight based on current flow step
                let dateToHighlight = null;
                let shouldShowNextButton = false;
                
                if (bookingFlowStep === 'tattoo_session') {
                    // For tattoo session step, only highlight if tattoo session slot is selected
                    // Don't use window.selectedDate from consultation step - user must select a new date
                    if (selectedTattooSessionSlot) {
                        dateToHighlight = selectedTattooSessionSlot.date;
                        shouldShowNextButton = true;
                    }
                    // Note: window.selectedDate is cleared in proceedToTattooSessionStep() if no tattoo session slot exists
                    // So we don't need to check it here - user must click a date on tattoo session calendar
                } else if (bookingFlowStep === 'consultation') {
                    // For consultation step, highlight if consultation slot is selected or date is selected
                    if (selectedConsultationSlot) {
                        dateToHighlight = selectedConsultationSlot.date;
                        shouldShowNextButton = true;
                    } else if (window.selectedDate) {
                        dateToHighlight = window.selectedDate;
                        shouldShowNextButton = true;
                    }
                } else {
                    // Regular booking flow
                    if (window.selectedDate) {
                        dateToHighlight = window.selectedDate;
                        shouldShowNextButton = true;
                    }
                }
                
                // Restore selected date highlight if date was previously selected
                if (dateToHighlight) {
                    window.selectedDate = dateToHighlight;
                    $('.calendar-day').removeClass('border-primary');
                    // Use setTimeout to ensure calendar is rendered before trying to highlight
                    setTimeout(function() {
                        const dayElement = $(`.calendar-day[data-date="${dateToHighlight}"]`);
                        if (dayElement.length > 0 && !dayElement.hasClass('unavailable') && !dayElement.attr('disabled')) {
                            dayElement.addClass('border-primary');
                            if (shouldShowNextButton) {
                                $('#calendarNextButton').removeClass('d-none');
                            }
                        } else {
                            // Date is not available, clear selection
                            window.selectedDate = null;
                            $('#calendarNextButton').addClass('d-none');
                        }
                    }, 100);
                } else {
                    // No date selected for current step, hide Next button
                    $('.calendar-day').removeClass('border-primary');
                    $('#calendarNextButton').addClass('d-none');
                }
                
                // Don't clear selectedSlot, selectedConsultationSlot, or selectedTattooSessionSlot
                // They should remain so user can go back and see their selection
                
                document.getElementById('calendarSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            
            // Back to slots button (from questions section)
            $(document).off('click', '#backToSlotsBtn').on('click', '#backToSlotsBtn', function() {
                document.getElementById('questionsSection').classList.add('d-none');
                document.getElementById('slotsSection').classList.remove('d-none');
                document.getElementById('slotsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            
            // Back to questions button (from payment section)
            $(document).off('click', '#backToQuestionsBtn').on('click', '#backToQuestionsBtn', function() {
                document.getElementById('paymentSection').classList.add('d-none');
                document.getElementById('questionsSection').classList.remove('d-none');
                document.getElementById('questionsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            
            // Back to consultation button (from tattoo session date selection)
            $(document).off('click', '#backToConsultationBtn').on('click', '#backToConsultationBtn', function() {
                if (!selectedConsultationSlot) {
                    alert('No consultation slot selected. Please start over.');
                    return;
                }
                
                // Reset booking flow step
                bookingFlowStep = 'consultation';
                
                // Clear minimum tattoo session date so calendar doesn't disable dates
                minimumTattooSessionDate = null;
                
                // Set the selected date for calendar highlighting
                window.selectedDate = selectedConsultationSlot.date;
                
                // Show calendar section first so it can be re-rendered
                document.getElementById('calendarSection').classList.remove('d-none');
                
                // Re-render calendar to remove gap-based date disabling
                renderCalendar();
                
                // After calendar is rendered, restore selected date highlight
                setTimeout(function() {
                    if (window.selectedDate) {
                        $('.calendar-day').removeClass('border-primary');
                        $(`.calendar-day[data-date="${window.selectedDate}"]`).addClass('border-primary');
                        $('#calendarNextButton').removeClass('d-none');
                    }
                }, 100);
                
                // Hide calendar, show slots section
                document.getElementById('calendarSection').classList.add('d-none');
                document.getElementById('slotsSection').classList.remove('d-none');
                
                // Hide back button
                $('#backToConsultationBtn').addClass('d-none');
                
                // Reset calendar title
                $('#calendarTitle').text('Step 1: Select Consultation Date');
                
                // Reload consultation slots for the selected consultation date
                loadConsultationSlots(selectedConsultationSlot.date);
            });
        }
        
        // Initialize calendar when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set up back button handlers
            setupBackButtonHandlers();
            try {
                renderCalendar();
                
                // Check if calendar was rendered after a short delay
                setTimeout(function() {
                    const calendarEl = document.getElementById('availability-calendar');
                    if (calendarEl && calendarEl.innerHTML.trim() === '') {
                        console.error('Calendar failed to render');
                        calendarEl.innerHTML = `
                            <div class="alert alert-warning">
                                <h6>Unable to load calendar</h6>
                                <p class="mb-0">Please refresh the page or contact support if the problem persists.</p>
                                <button class="btn btn-primary btn-sm mt-2" onclick="location.reload()">Refresh Page</button>
                            </div>
                        `;
                    }
                }, 1000);
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
        
        $(document).on('click', '.calendar-day.available:not([disabled])', function() {
            const date = $(this).data('date');
            if (!date) return;
            
            // Remove previous selection highlight
            $('.calendar-day').removeClass('border-primary');
            // Highlight selected date
            $(this).addClass('border-primary');
            
            // Store selected date
            window.selectedDate = date;
            
            // Show Next button only if date is selected
            // This ensures button is only visible when user actually selects a date
            if (date) {
                $('#calendarNextButton').removeClass('d-none');
            } else {
                $('#calendarNextButton').addClass('d-none');
            }
        });
        
        // Handle Next button from calendar
        $(document).off('click', '#nextToSlotsBtn').on('click', '#nextToSlotsBtn', function() {
            const date = window.selectedDate;
            if (!date) {
                alert('Please select a date first.');
                return;
            }
            
            // Check if separate consultation flow
            if (consultationInfo.is_separate) {
                if (bookingFlowStep === 'consultation') {
                    loadConsultationSlots(date);
                } else if (bookingFlowStep === 'tattoo_session') {
                    // Check if date is before minimum date
                    if (minimumTattooSessionDate) {
                        const selectedDate = new Date(date + 'T00:00:00');
                        const minDate = new Date(minimumTattooSessionDate.split(' ')[0] + 'T00:00:00');
                        if (selectedDate < minDate) {
                            alert(`Please select a date on or after ${minDate.toLocaleDateString()}. Minimum gap required: ${consultationInfo.gap_value} ${consultationInfo.gap_unit} after consultation.`);
                            return;
                        }
                    }
                    loadTattooSessionSlots(date);
                }
            } else {
                // Regular flow (combined or no consultation)
                loadRegularSlots(date);
            }
        });
        
        function loadConsultationSlots(date) {
            // Ensure booking flow step is set to consultation
            bookingFlowStep = 'consultation';
            
            // Hide calendar, show slots section
            document.getElementById('calendarSection').classList.add('d-none');
            document.getElementById('slotsSection').classList.remove('d-none');
            document.getElementById('slotsTitle').textContent = 'Select Consultation Time Slot';
            document.getElementById('slotsContainer').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            document.getElementById('slotsNextButton').classList.add('d-none');
            
            // Scroll to slots section
            document.getElementById('slotsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            $.ajax({
                url: '{{ route('api.consultation.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                method: 'GET',
                data: { date: date },
                success: function(response) {
                    if (response.success) {
                        displayConsultationSlots(response, date);
                    } else {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-triangle me-2"></i>
                                ${response.message || 'No consultation slots available'}
                            </div>
                        `;
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Failed to load consultation slots. Please try again.';
                    document.getElementById('slotsContainer').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ti ti-x-circle me-2"></i>
                            ${errorMessage}
                        </div>
                    `;
                }
            });
        }
        
        function loadTattooSessionSlots(date) {
            if (!selectedConsultationSlot) {
                alert('Please select a consultation slot first.');
                return;
            }
            
            // Hide calendar, show slots section
            document.getElementById('calendarSection').classList.add('d-none');
            document.getElementById('slotsSection').classList.remove('d-none');
            document.getElementById('slotsTitle').textContent = 'Select Tattoo Session Time Slot';
            document.getElementById('slotsContainer').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            document.getElementById('slotsNextButton').classList.add('d-none');
            
            // Scroll to slots section
            document.getElementById('slotsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            $.ajax({
                url: '{{ route('api.tattoo-session.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                method: 'GET',
                data: {
                    date: date,
                    consultation_date: selectedConsultationSlot.date,
                    consultation_start_time_utc: selectedConsultationSlot.start_time_utc,
                    consultation_end_time_utc: selectedConsultationSlot.end_time_utc
                },
                success: function(response) {
                    if (response.success) {
                        displayTattooSessionSlots(response, date);
                    } else {
                        document.getElementById('slotsContainer').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-triangle me-2"></i>
                                ${response.message || 'No tattoo session slots available'}
                            </div>
                        `;
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Failed to load tattoo session slots. Please try again.';
                    document.getElementById('slotsContainer').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ti ti-x-circle me-2"></i>
                            ${errorMessage}
                        </div>
                    `;
                }
            });
        }
        
        function loadRegularSlots(date) {
            // Hide calendar, show slots section
            document.getElementById('calendarSection').classList.add('d-none');
            document.getElementById('slotsSection').classList.remove('d-none');
            document.getElementById('slotsTitle').textContent = 'Select Time Slot';
            document.getElementById('slotsContainer').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            document.getElementById('slotsNextButton').classList.add('d-none');
            
            // Scroll to slots section
            document.getElementById('slotsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            $.ajax({
                url: '{{ route('api.availability.slots', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                method: 'GET',
                data: { date: date },
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
        }
        
        function displayTimeSlots(data, date) {
            const container = document.getElementById('slotsContainer');
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('slotsTitle').textContent = `Select Time Slot - ${formattedDate}`;
            
            // Set up back to calendar button handler
            $(document).off('click', '#backToCalendarBtn').on('click', '#backToCalendarBtn', function() {
                document.getElementById('slotsSection').classList.add('d-none');
                document.getElementById('calendarSection').classList.remove('d-none');
                
                // Restore selected date highlight if date was previously selected
                if (window.selectedDate) {
                    $('.calendar-day').removeClass('border-primary');
                    // Use setTimeout to ensure calendar is rendered before trying to highlight
                    setTimeout(function() {
                        $(`.calendar-day[data-date="${window.selectedDate}"]`).addClass('border-primary');
                        $('#calendarNextButton').removeClass('d-none');
                    }, 100);
                } else {
                    // Only clear if no date was selected
                    $('.calendar-day').removeClass('border-primary');
                    $('#calendarNextButton').addClass('d-none');
                }
                
                document.getElementById('calendarSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            
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
            
            let consultationDurationHTML = '';
            if (data.consultation_info && data.consultation_info.is_combined) {
                consultationDurationHTML = '<br><strong>Consultation Duration:</strong> ' + data.consultation_info.consultation_duration_minutes + ' minutes<br><strong>Total Duration:</strong> ' + (data.tattoo.session_time_h + (data.consultation_info.consultation_duration_minutes / 60)).toFixed(2) + ' hour(s)';
            }
            
            let consultationAlertHTML = '';
            if (data.consultation_info && data.consultation_info.is_combined) {
                consultationAlertHTML = '<div class="alert alert-info mb-2"><i class="ti ti-info-circle me-2"></i><strong>Consultation Included:</strong> This booking includes a consultation session.</div>';
            }
            
            let slotsHTML = `
                <div class="mb-3">
                    <p class="text-muted mb-2">
                        <strong>Session Duration:</strong> ${data.tattoo.session_time_h} hour(s)
                        ${consultationDurationHTML}
                        <br><strong>Timezone:</strong> ${data.timezone}
                    </p>
                    ${consultationAlertHTML}
                </div>
                <div class="row g-2">
            `;
            
            data.time_slots.forEach((slot, index) => {
                const totalDuration = slot.total_duration_minutes ? (slot.total_duration_minutes / 60).toFixed(2) : slot.duration_hours;
                
                slotsHTML += `
                    <div class="col-12 col-md-6">
                        <div class="card slot-card h-100" style="cursor: pointer;" data-slot-index="${index}" data-slot-date="${date}">
                            <div class="card-body text-center">
                                <h5 class="card-title mb-1">${slot.start_time_display}</h5>
                                <p class="text-muted mb-0 small">to ${slot.end_time_display}</p>
                                <p class="text-muted mb-0 small mt-1">Duration: ${totalDuration} hour(s)</p>
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
                        ${(window.bookingData?.questions || []).length > 0 ? 'Next: Answer Questions' : 'Next: Payment'} <i class="ti ti-arrow-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary w-100 mt-2" onclick="showBookingDetails('slots')">
                        <i class="ti ti-eye me-1"></i> View Details
                    </button>
                </div>
            `;
            
            container.innerHTML = slotsHTML;
            
            // Store questions data globally
            window.bookingData = {
                questions: data.questions || [],
                date: date,
                tattoo: data.tattoo,
                consultationInfo: consultationInfo,
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
                
                // Show Next button and update text based on whether questions exist
                const questions = window.bookingData?.questions || [];
                const nextButton = $('#nextToQuestionsBtn');
                if (questions.length > 0) {
                    nextButton.html('Next: Answer Questions <i class="ti ti-arrow-right ms-1"></i>');
                } else {
                    nextButton.html('Next: Payment <i class="ti ti-arrow-right ms-1"></i>');
                }
                $('#nextButtonContainer').removeClass('d-none');
            });
            
            // Handle Next button click
            $(document).off('click', '#nextToQuestionsBtn').on('click', '#nextToQuestionsBtn', function() {
                if (!window.selectedSlot) {
                    alert('Please select a time slot first.');
                    return;
                }
                
                // Check if there are questions
                const questions = window.bookingData?.questions || [];
                if (!questions || questions.length === 0) {
                    // No questions, skip directly to payment
                    proceedToPaymentStep();
                } else {
                    // Has questions, proceed to questions step
                    document.getElementById('slotsSection').classList.add('d-none');
                    proceedToQuestionsStep();
                }
            });
        }
        
        function displayConsultationSlots(data, date) {
            const container = document.getElementById('slotsContainer');
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('slotsTitle').textContent = `Select Consultation Time Slot - ${formattedDate}`;
            
            // Ensure back button handlers are set up
            setupBackButtonHandlers();
            
            if (data.is_unavailable) {
                container.innerHTML = `<div class="alert alert-warning"><i class="ti ti-calendar-x me-2"></i>Artist is unavailable on this date.</div>`;
                return;
            }
            
            if (!data.time_slots || data.time_slots.length === 0) {
                container.innerHTML = `<div class="alert alert-info"><i class="ti ti-info-circle me-2"></i>No available consultation slots for this date.</div>`;
                return;
            }
            
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
                // Check if this slot matches the previously selected consultation slot
                const isSelected = selectedConsultationSlot && 
                    selectedConsultationSlot.date === date &&
                    selectedConsultationSlot.start_time_utc === slot.start_time_utc &&
                    selectedConsultationSlot.end_time_utc === slot.end_time_utc;
                
                const selectedClass = isSelected ? 'border-primary' : '';
                
                slotsHTML += `
                    <div class="col-12 col-md-6">
                        <div class="card slot-card h-100 ${selectedClass}" style="cursor: pointer;" data-slot-index="${index}" data-slot-date="${date}">
                            <div class="card-body text-center">
                                <h5 class="card-title mb-1">${slot.start_time_display}</h5>
                                <p class="text-muted mb-0 small">to ${slot.end_time_display}</p>
                                <p class="text-muted mb-0 small mt-1">Duration: ${consultationDuration} minutes</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Check if we have a previously selected slot to show Next button
            const hasSelectedSlot = selectedConsultationSlot && 
                selectedConsultationSlot.date === date &&
                data.time_slots.some(slot => 
                    slot.start_time_utc === selectedConsultationSlot.start_time_utc &&
                    slot.end_time_utc === selectedConsultationSlot.end_time_utc
                );
            
            slotsHTML += `
                </div>
                <div class="mt-3 ${hasSelectedSlot ? '' : 'd-none'}" id="nextButtonContainer">
                    <button type="button" class="btn btn-primary w-100" id="nextToTattooSessionBtn">
                        Next: Select Tattoo Session <i class="ti ti-arrow-right ms-1"></i>
                    </button>
                </div>
            `;
            
            container.innerHTML = slotsHTML;
            
            // If we have a previously selected slot, ensure minimum date is calculated
            if (hasSelectedSlot) {
                calculateMinimumTattooSessionDate();
            }
            
            // Add click handlers
            $(document).off('click', '.slot-card').on('click', '.slot-card', function() {
                const slotIndex = $(this).data('slot-index');
                const slot = data.time_slots[slotIndex];
                
                $('.slot-card').removeClass('border-primary');
                $(this).addClass('border-primary');
                
                selectedConsultationSlot = {
                    date: date,
                    start_time_utc: slot.start_time_utc,
                    end_time_utc: slot.end_time_utc,
                    start_time_display: slot.start_time_display,
                    end_time_display: slot.end_time_display
                };
                
                calculateMinimumTattooSessionDate();
                $('#nextButtonContainer').removeClass('d-none');
            });
            
            $('#nextToTattooSessionBtn').off('click').on('click', function() {
                if (!selectedConsultationSlot) {
                    alert('Please select a consultation slot first.');
                    return;
                }
                // Hide slots section temporarily, will show calendar again
                document.getElementById('slotsSection').classList.add('d-none');
                proceedToTattooSessionStep();
            });
        }
        
        function displayTattooSessionSlots(data, date) {
            const container = document.getElementById('slotsContainer');
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('slotsTitle').textContent = `Select Tattoo Session Time Slot - ${formattedDate}`;
            
            // Ensure back button handlers are set up
            setupBackButtonHandlers();
            
            if (data.is_unavailable) {
                container.innerHTML = `<div class="alert alert-warning"><i class="ti ti-calendar-x me-2"></i>Artist is unavailable on this date.</div>`;
                return;
            }
            
            if (!data.time_slots || data.time_slots.length === 0) {
                container.innerHTML = `<div class="alert alert-info"><i class="ti ti-info-circle me-2"></i>No available tattoo session slots for this date.</div>`;
                return;
            }
            
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
                    ${gapInfo.gap_required ? `
                    <div class="alert alert-info mb-2">
                        <i class="ti ti-clock me-2"></i>
                        <strong>Minimum gap:</strong> ${gapInfo.gap_value} ${gapInfo.gap_unit} after consultation<br>
                        You can book from ${gapInfo.minimum_tattoo_session_date ? new Date(gapInfo.minimum_tattoo_session_date + 'T00:00:00').toLocaleDateString() : 'consultation end time'} onwards
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
                // Check if this slot matches the previously selected tattoo session slot
                const isSelected = selectedTattooSessionSlot && 
                    selectedTattooSessionSlot.date === date &&
                    selectedTattooSessionSlot.start_time_utc === slot.start_time_utc &&
                    selectedTattooSessionSlot.end_time_utc === slot.end_time_utc;
                
                const selectedClass = isSelected ? 'border-primary' : '';
                
                slotsHTML += `
                    <div class="col-12 col-md-6">
                        <div class="card slot-card h-100 ${selectedClass}" style="cursor: pointer;" data-slot-index="${index}" data-slot-date="${date}">
                            <div class="card-body text-center">
                                <h5 class="card-title mb-1">${slot.start_time_display}</h5>
                                <p class="text-muted mb-0 small">to ${slot.end_time_display}</p>
                                <p class="text-muted mb-0 small mt-1">Duration: ${slot.duration_hours} hour(s)</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Check if we have a previously selected slot to show Next button
            const hasSelectedSlot = selectedTattooSessionSlot && 
                selectedTattooSessionSlot.date === date &&
                data.time_slots.some(slot => 
                    slot.start_time_utc === selectedTattooSessionSlot.start_time_utc &&
                    slot.end_time_utc === selectedTattooSessionSlot.end_time_utc
                );
            
            const questions = data.questions || [];
            const nextButtonText = questions.length > 0 ? 'Next: Answer Questions' : 'Next: Payment';
            
            slotsHTML += `
                </div>
                <div class="mt-3 ${hasSelectedSlot ? '' : 'd-none'}" id="nextButtonContainer">
                    <button type="button" class="btn btn-primary w-100" id="nextToQuestionsBtn">
                        ${nextButtonText} <i class="ti ti-arrow-right ms-1"></i>
                    </button>
                </div>
            `;
            
            container.innerHTML = slotsHTML;
            
            // Store questions data globally
            if (!window.bookingData) {
                window.bookingData = {};
            }
            window.bookingData.questions = data.questions || [];
            window.bookingData.tattoo = data.tattoo;
            window.bookingData.artist = data.artist;
            window.bookingData.date = date;
            
            // Add click handlers
            $(document).off('click', '.slot-card').on('click', '.slot-card', function() {
                const slotIndex = $(this).data('slot-index');
                const slot = data.time_slots[slotIndex];
                
                $('.slot-card').removeClass('border-primary');
                $(this).addClass('border-primary');
                
                selectedTattooSessionSlot = {
                    date: date,
                    start_time_utc: slot.start_time_utc,
                    end_time_utc: slot.end_time_utc,
                    start_time_display: slot.start_time_display,
                    end_time_display: slot.end_time_display
                };
                
                // Update button text based on whether questions exist
                const questions = window.bookingData?.questions || [];
                const nextButton = $('#nextToQuestionsBtn');
                if (questions.length > 0) {
                    nextButton.html('Next: Answer Questions <i class="ti ti-arrow-right ms-1"></i>');
                } else {
                    nextButton.html('Next: Payment <i class="ti ti-arrow-right ms-1"></i>');
                }
                $('#nextButtonContainer').removeClass('d-none');
            });
            
            $('#nextToQuestionsBtn').off('click').on('click', function() {
                if (!selectedTattooSessionSlot) {
                    alert('Please select a tattoo session slot first.');
                    return;
                }
                
                // Check if there are questions
                const questions = window.bookingData?.questions || [];
                if (!questions || questions.length === 0) {
                    // No questions, skip directly to payment
                    proceedToPaymentStep();
                } else {
                    // Has questions, proceed to questions step
                    document.getElementById('slotsSection').classList.add('d-none');
                proceedToQuestionsStep();
                }
            });
        }
        
        function calculateMinimumTattooSessionDate() {
            if (!selectedConsultationSlot || !consultationInfo.gap_required) {
                minimumTattooSessionDate = null;
                return;
            }
            
            const consultationEnd = new Date(selectedConsultationSlot.date + 'T' + selectedConsultationSlot.end_time_utc);
            const gapValue = consultationInfo.gap_value || 0;
            const gapUnit = consultationInfo.gap_unit || 'days';
            
            let gapMinutes = 0;
            switch(gapUnit) {
                case 'minutes':
                    gapMinutes = gapValue;
                    break;
                case 'hours':
                    gapMinutes = gapValue * 60;
                    break;
                case 'days':
                    gapMinutes = gapValue * 24 * 60;
                    break;
            }
            
            const minimumDate = new Date(consultationEnd.getTime() + gapMinutes * 60000);
            minimumTattooSessionDate = minimumDate.toISOString().slice(0, 19).replace('T', ' ');
            
            // Update calendar to disable dates before minimum
            updateCalendarForGap(minimumDate);
        }
        
        function updateCalendarForGap(minimumDate) {
            // Only update calendar if we're on the tattoo session step
            // Don't modify calendar when on consultation step
            if (bookingFlowStep !== 'tattoo_session') {
                return;
            }
            
            const minDateStr = minimumDate.toISOString().split('T')[0];
            $('.calendar-day').each(function() {
                const dateStr = $(this).data('date');
                if (dateStr && dateStr < minDateStr) {
                    $(this).addClass('unavailable').removeClass('available');
                    $(this).attr('disabled', 'disabled');
                }
            });
        }
        
        function proceedToTattooSessionStep() {
            bookingFlowStep = 'tattoo_session';
            
            // Clear slot availability cache since flow changed
            clearSlotAvailabilityCache();
            
            // Clear selectedDate if no tattoo session date has been selected yet
            // This ensures the Next button doesn't show until user selects a date on tattoo session calendar
            if (!selectedTattooSessionSlot) {
                window.selectedDate = null;
            }
            
            // Explicitly hide Next button until user selects a date on tattoo session calendar
            // This ensures it's hidden even if calendar hasn't rendered yet
            $('#calendarNextButton').addClass('d-none');
            
            // Re-calculate minimum date if consultation slot is selected
            if (selectedConsultationSlot) {
                calculateMinimumTattooSessionDate();
            }
            
            // Update UI
            $('#calendarTitle').text('Step 2: Select Tattoo Session Date');
            $('#backToConsultationBtn').removeClass('d-none');
            
            // Show calendar section again
            document.getElementById('calendarSection').classList.remove('d-none');
            document.getElementById('slotsSection').classList.add('d-none');
            
            // Re-render calendar to disable dates (before consultation date and within gap period)
            renderCalendar();
            
            // Show selected consultation info
            if (selectedConsultationSlot) {
                const consultationDate = new Date(selectedConsultationSlot.date + 'T00:00:00').toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                
                // Add info card above calendar
                if (!$('#consultationSelectedInfo').length) {
                    $('#calendarSection .calendar-container').before(`
                        <div class="alert alert-success mb-3" id="consultationSelectedInfo">
                            <strong>Selected Consultation:</strong> ${consultationDate} at ${selectedConsultationSlot.start_time_display} - ${selectedConsultationSlot.end_time_display}
                            ${consultationInfo.gap_required ? `<br><strong>Minimum gap:</strong> ${consultationInfo.gap_value} ${consultationInfo.gap_unit} - Book from ${minimumTattooSessionDate ? new Date(minimumTattooSessionDate.split(' ')[0] + 'T00:00:00').toLocaleDateString() : 'consultation end'} onwards` : ''}
                        </div>
                    `);
                }
            }
            
            // Scroll to calendar
            document.getElementById('calendarSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function proceedToQuestionsStep() {
            // Check if there are questions first
            const questions = window.bookingData?.questions || [];
            if (!questions || questions.length === 0) {
                // No questions, skip directly to payment
                proceedToPaymentStep();
                return;
            }
            
            bookingFlowStep = 'questions';
            
            // Hide slots section, show questions section
            document.getElementById('slotsSection').classList.add('d-none');
            document.getElementById('questionsSection').classList.remove('d-none');
            
            // Show questions form
            setTimeout(() => {
                showQuestionsForm();
            }, 300);
            
            // Scroll to questions section
            document.getElementById('questionsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function proceedToPaymentStep() {
            bookingFlowStep = 'payment';
            
            // For separate consultation, we need to get payment info
            // Submit booking data to get payment information
            const formData = new FormData();
            formData.append('_token', csrfToken);
            
            if (consultationInfo.is_separate && selectedConsultationSlot && selectedTattooSessionSlot) {
                // Hide slots section, show payment section
                document.getElementById('slotsSection').classList.add('d-none');
                document.getElementById('paymentSection').classList.remove('d-none');
                
                // Update back button text based on whether questions exist
                const questions = window.bookingData?.questions || [];
                const backButton = document.getElementById('backToQuestionsBtn');
                if (backButton) {
                    if (questions && questions.length > 0) {
                        backButton.innerHTML = '<i class="ti ti-arrow-left me-1"></i> Back to Questions';
                    } else {
                        backButton.innerHTML = '<i class="ti ti-arrow-left me-1"></i> Change Time';
                    }
                }
                
                formData.append('consultation_slot[date]', selectedConsultationSlot.date);
                formData.append('consultation_slot[start_time_utc]', selectedConsultationSlot.start_time_utc);
                formData.append('consultation_slot[end_time_utc]', selectedConsultationSlot.end_time_utc);
                formData.append('tattoo_session_slot[date]', selectedTattooSessionSlot.date);
                formData.append('tattoo_session_slot[start_time_utc]', selectedTattooSessionSlot.start_time_utc);
                formData.append('tattoo_session_slot[end_time_utc]', selectedTattooSessionSlot.end_time_utc);
                
                // Add questions if any
                const questionsForm = document.getElementById('questionsForm');
                if (questionsForm) {
                    const formDataFromForm = new FormData(questionsForm);
                    for (let [key, value] of formDataFromForm.entries()) {
                        if (key.startsWith('questions[')) {
                            formData.append(key, value);
                        }
                    }
                }
                
                // Show loading state
                document.getElementById('paymentContainer').innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Preparing payment...</p>
                    </div>
                `;
                
                // For separate consultation, we need to calculate payment based on tattoo session slot
                // Use the tattoo session slot to get payment info
                const paymentFormData = new FormData();
                paymentFormData.append('_token', csrfToken);
                paymentFormData.append('slot[date]', selectedTattooSessionSlot.date);
                paymentFormData.append('slot[start_time_utc]', selectedTattooSessionSlot.start_time_utc);
                paymentFormData.append('slot[end_time_utc]', selectedTattooSessionSlot.end_time_utc);
                
                // Add questions if any
                if (questionsForm) {
                    const formDataFromForm = new FormData(questionsForm);
                    for (let [key, value] of formDataFromForm.entries()) {
                        if (key.startsWith('questions[')) {
                            paymentFormData.append(key, value);
                        }
                    }
                }
                
                // Submit to get payment info using regular submit endpoint with tattoo session slot
                $.ajax({
                    url: '{{ route('api.booking.submit', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                    method: 'POST',
                    data: paymentFormData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success && response.payment) {
                            window.bookingData.payment = response.payment;
                            setTimeout(() => {
                                showPaymentForm(response.payment);
                            }, 300);
                        } else {
                            document.getElementById('paymentContainer').innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    ${response.message || 'Failed to get payment information. Please try again.'}
                                </div>
                            `;
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'Failed to get payment information. Please try again.';
                        document.getElementById('paymentContainer').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="ti ti-x-circle me-2"></i>
                                ${errorMessage}
                            </div>
                        `;
                    }
                });
            } else {
                // Regular flow (non-separate consultation)
                // Hide slots section, show payment section
                document.getElementById('slotsSection').classList.add('d-none');
                document.getElementById('paymentSection').classList.remove('d-none');
                
                // Update back button text based on whether questions exist
                const questions = window.bookingData?.questions || [];
                const backButton = document.getElementById('backToQuestionsBtn');
                if (backButton) {
                    if (questions && questions.length > 0) {
                        backButton.innerHTML = '<i class="ti ti-arrow-left me-1"></i> Back to Questions';
                    } else {
                        backButton.innerHTML = '<i class="ti ti-arrow-left me-1"></i> Change Time';
                    }
                }
                
                // Submit booking data to get payment information
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('slot[date]', window.selectedSlot.date);
                formData.append('slot[start_time_utc]', window.selectedSlot.slot.start_time_utc);
                formData.append('slot[end_time_utc]', window.selectedSlot.slot.end_time_utc);
                
                // Add questions if any (though there shouldn't be any if we're here)
                const questionsForm = document.getElementById('questionsForm');
                if (questionsForm) {
                    const formDataFromForm = new FormData(questionsForm);
                    for (let [key, value] of formDataFromForm.entries()) {
                        if (key.startsWith('questions[')) {
                            formData.append(key, value);
                        }
                    }
                }
                
                // Show loading state
                document.getElementById('paymentContainer').innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Preparing payment...</p>
                    </div>
                `;
                
                // Submit to get payment info
                $.ajax({
                    url: '{{ route('api.booking.submit', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success && response.payment) {
                            window.bookingData.payment = response.payment;
                            window.bookingData.booking = response.booking_data;
                            setTimeout(() => {
                                showPaymentForm(response.payment);
                            }, 300);
                        } else {
                            document.getElementById('paymentContainer').innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    ${response.message || 'Failed to get payment information. Please try again.'}
                                </div>
                            `;
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'Failed to get payment information. Please try again.';
                        document.getElementById('paymentContainer').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="ti ti-x-circle me-2"></i>
                                ${errorMessage}
                            </div>
                        `;
                    }
                });
            }
        }
        
        function showQuestionsForm() {
            const questions = window.bookingData?.questions || [];
            const container = document.getElementById('questionsContainer');
            
            if (!questions || questions.length === 0) {
                // No questions, proceed directly to payment
                if (consultationInfo.is_separate) {
                    // For separate consultation, we'll handle payment after both slots are selected
                    proceedToPaymentStep();
                    return;
                }
                
                // Regular flow - submit booking with empty questions to get payment info
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('slot[date]', window.selectedSlot.date);
                formData.append('slot[start_time_utc]', window.selectedSlot.slot.start_time_utc);
                formData.append('slot[end_time_utc]', window.selectedSlot.slot.end_time_utc);
                
                // Show loading
                container.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                        <p>No questions to answer. Preparing payment...</p>
                </div>
            `;
                
                // Hide questions section, will show payment after
                document.getElementById('questionsSection').classList.add('d-none');
                
                // Submit to get payment info
                $.ajax({
                    url: '{{ route('api.booking.submit', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            // Store booking data
                            window.bookingData.booking = response.booking_data;
                            
                            // Check if payment is required
                            if (response.payment_required && response.payment.has_stripe_account) {
                                // Show payment step
                                setTimeout(() => {
                                    showPaymentForm(response.payment);
                                }, 300);
                            } else if (!response.payment_required) {
                                // No payment required, booking complete
                                alert('Booking submitted successfully!');
                                // TODO: Redirect to confirmation page
                } else {
                                // Payment required but Stripe not connected
                                alert('Payment is required but artist has not connected Stripe account. Please contact the artist.');
                            }
                        }
                    },
                    error: function(xhr) {
                        alert('An error occurred. Please try again.');
                        // Show slots section again
                        document.getElementById('questionsSection').classList.add('d-none');
                        document.getElementById('slotsSection').classList.remove('d-none');
                    }
                });
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
                    const maxImages = question.max_images || 1;
                    
                    questionsHTML += `
                        <div class="image-uploads-container" data-question-id="${question.id}" data-max-images="${maxImages}">
                            <div class="image-upload-item mb-3" data-index="0">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <label class="form-label mb-0">Image <span class="text-danger">*</span></label>
                                </div>
                                <input 
                                    type="file" 
                                    class="dropify image-upload-input" 
                                    id="${questionId}_0" 
                                    name="questions[${question.id}][]" 
                                    accept="image/*"
                                    data-height="200"
                                    required>
                            </div>
                            <div class="image-uploads-list"></div>
                            ${maxImages > 1 ? `
                                <button type="button" class="btn btn-sm btn-outline-primary add-more-image-btn" data-question-id="${question.id}">
                                    <i class="ti ti-plus me-1"></i> Add More Images
                                </button>
                                <small class="text-muted d-block mt-2">You can upload up to ${maxImages} images</small>
                            ` : ''}
                            <span class="field-error" id="${questionId}_error"></span>
                        </div>
                    `;
                }
                
                questionsHTML += `</div>`;
            });
            
            questionsHTML += `
                    <div class="mt-4 d-flex flex-column gap-2">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="backToSlotsBtn">
                                <i class="ti ti-arrow-left me-1"></i> Back
                            </button>
                            <button type="submit" class="btn btn-primary flex-grow-1" id="submitBookingBtn">
                                Proceed to Payment <i class="ti ti-arrow-right ms-1"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline-primary w-100" onclick="showBookingDetails('questions')">
                            <i class="ti ti-eye me-1"></i> View Details
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
            
            // Handle "Add More Images" button
            $(document).off('click', '.add-more-image-btn').on('click', '.add-more-image-btn', function() {
                const questionId = $(this).data('question-id');
                const container = $(this).closest('.image-uploads-container');
                const maxImages = parseInt(container.data('max-images')) || 1;
                const uploadsList = container.find('.image-uploads-list');
                const currentCount = container.find('.image-upload-item').length;
                
                if (currentCount >= maxImages) {
                    alert(`You can upload a maximum of ${maxImages} images.`);
                    return;
                }
                
                const index = currentCount;
                const newItemHtml = `
                    <div class="image-upload-item mb-3" data-index="${index}">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label class="form-label mb-0">Image ${index + 1}</label>
                            <button type="button" class="btn btn-sm btn-label-danger remove-image-btn ms-auto" data-question-id="${questionId}" data-index="${index}">
                                <i class="ti ti-trash me-1"></i> Remove
                            </button>
                        </div>
                        <input 
                            type="file" 
                            class="dropify image-upload-input" 
                            id="${questionId}_${index}" 
                            name="questions[${questionId}][]" 
                            accept="image/*"
                            data-height="200">
                    </div>
                `;
                
                uploadsList.append(newItemHtml);
                
                // Initialize Dropify for the new input
                $(`#${questionId}_${index}`).dropify({
                    messages: {
                        'default': 'Drag and drop an image here or click',
                        'replace': 'Drag and drop or click to replace',
                        'remove': 'Remove',
                        'error': 'Ooops, something wrong happened.'
                    }
                });
                
                // Update "Add More" button visibility
                if (container.find('.image-upload-item').length >= maxImages) {
                    container.find('.add-more-image-btn').hide();
                }
            });
            
            // Handle "Remove Image" button
            $(document).off('click', '.remove-image-btn').on('click', '.remove-image-btn', function() {
                const questionId = $(this).data('question-id');
                const index = $(this).data('index');
                const container = $(this).closest('.image-uploads-container');
                const item = $(this).closest('.image-upload-item');
                
                // Destroy Dropify instance
                const dropifyInput = item.find('.dropify');
                if (dropifyInput.length) {
                    dropifyInput.dropify('destroy');
                }
                
                // Remove the item
                item.remove();
                
                // Re-index remaining items
                container.find('.image-upload-item').each(function(idx) {
                    $(this).attr('data-index', idx);
                    const input = $(this).find('.image-upload-input');
                    const newId = `${questionId}_${idx}`;
                    input.attr('id', newId);
                    const label = $(this).find('label');
                    if (idx === 0) {
                        label.html('Image <span class="text-danger">*</span>');
                        input.prop('required', true);
                } else {
                        label.text(`Image ${idx + 1}`);
                        input.prop('required', false);
                    }
                });
                
                // Show "Add More" button if under limit
                const maxImages = parseInt(container.data('max-images')) || 1;
                if (container.find('.image-upload-item').length < maxImages) {
                    container.find('.add-more-image-btn').show();
                }
            });
            
            // Update "Add More" button visibility on page load
            $('.image-uploads-container').each(function() {
                const container = $(this);
                const maxImages = parseInt(container.data('max-images')) || 1;
                const currentCount = container.find('.image-upload-item').length;
                
                if (currentCount >= maxImages) {
                    container.find('.add-more-image-btn').hide();
                }
            });
            
            // Questions section is already visible
            
            // Handle back button
            // Back to calendar button
            $(document).off('click', '#backToCalendarBtn').on('click', '#backToCalendarBtn', function() {
                document.getElementById('slotsSection').classList.add('d-none');
                document.getElementById('calendarSection').classList.remove('d-none');
                document.getElementById('calendarSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            
            // Back to slots button
            $(document).off('click', '#backToSlotsBtn').on('click', '#backToSlotsBtn', function() {
                document.getElementById('questionsSection').classList.add('d-none');
                document.getElementById('slotsSection').classList.remove('d-none');
                document.getElementById('slotsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            
            // Back to questions button (from payment)
            $(document).off('click', '#backToQuestionsBtn').on('click', '#backToQuestionsBtn', function() {
                // Hide payment section
                document.getElementById('paymentSection').classList.add('d-none');
                
                // Check if there are questions to determine where to go back
                const questions = window.bookingData?.questions || [];
                
                if (questions && questions.length > 0) {
                    // Go back to questions section
                    document.getElementById('questionsSection').classList.remove('d-none');
                    document.getElementById('questionsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    // No questions, go back to slots section
                    document.getElementById('slotsSection').classList.remove('d-none');
                    document.getElementById('slotsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
            
            // Calendar next button (when date is selected)
            $(document).off('click', '#nextToSlotsBtn').on('click', '#nextToSlotsBtn', function() {
                const selectedDate = $('.calendar-day.available.border-primary').data('date');
                if (!selectedDate) {
                    alert('Please select a date first.');
                    return;
                }
                // Trigger date click to load slots
                $('.calendar-day.available.border-primary').trigger('click');
            });
            
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
                
                // Validate image uploads - ensure first image is uploaded for image questions
                let hasErrors = false;
                $('.image-uploads-container').each(function() {
                    const container = $(this);
                    const questionId = container.data('question-id');
                    const firstInput = container.find('.image-upload-item:first .image-upload-input');
                    
                    // Check if first image is uploaded
                    if (firstInput.length && !firstInput[0].files || firstInput[0].files.length === 0) {
                        hasErrors = true;
                        container.find('.field-error').text('Please upload at least one image.').show();
                        firstInput.closest('.image-upload-item').addClass('border border-danger rounded p-2');
                    } else {
                        container.find('.field-error').hide();
                        firstInput.closest('.image-upload-item').removeClass('border border-danger rounded p-2');
                    }
                });
                
                if (hasErrors) {
                    return false;
                }
                
                const formData = new FormData(this);
                
                // Add CSRF token
                formData.append('_token', csrfToken);
                
                // Handle separate consultation timing
                if (consultationInfo.is_separate && selectedConsultationSlot && selectedTattooSessionSlot) {
                    // Add consultation slot data
                    formData.append('consultation_slot[date]', selectedConsultationSlot.date);
                    formData.append('consultation_slot[start_time_utc]', selectedConsultationSlot.start_time_utc);
                    formData.append('consultation_slot[end_time_utc]', selectedConsultationSlot.end_time_utc);
                    
                    // Add tattoo session slot data
                    formData.append('tattoo_session_slot[date]', selectedTattooSessionSlot.date);
                    formData.append('tattoo_session_slot[start_time_utc]', selectedTattooSessionSlot.start_time_utc);
                    formData.append('tattoo_session_slot[end_time_utc]', selectedTattooSessionSlot.end_time_utc);
                } else {
                    // Regular flow - add slot data
                    formData.append('slot[date]', window.selectedSlot.date);
                    formData.append('slot[start_time_utc]', window.selectedSlot.slot.start_time_utc);
                    formData.append('slot[end_time_utc]', window.selectedSlot.slot.end_time_utc);
                }
                
                // Disable submit button
                const submitBtn = $('#submitBookingBtn');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');
                
                // Determine which endpoint to use
                const submitUrl = consultationInfo.is_separate && selectedConsultationSlot && selectedTattooSessionSlot
                    ? '{{ route('api.booking.separate', ['tattoo_id' => $tattoo['tattoo_id']]) }}'
                    : '{{ route('api.booking.submit', ['tattoo_id' => $tattoo['tattoo_id']]) }}';
                
                // Submit via AJAX
                $.ajax({
                    url: submitUrl,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            // Store booking data including answers
                            if (consultationInfo.is_separate) {
                                window.bookingData.consultationBooking = {
                                    id: response.consultation_booking_id,
                                    time: response.consultation_time
                                };
                                window.bookingData.tattooSessionBooking = {
                                    id: response.tattoo_session_booking_id,
                                    time: response.tattoo_session_time
                                };
                            } else {
                                window.bookingData.booking = response.booking_data;
                                window.bookingData.questionsAnswers = response.booking_data?.answers || {};
                            }
                            
                            // Store payment data
                            if (response.payment) {
                                window.bookingData.payment = response.payment;
                            }
                            
                            // Close questions offcanvas
                            const questionsOffcanvasInstance = bootstrap.Offcanvas.getInstance(document.getElementById('questionsOffcanvas'));
                            if (questionsOffcanvasInstance) {
                                questionsOffcanvasInstance.hide();
                            }
                            
                            // For separate consultation, we need to get payment info
                            if (consultationInfo.is_separate) {
                                proceedToPaymentStep();
                            } else if (response.payment_required && response.payment.has_stripe_account) {
                                // Show payment step after a short delay
                                setTimeout(() => {
                                    showPaymentForm(response.payment);
                                }, 300);
                            } else if (!response.payment_required) {
                                // No payment required, booking complete
                                alert('Booking submitted successfully!');
                                // TODO: Redirect to confirmation page
                            } else {
                                // Payment required but Stripe not connected
                                alert('Payment is required but artist has not connected Stripe account. Please contact the artist.');
                            }
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
                        submitBtn.prop('disabled', false).html('Proceed to Payment <i class="ti ti-arrow-right ms-1"></i>');
                    }
                });
            });
        }
        
        function showPaymentForm(paymentInfo) {
            const container = document.getElementById('paymentContainer');
            const currencySymbol = getCurrencySymbol(paymentInfo.currency);
            const depositTypeText = paymentInfo.deposit_type === 'percentage' 
                ? `${paymentInfo.deposit_value}% of ${currencySymbol}${paymentInfo.tattoo_price.toFixed(2)}` 
                : 'Fixed amount';
            
            // Platform fee
            const platformFee = paymentInfo.platform_fee || 10.00;
            
            // Store payment info globally for checkbox handler
            window.paymentInfo = paymentInfo;
            window.currentPaymentAmount = paymentInfo.deposit_amount;
            window.platformFee = platformFee;
            window.platformFee = platformFee;
            
            let paymentHTML = `
                <div class="mb-4">
                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="ti ti-info-circle me-2"></i>Payment Required</h6>
                        <p class="mb-1">A deposit is required to secure your booking.</p>
                </div>
                </div>
                
                <div class="mb-4">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Booking Summary</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tattoo Price:</span>
                                <strong>${currencySymbol}${paymentInfo.tattoo_price.toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Deposit Type:</span>
                                <span>${depositTypeText}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold" id="paymentLabel">Deposit Amount:</span>
                                <strong id="paymentAmount">${currencySymbol}${paymentInfo.deposit_amount.toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Platform Fee:</span>
                                <span>${currencySymbol}${platformFee.toFixed(2)}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total Amount:</span>
                                <strong class="text-primary fs-5" id="totalPaymentAmount">${currencySymbol}${(paymentInfo.deposit_amount + platformFee).toFixed(2)}</strong>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="payFullCheckbox">
                                <label class="form-check-label" for="payFullCheckbox">
                                    <strong>Pay Full Amount</strong> (${currencySymbol}${paymentInfo.tattoo_price.toFixed(2)})
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">Currency: ${paymentInfo.currency}</small>
                        </div>
                    </div>
                </div>
                
                <form id="paymentForm">
                    <div id="card-element" class="mb-3">
                        <!-- Stripe Elements will create form elements here -->
                    </div>
                    <div id="card-errors" class="text-danger mb-3" role="alert"></div>
                    
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="backToQuestionsBtn">
                                <i class="ti ti-arrow-left me-1"></i> Back
                            </button>
                            <button type="submit" class="btn btn-primary flex-grow-1" id="submitPaymentBtn" style="display: none;">
                                <i class="ti ti-credit-card me-1"></i> Pay <span id="paymentButtonAmount">${currencySymbol}${(paymentInfo.deposit_amount + platformFee).toFixed(2)}</span>
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline-primary w-100" onclick="showBookingDetails('payment')">
                            <i class="ti ti-eye me-1"></i> View Details
                        </button>
                    </div>
                </form>
            `;
            
            container.innerHTML = paymentHTML;
            
            // Store payment info for details view
            if (!window.bookingData.payment) {
                window.bookingData.payment = paymentInfo;
            }
            window.bookingData.payment.tattoo_price = paymentInfo.tattoo_price;
            window.bookingData.payment.deposit_amount = paymentInfo.deposit_amount;
            window.bookingData.payment.currency = paymentInfo.currency;
            window.bookingData.payment.platform_fee = platformFee;
            
            // Handle "Pay Full" checkbox
            $('#payFullCheckbox').on('change', function() {
                const isChecked = $(this).is(':checked');
                const currencySymbol = getCurrencySymbol(paymentInfo.currency);
                const platformFee = window.platformFee || 10.00;
                
                // Store payment type for details view
                window.bookingData.isPayFull = isChecked;
                
                if (isChecked) {
                    window.currentPaymentAmount = paymentInfo.tattoo_price;
                    $('#paymentLabel').text('Full Amount:');
                    $('#paymentAmount').text(`${currencySymbol}${paymentInfo.tattoo_price.toFixed(2)}`);
                    const totalAmount = paymentInfo.tattoo_price + platformFee;
                    $('#totalPaymentAmount').text(`${currencySymbol}${totalAmount.toFixed(2)}`);
                    $('#paymentButtonAmount').text(`${currencySymbol}${totalAmount.toFixed(2)}`);
                } else {
                    window.currentPaymentAmount = paymentInfo.deposit_amount;
                    $('#paymentLabel').text('Deposit Amount:');
                    $('#paymentAmount').text(`${currencySymbol}${paymentInfo.deposit_amount.toFixed(2)}`);
                    const totalAmount = paymentInfo.deposit_amount + platformFee;
                    $('#totalPaymentAmount').text(`${currencySymbol}${totalAmount.toFixed(2)}`);
                    $('#paymentButtonAmount').text(`${currencySymbol}${totalAmount.toFixed(2)}`);
                }
                
                // Recreate payment intent with new amount
                createPaymentIntent();
            });
            
            // Initialize Stripe
            const stripe = Stripe('{{ env('STRIPE_KEY') }}');
            let elements;
            let cardElement;
            let currentClientSecret = null;
            
            // Form submit handler (defined before createPaymentIntent so it's accessible)
            const formSubmitHandler = async function(e) {
                e.preventDefault();
                
                // Clear previous errors
                $('#card-errors').text('').hide();
                
                if (!currentClientSecret) {
                    $('#card-errors').text('Payment not initialized. Please wait...').show();
                return;
            }
            
                const submitBtn = $('#submitPaymentBtn');
                const currencySymbol = getCurrencySymbol(paymentInfo.currency);
                const amount = window.currentPaymentAmount || paymentInfo.deposit_amount;
                const platformFee = window.platformFee || 10.00;
                const totalAmount = amount + platformFee;
                
                if (!cardElement) {
                    $('#card-errors').text('Card element not loaded. Please refresh the page.').show();
                    submitBtn.prop('disabled', false).html(`<i class="ti ti-credit-card me-1"></i> Pay <span id="paymentButtonAmount">${currencySymbol}${totalAmount.toFixed(2)}</span>`);
                    return;
                }
                
                // Verify card element container exists
                const cardElementContainer = document.getElementById('card-element');
                if (!cardElementContainer) {
                    $('#card-errors').text('Card input field not found. Please refresh the page.').show();
                    submitBtn.prop('disabled', false).html(`<i class="ti ti-credit-card me-1"></i> Pay <span id="paymentButtonAmount">${currencySymbol}${totalAmount.toFixed(2)}</span>`);
                    return;
                }
                
                // Disable button and show processing
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');
                
                try {
                    // First, create payment method to validate card details
                    const {error: pmError, paymentMethod} = await stripe.createPaymentMethod({
                        type: 'card',
                        card: cardElement,
                    });
                    
                    if (pmError) {
                        // Show validation error
                        $('#card-errors').text(pmError.message).show();
                        submitBtn.prop('disabled', false).html(`<i class="ti ti-credit-card me-1"></i> Pay <span id="paymentButtonAmount">${currencySymbol}${totalAmount.toFixed(2)}</span>`);
                        return;
                    }
                    
                    // If payment method created successfully, confirm payment
                    const {error, paymentIntent} = await stripe.confirmCardPayment(currentClientSecret, {
                        payment_method: paymentMethod.id,
                    });
                    
                    if (error) {
                        // Show error to customer
                        $('#card-errors').text(error.message).show();
                        submitBtn.prop('disabled', false).html(`<i class="ti ti-credit-card me-1"></i> Pay <span id="paymentButtonAmount">${currencySymbol}${totalAmount.toFixed(2)}</span>`);
                    } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                        // Payment succeeded - save booking and send emails
                        container.innerHTML = `
                            <div class="text-center py-5">
                <div class="mb-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Processing...</span>
                </div>
                                </div>
                                <h5 class="mb-2">Payment Successful!</h5>
                                <p class="text-muted mb-4">Confirming your booking...</p>
                            </div>
                        `;
                        
                        // Get customer information - use authenticated user if available
                        let customerName, customerEmail;
                        
                        if (currentUser.isAuthenticated && currentUser.name && currentUser.email) {
                            // Use authenticated user data
                            customerName = currentUser.name;
                            customerEmail = currentUser.email;
                        } else {
                            // Collect customer information via prompts (for guest users)
                            customerName = prompt('Please enter your name:') || '';
                            customerEmail = prompt('Please enter your email address:') || '';
                            
                            if (!customerName || customerName.trim() === '') {
                                customerName = 'Guest';
                            }
                            
                            if (!customerEmail || !customerEmail.includes('@')) {
                                alert('Please provide a valid email address to receive booking confirmation.');
                                // Retry email collection
                                const retryEmail = prompt('Please enter your email address:') || '';
                                if (!retryEmail || !retryEmail.includes('@')) {
                                    container.innerHTML = `
                        <div class="alert alert-danger">
                                            <i class="ti ti-alert-circle me-2"></i>
                                            Email address is required. Please contact support.
                        </div>
                    `;
                                    return;
                                }
                                customerEmail = retryEmail;
                            }
                        }
                        
                        // Prepare booking data
                        let bookingData;
                        
                        if (consultationInfo.is_separate && selectedConsultationSlot && selectedTattooSessionSlot) {
                            // Separate consultation timing flow
                            bookingData = {
                                _token: csrfToken,
                                payment_intent_id: paymentIntent.id,
                                consultation_slot: {
                                    date: selectedConsultationSlot.date,
                                    start_time_utc: selectedConsultationSlot.start_time_utc,
                                    end_time_utc: selectedConsultationSlot.end_time_utc,
                                },
                                tattoo_session_slot: {
                                    date: selectedTattooSessionSlot.date,
                                    start_time_utc: selectedTattooSessionSlot.start_time_utc,
                                    end_time_utc: selectedTattooSessionSlot.end_time_utc,
                                },
                                customer_name: customerName,
                                customer_email: customerEmail,
                                amount: parseFloat(window.currentPaymentAmount || paymentInfo.deposit_amount),
                                currency: paymentInfo.currency,
                                full_amount_paid: $('#payFullCheckbox').is(':checked') ? 1 : 0,
                                questions: window.bookingData?.questionsAnswers || {},
                            };
                        } else {
                            // Regular flow
                            bookingData = {
                                _token: csrfToken,
                                payment_intent_id: paymentIntent.id,
                                slot: {
                                    date: window.selectedSlot.date,
                                    start_time_utc: window.selectedSlot.slot.start_time_utc,
                                    end_time_utc: window.selectedSlot.slot.end_time_utc,
                                },
                                customer_name: customerName,
                                customer_email: customerEmail,
                                amount: parseFloat(window.currentPaymentAmount || paymentInfo.deposit_amount),
                                currency: paymentInfo.currency,
                                full_amount_paid: $('#payFullCheckbox').is(':checked') ? 1 : 0,
                                questions: window.bookingData?.booking?.answers || window.bookingData?.questionsAnswers || {},
                            };
                        }
                        
                        // Log booking data for debugging
                        console.log('Booking data being sent:', bookingData);
                        
                        // Determine which endpoint to use
                        const confirmUrl = consultationInfo.is_separate && selectedConsultationSlot && selectedTattooSessionSlot
                            ? '{{ route('api.booking.separate', ['tattoo_id' => $tattoo['tattoo_id']]) }}'
                            : '{{ route('api.booking.confirm', ['tattoo_id' => $tattoo['tattoo_id']]) }}';
                        
                        // Save booking and send emails
                        $.ajax({
                            url: confirmUrl,
                            method: 'POST',
                            data: bookingData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function(response) {
                                if (response.success) {
                                    let confirmationHTML = `
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="ti ti-circle-check text-success" style="font-size: 4rem;"></i>
                                            </div>
                                            <h5 class="mb-2">Booking${consultationInfo.is_separate ? 's' : ''} Confirmed!</h5>
                                            <p class="text-muted mb-3">Your booking${consultationInfo.is_separate ? 's have' : ' has'} been confirmed and confirmation emails have been sent.</p>
                                    `;
                                    
                                    if (consultationInfo.is_separate) {
                                        confirmationHTML += `
                                            <div class="alert alert-info mb-3 text-start">
                                                <strong>Consultation Booking:</strong> #${response.consultation_booking_id}<br>
                                                ${response.consultation_time ? `${response.consultation_time.start} - ${response.consultation_time.end}` : ''}
                                                ${response.consultation_meet_link ? `<br><a href="${response.consultation_meet_link}" target="_blank" class="btn btn-sm btn-primary mt-2">Join Consultation Meeting</a>` : ''}
                                            </div>
                                            <div class="alert alert-success mb-3 text-start">
                                                <strong>Tattoo Session Booking:</strong> #${response.tattoo_session_booking_id}<br>
                                                ${response.tattoo_session_time ? `${response.tattoo_session_time.start} - ${response.tattoo_session_time.end}` : ''}
                                            </div>
                                        `;
                                    } else {
                                        confirmationHTML += `<p class="text-muted small mb-4">Booking ID: #${response.booking_id}</p>`;
                                    }
                                    
                                    confirmationHTML += `
                                            <button type="button" class="btn btn-primary" onclick="location.reload()">
                                                Done
                                            </button>
                                        </div>
                                    `;
                                    
                                    container.innerHTML = confirmationHTML;
                                } else {
                                    container.innerHTML = `
                                        <div class="alert alert-warning">
                                            <i class="ti ti-alert-triangle me-2"></i>
                                            Payment successful but booking confirmation failed: ${response.message || 'Unknown error'}
                                        </div>
                                    `;
                                }
                            },
                            error: function(xhr) {
                                const errorMessage = xhr.responseJSON?.message || 'Failed to confirm booking. Please contact support.';
                                container.innerHTML = `
                    <div class="alert alert-danger">
                                        <i class="ti ti-alert-circle me-2"></i>
                                        Payment successful but booking confirmation failed: ${errorMessage}
                                        <br><br>
                                        <small>Booking ID: ${paymentIntent.id}</small>
                                        <br><small>Please contact support with this information.</small>
                    </div>
                `;
                            }
                        });
                    }
                } catch (err) {
                    // Handle any unexpected errors
                    $('#card-errors').text('An error occurred. Please check your card details and try again.').show();
                    submitBtn.prop('disabled', false).html(`<i class="ti ti-credit-card me-1"></i> Pay <span id="paymentButtonAmount">${currencySymbol}${totalAmount.toFixed(2)}</span>`);
                }
            };
            
            // Function to create payment intent
            function createPaymentIntent() {
                const amount = window.currentPaymentAmount || paymentInfo.deposit_amount;
                const platformFee = window.platformFee || 10.00;
                const totalAmount = amount + platformFee;
                
                // Hide payment button while loading
                $('#submitPaymentBtn').hide();
                
                $.ajax({
                    url: '{{ route('api.booking.payment-intent', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        amount: amount, // Send the base amount (deposit or full), platform fee will be added server-side
                        currency: paymentInfo.currency
                    },
                    success: function(response) {
                        if (response.success) {
                            currentClientSecret = response.client_secret;
                            
                            // Unmount existing card element if it exists
                            if (cardElement) {
                                try {
                                    cardElement.unmount();
                                    cardElement = null; // Clear reference
                                } catch(e) {
                                    // Element might not be mounted yet or already unmounted
                                    cardElement = null;
                                }
                            }
                            
                            // Clear the card element container to ensure clean mount
                            const cardElementContainer = document.getElementById('card-element');
                            if (cardElementContainer) {
                                cardElementContainer.innerHTML = ''; // Clear any existing content
                            }
                            
                            // Initialize Stripe Elements
                            elements = stripe.elements({
                                clientSecret: response.client_secret
                            });
                            
                            cardElement = elements.create('card', {
                                style: {
                                    base: {
                                        fontSize: '16px',
                                        color: '#424770',
                                        '::placeholder': {
                                            color: '#aab7c4',
                                        },
                                    },
                                    invalid: {
                                        color: '#9e2146',
                                    },
                                },
                            });
                            
                            cardElement.mount('#card-element');
                            
                            // Show payment button after card element is mounted
                            $('#submitPaymentBtn').show();
                            
                            // Handle real-time validation errors
                            cardElement.on('change', function(event) {
                                const displayError = $('#card-errors');
                                if (event.error) {
                                    displayError.text(event.error.message).show();
                                } else {
                                    displayError.text('').hide();
                                }
                            });
                            
                            // Handle card completion
                            cardElement.on('ready', function() {
                                $('#card-errors').text('').hide();
                            });
                            
                            // Remove existing form submit listener using jQuery (more reliable)
                            // This avoids replacing the form which would unmount the Stripe element
                            $('#paymentForm').off('submit', formSubmitHandler).on('submit', formSubmitHandler);
                            
                            // Ensure button is visible
                            $('#submitPaymentBtn').show();
                        } else {
                            container.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    ${response.message || 'Failed to initialize payment. Please try again.'}
                    </div>
                `;
                        }
                    },
                    error: function(xhr) {
                        container.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="ti ti-alert-circle me-2"></i>
                                Failed to initialize payment. Please try again.
                            </div>
                        `;
                    }
                });
        }
        
            // Create initial payment intent
            createPaymentIntent();
            
            // Hide questions section, show payment section
            document.getElementById('questionsSection').classList.add('d-none');
            document.getElementById('paymentSection').classList.remove('d-none');
            
            // Update back button text based on whether questions exist
            const questions = window.bookingData?.questions || [];
            const backButton = document.getElementById('backToQuestionsBtn');
            if (backButton) {
                if (questions && questions.length > 0) {
                    backButton.innerHTML = '<i class="ti ti-arrow-left me-1"></i> Back to Questions';
                } else {
                    backButton.innerHTML = '<i class="ti ti-arrow-left me-1"></i> Change Time';
                }
            }
            
            // Scroll to payment section
            document.getElementById('paymentSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Handle back button - check if there are questions
            $(document).off('click', '#backToQuestionsBtn').on('click', '#backToQuestionsBtn', function() {
                // Hide payment section
                document.getElementById('paymentSection').classList.add('d-none');
                
                const questions = window.bookingData?.questions || [];
                
                    if (questions && questions.length > 0) {
                    // Go back to questions section
                    document.getElementById('questionsSection').classList.remove('d-none');
                    document.getElementById('questionsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                    // No questions, go back to slots section
                    document.getElementById('slotsSection').classList.remove('d-none');
                    document.getElementById('slotsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
            });
        }
        
        // Show booking details modal based on current step
        function showBookingDetails(step) {
            const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
            const content = document.getElementById('bookingDetailsContent');
            let detailsHTML = '';
            
            // Get tattoo info (always available)
            const tattoo = window.bookingData?.tattoo || @json($tattoo);
            const artist = window.bookingData?.artist || @json($artist);
            
            detailsHTML += `
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="ti ti-palette me-2"></i>Tattoo Information</h6>
                </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-3 text-center mb-3 mb-md-0">
                                ${tattoo && tattoo.field_tattoo_image_preview ? `
                                    <img src="${tattoo.field_tattoo_image_preview}" alt="${tattoo.title || 'Tattoo'}" class="img-fluid rounded" style="max-height: 150px;">
                                ` : ''}
                            </div>
                            <div class="col-12 col-md-9">
                                <h5 class="mb-2">${tattoo?.title || '{{ $tattoo['title'] ?? 'Tattoo' }}'}</h5>
                                <p class="text-muted mb-1"><strong>Artist:</strong> ${artist?.display_name || artist?.username || '{{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}'}</p>
                                ${tattoo?.session_time_h ? `<p class="text-muted mb-1"><strong>Session Duration:</strong> ${tattoo.session_time_h} hour(s)</p>` : ''}
                                ${tattoo?.cost_per_session ? `<p class="text-muted mb-0"><strong>Price:</strong> ${getCurrencySymbol(tattoo.currency || 'USD')}${tattoo.cost_per_session}</p>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Step 1: Slots - Show date if selected
            if (step === 'slots') {
                const date = window.bookingData?.date;
                if (date) {
                    const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    detailsHTML += `
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="ti ti-calendar me-2"></i>Selected Date</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><strong>Date:</strong> ${formattedDate}</p>
                            </div>
                        </div>
                    `;
                } else {
                    detailsHTML += `
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>Please select a date to see booking details.
                    </div>
                `;
                }
            }
            
            // Step 2: Questions - Show date and time slot
            if (step === 'questions') {
                const selectedSlot = window.selectedSlot;
                if (selectedSlot) {
                    const formattedDate = new Date(selectedSlot.date + 'T00:00:00').toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    detailsHTML += `
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="ti ti-calendar-event me-2"></i>Selected Time Slot</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Date:</strong> ${formattedDate}</p>
                                <p class="mb-1"><strong>Time:</strong> ${selectedSlot.slot.start_time_display} - ${selectedSlot.slot.end_time_display}</p>
                                <p class="mb-0"><strong>Duration:</strong> ${selectedSlot.slot.total_duration_minutes ? (selectedSlot.slot.total_duration_minutes / 60).toFixed(2) : selectedSlot.slot.duration_hours} hour(s)</p>
                            </div>
                        </div>
                    `;
                } else {
                    detailsHTML += `
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>Please select a time slot to see booking details.
                        </div>
                    `;
                }
            }
            
            // Step 3: Payment - Show all details including questions answered
            if (step === 'payment') {
                const selectedSlot = window.selectedSlot;
                const questionsAnswers = window.bookingData?.questionsAnswers || {};
                
                if (selectedSlot) {
                    const formattedDate = new Date(selectedSlot.date + 'T00:00:00').toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    detailsHTML += `
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="ti ti-calendar-event me-2"></i>Selected Time Slot</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Date:</strong> ${formattedDate}</p>
                                <p class="mb-1"><strong>Time:</strong> ${selectedSlot.slot.start_time_display} - ${selectedSlot.slot.end_time_display}</p>
                                <p class="mb-0"><strong>Duration:</strong> ${selectedSlot.slot.duration_hours} hour(s)</p>
                            </div>
                </div>
            `;
                }
                
                // Show questions and answers if available
                const questions = window.bookingData?.questions || [];
                if (questions.length > 0 && Object.keys(questionsAnswers).length > 0) {
                    detailsHTML += `
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="ti ti-question-mark me-2"></i>Questions & Answers</h6>
                            </div>
                            <div class="card-body">
                    `;
                    
                    questions.forEach(question => {
                        const answer = questionsAnswers[question.id];
                        if (answer) {
                            let answerDisplay = answer;
                            
                            // Handle image answers
                            if (question.type === 'image') {
                                if (Array.isArray(answer)) {
                                    answerDisplay = '<div class="d-flex flex-wrap gap-2">' + answer.map(img => {
                                        const imgUrl = img.startsWith('http') ? img : (img.startsWith('/') ? window.location.origin + img : window.location.origin + '/' + img);
                                        return `<img src="${imgUrl}" alt="Answer" class="img-thumbnail" style="max-height: 150px; max-width: 150px; object-fit: cover; cursor: pointer;" onclick="window.open('${imgUrl}', '_blank')">`;
                                    }).join('') + '</div>';
                                } else if (answer) {
                                    const imgUrl = answer.startsWith('http') ? answer : (answer.startsWith('/') ? window.location.origin + answer : window.location.origin + '/' + answer);
                                    answerDisplay = `<img src="${imgUrl}" alt="Answer" class="img-thumbnail" style="max-height: 200px; max-width: 200px; object-fit: cover; cursor: pointer;" onclick="window.open('${imgUrl}', '_blank')">`;
                                } else {
                                    answerDisplay = '<span class="text-muted">No image uploaded</span>';
                                }
                            }
                            
                            detailsHTML += `
                                <div class="mb-3 pb-3 border-bottom">
                                    <p class="mb-1"><strong>${question.question}</strong></p>
                                    <div class="text-muted">${answerDisplay}</div>
                                </div>
                            `;
                        }
                    });
                    
                    detailsHTML += `
                            </div>
                        </div>
                    `;
                }
                
                // Show payment details if available
                const paymentData = window.bookingData?.payment;
                const isPayFull = $('#payFullCheckbox').is(':checked') || window.bookingData?.isPayFull || false;
                const currentPaymentAmount = window.currentPaymentAmount || paymentData?.deposit_amount || paymentData?.deposit || 0;
                const platformFee = window.platformFee || paymentData?.platform_fee || 10.00;
                const tattooPrice = paymentData?.tattoo_price || 0;
                
                if (paymentData) {
                    const currencySymbol = getCurrencySymbol(paymentData.currency || 'USD');
                    const paymentType = isPayFull ? 'Full Amount' : 'Deposit Amount';
                    const paymentAmount = isPayFull ? tattooPrice : currentPaymentAmount;
                    const totalAmount = paymentAmount + platformFee;
                    
                    detailsHTML += `
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="ti ti-currency-dollar me-2"></i>Payment Information</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Payment Type:</strong> ${isPayFull ? '<span class="badge bg-success">Full Amount</span>' : '<span class="badge bg-info">Deposit</span>'}</p>
                                ${tattooPrice > 0 ? `<p class="mb-1"><strong>Tattoo Price:</strong> ${currencySymbol}${tattooPrice.toFixed(2)}</p>` : ''}
                                <p class="mb-1"><strong>${paymentType}:</strong> ${currencySymbol}${paymentAmount.toFixed(2)}</p>
                                <p class="mb-1"><strong>Platform Fee:</strong> ${currencySymbol}${platformFee.toFixed(2)}</p>
                                <p class="mb-0"><strong class="text-primary">Total Amount:</strong> <span class="fs-5 text-primary">${currencySymbol}${totalAmount.toFixed(2)}</span></p>
                                ${paymentData.currency ? `<p class="mb-0 mt-2"><small class="text-muted">Currency: ${paymentData.currency}</small></p>` : ''}
                            </div>
                        </div>
                    `;
                }
            }
            
            content.innerHTML = detailsHTML;
            modal.show();
        }
        
        function getCurrencySymbol(currency) {
            const symbols = {
                'USD': '$',
                'EUR': '€',
                'GBP': '£',
                'AED': 'AED ',
                'SAR': 'SAR ',
                'INR': '₹',
                'JPY': '¥',
                'CAD': 'C$',
                'AUD': 'A$',
            };
            return symbols[currency.toUpperCase()] || currency.toUpperCase() + ' ';
        }
    </script>
</body>
</html>

