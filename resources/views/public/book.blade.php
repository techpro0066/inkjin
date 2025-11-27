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
            <div class="d-flex gap-2">
                {{-- <button type="button" class="btn btn-sm btn-outline-primary" id="viewDetailsSlotsBtn" onclick="showBookingDetails('slots')">
                    <i class="ti ti-eye me-1"></i> View Details
                </button> --}}
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
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
            <div class="d-flex gap-2">
                {{-- <button type="button" class="btn btn-sm btn-outline-primary" id="viewDetailsQuestionsBtn" onclick="showBookingDetails('questions')">
                    <i class="ti ti-eye me-1"></i> View Details
                </button> --}}
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
        </div>
        <div class="offcanvas-body">
            <div id="questionsContainer">
                <!-- Questions form will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Payment Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="paymentOffcanvas" aria-labelledby="paymentOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="paymentOffcanvasLabel">Complete Payment</h5>
            <div class="d-flex gap-2">
                {{-- <button type="button" class="btn btn-sm btn-outline-primary" id="viewDetailsPaymentBtn" onclick="showBookingDetails('payment')">
                    <i class="ti ti-eye me-1"></i> View Details
                </button> --}}
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
        </div>
        <div class="offcanvas-body">
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
                // No questions, proceed directly to payment
                // Submit booking with empty questions to get payment info
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
            
                // Close questions offcanvas
                const questionsOffcanvasInstance = bootstrap.Offcanvas.getInstance(document.getElementById('questionsOffcanvas'));
                if (questionsOffcanvasInstance) {
                    questionsOffcanvasInstance.hide();
                }
                
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
                        // Show slots offcanvas again
                        setTimeout(() => {
                            const slotsOffcanvas = new bootstrap.Offcanvas(document.getElementById('slotsOffcanvas'));
                            slotsOffcanvas.show();
                        }, 300);
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
                
                // Add slot data
                formData.append('slot[date]', window.selectedSlot.date);
                formData.append('slot[start_time_utc]', window.selectedSlot.slot.start_time_utc);
                formData.append('slot[end_time_utc]', window.selectedSlot.slot.end_time_utc);
                
                // Disable submit button
                const submitBtn = $('#submitBookingBtn');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');
                
                // Submit via AJAX
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
                            // Store booking data including answers
                            window.bookingData.booking = response.booking_data;
                            window.bookingData.questionsAnswers = response.booking_data.answers || {};
                            
                            // Store payment data
                            if (response.payment) {
                                window.bookingData.payment = response.payment;
                            }
                            
                            // Close questions offcanvas
                            const questionsOffcanvasInstance = bootstrap.Offcanvas.getInstance(document.getElementById('questionsOffcanvas'));
                            if (questionsOffcanvasInstance) {
                                questionsOffcanvasInstance.hide();
                            }
                            
                            // Check if payment is required
                            if (response.payment_required && response.payment.has_stripe_account) {
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
                        const bookingData = {
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
                            full_amount_paid: $('#payFullCheckbox').is(':checked') ? 1 : 0, // Send as 1/0 for Laravel boolean validation
                            questions: window.bookingData?.booking?.answers || window.bookingData?.questionsAnswers || {},
                        };
                        
                        // Log booking data for debugging
                        console.log('Booking data being sent:', bookingData);
                        
                        // Save booking and send emails
                        $.ajax({
                            url: '{{ route('api.booking.confirm', ['tattoo_id' => $tattoo['tattoo_id']]) }}',
                            method: 'POST',
                            data: bookingData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function(response) {
                                if (response.success) {
                                    container.innerHTML = `
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="ti ti-circle-check text-success" style="font-size: 4rem;"></i>
                            </div>
                                            <h5 class="mb-2">Booking Confirmed!</h5>
                                            <p class="text-muted mb-3">Your booking has been confirmed and confirmation emails have been sent.</p>
                                            <p class="text-muted small mb-4">Booking ID: #${response.booking_id}</p>
                                            <button type="button" class="btn btn-primary" onclick="location.reload()">
                                                Done
                                            </button>
                        </div>
                                    `;
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
            
            // Show payment offcanvas
            const paymentOffcanvas = new bootstrap.Offcanvas(document.getElementById('paymentOffcanvas'));
            paymentOffcanvas.show();
            
            // Handle back button - check if there are questions
            $(document).off('click', '#backToQuestionsBtn').on('click', '#backToQuestionsBtn', function() {
                const paymentOffcanvasInstance = bootstrap.Offcanvas.getInstance(document.getElementById('paymentOffcanvas'));
                if (paymentOffcanvasInstance) {
                    paymentOffcanvasInstance.hide();
                }
                
                const questions = window.bookingData?.questions || [];
                
                setTimeout(() => {
                    if (questions && questions.length > 0) {
                        // Go back to questions
                        const questionsOffcanvas = new bootstrap.Offcanvas(document.getElementById('questionsOffcanvas'));
                        questionsOffcanvas.show();
                    } else {
                        // No questions, go back to slots
                        const slotsOffcanvas = new bootstrap.Offcanvas(document.getElementById('slotsOffcanvas'));
                        slotsOffcanvas.show();
                    }
                }, 300);
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
                                <p class="mb-0"><strong>Duration:</strong> ${selectedSlot.slot.duration_hours} hour(s)</p>
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

